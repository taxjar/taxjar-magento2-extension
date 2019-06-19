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
use Magento\Framework\Exception\LocalizedException;

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
        $customer = $this->customer->load($observer->getCustomerDataObject()->getId());

        /** @var \Magento\Customer\Model\Address $customerAddress */
        $customerAddress = $customer->getDefaultShippingAddress();

        if (!$customerAddress) {
            //TODO: confirm correct customer addr returned
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

        if ($event->getName() == 'customer_save_after_data_object' && empty($customer->getTjLastSync())) {
            try {
                $response = $this->client->postResource('customers', $data);  //create a new customer
            } catch (LocalizedException $e) {
                $message = json_decode($e->getMessage());

                if ($message->status = 422) {  //unprocessable
                    try {
                        $response = $this->client->putResource('customers', $customer->getId(), $data);
                    } catch (LocalizedException $e) {
                        $this->logger->log('ERROR PUT customerId ' . $customer->getId() . "" . $e->getMessage(), 'customers');
                    }
                }
            }
        } elseif ($event->getName() == 'customer_save_after_data_object') {
            try {
                //update existing customer
                $response = $this->client->putResource('customers', $customer->getId(), $data);
            } catch (LocalizedException $e) {
                $this->logger->log('ERROR PUT customerId ' . $customer->getId() . "" . $e->getMessage(), 'customers');
            }
        }

        if ($event->getName() == 'customer_delete_before') {
            try {
                $response = $this->client->deleteResource('customers', $customer->getId());  //delete customer
            } catch (LocalizedException $e) {
                $this->logger->log('DELETE customerId ' . $customer->getId() . "" . $e->getMessage(), 'customers');
            }
        } else {
            $customer->setData('tj_last_sync', $this->date->timestamp());
            $customer->save();
        }

        if (isset($response)) {
            $this->logger->log('SUCCESS customerId ' . $customer->getId(), 'customers');
        }
    }
}
