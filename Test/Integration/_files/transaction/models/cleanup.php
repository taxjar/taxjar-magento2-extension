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

use Magento\Framework\Registry;
use Magento\Sales\Model\Order;
use Magento\TestFramework\ObjectManager;

$orderData = include 'data/order_data.php';

$objectManager = ObjectManager::getInstance();
$registry = $objectManager->get(Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$order = $objectManager->create(Order::class);

$order->loadByIncrementId($orderData['increment_id']);
if ($order->getId()) {
    $order->delete();
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
