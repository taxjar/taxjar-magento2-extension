<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Block\Adminhtml\Order\View;

use Magento\Sales\Model\Order;
use Taxjar\SalesTax\Block\Adminhtml\Order\View\Synced;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

#[AllowMockObjectsWithoutExpectations]
class SyncedTest extends UnitTestCase
{
    public function testClassExists()
    {
        static::assertTrue(class_exists(Synced::class));
    }

    public function testGetSyncedAtDate()
    {
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $orderMock->setData('tj_salestax_sync_date', '2021-01-01 12:00:00');

        $sut = $this->getMockBuilder(Synced::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $result = $sut->getSyncedAtDate($orderMock);
        $this->assertSame('2021-01-01 12:00:00', $result);
    }
}
