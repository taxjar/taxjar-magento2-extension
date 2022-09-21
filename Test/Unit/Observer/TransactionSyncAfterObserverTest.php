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
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection;
use Taxjar\SalesTax\Observer\TransactionSyncAfterObserver;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class TransactionSyncAfterObserverTest extends UnitTestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ManagerInterface|ManagerInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $eventManagerMock;

    /**
     * @var TransactionSyncAfterObserver
     */
    private $sut;

    protected function setUp(): void
    {
        $this->eventManagerMock = $this->createMock(ManagerInterface::class);
    }

    public function testExecuteWithOrderAfterSyncSuccess()
    {
        $forceSync = true;
        $creditmemoMock = $this->createMock(Creditmemo::class);

        $creditmemoCollectionMock = $this->createMock(Collection::class);
        $creditmemoCollectionMock->expects(static::once())
            ->method('walk')
            ->withAnyParameters()
            ->willReturnCallback(function () use ($creditmemoMock, $forceSync) {
                $this->sut->dispatch($creditmemoMock, $forceSync);
            });

        $transactionMock = $this->createMock(Order::class);
        $transactionMock->expects(static::once())
            ->method('getCreditmemosCollection')
            ->willReturn($creditmemoCollectionMock);

        $this->eventManagerMock->expects(static::once())->method('dispatch')->with(
            'taxjar_salestax_transaction_sync',
            [
                'transaction' => $creditmemoMock,
                'force_sync' => $forceSync,
            ]
        );

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods([
                'getTransaction',
                'getForceSync',
                'getSuccess',
            ])
            ->getMock();

        $eventMock->expects(static::once())->method('getTransaction')->willReturn($transactionMock);
        $eventMock->expects(static::once())->method('getForceSync')->willReturn(true);
        $eventMock->expects(static::once())->method('getSuccess')->willReturn(true);

        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects(static::exactly(3))->method('getEvent')->willReturn($eventMock);

        $this->setExpectations();

        $this->sut->execute($observerMock);
    }

    public function testExecuteWithCreditmemoAfterSyncSuccess()
    {
        $transactionMock = $this->createMock(Creditmemo::class);

        $this->eventManagerMock->expects(static::never())->method('dispatch');

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods([
                'getTransaction',
                'getForceSync',
                'getSuccess',
            ])
            ->getMock();

        $eventMock->expects(static::once())->method('getTransaction')->willReturn($transactionMock);
        $eventMock->expects(static::once())->method('getForceSync')->willReturn(true);
        $eventMock->expects(static::once())->method('getSuccess')->willReturn(true);

        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects(static::exactly(3))->method('getEvent')->willReturn($eventMock);

        $this->setExpectations();

        $this->sut->execute($observerMock);
    }

    public function testExecuteWithOrderAfterSyncFailure()
    {
        $transactionMock = $this->createMock(Order::class);

        $this->eventManagerMock->expects(static::never())->method('dispatch');

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods([
                'getTransaction',
                'getForceSync',
                'getSuccess',
            ])
            ->getMock();

        $eventMock->expects(static::once())->method('getTransaction')->willReturn($transactionMock);
        $eventMock->expects(static::once())->method('getForceSync')->willReturn(true);
        $eventMock->expects(static::once())->method('getSuccess')->willReturn(false);

        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects(static::exactly(3))->method('getEvent')->willReturn($eventMock);

        $this->setExpectations();

        $this->sut->execute($observerMock);
    }

    public function testDispatchWithOrder()
    {
        $transactionMock = $this->createMock(Creditmemo::class);

        $this->eventManagerMock->expects(static::once())
            ->method('dispatch')
            ->with(
                'taxjar_salestax_transaction_sync',
                [
                    'transaction' => $transactionMock,
                    'force_sync' => true,
                ]
            );

        $this->setExpectations();

        $this->sut->dispatch($transactionMock, true);
    }

    protected function setExpectations()
    {
        $this->sut = new TransactionSyncAfterObserver(
            $this->eventManagerMock
        );
    }
}
