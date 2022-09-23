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

namespace TaxJax\SalesTax\Test\Unit\Plugin\Sales\Model\Order;

use Magento\Sales\Api\Data\CreditmemoExtension;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\CreditmemoRepository as CreditmemoRepositoryModel;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection as CreditmemoCollection;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Creditmemo\Metadata\Collection;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Creditmemo\Metadata\CollectionFactory;
use Taxjar\SalesTax\Model\Sales\Order\Creditmemo\Metadata;
use Taxjar\SalesTax\Plugin\Sales\Model\Order\CreditmemoRepository;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class CreditmemoRepositoryTest extends UnitTestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CollectionFactory|CollectionFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $collectionFactoryMock;

    /**
     * @var CreditmemoRepository
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
        $collectionMock->expects(static::once())
            ->method('addFieldToFilter')
            ->with('creditmemo_id', 12345)
            ->willReturnSelf();
        $collectionMock->expects(static::once())->method('setPageSize')->with(1)->willReturnSelf();
        $collectionMock->expects(static::once())->method('setCurPage')->with(1)->willReturnSelf();
        $collectionMock->expects(static::once())->method('getFirstItem')->willReturn($metadataMock);

        $creditmemoExtensionMock = $this->createMock(CreditmemoExtension::class);
        $creditmemoExtensionMock->expects(static::once())->method('setTjSyncedAt')->with($syncDate)->willReturn(true);

        $creditmemoMock = $this->createMock(Creditmemo::class);
        $creditmemoMock->expects(static::once())
            ->method('getEntityId')
            ->willReturn(12345);
        $creditmemoMock->expects(static::once())
            ->method('getExtensionAttributes')
            ->willReturn($creditmemoExtensionMock);
        $creditmemoMock->expects(static::once())
            ->method('setExtensionAttributes')
            ->with($creditmemoExtensionMock)
            ->willReturnSelf();

        $this->collectionFactoryMock->expects(static::once())->method('create')->willReturn($collectionMock);

        $creditmemoRepositoryMock = $this->createMock(CreditmemoRepositoryModel::class);

        $this->setExpectations();

        $this->sut->afterGet($creditmemoRepositoryMock, $creditmemoMock);
    }

    public function testAfterGetList()
    {
        $syncDate = '2022-09-23 01:23:45.0';

        $metadataMock = $this->createMock(Metadata::class);
        $metadataMock->expects(static::once())->method('getSyncedAt')->willReturn($syncDate);

        $collectionMock = $this->createMock(Collection::class);
        $collectionMock->expects(static::once())
            ->method('addFieldToFilter')
            ->with('creditmemo_id', 12345)
            ->willReturnSelf();
        $collectionMock->expects(static::once())->method('setPageSize')->with(1)->willReturnSelf();
        $collectionMock->expects(static::once())->method('setCurPage')->with(1)->willReturnSelf();
        $collectionMock->expects(static::once())->method('getFirstItem')->willReturn($metadataMock);

        $creditmemoExtensionMock = $this->createMock(CreditmemoExtension::class);
        $creditmemoExtensionMock->expects(static::once())->method('setTjSyncedAt')->with($syncDate)->willReturn(true);

        $creditmemoMock = $this->createMock(Creditmemo::class);
        $creditmemoMock->expects(static::once())
            ->method('getEntityId')
            ->willReturn(12345);
        $creditmemoMock->expects(static::once())
            ->method('getExtensionAttributes')
            ->willReturn($creditmemoExtensionMock);
        $creditmemoMock->expects(static::once())
            ->method('setExtensionAttributes')
            ->with($creditmemoExtensionMock)
            ->willReturnSelf();

        $creditmemoSearchResultMock = $this->createMock(CreditmemoCollection::class);
        $creditmemoSearchResultMock->expects(static::once())->method('getItems')->willReturn([$creditmemoMock]);

        $this->collectionFactoryMock->expects(static::once())->method('create')->willReturn($collectionMock);

        $creditmemoRepositoryMock = $this->createMock(CreditmemoRepositoryModel::class);

        $this->setExpectations();

        $this->sut->afterGetList($creditmemoRepositoryMock, $creditmemoSearchResultMock);
    }

    protected function setExpectations()
    {
        $this->sut = new CreditmemoRepository(
            $this->collectionFactoryMock
        );
    }
}
