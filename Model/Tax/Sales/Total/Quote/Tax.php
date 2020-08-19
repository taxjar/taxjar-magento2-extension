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

namespace Taxjar\SalesTax\Model\Tax\Sales\Total\Quote;

use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Tax extends \Magento\Tax\Model\Sales\Total\Quote\Tax
{
    /**
     * @var \Taxjar\SalesTax\Model\Smartcalcs
     */
    protected $smartCalcs;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Tax\Api\Data\QuoteDetailsItemExtensionFactory
     */
    protected $extensionFactory;

    /**
     * @var \Taxjar\SalesTax\Model\Tax\TaxCalculation
     */
    protected $taxCalculation;

    /**
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param \Magento\Tax\Api\TaxCalculationInterface $taxCalculationService
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory $quoteDetailsDataObjectFactory
     * @param \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory $quoteDetailsItemDataObjectFactory
     * @param \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory $taxClassKeyDataObjectFactory
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $customerAddressFactory
     * @param \Magento\Customer\Api\Data\RegionInterfaceFactory $customerAddressRegionFactory
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Taxjar\SalesTax\Model\Smartcalcs $smartCalcs
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
     * @param \Magento\Tax\Api\Data\QuoteDetailsItemExtensionFactory $extensionFactory
     * @param \Taxjar\SalesTax\Model\Tax\TaxCalculation $taxCalculation
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Api\TaxCalculationInterface $taxCalculationService,
        \Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory $quoteDetailsDataObjectFactory,
        \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory $quoteDetailsItemDataObjectFactory,
        \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory $taxClassKeyDataObjectFactory,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $customerAddressFactory,
        \Magento\Customer\Api\Data\RegionInterfaceFactory $customerAddressRegionFactory,
        \Magento\Tax\Helper\Data $taxData,
        \Taxjar\SalesTax\Model\Smartcalcs $smartCalcs,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Tax\Api\Data\QuoteDetailsItemExtensionFactory $extensionFactory,
        \Taxjar\SalesTax\Model\Tax\TaxCalculation $taxCalculation
    ) {
        $this->smartCalcs = $smartCalcs;
        $this->scopeConfig = $scopeConfig;
        $this->priceCurrency = $priceCurrency;
        $this->extensionFactory = $extensionFactory;
        $this->taxCalculation = $taxCalculation;

        parent::__construct(
            $taxConfig,
            $taxCalculationService,
            $quoteDetailsDataObjectFactory,
            $quoteDetailsItemDataObjectFactory,
            $taxClassKeyDataObjectFactory,
            $customerAddressFactory,
            $customerAddressRegionFactory,
            $taxData
        );
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return $this
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        $this->clearValues($total);
        if (!$shippingAssignment->getItems()) {
            return $this;
        }

        $isEnabled = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $quote->getStoreId()
        );

        if (!$isEnabled) {
            return parent::collect($quote, $shippingAssignment, $total);
        }

        $baseQuoteTaxDetails = $this->getQuoteTaxDetailsInterface($shippingAssignment, $total, true);
        $this->smartCalcs->getTaxForOrder($quote, $baseQuoteTaxDetails, $shippingAssignment);

        if ($this->smartCalcs->isValidResponse()) {
            $quoteTax = $this->getQuoteTax($quote, $shippingAssignment, $total);

            //Populate address and items with tax calculation results
            $itemsByType = $this->organizeItemTaxDetailsByType($quoteTax['tax_details'], $quoteTax['base_tax_details']);

            if (isset($itemsByType[self::ITEM_TYPE_PRODUCT])) {
                $this->processProductItems($shippingAssignment, $itemsByType[self::ITEM_TYPE_PRODUCT], $total);
            }

            if (isset($itemsByType[self::ITEM_TYPE_SHIPPING])) {
                $shippingTaxDetails = $itemsByType[self::ITEM_TYPE_SHIPPING][self::ITEM_CODE_SHIPPING][self::KEY_ITEM];
                $baseShippingTaxDetails =
                    $itemsByType[self::ITEM_TYPE_SHIPPING][self::ITEM_CODE_SHIPPING][self::KEY_BASE_ITEM];
                $this->processShippingTaxInfo(
                    $shippingAssignment,
                    $total,
                    $shippingTaxDetails,
                    $baseShippingTaxDetails
                );
            }

            //Process taxable items that are not product or shipping
            $this->processExtraTaxables($total, $itemsByType);

            //Save applied taxes for each item and the quote in aggregation
            $this->processAppliedTaxes($total, $shippingAssignment, $itemsByType);

            if ($this->includeExtraTax()) {
                $total->addTotalAmount('extra_tax', $total->getExtraTaxAmount());
                $total->addBaseTotalAmount('extra_tax', $total->getBaseExtraTaxAmount());
            }
        } else {
            return parent::collect($quote, $shippingAssignment, $total);
        }

        return $this;
    }

    /**
     * Get quote tax details from SmartCalcs
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return array
     */
    protected function getQuoteTax(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        $baseTaxDetailsInterface = $this->getQuoteTaxDetailsInterface($shippingAssignment, $total, true);
        $taxDetailsInterface = $this->getQuoteTaxDetailsInterface($shippingAssignment, $total, false);

        $baseTaxDetails = $this->getQuoteTaxDetailsOverride($quote, $baseTaxDetailsInterface, true);
        $taxDetails = $this->getQuoteTaxDetailsOverride($quote, $taxDetailsInterface, false);

        return [
            'base_tax_details' => $baseTaxDetails,
            'tax_details' => $taxDetails
        ];
    }

    /**
     * Get tax details interface based on the quote and items
     *
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @param bool $useBaseCurrency
     * @return \Magento\Tax\Api\Data\QuoteDetailsInterface
     */
    protected function getQuoteTaxDetailsInterface($shippingAssignment, $total, $useBaseCurrency)
    {
        $address = $shippingAssignment->getShipping()->getAddress();
        //Setup taxable items
        $priceIncludesTax = $this->_config->priceIncludesTax($address->getQuote()->getStore());
        $itemDataObjects = $this->mapItems($shippingAssignment, $priceIncludesTax, $useBaseCurrency);

        //Add shipping
        $shippingDataObject = $this->getShippingDataObject($shippingAssignment, $total, $useBaseCurrency);
        if ($shippingDataObject != null) {
            $shippingDataObject = $this->extendShippingItem($shippingDataObject);
            $itemDataObjects[] = $shippingDataObject;
        }

        //process extra taxable items associated only with quote
        $quoteExtraTaxables = $this->mapQuoteExtraTaxables(
            $this->quoteDetailsItemDataObjectFactory,
            $address,
            $useBaseCurrency
        );
        if (!empty($quoteExtraTaxables)) {
            $itemDataObjects = array_merge($itemDataObjects, $quoteExtraTaxables);
        }

        //Preparation for calling taxCalculationService
        $quoteDetails = $this->prepareQuoteDetails($shippingAssignment, $itemDataObjects);

        return $quoteDetails;
    }

    /**
     * Get quote tax details for calculation
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterface $taxDetails
     * @param bool $useBaseCurrency
     * @return array
     */
    public function getQuoteTaxDetailsOverride(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Tax\Api\Data\QuoteDetailsInterface $taxDetails,
        $useBaseCurrency
    ) {
        $store = $quote->getStore();
        $taxDetails = $this->taxCalculation->calculateTaxDetails($taxDetails, $useBaseCurrency, $store);
        return $taxDetails;
    }

    /**
     * Map an item to item data object with product ID for SmartCalcs
     *
     * @param \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory $itemDataObjectFactory
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param bool $priceIncludesTax
     * @param bool $useBaseCurrency
     * @param string $parentCode
     * @return \Magento\Tax\Api\Data\QuoteDetailsItemInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function mapItem(
        \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory $itemDataObjectFactory,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        $priceIncludesTax,
        $useBaseCurrency,
        $parentCode = null
    ) {
        $itemDataObject = parent::mapItem(
            $itemDataObjectFactory,
            $item,
            $priceIncludesTax,
            $useBaseCurrency,
            $parentCode
        );

        $lineItemTax = $this->smartCalcs->getResponseLineItem($itemDataObject->getCode());

        /**
         * @var \Magento\Tax\Api\Data\QuoteDetailsItemExtensionInterface $extensionAttributes
         */
        $extensionAttributes = $itemDataObject->getExtensionAttributes()
            ? $itemDataObject->getExtensionAttributes()
            : $this->extensionFactory->create();

        if (is_array($lineItemTax)) {
            $taxCollectable = $lineItemTax['taxable_amount'] * $lineItemTax['combined_tax_rate'];

            $extensionAttributes->setTaxCollectable($taxCollectable);
            $extensionAttributes->setCombinedTaxRate($lineItemTax['combined_tax_rate'] * 100);
        }

        $extensionAttributes->setProductType($item->getProductType());
        $extensionAttributes->setPriceType($item->getProduct()->getPriceType());

        $jurisdictions = ['country', 'state', 'county', 'city', 'special', 'gst', 'pst', 'qst'];
        $jurisdictionRates = [];

        foreach ($jurisdictions as $jurisdictionId => $jurisdiction) {
            $rate = isset($lineItemTax[$jurisdiction . '_tax_rate']) ? $lineItemTax[$jurisdiction . '_tax_rate'] : 0;
            $amount = isset($lineItemTax[$jurisdiction . '_amount']) ? $lineItemTax[$jurisdiction . '_amount'] : 0;

            // US state line item rates use `state_sales_tax_rate`
            if ($jurisdiction == 'state' && isset($lineItemTax['state_sales_tax_rate'])) {
                $rate = $lineItemTax['state_sales_tax_rate'];
            }

            // US special district tax line item amounts use `special_district_amount`
            if ($jurisdiction == 'special' && isset($lineItemTax['special_district_amount'])) {
                $amount = $lineItemTax['special_district_amount'];
            }

            // Canada line item amounts include `gst`, `pst`, and `qst`
            if (in_array($jurisdiction, ['gst', 'pst', 'qst']) && isset($lineItemTax[$jurisdiction])) {
                $amount = $lineItemTax[$jurisdiction];
            }

            // Country line item amounts use `country_tax_collectable`
            if ($jurisdiction == 'country' && isset($lineItemTax['country_tax_collectable'])) {
                $amount = $lineItemTax['country_tax_collectable'];
            }

            if ($rate) {
                $jurisdictionRates[$jurisdiction] = [
                    'id' => $jurisdictionId,
                    'rate' => $rate * 100,
                    'amount' => $amount
                ];
            }
        }

        $extensionAttributes->setJurisdictionTaxRates($jurisdictionRates);

        $itemDataObject->setExtensionAttributes($extensionAttributes);

        return $itemDataObject;
    }

    /**
     * @param \Magento\Tax\Api\Data\QuoteDetailsItemInterface $shippingDataObject
     * @return \Magento\Tax\Api\Data\QuoteDetailsItemInterface
     */
    protected function extendShippingItem(
        \Magento\Tax\Api\Data\QuoteDetailsItemInterface $shippingDataObject
    ) {
        /** @var \Magento\Tax\Api\Data\QuoteDetailsItemExtensionInterface $extensionAttributes */
        $extensionAttributes = $shippingDataObject->getExtensionAttributes()
            ? $shippingDataObject->getExtensionAttributes()
            : $this->extensionFactory->create();

        $shippingTax = $this->smartCalcs->getResponseShipping();

        if (is_array($shippingTax)) {
            $extensionAttributes->setTaxCollectable($shippingTax['tax_collectable']);
            $extensionAttributes->setCombinedTaxRate($shippingTax['combined_tax_rate'] * 100);
            $extensionAttributes->setJurisdictionTaxRates([
                'shipping' => [
                    'id' => 'shipping',
                    'rate' => $shippingTax['combined_tax_rate'] * 100,
                    'amount' => $shippingTax['tax_collectable']
                ]
            ]);

            $shippingDataObject->setExtensionAttributes($extensionAttributes);
        }

        return $shippingDataObject;
    }
}
