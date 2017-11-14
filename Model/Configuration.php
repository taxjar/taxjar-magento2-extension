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

class Configuration
{
    const TAXJAR_VERSION              = '0.7.6';
    const TAXJAR_AUTH_URL             = 'https://app.taxjar.com';
    const TAXJAR_API_URL              = 'https://api.taxjar.com/v2';
    const TAXJAR_APIKEY               = 'tax/taxjar/apikey';
    const TAXJAR_BACKUP               = 'tax/taxjar/backup';
    const TAXJAR_CATEGORIES           = 'tax/taxjar/categories';
    const TAXJAR_CONNECTED            = 'tax/taxjar/connected';
    const TAXJAR_CUSTOMER_TAX_CLASSES = 'tax/taxjar/customer_tax_classes';
    const TAXJAR_DEBUG                = 'tax/taxjar/debug';
    const TAXJAR_EMAIL                = 'tax/taxjar/email';
    const TAXJAR_ENABLED              = 'tax/taxjar/enabled';
    const TAXJAR_FREIGHT_TAXABLE      = 'tax/taxjar/freight_taxable';
    const TAXJAR_LAST_UPDATE          = 'tax/taxjar/last_update';
    const TAXJAR_PRODUCT_TAX_CLASSES  = 'tax/taxjar/product_tax_classes';
    const TAXJAR_STATES               = 'tax/taxjar/states';
    const TAXJAR_TRANSACTION_AUTH     = 'tax/taxjar/transaction_auth';
    const TAXJAR_TRANSACTION_SYNC     = 'tax/taxjar/transactions';
    const TAXJAR_EXEMPT_TAX_CODE      = '99999';

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;

    /**
     * @param Config $resourceConfig
     */
    public function __construct(
        Config $resourceConfig
    ) {
        $this->resourceConfig = $resourceConfig;
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
