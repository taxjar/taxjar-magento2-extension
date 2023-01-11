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

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Store\Model\ScopeInterface;
use Taxjar\SalesTax\Api\Data\Sales\Order\MetadataInterface;
use Taxjar\SalesTax\Model\Sales\Order\Metadata;
use Taxjar\SalesTax\Model\Tax\NexusFactory;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Smartcalcs
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var ZendClientFactory
     */
    protected $clientFactory;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     * @var \Taxjar\SalesTax\Model\Tax\NexusFactory
     */
    protected $nexusFactory;

    /**
     * @var \Magento\Tax\Api\TaxClassRepositoryInterface
     */
    protected $taxClassRepository;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var \Magento\Tax\Helper\Data $taxData
     */
    protected $taxData;

    /**
     * @var \Taxjar\SalesTax\Helper\Data
     */
    protected $tjHelper;

    /**
     * @var \Magento\Directory\Model\Country\Postcode\ConfigInterface
     */
    protected $postCodesConfig;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Zend_Http_Response
     */
    protected $response;

    /**
     * @var \Taxjar\SalesTax\Model\Logger
     */
    protected $logger;

    /**
     * @var TaxjarConfig
     */
    protected $taxjarConfig;

    /**
     * @var \Taxjar\SalesTax\Helper\Nexus
     */
    private $nexusHelper;

    /**
     * @var int
     */
    private $storeId;

    /**
     * Smartcalcs constructor.
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param RegionFactory $regionFactory
     * @param NexusFactory $nexusFactory
     * @param \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepositoryInterface
     * @param ScopeConfigInterface $scopeConfig
     * @param ZendClientFactory $clientFactory
     * @param ProductMetadataInterface $productMetadata
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Taxjar\SalesTax\Helper\Data $tjHelper
     * @param \Magento\Directory\Model\Country\Postcode\ConfigInterface $postCodesConfig
     * @param Logger $logger
     * @param TaxjarConfig $taxjarConfig
     * @param \Taxjar\SalesTax\Helper\Nexus $nexusHelper
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        RegionFactory $regionFactory,
        NexusFactory $nexusFactory,
        \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepositoryInterface,
        ScopeConfigInterface $scopeConfig,
        ZendClientFactory $clientFactory,
        ProductMetadataInterface $productMetadata,
        \Magento\Tax\Helper\Data $taxData,
        \Taxjar\SalesTax\Helper\Data $tjHelper,
        \Magento\Directory\Model\Country\Postcode\ConfigInterface $postCodesConfig,
        \Taxjar\SalesTax\Model\Logger $logger,
        TaxjarConfig $taxjarConfig,
        \Taxjar\SalesTax\Helper\Nexus $nexusHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->regionFactory = $regionFactory;
        $this->nexusFactory = $nexusFactory;
        $this->taxClassRepository = $taxClassRepositoryInterface;
        $this->productMetadata = $productMetadata;
        $this->scopeConfig = $scopeConfig;
        $this->clientFactory = $clientFactory;
        $this->taxData = $taxData;
        $this->tjHelper = $tjHelper;
        $this->postCodesConfig = $postCodesConfig;
        $this->logger = $logger->setFilename(TaxjarConfig::TAXJAR_CALCULATIONS_LOG);
        $this->taxjarConfig = $taxjarConfig;
        $this->nexusHelper = $nexusHelper;
    }

    /**
     * Tax calculation for order
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteTaxDetails
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @return $this
     */
    public function getTaxForOrder(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteTaxDetails,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
    ) {
        $this->storeId = $quote->getStoreId();

        $apiKey = $this->taxjarConfig->getApiKey($this->storeId);
        $address = $shippingAssignment->getShipping()->getAddress();

        if (!$this->_isValidRequest($address)) {
            return;
        }

        $order = $this->_getOrder($quote, $quoteTaxDetails, $address);

        if ($this->_orderChanged($order)) {
            $client = $this->clientFactory->create();
            $client->setUri($this->taxjarConfig->getApiUrl() . '/magento/taxes');
            $client->setConfig([
                'useragent' => $this->tjHelper->getUserAgent(),
                'referer' => $this->tjHelper->getStoreUrl()
            ]);
            $client->setHeaders([
                'Authorization' => "Bearer $apiKey",
                'x-api-version' => TaxjarConfig::TAXJAR_X_API_VERSION
            ]);
            $client->setRawData(json_encode($order), 'application/json');

            $this->logger->log('Calculating sales tax: ' . json_encode($order), 'post');

            $this->_setSessionData('order', json_encode($order, JSON_NUMERIC_CHECK | JSON_PRESERVE_ZERO_FRACTION));

            try {
                $response = $client->request('POST');
                $this->response = $response;
                $this->_setSessionData('response', $response);

                if (200 == $response->getStatus()) {
                    $this->logger->log('Successful API response: ' . $response->getBody(), 'success');
                    $metadata = [
                        MetadataInterface::TAX_CALCULATION_STATUS => Metadata::TAX_CALCULATION_STATUS_SUCCESS,
                    ];
                } else {
                    $errorResponse = json_decode($response->getBody());
                    $this->logger->log(
                        $errorResponse->status . ' ' . $errorResponse->error . ' - ' . $errorResponse->detail,
                        'error'
                    );
                    $metadata = [
                        MetadataInterface::TAX_CALCULATION_STATUS => Metadata::TAX_CALCULATION_STATUS_ERROR,
                        MetadataInterface::TAX_CALCULATION_MESSAGE =>
                            $errorResponse->error . ' - ' . $errorResponse->detail,
                    ];
                }
            } catch (\Zend_Http_Client_Exception $e) {
                // Catch API timeouts and network issues
                $this->logger->log(
                    'API timeout or network issue between your store and TaxJar, please try again later.',
                    'error'
                );
                $this->response = null;
                $this->_unsetSessionData('response');
                $metadata = [
                    MetadataInterface::TAX_CALCULATION_STATUS => Metadata::TAX_CALCULATION_STATUS_ERROR,
                    MetadataInterface::TAX_CALCULATION_MESSAGE => $e->getMessage(),
                ];
            }
        } else {
            $sessionResponse = $this->_getSessionData('response');

            if (isset($sessionResponse)) {
                $this->response = $sessionResponse;
            }
        }

        if (isset($metadata)) {
            $this->_setSessionData('order_metadata', json_encode($metadata));
        }

        return $this;
    }

    public function _isValidRequest($address)
    {
        if ($this->taxjarConfig->getApiKey($this->storeId) == null) {
            $this->_setSessionData('order_metadata', json_encode([
                MetadataInterface::TAX_CALCULATION_STATUS => Metadata::TAX_CALCULATION_STATUS_ERROR,
                MetadataInterface::TAX_CALCULATION_MESSAGE => 'SKIPPED - No TaxJar API key found.'
            ]));

            return false;
        }

        if (!$address->getPostcode()) {
            $this->_setSessionData('order_metadata', json_encode([
                MetadataInterface::TAX_CALCULATION_STATUS => Metadata::TAX_CALCULATION_STATUS_ERROR,
                MetadataInterface::TAX_CALCULATION_MESSAGE => 'SKIPPED - No postal code found on address.'
            ]));

            return false;
        }

        if (!$this->_validatePostcode($address->getPostcode(), $address->getCountry())) {
            $this->_setSessionData('order_metadata', json_encode([
                MetadataInterface::TAX_CALCULATION_STATUS => Metadata::TAX_CALCULATION_STATUS_ERROR,
                MetadataInterface::TAX_CALCULATION_MESSAGE => 'SKIPPED - Could not validate address postal code.'
            ]));

            return false;
        }

        $hasNexus = $this->nexusHelper->hasNexusByLocation(
            $this->storeId,
            $address->getRegionCode(),
            $address->getCountry()
        );

        if (!$hasNexus) {
            $this->_setSessionData('order_metadata', json_encode([
                MetadataInterface::TAX_CALCULATION_STATUS => Metadata::TAX_CALCULATION_STATUS_ERROR,
                MetadataInterface::TAX_CALCULATION_MESSAGE => 'SKIPPED - Order does not meet nexus requirements.'
            ]));

            return false;
        }

        if (!count($address->getAllItems())) {
            $this->_setSessionData('order_metadata', json_encode([
                MetadataInterface::TAX_CALCULATION_STATUS => Metadata::TAX_CALCULATION_STATUS_ERROR,
                MetadataInterface::TAX_CALCULATION_MESSAGE => 'SKIPPED - No items found for address.'
            ]));

            return false;
        }

        if ($this->_isCustomerExempt($address)) {
            $this->_setSessionData('order_metadata', json_encode([
                MetadataInterface::TAX_CALCULATION_STATUS => Metadata::TAX_CALCULATION_STATUS_ERROR,
                MetadataInterface::TAX_CALCULATION_MESSAGE => 'SKIPPED - Customer belongs to an exempt tax class.'
            ]));

            return false;
        }

        return true;
    }

    /**
     * @param $quote
     * @param $quoteTaxDetails
     * @param $address
     * @return array
     */
    private function _getOrder($quote, $quoteTaxDetails, $address)
    {
        $shipping = (float) $address->getShippingAmount();
        $shippingDiscount = (float) $address->getShippingDiscountAmount();

        $shippingRegionId = $this->_getStoreValue('shipping/origin/region_id', $quote->getStoreId());

        $fromAddress = [
            'from_country' => $this->_getStoreValue('shipping/origin/country_id', $quote->getStoreId()),
            'from_zip' => $this->_getStoreValue('shipping/origin/postcode', $quote->getStoreId()),
            'from_state' => $this->regionFactory->create()->load($shippingRegionId)->getCode(),
            'from_city' => $this->_getStoreValue('shipping/origin/city', $quote->getStoreId()),
            'from_street' => $this->_getStoreValue('shipping/origin/street_line1', $quote->getStoreId()),
        ];

        $toAddress = [
            'to_country' => $address->getCountry(),
            'to_zip' => $address->getPostcode(),
            'to_state' => $address->getRegionCode(),
            'to_city' => $address->getCity(),
            'to_street' => $address->getStreetLine(1)
        ];

        return array_merge($fromAddress, $toAddress, [
            'shipping' => $shipping - abs($shippingDiscount),
            'line_items' => $this->_getLineItems($quote, $quoteTaxDetails),
            'nexus_addresses' => $this->nexusHelper->getNexusAddresses($quote->getStoreId()),
            'customer_id' => $quote->getCustomerId() ? $quote->getCustomerId() : '',
            'plugin' => 'magento'
        ]);
    }

    /**
     * Get the SmartCalcs API response
     *
     * @return array
     */
    public function getResponse()
    {
        if ($this->response) {
            return [
                'body' => json_decode($this->response->getBody(), true),
                'status' => $this->response->getStatus(),
            ];
        } else {
            return [
                'status' => 204,
            ];
        }
    }

    /**
     * Get a specific line item breakdown from a SmartCalcs API response
     *
     * @param int $id
     * @return array
     */
    public function getResponseLineItem($id)
    {
        if ($this->response) {
            $responseBody = json_decode($this->response->getBody(), true);

            if (isset($responseBody['tax']['breakdown']['line_items'])) {
                $lineItems = $responseBody['tax']['breakdown']['line_items'];
                $matchedKey = array_search($id, array_column($lineItems, 'id'));

                if (isset($lineItems[$matchedKey]) && $matchedKey !== false) {
                    return $lineItems[$matchedKey];
                }
            }
        }
    }

    /**
     * Get the shipping breakdown from a SmartCalcs API response
     *
     * @return array
     */
    public function getResponseShipping()
    {
        if ($this->response) {
            $responseBody = json_decode($this->response->getBody(), true);

            if (isset($responseBody['tax']['breakdown']['shipping'])) {
                return $responseBody['tax']['breakdown']['shipping'];
            }
        }
    }

    /**
     * Determine if SmartCalcs returned a valid response
     *
     * @return bool
     */
    public function isValidResponse()
    {
        $response = $this->getResponse();

        if (isset($response['body']['tax']) && $response['status'] == 200) {
            return true;
        }

        return false;
    }

    /**
     * Validate postcode based on country using patterns defined in
     * app/code/Magento/Directory/etc/zip_codes.xml
     *
     * @param string $postcode
     * @param string $countryId
     * @return bool
     */
    private function _validatePostcode($postcode, $countryId)
    {
        $postCodes = $this->postCodesConfig->getPostCodes();
        if (isset($postCodes[$countryId]) && is_array($postCodes[$countryId])) {
            foreach ($postCodes[$countryId] as $pattern) {
                if (preg_match("/{$pattern['pattern']}/", trim((string) $postcode))) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Verify if customer is exempt from sales tax
     *
     * @param \Magento\Quote\Model\Quote\Address $address
     * @return bool
     */
    private function _isCustomerExempt(
        \Magento\Quote\Model\Quote\Address $address
    ) {
        if ($address->getQuote()->getCustomerTaxClassId()) {
            $customerTaxClass = $this->taxClassRepository->get($address->getQuote()->getCustomerTaxClassId());
            $customerTaxCode = $customerTaxClass->getTjSalestaxCode();
            return $customerTaxCode == TaxjarConfig::TAXJAR_EXEMPT_TAX_CODE;
        }

        return false;
    }

    /**
     * Get order line items
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteTaxDetails
     * @return array
     */
    private function _getLineItems(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteTaxDetails
    ) {
        $lineItems = [];
        $store = $quote->getStore();
        $items = $quoteTaxDetails->getItems();

        if (count($items) > 0) {
            $parentQuantities = [];

            foreach ($items as $item) {
                if ($item->getType() == 'product') {
                    $id = $item->getCode();
                    $parentId = $item->getParentCode();
                    $quantity = $item->getQuantity();
                    $unitPrice = (float) $item->getUnitPrice();
                    $discount = (float) $item->getDiscountAmount();
                    $extensionAttributes = $item->getExtensionAttributes();
                    $taxCode = '';

                    if ($extensionAttributes->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                        $parentQuantities[$id] = $quantity;

                        if ($extensionAttributes->getPriceType() ==
                            \Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC) {
                            continue;
                        }
                    }

                    if (isset($parentQuantities[$parentId])) {
                        $quantity *= $parentQuantities[$parentId];
                    }

                    if (!$this->taxData->applyTaxAfterDiscount($store)) {
                        $discount = 0;
                    }

                    if ($discount > ($unitPrice * $quantity)) {
                        $discount = ($unitPrice * $quantity);
                    }

                    // Check for a PTC assigned directly to the product; otherwise fall back to tax classes
                    if ($extensionAttributes->getTjPtc()) {
                        $taxCode = $extensionAttributes->getTjPtc();
                    } elseif ($item->getTaxClassKey()->getValue()) {
                        // This TaxClass may have been overwritten in the TaxjarCommonTaxCollector plugin
                        $taxClass = $this->taxClassRepository->get($item->getTaxClassKey()->getValue());
                        $taxCode = $taxClass->getTjSalestaxCode();
                    }

                    if (in_array($this->productMetadata->getEdition(), ['Enterprise', 'B2B']) &&
                        $extensionAttributes->getProductType() ==
                        \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD
                    ) {
                        $giftTaxClassId = $this->scopeConfig->getValue(
                            'tax/classes/wrapping_tax_class',
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $quote->getStoreId()
                        );

                        $taxCode = TaxjarConfig::TAXJAR_GIFT_CARD_TAX_CODE;

                        if ($giftTaxClassId) {
                            $giftTaxClass = $this->taxClassRepository->get($giftTaxClassId);
                            $giftTaxClassCode = $giftTaxClass->getTjSalestaxCode();
                            $taxCode = $giftTaxClassCode;
                        }
                    }

                    array_push($lineItems, [
                        'id' => $id,
                        'quantity' => $quantity,
                        'product_tax_code' => $taxCode,
                        'unit_price' => $unitPrice,
                        'discount' => $discount,
                    ]);
                }
            }
        }

        return $lineItems;
    }

    /**
     * Verify if the order changed compared to session
     *
     * @param  array $currentOrder
     * @return bool
     */
    private function _orderChanged($currentOrder)
    {
        $sessionOrder = $this->_getSessionData('order');
        $currentOrder = json_encode($currentOrder, JSON_NUMERIC_CHECK | JSON_PRESERVE_ZERO_FRACTION);

        if ($sessionOrder) {
            return $currentOrder !== $sessionOrder;
        }

        return true;
    }

    /**
     * Get prefixed session data from checkout/session
     *
     * @param  string $key
     * @return object
     */
    private function _getSessionData($key)
    {
        return $this->checkoutSession->getData('taxjar_salestax_' . $key);
    }

    /**
     * Set prefixed session data in checkout/session
     *
     * @param  string $key
     * @param  string $val
     * @return object
     */
    private function _setSessionData($key, $val)
    {
        return $this->checkoutSession->setData('taxjar_salestax_' . $key, $val);
    }

    /**
     * Unset prefixed session data in checkout/session
     *
     * @param  string $key
     * @return object
     */
    private function _unsetSessionData($key)
    {
        return $this->checkoutSession->unsetData('taxjar_salestax_' . $key);
    }

    private function _getStoreValue($value, $storeId)
    {
        return $this->scopeConfig->getValue($value, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
