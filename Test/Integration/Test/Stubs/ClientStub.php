<?php

namespace Taxjar\SalesTax\Test\Integration\Test\Stubs;

use Magento\Framework\Exception\LocalizedException;
use Taxjar\SalesTax\Api\Client\ClientInterface;
use Taxjar\SalesTax\Helper\Data;
use Taxjar\SalesTax\Model\BackupRateOriginAddress;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class ClientStub implements ClientInterface
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
    public $mockResponse = null;

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
     * @throws LocalizedException
     */
    public function getResource($resource, $errors = [])
    {
        return $this->getMockResponse();
    }

    /**
     * Perform a POST request
     *
     * @param string $resource
     * @param array $data
     * @param array $errors
     * @throws LocalizedException
     */
    public function postResource($resource, $data, $errors = [])
    {
        return $this->getMockResponse();
    }

    /**
     * Perform a PUT request
     *
     * @param string $resource
     * @param int $resourceId
     * @param array $data
     * @param array $errors
     * @throws LocalizedException
     */
    public function putResource($resource, $resourceId, $data, $errors = [])
    {
        return $this->getMockResponse();
    }

    /**
     * Perform a DELETE request
     *
     * @param string $resource
     * @param int $resourceId
     * @param array $errors
     * @throws LocalizedException
     */
    public function deleteResource($resource, $resourceId, $errors = [])
    {
        return $this->getMockResponse();
    }

    /**
     * Set API token for client requests
     *
     * @param string $key
     * @return void
     */
    public function setApiKey($key): void
    {
        $this->apiKey = $key;
    }

    /**
     * @param bool $toggle
     * @return void
     */
    public function showResponseErrors($toggle): void
    {
        $this->showResponseErrors = $toggle;
    }

    private function getMockResponse()
    {
        if (! $this->mockResponse) {
            throw new LocalizedException(__('No mock response was set!'));
        }

        return $this->mockResponse;
    }

    public function setMockResponse($value): ClientStub
    {
        $this->mockResponse = $value;

        return $this;
    }
}
