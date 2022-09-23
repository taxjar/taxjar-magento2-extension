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

namespace Taxjar\SalesTax\Setup\Patch\Data;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory as CreditmemoCollectionFactory;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Creditmemo\Metadata\CollectionFactory
    as CreditmemoMetadataCollectionFactory;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata\CollectionFactory as OrderMetadataCollectionFactory;
use Taxjar\SalesTax\Model\Sales\Order\Creditmemo\Metadata as CreditmemoMetadata;
use Taxjar\SalesTax\Model\Sales\Order\Metadata as OrderMetadata;

class MigrateSyncDatePatch implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollection;

    /**
     * @var CreditmemoCollectionFactory
     */
    private $creditmemoCollection;

    /**
     * @var OrderMetadataCollectionFactory
     */
    private $orderMetadataCollection;

    /**
     * @var CreditmemoMetadataCollectionFactory
     */
    private $creditmemoMetadataCollection;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param OrderCollectionFactory $orderCollection
     * @param CreditmemoCollectionFactory $creditmemoCollection
     * @param OrderMetadataCollectionFactory $orderMetadataCollection
     * @param CreditmemoMetadataCollectionFactory $creditmemoMetadataCollection
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        OrderCollectionFactory $orderCollection,
        CreditmemoCollectionFactory $creditmemoCollection,
        OrderMetadataCollectionFactory $orderMetadataCollection,
        CreditmemoMetadataCollectionFactory $creditmemoMetadataCollection
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->orderCollection = $orderCollection;
        $this->creditmemoCollection = $creditmemoCollection;
        $this->orderMetadataCollection = $orderMetadataCollection;
        $this->creditmemoMetadataCollection = $creditmemoMetadataCollection;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->orderCollection->create()
            ->addFieldToFilter('entity_id', ['neq' => 'NULL'])
            ->addFieldToFilter('tj_salestax_sync_date', ['neq' => 'NULL'])
            ->walk([$this, 'migrateOrderSyncDate']);

        $this->creditmemoCollection->create()
            ->addFieldToFilter('entity_id', ['neq' => 'NULL'])
            ->addFieldToFilter('tj_salestax_sync_date', ['neq' => 'NULL'])
            ->walk([$this, 'migrateCreditmemoSyncDate']);

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [
            AddProductTaxCategoryPatch::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return void
     * @throws AlreadyExistsException
     */
    public function migrateOrderSyncDate(Order $order)
    {
        /** @var OrderMetadata $metadata */
        $metadata = $this->orderMetadataCollection->create()
            ->addFieldToFilter('order_id', $order->getId())
            ->getFirstItem();

        $metadata->setSyncedAt($order->getData('tj_salestax_sync_date'));
        $metadata->setOrderId($order->getIncrementId());
        $metadata->getResource()->save($metadata);
    }

    /**
     * @return void
     * @throws AlreadyExistsException
     */
    public function migrateCreditmemoSyncDate(Order\Creditmemo $creditmemo)
    {
        /** @var CreditmemoMetadata $metadata */
        $metadata = $this->creditmemoMetadataCollection->create()
            ->addFieldToFilter('creditmemo_id', $creditmemo->getId())
            ->getFirstItem();

        $metadata->setSyncedAt($creditmemo->getData('tj_salestax_sync_date'));
        $metadata->setCreditmemoId($creditmemo->getIncrementId());
        $metadata->getResource()->save($metadata);
    }
}
