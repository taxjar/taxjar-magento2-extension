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

namespace Taxjar\SalesTax\Test\Integration\Model\Tax\Sales\Total\Quote;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/SetupUtil.php';

class TaxTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Utility object for setting up tax rates, tax classes and tax rules
     *
     * @var SetupUtil
     */
    protected $setupUtil = null;

    /**
     * Test tax calculation with various configuration and combination of items
     * This method will test various collectors through $quoteAddress->collectTotals() method
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @dataProvider taxDataProvider
     */
    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
    #[DataProvider('taxDataProvider')]
    public function testTaxCalculation(array $configData, array $quoteData, array $expectedResults)
    {
        /** @var  \Magento\Framework\ObjectManagerInterface $objectManager */
        $objectManager = Bootstrap::getObjectManager();
        /** @var  \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector */
        $totalsCollector = $objectManager->create('Magento\Quote\Model\Quote\TotalsCollector');

        $this->setupUtil = new SetupUtil($objectManager);
        $this->setupUtil->setupTax($configData);

        $quote = $this->setupUtil->setupQuote($quoteData);
        $quoteAddress = $quote->getShippingAddress();
        $totalsCollector->collectAddressTotals($quote, $quoteAddress);
        $this->verifyResult($quoteAddress, $expectedResults);
    }

    /**
     * Verify fields in quote item
     *
     * @param \Magento\Quote\Model\Quote\Address\Item $item
     * @param array $expectedItemData
     * @return $this
     */
    protected function verifyItem($item, $expectedItemData)
    {
        foreach ($expectedItemData as $key => $value) {
            if ($key === 'applied_taxes') {
                $actual = $item->getData($key);
                foreach ($value as $i => $expectedTax) {
                    foreach ($expectedTax as $taxKey => $taxValue) {
                        if ($taxKey === 'item_id') {
                            continue;
                        }
                        $this->assertEqualsWithDelta(
                            $taxValue,
                            $actual[$i][$taxKey],
                            0.01,
                            'item applied_taxes[' . $i . '][' . $taxKey . '] is incorrect'
                        );
                    }
                }
                continue;
            }
            $this->assertEqualsWithDelta($value, $item->getData($key), 0.01, 'item ' . $key . ' is incorrect');
        }

        return $this;
    }

    /**
     * Verify one tax rate in a tax row
     *
     * @param array $appliedTaxRate
     * @param array $expectedAppliedTaxRate
     * @return $this
     */
    protected function verifyAppliedTaxRate($appliedTaxRate, $expectedAppliedTaxRate)
    {
        foreach ($expectedAppliedTaxRate as $key => $value) {
            $this->assertEqualsWithDelta(
                $value,
                $appliedTaxRate[$key],
                0.01,
                'Applied tax rate ' . $key . ' is incorrect'
            );
        }
        return $this;
    }

    /**
     * Verify one row in the applied taxes
     *
     * @param array $appliedTax
     * @param array $expectedAppliedTax
     * @return $this
     */
    protected function verifyAppliedTax($appliedTax, $expectedAppliedTax)
    {
        foreach ($expectedAppliedTax as $key => $value) {
            if ($key == 'rates') {
                foreach ($value as $index => $taxRate) {
                    $this->verifyAppliedTaxRate($appliedTax['rates'][$index], $taxRate);
                }
            } else {
                $this->assertEqualsWithDelta($value, $appliedTax[$key], 0.01, 'Applied tax ' . $key . ' is incorrect');
            }
        }
        return $this;
    }

    /**
     * Verify that applied taxes are correct
     *
     * @param array $appliedTaxes
     * @param array $expectedAppliedTaxes
     * @return $this
     */
    protected function verifyAppliedTaxes($appliedTaxes, $expectedAppliedTaxes)
    {
        foreach ($expectedAppliedTaxes as $taxRateKey => $expectedTaxRate) {
            $this->assertTrue(isset($appliedTaxes[$taxRateKey]), 'Missing tax rate ' . $taxRateKey);
            $this->verifyAppliedTax($appliedTaxes[$taxRateKey], $expectedTaxRate);
        }
        return $this;
    }

    /**
     * Verify fields in quote address
     *
     * @param \Magento\Quote\Model\Quote\Address $quoteAddress
     * @param array $expectedAddressData
     * @return $this
     */
    protected function verifyQuoteAddress($quoteAddress, $expectedAddressData)
    {
        foreach ($expectedAddressData as $key => $value) {
            if ($key == 'applied_taxes') {
                $this->verifyAppliedTaxes($quoteAddress->getAppliedTaxes(), $value);
            } else {
                $this->assertEqualsWithDelta(
                    $value,
                    $quoteAddress->getData($key),
                    0.01,
                    'Quote address ' . $key . ' is incorrect'
                );
            }
        }

        return $this;
    }

    /**
     * Verify fields in quote address and quote item are correct
     *
     * @param \Magento\Quote\Model\Quote\Address $quoteAddress
     * @param array $expectedResults
     * @return $this
     */
    protected function verifyResult($quoteAddress, $expectedResults)
    {
        $addressData = $expectedResults['address_data'];

        $this->verifyQuoteAddress($quoteAddress, $addressData);

        $quoteItems = $quoteAddress->getAllItems();
        foreach ($quoteItems as $item) {
            /** @var  \Magento\Quote\Model\Quote\Address\Item $item */
            $sku = $item->getProduct()->getData('sku');
            if (!isset($expectedResults['items_data'][$sku])) {
                continue;
            }
            $expectedItemData = $expectedResults['items_data'][$sku];
            $this->verifyItem($item, $expectedItemData);
        }

        return $this;
    }

    /**
     * Read the array defined in ../../../../_files/tax_calculation_data_aggregated.php
     * and feed it to testTaxCalculation
     *
     * @return array
     */
    public static function taxDataProvider(): array
    {
        $taxCalculationData = [];
        include __DIR__ . '/../../../../../_files/tax_calculation_data_aggregated.php';

        $result = [];
        foreach ($taxCalculationData as $scenarioName => $scenarioData) {
            $result[$scenarioName] = [
                $scenarioData['config_data'],
                $scenarioData['quote_data'],
                $scenarioData['expected_results'],
            ];
        }

        return $result;
    }
}
