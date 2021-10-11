<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Model\Transaction;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Sales\Model\Order;
use Taxjar\SalesTax\Model\Transaction\Backfill;
use \Taxjar\SalesTax\Model\Transaction\Order as TaxjarOrder;
use Taxjar\SalesTax\Model\Logger;
use Taxjar\SalesTax\Model\Transaction\OrderFactory;
use Taxjar\SalesTax\Model\Transaction\Refund;
use Taxjar\SalesTax\Model\Transaction\RefundFactory;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class BackfillTest extends UnitTestCase
{
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|OrderFactory
     */
    private $orderFactory;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|RefundFactory
     */
    private $refundFactory;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|Logger
     */
    private $logger;
    /**
     * @var Serialize|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $serializer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderFactory = $this->createMock(OrderFactory::class);
        $this->refundFactory = $this->createMock(RefundFactory::class);
        $this->logger = $this->createMock(Logger::class);
        $this->serializer = $this->createMock(Serialize::class);

        $this->logger->expects($this->once())->method('setFilename')->with('transactions.log')->willReturnSelf();
        $this->logger->expects($this->once())->method('force')->willReturnSelf();
    }

    public function testProcessLogsNonSyncableOrder()
    {
        $mockOrder = $this->createMock(Order::class);
        $mockOrder->expects($this->any())->method('getIncrementId')->willReturn(9);

        $operation = $this->createMock(OperationInterface::class);
        $operation->expects($this->any())->method('getSerializedData')->willReturn(null);

        $this->serializer->expects($this->any())->method('unserialize')->willReturn([$mockOrder]);

        $mockTjOrder = $this->createMock(TaxjarOrder::class);
        $mockTjOrder->expects($this->any())->method('isSyncable')->with($mockOrder)->willReturn(false);

        $this->orderFactory->expects($this->any())->method('create')->willReturn($mockTjOrder);
        $this->logger->expects($this->any())->method('log')->with('Order #9 is not syncable', 'skip');

        $sut = $this->getTestSubject();
        $sut->process($operation);
    }

    public function testProcessSyncableOrder()
    {
        $mockCreditmemo = $this->createMock(Order\Creditmemo::class);

        $mockOrder = $this->createMock(Order::class);
        $mockOrder->expects($this->any())->method('getIncrementId')->willReturn(9);
        $mockOrder->expects($this->any())->method('getCreditmemosCollection')->willReturn([$mockCreditmemo]);

        $operation = $this->createMock(OperationInterface::class);
        $operation->expects($this->any())->method('getSerializedData')->willReturn(null);

        $this->serializer->expects($this->any())->method('unserialize')->willReturn([$mockOrder]);

        $mockRefund = $this->createMock(Refund::class);
        $mockRefund->expects($this->any())->method('build')->with($mockOrder, $mockCreditmemo);
        $mockRefund->expects($this->any())->method('push');

        $mockTjOrder = $this->createMock(TaxjarOrder::class);
        $mockTjOrder->expects($this->any())->method('isSyncable')->with($mockOrder)->willReturn(true);
        $mockTjOrder->expects($this->any())->method('build')->with($mockOrder);
        $mockTjOrder->expects($this->any())->method('push');

        $this->orderFactory->expects($this->any())->method('create')->willReturn($mockTjOrder);
        $this->refundFactory->expects($this->any())->method('create')->willReturn($mockRefund);

        $sut = $this->getTestSubject();
        $sut->process($operation);
    }

    public function testProcessLogsCaughtException()
    {
        $mockOrder = $this->createMock(Order::class);
        $mockOrder->expects($this->any())->method('getIncrementId')->willReturn(9);

        $operation = $this->createMock(OperationInterface::class);
        $operation->expects($this->any())->method('getSerializedData')->willReturn(null);

        $this->serializer->expects($this->any())->method('unserialize')->willReturn([$mockOrder]);
        $this->orderFactory->expects($this->any())->method('create')->willThrowException(new \Exception('test error'));

        $this->logger->expects($this->any())->method('log')->with('Error syncing order #9 - test error', 'error');

        $sut = $this->getTestSubject();
        $sut->process($operation);
    }

    protected function getTestSubject(): Backfill
    {
        return new Backfill(
            $this->orderFactory,
            $this->refundFactory,
            $this->logger,
            $this->serializer
        );
    }
}
