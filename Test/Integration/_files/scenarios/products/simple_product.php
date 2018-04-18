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

$taxCalculationData['simple_product'] = [
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
                'qty' => 1
            ]
        ],
    ],
    'expected_results' => [
        'address_data' => [
            'tax_amount' => 2.85,
            'subtotal' => 29.99,
            'subtotal_incl_tax' => 29.99 + 2.85,
            'grand_total' => 29.99 + 2.85
        ],
        'items_data' => [
            'taxjar-tshirt' => [
                'tax_amount' => 2.85,
                'tax_percent' => 9.5,
                'price' => 29.99,
                'price_incl_tax' => 29.99 + 2.85,
                'row_total' => 29.99,
                'row_total_incl_tax' => 29.99 + 2.85,
                'applied_taxes' => [
                    [
                        'id' => 1,
                        'item_id' => null,
                        'associated_item_id' => null,
                        'item_type' => 'product',
                        'amount' => 1.87,
                        'base_amount' => 1.87,
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
                        'amount' => 0.3,
                        'base_amount' => 0.3,
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
                        'amount' => 0.67,
                        'base_amount' => 0.67,
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
        ],
    ],
];

$taxCalculationData['simple_product_multiple'] = [
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
                'qty' => 1
            ],
            [
                'type' => \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
                'sku' => 'taxjar-trucker-hat',
                'price' => 9.99,
                'qty' => 1
            ],
            [
                'type' => \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
                'sku' => 'taxjar-hoodie',
                'price' => 59.99,
                'qty' => 1
            ]
        ],
    ],
    'expected_results' => [
        'address_data' => [
            'tax_amount' => 9.5,
            'subtotal' => 99.97,
            'subtotal_incl_tax' => 99.97 + 9.5,
            'grand_total' => 99.97 + 9.5
        ],
        'items_data' => [
            'taxjar-tshirt' => [
                'tax_amount' => 2.85,
                'tax_percent' => 9.5,
                'price' => 29.99,
                'price_incl_tax' => 29.99 + 2.85,
                'row_total' => 29.99,
                'row_total_incl_tax' => 29.99 + 2.85,
                'applied_taxes' => [
                    [
                        'id' => 1,
                        'item_id' => null,
                        'associated_item_id' => null,
                        'item_type' => 'product',
                        'amount' => 1.87,
                        'base_amount' => 1.87,
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
                        'amount' => 0.3,
                        'base_amount' => 0.3,
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
                        'amount' => 0.67,
                        'base_amount' => 0.67,
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
            'taxjar-trucker-hat' => [
                'tax_amount' => 0.95,
                'tax_percent' => 9.5,
                'price' => 9.99,
                'price_incl_tax' => 9.99 + 0.95,
                'row_total' => 9.99,
                'row_total_incl_tax' => 9.99 + 0.95,
                'applied_taxes' => [
                    [
                        'id' => 1,
                        'item_id' => null,
                        'associated_item_id' => null,
                        'item_type' => 'product',
                        'amount' => 0.62,
                        'base_amount' => 0.62,
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
                        'amount' => 0.10,
                        'base_amount' => 0.10,
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
                        'amount' => 0.22,
                        'base_amount' => 0.22,
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
            'taxjar-hoodie' => [
                'tax_amount' => 5.7,
                'tax_percent' => 9.5,
                'price' => 59.99,
                'price_incl_tax' => 59.99 + 5.7,
                'row_total' => 59.99,
                'row_total_incl_tax' => 59.99 + 5.7,
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
        ],
    ],
];
