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

// @codingStandardsIgnoreStart

$taxjarCredentials = require __DIR__ . '/../credentials.php';

/**
 * Global array that holds test scenarios data
 *
 * @var array
 */
$taxCalculationData = [];

require_once __DIR__ . '/scenarios/countries/au_calculation.php';
require_once __DIR__ . '/scenarios/countries/ca_calculation.php';
require_once __DIR__ . '/scenarios/countries/eu_calculation.php';
require_once __DIR__ . '/scenarios/currencies/aud_with_usd_base_currency.php';
require_once __DIR__ . '/scenarios/currencies/cad_with_usd_base_currency.php';
require_once __DIR__ . '/scenarios/currencies/eur_with_usd_base_currency.php';
require_once __DIR__ . '/scenarios/discounts/shopping_cart_rule_percent.php';
require_once __DIR__ . '/scenarios/discounts/shopping_cart_rule_fixed.php';
require_once __DIR__ . '/scenarios/discounts/shopping_cart_rule_fixed_shipping.php';
require_once __DIR__ . '/scenarios/exemptions/none_tax_class_exemption.php';
require_once __DIR__ . '/scenarios/exemptions/us_ca_grocery_exemption.php';
require_once __DIR__ . '/scenarios/exemptions/us_ny_clothing_exemption.php';
require_once __DIR__ . '/scenarios/fallbacks/invalid_token_fallback.php';
require_once __DIR__ . '/scenarios/fallbacks/native_intl_rate_fallback.php';
require_once __DIR__ . '/scenarios/fallbacks/no_nexus_fallback.php';
require_once __DIR__ . '/scenarios/fallbacks/smartcalcs_disabled_fallback.php';
require_once __DIR__ . '/scenarios/products/configurable_product.php';
require_once __DIR__ . '/scenarios/products/simple_product.php';
require_once __DIR__ . '/scenarios/line_item_rounding_calculation.php';
require_once __DIR__ . '/scenarios/shipping_tax_calculation.php';

// @codingStandardsIgnoreEnd
