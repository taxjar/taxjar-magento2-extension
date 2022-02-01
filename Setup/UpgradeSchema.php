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
 * @copyright  Copyright (c) 2017 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Taxjar\SalesTax\Api\Data\Sales\Order\MetadataInterface;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '0.7.0') < 0) {
            $installer = $setup;
            $installer->startSetup();

            /**
            * Update table 'sales_order'
            */
            $installer->getConnection('sales')->addColumn(
                $installer->getTable('sales_order'),
                'tj_salestax_sync_date',
                [
                    'type' => Table::TYPE_TIMESTAMP,
                    'nullable' => true,
                    'comment' => 'Order sync date for TaxJar'
                ]
            );

            /**
            * Update table 'sales_credit_memo'
            */
            $installer->getConnection('sales')->addColumn(
                $installer->getTable('sales_creditmemo'),
                'tj_salestax_sync_date',
                [
                    'type' => Table::TYPE_TIMESTAMP,
                    'nullable' => true,
                    'comment' => 'Refund sync date for TaxJar'
                ]
            );

            $installer->endSetup();
        }

        if (version_compare($context->getVersion(), '1.0.0') < 0) {
            $installer = $setup;
            $installer->startSetup();

            /**
            * Update table 'tax_nexus'
            */
            $installer->getConnection()->addColumn(
                $installer->getTable('tax_nexus'),
                'store_id',
                [
                    'type' => Table::TYPE_SMALLINT,
                    'default' => 0,
                    'nullable' => false,
                    'unsigned' => true,
                    'comment' => 'Store ID'
                ]
            );

            $installer->endSetup();
        }

        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $installer = $setup;
            $installer->startSetup();

            /**
             * Create table 'tj_product_tax_categories'
             */
            $table = $installer->getConnection()
                ->newTable($installer->getTable('tj_product_tax_categories'))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'ID'
                )->addColumn(
                    'product_tax_code',
                    Table::TYPE_TEXT,
                    32,
                    ['nullable' => false, 'default' => ''],
                    'Product Tax Code'
                )->addColumn(
                    'name',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false, 'default' => ''],
                    'Name'
                )->addColumn(
                    'description',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false, 'default' => ''],
                    'Description'
                )->addColumn(
                    'plus_only',
                    Table::TYPE_BOOLEAN,
                    null,
                    ['nullable' => false, 'default' => false],
                    'Plus only'
                )->addIndex(
                    $installer->getIdxName('tj_product_tax_categories', 'product_tax_code'),
                    'product_tax_code'
                )->setComment('TaxJar Product Tax Codes');

            $installer->getConnection()->createTable($table);

            $installer->endSetup();
        }

        if (version_compare($context->getVersion(), '1.0.4', '<')) {
            $installer = $setup;
            $installer->startSetup();

            /**
             * Update table 'tj_product_tax_categories'
             */
            $installer->getConnection()->dropColumn(
                $installer->getTable('tj_product_tax_categories'),
                'plus_only'
            );

            $installer->endSetup();
        }

        if (version_compare($context->getVersion(), '1.0.6', '<')) {
            $installer = $setup;
            $installer->startSetup();

            /**
             * Update table 'sales_order_item'
             */
            $installer->getConnection()->addColumn(
                $installer->getTable('sales_order_item'),
                'tj_ptc',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 32,
                    'nullable' => true,
                    'default' => null,
                    'comment' => 'TaxJar Product Tax Code'
                ]
            );

            $installer->endSetup();
        }

        /**
         * Creates table `tj_sales_order_metadata`
         */
        if (version_compare($context->getVersion(), '1.0.7', '<')) {
            $setup->startSetup();

            $table = $setup
                ->getConnection()
                ->newTable($setup->getTable(Metadata::TABLE))
                ->setComment('TaxJar Sales Order Metadata')
                ->addColumn(
                    MetadataInterface::ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'primary'  => true,
                        'nullable' => false
                    ]
                )
                ->addColumn(
                    MetadataInterface::ORDER_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => false,
                        'type'     => Table::TYPE_INTEGER,
                        'comment'  => 'Order ID'
                    ]
                )
                ->addColumn(
                    MetadataInterface::TAX_CALCULATION_STATUS,
                    Table::TYPE_TEXT,
                    null,
                    [
                        'nullable' => false,
                        'comment'  => 'Tax Calculation Status'
                    ]
                )
                ->addColumn(
                    MetadataInterface::TAX_CALCULATION_MESSAGE,
                    Table::TYPE_TEXT,
                    null,
                    [
                        'nullable' => false,
                        'comment'  => 'Tax Calculation Status'
                    ]
                )
                ->addForeignKey(
                    $setup->getFkName(
                        $setup->getTable(Metadata::TABLE),
                        MetadataInterface::ORDER_ID,
                        'sales_order',
                        'entity_id'
                    ),
                    MetadataInterface::ORDER_ID,
                    $setup->getTable('sales_order'),
                    'entity_id',
                    Table::ACTION_CASCADE
                )
                ->addIndex(
                    $setup->getIdxName(
                        Metadata::TABLE,
                        [MetadataInterface::ORDER_ID],
                        AdapterInterface::INDEX_TYPE_UNIQUE
                    ),
                    [MetadataInterface::ORDER_ID],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                );

            $setup->getConnection()->createTable($table);
            $setup->endSetup();
        }
    }
}
