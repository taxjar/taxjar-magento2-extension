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

$taxCalculationData['shopping_cart_rule_percent'] = [
    'config_data' => [
        SetupUtil::CONFIG_OVERRIDES => array_merge($taxjarCredentials, [
            'shipping/origin/street_line1' => '600 Montgomery St',
            'shipping/origin/city' => 'San Francisco',
            'shipping/origin/region_id' => 12,
            'shipping/origin/country_id' => 'US',
            'shipping/origin/postcode' => '94111'
        ])
    ],
    'quote_data' => [
        'billing_address' => [
            'firstname' => 'Tony',
            'lastname' => 'Stark',
            'street' => '10880 Malibu Point',
            'city' => 'Malibu',
            'region_id' => SetupUtil::REGION_CA,
            'postcode' => '90265',
            'country_id' => SetupUtil::COUNTRY_US,
            'telephone' => '999-999-9999'
        ],
        'shipping_address' => [
            'firstname' => 'Tony',
            'lastname' => 'Stark',
            'street' => '10880 Malibu Point',
            'city' => 'Malibu',
            'region_id' => SetupUtil::REGION_CA,
            'postcode' => '90265',
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
        'shopping_cart_rules' => [
            [
                'discount_amount' => 25
            ]
        ]
    ],
    'expected_results' => [
        'address_data' => [
            'tax_amount' => 2.14,
            'subtotal' => 29.99,
            'subtotal_incl_tax' => 29.99 + 2.14,
            'grand_total' => 24.63
        ],
        'items_data' => [
            'taxjar-tshirt' => [
                'tax_amount' => 2.14,
                'tax_percent' => 9.5,
                'price' => 29.99,
                'price_incl_tax' => 29.99 + 2.14,
                'row_total' => 29.99,
                'row_total_incl_tax' => 29.99 + 2.14,
                'discount_amount' => 7.50
            ],
        ],
    ],
];
