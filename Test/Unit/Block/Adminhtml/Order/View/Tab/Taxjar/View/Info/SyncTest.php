<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Block\Adminhtml\Order\View\Tab\Taxjar\View\Info;

use IntlDateFormatter;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Taxjar\SalesTax\Block\Adminhtml\Order\View\Tab\Taxjar\View\Info\Sync;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

#[AllowMockObjectsWithoutExpectations]
class SyncTest extends UnitTestCase
{
    public function testGetFormattedSyncDateReturnsFormattedDateForSyncedOrder()
    {
        $syncedAt = '2021-01-01 12:00:00';
        $localizedDate = new \DateTime('2021-01-01 13:00:00');

        $localeDateMock = $this->createMock(TimezoneInterface::class);
        $localeDateMock->expects($this->once())
            ->method('date')
            ->with($this->callback(function ($date) use ($syncedAt) {
                return $date->format('Y-m-d H:i:s') === $syncedAt;
            }))
            ->willReturn($localizedDate);

        $sut = $this->getMockBuilder(Sync::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['formatDate'])
            ->getMock();
        $sut->expects($this->once())
            ->method('formatDate')
            ->with($localizedDate, IntlDateFormatter::MEDIUM, true)
            ->willReturn('Jan 1, 2021, 1:00:00 PM');
        $this->setLocaleDate($sut, $localeDateMock);

        $this->assertSame('Jan 1, 2021, 1:00:00 PM', $sut->getFormattedSyncDate($this->createOrder($syncedAt)));
    }

    public function testGetFormattedSyncDateReturnsNullForUnsyncedOrder()
    {
        $sut = $this->getMockBuilder(Sync::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['formatDate'])
            ->getMock();
        $sut->expects($this->never())->method('formatDate');

        $this->assertNull($sut->getFormattedSyncDate($this->createOrder(null)));
    }

    private function createOrder(?string $syncedAt): Order
    {
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $order->setData('tj_salestax_sync_date', $syncedAt);

        return $order;
    }

    private function setLocaleDate(Sync $block, TimezoneInterface $localeDate): void
    {
        $property = new \ReflectionProperty(Sync::class, '_localeDate');
        $property->setValue($block, $localeDate);
    }
}
