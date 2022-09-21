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

namespace Taxjar\SalesTax\Observer;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Taxjar\SalesTax\Helper\Data;

/**
 * Dispatches transaction sync event on Sales Order update.
 */
class SalesOrderSyncObserver implements ObserverInterface
{
    /**
     * @var ManagerInterface
     */
    private ManagerInterface $eventManager;

    /**
     * @var \Taxjar\SalesTax\Helper\Data
     */
    private \Taxjar\SalesTax\Helper\Data $helper;

    /**
     * @param ManagerInterface $eventManager
     * @param Data $helper
     */
    public function __construct(
        ManagerInterface $eventManager,
        \Taxjar\SalesTax\Helper\Data $helper
    ) {
        $this->eventManager = $eventManager;
        $this->helper = $helper;
    }

    /**
     * Update TaxJar transaction record on sales order update.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        if ($order->getId() && in_array($order->getState(), $this->helper->getSyncableOrderStates())) {
            $this->eventManager->dispatch('taxjar_salestax_transaction_sync', [
                'transaction' => $order,
                'force_sync' => false,
            ]);
        }
    }
}
