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

class Order extends \Taxjar\SalesTax\Model\Transaction
{
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $originalOrder;

    /**
     * @var array
     */
    protected $request;

    /**
     * Build an order transaction
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function build(
        \Magento\Sales\Model\Order $order
    ) {
        $createdAt = new \DateTime($order->getCreatedAt());
        $subtotal = (float) $order->getSubtotal();
        $shipping = (float) $order->getShippingAmount();
        $discount = (float) $order->getDiscountAmount();
        $shippingDiscount = (float) $order->getShippingDiscountAmount();
        $salesTax = (float) $order->getTaxAmount();

        $this->originalOrder = $order;

        $newOrder = [
            'plugin' => 'magento',
            'provider' => $this->getProvider($order),
            'transaction_id' => $order->getIncrementId(),
            'transaction_date' => $createdAt->format(\DateTime::ISO8601),
            'amount' => $subtotal + $shipping - abs($discount),
            'shipping' => $shipping - abs($shippingDiscount),
            'sales_tax' => $salesTax
        ];

        $this->request = array_merge(
            $newOrder,
            $this->buildFromAddress($order),
            $this->buildToAddress($order),
            $this->buildLineItems($order, $order->getAllItems()),
            $this->buildCustomerExemption($order)
        );

        return $this->request;
    }

    /**
     * Push an order transaction to SmartCalcs
     *
     * @param string|null $forceMethod
     * @return void
     */
    public function push($forceMethod = null) {
        $orderUpdatedAt = $this->originalOrder->getUpdatedAt();
        $orderSyncedAt = $this->originalOrder->getTjSalestaxSyncDate();
        $this->apiKey = $this->taxjarConfig->getApiKey($this->originalOrder->getStoreId());

        if (!$this->isSynced($orderSyncedAt)) {
            $method = 'POST';
        } else {
            if ($orderSyncedAt < $orderUpdatedAt) {
                $method = 'PUT';
            } else {
                $this->logger->log('Order #' . $this->request['transaction_id'] . ' not updated since last sync', 'skip');
                return;
            }
        }

        if ($this->apiKey) {
            $this->client->setApiKey($this->apiKey);
        }

        if ($forceMethod) {
            $method = $forceMethod;
        }

        try {
            $this->logger->log('Pushing order #' . $this->request['transaction_id'] . ': ' . json_encode($this->request), $method);

            if ($method == 'POST') {
                $response = $this->client->postResource('orders', $this->request);
                $this->logger->log('Order #' . $this->request['transaction_id'] . ' created in TaxJar: ' . json_encode($response), 'api');
            } else {
                $response = $this->client->putResource('orders', $this->request['transaction_id'], $this->request);
                $this->logger->log('Order #' . $this->request['transaction_id'] . ' updated in TaxJar: ' . json_encode($response), 'api');
            }

            $this->originalOrder->setTjSalestaxSyncDate(gmdate('Y-m-d H:i:s'))->save();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->log('Error: ' . $e->getMessage(), 'error');
            $error = json_decode($e->getMessage());

            // Retry push for not found records using POST
            if (!$forceMethod && $method == 'PUT' && $error && $error->status == 404) {
                $this->logger->log('Attempting to create order #' . $this->request['transaction_id'], 'retry');
                return $this->push('POST');
            }

            // Retry push for existing records using PUT
            if (!$forceMethod && $method == 'POST' && $error && $error->status == 422) {
                $this->logger->log('Attempting to update order #' . $this->request['transaction_id'], 'retry');
                return $this->push('PUT');
            }
        }
    }

    /**
     * Determines if an order can be synced
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function isSyncable(
        \Magento\Sales\Model\Order $order
    ) {
        $states = ['complete', 'closed'];

        if (!($order instanceof \Magento\Framework\Model\AbstractModel)) {
            return false;
        }

        if (!in_array($order->getState(), $states)) {
            return false;
        }

        // USD currency orders for reporting only
        if ($order->getOrderCurrencyCode() != 'USD') {
            return false;
        }

        if ($order->getIsVirtual()) {
            $address = $order->getBillingAddress();
        } else {
            $address = $order->getShippingAddress();
        }

        // US orders for reporting only
        if ($address->getCountryId() != 'US') {
            return false;
        }

        // Check if transaction sync is disabled at the store level OR at the store AND website levels
        $storeSyncEnabled = $this->helper->isTransactionSyncEnabled($order->getStoreId(), 'store');
        $websiteSyncEnabled = $this->helper->isTransactionSyncEnabled($order->getStore()->getWebsiteId(), 'website');

        if (!$storeSyncEnabled || (!$websiteSyncEnabled && !$storeSyncEnabled)) {
            return false;
        }

        return true;
    }
}
