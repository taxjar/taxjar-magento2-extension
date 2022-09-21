<?php
/**
 * Taxjar_SalesTax
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Taxjar
 * @package    Taxjar_SalesTax
 * @copyright  Copyright (c) 2022 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Test\Unit\Model\ResourceModel\Transaction\Sync;

use Magento\AsynchronousOperations\Model\Operation;
use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\Bulk\OperationManagementInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\OrderRepository;
use Taxjar\SalesTax\Api\Data\TransactionManagementInterface;
use Taxjar\SalesTax\Model\Logger;
use Taxjar\SalesTax\Model\ResourceModel\Transaction\Sync\Consumer;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class ConsumerTest extends UnitTestCase
{
    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject|Logger&\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;
    /**
     * @var OperationManagementInterface|OperationManagementInterface&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $operationManagementMock;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|OrderRepository|OrderRepository&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject&OrderRepository
     */
    private $orderRepositoryMock;
    /**
     * @var SerializerInterface|\PHPUnit\Framework\MockObject\MockObject|SerializerInterface&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject&SerializerInterface
     */
    private $serializerMock;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TransactionManagementInterface|TransactionManagementInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $transactionManagementMock;
    /**
     * @var Consumer
     */
    private Consumer $sut;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->operationManagementMock = $this->createMock(OperationManagementInterface::class);
        $this->orderRepositoryMock = $this->createMock(OrderRepository::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->transactionManagementMock = $this->createMock(TransactionManagementInterface::class);
    }

    public function testProcess()
    {
        $operationIdMock = 12345;
        $serializedDataMock = '{"entity_id": "99", "force_sync": true}';

        $operationMock = $this->createMock(Operation::class);
        $operationMock->expects(static::any())->method('getId')->willReturn($operationIdMock);
        $operationMock->expects(static::once())->method('getSerializedData')->willReturn($serializedDataMock);

        $this->serializerMock->expects(static::once())
            ->method('unserialize')
            ->with($serializedDataMock)
            ->willReturn([
                'entity_id' => 99,
                'force_sync' => true,
            ]);

        $orderMock = $this->createMock(Order::class);
        $orderMock->expects(static::once())->method('getCreditmemosCollection')->willReturn([]);

        $this->orderRepositoryMock->expects(static::once())
            ->method('get')
            ->with(99)
            ->willReturn($orderMock);

        $this->transactionManagementMock->expects(static::once())
            ->method('sync')
            ->with($orderMock, true)
            ->willReturn(true);

        $this->operationManagementMock->expects(static::once())
            ->method('changeOperationStatus')
            ->with(
                $operationIdMock,
                OperationInterface::STATUS_TYPE_COMPLETE,
                null,
                null,
                $serializedDataMock
            );

        $this->setExpectations();

        $this->sut->process($operationMock);
    }

    public function testProcessLocalizedException()
    {
        $operationIdMock = 12345;
        $serializedDataMock = '{"entity_id": "99", "force_sync": true}';

        $operationMock = $this->createMock(Operation::class);
        $operationMock->expects(static::any())->method('getId')->willReturn($operationIdMock);
        $operationMock->expects(static::once())->method('getSerializedData')->willReturn($serializedDataMock);

        $this->serializerMock->expects(static::once())
            ->method('unserialize')
            ->with($serializedDataMock)
            ->willReturn([
                'entity_id' => 99,
                'force_sync' => true,
            ]);

        $orderMock = $this->createMock(Order::class);

        $this->orderRepositoryMock->expects(static::once())
            ->method('get')
            ->with(99)
            ->willReturn($orderMock);

        $exceptionMessageMock = __('test exception message');

        $this->transactionManagementMock->expects(static::once())
            ->method('sync')
            ->willThrowException(
                new LocalizedException($exceptionMessageMock, null, 9001)
            );

        $this->loggerMock->expects(static::once())->method('log')->with($exceptionMessageMock);

        $this->operationManagementMock->expects(static::once())
            ->method('changeOperationStatus')
            ->with(
                $operationIdMock,
                OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED,
                9001,
                $exceptionMessageMock,
                $serializedDataMock
            );

        $this->setExpectations();

        $this->sut->process($operationMock);
    }

    public function testProcessException()
    {
        $operationIdMock = 12345;
        $serializedDataMock = '{"entity_id": "99", "force_sync": true}';

        $operationMock = $this->createMock(Operation::class);
        $operationMock->expects(static::any())->method('getId')->willReturn($operationIdMock);
        $operationMock->expects(static::once())->method('getSerializedData')->willReturn($serializedDataMock);

        $this->serializerMock->expects(static::once())
            ->method('unserialize')
            ->with($serializedDataMock)
            ->willReturn([
                'entity_id' => 99,
                'force_sync' => true,
            ]);

        $orderMock = $this->createMock(Order::class);

        $this->orderRepositoryMock->expects(static::once())
            ->method('get')
            ->with(99)
            ->willReturn($orderMock);

        $exceptionMessageMock = 'test base exception class message';

        $this->transactionManagementMock->expects(static::once())
            ->method('sync')
            ->willThrowException(
                new \Exception($exceptionMessageMock, 9001)
            );

        $this->loggerMock->expects(static::once())->method('log')->with($exceptionMessageMock);

        $expectedMessageMock = __(
            'Sorry, something went wrong during TaxJar transaction sync. Please see log for details.'
        );

        $this->operationManagementMock->expects(static::once())
            ->method('changeOperationStatus')
            ->with(
                $operationIdMock,
                OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED,
                9001,
                $expectedMessageMock,
                $serializedDataMock
            );

        $this->setExpectations();

        $this->sut->process($operationMock);
    }

    public function testProcessInvalidOperationData()
    {
        $operationIdMock = 12345;
        $serializedDataMock = '{"entity_id": "", "force_sync": true}';

        $operationMock = $this->createMock(Operation::class);
        $operationMock->expects(static::any())->method('getId')->willReturn($operationIdMock);
        $operationMock->expects(static::once())->method('getSerializedData')->willReturn($serializedDataMock);

        $this->serializerMock->expects(static::once())
            ->method('unserialize')
            ->with($serializedDataMock)
            ->willReturn([
                'entity_id' => "",
                'force_sync' => true,
            ]);

        $exceptionMessageMock =  __('Invalid operation data in TaxJar transaction backfill.');

        $this->loggerMock->expects(static::once())->method('log')->with($exceptionMessageMock);

        $this->operationManagementMock->expects(static::once())
            ->method('changeOperationStatus')
            ->with(
                $operationIdMock,
                OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED,
                0,
                $exceptionMessageMock,
                $serializedDataMock
            );

        $this->setExpectations();

        $this->sut->process($operationMock);
    }

    public function testSync()
    {
        $orderMock = $this->createMock(Order::class);
        $creditmemoMock = $this->createMock(Creditmemo::class);

        $orderMock->expects(static::once())
            ->method('getCreditmemosCollection')
            ->willReturn([$creditmemoMock]);

        $this->transactionManagementMock->expects(static::exactly(2))
            ->method('sync')
            ->withConsecutive(
                [$orderMock, true],
                [$creditmemoMock, true]
            )->willReturnOnConsecutiveCalls(
                true,
                true
            );

        $this->setExpectations();

        $this->sut->sync($orderMock, true);
    }

    public function testSyncException()
    {
        $orderMock = $this->createMock(Order::class);
        $creditmemoMock = $this->createMock(Creditmemo::class);

        $orderMock->expects(static::once())
            ->method('getCreditmemosCollection')
            ->willReturn([$creditmemoMock]);

        $this->transactionManagementMock->expects(static::exactly(2))
            ->method('sync')
            ->withConsecutive(
                [$orderMock, true],
                [$creditmemoMock, true]
            )->willReturnOnConsecutiveCalls(
                true,
                false
            );

        $this->setExpectations();

        $exceptionMessage = 'One or more credit memos could not be synced to TaxJar. See logs for additional details.';

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->sut->sync($orderMock, true);
    }

    public function setExpectations()
    {
        $this->sut = new Consumer(
            $this->loggerMock,
            $this->operationManagementMock,
            $this->orderRepositoryMock,
            $this->serializerMock,
            $this->transactionManagementMock
        );
    }
}
