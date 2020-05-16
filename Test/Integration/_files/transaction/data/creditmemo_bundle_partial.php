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

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\ItemFactory;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\TestFramework\ObjectManager;

$skusNotToRefund = ['24-WG086', '24-WG088'];

$objectManager = ObjectManager::getInstance();

/** @var ItemFactory $creditmemoItemFactory */
$creditmemoItemFactory = $objectManager->create(ItemFactory::class);

/** @var CreditmemoFactory $creditmemoFactory */
$creditmemoFactory = $objectManager->get(CreditmemoFactory::class);

/** @var Creditmemo $creditmemo */
$creditmemo = $creditmemoFactory->createByOrder($order, $order->getData());

$creditmemo->setOrder($order)
    ->setSubtotal(36.0)
    ->setState(Creditmemo::STATE_OPEN);

$items = $creditmemo->getAllItems();

// Create a partial refund by only refunding certain items
foreach ($items as $item) {
    if (in_array($item->getSku(), $skusNotToRefund)) {
        $item->setQty(0);
    }
}

$creditmemo->setItems($items);
$creditmemo->save();
