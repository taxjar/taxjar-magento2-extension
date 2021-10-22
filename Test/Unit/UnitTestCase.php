<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Taxjar\SalesTax\Test\BaseTestCase;

class UnitTestCase extends BaseTestCase
{
    /**
     * @param int|string $dataName
     *
     * @internal This method is not covered by the backward compatibility promise for PHPUnit
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->objectManager = new ObjectManager($this);
    }
}
