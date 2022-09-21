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
use Magento\Sales\Api\Data\OrderInterface;
use Taxjar\SalesTax\Api\Data\TransactionInterface;
use Taxjar\SalesTax\Helper\Data;
use Taxjar\SalesTax\Model\Logger;
use Taxjar\SalesTax\Model\Transaction;
use Taxjar\SalesTax\Model\Transaction\Order\LineItemFactory;

/**
 * Encapsulates logic relevant to syncing Sales Order entities to TaxJar.
 */
class Order extends Transaction implements TransactionInterface
{
    /**
     * @var LineItemFactory
     */
    private LineItemFactory $_lineItem;

    /**
     * @var \Taxjar\SalesTax\Helper\Data
     */
    private \Taxjar\SalesTax\Helper\Data $_helper;

    /**
     * @var array|Transaction\Order\LineItem[]
     */
    private array $lineItems;

    /**
     * Order transaction constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param RegionFactory $regionFactory
     * @param Logger $logger
     * @param OrderInterface $transaction
     * @param LineItemFactory $lineItem
     * @param Data $helper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RegionFactory $regionFactory,
        Logger $logger,
        $transaction,
        \Taxjar\SalesTax\Model\Transaction\Order\LineItemFactory $lineItem,
        \Taxjar\SalesTax\Helper\Data $helper
    ) {
        $this->_lineItem = $lineItem;
        $this->_helper = $helper;
        parent::__construct($scopeConfig, $regionFactory, $logger, $transaction);
    }

    /**
     * @inheritDoc
     */
    public function getResourceName(): string
    {
        return 'orders';
    }

    /**
     * @inheritDoc
     */
    public function getResourceId(): ?string
    {
        return $this->_transaction->getIncrementId();
    }

    /**
     * Return order items as LineItem array.
     *
     * @return Transaction\Order\LineItem[]
     */
    public function getLineItems(): array
    {
        if (empty($this->lineItems)) {
            foreach ($this->_transaction->getItems() as $item) {
                $this->lineItems[] = $this->_lineItem->create(['item' => $item]);
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
     * @inheritDoc
     */
    public function canSync(): bool
    {
        $storeId = $this->_transaction->getStoreId();
        $address = $this->_getOrderAddress();

        return (
            $this->_transaction->getEntityId() !== null &&
            $this->_helper->isTransactionSyncEnabled($storeId) &&
            $this->_helper->isSyncableOrderState($this->_transaction) &&
            $this->_helper->isSyncableOrderCurrency($this->_transaction) &&
            $this->_helper->isSyncableOrderCountry($address)
        );
    }

    /**
     * Determine if order should sync based upon timestamp values.
     *
     * @param bool $force Optional flag forces transactions to sync ignoring last updated date.
     *
     * @return bool
     */
    public function shouldSync(bool $force = false): bool
    {
        $updatedAt = $this->_transaction->getUpdatedAt();
        $syncedAt = $this->_transaction->getData(self::FIELD_SYNC_DATE);
        return $force || ! ($syncedAt !== null) || $updatedAt > $syncedAt;
    }

    /**
     * Return API transaction details.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getTransactionDetails(): array
    {
        $subtotal = (float) $this->_transaction->getSubtotalInvoiced();
        $shipping = (float) $this->_transaction->getShippingInvoiced();
        $discount = (float) $this->_transaction->getDiscountInvoiced();
        $shippingDiscount = (float) $this->_transaction->getShippingDiscountAmount();
        $salesTax = (float) $this->_transaction->getTaxInvoiced();

        return [
            'plugin' => 'magento',
            'provider' => $this->getProvider(),
            'transaction_id' => $this->_transaction->getIncrementId(),
            'transaction_date' => $this->_transaction->getCreatedAt(),
            'amount' => $subtotal + $shipping - abs($discount),
            'shipping' => $shipping - abs($shippingDiscount),
            'sales_tax' => $salesTax
        ];
    }

    /**
     * Return API request body's representation of transaction line items.
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
            $unitSubtotal = $item->getPrice() * $item->getQtyInvoiced();

            $aggregateDiscount = $this->_getAggregateDiscount($item->getItemId());
            $discount = $aggregateDiscount > 0 ? $aggregateDiscount : $item->getDiscountInvoiced();

            if ($discount > $unitSubtotal) {
                $discount = $unitSubtotal;
            }

            $aggregateTax = $this->_getAggregateTax($item->getItemId());
            $tax = $aggregateTax > 0 ? $aggregateTax : $item->getTaxInvoiced();

            $lineItemData[] = [
                'id'                 => $item->getItemId(),
                'quantity'           => (float) $item->getQtyInvoiced(),
                'product_identifier' => $item->getSku(),
                'description'        => $item->getName(),
                'unit_price'         => (float) $item->getPrice(),
                'discount'           => (float) $discount,
                'sales_tax'          => (float) $tax,
                'product_tax_code'   => $lineItem->getProductTaxCode(),
            ];
        }

        return ['line_items' => $lineItemData];
    }

    /**
     * Aggregates discount amounts across grouped items.
     *
     * @param int $parentItemId
     *
     * @return float|int|null
     */
    private function _getAggregateDiscount(int $parentItemId): float|int|null
    {
        $discount = 0;

        foreach ($this->getLineItems() as $lineItem) {
            if ($parentItemId && $parentItemId === $lineItem->getItem()->getParentItemId()) {
                $discount += $lineItem->getItem()->getDiscountAmount();
            }
        }

        return $discount;
    }

    /**
     * Aggregates tax amounts across grouped items.
     *
     * @param int $parentItemId
     *
     * @return float|null
     */
    private function _getAggregateTax(int $parentItemId): ?float
    {
        $tax = 0;

        foreach ($this->getLineItems() as $lineItem) {
            if ($parentItemId && $parentItemId === $lineItem->getItem()->getParentItemId()) {
                $tax += $lineItem->getItem()->getTaxAmount();
            }
        }

        return (float) $tax;
    }
}
