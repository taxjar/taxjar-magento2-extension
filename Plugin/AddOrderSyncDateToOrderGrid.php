<?php

namespace Taxjar\SalesTax\Plugin;

use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection;

class AddOrderSyncDateToOrderGrid
{
    /**
     * @var Collection
     */
    private $collection;

    public function __construct(
        Collection $collection
    ) {
        $this->collection = $collection;
    }

    /**
     * Join tj_salestax_sync_date in order grid
     *
     * @param CollectionFactory $subject
     * @param $collection
     * @return \Magento\Framework\Data\Collection
     */
    public function afterGetReport(
        CollectionFactory $subject,
        $collection
    ) {
        if ($collection instanceof Collection) {
            $select = $collection->getSelect();
            $select->joinLeft(
                ['so' => 'sales_order'],
                'main_table.entity_id = so.entity_id',
                'tj_salestax_sync_date'
            );
        }

        return $collection;
    }
}
