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
 * @copyright  Copyright (c) 2020 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Controller\Adminhtml\Report;

class Index extends \Taxjar\SalesTax\Controller\Adminhtml\Report
{
    /** @var \Magento\Framework\App\Response\Http\FileFactory */
    protected $fileFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Response\Http\FileFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    ) {
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $date = date('Y-m-d');
        $fileName = 'taxjar-report-' . $date . '.json';
        $data = $this->generateReport();

        try {
            return $this->fileFactory->create($fileName, $data);
        } catch (\Exception $e) {
            return $this->fileFactory->create($fileName, 'An error occurred');
        }
    }

    protected function generateReport()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();  //TODO: replace use of OM

        /** @var \Taxjar\SalesTax\Model\Plugindx $plugindx */
        $plugindx = $objectManager->create('Taxjar\SalesTax\Model\Plugindx');

        $config = $this->getConfig();
        $data = $plugindx->build($config);

        return $data;
    }

    protected function getConfig()
    {
        $json = '
            {
              "config": [
                {
                  "name": "TaxJar Email",
                  "path": "tax/taxjar/email"
                },
                {
                  "name": "TaxJar API Key",
                  "path": "tax/taxjar/apikey"
                },
                {
                  "name": "Checkout Calculations",
                  "path": "tax/taxjar/enabled"
                },
                {
                  "name": "Transaction Sync",
                  "path": "tax/taxjar/transactions"
                },
                {
                  "name": "Backup Rates",
                  "path": "tax/taxjar/backup"
                },
                {
                  "name": "Backup Product Tax Classes",
                  "path": "tax/taxjar/product_tax_classes"
                },
                {
                  "name": "Backup Customer Tax Classes",
                  "path": "tax/taxjar/customer_tax_classes"
                },
                {
                  "name": "Debug Mode",
                  "path": "tax/taxjar/debug"
                },
                {
                  "name": "Tax Class for Shipping",
                  "path": "tax/classes/shipping_tax_class"
                },
                {
                  "name": "Tax Class for Gift Options",
                  "path": "tax/classes/wrapping_tax_class"
                },
                {
                  "name": "Default Tax Class for Product",
                  "path": "tax/classes/default_product_tax_class"
                },
                {
                  "name": "Default Tax Class for Customer",
                  "path": "tax/classes/default_customer_tax_class"
                },
                {
                  "name": "Tax Calculation Method Based On",
                  "path": "tax/calculation/algorithm"
                },
                {
                  "name": "Tax Calculation Based On",
                  "path": "tax/calculation/based_on"
                },
                {
                  "name": "Catalog Prices",
                  "path": "tax/calculation/price_includes_tax"
                },
                {
                  "name": "Shipping Prices",
                  "path": "tax/calculation/shipping_includes_tax"
                },
                {
                  "name": "Apply Customer Tax",
                  "path": "tax/calculation/apply_after_discount"
                },
                {
                  "name": "Apply Discount On Prices",
                  "path": "tax/calculation/discount_tax"
                },
                {
                  "name": "Apply Tax On",
                  "path": "tax/calculation/apply_tax_on"
                },
                {
                  "name": "Enable Cross Border Trade",
                  "path": "tax/calculation/cross_border_trade_enabled"
                },
                {
                  "name": "Default Country",
                  "path": "tax/defaults/country"
                },
                {
                  "name": "Default State",
                  "path": "tax/defaults/region"
                },
                {
                  "name": "Default Post Code",
                  "path": "tax/defaults/postcode"
                },
                {
                  "name": "Display Product Prices In Catalog",
                  "path": "tax/display/type"
                },
                {
                  "name": "Display Shipping Prices",
                  "path": "tax/display/shipping"
                },
                {
                  "name": "Display Prices",
                  "path": "tax/cart_display/price"
                },
                {
                  "name": "Display Subtotal",
                  "path": "tax/cart_display/subtotal"
                },
                {
                  "name": "Display Shipping Amount",
                  "path": "tax/cart_display/shipping"
                },
                {
                  "name": "Include Tax In Order Total",
                  "path": "tax/cart_display/grandtotal"
                },
                {
                  "name": "Display Full Tax Summary",
                  "path": "tax/cart_display/full_summary"
                },
                {
                  "name": "Display Zero Tax Subtotal",
                  "path": "tax/cart_display/zero_tax"
                },
                {
                  "name": "Display Gift Wrapping Prices",
                  "path": "tax/cart_display/gift_wrapping"
                },
                {
                  "name": "Display Printed Card Prices",
                  "path": "tax/cart_display/printed_card"
                },
                {
                  "name": "Display Prices",
                  "path": "tax/sales_display/price"
                },
                {
                  "name": "Display Subtotal",
                  "path": "tax/sales_display/subtotal"
                },
                {
                  "name": "Display Shipping Amount",
                  "path": "tax/sales_display/shipping"
                },
                {
                  "name": "Include Tax In Order Total",
                  "path": "tax/sales_display/grandtotal"
                },
                {
                  "name": "Display Full Tax Summary",
                  "path": "tax/sales_display/full_summary"
                },
                {
                  "name": "Display Zero Tax Subtotal",
                  "path": "tax/sales_display/zero_tax"
                },
                {
                  "name": "Display Gift Wrapping Prices",
                  "path": "tax/sales_display/gift_wrapping"
                },
                {
                  "name": "Display Printed Card Prices",
                  "path": "tax/sales_display/printed_card"
                },
                {
                  "name": "Enable FPT",
                  "path": "tax/weee/enable"
                },
                {
                  "name": "Display Prices In Product Lists",
                  "path": "tax/weee/display_list"
                },
                {
                  "name": "Display Prices On Product View Page",
                  "path": "tax/weee/display"
                },
                {
                  "name": "Display Prices In Sales Modules",
                  "path": "tax/weee/display_sales"
                },
                {
                  "name": "Display Prices In Emails",
                  "path": "tax/weee/display_email"
                },
                {
                  "name": "Apply Tax To FPT",
                  "path": "tax/weee/apply_vat"
                },
                {
                  "name": "Include FPT In Subtotal",
                  "path": "tax/weee/include_in_subtotal"
                },
                {
                  "name": "Country",
                  "path": "shipping/origin/country_id"
                },
                {
                  "name": "Region/State",
                  "path": "shipping/origin/region_id"
                },
                {
                  "name": "ZIP/Postal Code",
                  "path": "shipping/origin/postcode"
                },
                {
                  "name": "City",
                  "path": "shipping/origin/city"
                },
                {
                  "name": "Street Address",
                  "path": "shipping/origin/street_line1"
                },
                {
                  "name": "Street Address Line 2",
                  "path": "shipping/origin/street_line2"
                }
              ],
              "helpers": [
                {
                  "name": "Magento Edition",
                  "path": "magento/edition"
                },
                {
                  "name": "Magento Locale",
                  "path": "magento/locale"
                },
                {
                  "name": "Magento Version",
                  "path": "magento/version"
                },
                {
                  "name": "Modules Installed",
                  "path": "magento/modules"
                },
                {
                  "name": "Module Version",
                  "path": "magento/module_version"
                },
                {
                  "name": "Magento Applied Patches",
                  "path": "magento/applied_patches"
                }
              ],
              "server": [
                {
                  "name": "PHP Version",
                  "path": "Core/PHP Version"
                },
                {
                  "name": "memory_limit",
                  "path": "Core/memory_limit"
                }
              ],
              "logs": [
                {
                  "path": "taxjar.log",
                  "lines": 250
                },
                {
                  "path": "system.log",
                  "lines": 250
                },
                {
                  "path": "exception.log",
                  "lines": 250
                }
              ]
            }';

        return $json;
    }
}
