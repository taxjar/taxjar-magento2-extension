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

    public function __construct()
    {
        parent::__construct();

        $this->objectManager = Bootstrap::getObjectManager();
    }
}
