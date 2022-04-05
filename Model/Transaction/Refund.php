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
 * @copyright  Copyright (c) 2017 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Model\Transaction;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Creditmemo;

class Refund extends \Taxjar\SalesTax\Model\Transaction
{
    /**
     * @var OrderInterface|\Magento\Sales\Model\Order
     */
    protected $originalOrder;

    /**
     * @var CreditmemoInterface|Creditmemo
     */
    protected $originalRefund;

    /**
     * @var array
     */
    protected $request;

    /**
     * Set request value
     *
     * @param array $value
     * @return $this
     */
    public function setRequest($value)
    {
        $this->request = $value;

        return $this;
    }

    /**
     * Build a refund transaction
     *
     * @param OrderInterface $order
     * @param CreditmemoInterface $creditmemo
     * @return array
     * @throws LocalizedException
     */
    public function build(
        OrderInterface $order,
        CreditmemoInterface $creditmemo
    ): array {
        $subtotal = (float) $creditmemo->getSubtotal();
        $shipping = (float) $creditmemo->getShippingAmount();
        $discount = (float) $creditmemo->getDiscountAmount();
        $salesTax = (float) $creditmemo->getTaxAmount();
        $adjustment = (float) $creditmemo->getAdjustment();
        $itemDiscounts = 0;

        $this->originalOrder = $order;
        $this->originalRefund = $creditmemo;

        $refund = [
            'plugin' => 'magento',
            'provider' => $this->getProvider($order),
            'transaction_id' => $creditmemo->getIncrementId() . '-refund',
            'transaction_reference_id' => $order->getIncrementId(),
            'transaction_date' => $creditmemo->getCreatedAt(),
            'amount' => $subtotal + $shipping - abs($discount) + $adjustment,
            'shipping' => $shipping,
            'sales_tax' => $salesTax
        ];

        $requestBody = array_merge(
            $refund,
            $this->buildFromAddress($order),
            $this->buildToAddress($order),
            $this->buildLineItems($order, $creditmemo->getAllItems(), 'refund'),
            $this->buildCustomerExemption($order)
        );

        if (isset($requestBody['line_items'])) {
            $adjustmentFee = $creditmemo->getAdjustmentNegative();
            $adjustmentRefund = $creditmemo->getAdjustmentPositive();

            // Discounts on credit memos act as fees and shouldn't be included in $itemDiscounts
            foreach ($requestBody['line_items'] as $k => $lineItem) {
                if ($subtotal != 0) {
                    $lineItemSubtotal = $lineItem['unit_price'] * $lineItem['quantity'];
                    $requestBody['line_items'][$k]['discount'] += ($adjustmentFee * ($lineItemSubtotal / $subtotal));
                }

                $itemDiscounts += $lineItem['discount'];
            }

            if ($adjustmentRefund > 0) {
                $requestBody['line_items'][] = [
                    'id' => 'adjustment-refund',
                    'quantity' => 1,
                    'product_identifier' => 'adjustment-refund',
                    'description' => 'Adjustment Refund',
                    'unit_price' => $adjustmentRefund,
                    'discount' => 0,
                    'sales_tax' => 0
                ];
            }
        }

        if ((abs($discount) - $itemDiscounts) > 0) {
            $shippingDiscount = abs($discount) - $itemDiscounts;
            $requestBody['shipping'] = $shipping - $shippingDiscount;
        }

        $this->setRequest($requestBody);

        return $this->request;
    }

    /**
     * Sends the current member's $request property via client.
     *
     * @param bool $forceFlag Ignore last updated and synced dates
     * @param string|null $method Optionally specify HTTP method
     * @throws LocalizedException
     */
    public function push(bool $forceFlag = false, string $method = null)
    {
        $refundUpdatedAt = $this->originalRefund->getUpdatedAt();
        $refundSyncedAt = $this->originalRefund->getData('tj_salestax_sync_date');

        if ($this->apiKey = $this->taxjarConfig->getApiKey($this->originalOrder->getStoreId())) {
            $this->client->setApiKey($this->apiKey);
        }

        if ($refundUpdatedAt <= $refundSyncedAt) {
            if ($forceFlag) {
                $this->logger->log('Forced update of Refund #' . $this->request['transaction_id'], 'api');
            } else {
                $this->logger->log('Refund #' . $this->request['transaction_id']
                    . ' for order #' . $this->request['transaction_reference_id']
                    . ' not updated since last sync', 'skip');
                return;
            }
        }

        $httpMethod = $method ?: ($this->isSynced($refundSyncedAt) ? 'PUT' : 'POST');

        try {
            $this->logger->log('Pushing refund / credit memo #' . $this->request['transaction_id']
                . ' for order #' . $this->request['transaction_reference_id']
                . ': ' . json_encode($this->request), $method);

            $response = $this->makeRequest($httpMethod);

            $this->logger->log(
                sprintf('Refund #%s saved: %s', $this->request['transaction_id'], json_encode($response)),
                'api'
            );

            $this->originalRefund->setData('tj_salestax_sync_date', gmdate('Y-m-d H:i:s'));
            $this->originalRefund->getResource()->saveAttribute($this->originalRefund, 'tj_salestax_sync_date');
        } catch (\Exception $e) {
            $this->logger->log('Error: ' . $e->getMessage(), 'error');
            $error = json_decode($e->getMessage());
            if ($error && !$method) {
                $this->handleError($error, $httpMethod, $forceFlag);
            }
        }
    }

    /**
     * Uses TaxJar Client class to call API
     *
     * @param string $method HTTP method to use for request
     * @return array Client response data array
     * @throws LocalizedException
     */
    protected function makeRequest(string $method): array
    {
        switch ($method) {
            case 'POST':
                return $this->client->postResource('refunds', $this->request);
            case 'PUT':
                return $this->client->putResource('refunds', $this->request['transaction_id'], $this->request);
            default:
                throw new LocalizedException(
                    __('Unhandled HTTP method "%s" in TaxJar refund transaction sync.', $method)
                );
        }
    }

    /**
     * Handles case where error occurs when POSTing a resource that already
     * exists or PUTing a resource that has not been created yet.
     *
     * @param object $error `jsonDecode()`ed response error message object
     * @param string $method HTTP method used in request
     * @param bool $forceFlag Request ignores last updated and synced dates
     * @throws LocalizedException
     */
    protected function handleError($error, string $method, bool $forceFlag): void
    {
        if ($method == 'POST' && $error->status == 422) {
            $retry = 'PUT';
        }

        if ($method == 'PUT' && $error->status == 404) {
            $retry = 'POST';
        }

        if (isset($retry)) {
            $this->logger->log(
                sprintf('Attempting to retry saving refund / credit memo #%s', $this->request['transaction_id']),
                'retry'
            );
            $this->push($forceFlag, $retry);
        }
    }
}
