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
 * @copyright  Copyright (c) 2020 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Plugin;

use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Grid\Collection as CreditmemoGridCollection;

class AddTjSyncDateToGrid
{
    /**
     * Join tj_salestax_sync_date in the order and creditmemo admin grids
     *
     * @param CollectionFactory $subject
     * @param $collection
     * @return \Magento\Framework\Data\Collection
     */
    public function afterGetReport(
        CollectionFactory $subject,
        $collection
    ) {
        if ($collection instanceof OrderGridCollection) {
            $collection->getSelect()->joinLeft(
                'sales_order',
                'main_table.entity_id = sales_order.entity_id',
                'tj_salestax_sync_date'
            );
        }

        if ($collection instanceof CreditmemoGridCollection) {
            $collection->getSelect()->joinLeft(
                'sales_creditmemo',
                'main_table.entity_id = sales_creditmemo.entity_id',
                'tj_salestax_sync_date'
            );
        }

        return $collection;
    }
}
