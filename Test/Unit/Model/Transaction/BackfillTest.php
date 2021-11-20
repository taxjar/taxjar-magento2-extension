<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Model\Transaction;

class BackfillTest extends \Taxjar\SalesTax\Test\Unit\UnitTestCase
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $orderRepositoryMock;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|\Taxjar\SalesTax\Model\Transaction\Order
     */
    protected $orderTransactionMock;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|\Taxjar\SalesTax\Model\Transaction\Refund
     */
    protected $refundTransactionMock;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|\Taxjar\SalesTax\Model\Logger
     */
    protected $loggerMock;
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $serializerMock;
    /**
     * @var \Magento\Framework\EntityManager\EntityManager|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityManagerMock;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|\Taxjar\SalesTax\Helper\Data
     */
    protected $tjSalesTaxDataMock;
    /**
     * @var \Magento\AsynchronousOperations\Api\Data\OperationInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $operationMock;
    /**
     * @var \Taxjar\SalesTax\Model\Transaction\Backfill
     */
    protected $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderRepositoryMock = $this->getMockBuilder(\Magento\Sales\Api\OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->orderTransactionMock = $this->getMockBuilder(\Taxjar\SalesTax\Model\Transaction\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->refundTransactionMock = $this->getMockBuilder(\Taxjar\SalesTax\Model\Transaction\Refund::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(\Taxjar\SalesTax\Model\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serializerMock = $this->getMockBuilder(\Magento\Framework\Serialize\SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->entityManagerMock = $this->getMockBuilder(\Magento\Framework\EntityManager\EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tjSalesTaxDataMock = $this->getMockBuilder(\Taxjar\SalesTax\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->operationMock = $this->getMockBuilder(\Magento\AsynchronousOperations\Api\Data\OperationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->setExpectations();
    }

    public function testProcessMethodWithInvalidOperationData()
    {
        // Mock an operation with data where required key `orderIds` is missing
        $serializedMock = "['meta_information' => ['force' => false]]";
        $unserializedMock = ['meta_information' => ['force' => false]];
        $this->expectOperationData($serializedMock, $unserializedMock);

        // Expect result of fail method
        $exceptionMessage = 'Operation data could not be parsed. Required array key `orderIds` does not exist.';
        $this->loggerMock->expects(static::once())
            ->method('log')
            ->with("Error syncing order #UNKNOWN - $exceptionMessage", 'error');
        $this->expectOperationUpdated(
            \Magento\Framework\Bulk\OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED,
            0,
            "$exceptionMessage"
        );

        $this->setExpectations();

        $this->sut->process($this->operationMock);
    }

    public function testProcessLogsNonSyncableOrder()
    {
        $serializedMock = "['meta_information' => ['orderIds' => ['9'], 'force' => false]]";
        $unserializedMock = ['meta_information' => ['orderIds' => ['9'], 'force' => false]];
        $this->expectOperationData($serializedMock, $unserializedMock);

        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderRepositoryMock->expects(static::once())
            ->method('get')
            ->with(9)
            ->willReturn($orderMock);
        $this->orderTransactionMock->expects(static::once())
            ->method('isSyncable')
            ->with($orderMock)
            ->willReturn(false);

        $this->loggerMock->expects(static::once())
            ->method('log')
            ->with('Order #9 is not syncable', 'skip');

        $this->expectOperationUpdated(
            \Magento\Framework\Bulk\OperationInterface::STATUS_TYPE_COMPLETE,
            null,
            null
        );

        $this->setExpectations();

        $this->sut->process($this->operationMock);
    }

    public function testProcessMethodOnSuccess()
    {
        $serializedMock = "['meta_information' => ['orderIds' => ['9'], 'force' => false]]";
        $unserializedMock = ['meta_information' => ['orderIds' => ['9'], 'force' => false]];
        $this->expectOperationData($serializedMock, $unserializedMock);

        $creditmemoMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Creditmemo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects(static::once())
            ->method('getStoreId')
            ->willReturn(1);
        $orderMock->expects(static::once())
            ->method('getCreditmemosCollection')
            ->willReturn([$creditmemoMock]);
        $this->orderRepositoryMock->expects(static::once())
            ->method('get')
            ->with(9)
            ->willReturn($orderMock);

        $this->orderTransactionMock->expects(static::once())
            ->method('isSyncable')
            ->with($orderMock)
            ->willReturn(true);
        $this->tjSalesTaxDataMock->expects(static::once())
            ->method('isTransactionSyncEnabled')
            ->with(1)
            ->willReturn(true);

        $this->orderTransactionMock->expects(static::once())
            ->method('build')
            ->with($orderMock);
        $this->orderTransactionMock->expects(static::once())
            ->method('push')
            ->with(false);
        $this->refundTransactionMock->expects(static::once())
            ->method('build')
            ->with($orderMock, $creditmemoMock);
        $this->refundTransactionMock->expects(static::once())
            ->method('push')
            ->with(false);

        $this->expectOperationUpdated(
            \Magento\Framework\Bulk\OperationInterface::STATUS_TYPE_COMPLETE,
            null,
            null
        );

        $this->setExpectations();

        $this->sut->process($this->operationMock);
    }

    protected function setExpectations(): void
    {
        $this->loggerMock->expects(static::atLeastOnce())
            ->method('setFilename')
            ->with('transactions.log')
            ->willReturnSelf();
        $this->loggerMock->expects(static::any())
            ->method('force')
            ->willReturnSelf();

        $this->sut = new \Taxjar\SalesTax\Model\Transaction\Backfill(
            $this->orderRepositoryMock,
            $this->orderTransactionMock,
            $this->refundTransactionMock,
            $this->loggerMock,
            $this->serializerMock,
            $this->entityManagerMock,
            $this->tjSalesTaxDataMock
        );
    }

    private function expectOperationData($serialized, $unserialized)
    {
        $this->operationMock->expects(static::any())
            ->method('getSerializedData')
            ->willReturn($serialized);
        $this->serializerMock->expects(static::once())
            ->method('unserialize')
            ->willReturn($unserialized);
    }

    private function expectOperationUpdated($status, $code, $message)
    {
        $this->operationMock->expects(static::once())
            ->method('setStatus')
            ->with($status);
        $this->operationMock->expects(static::once())
            ->method('setErrorCode')
            ->with($code);
        $this->operationMock->expects(static::once())
            ->method('setResultMessage')
            ->with($message);
        $this->entityManagerMock->expects(static::once())
            ->method('save')
            ->with($this->operationMock);
    }
}
