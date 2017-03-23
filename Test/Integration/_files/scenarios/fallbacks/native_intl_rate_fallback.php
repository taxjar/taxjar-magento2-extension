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
use Taxjar\SalesTax\Test\Integration\Model\Tax\Sales\Total\Quote\SetupUtil;

$taxCalculationData['native_intl_rate_fallback'] = [
    'config_data' => [
        SetupUtil::CONFIG_OVERRIDES => array_merge($taxjarCredentials, [
            'shipping/origin/street_line1' => '600 Montgomery St',
            'shipping/origin/city' => 'San Francisco',
            'shipping/origin/region_id' => SetupUtil::REGION_CA,
            'shipping/origin/country_id' => SetupUtil::COUNTRY_US,
            'shipping/origin/postcode' => '94111'
        ]),
        SetupUtil::TAX_RULE_OVERRIDES => [
            [
                'code' => 'Int Product Tax Rule',
                'tax_rate_ids' => [SetupUtil::TAX_RATE_INDIA],
                'product_tax_class_ids' => [SetupUtil::PRODUCT_DEFAULT_TAX_CLASS]
            ]
        ]
    ],
    'quote_data' => [
        'billing_address' => [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'street' => 'Apollo Bunder',
            'city' => 'Mumbai',
            'postcode' => '400001',
            'country_id' => SetupUtil::COUNTRY_IN,
            'telephone' => '999-999-9999'
        ],
        'shipping_address' => [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'street' => 'Apollo Bunder',
            'city' => 'Mumbai',
            'postcode' => '400001',
            'country_id' => SetupUtil::COUNTRY_IN,
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
            'tax_amount' => 4.35,
            'subtotal' => 29.99,
            'subtotal_incl_tax' => 29.99 + 4.35,
            'grand_total' => 29.99 + 4.35
        ],
        'items_data' => [
            'taxjar-tshirt' => [
                'tax_amount' => 4.35,
                'tax_percent' => 14.5,
                'price' => 29.99,
                'price_incl_tax' => 29.99 + 4.35,
                'row_total' => 29.99,
                'row_total_incl_tax' => 29.99 + 4.35
            ],
        ],
    ],
];
