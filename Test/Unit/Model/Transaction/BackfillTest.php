<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Model\Transaction;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Taxjar\SalesTax\Model\Transaction\Backfill;
use \Taxjar\SalesTax\Model\Transaction\Order as TaxjarOrder;
use Taxjar\SalesTax\Model\Logger;
use Taxjar\SalesTax\Model\Transaction\Refund;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class BackfillTest extends UnitTestCase
{
    /**
     * @var OrderRepositoryInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderRepository;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|TaxjarOrder
     */
    private $orderTransaction;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|Refund
     */
    private $refundTransaction;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|Logger
     */
    private $logger;
    /**
     * @var Serialize|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $serializer;
    /**
     * @var EntityManager|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->orderTransaction = $this->createMock(\Taxjar\SalesTax\Model\Transaction\Order::class);
        $this->refundTransaction = $this->createMock(Refund::class);
        $this->logger = $this->createMock(Logger::class);
        $this->serializer = $this->createMock(Serialize::class);
        $this->entityManager = $this->createMock(EntityManager::class);

        $this->logger->expects($this->once())->method('setFilename')->with('transactions.log')->willReturnSelf();
        $this->logger->expects($this->once())->method('force')->willReturnSelf();
    }

    public function testInvalidOperationDataThrowsException()
    {
        $operation = $this->createMock(OperationInterface::class);
        $operation->expects($this->any())->method('getSerializedData')
            ->willReturn("['meta_information' => ['orderIds' => ['9'], 'force' => false]]");

        $this->serializer->expects($this->any())->method('unserialize')->willReturn([
            'meta_information' => [
                'force' => false,
            ],
        ]);

        $this->logger->expects($this->any())->method('log')->with(
            'Error syncing order #UNKNOWN - Operation data could not be parsed. Required array key `orderIds` does not exist.',
            'error'
        );

        $sut = $this->getTestSubject();
        $sut->process($operation);
    }

    public function testProcessLogsNonSyncableOrder()
    {
        $mockOrder = $this->createMock(Order::class);
        $mockOrder->expects($this->any())->method('getIncrementId')->willReturn(9);

        $operation = $this->createMock(OperationInterface::class);
        $operation->expects($this->any())->method('getSerializedData')
            ->willReturn("['meta_information' => ['orderIds' => ['9'], 'force' => false]]");

        $this->serializer->expects($this->any())->method('unserialize')->willReturn([
            'meta_information' => [
                'orderIds' => ['9'],
                'force' => false,
            ],
        ]);
        $this->orderRepository->expects($this->any())->method('get')->with('9')->willReturn($mockOrder);
        $this->orderTransaction->expects($this->any())->method('isSyncable')->willReturn(false);

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
        $operation->expects($this->any())->method('getSerializedData')
            ->willReturn("['meta_information' => ['orderIds' => ['9'], 'force' => false]]");
        $operation->expects($this->any())->method('setStatus')->with(1)->willReturnSelf();
        $operation->expects($this->any())->method('setErrorCode')->with(null)->willReturnSelf();
        $operation->expects($this->any())->method('setResultMessage')->with(null)->willReturnSelf();

        $this->serializer->expects($this->any())->method('unserialize')
            ->willReturn([
                'meta_information' => [
                    'orderIds' => ['9'],
                    'force' => false,
                ],
            ]);

        $this->orderRepository->expects($this->any())->method('get')->with('9')->willReturn($mockOrder);
        $this->orderTransaction->expects($this->any())->method('isSyncable')->willReturn(true);
        $this->orderTransaction->expects($this->any())->method('build')->with($mockOrder);
        $this->orderTransaction->expects($this->any())->method('push')->with(false);
        $this->refundTransaction->expects($this->any())->method('build')->with($mockOrder, $mockCreditmemo);
        $this->refundTransaction->expects($this->any())->method('push');

        $this->entityManager->expects($this->any())->method('save');

        $sut = $this->getTestSubject();
        $sut->process($operation);
    }

    public function testProcessLogsCaughtException()
    {
        $operation = $this->createMock(OperationInterface::class);
        $operation->expects($this->any())->method('getSerializedData')->willReturn("['meta_information' => ['orderIds' => ['9']]]");

        $this->serializer->expects($this->any())->method('unserialize')
            ->willReturn([
                'meta_information' => [
                    'orderIds' => ['9'],
                    'force' => false,
                ],
            ]);

        $this->orderRepository->expects($this->any())->method('get')->with('9')
            ->willThrowException(new \Exception('testing'));

        $this->logger->expects($this->any())->method('log')
            ->with('Error syncing order #9 - testing', 'error');

        $sut = $this->getTestSubject();
        $sut->process($operation);
    }

    public function testProcessForceNonSyncableOrder()
    {
        $mockCreditmemo = $this->createMock(Order\Creditmemo::class);

        $mockOrder = $this->createMock(Order::class);
        $mockOrder->expects($this->any())->method('getIncrementId')->willReturn(9);
        $mockOrder->expects($this->any())->method('getCreditmemosCollection')->willReturn([$mockCreditmemo]);

        $operation = $this->createMock(OperationInterface::class);
        $operation->expects($this->any())->method('getSerializedData')
            ->willReturn("['meta_information' => ['orderIds' => ['9'], 'force' => true]]");
        $operation->expects($this->any())->method('setStatus')->with(1)->willReturnSelf();
        $operation->expects($this->any())->method('setErrorCode')->with(null)->willReturnSelf();
        $operation->expects($this->any())->method('setResultMessage')->with(null)->willReturnSelf();

        $this->serializer->expects($this->any())->method('unserialize')
            ->willReturn([
                'meta_information' => [
                    'orderIds' => ['9'],
                    'force' => true,
                ],
            ]);

        $this->orderRepository->expects($this->any())->method('get')->with('9')->willReturn($mockOrder);
        $this->orderTransaction->expects($this->any())->method('isSyncable')->willReturn(false);
        $this->orderTransaction->expects($this->any())->method('build')->with($mockOrder);
        $this->orderTransaction->expects($this->any())->method('push')->with(true);
        $this->refundTransaction->expects($this->any())->method('build')->with($mockOrder, $mockCreditmemo);
        $this->refundTransaction->expects($this->any())->method('push');

        $this->entityManager->expects($this->any())->method('save');

        $sut = $this->getTestSubject();
        $sut->process($operation);
    }

    protected function getTestSubject(): Backfill
    {
        return new Backfill(
            $this->orderRepository,
            $this->orderTransaction,
            $this->refundTransaction,
            $this->logger,
            $this->serializer,
            $this->entityManager
        );
    }
}
