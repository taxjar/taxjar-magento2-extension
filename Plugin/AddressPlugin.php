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

namespace TaxJar\SalesTax\Plugin;

use Taxjar\SalesTax\Model\SyncCustomers;
use Magento\Customer\Model\Address;

class AddressPlugin
{
    /**
     * @var \Taxjar\SalesTax\Model\SyncCustomers
     */
    protected $_customerSync;

    public function __construct(SyncCustomers $customerSync)
    {
        $this->_customerSync = $customerSync;
    }

    public function afterAfterSave($address)
    {
        $customer = $address->getCustomer();
        $customer->cleanAllAddresses();
        $this->_customerSync->updateCustomer($customer);
    }

    public function afterAfterDeleteCommit($address)
    {
        $customer = $address->getCustomer();
        $customer->cleanAllAddresses();
        $this->_customerSync->updateCustomer($customer);
    }
}
