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

use DateTime;
use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Order extends \Taxjar\SalesTax\Model\Transaction
{
    protected const SYNCABLE_STATES = ['complete', 'closed'];

    protected const SYNCABLE_CURRENCIES = ['USD'];

    protected const SYNCABLE_COUNTRIES = ['US'];

    /**
     * @var OrderInterface
     */
    protected $originalOrder;

    /**
     * @var array
     */
    protected $request;

    /**
     * Build an order transaction
     *
     * @param OrderInterface $order
     * @return array
     * @throws Exception
     */
    public function build(OrderInterface $order): array
    {
        $createdAt = new DateTime($order->getCreatedAt());
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
            'transaction_date' => $createdAt->format(DateTime::ISO8601),
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
     * @param string|null $method
     * @return void
     * @throws LocalizedException
     */
    public function push(string $method = null) {
        $orderUpdatedAt = $this->originalOrder->getUpdatedAt();
        $orderSyncedAt = $this->originalOrder->getData('tj_salestax_sync_date');

        if ($this->apiKey = $this->taxjarConfig->getApiKey($this->originalOrder->getStoreId())) {
            $this->client->setApiKey($this->apiKey);
        }

        if ($orderUpdatedAt >= $orderSyncedAt) {
            if ($method) {
                $this->logger->log('Forced update of Order #' . $this->request['transaction_id'], 'api');
            } else {
                $this->logger->log('Order #' . $this->request['transaction_id'] . ' not updated since last sync', 'skip');
                return;
            }
        }

        $httpMethod = $method ?: ($this->isSynced($orderSyncedAt) ? Request::METHOD_PUT : Request::METHOD_POST);

        try {
            $this->logger->log(__(
                'Pushing order #%s: %s',
                $this->request['transaction_id'],
                json_encode($this->request)
            ), $method);

            $response = $this->makeRequest($httpMethod);

            $this->logger->log(__(
                'Order #%s saved to TaxJar: %s',
                $this->request['transaction_id'],
                json_encode($response)
            ), 'api');

            $this->originalOrder->setData('tj_salestax_sync_date', gmdate('Y-m-d H:i:s'))->save();
        } catch (LocalizedException $e) {
            $this->logger->log('Error: ' . $e->getMessage(), 'error');
            $error = json_decode($e->getMessage());
            if ($error && !$method) {
                $this->handleError($error, $httpMethod);
            }
        }
    }

    /**
     * @param string $method
     * @return array
     * @throws LocalizedException
     */
    protected function makeRequest(string $method): array
    {
        switch ($method) {
            case Request::METHOD_POST:
                return $this->client->postResource('orders', $this->request);
            case Request::METHOD_PUT:
                return $this->client->putResource('orders', $this->request['transaction_id'], $this->request);
            default:
                throw new LocalizedException(
                    __('Unhandled HTTP method "%s" in TaxJar order transaction sync.', $method)
                );
        }
    }

    protected function handleError($error, string $method): void
    {
        if ($method == Request::METHOD_POST && $error->status == Response::HTTP_UNPROCESSABLE_ENTITY) {
            $retry = Request::METHOD_PUT;
        }

        if ($method == Request::METHOD_PUT && $error->status == Response::HTTP_NOT_FOUND) {
            $retry = Request::METHOD_POST;
        }

        if (isset($retry)) {
            $this->logger->log(__('Attempting to retry saving order #%s', $this->request['transaction_id']), 'retry');
            $this->push($retry);
        }
    }

    /**
     * Determines if an order can be synced
     *
     * @param OrderInterface $order
     * @return bool
     */
    public function isSyncable(OrderInterface $order): bool
    {
        return $this->stateIsSyncable($order)
            && $this->currencyIsSyncable($order)
            && $this->countryIsSyncable($order)
            && $this->transactionSyncIsEnabled($order);
    }

    protected function stateIsSyncable($order): bool
    {
        return in_array($order->getState(), self::SYNCABLE_STATES);
    }

    protected function currencyIsSyncable($order): bool
    {
        return in_array($order->getOrderCurrencyCode(), self::SYNCABLE_CURRENCIES);
    }

    protected function countryIsSyncable(\Magento\Sales\Model\Order $order): bool
    {
        $address = $order->getIsVirtual() ? $order->getBillingAddress() : $order->getShippingAddress();
        return in_array($address->getCountryId(), self::SYNCABLE_COUNTRIES);
    }

    protected function transactionSyncIsEnabled($order): bool
    {
        return $this->helper->isTransactionSyncEnabled($order->getStoreId(), 'store');
    }
}
