<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Controller\Adminhtml\Transaction;

class BackfillTest extends \Taxjar\SalesTax\Test\Unit\UnitTestCase
{
    /**
     * @var \Taxjar\SalesTax\Controller\Adminhtml\Transaction\Backfill
     */
    private \Taxjar\SalesTax\Controller\Adminhtml\Transaction\Backfill $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contextMock = $this->createMock(\Magento\Backend\App\Action\Context::class);
        $this->eventManagerMock = $this->createMock(\Magento\Framework\Event\ManagerInterface::class);
        $this->resultFactoryMock = $this->createMock(\Magento\Framework\Controller\ResultFactory::class);
        $this->requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);

        $this->setExpectations();
    }

    public function testExecuteMethodResultOnSuccess()
    {
        $this->requestMock->expects(static::any())->method('getParam')->willReturnMap([
            ['start_date', null, '2022-08-01'],
            ['end_date', null, '2022-08-31'],
            ['force_sync', null, true],
            ['store_id', null, null],
            ['website_id', null, null]
        ]);

        $this->eventManagerMock->expects(static::once())
            ->method('dispatch')
            ->with('taxjar_salestax_backfill_transactions', [
                'start_date' => '2022-08-01',
                'end_date' => '2022-08-31',
                'force_sync' => true,
                'store_id' => null,
                'website_id' => null
            ]);

        $resultMock = $this->createMock(\Magento\Framework\Controller\Result\Json::class);

        $resultMock->expects(static::once())
            ->method('setData')
            ->with([
                'success' => true,
                'error_message' => '',
                'result' => __('Successfully scheduled TaxJar transaction backfill.'),
            ])
            ->willReturnSelf();

        $this->resultFactoryMock->expects(static::atLeastOnce())
            ->method('create')
            ->with('json')
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
            ->with('json')
            ->willReturn($resultMock);

        $this->resultFactoryMock->expects(static::atLeastOnce())
            ->method('create')
            ->willReturn($resultMock);

        $this->setExpectations();

        static::assertEquals($resultMock, $this->sut->execute());
    }

    public function setExpectations()
    {
        $this->contextMock->expects(static::any())
            ->method('getEventManager')
            ->willReturn($this->eventManagerMock);

        $this->contextMock->expects(static::any())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);

        $this->contextMock->expects(static::any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->sut = new \Taxjar\SalesTax\Controller\Adminhtml\Transaction\Backfill($this->contextMock);
    }
}
