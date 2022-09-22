<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Controller\Adminhtml\Transaction;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterfaceFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;

class SyncTest extends \Taxjar\SalesTax\Test\Unit\UnitTestCase
{
    /**
     * @var \Magento\Backend\App\Action\Context|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextMock;

    /**
     * @var OrderRepositoryInterfaceFactory|OrderRepositoryInterfaceFactory&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderRepositoryFactoryMock;

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

        $this->orderRepositoryFactoryMock = $this->createMock(OrderRepositoryInterfaceFactory::class);

        $this->requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);

        $this->eventManagerMock = $this->createMock(\Magento\Framework\Event\ManagerInterface::class);

        $this->resultFactoryMock = $this->createMock(\Magento\Framework\Controller\ResultFactory::class);

        $this->setExpectations();
    }

    public function testExecuteMethodResultOnSuccess()
    {
        $orderId = 42;

        $this->requestMock->expects(static::once())
            ->method('getParam')
            ->willReturnMap([
                ['order_id', null, $orderId]
            ]);

        $orderMock = $this->createMock(Order::class);

        $orderRepositoryMock = $this->createMock(OrderRepository::class);
        $orderRepositoryMock->expects(static::once())->method('get')->with($orderId)->willReturn($orderMock);

        $this->orderRepositoryFactoryMock->expects(static::once())->method('create')->willReturn($orderRepositoryMock);

        $this->eventManagerMock->expects(static::once())
            ->method('dispatch')
            ->with(
                'taxjar_salestax_transaction_sync',
                [
                    'transaction' => $orderMock,
                    'force_sync' => true,
                ]
            );

        $resultMock = $this->createMock(\Magento\Framework\Controller\Result\Json::class);
        $resultMock->expects(static::once())
            ->method('setData')
            ->with(
                [
                    'data' => [
                        'success' => true,
                        'error_message' => '',
                    ],
                ]
            )
            ->willReturnSelf();

        $this->resultFactoryMock->expects(static::atLeastOnce())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($resultMock);

        $this->setExpectations();

        static::assertEquals($resultMock, $this->sut->execute());
    }

    public function testExecuteMethodResultOnException()
    {
        $orderId = 42;

        $this->requestMock->expects(static::once())
            ->method('getParam')
            ->willReturnMap([
                ['order_id', null, $orderId]
            ]);

        $exceptionMessage = __('Test exception.');

        $orderRepositoryMock = $this->createMock(OrderRepository::class);
        $orderRepositoryMock->expects(static::once())
            ->method('get')
            ->with($orderId)
            ->willThrowException(
                new LocalizedException($exceptionMessage)
            );

        $this->orderRepositoryFactoryMock->expects(static::once())->method('create')->willReturn($orderRepositoryMock);

        $this->eventManagerMock->expects(static::never())->method('dispatch');

        $resultMock = $this->createMock(\Magento\Framework\Controller\Result\Json::class);
        $resultMock->expects(static::once())
            ->method('setData')
            ->with(
                [
                    'data' => [
                        'success' => false,
                        'error_message' => $exceptionMessage,
                    ],
                ]
            )
            ->willReturnSelf();

        $this->resultFactoryMock->expects(static::atLeastOnce())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
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

        $this->contextMock->expects(static::any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->sut = new \Taxjar\SalesTax\Controller\Adminhtml\Transaction\Sync(
            $this->contextMock,
            $this->orderRepositoryFactoryMock
        );
    }
}
