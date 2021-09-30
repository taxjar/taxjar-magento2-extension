<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Observer\Concerns;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\Bulk\OperationInterface as BulkOperationInterface;
use Magento\Framework\DataObject\IdentityService;
use Magento\Framework\Serialize\SerializerInterface;

trait SchedulesOperations
{
    public function schedule(array $data, string $topic, string $description, $userId = null): self
    {
        $uuid = $this->getIdentityService()->generateId();

        foreach ($data as $datum) {
            $operations[] = $this->makeOperation($uuid, $topic, $datum);
        }

        if (! empty($operations)) {
            $this->getBulkManagementInterface()
                ->scheduleBulk($uuid, $operations, $description, $userId);
        }

        return $this;
    }

    protected function makeOperation(string $bulkUuid, string $topic, array $payload): OperationInterface
    {
        $operation = $this->getOperationFactory()->create();
        $operation->setBulkUuid($bulkUuid);
        $operation->setTopicName($topic);
        $operation->setStatus(BulkOperationInterface::STATUS_TYPE_OPEN);
        $operation->setSerializedData($this->getSerializerInterface()->serialize($payload));
        return $operation;
    }

    abstract protected function getIdentityService(): IdentityService;

    abstract protected function getOperationFactory(): OperationInterfaceFactory;

    abstract protected function getSerializerInterface(): SerializerInterface;

    abstract protected function getBulkManagementInterface(): BulkManagementInterface;
}
