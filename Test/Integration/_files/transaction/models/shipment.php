<?php

use Magento\TestFramework\ObjectManager;
use Magento\Sales\Model\Convert\Order as ConvertOrder;

$objectManager = ObjectManager::getInstance();

if (!$order->canShip()) {
    throw new Exception('Unable to  create shipment');
}

$convertOrder = $objectManager->create(ConvertOrder::class);
$shipment = $convertOrder->toShipment($order);

foreach ($order->getAllItems() AS $orderItem) {
    if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
        continue;
    }

    $qtyShipped = $orderItem->getQtyToShip();
    $shipmentItem = $convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);

    $shipment->addItem($shipmentItem);
}

$shipment->register();
$shipment->getOrder()->setIsInProcess(true);
$shipment->save();
$shipment->getOrder()->save();
