<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Stub;

use Magento\Sales\Api\Data\OrderExtensionInterface;

/**
 * Extends OrderExtensionInterface with TaxJar extension attribute methods.
 * Magento auto-generates these at runtime, but PHPUnit cannot mock them
 * on the base interface when the autoloader hasn't generated the class.
 */
interface OrderExtensionStubInterface extends OrderExtensionInterface
{
    public function getTjTaxCalculationStatus();

    public function setTjTaxCalculationStatus($status);

    public function getTjTaxCalculationMessage();

    public function setTjTaxCalculationMessage($message);
}
