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
            ->setMethods(['getTjSalestaxSyncDate'])
            ->getMockForAbstractClass();
        $orderMock->expects(static::once())->method('getTjSalestaxSyncDate');
        $sut = $this->objectManager->getObject(Synced::class);
        $sut->getSyncedAtDate($orderMock);
    }
}
