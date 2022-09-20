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

namespace Taxjar\SalesTax\Model\Transaction;

use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Taxjar\SalesTax\Api\Data\TransactionInterface;
use Taxjar\SalesTax\Model\Logger;
use Taxjar\SalesTax\Model\Transaction;
use Taxjar\SalesTax\Model\Transaction\Refund\LineItem;
use Taxjar\SalesTax\Model\Transaction\Refund\LineItemFactory;

class Refund extends Transaction implements TransactionInterface
{
    /**
     * @var OrderFactory
     */
    private OrderFactory $_orderTransaction;

    /**
     * @var CreditmemoInterface
     */
    protected $_transaction;

    /**
     * @var LineItemFactory
     */
    private $lineitem;

    /**
     * @var array|LineItem[]
     */
    private array $lineItems;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param RegionFactory $regionFactory
     * @param Logger $logger
     * @param CreditmemoInterface $transaction
     * @param LineItemFactory $lineItem
     * @param OrderFactory $orderTransaction
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RegionFactory $regionFactory,
        Logger $logger,
        CreditmemoInterface $transaction,
        LineItemFactory $lineItem,
        OrderFactory $orderTransaction
    ) {
        $this->lineitem = $lineItem;
        $this->_orderTransaction = $orderTransaction;
        parent::__construct($scopeConfig, $regionFactory, $logger, $transaction);
    }

    /**
     * @inheritDoc
     */
    public function getResourceName(): string
    {
        return 'refunds';
    }

    /**
     * @inheritDoc
     */
    public function getResourceId(): ?string
    {
        return $this->_transaction->getIncrementId() . '-refund';
    }

    /**
     * Return creditmemo items as LineItem array.
     *
     * @return \Taxjar\SalesTax\Model\Transaction\Refund\LineItem[]
     */
    public function getLineItems(): array
    {
        if (empty($this->lineItems)) {
            foreach ($this->_transaction->getItems() as $item) {
                $this->lineItems[] = $this->lineitem->create(['item' => $item]);
            }
        }

        return $this->lineItems;
    }

    /**
     * Return API request body array.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRequestBody(): array
    {
        return array_merge(
            $this->getTransactionDetails(),
            $this->getAddressFrom(),
            $this->getAddressTo(),
            $this->getLineItemData(),
            $this->getCustomerExemption()
        );
    }

    /**
     * Return API transaction details.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getTransactionDetails(): array
    {
        $amount = $this->_transaction->getSubtotal() +
                  $this->_transaction->getShippingAmount() -
                  abs($this->_transaction->getDiscountAmount()) +
                  $this->_transaction->getAdjustment();

        return [
            'plugin' => 'magento',
            'provider' => $this->getProvider(),
            'transaction_id' => $this->_transaction->getIncrementId() . '-refund',
            'transaction_reference_id' => $this->_transaction->getOrder()->getEntityId(),
            'transaction_date' => $this->_transaction->getCreatedAt(),
            'amount' => $amount,
            'shipping' => $this->_transaction->getShippingAmount(),
            'sales_tax' => $this->_transaction->getTaxAmount()
        ];
    }

    /**
     * Get creditmemo line item data.
     *
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getLineItemData(): array
    {
        $lineItemData = [];

        foreach ($this->getLineItems() as $lineItem) {
            if (!$lineItem->isSyncable()) {
                continue;
            }

            $item = $lineItem->getItem();
            $unitSubtotal = $item->getPrice() * $item->getQty();
            $discount = (float) $this->_getAggregateDiscount($item->getItemId()) ?: $item->getDiscountAmount();
            $discount = (float) (($discount > $unitSubtotal) ? $unitSubtotal : $discount);
            $salesTax = (float) $this->_getAggregateTax($item->getItemId()) ?: $item->getTaxAmount();
            $productTaxCode = $lineItem->getProductTaxCode();

            $lineItemData[] = [
                'id'                 => $item->getOrderItemId(),
                'quantity'           => $item->getQty(),
                'product_identifier' => $item->getSku(),
                'description'        => $item->getName(),
                'unit_price'         => $item->getPrice(),
                'discount'           => $discount,
                'sales_tax'          => $salesTax,
                'product_tax_code'   => $productTaxCode
            ];
        }

        return ['line_items' => $lineItemData];
    }

    /**
     * @inheritDoc
     */
    public function canSync(): bool
    {
        $transaction = $this->_getOrderTransaction();
        return $transaction->canSync();
    }

    /**
     * @inheritDoc
     */
    public function shouldSync(bool $force = false): bool
    {
        return $this->_getOrderTransaction()->shouldSync($force);
    }

    /**
     * Retrieves the related Magento Sales Order and inits a Transaction class around it.
     *
     * @return Order|null
     */
    private function _getOrderTransaction(): ?Order
    {
        $order = $this->_transaction->getOrder();
        return $this->_orderTransaction->create(['transaction' => $order]);
    }

    /**
     * Aggregates discount amounts across grouped items.
     *
     * @param int|null $parentItemId
     *
     * @return float|null
     */
    private function _getAggregateDiscount(?int $parentItemId): ?float
    {
        $discount = 0;

        foreach ($this->getLineItems() as $lineItem) {
            if ($parentItemId && $parentItemId === $lineItem->getItem()->getParentItemId()) {
                $discount += $lineItem->getItem()->getOrderItem()->getDiscountRefunded();
            }
        }

        return (float) $discount;
    }

    /**
     * Aggregates tax amounts across grouped items.
     *
     * @param int|null $parentItemId
     *
     * @return float|null
     */
    private function _getAggregateTax(?int $parentItemId): ?float
    {
        $tax = 0;

        foreach ($this->getLineItems() as $lineItem) {
            if ($parentItemId && $parentItemId === $lineItem->getItem()->getParentItemId()) {
                $tax += $lineItem->getItem()->getOrderItem()->getTaxRefunded();
            }
        }

        return (float) $tax;
    }
}
