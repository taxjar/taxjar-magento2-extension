<?php

namespace Taxjar\SalesTax\Plugin\Customer\Model\ResourceModel;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Taxjar\SalesTax\Model\Client;
use Taxjar\SalesTax\Model\ClientFactory;

class CustomerRepository
{
    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @param ClientFactory $clientFactory
     */
    public function __construct(ClientFactory $clientFactory, ?LoggerInterface $logger = null)
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
            if ($this->logger) {
                $this->logger->error($message);
            }
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
}
