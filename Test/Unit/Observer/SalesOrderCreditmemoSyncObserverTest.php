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

namespace Taxjar\SalesTax\Text\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Manager as EventManager;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Taxjar\SalesTax\Helper\Data;
use Taxjar\SalesTax\Observer\SalesOrderCreditmemoSyncObserver;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class SalesOrderCreditmemoSyncObserverTest extends UnitTestCase
{
    /**
     * @var EventManager|EventManager&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $eventManagerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Data|Data&\PHPUnit\Framework\MockObject\MockObject
     */
    private $helperMock;

    /**
     * @var SalesOrderCreditmemoSyncObserver
     */
    private $sut;

    protected function setUp(): void
    {
        $this->eventManagerMock = $this->createMock(EventManager::class);
        $this->helperMock = $this->createMock(Data::class);
    }

    public function testExecute()
    {
        $orderMock = $this->createMock(Order::class);
        $orderMock->expects(static::once())->method('getState')->willReturn('valid');

        $creditmemoMock = $this->createMock(Creditmemo::class);
        $creditmemoMock->expects(static::once())->method('getId')->willReturn(123);
        $creditmemoMock->expects(static::once())->method('getOrder')->willReturn($orderMock);

        $eventMock = $this->getMockBuilder(Event::class)->addMethods(['getCreditmemo'])->getMock();
        $eventMock->expects(static::once())->method('getCreditmemo')->willReturn($creditmemoMock);

        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects(static::once())->method('getEvent')->willReturn($eventMock);

        $this->helperMock->expects(static::once())->method('getSyncableOrderStates')->willReturn(['valid']);

        $this->eventManagerMock->expects(static::once())
            ->method('dispatch')
            ->with(
                'taxjar_salestax_transaction_sync',
                [
                    'transaction' => $creditmemoMock,
                    'force_sync' => false,
                ]
            );

        $this->setExpectations();

        $this->sut->execute($observerMock);
    }

    public function testExecuteInvalidCreditmemo()
    {
        $creditmemoMock = $this->createMock(Creditmemo::class);
        $creditmemoMock->expects(static::once())->method('getId')->willReturn(null);

        $eventMock = $this->getMockBuilder(Event::class)->addMethods(['getCreditmemo'])->getMock();
        $eventMock->expects(static::once())->method('getCreditmemo')->willReturn($creditmemoMock);

        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects(static::once())->method('getEvent')->willReturn($eventMock);

        $this->eventManagerMock->expects(static::never())->method('dispatch');

        $this->setExpectations();

        $this->sut->execute($observerMock);
    }

    protected function setExpectations()
    {
        $this->sut = new SalesOrderCreditmemoSyncObserver(
            $this->eventManagerMock,
            $this->helperMock
        );
    }
}
