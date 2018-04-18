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

$taxCalculationData['configurable_product'] = [
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
            'firstname' => 'Maurice',
            'lastname' => 'Moss',
            'street' => '123 Westcreek Pkwy',
            'city' => 'Westlake Village',
            'region_id' => SetupUtil::REGION_CA,
            'postcode' => '91362',
            'country_id' => SetupUtil::COUNTRY_US,
            'telephone' => '011-899-9881'
        ],
        'shipping_address' => [
            'firstname' => 'Maurice',
            'lastname' => 'Moss',
            'street' => '123 Westcreek Pkwy',
            'city' => 'Westlake Village',
            'region_id' => SetupUtil::REGION_CA,
            'postcode' => '91362',
            'country_id' => SetupUtil::COUNTRY_US,
            'telephone' => '011-899-9881'
        ],
        'items' => [
            [
                'type' => SetupUtil::PRODUCT_TYPE_CONFIGURABLE,
                'sku' => 'taxjar-configurable-tshirt',
                'price' => 19.99,
                'qty' => 3,
                'options' => [
                    'value' => [
                        'option_0' => ['Option 1'],
                        'option_1' => ['Option 2'],
                        'option_2' => ['Option 3']
                    ],
                    'color' => [
                        'option_0' => 'Green',
                        'option_1' => 'Black',
                        'option_2' => 'Grey'
                    ]
                ]
            ],
        ],
    ],
    'expected_results' => [
        'address_data' => [
            'tax_amount' => 5.7,
            'subtotal' => 59.97,
            'subtotal_incl_tax' => 59.97 + 5.7,
            'grand_total' => 59.97 + 5.7
        ],
        'items_data' => [
            'taxjar-configurable-tshirt' => [
                'tax_amount' => 5.7,
                'tax_percent' => 9.5,
                'price' => 19.99,
                'price_incl_tax' => 19.99 + (5.7 / 3),
                'row_total' => 59.97,
                'row_total_incl_tax' => 59.97 + 5.7,
                'applied_taxes' => [
                    [
                        'id' => 1,
                        'item_id' => null,
                        'associated_item_id' => null,
                        'item_type' => 'product',
                        'amount' => 3.75,
                        'base_amount' => 3.75,
                        'percent' => 6.25,
                        'rates' => [
                            [
                                'code' => 1,
                                'title' => 'State Tax',
                                'percent' => 6.25
                            ]
                        ]
                    ],
                    [
                        'id' => 2,
                        'item_id' => null,
                        'associated_item_id' => null,
                        'item_type' => 'product',
                        'amount' => 0.6,
                        'base_amount' => 0.6,
                        'percent' => 1.0,
                        'rates' => [
                            [
                                'code' => 2,
                                'title' => 'County Tax',
                                'percent' => 1.0
                            ]
                        ]
                    ],
                    [
                        'id' => 4,
                        'item_id' => null,
                        'associated_item_id' => null,
                        'item_type' => 'product',
                        'amount' => 1.35,
                        'base_amount' => 1.35,
                        'percent' => 2.25,
                        'rates' => [
                            [
                                'code' => 4,
                                'title' => 'Special District Tax',
                                'percent' => 2.25
                            ]
                        ]
                    ]
                ]
            ],
            'taxjar-configurable-tshirt-option-0' => [
                'tax_amount' => 0,
                'tax_percent' => 0,
                'price' => 0,
                'price_incl_tax' => 0,
                'row_total' => 0,
                'row_total_incl_tax' => 0
            ]
        ],
    ],
];
