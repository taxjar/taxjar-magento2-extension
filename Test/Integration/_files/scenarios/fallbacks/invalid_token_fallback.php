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

use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\Config;
use Taxjar\SalesTax\Model\Configuration as TaxJarConfig;
use Taxjar\SalesTax\Test\Integration\Model\Tax\Sales\Total\Quote\SetupUtil;

$taxCalculationData['invalid_token_fallback'] = [
    'config_data' => [
        SetupUtil::CONFIG_OVERRIDES => array_merge($taxjarCredentials, [
            TaxjarConfig::TAXJAR_APIKEY => 'abc123',
            'shipping/origin/street_line1' => '400 Broad St',
            'shipping/origin/city' => 'Seattle',
            'shipping/origin/region_id' => SetupUtil::REGION_WA,
            'shipping/origin/country_id' => SetupUtil::COUNTRY_US,
            'shipping/origin/postcode' => '98101'
        ]),
        SetupUtil::TAX_RULE_OVERRIDES => [
            [
                'code' => 'Product Tax Rule',
                'tax_rate_ids' => [SetupUtil::TAX_RATE_SEATTLE],
                'product_tax_class_ids' => [SetupUtil::PRODUCT_DEFAULT_TAX_CLASS]
            ]
        ]
    ],
    'quote_data' => [
        'billing_address' => [
            'firstname' => 'Bernd',
            'lastname' => 'Ustorf',
            'street' => '400 Broad St',
            'city' => 'Seattle',
            'region_id' => SetupUtil::REGION_WA,
            'postcode' => '98101',
            'country_id' => SetupUtil::COUNTRY_US,
            'telephone' => '999-999-9999'
        ],
        'shipping_address' => [
            'firstname' => 'Bernd',
            'lastname' => 'Ustorf',
            'street' => '400 Broad St',
            'city' => 'Seattle',
            'region_id' => SetupUtil::REGION_WA,
            'postcode' => '98101',
            'country_id' => SetupUtil::COUNTRY_US,
            'telephone' => '999-999-9999'
        ],
        'items' => [
            [
                'type' => \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
                'sku' => 'taxjar-tshirt',
                'price' => 29.99,
                'qty' => 1
            ]
        ],
    ],
    'expected_results' => [
        'address_data' => [
            'tax_amount' => 2.88,
            'subtotal' => 29.99,
            'subtotal_incl_tax' => 29.99 + 2.88,
            'grand_total' => 29.99 + 2.88
        ],
        'items_data' => [
            'taxjar-tshirt' => [
                'tax_amount' => 2.88,
                'tax_percent' => 9.6,
                'price' => 29.99,
                'price_incl_tax' => 29.99 + 2.88,
                'row_total' => 29.99,
                'row_total_incl_tax' => 29.99 + 2.88
            ],
        ],
    ],
];
