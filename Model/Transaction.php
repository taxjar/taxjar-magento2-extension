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

namespace Taxjar\SalesTax\Model;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class Transaction
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Taxjar\SalesTax\Model\Logger
     */
    protected $logger;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     * @var \Taxjar\SalesTax\Model\ClientFactory
     */
    protected $clientFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Tax\Api\TaxClassRepositoryInterface
     */
    protected $taxClassRepository;

    /**
     * @var \Taxjar\SalesTax\Model\Client
     */
    protected $client;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Taxjar\SalesTax\Model\ClientFactory $clientFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository
     * @param \Taxjar\SalesTax\Model\Logger $logger
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Taxjar\SalesTax\Model\ClientFactory $clientFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository,
        \Taxjar\SalesTax\Model\Logger $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->clientFactory = $clientFactory;
        $this->productFactory = $productFactory;
        $this->regionFactory = $regionFactory;
        $this->taxClassRepository = $taxClassRepository;
        $this->logger = $logger;

        $this->client = $this->clientFactory->create();
        $this->client->showResponseErrors(true);
    }

    /**
     * Check if a transaction is synced
     *
     * @param string $syncDate
     * @return array
     */
    protected function isSynced($syncDate)
    {
        if (empty($syncDate) || $syncDate == '0000-00-00 00:00:00') {
            return false;
        }

        return true;
    }

    /**
     * Build `from` address for SmartCalcs request
     *
     * @return array
     */
    protected function buildFromAddress()
    {
        $fromCountry = $this->scopeConfig->getValue(
            \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_COUNTRY_ID
        );
        $fromPostcode = $this->scopeConfig->getValue(
            \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_POSTCODE
        );
        $region = $this->regionFactory->create();
        $regionId = $this->scopeConfig->getValue(
            \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_REGION_ID
        );
        $region->load($regionId);
        $fromState = $region->getCode();
        $fromCity = $this->scopeConfig->getValue(
            \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_CITY
        );
        $fromStreet = $this->scopeConfig->getValue('shipping/origin/street_line1') . $this->scopeConfig->getValue('shipping/origin/street_line2');

        return [
            'from_country' => $fromCountry,
            'from_zip' => $fromPostcode,
            'from_state' => $fromState,
            'from_city' => $fromCity,
            'from_street' => $fromStreet
        ];
    }

    /**
     * Build `to` address for SmartCalcs request
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    protected function buildToAddress(
        \Magento\Sales\Model\Order $order
    ) {
        if ($order->getIsVirtual()) {
            $address = $order->getBillingAddress();
        } else {
            $address = $order->getShippingAddress();
        }

        $toAddress = [
            'to_country' => $address->getCountryId(),
            'to_zip' => $address->getPostcode(),
            'to_state' => $address->getRegionCode(),
            'to_city' => $address->getCity(),
            'to_street' => $address->getData('street')
        ];

        return $toAddress;
    }

    /**
     * Build line items for SmartCalcs request
     *
     * @param array $items
     * @param string $type
     * @return array
     */
    protected function buildLineItems($items, $type = 'order') {
        $lineItems = [];
        $parentDiscounts = $this->getParentDiscounts($items);

        foreach ($items as $item) {
            if ($item->getParentItemId()) {
                continue;
            }

            $discount = (float) $item->getDiscountAmount();

            if (isset($parentDiscounts[$item->getId()])) {
                $discount = $parentDiscounts[$item->getId()] ?: $discount;
            }

            $lineItem = [
                'id' => $this->getLineItemId($item),
                'quantity' => (int) $item->getQtyOrdered(),
                'product_identifier' => $item->getSku(),
                'description' => $item->getName(),
                'unit_price' => (float) $item->getPrice(),
                'discount' => $discount,
                'sales_tax' => (float) $item->getTaxAmount()
            ];

            if ($type == 'refund') {
                $lineItem['quantity'] = (int) $item->getQty();
            }

            $product = $this->productFactory->create()->load($item->getProductId());

            if ($product->getTaxClassId()) {
                $taxClass = $this->taxClassRepository->get($product->getTaxClassId());

                if ($taxClass->getTjSalestaxCode()) {
                    $lineItem['product_tax_code'] = $taxClass->getTjSalestaxCode();
                }
            }

            $lineItems['line_items'][] = $lineItem;
        }

        return $lineItems;
    }

    /**
     * @param $item
     * @return string
     */
    public function getLineItemId($item)
    {
        $id = $item->getProductId();

        if ($item->getProductType() == Configurable::TYPE_CODE) {
            $childrenItems = $item->getChildrenItems();
            if (!empty($childrenItems)) {
                $childItem = $childrenItems[0];
                $id = $item->getProductId() . '_' . $childItem->getProductId();
            }
        }

        return $id;
    }

    /**
     * Get discounts for bundle products
     *
     * @param array $items
     * @return array
     */
    protected function getParentDiscounts(
        array $items
    ) {
        $parentDiscounts = [];

        foreach ($items as $item) {
            if ($item->getParentItemId()) {
                $discount = (float) $item->getDiscountAmount();

                if (isset($parentDiscounts[$item->getParentItemId()])) {
                    $parentDiscounts[$item->getParentItemId()] += $discount;
                } else {
                    $parentDiscounts[$item->getParentItemId()] = $discount;
                }
            }
        }

        return $parentDiscounts;
    }
}
