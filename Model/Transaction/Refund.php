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

use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class Refund extends \Taxjar\SalesTax\Model\Transaction
{
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $originalOrder;

    /**
     * @var \Magento\Sales\Model\Order\Creditmemo
     */
    protected $originalRefund;

    /**
     * @var array
     */
    protected $request;

    /**
     * Build a refund transaction
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return array
     */
    public function build(
        \Magento\Sales\Model\Order $order,
        \Magento\Sales\Model\Order\Creditmemo $creditmemo
    ) {
        $subtotal = (float) $creditmemo->getSubtotal();
        $shipping = (float) $creditmemo->getShippingAmount();
        $discount = (float) $creditmemo->getDiscountAmount();
        $shippingDiscount = (float) $creditmemo->getShippingDiscountAmount();
        $salesTax = (float) $creditmemo->getTaxAmount();

        $this->originalOrder = $order;
        $this->originalRefund = $creditmemo;

        $refund = [
            'plugin' => 'magento',
            'transaction_id' => $creditmemo->getIncrementId() . '-refund',
            'transaction_reference_id' => $order->getIncrementId(),
            'transaction_date' => $creditmemo->getCreatedAt(),
            'amount' => $subtotal + $shipping - abs($discount),
            'shipping' => $shipping - abs($shippingDiscount),
            'sales_tax' => $salesTax
        ];

        $this->request = array_merge(
            $refund,
            $this->buildFromAddress($order),
            $this->buildToAddress($order),
            $this->buildLineItems($order, $creditmemo->getAllItems(), 'refund')
        );

        $customer = $order->getCustomer();
        if ($customer_id = $customer->getId() && $customer->getCustomAttribute('tj_salestax_sync_date')->getValue()) {
            $this->request['customer_id'] = $customer_id;
        }

        return $this->request;
    }

    /**
     * Push refund transaction to SmartCalcs
     *
     * @param string|null $forceMethod
     * @return void
     */
    public function push($forceMethod = null) {
        $refundUpdatedAt = $this->originalRefund->getUpdatedAt();
        $refundSyncedAt = $this->originalRefund->getTjSalestaxSyncDate();
        $refundApiKey = trim($this->scopeConfig->getValue(TaxjarConfig::TAXJAR_APIKEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->originalOrder->getStoreId()
        ));

        if (!$this->isSynced($refundSyncedAt)) {
            $method = 'POST';
        } else {
            if ($refundSyncedAt < $refundUpdatedAt) {
                $method = 'PUT';
            } else {
                $this->logger->log('Refund #' . $this->request['transaction_id']
                                        . ' for order #' . $this->request['transaction_reference_id']
                                        . ' not updated since last sync', 'skip');
                return;
            }
        }

        if ($refundApiKey) {
            $this->client->setApiKey($refundApiKey);
        }

        if ($forceMethod) {
            $method = $forceMethod;
        }

        try {
            $this->logger->log('Pushing refund / credit memo #' . $this->request['transaction_id']
                                    . ' for order #' . $this->request['transaction_reference_id']
                                    . ': ' . json_encode($this->request), $method);

            if ($method == 'POST') {
                $response = $this->client->postResource('refunds', $this->request);
                $this->logger->log('Refund #' . $this->request['transaction_id'] . ' created: ' . json_encode($response), 'api');
            } else {
                $response = $this->client->putResource('refunds', $this->request['transaction_id'], $this->request);
                $this->logger->log('Refund #' . $this->request['transaction_id'] . ' updated: ' . json_encode($response), 'api');
            }

            $originalAmountRefunded = $this->originalOrder->getAmountRefunded();
            $originalBaseAmountRefunded = $this->originalOrder->getBaseAmountRefunded();
            $originalBaseAmountRefundedOnline = $this->originalOrder->getBaseAmountRefundedOnline();

            $this->originalRefund
                ->setTjSalestaxSyncDate(gmdate('Y-m-d H:i:s'))
                ->setPaymentRefundDisallowed(true)
                ->setAutomaticallyCreated(true)
                ->save();

            $this->originalOrder
                ->setAmountRefunded($originalAmountRefunded)
                ->setBaseAmountRefunded($originalBaseAmountRefunded)
                ->setBaseAmountRefundedOnline($originalBaseAmountRefundedOnline)
                ->save();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->log('Error: ' . $e->getMessage(), 'error');
            $error = json_decode($e->getMessage());

            // Retry push for not found records using POST
            if (!$forceMethod && $method == 'PUT' && $error && $error->status == 404) {
                $this->logger->log('Attempting to create refund / credit memo #' . $this->request['transaction_id'], 'retry');
                return $this->push('POST');
            }

            // Retry push for existing records using PUT
            if (!$forceMethod && $method == 'POST' && $error && $error->status == 422) {
                $this->logger->log('Attempting to update refund / credit memo #' . $this->request['transaction_id'], 'retry');
                return $this->push('PUT');
            }
        }
    }
}
