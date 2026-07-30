<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Taxjar\SalesTax\Test\BaseTestCase;

class UnitTestCase extends BaseTestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = new ObjectManager($this);
    }
}
