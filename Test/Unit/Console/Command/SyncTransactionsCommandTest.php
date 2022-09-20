<?php

namespace Taxjar\SalesTax\Test\Unit\Console\Command;

use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class SyncTransactionsCommandTest extends UnitTestCase
{
    /**
     * @var \Taxjar\SalesTax\Console\Command\SyncTransactionsCommand
     */
    private \Taxjar\SalesTax\Console\Command\SyncTransactionsCommand $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stateMock = $this->getMockBuilder(\Magento\Framework\App\State::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventManagerMock = $this->getMockBuilder(\Magento\Framework\Event\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(\Taxjar\SalesTax\Model\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->setExpectations();
    }

    /**
     * @dataProvider parseInputDataProvider
     */
    public function testParseInput(array $argumentMap, array $optionMap, array $results)
    {
        $inputMock = $this->getMockBuilder(InputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $inputMock->expects(static::any())->method('getArgument')->willReturnMap($argumentMap);
        $inputMock->expects(static::any())->method('getOption')->willReturnMap($optionMap);

        $this->setExpectations();

        $method = new \ReflectionMethod($this->sut::class, '_parseInput');
        $method->invoke($this->sut, $inputMock);

        static::assertEquals($results['start_date'], $this->sut->startDate);
        static::assertEquals($results['end_date'], $this->sut->endDate);
        static::assertEquals($results['force_sync'], $this->sut->forceSync);
    }

    /**
     * @return array[]
     */
    public function parseInputDataProvider()
    {
        return [
            'no_input' => [
                'argumentMap' => [
                    ['<from>', null],
                    ['<to>', null],
                ],
                'optionMap' => [
                    ['force', null],
                ],
                'results' => [
                    'start_date' => null,
                    'end_date' => null,
                    'force_sync' => false,
                ],
            ],
            'force' => [
                'argumentMap' => [
                    ['<from>', null],
                    ['<to>', null],
                ],
                'optionMap' => [
                    ['force', '-f'],
                ],
                'results' => [
                    'start_date' => null,
                    'end_date' => null,
                    'force_sync' => true,
                ],
            ],
            'date_range' => [
                'argumentMap' => [
                    ['<from>', '2022-08-01'],
                    ['<to>', '2022-08-31'],
                ],
                'optionMap' => [
                    ['force', null],
                ],
                'results' => [
                    'start_date' => '2022-08-01',
                    'end_date' => '2022-08-31',
                    'force_sync' => false,
                ],
            ],
            'date_range_forced' => [
                'argumentMap' => [
                    ['<from>', '2022-08-01'],
                    ['<to>', '2022-08-31'],
                ],
                'optionMap' => [
                    ['force', '-f'],
                ],
                'results' => [
                    'start_date' => '2022-08-01',
                    'end_date' => '2022-08-31',
                    'force_sync' => true,
                ],
            ],
        ];
    }

    public function testParseInputWithInvalidDateRange()
    {
        $invalidDateRangeMap = [
            ['<from>', '2022-09-01'],
            ['<to>', '2022-08-31'],
        ];

        $inputMock = $this->getMockBuilder(InputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $inputMock->expects(static::any())->method('getArgument')->willReturnMap($invalidDateRangeMap);
        $inputMock->expects(static::any())->method('getOption')->willReturnMap(['force', false]);

        $this->setExpectations();

        $method = new \ReflectionMethod($this->sut::class, '_parseInput');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Invalid date range provided.');

        $method->invoke($this->sut, $inputMock);
    }

    public function testExecuteSuccess()
    {
        $inputMock = $this->getMockBuilder(InputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $inputMock->expects(static::any())->method('getArgument')->willReturnMap([
            ['<from>', '2022-08-01'],
            ['<to>', '2022-08-31'],
        ]);

        $inputMock->expects(static::any())->method('getOption')->willReturnMap([
            ['force', '-f']
        ]);

        $outputMock = $this->getMockBuilder(OutputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $outputMock->expects(static::once())
            ->method('writeln')
            ->with(PHP_EOL . 'Transaction backfill successfully initiated.');

        $this->stateMock->expects(static::once())->method('setAreaCode')->with('adminhtml');
        $this->loggerMock->expects(static::once())->method('console')->with($outputMock);

        $this->eventManagerMock->expects(static::once())
            ->method('dispatch')
            ->with('taxjar_salestax_backfill_transactions', [
                'start_date' => '2022-08-01',
                'end_date' => '2022-08-31',
                'force_sync' => true,
            ]);

        $this->setExpectations();

        $method = new \ReflectionMethod($this->sut::class, 'execute');
        $method->invoke($this->sut, $inputMock, $outputMock);
    }

    public function testExecuteFailure()
    {
        $message = __('Manual test exception.');
        $exceptionMock = new LocalizedException($message);

        $inputMock = $this->getMockBuilder(InputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $outputMock = $this->getMockBuilder(OutputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $outputMock->expects(static::once())
            ->method('writeln')
            ->with(PHP_EOL . '<error>' . 'Failed to initiate transaction backfill: ' . $message . '</error>');

        $this->stateMock->expects(static::once())
            ->method('setAreaCode')
            ->with('adminhtml')
            ->willThrowException($exceptionMock);

        $this->setExpectations();

        $method = new \ReflectionMethod($this->sut::class, 'execute');
        $method->invoke($this->sut, $inputMock, $outputMock);
    }

    protected function setExpectations()
    {
        $this->sut = new \Taxjar\SalesTax\Console\Command\SyncTransactionsCommand(
            $this->stateMock,
            $this->eventManagerMock,
            $this->loggerMock
        );
    }
}
