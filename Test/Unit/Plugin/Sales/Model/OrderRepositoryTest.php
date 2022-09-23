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

namespace TaxJax\SalesTax\Test\Unit\Plugin\Sales\Model;

use Magento\Sales\Api\Data\OrderExtension;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository as OrderRepositoryModel;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata\Collection;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata\CollectionFactory;
use Taxjar\SalesTax\Model\Sales\Order\Metadata;
use Taxjar\SalesTax\Plugin\Sales\Model\OrderRepository;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class OrderRepositoryTest extends UnitTestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CollectionFactory|CollectionFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $collectionFactoryMock;

    /**
     * @var OrderRepository
     */
    private $sut;

    protected function setUp(): void
    {
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
    }

    public function testAfterGet()
    {
        $syncDate = '2022-09-23 01:23:45.0';

        $metadataMock = $this->createMock(Metadata::class);
        $metadataMock->expects(static::once())->method('getSyncedAt')->willReturn($syncDate);

        $collectionMock = $this->createMock(Collection::class);
        $collectionMock->expects(static::once())->method('addFieldToFilter')->with('order_id', 12345)->willReturnSelf();
        $collectionMock->expects(static::once())->method('setPageSize')->with(1)->willReturnSelf();
        $collectionMock->expects(static::once())->method('setCurPage')->with(1)->willReturnSelf();
        $collectionMock->expects(static::once())->method('getFirstItem')->willReturn($metadataMock);

        $orderExtensionMock = $this->createMock(OrderExtension::class);
        $orderExtensionMock->expects(static::once())->method('setTjSyncedAt')->with($syncDate)->willReturn(true);

        $orderMock = $this->createMock(Order::class);
        $orderMock->expects(static::once())
            ->method('getEntityId')
            ->willReturn(12345);
        $orderMock->expects(static::once())
            ->method('getExtensionAttributes')
            ->willReturn($orderExtensionMock);
        $orderMock->expects(static::once())
            ->method('setExtensionAttributes')
            ->with($orderExtensionMock)
            ->willReturnSelf();

        $this->collectionFactoryMock->expects(static::once())->method('create')->willReturn($collectionMock);

        $orderRepositoryMock = $this->createMock(OrderRepositoryModel::class);

        $this->setExpectations();

        $this->sut->afterGet($orderRepositoryMock, $orderMock);
    }

    public function testAfterGetList()
    {
        $syncDate = '2022-09-23 01:23:45.0';

        $metadataMock = $this->createMock(Metadata::class);
        $metadataMock->expects(static::once())->method('getSyncedAt')->willReturn($syncDate);

        $collectionMock = $this->createMock(Collection::class);
        $collectionMock->expects(static::once())->method('addFieldToFilter')->with('order_id', 12345)->willReturnSelf();
        $collectionMock->expects(static::once())->method('setPageSize')->with(1)->willReturnSelf();
        $collectionMock->expects(static::once())->method('setCurPage')->with(1)->willReturnSelf();
        $collectionMock->expects(static::once())->method('getFirstItem')->willReturn($metadataMock);

        $orderExtensionMock = $this->createMock(OrderExtension::class);
        $orderExtensionMock->expects(static::once())->method('setTjSyncedAt')->with($syncDate)->willReturn(true);

        $orderMock = $this->createMock(Order::class);
        $orderMock->expects(static::once())
            ->method('getEntityId')
            ->willReturn(12345);
        $orderMock->expects(static::once())
            ->method('getExtensionAttributes')
            ->willReturn($orderExtensionMock);
        $orderMock->expects(static::once())
            ->method('setExtensionAttributes')
            ->with($orderExtensionMock)
            ->willReturnSelf();

        $orderSearchResultMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\Collection::class);
        $orderSearchResultMock->expects(static::once())->method('getItems')->willReturn([$orderMock]);

        $this->collectionFactoryMock->expects(static::once())->method('create')->willReturn($collectionMock);

        $orderRepositoryMock = $this->createMock(OrderRepositoryModel::class);

        $this->setExpectations();

        $this->sut->afterGetList($orderRepositoryMock, $orderSearchResultMock);
    }

    protected function setExpectations()
    {
        $this->sut = new OrderRepository(
            $this->collectionFactoryMock
        );
    }
}
