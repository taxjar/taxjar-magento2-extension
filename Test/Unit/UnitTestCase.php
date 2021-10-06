<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Taxjar\SalesTax\Test\BaseTestCase;

class UnitTestCase extends BaseTestCase
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    public function __construct()
    {
        parent::__construct();

        $this->objectManager = new ObjectManager($this);
    }
}
