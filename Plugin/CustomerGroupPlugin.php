<?php

namespace TaxJar\SalesTax\Plugin;

use Taxjar\SalesTax\Model\SyncCustomers;
use \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;

class CustomerGroupPlugin
{
    /**
     * @var \Taxjar\SalesTax\Model\SyncCustomers
     */
    protected $customerSync;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $customerFactory;

    /**
     * @var \Taxjar\SalesTax\Model\SyncCustomers
     */
    protected $originalTaxClassId;

    public function __construct(SyncCustomers $customerSync, CollectionFactory $customerFactory)
    {
        $this->customerSync = $customerSync;
        $this->customerFactory = $customerFactory;
    }

    public function beforeSave($customerGroupRepo, $customerGroup)
    {
        $customerGroup = $customerGroupRepo->getById($customerGroup->getId());
        $this->originalTaxClassId = $customerGroup->getTaxClassId();
    }

    public function afterSave($customerGroupRepo, $customerGroup)
    {
        if ($customerGroup->getTaxClassId() != $this->originalTaxClassId) {
            // On change we need to loop through all the affected customers and sync/resync them
            // First verify that it's a change we care about
            // Ned to do a join on group to grab the right class
            $customers = $this->customerFactory->create();
            $customers->addFieldToFilter('group_id', $customerGroup->getId());

            // This process can take awhile
            @set_time_limit(0);
            @ignore_user_abort(true);

            foreach ($customers as $customer) {
                $this->customerSync->updateCustomer($customer);
            }
        }
        
        return $customerGroup;
    }
}
