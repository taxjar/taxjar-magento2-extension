<?php

namespace TaxJar\SalesTax\Plugin;

use Magento\Customer\Model\Customer;
use Taxjar\SalesTax\Model\SyncCustomers;

class CustomerPlugin
{
    /**
     * @var \Taxjar\SalesTax\Model\SyncCustomers
     */
    protected $_customerSync;

    public function __construct(SyncCustomers $customerSync)
    {
        $this->_customerSync = $customerSync;
    }

    public function afterAfterSave(Customer $customer)
    {
        // Will still need address plugin
        $this->_customerSync->updateCustomer($customer);
    }

    public function afterAfterDeleteCommit(Customer $customer)
    {
        $this->_customerSync->deleteCustomer($customer);
    }
}
