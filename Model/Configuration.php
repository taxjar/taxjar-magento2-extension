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

use Magento\Config\Model\ResourceModel\Config as MagentoConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Tax\Model\Config as MagentoTaxConfig;

class Configuration
{
    const TAXJAR_VERSION                    = '2.1.0';
    const TAXJAR_AUTH_URL                   = 'https://app.taxjar.com';
    const TAXJAR_API_URL                    = 'https://api.taxjar.com/v2';
    const TAXJAR_SANDBOX_API_URL            = 'https://api.sandbox.taxjar.com/v2';
    const TAXJAR_MAGETWO_GUIDE_URL          = 'https://developers.taxjar.com/integrations/guides/magento2';
    const TAXJAR_FEED_URL                   = 'www.taxjar.com/magento2/feed.xml';
    const TAXJAR_ADDRESS_VALIDATION         = 'tax/taxjar/address_validation';
    const TAXJAR_APIKEY                     = 'tax/taxjar/apikey';
    const TAXJAR_SANDBOX_APIKEY             = 'tax/taxjar/sandbox_apikey';
    const TAXJAR_BACKUP                     = 'tax/taxjar/backup';
    const TAXJAR_BACKUP_RATE_COUNT          = 'tax/taxjar/backup_rate_count';
    const TAXJAR_CONNECTED                  = 'tax/taxjar/connected';
    const TAXJAR_CUSTOMER_TAX_CLASSES       = 'tax/taxjar/customer_tax_classes';
    const TAXJAR_DEBUG                      = 'tax/taxjar/debug';
    const TAXJAR_EMAIL                      = 'tax/taxjar/email';
    const TAXJAR_ENABLED                    = 'tax/taxjar/enabled';
    const TAXJAR_FREIGHT_TAXABLE            = 'tax/taxjar/freight_taxable';
    const TAXJAR_LAST_UPDATE                = 'tax/taxjar/last_update';
    const TAXJAR_PLUS                       = 'tax/taxjar/plus';
    const TAXJAR_PRODUCT_TAX_CLASSES        = 'tax/taxjar/product_tax_classes';
    const TAXJAR_SANDBOX_ENABLED            = 'tax/taxjar/sandbox';
    const TAXJAR_STATES                     = 'tax/taxjar/states';
    const TAXJAR_TRANSACTION_SYNC           = 'tax/taxjar/transactions';
    const TAXJAR_DEFAULT_LOG                = 'default.log';
    const TAXJAR_CALCULATIONS_LOG           = 'calculations.log';
    const TAXJAR_TRANSACTIONS_LOG           = 'transactions.log';
    const TAXJAR_ADDRVALIDATION_LOG         = 'address_validation.log';
    const TAXJAR_CUSTOMER_LOG               = 'customers.log';
    const TAXJAR_TAXABLE_TAX_CODE           = '11111';
    const TAXJAR_EXEMPT_TAX_CODE            = '99999';
    const TAXJAR_GIFT_CARD_TAX_CODE         = '14111803A0001';
    const TAXJAR_BACKUP_RATE_CODE           = 'TaxJar Backup Rates';
    const TAXJAR_X_API_VERSION              = '2020-08-07';
    const TAXJAR_TOPIC_CREATE_RATES         = 'taxjar.backup_rates.create';
    const TAXJAR_TOPIC_DELETE_RATES         = 'taxjar.backup_rates.delete';
    const TAXJAR_TOPIC_SYNC_TRANSACTIONS    = 'taxjar.transactions.sync';

    /**
     * @var MagentoConfig
     */
    protected $resourceConfig;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param MagentoConfig $resourceConfig
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        MagentoConfig $resourceConfig,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->resourceConfig = $resourceConfig;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Returns the base API url
     *
     * @return string
     */
    public function getApiUrl(): string
    {
        return $this->isSandboxEnabled() ? self::TAXJAR_SANDBOX_API_URL : self::TAXJAR_API_URL;
    }

    /**
     * Returns the scoped API token
     *
     * @param int|null $storeId
     * @return string
     */
    public function getApiKey(?int $storeId = null): string
    {
        $apiKey = (string) $this->scopeConfig->getValue(
            $this->isSandboxEnabled() ? self::TAXJAR_SANDBOX_APIKEY : self::TAXJAR_APIKEY,
            ($storeId === null) ? ScopeConfigInterface::SCOPE_TYPE_DEFAULT : ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return preg_replace('/\s+/', '', $apiKey);
    }

    /**
     * Checks if sandbox mode is enabled
     *
     * @return bool
     */
    public function isSandboxEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::TAXJAR_SANDBOX_ENABLED);
    }

    /**
     * Sets tax basis in Magento
     *
     * @param $configJson
     * @return void
     */
    public function setTaxBasis($configJson): void
    {
        $basis = 'shipping';

        if ($configJson['tax_source'] === 'origin') {
            $basis = 'origin';
        }

        $this->_setConfig(MagentoTaxConfig::CONFIG_XML_PATH_BASED_ON, $basis);
    }

    public function getBackupRateCount(): int
    {
        return (int) $this->scopeConfig->getValue(self::TAXJAR_BACKUP_RATE_COUNT);
    }

    public function setBackupRateCount($value): void
    {
        $this->_setConfig(self::TAXJAR_BACKUP_RATE_COUNT, $value);
    }

    /**
     * Set display settings for tax in Magento
     *
     * @return void
     */
    public function setDisplaySettings(): void
    {
        $settings = [
            MagentoTaxConfig::CONFIG_XML_PATH_PRICE_DISPLAY_TYPE,
            MagentoTaxConfig::CONFIG_XML_PATH_DISPLAY_SHIPPING,
            MagentoTaxConfig::XML_PATH_DISPLAY_CART_PRICE,
            MagentoTaxConfig::XML_PATH_DISPLAY_CART_SUBTOTAL,
            MagentoTaxConfig::XML_PATH_DISPLAY_CART_SHIPPING,
        ];

        foreach ($settings as $setting) {
            $this->_setConfig($setting, 1);
        }
    }

    /**
     * Store config
     *
     * @param string $path
     * @param mixed $value
     * @return void
     */
    private function _setConfig(string $path, $value): void
    {
        $this->resourceConfig->saveConfig($path, $value, 'default');
    }
}
