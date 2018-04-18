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

$taxCalculationData['line_item_rounding_calculation'] = [
    'config_data' => [
        SetupUtil::CONFIG_OVERRIDES => array_merge($taxjarCredentials, [
            'shipping/origin/street_line1' => '201 N Broadway',
            'shipping/origin/city' => 'Escondido',
            'shipping/origin/region_id' => SetupUtil::REGION_CA,
            'shipping/origin/country_id' => SetupUtil::COUNTRY_US,
            'shipping/origin/postcode' => '92025'
        ])
    ],
    'quote_data' => [
        'billing_address' => [
            'firstname' => 'Jake',
            'lastname' => 'Johnson',
            'street' => '19182 Jamboree Rd',
            'city' => 'Irvine',
            'region_id' => SetupUtil::REGION_CA,
            'postcode' => '92612',
            'country_id' => SetupUtil::COUNTRY_US,
            'telephone' => '999-999-9999'
        ],
        'shipping_address' => [
            'firstname' => 'Jake',
            'lastname' => 'Johnson',
            'street' => '19182 Jamboree Rd',
            'city' => 'Irvine',
            'region_id' => SetupUtil::REGION_CA,
            'postcode' => '92612',
            'country_id' => SetupUtil::COUNTRY_US,
            'telephone' => '999-999-9999'
        ],
        'items' => [
            [
                'type' => \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
                'sku' => 'taxjar-book1',
                'price' => 395,
                'qty' => 1
            ],
            [
                'type' => \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
                'sku' => 'taxjar-book2',
                'price' => 50,
                'qty' => 1
            ],
            [
                'type' => \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
                'sku' => 'taxjar-book3',
                'price' => 50,
                'qty' => 1
            ]
        ]
    ],
    'expected_results' => [
        'address_data' => [
            'tax_amount' => 38.36,
            'subtotal' => 495,
            'subtotal_incl_tax' => 495 + 38.36,
            'grand_total' => 495 + 38.36
        ],
        'items_data' => [
            'taxjar-book1' => [
                'tax_amount' => 30.6125,
                'tax_percent' => 7.750,
                'price' => 395,
                'price_incl_tax' => 395 + 30.6125,
                'row_total' => 395,
                'row_total_incl_tax' => 395 + 30.6125
            ],
            'taxjar-book2' => [
                'tax_amount' => 3.875,
                'tax_percent' => 7.750,
                'price' => 50,
                'price_incl_tax' => 50 + 3.875,
                'row_total' => 50,
                'row_total_incl_tax' => 50 + 3.875
            ],
            'taxjar-book3' => [
                'tax_amount' => 3.875,
                'tax_percent' => 7.750,
                'price' => 50,
                'price_incl_tax' => 50 + 3.875,
                'row_total' => 50,
                'row_total_incl_tax' => 50 + 3.875
            ]
        ],
    ],
];
