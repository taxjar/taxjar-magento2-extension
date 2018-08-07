<?php
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
