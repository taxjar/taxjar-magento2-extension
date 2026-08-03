<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Integration;

use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryReservationsApi\Model\CleanupReservationsInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Taxjar\SalesTax\Test\BaseTestCase;

class IntegrationTestCase extends BaseTestCase
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var CleanupReservationsInterface
     */
    protected $cleanupReservations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
    }
}
