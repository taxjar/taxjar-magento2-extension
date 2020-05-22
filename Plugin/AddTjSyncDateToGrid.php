<?php

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
     * @return \Magento\Framework\Data\Collection;
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
