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

$taxCalculationData['shipping_tax_calculation'] = [
    'config_data' => [
        SetupUtil::CONFIG_OVERRIDES => array_merge($taxjarCredentials, [
            'shipping/origin/street_line1' => '4525 Oak St',
            'shipping/origin/city' => 'Kansas City',
            'shipping/origin/region_id' => SetupUtil::REGION_MO,
            'shipping/origin/country_id' => SetupUtil::COUNTRY_US,
            'shipping/origin/postcode' => '64111'
        ]),
        SetupUtil::NEXUS_OVERRIDES => [
            [
                'street' => '4525 Oak St',
                'city' => 'Kansas City',
                'country_id' => SetupUtil::COUNTRY_US,
                'region' => 'Missouri',
                'region_id' => SetupUtil::REGION_MO,
                'region_code' => 'MO',
                'postcode' => '64111'
            ]
        ]
    ],
    'quote_data' => [
        'billing_address' => [
            'firstname' => 'Jake',
            'lastname' => 'Johnson',
            'street' => '4750 Broadway St',
            'city' => 'Kansas City',
            'region_id' => SetupUtil::REGION_MO,
            'postcode' => '64112',
            'country_id' => SetupUtil::COUNTRY_US,
            'telephone' => '999-999-9999'
        ],
        'shipping_address' => [
            'firstname' => 'Jake',
            'lastname' => 'Johnson',
            'street' => '4750 Broadway St',
            'city' => 'Kansas City',
            'region_id' => SetupUtil::REGION_MO,
            'postcode' => '64112',
            'country_id' => SetupUtil::COUNTRY_US,
            'telephone' => '999-999-9999'
        ],
        'items' => [
            [
                'type' => \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
                'sku' => 'taxjar-bbq',
                'price' => 49.99,
                'qty' => 1
            ]
        ],
        'shipping' => [
            'method' => 'flatrate_flatrate',
            'description' => 'Flat Rate - Fixed',
            'amount' => 5,
            'base_amount' => 5,
        ]
    ],
    'expected_results' => [
        'address_data' => [
            'tax_amount' => 4.66,
            'subtotal' => 49.99,
            'subtotal_incl_tax' => 49.99 + (4.66 - 0.42),
            'grand_total' => 49.99 + 4.66 + 5
        ],
        'items_data' => [
            'taxjar-bbq' => [
                'tax_amount' => 4.24,
                'tax_percent' => 8.475,
                'price' => 49.99,
                'price_incl_tax' => 49.99 + 4.24,
                'row_total' => 49.99,
                'row_total_incl_tax' => 49.99 + 4.24
            ],
            'shipping' => [
                'tax_amount' => 0.42,
                'tax_percent' => 8.475,
                'row_total' => 5,
                'row_total_incl_tax' => 5 + 0.42
            ]
        ],
    ],
];
