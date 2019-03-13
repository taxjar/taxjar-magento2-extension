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

namespace Taxjar\SalesTax\Api;

interface AddressValidationInterface
{
    /**
     * @api
     *
     * @param string $street0
     * @param string $street1
     * @param string $city
     * @param string $region
     * @param string $country
     * @param string $postcode
     * @return mixed
     */
    public function validateAddress($street0 = null, $street1 = null, $city = null, $region = null, $country = null, $postcode = null);
}
