<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Observer\Concerns;

use Magento\AsynchronousOperations\Model\MassSchedule;
use Magento\Framework\Exception\BulkException;
use Magento\Framework\Exception\LocalizedException;

trait SchedulesOperations
{
    /**
     * @throws BulkException|LocalizedException
     */
    public function schedule(array $data, string $topic): self
    {
        $result = $this->getMassSchedule()->publishMass($topic, $data);

        if (! $result) {
            throw new LocalizedException(
                __('Something went wrong while processing the request.')
            );
        }

        return $this;
    }

    abstract protected function getMassSchedule(): MassSchedule;
}
