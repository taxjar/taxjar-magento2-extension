<?php
/**
 * Taxjar_SalesTax
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Taxjar
 * @package    Taxjar_SalesTax
 * @copyright  Copyright (c) 2022 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Model\Transaction\Order;

use Magento\Bundle\Model\Product\Price;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order\Item;

/**
 * Encapsulates logic relevant to parsing Order Item entities into TaxJar API order request line items.
 */
class LineItem extends \Taxjar\SalesTax\Model\Transaction\LineItem
{
    /**
     * @var LineItem
     */
    public LineItem $parentLineItem;

    /**
     * Return order item.
     *
     * @return Item
     */
    public function getItem(): Item
    {
        return $this->item;
    }

    /**
     * Return order item's parent item as a TaxJar LineItem.
     *
     * @return LineItem|null
     */
    public function getParentLineItem(): ?LineItem
    {
        if ($this->parentLineItem === null && $this->hasParent()) {
            $this->parentLineItem = ObjectManager::getInstance()->create(self::class, [
                'item' => $this->getItem()->getParentItem()
            ]);
        }

        return $this->parentLineItem;
    }

    /**
     * Return if line item should be included in API request body.
     *
     * @return bool
     */
    public function isSyncable(): bool
    {
        if ($this->_isChildProduct()) {
            if ($this->getParentLineItem()?->hasType('bundle')) {
                if ($this->getParentLineItem()?->_hasFixedPrice()) {
                    return false;  // Skip children of fixed price bundles
                }
            } else {
                return false;  // Skip children of configurable products
            }
        }

        if ($this->_isDynamicBundleParent()) {
            return false;  // Skip dynamic bundle parent item
        }

        return true;
    }

    /**
     * Return if item has parent id saved on record.
     *
     * @return bool
     */
    public function hasParent(): bool
    {
        return $this->getItem()->getParentItemId() !== null;
    }

    /**
     * Return if item has type in provided type array.
     *
     * @param string|string[] $type
     *
     * @return bool
     */
    public function hasType($type): bool
    {
        if (is_string($type)) {
            $type = [$type];
        }

        return in_array($this->getItem()->getProductType(), $type);
    }

    /**
     * Return if product price type is fixed.
     *
     * @return bool
     */
    private function _hasFixedPrice(): bool
    {
        return $this->getItem()->getProduct()->getPriceType() === Price::PRICE_TYPE_FIXED;
    }

    /**
     * Return if product is child of grouped product.
     *
     * @return bool
     */
    private function _isChildProduct(): bool
    {
        return $this->hasType(['simple', 'virtual']) && $this->hasParent();
    }

    /**
     * Return if item is the parent product of a non-fixed price group.
     *
     * @return bool
     */
    private function _isDynamicBundleParent(): bool
    {
        return $this->hasType('bundle') && !$this->_hasFixedPrice();
    }
}
