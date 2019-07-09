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

namespace Taxjar\SalesTax\Model\Tax;

use Magento\Tax\Api\TaxClassManagementInterface;
use Magento\Tax\Api\Data\AppliedTaxInterfaceFactory;
use Magento\Tax\Api\Data\AppliedTaxRateInterfaceFactory;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Api\Data\TaxDetailsInterfaceFactory;
use Magento\Tax\Api\Data\TaxDetailsItemInterfaceFactory;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\Calculation\CalculatorFactory;
use Magento\Tax\Model\Config;
use Magento\Tax\Model\TaxDetails\TaxDetails;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TaxCalculation extends \Magento\Tax\Model\TaxCalculation
{
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Tax\Api\Data\AppliedTaxInterfaceFactory
     */
    protected $appliedTaxDataObjectFactory;

    /**
     * @var \Magento\Tax\Api\Data\AppliedTaxRateInterfaceFactory
     */
    protected $appliedTaxRateDataObjectFactory;

    /**
     * @var QuoteDetailsItemInterface[]
     */
    protected $keyedQuoteDetailItems;

    /**
     * @param Calculation $calculation
     * @param CalculatorFactory $calculatorFactory
     * @param Config $config
     * @param TaxDetailsInterfaceFactory $taxDetailsDataObjectFactory
     * @param TaxDetailsItemInterfaceFactory $taxDetailsItemDataObjectFactory
     * @param StoreManagerInterface $storeManager
     * @param TaxClassManagementInterface $taxClassManagement
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param AppliedTaxInterfaceFactory $appliedTaxDataObjectFactory
     * @param AppliedTaxRateInterfaceFactory $appliedTaxRateDataObjectFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Calculation $calculation,
        CalculatorFactory $calculatorFactory,
        Config $config,
        TaxDetailsInterfaceFactory $taxDetailsDataObjectFactory,
        TaxDetailsItemInterfaceFactory $taxDetailsItemDataObjectFactory,
        StoreManagerInterface $storeManager,
        TaxClassManagementInterface $taxClassManagement,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        AppliedTaxInterfaceFactory $appliedTaxDataObjectFactory,
        AppliedTaxRateInterfaceFactory $appliedTaxRateDataObjectFactory
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->appliedTaxDataObjectFactory = $appliedTaxDataObjectFactory;
        $this->appliedTaxRateDataObjectFactory = $appliedTaxRateDataObjectFactory;

        return parent::__construct(
            $calculation,
            $calculatorFactory,
            $config,
            $taxDetailsDataObjectFactory,
            $taxDetailsItemDataObjectFactory,
            $storeManager,
            $taxClassManagement,
            $dataObjectHelper
        );
    }

    /**
     * Calculate sales tax for each item in a quote
     *
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails
     * @param bool $useBaseCurrency
     * @param \Magento\Framework\App\ScopeInterface $scope
     * @return \Magento\Tax\Api\Data\TaxDetailsInterface
     */
    public function calculateTaxDetails(
        \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails,
        $useBaseCurrency,
        $scope
    ) {
        // initial TaxDetails data
        $taxDetailsData = [
            TaxDetails::KEY_SUBTOTAL => 0.0,
            TaxDetails::KEY_TAX_AMOUNT => 0.0,
            TaxDetails::KEY_DISCOUNT_TAX_COMPENSATION_AMOUNT => 0.0,
            TaxDetails::KEY_APPLIED_TAXES => [],
            TaxDetails::KEY_ITEMS => [],
        ];

        $items = $quoteDetails->getItems();

        if (empty($items)) {
            return $this->taxDetailsDataObjectFactory->create()
                ->setSubtotal(0.0)
                ->setTaxAmount(0.0)
                ->setDiscountTaxCompensationAmount(0.0)
                ->setAppliedTaxes([])
                ->setItems([]);
        }

        $keyedItems = [];
        $parentToChildren = [];

        foreach ($items as $item) {
            if ($item->getParentCode() === null) {
                $keyedItems[$item->getCode()] = $item;
            } else {
                $parentToChildren[$item->getParentCode()][] = $item;
            }
        }

        $this->keyedQuoteDetailItems = $keyedItems;

        $processedItems = [];
        /** @var QuoteDetailsItemInterface $item */
        foreach ($keyedItems as $item) {
            if (isset($parentToChildren[$item->getCode()])) {
                $processedChildren = [];
                foreach ($parentToChildren[$item->getCode()] as $child) {
                    $processedItem = $this->processItemDetails($child, $useBaseCurrency, $scope);
                    $taxDetailsData = $this->aggregateItemData($taxDetailsData, $processedItem);
                    $processedItems[$processedItem->getCode()] = $processedItem;
                    $processedChildren[] = $processedItem;
                }
                $processedItem = $this->calculateParent($processedChildren, $item->getQuantity());
                $processedItem->setCode($item->getCode());
                $processedItem->setType($item->getType());
            } else {
                $processedItem = $this->processItemDetails($item, $useBaseCurrency, $scope);
                $taxDetailsData = $this->aggregateItemData($taxDetailsData, $processedItem);
            }
            $processedItems[$processedItem->getCode()] = $processedItem;
        }

        $taxDetailsDataObject = $this->taxDetailsDataObjectFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $taxDetailsDataObject,
            $taxDetailsData,
            '\Magento\Tax\Api\Data\TaxDetailsInterface'
        );
        $taxDetailsDataObject->setItems($processedItems);
        return $taxDetailsDataObject;
    }

    /**
     * Process a quote item into a tax details item
     *
     * @param QuoteDetailsItemInterface $item
     * @param bool $useBaseCurrency
     * @param \Magento\Framework\App\ScopeInterface $scope
     * @return \Magento\Tax\Api\Data\TaxDetailsItemInterface
     */
    protected function processItemDetails(
        QuoteDetailsItemInterface $item,
        $useBaseCurrency,
        $scope
    ) {
        $price = $item->getUnitPrice();
        $quantity = $this->getTotalQuantity($item);

        $extensionAttributes = $item->getExtensionAttributes();
        $taxCollectable = $extensionAttributes ? $extensionAttributes->getTaxCollectable() : 0;
        $taxPercent = $extensionAttributes ? $extensionAttributes->getCombinedTaxRate() : 0;

        if (!$useBaseCurrency) {
            $taxCollectable = $this->priceCurrency->convert($taxCollectable, $scope);
        }

        $rowTotal = $price * $quantity;
        $rowTotalInclTax = $rowTotal + $taxCollectable;

        $priceInclTax = $rowTotalInclTax / $quantity;
        $discountTaxCompensationAmount = 0;

        $appliedTaxes = $this->getAppliedTaxes($item, $scope);

        return $this->taxDetailsItemDataObjectFactory->create()
             ->setCode($item->getCode())
             ->setType($item->getType())
             ->setRowTax($taxCollectable)
             ->setPrice($price)
             ->setPriceInclTax($priceInclTax)
             ->setRowTotal($rowTotal)
             ->setRowTotalInclTax($rowTotalInclTax)
             ->setDiscountTaxCompensationAmount($discountTaxCompensationAmount)
             ->setAssociatedItemCode($item->getAssociatedItemCode())
             ->setTaxPercent($taxPercent)
             ->setAppliedTaxes($appliedTaxes);
    }

    /**
     * Set applied taxes for tax summary based on SmartCalcs response breakdown
     *
     * @param QuoteDetailsItemInterface $item
     * @param \Magento\Framework\App\ScopeInterface $scope
     * @return \Magento\Tax\Api\Data\AppliedTaxInterface
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function getAppliedTaxes(
        QuoteDetailsItemInterface $item,
        $scope
    ) {
        $extensionAttributes = $item->getExtensionAttributes();
        $jurisdictionTaxRates = $extensionAttributes ? $extensionAttributes->getJurisdictionTaxRates() : [];
        $appliedTaxes = [];

        if (empty($jurisdictionTaxRates)) {
            return $appliedTaxes;
        }

        foreach ($jurisdictionTaxRates as $jurisdiction => $jurisdictionTax) {
            if ($jurisdictionTax['rate'] == 0) {
                continue;
            }

            // @codingStandardsIgnoreStart
            $jurisdictionTitle = (in_array($jurisdiction, ['gst', 'pst', 'qst'])) ? strtoupper($jurisdiction) : ucfirst($jurisdiction) . ' Tax';
            // @codingStandardsIgnoreEnd

            // Display "Special District Tax" instead of "Special Tax"
            if ($jurisdiction == 'special') {
                $jurisdictionTitle = 'Special District Tax';
            }

            // Display "VAT" instead of "Country Tax"
            if ($jurisdiction == 'country') {
                $jurisdictionTitle = 'VAT';
            }

            $rateDataObject = $this->appliedTaxRateDataObjectFactory->create()
                ->setPercent($jurisdictionTax['rate'])
                ->setCode($jurisdictionTax['id'])
                ->setTitle($jurisdictionTitle);

            $appliedTaxDataObject = $this->appliedTaxDataObjectFactory->create();
            $appliedTaxDataObject->setAmount($jurisdictionTax['amount']);
            $appliedTaxDataObject->setPercent($jurisdictionTax['rate']);
            $appliedTaxDataObject->setTaxRateKey($jurisdictionTax['id']);
            $appliedTaxDataObject->setRates([$rateDataObject]);

            $appliedTaxes[$appliedTaxDataObject->getTaxRateKey()] = $appliedTaxDataObject;
        }

        return $appliedTaxes;
    }

    /**
     * Calculates the total quantity for this item.
     *
     * @param QuoteDetailsItemInterface $item
     * @return float
     */
    protected function getTotalQuantity(QuoteDetailsItemInterface $item)
    {
        if ($item->getParentCode()) {
            $parentQuantity = $this->keyedQuoteDetailItems[$item->getParentCode()]->getQuantity();
            return $parentQuantity * $item->getQuantity();
        }
        return $item->getQuantity();
    }
}
