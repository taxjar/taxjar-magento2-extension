<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test;

use PHPUnit\Framework\TestCase;

class BaseTestCase extends TestCase
{
    public function todo(string $message = '')
    {
        $this->markTestSkipped($message);
    }
}
