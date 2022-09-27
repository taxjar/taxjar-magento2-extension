<?php

namespace Taxjar\SalesTax\Plugin;

use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\View\Element\UiComponent\DataProvider\FilterPool;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Grid\Collection as CreditmemoGridCollection;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Order\Grid\Collection as OrderCreditmemoGridCollection;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;

class BeforeApplyFilters
{
    /**
     * Fix for ambiguous queries in `AddTjSyncDateToGrid::class` due to joining `sales_...` tables.
     *
     * @param FilterPool $subject
     * @param Collection|AbstractDb $collection
     * @param SearchCriteriaInterface $criteria
     * @return array
     */
    public function beforeApplyFilters(
        FilterPool $subject,
        Collection $collection,
        SearchCriteriaInterface $criteria
    ): array {
        if ($collection instanceof OrderGridCollection
            || $collection instanceof CreditmemoGridCollection
            || $collection instanceof OrderCreditmemoGridCollection
        ) {
            foreach ($criteria->getFilterGroups() as $filterGroup) {
                foreach ($filterGroup->getFilters() as $filter) {
                    if ($filter->getField() == 'created_at') {
                        $filter->setField('main_table.created_at');
                    }
                }
            }
        }

        if ($collection instanceof OrderCreditmemoGridCollection) {
            foreach ($criteria->getFilterGroups() as $filterGroup) {
                foreach ($filterGroup->getFilters() as $filter) {
                    if ($filter->getField() == 'order_id') {
                        $filter->setField('main_table.order_id');
                    }
                }
            }
        }

        return [$collection, $criteria];
    }
}
