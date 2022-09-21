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

namespace Taxjar\SalesTax\Model\Transaction\Refund;

use Magento\Catalog\Model\ProductRepository;
use Magento\Sales\Model\Order\Creditmemo\Item;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Taxjar\SalesTax\Model\Transaction\Order\LineItemFactory;

/**
 * Encapsulates logic relevant to parsing Creditmemo Item entities into TaxJar API refund request line items.
 */
class LineItem extends \Taxjar\SalesTax\Model\Transaction\LineItem
{
    /**
     * @var LineItemFactory
     */
    private LineItemFactory $orderLineItem;

    /**
     * Refund line item constructor.
     *
     * @param ProductRepository $productRepository
     * @param TaxClassRepositoryInterface $taxClassRepository
     * @param \Magento\Sales\Model\Order\Item|Item $item
     * @param LineItemFactory $orderLineItem
     */
    public function __construct(
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository,
        $item,
        \Taxjar\SalesTax\Model\Transaction\Order\LineItemFactory $orderLineItem
    ) {
        $this->orderLineItem = $orderLineItem;
        parent::__construct($productRepository, $taxClassRepository, $item);
    }

    /**
     * Return creditmemo item.
     *
     * @return Item
     */
    public function getItem(): Item
    {
        return $this->item;
    }

    /**
     * Get discount from line item.
     *
     * @return float|null
     */
    public function getDiscountAmount(): ?float
    {
        return $this->item->getDiscountAmount();
    }

    /**
     * Get tax from line item.
     *
     * @return float|null
     */
    public function getTaxAmount(): ?float
    {
        return $this->item->getTaxAmount();
    }

    /**
     * Return if line item should be included in API request body.
     *
     * @return bool
     */
    public function isSyncable(): bool
    {
        if ($this->getItem()->getQty() == 0) {
            return false;
        }

        if (!$this->_getOrderLineItem()->isSyncable()) {
            return false;
        }

        if ($this->getItem()->getOrderItem()->getParentItemId()) {
            return false;
        }

        return true;
    }

    /**
     * Get related order item as LineItem.
     *
     * @return \Taxjar\SalesTax\Model\Transaction\Order\LineItem
     */
    private function _getOrderLineItem(): \Taxjar\SalesTax\Model\Transaction\Order\LineItem
    {
        return $this->orderLineItem->create(['item' => $this->getItem()->getOrderItem()]);
    }
}
