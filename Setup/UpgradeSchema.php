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

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

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
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
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
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
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
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
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
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'ID'
                )->addColumn(
                    'product_tax_code',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    32,
                    ['nullable' => false, 'default' => ''],
                    'Product Tax Code'
                )->addColumn(
                    'name',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => false, 'default' => ''],
                    'Name'
                )->addColumn(
                    'description',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => false, 'default' => ''],
                    'Description'
                )->addColumn(
                    'plus_only',
                    \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
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
    }
}
