<?php

namespace Taxjar\SalesTax\Test\Integration\Observer;

use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;
use Magento\Customer\Model\ResourceModel\Online\Grid\Collection as CustomerGridCollection;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Grid\Collection as CreditmemoGridCollection;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Order\Grid\Collection as OrderCreditmemoGridCollection;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;
use Taxjar\SalesTax\Plugin\AddTjSyncDateToGrid;
use Taxjar\SalesTax\Test\Integration\IntegrationTestCase;

class AddTjSyncDateToGridTest extends IntegrationTestCase
{
    public function testAfterGetReportOrderGrid()
    {
        $subject = $this->objectManager->get(CollectionFactory::class);
        $collection = $this->objectManager->get(OrderGridCollection::class);

        /** @var AddTjSyncDateToGrid $sut */
        $sut = $this->objectManager->get(AddTjSyncDateToGrid::class);
        $result = $sut->afterGetReport($subject, $collection);

        $joinParts = $result->getSelect()->getPart(\Magento\Framework\DB\Select::FROM);

        $expectedJoinParts = [
            'joinType' => 'left join',
            'tableName' => 'sales_order',
            'joinCondition' => 'main_table.entity_id = sales_alias.entity_id',
        ];

        $this->assertCount(2, $joinParts);
        $this->assertArrayHasKey('main_table', $joinParts);
        $this->assertArrayHasKey('sales_alias', $joinParts);
        $this->assertSame($expectedJoinParts['joinType'], $joinParts['sales_alias']['joinType']);
        $this->assertSame($expectedJoinParts['tableName'], $joinParts['sales_alias']['tableName']);
        $this->assertSame($expectedJoinParts['joinCondition'], $joinParts['sales_alias']['joinCondition']);
    }

    public function testAfterGetReportCreditmemoGrid()
    {
        $subject = $this->objectManager->get(CollectionFactory::class);
        $collection = $this->objectManager->get(CreditmemoGridCollection::class);

        /** @var AddTjSyncDateToGrid $sut */
        $sut = $this->objectManager->get(AddTjSyncDateToGrid::class);
        $result = $sut->afterGetReport($subject, $collection);

        $joinParts = $result->getSelect()->getPart(\Magento\Framework\DB\Select::FROM);

        $expectedJoinParts = [
            'joinType' => 'left join',
            'tableName' => 'sales_creditmemo',
            'joinCondition' => 'main_table.entity_id = sales_alias.entity_id',
        ];

        $this->assertCount(2, $joinParts);
        $this->assertArrayHasKey('main_table', $joinParts);
        $this->assertArrayHasKey('sales_alias', $joinParts);
        $this->assertSame($expectedJoinParts['joinType'], $joinParts['sales_alias']['joinType']);
        $this->assertSame($expectedJoinParts['tableName'], $joinParts['sales_alias']['tableName']);
        $this->assertSame($expectedJoinParts['joinCondition'], $joinParts['sales_alias']['joinCondition']);
    }

    public function testAfterGetReportOrderCreditmemoGrid()
    {
        $subject = $this->objectManager->get(CollectionFactory::class);
        $collection = $this->objectManager->get(OrderCreditmemoGridCollection::class);

        /** @var AddTjSyncDateToGrid $sut */
        $sut = $this->objectManager->get(AddTjSyncDateToGrid::class);
        $result = $sut->afterGetReport($subject, $collection);

        $joinParts = $result->getSelect()->getPart(\Magento\Framework\DB\Select::FROM);

        $expectedJoinParts = [
            'joinType' => 'left join',
            'tableName' => 'sales_creditmemo',
            'joinCondition' => 'main_table.entity_id = sales_alias.entity_id',
        ];

        $this->assertCount(2, $joinParts);
        $this->assertArrayHasKey('main_table', $joinParts);
        $this->assertArrayHasKey('sales_alias', $joinParts);
        $this->assertSame($expectedJoinParts['joinType'], $joinParts['sales_alias']['joinType']);
        $this->assertSame($expectedJoinParts['tableName'], $joinParts['sales_alias']['tableName']);
        $this->assertSame($expectedJoinParts['joinCondition'], $joinParts['sales_alias']['joinCondition']);
    }

    public function testAfterGetReportOtherGrid()
    {
        $subject = $this->objectManager->get(CollectionFactory::class);
        $collection = $this->objectManager->get(CustomerGridCollection::class);

        /** @var AddTjSyncDateToGrid $sut */
        $sut = $this->objectManager->get(AddTjSyncDateToGrid::class);
        $result = $sut->afterGetReport($subject, $collection);

        $joinParts = $result->getSelect()->getPart(\Magento\Framework\DB\Select::FROM);

        $this->assertArrayHasKey('main_table', $joinParts);
        $this->assertArrayNotHasKey('sales_alias', $joinParts);
    }
}
