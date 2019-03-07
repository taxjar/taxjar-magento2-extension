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

namespace Taxjar\SalesTax\Model;

class SuggestedAddress extends \Magento\Quote\Model\Quote\Address implements \Taxjar\SalesTax\Api\SuggestedAddressInterface
{
    public function getSuggestedAddress()
    {
        return $this->_get(self::KEY_SUGGESTED_ADDRESS);
    }

    public function setSuggestedAddress($suggestedAddress)
    {
        return $this->setData(self::KEY_SUGGESTED_ADDRESS, $suggestedAddress);
    }
}
