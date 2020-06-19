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

use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class Client
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var string
     */
    protected $apiKey;

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
     * @var bool
     */
    protected $showResponseErrors;

    /**
     * @var \Taxjar\SalesTax\Helper\Data
     */
    protected $tjHelper;

    /**
     * @var TaxjarConfig
     */
    protected $taxjarConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param RegionFactory $regionFactory
     * @param \Taxjar\SalesTax\Helper\Data $tjHelper
     * @param TaxjarConfig $taxjarConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RegionFactory $regionFactory,
        \Taxjar\SalesTax\Helper\Data $tjHelper,
        TaxjarConfig $taxjarConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->regionFactory = $regionFactory;
        $this->tjHelper = $tjHelper;
        $this->taxjarConfig = $taxjarConfig;
        $this->apiKey = $this->taxjarConfig->getApiKey();
        $this->storeZip = trim($this->scopeConfig->getValue('shipping/origin/postcode'));
        $region = $this->_getShippingRegion();
        $this->storeRegionCode = $region->getCode();
    }

    /**
     * Perform a GET request
     *
     * @param string $resource
     * @param array $errors
     * @return array
     */
    public function getResource($resource, $errors = [])
    {
        $client = $this->getClient($this->_getApiUrl($resource));
        return $this->_getRequest($client, $errors);
    }

    /**
     * Perform a POST request
     *
     * @param string $resource
     * @param array $data
     * @param array $errors
     * @return array
     */
    public function postResource($resource, $data, $errors = [])
    {
        $client = $this->getClient($this->_getApiUrl($resource), \Zend_Http_Client::POST);
        $client->setRawData(json_encode($data), 'application/json');
        return $this->_getRequest($client, $errors);
    }

    /**
     * Perform a PUT request
     *
     * @param string $resource
     * @param int $resourceId
     * @param array $data
     * @param array $errors
     * @return array
     */
    public function putResource($resource, $resourceId, $data, $errors = [])
    {
        $resourceUrl = $this->_getApiUrl($resource) . '/' . $resourceId;
        $client = $this->getClient($resourceUrl, \Zend_Http_Client::PUT);
        $client->setRawData(json_encode($data), 'application/json');
        return $this->_getRequest($client, $errors);
    }

    /**
     * Perform a DELETE request
     *
     * @param string $resource
     * @param int $resourceId
     * @param array $errors
     * @return array
     */
    public function deleteResource($resource, $resourceId, $errors = [])
    {
        $resourceUrl = $this->_getApiUrl($resource) . '/' . $resourceId;
        $client = $this->getClient($resourceUrl, \Zend_Http_Client::DELETE);
        return $this->_getRequest($client, $errors);
    }

    /**
     * Set API token for client requests
     *
     * @param string $key
     * @return void
     */
    public function setApiKey($key)
    {
        $this->apiKey = $key;
    }

    /**
     * @param bool $toggle
     * @return void
     */
    public function showResponseErrors($toggle)
    {
        $this->showResponseErrors = $toggle;
    }

    /**
     * Get HTTP Client
     *
     * @param string $url
     * @param string $method
     * @return \Zend_Http_Client $client
     */
    private function getClient($url, $method = \Zend_Http_Client::GET)
    {
        // @codingStandardsIgnoreStart
        $client = new \Zend_Http_Client($url, ['timeout' => 30]);
        // @codingStandardsIgnoreEnd
        $client->setUri($url);
        $client->setMethod($method);
        $client->setConfig([
            'useragent' => $this->tjHelper->getUserAgent(),
            'referer' => $this->tjHelper->getStoreUrl()
        ]);
        $client->setHeaders('Authorization', 'Bearer ' . $this->apiKey);

        return $client;
    }

    /**
     * Get HTTP request
     *
     * @param \Zend_Http_Client $client
     * @param array $errors
     * @return array
     * @throws LocalizedException
     */
    private function _getRequest($client, $errors = [])
    {
        try {
            $response = $client->request();

            if ($response->isSuccessful()) {
                $json = $response->getBody();
                return json_decode($json, true);
            } else {
                $this->_handleError($errors, $response);
            }
        } catch (\Zend_Http_Client_Exception $e) {
            throw new LocalizedException(__('Could not connect to TaxJar.'));
        }
    }

    /**
     * Get SmartCalcs API URL
     *
     * @param string $resource
     * @return string
     */
    private function _getApiUrl($resource)
    {
        $apiUrl = $this->taxjarConfig->getApiUrl();

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
            case 'orders':
                $apiUrl .= '/transactions/orders';
                break;
            case 'refunds':
                $apiUrl .= '/transactions/refunds';
                break;
            case 'addressValidation':
                $apiUrl .= '/addresses/validate';
                break;
            case 'verify':
                $apiUrl .= '/verify';
                break;
            case 'customers':
                $apiUrl .= '/customers';
                break;
            case 'taxes':
                $apiUrl .= '/magento/taxes';
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
     * @param \Zend_Http_Response $response
     * @return void
     * @throws LocalizedException
     */
    private function _handleError($customErrors, $response)
    {
        $errors = $this->_defaultErrors() + $customErrors;
        $statusCode = $response->getStatus();

        if ($this->showResponseErrors) {
            throw new LocalizedException(__($response->getBody()));
        }

        if (isset($errors[$statusCode])) {
            throw new LocalizedException($errors[$statusCode]);
        }

        throw new LocalizedException($errors['default']);
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
            '401' => __('Your TaxJar API token is invalid. Please review your TaxJar account at %1.', TaxjarConfig::TAXJAR_AUTH_URL),
            '403' => __('Your TaxJar trial or subscription may have expired. Please review your TaxJar account at %1.', TaxjarConfig::TAXJAR_AUTH_URL),
            'default' => __('Could not connect to TaxJar.')
        ];
        // @codingStandardsIgnoreEnd
    }
}
