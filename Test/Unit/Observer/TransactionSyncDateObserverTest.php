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

namespace Taxjar\SalesTax\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo as CreditmemoResource;
use Taxjar\SalesTax\Model\Logger;
use Taxjar\SalesTax\Model\Transaction;
use Taxjar\SalesTax\Observer\TransactionSyncDateObserver;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class TransactionSyncDateObserverTest extends UnitTestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Logger|Logger&\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var OrderResource|OrderResource&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderResourceMock;

    /**
     * @var CreditmemoResource|CreditmemoResource&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $creditmemoResourceMock;

    /**
     * @var TransactionSyncDateObserver
     */
    private $sut;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->orderResourceMock = $this->createMock(OrderResource::class);
        $this->creditmemoResourceMock = $this->createMock(CreditmemoResource::class);
    }

    public function testExecuteWithTransactionTypeOfOrder()
    {
        $orderMock = $this->createMock(Order::class);

        $eventMock = $this->getMockBuilder(Event::class)->addMethods(['getTransaction'])->getMock();
        $eventMock->expects(static::once())->method('getTransaction')->willReturn($orderMock);

        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects(static::once())->method('getEvent')->willReturn($eventMock);

        $this->orderResourceMock->expects(static::once())
            ->method('saveAttribute')
            ->with($orderMock, Transaction::FIELD_SYNC_DATE)
            ->willReturnSelf();

        $this->creditmemoResourceMock->expects(static::never())->method('saveAttribute');

        $this->setExpectations();

        $this->sut->execute($observerMock);
    }

    public function testExecuteWithTransactionTypeOfCreditmemo()
    {
        $creditmemoMock = $this->createMock(Creditmemo::class);

        $eventMock = $this->getMockBuilder(Event::class)->addMethods(['getTransaction'])->getMock();
        $eventMock->expects(static::once())->method('getTransaction')->willReturn($creditmemoMock);

        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects(static::once())->method('getEvent')->willReturn($eventMock);

        $this->orderResourceMock->expects(static::never())->method('saveAttribute');

        $this->creditmemoResourceMock->expects(static::once())
            ->method('saveAttribute')
            ->with($creditmemoMock, Transaction::FIELD_SYNC_DATE)
            ->willReturnSelf();

        $this->setExpectations();

        $this->sut->execute($observerMock);
    }

    public function testExecuteWithTypeError()
    {
        $eventMock = $this->getMockBuilder(Event::class)->addMethods(['getTransaction'])->getMock();
        $eventMock->expects(static::once())->method('getTransaction')->willReturn(new \stdClass());

        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects(static::once())->method('getEvent')->willReturn($eventMock);

        // Not practical to check the actual Type Error message
        $this->loggerMock->expects(static::once())->method('log')->withAnyParameters();

        $this->setExpectations();

        $this->sut->execute($observerMock);
    }

    public function testExecuteWithException()
    {
        $orderMock = $this->createMock(Order::class);

        $eventMock = $this->getMockBuilder(Event::class)->addMethods(['getTransaction'])->getMock();
        $eventMock->expects(static::once())->method('getTransaction')->willReturn($orderMock);

        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects(static::once())->method('getEvent')->willReturn($eventMock);

        $this->orderResourceMock->expects(static::once())
            ->method('saveAttribute')
            ->with($orderMock, Transaction::FIELD_SYNC_DATE)
            ->willThrowException(
                new \Exception('Testing saveAttribute exception.')
            );

        $this->loggerMock->expects(static::once())->method('log')->with('Testing saveAttribute exception.', 'error');

        $this->setExpectations();

        $this->sut->execute($observerMock);
    }

    protected function setExpectations()
    {
        $this->sut = new TransactionSyncDateObserver(
            $this->loggerMock,
            $this->orderResourceMock,
            $this->creditmemoResourceMock
        );
    }
}
