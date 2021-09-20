<?php

namespace Taxjar\SalesTax\Test\Mocks;

use Magento\Framework\Exception\LocalizedException;
use Taxjar\SalesTax\Api\Client\ClientInterface;
use Taxjar\SalesTax\Helper\Data;
use Taxjar\SalesTax\Model\BackupRateOriginAddress;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class ClientMock implements ClientInterface
{
    /**
     * @var Data
     */
    private $tjHelper;
    /**
     * @var TaxjarConfig
     */
    private $taxjarConfig;
    /**
     * @var BackupRateOriginAddress
     */
    private $backupRateOriginAddress;
    /**
     * @var string
     */
    private $apiKey;
    /**
     * @var mixed
     */
    public $mockResponse;

    /**
     * @param Data $tjHelper
     * @param TaxjarConfig $taxjarConfig
     * @param BackupRateOriginAddress $backupRateOriginAddress
     */
    public function __construct(
        Data $tjHelper,
        TaxjarConfig $taxjarConfig,
        BackupRateOriginAddress $backupRateOriginAddress
    ) {
        $this->tjHelper = $tjHelper;
        $this->taxjarConfig = $taxjarConfig;
        $this->backupRateOriginAddress = $backupRateOriginAddress;
        $this->apiKey = 'test-api-key';
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
        return $this->mockResponse;
//        $client = $this->getClient($this->_getApiUrl($resource));
//        return $this->_getRequest($client, $errors);
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
//        $client->setConfig([
//            'useragent' => $this->tjHelper->getUserAgent(),
//            'referer' => $this->tjHelper->getStoreUrl()
//        ]);
//        $client->setHeaders([
//            'Authorization' => 'Bearer ' . $this->apiKey,
//            'x-api-version' => TaxJarConfig::TAXJAR_X_API_VERSION
//        ]);

        return $client;
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
                $apiUrl .= '/plugins/magento/configuration/' . $this->backupRateOriginAddress->getShippingRegionCode();
                break;
            case 'rates':
                $apiUrl .= '/plugins/magento/rates/' . $this->backupRateOriginAddress->getShippingRegionCode() . '/' . $this->backupRateOriginAddress->getShippingZipCode();
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

    private function _getRequest($client, $errors = [])
    {
        return $this->getMockResponse();
    }

    private function getMockResponse()
    {
        return $this->mockResponse ?? ['error' => 'No mock resopnse set!'];
    }

    public function setMockResponse($value): ClientMock
    {
        $this->mockResponse = $value;

        return $this;
    }
}
