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

use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Configuration
{
    const TAXJAR_VERSION              = '1.5.7';
    const TAXJAR_AUTH_URL             = 'https://app.taxjar.com';
    const TAXJAR_API_URL              = 'https://api.taxjar.com/v2';
    const TAXJAR_SANDBOX_API_URL      = 'https://api.sandbox.taxjar.com/v2';
    const TAXJAR_FEED_URL             = 'www.taxjar.com/magento2/feed.xml';
    const TAXJAR_ADDRESS_VALIDATION   = 'tax/taxjar/address_validation';
    const TAXJAR_APIKEY               = 'tax/taxjar/apikey';
    const TAXJAR_SANDBOX_APIKEY       = 'tax/taxjar/sandbox_apikey';
    const TAXJAR_BACKUP               = 'tax/taxjar/backup';
    const TAXJAR_CONNECTED            = 'tax/taxjar/connected';
    const TAXJAR_CUSTOMER_TAX_CLASSES = 'tax/taxjar/customer_tax_classes';
    const TAXJAR_DEBUG                = 'tax/taxjar/debug';
    const TAXJAR_EMAIL                = 'tax/taxjar/email';
    const TAXJAR_ENABLED              = 'tax/taxjar/enabled';
    const TAXJAR_FREIGHT_TAXABLE      = 'tax/taxjar/freight_taxable';
    const TAXJAR_LAST_UPDATE          = 'tax/taxjar/last_update';
    const TAXJAR_PLUS                 = 'tax/taxjar/plus';
    const TAXJAR_PRODUCT_TAX_CLASSES  = 'tax/taxjar/product_tax_classes';
    const TAXJAR_SANDBOX_ENABLED      = 'tax/taxjar/sandbox';
    const TAXJAR_STATES               = 'tax/taxjar/states';
    const TAXJAR_TRANSACTION_SYNC     = 'tax/taxjar/transactions';
    const TAXJAR_DEFAULT_LOG          = 'default.log';
    const TAXJAR_CALCULATIONS_LOG     = 'calculations.log';
    const TAXJAR_TRANSACTIONS_LOG     = 'transactions.log';
    const TAXJAR_ADDRVALIDATION_LOG   = 'address_validation.log';
    const TAXJAR_CUSTOMER_LOG         = 'customers.log';
    const TAXJAR_EXEMPT_TAX_CODE      = '99999';
    const TAXJAR_GIFT_CARD_TAX_CODE   = '14111803A0001';
    const TAXJAR_BACKUP_RATE_CODE     = 'TaxJar Backup Rates';

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param Config $resourceConfig
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Config $resourceConfig,
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
    public function getApiUrl()
    {
        return $this->isSandboxEnabled() ? self::TAXJAR_SANDBOX_API_URL : self::TAXJAR_API_URL;
    }

    /**
     * Returns the scoped API token
     *
     * @param int $storeId
     * @return string
     */
    public function getApiKey($storeId = null)
    {
        return preg_replace('/\s+/', '', $this->scopeConfig->getValue(
            $this->isSandboxEnabled() ? self::TAXJAR_SANDBOX_APIKEY : self::TAXJAR_APIKEY,
            is_null($storeId) ? ScopeConfigInterface::SCOPE_TYPE_DEFAULT : ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * Checks if sandbox mode is enabled
     *
     * @return bool
     */
    public function isSandboxEnabled() {
        return (bool) $this->scopeConfig->getValue(self::TAXJAR_SANDBOX_ENABLED);
    }

    /**
     * Sets tax basis in Magento
     *
     * @param string $configJson
     * @return void
     */
    public function setTaxBasis($configJson)
    {
        $basis = 'shipping';

        if ($configJson['tax_source'] === 'origin') {
            $basis = 'origin';
        }

        $this->_setConfig('tax/calculation/based_on', $basis);
    }

    /**
     * Set display settings for tax in Magento
     *
     * @return void
     */
    public function setDisplaySettings()
    {
        $settings = [
            'tax/display/type',
            'tax/display/shipping',
            'tax/cart_display/price',
            'tax/cart_display/subtotal',
            'tax/cart_display/shipping'
        ];

        foreach ($settings as $setting) {
            $this->_setConfig($setting, 1);
        }
    }

    /**
     * Store config
     *
     * @param string $path
     * @param string $value
     * @return void
     */
    private function _setConfig($path, $value)
    {
        $this->resourceConfig->saveConfig($path, $value, 'default', 0);
    }
}
