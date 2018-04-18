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

$taxCalculationData['cad_with_usd_base_currency'] = [
    'config_data' => [
        SetupUtil::CONFIG_OVERRIDES => array_merge($taxjarCredentials, [
            'shipping/origin/street_line1' => '1661 Duranleau St',
            'shipping/origin/city' => 'Vancouver',
            'shipping/origin/region_id' => 67,
            'shipping/origin/country_id' => 'CA',
            'shipping/origin/postcode' => 'V6H 3S3'
        ]),
        SetupUtil::NEXUS_OVERRIDES => [
            [
                'street' => '1661 Duranleau St',
                'city' => 'Vancouver',
                'country_id' => 'CA',
                'region' => 'British Columbia',
                'region_id' => 67,
                'region_code' => 'BC',
                'postcode' => 'V6H 3S3'
            ]
        ]
    ],
    'quote_data' => [
        'billing_address' => [
            'firstname' => 'Maurice',
            'lastname' => 'Moss',
            'street' => '301 Front St W',
            'city' => 'Toronto',
            'region_id' => 74,
            'postcode' => 'M5V 2T6',
            'country_id' => 'CA',
            'telephone' => '999-999-9999'
        ],
        'shipping_address' => [
            'firstname' => 'Maurice',
            'lastname' => 'Moss',
            'street' => '301 Front St W',
            'city' => 'Toronto',
            'region_id' => 74,
            'postcode' => 'M5V 2T6',
            'country_id' => 'CA',
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
        'currency' => [
            'base_code' => 'USD',
            'active_code' => 'CAD',
            'conversion_rate' => 1.28
        ]
    ],
    'expected_results' => [
        'address_data' => [
            'tax_amount' => (3.90 * 1.28),
            'base_tax_amount' => 3.90,
            'subtotal' => (29.99 * 1.28),
            'base_subtotal' => 29.99,
            'subtotal_incl_tax' => (29.99 * 1.28) + (3.90 * 1.28),
            'base_subtotal_incl_tax' => 29.99 + 3.90,
            'grand_total' => (29.99 * 1.28) + (3.90 * 1.28),
            'base_grand_total' => 29.99 + 3.90
        ],
        'items_data' => [
            'taxjar-tshirt' => [
                'tax_amount' => (3.90 * 1.28),
                'tax_percent' => 13.0,
                'price' => 29.99,
                'price_incl_tax' => (29.99 * 1.28) + (3.90 * 1.28),
                'row_total' => (29.99 * 1.28),
                'base_row_total' => 29.99,
                'row_total_incl_tax' => (29.99 * 1.28) + (3.90 * 1.28),
                'base_row_total_incl_tax' => 29.99 + 3.90,
                'applied_taxes' => [
                    [
                        'id' => 5,
                        'item_id' => '2',
                        'associated_item_id' => null,
                        'item_type' => 'product',
                        'amount' => 1.5,
                        'base_amount' => 1.5, // Base amount remains the same
                        'percent' => 5.0,
                        'rates' => [
                            [
                                'code' => 5,
                                'title' => 'GST',
                                'percent' => 5.0
                            ]
                        ]
                    ],
                    [
                        'id' => 6,
                        'item_id' => '2',
                        'associated_item_id' => null,
                        'item_type' => 'product',
                        'amount' => 2.4,
                        'base_amount' => 2.4, // Base amount remains the same
                        'percent' => 8.0,
                        'rates' => [
                            [
                                'code' => 6,
                                'title' => 'PST',
                                'percent' => 8.0
                            ]
                        ]
                    ]
                ]
            ],
        ],
    ],
];
