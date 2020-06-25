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
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param RegionFactory $regionFactory
     * @param NexusFactory $nexusFactory
     * @param \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepositoryInterface
     * @param ScopeConfigInterface $scopeConfig
     * @param ZendClientFactory $clientFactory
     * @param ProductMetadataInterface $productMetadata
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Taxjar\SalesTax\Helper\Data $tjHelper
     * @param TaxjarConfig $taxjarConfig
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
        TaxjarConfig $taxjarConfig
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
        $address = $shippingAssignment->getShipping()->getAddress();
        $apiKey = $this->taxjarConfig->getApiKey($quote->getStoreId());

        if (!$apiKey) {
            return;
        }

        if (!$address->getPostcode()) {
            return;
        }

        if (!$this->_validatePostcode($address->getPostcode(), $address->getCountry())) {
            return;
        }

        if (!$this->_hasNexus($quote->getStoreId(), $address->getRegionCode(), $address->getCountry())) {
            return;
        }

        if (!count($address->getAllItems())) {
            return;
        }

        if ($this->_isCustomerExempt($address)) {
            return;
        }

        $shipping = (float) $address->getShippingAmount();
        $shippingDiscount = (float) $address->getShippingDiscountAmount();

        $shippingRegionId = $this->scopeConfig->getValue('shipping/origin/region_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $quote->getStoreId()
        );

        $fromAddress = [
            'from_country' => $this->scopeConfig->getValue('shipping/origin/country_id',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $quote->getStoreId()
            ),
            'from_zip' => $this->scopeConfig->getValue('shipping/origin/postcode',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $quote->getStoreId()
            ),
            'from_state' => $this->regionFactory->create()->load($shippingRegionId)->getCode(),
            'from_city' => $this->scopeConfig->getValue('shipping/origin/city',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $quote->getStoreId()
            ),
            'from_street' => $this->scopeConfig->getValue('shipping/origin/street_line1',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $quote->getStoreId()
            ),
        ];

        $toAddress = [
            'to_country' => $address->getCountry(),
            'to_zip' => $address->getPostcode(),
            'to_state' => $address->getRegionCode(),
            'to_city' => $address->getCity(),
            'to_street' => $address->getStreetLine(1)
        ];

        $order = array_merge($fromAddress, $toAddress, [
            'shipping' => $shipping - abs($shippingDiscount),
            'line_items' => $this->_getLineItems($quote, $quoteTaxDetails),
            'nexus_addresses' => $this->_getNexusAddresses($quote->getStoreId()),
            'customer_id' => $quote->getCustomerId() ? $quote->getCustomerId() : '',
            'plugin' => 'magento'
        ]);

        if ($this->_orderChanged($order)) {
            $client = $this->clientFactory->create();
            $client->setUri($this->taxjarConfig->getApiUrl() . '/magento/taxes');
            $client->setConfig([
                'useragent' => $this->tjHelper->getUserAgent(),
                'referer' => $this->tjHelper->getStoreUrl()
            ]);
            $client->setHeaders('Authorization', 'Bearer ' . $apiKey);
            $client->setRawData(json_encode($order), 'application/json');

            $this->logger->log('Calculating sales tax: ' . json_encode($order), 'post');

            $this->_setSessionData('order', json_encode($order, JSON_NUMERIC_CHECK | JSON_PRESERVE_ZERO_FRACTION));

            try {
                $response = $client->request('POST');
                $this->response = $response;
                $this->_setSessionData('response', $response);

                if (200 == $response->getStatus()) {
                    $this->logger->log('Successful API response: ' . $response->getBody(), 'success');
                } else {
                    $errorResponse = json_decode($response->getBody());
                    $this->logger->log($errorResponse->status . ' ' . $errorResponse->error . ' - ' . $errorResponse->detail, 'error');
                }
            } catch (\Zend_Http_Client_Exception $e) {
                // Catch API timeouts and network issues
                $this->logger->log('API timeout or network issue between your store and TaxJar, please try again later.', 'error');
                $this->response = null;
                $this->_unsetSessionData('response');
            }
        } else {
            $sessionResponse = $this->_getSessionData('response');

            if (isset($sessionResponse)) {
                $this->response = $sessionResponse;
            }
        }

        return $this;
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
            $patterns = $postCodes[$countryId];

            foreach ($patterns as $pattern) {
                preg_match('/' . $pattern['pattern'] . '/', $postcode, $matches);

                if (count($matches)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Verify if nexus is triggered for location
     *
     * @param int $storeId
     * @param string $regionCode
     * @param string $country
     * @return bool
     */
    private function _hasNexus($storeId, $regionCode, $country)
    {
        if ($country == 'US') {
            if (empty($regionCode)) {
                return false;
            }

            $nexusInRegion = $this->nexusFactory->create()->getCollection()
                ->addStoreFilter($storeId)
                ->addRegionCodeFilter($regionCode);

            if ($nexusInRegion->getSize()) {
                return true;
            }
        } else {
            $nexusInCountry = $this->nexusFactory->create()->getCollection()
                ->addStoreFilter($storeId)
                ->addCountryFilter($country);

            if ($nexusInCountry->getSize()) {
                return true;
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

            if ($customerTaxCode == TaxjarConfig::TAXJAR_EXEMPT_TAX_CODE) {
                return true;
            }
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

                        if ($extensionAttributes->getPriceType() == \Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC) {
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

                    if ($item->getTaxClassKey()->getValue()) {
                        $taxClass = $this->taxClassRepository->get($item->getTaxClassKey()->getValue());
                        $taxCode = $taxClass->getTjSalestaxCode();
                    }

                    if ($this->productMetadata->getEdition() == 'Enterprise' || $this->productMetadata->getEdition() == 'B2B') {
                        if ($extensionAttributes->getProductType() == \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD) {
                            $giftTaxClassId = $this->scopeConfig->getValue('tax/classes/wrapping_tax_class',
                                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                                $quote->getStoreId()
                            );

                            if ($giftTaxClassId) {
                                $giftTaxClass = $this->taxClassRepository->get($giftTaxClassId);
                                $giftTaxClassCode = $giftTaxClass->getTjSalestaxCode();
                                $taxCode = $giftTaxClassCode;
                            } else {
                                $taxCode = TaxjarConfig::TAXJAR_GIFT_CARD_TAX_CODE;
                            }
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
     * Get international nexus addresses for `nexus_addresses` param
     *
     * @param int $storeId
     * @return array
     */
    private function _getNexusAddresses($storeId)
    {
        $nexusAddresses = $this->nexusFactory->create()->getCollection()->addStoreFilter($storeId);
        $addresses = [];

        foreach ($nexusAddresses as $nexusAddress) {
            $addresses[] = [
                'id' => $nexusAddress->getId(),
                'country' => $nexusAddress->getCountryId(),
                'zip' => $nexusAddress->getPostcode(),
                'state' => $nexusAddress->getRegionCode(),
                'city' => $nexusAddress->getCity(),
                'street' => $nexusAddress->getStreet()
            ];
        }

        return $addresses;
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
        } else {
            return true;
        }
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
}
