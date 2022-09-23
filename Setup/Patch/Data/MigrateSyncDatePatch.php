<?php

namespace Taxjar\SalesTax\Setup\Patch\Data;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Taxjar\SalesTax\Model\Sales\Order\Creditmemo\Metadata as CreditmemoMetadata;
use Taxjar\SalesTax\Model\Sales\Order\Metadata as OrderMetadata;

class MigrateSyncDatePatch implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory
     */
    private \Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory $creditmemoCollection;

    /**
     * @var \Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata\CollectionFactory
     */
    private \Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata\CollectionFactory $orderMetadataCollection;

    /**
     * @var \Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Creditmemo\Metadata\CollectionFactory
     */
    private \Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Creditmemo\Metadata\CollectionFactory $creditmemoMetadataCollection;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CollectionFactory $orderCollection
     * @param \Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory $creditmemoCollection
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection,
        \Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory $creditmemoCollection,
        \Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata\CollectionFactory $orderMetadataCollection,
        \Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Creditmemo\Metadata\CollectionFactory $creditmemoMetadataCollection
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
