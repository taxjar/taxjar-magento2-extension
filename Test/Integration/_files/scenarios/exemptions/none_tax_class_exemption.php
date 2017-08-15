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

$taxCalculationData['none_tax_class_exemption'] = [
    'config_data' => [
        SetupUtil::CONFIG_OVERRIDES => array_merge($taxjarCredentials, [
            'shipping/origin/street_line1' => '600 Montgomery St',
            'shipping/origin/city' => 'San Francisco',
            'shipping/origin/region_id' => SetupUtil::REGION_CA,
            'shipping/origin/country_id' => 'US',
            'shipping/origin/postcode' => '94111'
        ])
    ],
    'quote_data' => [
        'billing_address' => [
            'firstname' => 'Jake',
            'lastname' => 'Johnson',
            'street' => '123 Westcreek Pkwy',
            'city' => 'Westlake Village',
            'region_id' => SetupUtil::REGION_CA,
            'postcode' => '91362',
            'country_id' => SetupUtil::COUNTRY_US,
            'telephone' => '011-899-9881'
        ],
        'shipping_address' => [
            'firstname' => 'Jake',
            'lastname' => 'Johnson',
            'street' => '123 Westcreek Pkwy',
            'city' => 'Westlake Village',
            'region_id' => SetupUtil::REGION_CA,
            'postcode' => '91362',
            'country_id' => SetupUtil::COUNTRY_US,
            'telephone' => '011-899-9881'
        ],
        'items' => [
            [
                'type' => \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
                'sku' => 'taxjar-tshirt',
                'price' => 29.99,
                'qty' => 1,
                'tax_class_name' => ''
            ]
        ],
    ],
    'expected_results' => [
        'address_data' => [
            'tax_amount' => 0,
            'subtotal' => 29.99,
            'subtotal_incl_tax' => 29.99,
            'grand_total' => 29.99
        ],
        'items_data' => [
            'taxjar-tshirt' => [
                'tax_amount' => 0,
                'tax_percent' => 0,
                'price' => 29.99,
                'price_incl_tax' => 29.99,
                'row_total' => 29.99,
                'row_total_incl_tax' => 29.99
            ],
        ],
    ],
];
