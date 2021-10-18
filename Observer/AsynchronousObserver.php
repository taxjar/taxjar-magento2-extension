<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Observer;

use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\AsynchronousOperations\Model\MassSchedule;
use Magento\Authorization\Model\UserContextInterface;
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
     * @var MassSchedule
     */
    protected $massSchedule;

    /**
     * @param MassSchedule $massSchedule
     */
    public function __construct(
        MassSchedule $massSchedule
    ) {
        $this->massSchedule = $massSchedule;
    }

    public function execute(Observer $observer): void
    {
        $data = $observer->getData();
        $this->schedule(...$data);
    }

    protected function getMassSchedule(): MassSchedule
    {
        return $this->massSchedule;
    }
}
