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

use Magento\Bundle\Model\Product\Price;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

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
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Magento\Tax\Api\TaxClassRepositoryInterface
     */
    protected $taxClassRepository;

    /**
     * @var \Taxjar\SalesTax\Model\Client
     */
    protected $client;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Taxjar\SalesTax\Model\ClientFactory $clientFactory
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository
     * @param \Taxjar\SalesTax\Model\Logger $logger
     * @param \Magento\Framework\ObjectManagerInterface $objectmanager
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Taxjar\SalesTax\Model\ClientFactory $clientFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository,
        \Taxjar\SalesTax\Model\Logger $logger,
        \Magento\Framework\ObjectManagerInterface $objectmanager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->clientFactory = $clientFactory;
        $this->productRepository = $productRepository;
        $this->regionFactory = $regionFactory;
        $this->taxClassRepository = $taxClassRepository;
        $this->logger = $logger->setFilename(TaxjarConfig::TAXJAR_TRANSACTIONS_LOG);
        $this->objectManager = $objectmanager;

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
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    protected function buildFromAddress(
        \Magento\Sales\Model\Order $order
    ) {
        $fromCountry = $this->scopeConfig->getValue(
            \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_COUNTRY_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );
        $fromPostcode = $this->scopeConfig->getValue(
            \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_POSTCODE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );
        $region = $this->regionFactory->create();
        $regionId = $this->scopeConfig->getValue(
            \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_REGION_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );
        $region->load($regionId);
        $fromState = $region->getCode();
        $fromCity = $this->scopeConfig->getValue(
            \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_CITY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );
        $fromStreet1 = $this->scopeConfig->getValue('shipping/origin/street_line1',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );
        $fromStreet2 = $this->scopeConfig->getValue('shipping/origin/street_line2',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );

        return [
            'from_country' => $fromCountry,
            'from_zip' => $fromPostcode,
            'from_state' => $fromState,
            'from_city' => $fromCity,
            'from_street' => $fromStreet1 . $fromStreet2
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
     * @param \Magento\Sales\Model\Order $order
     * @param array $items
     * @param string $type
     * @return array
     */
    protected function buildLineItems($order, $items, $type = 'order') {
        $lineItems = [];
        $parentDiscounts = $this->getParentAmounts('discount', $items, $type);
        $parentTaxes = $this->getParentAmounts('tax', $items, $type);

        foreach ($items as $item) {
            $itemType = $item->getProductType();
            $parentItem = $item->getParentItem();
            $unitPrice = (float) $item->getPrice();

            if (($itemType == 'simple' || $itemType == 'virtual') && $item->getParentItemId()) {
                if (!empty($parentItem) && $parentItem->getProductType() == 'bundle') {
                    if ($parentItem->getProduct()->getPriceType() == Price::PRICE_TYPE_FIXED) {
                        continue;  // Skip children of fixed price bundles
                    }
                } else {
                    continue;  // Skip children of configurable products
                }
            }

            if ($itemType == 'bundle' && $item->getProduct()->getPriceType() != Price::PRICE_TYPE_FIXED) {
                continue;  // Skip dynamic bundle parent item
            }

            if (method_exists($item, 'getOrderItem') && $item->getOrderItem()->getParentItemId()) {
                continue;
            }

            $itemId = $item->getOrderItemId() ? $item->getOrderItemId() : $item->getItemId();
            $discount = (float) $item->getDiscountAmount();
            $tax = (float) $item->getTaxAmount();

            if (isset($parentDiscounts[$itemId])) {
                $discount = $parentDiscounts[$itemId] ?: $discount;
            }

            if ($discount > $unitPrice) {
                $discount = $unitPrice;
            }

            if (isset($parentTaxes[$itemId])) {
                $tax = $parentTaxes[$itemId] ?: $tax;
            }

            $lineItem = [
                'id' => $itemId,
                'quantity' => (int) $item->getQtyOrdered(),
                'product_identifier' => $item->getSku(),
                'description' => $item->getName(),
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'sales_tax' => $tax
            ];

            if ($type == 'refund' && method_exists($item, 'getOrderItem')) {
                $orderItem = $item->getOrderItem();
                $lineItem['quantity'] = (int) $orderItem->getQtyRefunded();
                $lineItem['unit_price'] = $orderItem->getAmountRefunded() / $lineItem['quantity'];
            }

            $product = $this->productRepository->getById($item->getProductId(), false, $order->getStoreId());

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
     * Add customer_id to transaction
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    function buildCustomerExemption($order)
    {
        if ($order->getCustomerId()) {
            return ['customer_id' => $order->getCustomerId()];
        }

        return [];
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getProvider($order)
    {
        $provider = 'api';

        try {
            if (class_exists('\Ess\M2ePro\Model\Order')) {
                $m2eOrder = $this->objectManager->create('\Ess\M2ePro\Model\Order');
                $m2eOrder = $m2eOrder->load($order->getId(), 'magento_order_id');

                if (in_array($m2eOrder->getComponentMode(), ['amazon', 'ebay', 'walmart'])) {
                    $provider = $m2eOrder->getComponentMode();
                }
            }
        } catch (\Ess\M2ePro\Model\Exception\Logic $e) {
            // noop: M2e order does not exist or component mode can't be loaded
        }

        return $provider;
    }

    /**
     * Get parent amounts (discounts, tax, etc) for configurable / bundle products
     *
     * @param string $attr
     * @param array $items
     * @param string $type
     * @return array
     */
    protected function getParentAmounts($attr, $items, $type = 'order') {
        $parentAmounts = [];

        foreach ($items as $item) {
            $parentItemId = null;

            if ($item->getParentItemId()) {
                $parentItemId = $item->getParentItemId();
            }

            if (method_exists($item, 'getOrderItem') && $item->getOrderItem()->getParentItemId()) {
                $parentItemId = $item->getOrderItem()->getParentItemId();
            }

            if (isset($parentItemId)) {
                switch ($attr) {
                    case 'discount':
                        $amount = (float) (($type == 'order') ? $item->getDiscountAmount() : $item->getDiscountRefunded());
                        break;
                    case 'tax':
                        $amount = (float) (($type == 'order') ? $item->getTaxAmount() : $item->getTaxRefunded());
                        break;
                }

                if (isset($parentAmounts[$parentItemId])) {
                    $parentAmounts[$parentItemId] += $amount;
                } else {
                    $parentAmounts[$parentItemId] = $amount;
                }
            }
        }

        return $parentAmounts;
    }
}
