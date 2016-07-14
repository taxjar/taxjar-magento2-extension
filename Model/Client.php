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
 * @copyright  Copyright (c) 2016 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Model;

use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;

class Client
{
    const API_URL = 'https://api.taxjar.com/v2';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    
    /**
     * @var string
     */
    protected $storeZip;
    
    /**
     * @var string
     */
    protected $storeRegionCode;
    
    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $regionFactory;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param RegionFactory $regionFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RegionFactory $regionFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->regionFactory = $regionFactory;
        $this->storeZip = trim($this->scopeConfig->getValue('shipping/origin/postcode'));
        $region = $this->_getShippingRegion();
        $this->storeRegionCode = $region->getCode();
    }
    
    /**
     * Perform a GET request
     *
     * @param string $apiKey
     * @param string $url
     * @param array $errors
     * @return array
     */
    public function getResource($apiKey, $resource, $errors = [])
    {
        $client = $this->_getClient($apiKey, $this->_getApiUrl($resource));
        return $this->_getRequest($client, $errors);
    }

    /**
     * Perform a POST request
     *
     * @param string $apiKey
     * @param string $resource
     * @param array $data
     * @param array $errors
     * @return array
     */
    public function postResource($apiKey, $resource, $data, $errors = [])
    {
        $client = $this->_getClient($apiKey, $this->_getApiUrl($resource), \Zend_Http_Client::POST);
        $client->setRawData(json_encode($data), 'application/json');
        return $this->_getRequest($client, $errors);
    }
    
    /**
     * Perform a PUT request
     *
     * @param string $apiKey
     * @param string $resource
     * @param int $resourceId
     * @param array $data
     * @param array $errors
     * @return array
     */
    public function putResource($apiKey, $resource, $resourceId, $data, $errors = [])
    {
        $resourceUrl = $this->_getApiUrl($resource) . '/' . $resourceId;
        $client = $this->_getClient($apiKey, $resourceUrl, \Zend_Http_Client::PUT);
        $client->setRawData(json_encode($data), 'application/json');
        return $this->_getRequest($client, $errors);
    }
    
    /**
     * Perform a DELETE request
     *
     * @param string $apiKey
     * @param string $resource
     * @param int $resourceId
     * @param array $errors
     * @return array
     */
    public function deleteResource($apiKey, $resource, $resourceId, $errors = [])
    {
        $resourceUrl = $this->_getApiUrl($resource) . '/' . $resourceId;
        $client = $this->_getClient($apiKey, $resourceUrl, \Zend_Http_Client::DELETE);
        return $this->_getRequest($client, $errors);
    }

    /**
     * Get HTTP Client
     *
     * @param string $apiKey
     * @param string $url
     * @param string $method
     * @return ZendClient $response
     */
    private function _getClient($apiKey, $url, $method = \Zend_Http_Client::GET)
    {
        // @codingStandardsIgnoreStart
        $client = new \Zend_Http_Client($url);
        // @codingStandardsIgnoreEnd
        $client->setUri($url);
        $client->setMethod($method);
        $client->setHeaders('Authorization', 'Bearer ' . $apiKey);

        return $client;
    }
    
    /**
     * Get HTTP request
     *
     * @param Varien_Http_Client $client
     * @param array $errors
     * @return array
     */
    private function _getRequest($client, $errors = [])
    {
        try {
            $response = $client->request();
            
            if ($response->isSuccessful()) {
                $json = $response->getBody();
                return json_decode($json, true);
            } else {
                $this->_handleError($errors, $response->getStatus());
            }
        } catch (\Zend_Http_Client_Exception $e) {
            throw new LocalizedException(__('Could not connect to TaxJar.'));
        }
    }
    
    /**
     * Get SmartCalcs API URL
     *
     * @param string $type
     * @return string
     */
    private function _getApiUrl($resource)
    {
        $apiUrl = self::API_URL;

        switch ($resource) {
            case 'config':
                $apiUrl .= '/plugins/magento/configuration/' . $this->storeRegionCode;
                break;
            case 'rates':
                $apiUrl .= '/plugins/magento/rates/' . $this->storeRegionCode . '/' . $this->storeZip;
                break;
            case 'categories':
                $apiUrl .= '/categories';
                break;
            case 'nexus':
                $apiUrl .= '/nexus/addresses';
                break;
        }
        
        return $apiUrl;
    }
    
    /**
     * Get shipping region
     *
     * @return string
     */
    private function _getShippingRegion()
    {
        $region = $this->regionFactory->create();
        $regionId = $this->scopeConfig->getValue(
            \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_REGION_ID
        );
        $region->load($regionId);
        return $region;
    }
    
    /**
     * Handle API errors and throw exception
     *
     * @param array $customErrors
     * @param string $statusCode
     * @return string
     */
    private function _handleError($customErrors, $statusCode)
    {
        $errors = $this->_defaultErrors() + $customErrors;
        
        if (isset($errors[$statusCode])) {
            throw new LocalizedException($errors[$statusCode]);
        } else {
            throw new LocalizedException($errors['default']);
        }
    }
    
    /**
     * Return default API errors
     *
     * @return array
     */
    private function _defaultErrors()
    {
        // @codingStandardsIgnoreStart
        return [
            '401' => __('Your TaxJar API token is invalid. Please review your TaxJar account at https://app.taxjar.com.'),
            'default' => __('Could not connect to TaxJar.')
        ];
        // @codingStandardsIgnoreEnd
    }
}
