<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Model\Import;

use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\Calculation\Rule;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class RuleTest extends UnitTestCase
{
    public function testCreate()
    {
        $code = 'code';
        $rates = [
            'rate-data'
        ];
        $customerClasses = ['1'];
        $productClasses = ['2'];
        $position = 1;

        $mockRule = $this->createMock(Rule::class);
        $mockRule->expects($this->once())->method('getRates')->willReturn([]);
        $mockRule->expects($this->once())->method('load')->with($code, 'code');
        $mockRule->expects($this->once())->method('setTaxRateIds')->with($rates);
        $mockRule->expects($this->once())->method('setCode')->with($code);
        $mockRule->expects($this->once())->method('setCustomerTaxClassIds')->with($customerClasses);
        $mockRule->expects($this->once())->method('setProductTaxClassIds')->with($productClasses);
        $mockRule->expects($this->once())->method('setPosition')->with($position);
        $mockRule->expects($this->once())->method('setPriority')->with(1);
        $mockRule->expects($this->once())->method('setCalculateSubtotal')->with(0);

        $mockRuleFactory = $this->getMockBuilder(\Taxjar\SalesTax\Model\Import\RuleModelFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $mockRuleRepository = $this->getMockBuilder(\Magento\Tax\Api\TaxRuleRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get', 'getList', 'save', 'delete', 'deleteById'])
            ->getMock();

        $mockRuleFactory->expects($this->once())->method('create')->willReturn($mockRule);
        $mockRuleRepository->expects($this->once())->method('save')->willReturn($mockRule);

        $sut = $this->getMockBuilder(\Taxjar\SalesTax\Model\Import\Rule::class)
            ->setConstructorArgs([$mockRuleFactory, $mockRuleRepository])
            ->onlyMethods(['saveCalculationData'])
            ->getMock();

        $sut->expects($this->any())->method('saveCalculationData')->willReturn(true);

        $result = $sut->create($code, $customerClasses, $productClasses, $position, $rates);

        self::assertInstanceOf(Rule::class, $result);
    }

    public function testSaveCalculationData()
    {
        $mockCalculation = $this->getMockBuilder(Calculation::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'setData',
                'save',
                'getCollection',
                'getId',
                'delete'
            ])
            ->addMethods([
                'addFieldToFilter',
                'getFirstItem'
            ])
            ->getMock();

        $mockCalculation->expects($this->any())->method('getCollection')->willReturnSelf();
        $mockCalculation->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $mockCalculation->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $mockCalculation->expects($this->any())->method('getId')->willReturn(99);
        $mockCalculation->expects($this->any())->method('delete')->willReturn(true);

        $setDataCallCount = 0;
        $mockCalculation->expects($this->exactly(4))
            ->method('setData')
            ->willReturnCallback(function ($data) use (&$setDataCallCount, $mockCalculation) {
                $setDataCallCount++;

                $expectedData = [
                    [
                        'tax_calculation_rule_id' => 999,
                        'tax_calculation_rate_id' => 1,
                        'customer_tax_class_id' => 1,
                        'product_tax_class_id' => 3,
                    ],
                    [
                        'tax_calculation_rule_id' => 999,
                        'tax_calculation_rate_id' => 1,
                        'customer_tax_class_id' => 1,
                        'product_tax_class_id' => 4,
                    ],
                    [
                        'tax_calculation_rule_id' => 999,
                        'tax_calculation_rate_id' => 1,
                        'customer_tax_class_id' => 2,
                        'product_tax_class_id' => 3,
                    ],
                    [
                        'tax_calculation_rule_id' => 999,
                        'tax_calculation_rate_id' => 1,
                        'customer_tax_class_id' => 2,
                        'product_tax_class_id' => 4,
                    ],
                ];

                $this->assertSame($expectedData[$setDataCallCount - 1], $data);
                return $mockCalculation;
            });

        $mockCalculation->expects($this->exactly(4))
            ->method('save')
            ->willReturn(true);

        $mockRule = $this->createMock(Rule::class);
        $mockRule->expects($this->exactly(2))
            ->method('getData')
            ->willReturnCallback(function ($key) {
                static $callCount = 0;
                $callCount++;

                switch ($callCount) {
                    case 1:
                        $this->assertEquals('customer_tax_class_ids', $key);
                        return [1, 2];
                    case 2:
                        $this->assertEquals('product_tax_class_ids', $key);
                        return [3, 4];
                    default:
                        throw new \Exception('Unexpected call count: ' . $callCount);
                }
            });

        $mockRule->expects($this->exactly(4))
            ->method('getId')
            ->willReturn(999);

        $mockRule->expects($this->exactly(4))
            ->method('getCalculationModel')
            ->willReturn($mockCalculation);

        $mockRuleFactory = $this->getMockBuilder(\Taxjar\SalesTax\Model\Import\RuleModelFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $mockRuleRepository = $this->getMockBuilder(\Magento\Tax\Api\TaxRuleRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get', 'getList', 'save', 'delete', 'deleteById'])
            ->getMock();

        $sut = new \Taxjar\SalesTax\Model\Import\Rule(
            $mockRuleFactory,
            $mockRuleRepository
        );

        $sut->saveCalculationData($mockRule, [1]);
    }
}
