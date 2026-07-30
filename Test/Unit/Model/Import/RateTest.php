<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Model\Import;

use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Magento\Tax\Model\Calculation\Rule;
use Magento\Tax\Model\Calculation\RateFactory;
use Magento\Tax\Model\CalculationFactory;
use PHPUnit\Framework\TestCase;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\Import\Rate;

class RateTest extends TestCase
{
    /** @var Rate */
    private $rate;

    /** @var Rule */
    private $mockRule;

    protected function setUp(): void
    {
        $this->mockRule = $this->createStub(Rule::class);

        $this->rate = new Rate(
            $this->createStub(CacheInterface::class),
            $this->createStub(ScopeConfigInterface::class),
            $this->createStub(RateFactory::class),
            $this->createStub(CalculationFactory::class),
            $this->createStub(TaxRateRepositoryInterface::class),
            $this->createStub(RegionFactory::class),
            $this->createStub(SearchCriteriaBuilder::class),
            $this->createStub(FilterBuilder::class),
            $this->mockRule
        );
    }

    public function testGetExistingRatesReturnsEmptyArrayWhenNoRuleExists(): void
    {
        $this->mockRule->method('load')
            ->willReturn($this->mockRule);
        $this->mockRule->method('getId')
            ->willReturn(null);

        $result = $this->rate->getExistingRates();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetExistingRatesReturnsRatesWhenRuleExists(): void
    {
        $this->mockRule->method('load')
            ->willReturn($this->mockRule);
        $this->mockRule->method('getId')
            ->willReturn(1);
        $this->mockRule->method('getRates')
            ->willReturn([10, 20, 20, 30]);

        $result = $this->rate->getExistingRates();

        $this->assertIsArray($result);
        $this->assertEquals([10, 20, 30], array_values($result));
    }
}
