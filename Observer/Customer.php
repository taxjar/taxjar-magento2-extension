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
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var \Taxjar\SalesTax\Model\Client
     */
    protected $client;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

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
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Taxjar\SalesTax\Model\ClientFactory $clientFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \\Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     *
     */
    public function __construct(
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Taxjar\SalesTax\Model\ClientFactory $clientFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Taxjar\SalesTax\Model\Logger $logger,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Framework\Registry $registry
    ) {
        $this->addressRepository = $addressRepository;
        $this->client = $clientFactory->create();
        $this->client->showResponseErrors(true);
        $this->customerRepository = $customerRepository;
        $this->date = $date;
        $this->logger = $logger->setFilename(TaxjarConfig::TAXJAR_CUSTOMER_LOG);
        $this->region = $regionFactory->create();
        $this->registry = $registry;
    }

    /**
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $eventType = $this->checkEventType($observer->getEvent()->getName());
        $customerId = $this->getCustomerIdFromObserver($observer);

        if (!$customerId) {
            return;
        }

        if (!$this->registry->registry('taxjar_sync_customer_' . $eventType . '_' . $customerId)) {
            $this->registry->register('taxjar_sync_customer_' . $eventType . '_' . $customerId, true);
        } else {
            return;
        }

        /** @var \Magento\Customer\Model\Data\Customer $customer */
        $customer = $this->customerRepository->getById($customerId);
        $lastSync = $customer->getCustomAttribute('tj_last_sync');
        $regions = $customer->getCustomAttribute('tj_regions');
        $customerAddress = $customer->getAddresses();
        $customerAddress = reset($customerAddress);

        try {
            $shippingAddressId = $customer->getDefaultShipping();

            if (!empty($shippingAddressId)) {
                $customerAddress = $this->addressRepository->getById($shippingAddressId);
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            // noop
        }

        $data = [
            'customer_id' => $customer->getId(),
            'exemption_type' => $customer->getCustomAttribute('tj_exemption_type')->getValue(),
            'name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
            'street' => '',
            'city' => '',
            'state' => '',
            'zip' => ''
        ];

        if (!empty($regions)) {
            $customerRegions = [];

            foreach (explode(',', $regions->getValue()) as $region) {
                $customerRegions[] = [
                    'country' => 'US',
                    'state' => $this->region->load($region)->getCode()
                ];
            }

            $data['exempt_regions'] = $customerRegions;
        }

        if ($customerAddress) {
            $data = array_merge($data, [
                'country' => $customerAddress->getCountryId(),
                'state' => $customerAddress->getRegion()->getRegionCode(),
                'zip' => $customerAddress->getPostcode(),
                'city' => $customerAddress->getCity(),
                'street' => implode(", ", $customerAddress->getStreet())
            ]);
        }

        if ($eventType == 'update' && empty($lastSync)) {
            try {
                $response = $this->client->postResource('customers', $data);  //create a new customer
            } catch (LocalizedException $e) {
                $message = json_decode($e->getMessage());

                if (isset($message->status) && $message->status == 422) {  //unprocessable
                    try {
                        $this->logger->log('Could not update customer #' . $customer->getId() . ', attempting to create instead',
                            'fallback');
                        $response = $this->client->putResource('customers', $customer->getId(), $data);
                    } catch (LocalizedException $e) {
                        $this->logger->log('Could not update customer #' . $customer->getId() . ": " . $e->getMessage(),
                            'error');
                    }
                } else {
                    $this->logger->log('Could not create customer #' . $customer->getId() . ': ' . $e->getMessage(),
                        'error');
                }
            }
        } elseif ($eventType == 'update') {
            try {
                $response = $this->client->putResource('customers', $customer->getId(), $data);
            } catch (LocalizedException $e) {
                $message = json_decode($e->getMessage());

                if (isset($message->status) && $message->status == 404) {  //unprocessable
                    try {
                        $this->logger->log('Could not create customer #' . $customer->getId() . ', attempting to update instead',
                            'fallback');
                        $response = $this->client->postResource('customers', $data);
                    } catch (LocalizedException $e) {
                        $this->logger->log('Could not create customer #' . $customer->getId() . ": " . $e->getMessage(),
                            'error');
                    }
                } else {
                    $this->logger->log('Could not update customer #' . $customer->getId() . ': ' . $e->getMessage(),
                        'error');
                }
            }
        }

        if ($eventType == 'delete') {
            try {
                $response = $this->client->deleteResource('customers', $customer->getId());  //delete customer
            } catch (LocalizedException $e) {
                $this->logger->log('Could not delete customer #' . $customer->getId() . ": " . $e->getMessage(),
                    'error');
            }
        }

        if ($eventType !== 'delete' && isset($response)) {
            $this->logger->log('Successful API response: ' . json_encode($response), 'success');
            $customer->setCustomAttribute('tj_last_sync', $this->date->timestamp());
            $this->customerRepository->save($customer);
        }
    }

    /**
     * Determine if the customer is being created/updated or deleted
     *
     * @param string $eventName
     * @return bool|string
     */
    protected function checkEventType($eventName)
    {
        switch ($eventName) {
            case 'customer_save_after_data_object':
            case 'customer_address_save_after':
                return 'update';
            case 'customer_delete_before':
                return 'delete';
        }

        return false;
    }

    /**
     * Get the customerId from the observer
     *
     * @param Observer $observer
     * @return int|bool
     */
    protected function getCustomerIdFromObserver($observer)
    {
        if ($observer->getCustomerDataObject()) {
            return $observer->getCustomerDataObject()->getId();
        } elseif ($observer->getCustomerAddress()) {
            return $observer->getCustomerAddress()->getCustomerId();
        } elseif ($observer->getCustomer()) {
            return $observer->getCustomer()->getId();
        }

        return false;
    }
}
