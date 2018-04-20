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

$taxCalculationData['ny_clothing_exemption_under_110'] = [
    'config_data' => [
        SetupUtil::CONFIG_OVERRIDES => array_merge($taxjarCredentials, [
            'shipping/origin/street_line1' => '350 5th Ave',
            'shipping/origin/city' => 'New York',
            'shipping/origin/region_id' => SetupUtil::REGION_NY,
            'shipping/origin/country_id' => SetupUtil::COUNTRY_US,
            'shipping/origin/postcode' => '10118'
        ])
    ],
    'quote_data' => [
        'billing_address' => [
            'firstname' => 'George',
            'lastname' => 'Costanza',
            'street' => '321 W. 90th Street',
            'city' => 'New York',
            'region_id' => SetupUtil::REGION_NY,
            'postcode' => '10024',
            'country_id' => SetupUtil::COUNTRY_US,
            'telephone' => '999-999-9999'
        ],
        'shipping_address' => [
            'firstname' => 'George',
            'lastname' => 'Costanza',
            'street' => '321 W. 90th Street',
            'city' => 'New York',
            'region_id' => SetupUtil::REGION_NY,
            'postcode' => '10024',
            'country_id' => SetupUtil::COUNTRY_US,
            'telephone' => '999-999-9999'
        ],
        'items' => [
            [
                'type' => \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
                'sku' => 'taxjar-tshirt',
                'price' => 19.99,
                'qty' => 1,
                'tax_class_name' => SetupUtil::PRODUCT_CLOTHING_TAX_CLASS
            ]
        ],
        'shipping' => [
            'method' => 'flatrate_flatrate',
            'description' => 'Flat Rate - Fixed',
            'amount' => 5,
            'base_amount' => 5,
        ],
    ],
    'expected_results' => [
        'address_data' => [
            'tax_amount' => 0,
            'subtotal' => 19.99,
            'subtotal_incl_tax' => 19.99,
            'grand_total' => 19.99 + 5
        ],
        'items_data' => [
            'taxjar-tshirt' => [
                'tax_amount' => 0,
                'tax_percent' => 0,
                'price' => 19.99,
                'price_incl_tax' => 19.99,
                'row_total' => 19.99,
                'row_total_incl_tax' => 19.99
            ],
        ],
        'shipping' => [
            'tax_amount' => 0,
            'tax_percent' => 0,
            'row_total' => 5,
            'row_total_incl_tax' => 5
        ],
    ],
];

$taxCalculationData['ny_clothing_exemption_over_110'] = [
    'config_data' => [
        SetupUtil::CONFIG_OVERRIDES => array_merge($taxjarCredentials, [
            'shipping/origin/street_line1' => '350 5th Ave',
            'shipping/origin/city' => 'New York',
            'shipping/origin/region_id' => SetupUtil::REGION_NY,
            'shipping/origin/country_id' => SetupUtil::COUNTRY_US,
            'shipping/origin/postcode' => '10118'
        ])
    ],
    'quote_data' => [
        'billing_address' => [
            'firstname' => 'George',
            'lastname' => 'Costanza',
            'street' => '321 W. 90th Street',
            'city' => 'New York',
            'region_id' => SetupUtil::REGION_NY,
            'postcode' => '10024',
            'country_id' => SetupUtil::COUNTRY_US,
            'telephone' => '999-999-9999'
        ],
        'shipping_address' => [
            'firstname' => 'George',
            'lastname' => 'Costanza',
            'street' => '321 W. 90th Street',
            'city' => 'New York',
            'region_id' => SetupUtil::REGION_NY,
            'postcode' => '10024',
            'country_id' => SetupUtil::COUNTRY_US,
            'telephone' => '999-999-9999'
        ],
        'items' => [
            [
                'type' => \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
                'sku' => 'taxjar-jacket',
                'price' => 199.99,
                'qty' => 1,
                'tax_class_name' => SetupUtil::PRODUCT_CLOTHING_TAX_CLASS
            ]
        ],
        'shipping' => [
            'method' => 'flatrate_flatrate',
            'description' => 'Flat Rate - Fixed',
            'amount' => 5,
            'base_amount' => 5,
        ],
    ],
    'expected_results' => [
        'address_data' => [
            'tax_amount' => 17.75 + 0.44,
            'subtotal' => 199.99,
            'subtotal_incl_tax' => 199.99 + 17.75,
            'grand_total' => 199.99 + 17.75 + 5 + 0.44
        ],
        'items_data' => [
            'taxjar-jacket' => [
                'tax_amount' => 17.75,
                'tax_percent' => 8.875,
                'price' => 199.99,
                'price_incl_tax' => 199.99 + 17.75,
                'row_total' => 199.99,
                'row_total_incl_tax' => 199.99 + 17.75
            ],
        ],
        'shipping' => [
            'tax_amount' => 0.44,
            'tax_percent' => 8.875,
            'row_total' => 5,
            'row_total_incl_tax' => 5 + 0.44
        ],
    ],
];
