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
 * @copyright  Copyright (c) 2020 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;

$addressData = include 'data/address_data.php';
$orderData = include 'data/order_data.php';
$orderData['increment_id'] = '100000004';
$qty = 5;
$qtyRefunded = 3;

$objectManager = ObjectManager::getInstance();

$billingAddress = $objectManager->create(OrderAddress::class, ['data' => $addressData]);
$billingAddress->setAddressType('billing');

$shippingAddress = clone $billingAddress;
$shippingAddress->setId(null)->setAddressType('shipping');

/** @var Payment $payment */
$payment = $objectManager->create(Payment::class);
$payment->setMethod('checkmo')
    ->setAdditionalInformation('last_trans_id', '11122')
    ->setAdditionalInformation(
        'metadata',
        [
            'type' => 'free',
            'fraudulent' => false,
        ]
    );

$orderItems = [];

// Create the remaining simple items
foreach($products as $product) {
    /** @var OrderItem $orderItem */
    $orderItem = $objectManager->create(OrderItem::class);
    $orderItem->setProductId($product->getId())
        ->setQtyRefunded($qtyRefunded)
        ->setQtyOrdered($qty)
        ->setBasePrice($product->getPrice())
        ->setPrice($product->getPrice())
        ->setRowTotal($product->getPrice())
        ->setProductType($product->getTypeId())
        ->setName($product->getName())
        ->setSku($product->getSku());

    $orderItems[] = $orderItem;

    break;
}

/** @var Order $order */
$order = $objectManager->create(Order::class);
$order->setIncrementId($orderData['increment_id'])
    ->setState(Order::STATE_PROCESSING)
    ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING))
    ->setSubtotal($orderData['subtotal'])
    ->setGrandTotal($orderData['grand_total'])
    ->setBaseSubtotal($orderData['base_subtotal'])
    ->setBaseGrandTotal($orderData['base_grand_total'])
    ->setOrderCurrencyCode('AUD')
    ->setBaseToGlobalRate(1.0000)
    ->setBaseToOrderRate(1.0000)
    ->setCustomerIsGuest(true)
    ->setCustomerEmail($orderData['email'])
    ->setBillingAddress($billingAddress)
    ->setShippingAddress($shippingAddress)
    ->setStoreId($objectManager->get(StoreManagerInterface::class)->getStore()->getId())
    ->setPayment($payment);

foreach($orderItems as $orderItem){
    $order->addItem($orderItem);
}

/** @var OrderRepositoryInterface $orderRepository */
$orderRepository = $objectManager->create(OrderRepositoryInterface::class);

try {
    $orderRepository->save($order);
} catch (Exception $e) {
    $msg = $e->getMessage();
}
