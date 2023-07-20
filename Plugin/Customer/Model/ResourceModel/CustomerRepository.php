<?php

namespace Taxjar\SalesTax\Plugin\Customer\Model\ResourceModel;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Taxjar\SalesTax\Model\Client;
use Taxjar\SalesTax\Model\ClientFactory;
use Taxjar\SalesTax\Model\Configuration;
use Taxjar\SalesTax\Model\Logger;

class CustomerRepository
{
    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var Logger $logger
     */
    protected $logger;

    /**
     * @param ClientFactory $clientFactory
     * @param Logger $logger
     */
    public function __construct(ClientFactory $clientFactory, Logger $logger)
    {
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
    }

    /**
     * Attempt to delete the corresponding TJ API resource prior to customer deletion
     *
     * @param CustomerRepositoryInterface $subject
     * @param int $customerId
     * @return int
     */
    public function beforeDeleteById(
        CustomerRepositoryInterface $subject,
        $customerId
    ) {
        try {
            // Although not annotated, client resource methods may throw exception
            $this->getClient()->deleteResource('customers', $customerId);
        } catch (LocalizedException $e) {
            $message = 'Could not delete customer #' . $customerId . ": " . $e->getMessage();
            $this->getLogger()->log($message, 'error');
        }

        return $customerId;
    }

    /**
     * Returns new TJ client configured to show error responses.
     *
     * @return Client
     */
    private function getClient(): Client
    {
        $client = $this->clientFactory->create();
        $client->showResponseErrors(true);
        return $client;
    }

    /**
     * Returns our Logger instance configured to write to the TaxJar customer log.
     *
     * @return Logger
     */
    private function getLogger(): Logger
    {
        $logger = $this->logger;
        $logger->setFilename(Configuration::TAXJAR_CUSTOMER_LOG);
        return $logger;
    }
}
