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
 * @copyright  Copyright (c) 2022 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc.
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Helper;

use Magento\Store\Model\ScopeInterface;
use Taxjar\SalesTax\Model\Configuration;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected const SYNCABLE_CURRENCIES = ['USD'];

    protected const SYNCABLE_COUNTRIES = ['US'];

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var array
     */
    private array $_syncableOrderStates;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param array $syncableOrderStates
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        array $syncableOrderStates = []
    ) {
        parent::__construct($context);

        $this->request = $request;
        $this->productMetadata = $productMetadata;
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
        $this->_syncableOrderStates = $syncableOrderStates;
    }

    /**
     * @param $scope
     * @param $scopeCode
     *
     * @return bool
     */
    public function isEnabled($scope = ScopeInterface::SCOPE_STORE, $scopeCode = null): bool
    {
        return (bool) $this->scopeConfig->getValue(Configuration::TAXJAR_ENABLED, $scope, $scopeCode);
    }

    /**
     * Transaction Sync enabled check
     *
     * @param int $scopeCode
     * @param string $scope
     * @return bool
     */
    public function isTransactionSyncEnabled($scopeCode = 0, $scope = ScopeInterface::SCOPE_STORE): bool
    {
        $scopeCode = $scopeCode ?: (int) $this->request->getParam($scope, 0);
        return (bool) $this->scopeConfig->getValue(Configuration::TAXJAR_TRANSACTION_SYNC, $scope, $scopeCode);
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
        $precision = 'Precision ' .  \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION;
        $taxjar = 'Taxjar_SalesTax/' . Configuration::TAXJAR_VERSION;

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
     * @return bool
     */
    public function isSyncableOrder(\Magento\Sales\Api\Data\OrderInterface $order): bool
    {
        $validated = $this->getOrderValidation($order);
        return array_reduce($validated, function ($a, $b) {
            return $a && $b;
        }, true);
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
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
     * Returns an array of syncable order states as defined in di.xml and injected in constructor.
     *
     * @return array
     */
    public function getSyncableOrderStates(): array
    {
        return $this->_syncableOrderStates;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return bool
     */
    public function isSyncableOrderState(\Magento\Sales\Api\Data\OrderInterface $order): bool
    {
        return in_array($order->getState(), array_values($this->getSyncableOrderStates()));
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
