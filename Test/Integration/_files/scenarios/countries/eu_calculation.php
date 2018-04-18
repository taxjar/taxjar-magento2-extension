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

$taxCalculationData['eu_calculation'] = [
    'config_data' => [
        SetupUtil::CONFIG_OVERRIDES => array_merge($taxjarCredentials, [
            'shipping/origin/street_line1' => '20 Deans Yd',
            'shipping/origin/city' => 'London',
            'shipping/origin/region_id' => 67,
            'shipping/origin/country_id' => 'GB',
            'shipping/origin/postcode' => 'SW1P 3PA'
        ]),
        SetupUtil::NEXUS_OVERRIDES => [
            [
                'street' => '20 Deans Yd',
                'city' => 'London',
                'country_id' => 'GB',
                'region' => 'Westminster',
                'postcode' => 'SW1P 3PA'
            ]
        ]
    ],
    'quote_data' => [
        'billing_address' => [
            'firstname' => 'Maurice',
            'lastname' => 'Moss',
            'street' => 'Lancaster Terrace',
            'city' => 'London',
            'postcode' => 'W2 2TY',
            'country_id' => 'GB',
            'telephone' => '999-999-9999'
        ],
        'shipping_address' => [
            'firstname' => 'Maurice',
            'lastname' => 'Moss',
            'street' => 'Lancaster Terrace',
            'city' => 'London',
            'postcode' => 'W2 2TY',
            'country_id' => 'GB',
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
            'tax_amount' => 6.00,
            'subtotal' => 29.99,
            'subtotal_incl_tax' => 29.99 + 6.00,
            'grand_total' => 29.99 + 6.00
        ],
        'items_data' => [
            'taxjar-tshirt' => [
                'tax_amount' => 6.00,
                'tax_percent' => 20.0,
                'price' => 29.99,
                'price_incl_tax' => 29.99 + 6.00,
                'row_total' => 29.99,
                'row_total_incl_tax' => 29.99 + 6.00,
                'applied_taxes' => [
                    [
                        'id' => 0,
                        'item_id' => null,
                        'associated_item_id' => null,
                        'item_type' => 'product',
                        'amount' => 6.00,
                        'base_amount' => 6.00,
                        'percent' => 20.0,
                        'rates' => [
                            [
                                'code' => 0,
                                'title' => 'VAT',
                                'percent' => 20.0
                            ]
                        ]
                    ]
                ]
            ],
        ],
    ],
];
