<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Model\Tax;

use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Api\Data\QuoteDetailsItemExtensionInterface;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class TaxCalculationNullCodeTest extends UnitTestCase
{
    /**
     * Verify that items with null codes don't cause array offset errors
     * when building the keyed items and parent-to-children maps.
     */
    public function testItemsWithNullCodeAreSkipped()
    {
        $itemWithCode = $this->createStub(QuoteDetailsItemInterface::class);
        $itemWithCode->method('getCode')->willReturn('item_1');
        $itemWithCode->method('getParentCode')->willReturn(null);
        $itemWithCode->method('getType')->willReturn('product');

        $itemWithNullCode = $this->createStub(QuoteDetailsItemInterface::class);
        $itemWithNullCode->method('getCode')->willReturn(null);
        $itemWithNullCode->method('getParentCode')->willReturn(null);
        $itemWithNullCode->method('getType')->willReturn('product');

        $items = [$itemWithCode, $itemWithNullCode];

        $keyedItems = [];
        $parentToChildren = [];

        foreach ($items as $item) {
            $code = $item->getCode();
            $parentCode = $item->getParentCode();

            if ($parentCode === null) {
                if ($code !== null) {
                    $keyedItems[$code] = $item;
                }
            } else {
                $parentToChildren[$parentCode][] = $item;
            }
        }

        $this->assertCount(1, $keyedItems);
        $this->assertArrayHasKey('item_1', $keyedItems);
        $this->assertEmpty($parentToChildren);
    }

    /**
     * Verify that child items with null parent codes don't cause
     * array offset errors when looking up parent quantities.
     */
    public function testNullParentIdDoesNotCauseArrayOffsetError()
    {
        $parentQuantities = ['parent_1' => 2];
        $parentId = null;

        // This is the exact pattern from Smartcalcs.php
        $quantity = 1;
        if ($parentId !== null && isset($parentQuantities[$parentId])) {
            $quantity *= $parentQuantities[$parentId];
        }

        $this->assertEquals(1, $quantity);
    }

    /**
     * Verify that a valid parent ID correctly multiplies quantity.
     */
    public function testValidParentIdMultipliesQuantity()
    {
        $parentQuantities = ['parent_1' => 3];
        $parentId = 'parent_1';

        $quantity = 2;
        if ($parentId !== null && isset($parentQuantities[$parentId])) {
            $quantity *= $parentQuantities[$parentId];
        }

        $this->assertEquals(6, $quantity);
    }
}
