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
 * @copyright  Copyright (c) 2017 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc.
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Pricing\PriceCurrencyInterface as PriceCurrencyInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class Data extends AbstractHelper
{
    protected const SYNCABLE_STATES = ['complete', 'closed'];

    protected const SYNCABLE_CURRENCIES = ['USD'];

    protected const SYNCABLE_COUNTRIES = ['US'];

    protected $request;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @param Context $context
     * @param Http $request
     * @param ProductMetadataInterface $productMetadata
     * @param PriceCurrencyInterface $priceCurrency,
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        Http $request,
        ProductMetadataInterface $productMetadata,
        StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->request = $request;
        $this->productMetadata = $productMetadata;
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
        parent::__construct($context);
    }

    /**
     * Sort a multidimensional array by key
     *
     * @param array $array
     * @param string $on
     * @param const $order
     * @return array
     */
    public function sortArray($array, $on, $order = SORT_ASC)
    {
        $newArray = [];
        $sortableArray = [];

        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortableArray[$k] = $v2;
                        }
                    }
                } else {
                    $sortableArray[$k] = $v;
                }
            }

            switch ($order) {
                case SORT_ASC:
                    asort($sortableArray);
                    break;
                case SORT_DESC:
                    arsort($sortableArray);
                    break;
            }

            foreach ($sortableArray as $k => $v) {
                $newArray[$k] = $array[$k];
            }
        }

        return $newArray;
    }

    /**
     * Transaction Sync enabled check
     *
     * @param int $scopeCode
     * @param string $scope
     * @return bool
     */
    public function isTransactionSyncEnabled($scopeCode = 0, $scope = ScopeInterface::SCOPE_STORE)
    {
        $scopeCode = $scopeCode ?: (int) $this->request->getParam($scope, 0);
        return (bool)$this->scopeConfig->getValue(TaxjarConfig::TAXJAR_TRANSACTION_SYNC, $scope, $scopeCode);
    }

    /**
     * Return a custom user agent string
     *
     * @return string
     */
    public function getUserAgent()
    {
        $disabledFunctions = explode(',', ini_get('disable_functions'));
        $os = !in_array('php_uname', $disabledFunctions) ? php_uname('a') : '';
        $php = 'PHP ' . PHP_VERSION;
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $curl = !in_array('curl_version', $disabledFunctions) ? 'cURL ' . curl_version()['version'] : '';
        $openSSL = defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : '';
        $magento = 'Magento ' . $this->productMetadata->getEdition() . ' ' . $this->productMetadata->getVersion();
        $precision = 'Precision ' . PriceCurrencyInterface::DEFAULT_PRECISION;
        $taxjar = 'Taxjar_SalesTax/' . TaxjarConfig::TAXJAR_VERSION;

        return "TaxJar/Magento ($os; $php; $curl; $openSSL; $precision; $magento) $taxjar";
    }

    /**
     * Return the base url of the current store
     *
     * @return string|null
     */
    public function getStoreUrl(): ?string
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return \Magento\Sales\Api\Data\OrderAddressInterface|null
     */
    public function getOrderAddress(
        \Magento\Sales\Api\Data\OrderInterface $order
    ): ?\Magento\Sales\Api\Data\OrderAddressInterface {
        return $order->getIsVirtual()
            ? $order->getBillingAddress()
            : $order->getShippingAddress();
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param string|null $key
     * @return bool
     */
    public function isSyncableOrder(\Magento\Sales\Api\Data\OrderInterface $order, string $key = null): bool
    {
        $validated = $this->getOrderValidation($order);

        if ($key !== null) {
            return array_key_exists($key, $validated) ? $validated[$key] : false;
        }

        return array_reduce($validated, function ($a, $b) {
            return $a && $b;
        }, true);
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    public function getOrderValidation(\Magento\Sales\Api\Data\OrderInterface $order): array
    {
        return [
            'state' => $this->isSyncableOrderState($order),
            'order_currency_code' => $this->isSyncableOrderCurrency($order),
            'country' => $this->isSyncableOrderCountry(
                $this->getOrderAddress($order)
            ),
        ];
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return bool
     */
    public function isSyncableOrderState(\Magento\Sales\Api\Data\OrderInterface $order): bool
    {
        return in_array($order->getState(), self::SYNCABLE_STATES);
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return bool
     */
    public function isSyncableOrderCurrency(\Magento\Sales\Api\Data\OrderInterface $order): bool
    {
        return in_array($order->getOrderCurrencyCode(), self::SYNCABLE_CURRENCIES);
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return bool
     */
    public function isSyncableOrderCountry(\Magento\Sales\Api\Data\OrderAddressInterface $address): bool
    {
        return in_array($address->getCountryId(), self::SYNCABLE_COUNTRIES);
    }
}
