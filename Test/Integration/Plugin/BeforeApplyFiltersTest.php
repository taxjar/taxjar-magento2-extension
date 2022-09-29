<?php

namespace Taxjar\SalesTax\Test\Integration;

use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\FilterPool;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Grid\Collection as CreditmemoGridCollection;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Order\Grid\Collection as OrderCreditmemoGridCollection;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;
use Taxjar\SalesTax\Plugin\BeforeApplyFilters;

class BeforeApplyFiltersTest extends IntegrationTestCase
{
    public function testBeforeApplyFiltersWithOrderGridCollection()
    {
        $subject = $this->objectManager->get(FilterPool::class);
        $collection = $this->objectManager->get(OrderGridCollection::class);
        $criteria = $this->objectManager->get(SearchCriteriaInterface::class);

        $filter = $this->objectManager->get(\Magento\Framework\Api\Filter::class);
        $filter->setField('created_at');
        $filter->setValue('2022-09-01');
        $filter->setConditionType('gte');

        $filterGroup = $this->objectManager->get(\Magento\Framework\Api\Search\FilterGroup::class);
        $filterGroup->setFilters([$filter]);

        $criteria->setFilterGroups([$filterGroup]);

        $sut = $this->objectManager->get(BeforeApplyFilters::class);
        [$resultCollection, $resultCriteria] = $sut->beforeApplyFilters($subject, $collection, $criteria);

        $this->assertInstanceOf(OrderGridCollection::class, $resultCollection);
        $this->assertInstanceOf(SearchCriteriaInterface::class, $resultCriteria);
        $this->assertCount(1, $resultCriteria->getFilterGroups());

        $resultFilterGroup = $resultCriteria->getFilterGroups()[0];
        $this->assertCount(1, $resultFilterGroup->getFilters());

        $resultFilter = $resultFilterGroup->getFilters()[0];
        $this->assertEquals('main_table.created_at', $resultFilter->getField());
    }

    public function testBeforeApplyFiltersWithCreditmemoGridCollection()
    {
        $subject = $this->objectManager->get(FilterPool::class);
        $collection = $this->objectManager->get(CreditmemoGridCollection::class);
        $criteria = $this->objectManager->get(SearchCriteriaInterface::class);

        $filter = $this->objectManager->get(\Magento\Framework\Api\Filter::class);
        $filter->setField('created_at');
        $filter->setValue('2022-09-01');
        $filter->setConditionType('gte');

        $filterGroup = $this->objectManager->get(\Magento\Framework\Api\Search\FilterGroup::class);
        $filterGroup->setFilters([$filter]);

        $criteria->setFilterGroups([$filterGroup]);

        $sut = $this->objectManager->get(BeforeApplyFilters::class);
        [$resultCollection, $resultCriteria] = $sut->beforeApplyFilters($subject, $collection, $criteria);

        $this->assertInstanceOf(CreditmemoGridCollection::class, $resultCollection);
        $this->assertInstanceOf(SearchCriteriaInterface::class, $resultCriteria);
        $this->assertCount(1, $resultCriteria->getFilterGroups());

        $resultFilterGroup = $resultCriteria->getFilterGroups()[0];
        $this->assertCount(1, $resultFilterGroup->getFilters());

        $resultFilter = $resultFilterGroup->getFilters()[0];
        $this->assertEquals('main_table.created_at', $resultFilter->getField());
    }

    public function testBeforeApplyFiltersWithOrderCreditmemoGridCollection()
    {
        $subject = $this->objectManager->get(FilterPool::class);
        $collection = $this->objectManager->get(OrderCreditmemoGridCollection::class);
        $criteria = $this->objectManager->get(SearchCriteriaInterface::class);

        $filterCreatedAt = $this->objectManager->get(\Magento\Framework\Api\Filter::class);
        $filterCreatedAt->setField('created_at');
        $filterCreatedAt->setValue('2022-09-01');
        $filterCreatedAt->setConditionType('gte');

        $filterOrderId = $this->objectManager->get(\Magento\Framework\Api\Filter::class);
        $filterOrderId->setField('order_id');
        $filterOrderId->setValue('1');
        $filterOrderId->setConditionType('eq');

        $filterGroup = $this->objectManager->get(\Magento\Framework\Api\Search\FilterGroup::class);
        $filterGroup->setFilters([$filterCreatedAt, $filterOrderId]);

        $criteria->setFilterGroups([$filterGroup]);

        $sut = $this->objectManager->get(BeforeApplyFilters::class);
        [$resultCollection, $resultCriteria] = $sut->beforeApplyFilters($subject, $collection, $criteria);

        $this->assertInstanceOf(OrderCreditmemoGridCollection::class, $resultCollection);
        $this->assertInstanceOf(SearchCriteriaInterface::class, $resultCriteria);
        $this->assertCount(1, $resultCriteria->getFilterGroups());

        $resultFilterGroup = $resultCriteria->getFilterGroups()[0];
        $this->assertCount(2, $resultFilterGroup->getFilters());

        foreach ($resultFilterGroup->getFilters() as $filter) {
            $this->assertStringContainsString('main_table.', $filter->getField());
        }
    }
}
