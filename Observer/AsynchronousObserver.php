<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Observer;

use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Taxjar\SalesTax\Observer\Concerns\SchedulesOperations;

abstract class AsynchronousObserver implements ObserverInterface
{
    use SchedulesOperations;

    /**
     * @var IdentityService
     */
    protected $identityService;

    /**
     * @var OperationInterfaceFactory
     */
    protected $operationInterfaceFactory;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var BulkManagementInterface
     */
    protected $bulkManagement;

    /**
     * @param IdentityService $identityService
     * @param OperationInterfaceFactory $operationInterfaceFactory
     * @param SerializerInterface $serializer
     * @param BulkManagementInterface $bulkManagement
     */
    public function __construct(
        IdentityService $identityService,
        OperationInterfaceFactory $operationInterfaceFactory,
        SerializerInterface $serializer,
        BulkManagementInterface $bulkManagement
    ) {
        $this->identityService = $identityService;
        $this->operationInterfaceFactory = $operationInterfaceFactory;
        $this->serializer = $serializer;
        $this->bulkManagement = $bulkManagement;
    }

    public function execute(Observer $observer): void
    {
        $data = $observer->getData();
        $this->schedule(...$data);
    }

    protected function getIdentityService(): IdentityService
    {
        return $this->identityService;
    }

    protected function getOperationFactory(): OperationInterfaceFactory
    {
        return $this->operationInterfaceFactory;
    }

    protected function getSerializerInterface(): SerializerInterface
    {
        return $this->serializer;
    }

    protected function getBulkManagementInterface(): BulkManagementInterface
    {
        return $this->bulkManagement;
    }
}
