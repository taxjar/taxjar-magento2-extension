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


        if (version_compare($context->getVersion(), '1.1.0') < 0) {
            $installer = $setup;
            $installer->startSetup();

            /**
             * Update table 'tax_class'
             */
            $installer->getConnection()->addColumn(
                $installer->getTable('tax_class'),
                'tj_salestax_exempt_type',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'default' => 'non_exempt',
                    'length' => 255,
                    'nullable' => false,
                    'comment' => 'TaxJar Sales Tax Exempt Type'
                ]
            );

            /**
             * Update table 'customer_entity'
             */
            $installer->getConnection()->addColumn(
                $installer->getTable('customer_entity'),
                'tj_salestax_sync_date',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    'default' => '',
                    'nullable' => true,
                    'comment' => 'TaxJar Sales Tax Last Sync Date'
                ]
            );

            $installer->endSetup();
        }
    }
}
