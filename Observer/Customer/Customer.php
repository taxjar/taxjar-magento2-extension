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

namespace Taxjar\SalesTax\Observer\Customer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

abstract class Customer implements ObserverInterface
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
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Taxjar\SalesTax\Model\ClientFactory $clientFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \\Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Taxjar\SalesTax\Model\Logger $logger
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     */
    public function __construct(
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Taxjar\SalesTax\Model\ClientFactory $clientFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Taxjar\SalesTax\Model\Logger $logger,
        \Magento\Directory\Model\RegionFactory $regionFactory
    ) {
        $this->addressRepository = $addressRepository;
        $this->client = $clientFactory->create();
        $this->client->showResponseErrors(true);
        $this->customerRepository = $customerRepository;
        $this->date = $date;
        $this->logger = $logger->setFilename(TaxjarConfig::TAXJAR_CUSTOMER_LOG);
        $this->region = $regionFactory->create();
    }

    /**
     * @param string $lastSync
     * @param array $data
     * @param int $customerId
     * @return array|null
     * @throws LocalizedException
     */
    protected function updateTaxjar($lastSync, $data)
    {
        $customerId = $data['customer_id'];
        $response = null;

        if (empty($lastSync)) {
            try {
                $response = $this->client->postResource('customers', $data);  //create a new customer
            } catch (LocalizedException $e) {
                $message = json_decode($e->getMessage());

                if (isset($message->status) && $message->status == 422) {  //unprocessable
                    try {
                        $this->logger->log('Could not update customer #' . $customerId . ', attempting to create instead',
                            'fallback');
                        $response = $this->client->putResource('customers', $customerId, $data);
                    } catch (LocalizedException $e) {
                        $this->logger->log('Could not update customer #' . $customerId . ": " . $e->getMessage(),
                            'error');
                    }
                } else {
                    $this->logger->log('Could not create customer #' . $customerId . ': ' . $e->getMessage(), 'error');
                }
            }
        } else {
            try {
                $response = $this->client->putResource('customers', $customerId, $data);
            } catch (LocalizedException $e) {
                $message = json_decode($e->getMessage());

                if (isset($message->status) && $message->status == 404) {  //unprocessable
                    try {
                        $this->logger->log('Could not create customer #' . $customerId . ', attempting to update instead',
                            'fallback');
                        $response = $this->client->postResource('customers', $data);
                    } catch (LocalizedException $e) {
                        $this->logger->log('Could not create customer #' . $customerId . ": " . $e->getMessage(),
                            'error');
                    }
                } else {
                    $this->logger->log('Could not update customer #' . $customerId . ': ' . $e->getMessage(), 'error');
                }
            }
        }

        return $response;
    }

    /**
     * @param string $regions
     * @return array
     */
    protected function getRegionsArray($regions)
    {
        $customerRegions = [];

        if (!empty($regions)) {
            foreach (explode(',', $regions) as $region) {
                $customerRegions[] = [
                    'country' => 'US',
                    'state' => $this->region->load($region)->getCode()
                ];
            }
        }

        return $customerRegions;
    }
}
