<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Block\Adminhtml\Order\View;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\TestFramework\ObjectManager;
use Taxjar\SalesTax\Block\Adminhtml\Order\View\Synced;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class SyncedTest extends UnitTestCase
{
    public function testClassExists()
    {
        static::assertTrue(class_exists(Synced::class));
    }

    public function testGetSyncedAtDate()
    {
        $orderMock = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getTjSalestaxSyncDate'])
            ->getMockForAbstractClass();
        $orderMock->expects(static::once())->method('getTjSalestaxSyncDate');

        // Create Synced object directly since it's a simple class
        $sut = $this->getMockBuilder(Synced::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $result = $sut->getSyncedAtDate($orderMock);
    }
}
