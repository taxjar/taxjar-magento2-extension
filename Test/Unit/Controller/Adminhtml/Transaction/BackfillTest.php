<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Controller\Adminhtml\Transaction;

class BackfillTest extends \Taxjar\SalesTax\Test\Unit\UnitTestCase
{
    /**
     * @var \Magento\Backend\App\Action\Context|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextMock;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|\Taxjar\SalesTax\Model\Logger
     */
    protected $loggerMock;
    /**
     * @var \Magento\Framework\App\RequestInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $requestMock;
    /**
     * @var \Magento\Framework\Event\ManagerInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $eventManagerMock;
    /**
     * @var \Magento\Framework\Controller\ResultFactory|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $resultFactoryMock;
    /**
     * @var \Taxjar\SalesTax\Controller\Adminhtml\Transaction\Sync
     */
    protected $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contextMock = $this->getMockBuilder(\Magento\Backend\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(\Taxjar\SalesTax\Model\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventManagerMock = $this->getMockBuilder(\Magento\Framework\Event\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultFactoryMock = $this->getMockBuilder(\Magento\Framework\Controller\ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->setExpectations();
    }

    public function testExecuteMethodResultOnSuccess()
    {
        $this->eventManagerMock->expects(static::atLeastOnce())
            ->method('dispatch')
            ->with('taxjar_salestax_backfill_transactions');
        $resultMock = $this->createMock(\Magento\Framework\Controller\Result\Json::class);
        $resultMock->expects(static::once())
            ->method('setData')
            ->with([
                'success' => true,
                'error_message' => '',
                'result' => ['test-playback']
            ])
            ->willReturnSelf();
        $this->loggerMock->expects(static::atLeastOnce())
            ->method('record')
            ->willReturnSelf();
        $this->loggerMock->expects(static::atLeastOnce())
            ->method('playback')
            ->willReturn(['test-playback']);
        $this->resultFactoryMock->expects(static::atLeastOnce())
            ->method('create')
            ->willReturn($resultMock);

        $this->setExpectations();

        static::assertEquals($resultMock, $this->sut->execute());
    }

    public function testExecuteMethodResultOnException()
    {
        $this->eventManagerMock->expects(static::atLeastOnce())
            ->method('dispatch')
            ->with('taxjar_salestax_backfill_transactions')
            ->willThrowException(new \Exception('test'));
        $resultMock = $this->createMock(\Magento\Framework\Controller\Result\Json::class);
        $resultMock->expects(static::once())
            ->method('setData')
            ->with([
                'success' => false,
                'error_message' => 'test',
            ])
            ->willReturnSelf();
        $this->resultFactoryMock->expects(static::atLeastOnce())
            ->method('create')
            ->willReturn($resultMock);

        $this->setExpectations();

        static::assertEquals($resultMock, $this->sut->execute());
    }

    protected function setExpectations()
    {
        $this->contextMock->expects(static::atLeastOnce())
            ->method('getEventManager')
            ->willReturn($this->eventManagerMock);
        $this->contextMock->expects(static::atLeastOnce())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);

        $this->sut = new \Taxjar\SalesTax\Controller\Adminhtml\Transaction\Backfill(
            $this->contextMock,
            $this->loggerMock
        );
    }
}
