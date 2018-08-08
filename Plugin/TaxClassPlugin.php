<?php

namespace TaxJar\SalesTax\Plugin;

use Magento\Tax\Model\ClassModel;
use Taxjar\SalesTax\Model\SyncCustomers;
use \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;

class TaxClassPlugin
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
    protected $originalTaxClassData;

    public function __construct(SyncCustomers $customerSync, CollectionFactory $customerFactory)
    {
        $this->customerSync = $customerSync;
        $this->customerFactory = $customerFactory;

    }


    public function beforeSave($taxClassRepo, $taxClass)
    {
        $this->originalTaxClassData = $taxClassRepo->get($taxClass->getId())->getData();
    }

    public function afterSave($taxClassRepo, $id, $taxClass)
    {
        if ('CUSTOMER' == $taxClass->getClassType()) {

            // On change we need to loop through all the affected customers and sync/resync them
            // First verify that it's a change we care about

            // Ned to do a join on group to grab the right class
            $customers = $this->customerFactory->create();
            $customers->getSelect()->join(
                ['customer_group'=>$customers->getResource()->getTable('customer_group')],
                'e.group_id = customer_group.customer_group_id',
                ['tax_class_id'])
                ->where('tax_class_id = ? ', $taxClass->getId());

            if ('' == $taxClass->getTjSalestaxCode() && '' == $this->originalTaxClassData['tj_salestax_code']) {
                // * No -> No -- Don't sync
                return;
            } else if (('99999' == $taxClass->getTjSalestaxCode() && '99999' == $this->originalTaxClassData['tj_salestax_code']) && ($taxClass->getTjSalestaxExemptType() == $this->originalTaxClassData['tj_salestax_exempt_type'])) {
                // * Yes -> Yes -- Sync as exemption type
                return;
            }

            // This process can take awhile
            @set_time_limit(0);
            @ignore_user_abort(true);
            foreach ($customers as $customer) {
                $this->customerSync->updateCustomer($customer);
            }
        }
    }
}
