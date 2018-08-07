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

use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Taxjar\SalesTax\Model\Logger;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;


class SyncCustomers
{
    /**
     * @var \Magento\Framework\HTTP\ZendClientFactory;
     */
    protected $_clientFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Tax\Api\TaxClassRepositoryInterface
     */
    protected $_taxClassRepo;

    /**
     * @var \Taxjar\SalesTax\Model\Logger
     */
    protected $_logger;


    public function __construct(Logger $logger,
                                ZendClientFactory $clientFactory,
                                ScopeConfigInterface $scopeConfig,
                                TaxClassRepositoryInterface $taxClassRepo)
    {
        $this->_clientFactory = $clientFactory;
        $this->_logger = $logger->setFilename('customers.log');
        $this->_scopeConfig = $scopeConfig;
        $this->_taxClassRepo = $taxClassRepo;
    }

    public function updateCustomer($customer)
    {

        // Determine if taxjar touching is necessary
        // $customer = $this->_customer;
        // if no sync date or sync date is older than the current time run resync
        if((!$customer->getTjSalestaxSyncDate() or $customer->getTjSalestaxSyncDate() < time()) && !$customer->getTjProcessed()) {
            if (!$customer->getTjSalestaxSyncDate()) {
                $requestType = 'post';
                $url = TaxjarConfig::TAXJAR_API_URL . '/customers';
            } else {
                $requestType = 'put';
                $url = TaxjarConfig::TAXJAR_API_URL . '/customers/' . $customer->getId();
            }


            $taxClassId = $customer->getTaxClassId();
            $taxClass = $this->_taxClassRepo->get($taxClassId);

            $request = array(
                'customer_id'       => $customer->getId(),
                'exemption_type'    => $taxClass->getTjSalestaxExemptType(),
                'name'              => $customer->getName(),
            );

            $addressToUse = $customer->getPrimaryShippingAddress();

            if($addressToUse){
                $request['exempt_regions'] = array(
                    array(
                        'country'       => $addressToUse->getCountry(),
                        'state'         => $addressToUse->getRegionCode()
                    ));
                $request['country']     = $addressToUse->getCountry();
                $request['state']       = $addressToUse->getRegionCode();
                $request['zip']         = $addressToUse->getPostcode();
                $request['city']        = $addressToUse->getCity();
                $request['street']      = $addressToUse->getStreetFull();
            } else {
                // Hardcoding due to endpoint requirements
                $request['exempt_regions'] = array(
                    array(
                        'country'       => 'US',
                        'state'         => 'NY'
                    ));
            }

            // Prepare for api call
            $apiKey = preg_replace('/\s+/', '', $this->_scopeConfig->getValue(TaxjarConfig::TAXJAR_APIKEY,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ));
            $client = $this->_clientFactory->create();
            $client->setUri($url);
            $client->setHeaders('Authorization', 'Bearer ' . $apiKey);
            $client->setRawData(json_encode($request), 'application/json');
            $this->_logger->log('Syncing Customer: ' . json_encode($request), 'post');
            try {
                $response = $client->request(strtoupper($requestType));

                if (200 <= $response->getStatus() && 300 > $response->getStatus()) {
                    $this->_logger->log('Successful API response: ' . $response->getBody(), 'success');
                    $customer->setTjSalestaxSyncDate(time())->setTjProcessed(true)->save();
                } else {
                    $errorResponse = json_decode($response->getBody());
                    $this->_logger->log($errorResponse->status . ' ' . $errorResponse->error . ' - ' . $errorResponse->detail, 'error');
                }
            } catch (\Zend_Http_Client_Exception $e) {
                // Catch API timeouts and network issues
                $this->_logger->log('API timeout or network issue between your store and TaxJar, please try again later.', 'error');
            }
        }
    }

    public function deleteCustomer($customer)
    {
        $requestType = 'delete';
        $url = 'https://api.taxjar.com/v2/customers/' . $customer->getId();

        // Prepare for api call
        $apiKey = preg_replace('/\s+/', '', $this->_scopeConfig->getValue(TaxjarConfig::TAXJAR_APIKEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
        $client = $this->_clientFactory->create();
        $client->setUri($url);
        $client->setHeaders('Authorization', 'Bearer ' . $apiKey);

        $this->_logger->log('Deleting Customer: ' . $customer->getId(), $requestType);

        try {
            $response = $client->request(strtoupper($requestType));

            if (200 <= $response->getStatus() && 300 > $response->getStatus()) {
                $this->_logger->log('Successful API response: ' . $response->getBody(), 'success');
                // Since we are successful we want to set the last sync time to now and save the customer
            } else {
                $errorResponse = json_decode($response->getBody());
                $this->_logger->log($errorResponse->status . ' ' . $errorResponse->error . ' - ' . $errorResponse->detail, 'error');
            }

        } catch (Zend_Http_Client_Exception $e) {
            // Catch API timeouts and network issues
            $this->_logger->log('API timeout or network issue between your store and TaxJar, please try again later.', 'error');
        }
    }
}
