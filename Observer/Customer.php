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

namespace Taxjar\SalesTax\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class Customer implements ObserverInterface
{
    /**
     * @var \Taxjar\SalesTax\Model\Client
     */
    protected $client;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customer;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Taxjar\SalesTax\Model\Logger
     */
    protected $logger;

    /**
     * @var \Magento\Directory\Model\Region
     */
    protected $region;

    /**
     * @param \Taxjar\SalesTax\Model\ClientFactory $clientFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \\Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     *
     */
    public function __construct(
        \Taxjar\SalesTax\Model\ClientFactory $clientFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Taxjar\SalesTax\Model\Logger $logger,
        \Magento\Directory\Model\RegionFactory $regionFactory
    ) {
        $this->client = $clientFactory->create();
        $this->client->showResponseErrors(true);
        $this->customer = $customerFactory->create();
        $this->date = $date;
        $this->logger = $logger->setFilename(TaxjarConfig::TAXJAR_CUSTOMER_LOG);
        $this->region = $regionFactory->create();
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Framework\Event $event */
        $event = $observer->getEvent();

        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $this->customer->load($observer->getCustomer()->getId());

        $customerAddress = $customer->getDefaultShippingAddress();
        if (!$customerAddress) {
            $customerAddress = $customer->getAddresses();
            $customerAddress = reset($customerAddress);
        }

        $data = [
            'customer_id' => $customer->getId(),
            'exemption_type' => $customer->getTjExemptionType(),
            'name' => $customer->getFirstname() . ' ' . $customer->getLastname()
        ];

        $regions = $customer->getTjRegions();
        if (!empty($regions)) {
            $r = [];
            foreach (explode(',', $regions) as $region) {
                $r[] = ['country' => 'US', 'state' => $this->region->load($region)->getCode()];
            }
            $data['exempt_regions'] = $r;
        }

        if ($customerAddress) {
            $data += [
                'country' => $customerAddress->getCountry(),
                'state' => $customerAddress->getRegionCode(),
                'zip' => $customerAddress->getPostcode(),
                'city' => $customerAddress->getCity(),
                'street' => $customerAddress->getStreetFull()
            ];
        }

        try {
            if ($event->getName() == 'adminhtml_customer_save_after') {
                if (empty($customer->getTjLastSync())) {
//                    $this->logger->log('POST customerId ' . $customer->getId(), 'customers');
                    $this->client->postResource('customers', $data);  //create a new customer
                } else {
//                    $this->logger->log('PUT customerId ' . $customer->getId(), 'customers');
                    $this->client->putResource('customers', $customer->getId(), $data);  //update existing customer
                }
                $customer->setData('tj_last_sync', $this->date->timestamp());
                $customer->save();
            }

            if ($event->getName() == 'customer_delete_before') {
//                $this->logger->log('DELETE customerId ' . $customer->getId(), 'customers');
                $this->client->deleteResource('customers', $customer->getId());  //delete customer
            }
        } catch (\Exception $exception) {
            $msg = $exception->getMessage();
            $this->logger->log('FAILED customerId ' . $customer->getId() . "" . $msg, 'customers');
        }
    }
}