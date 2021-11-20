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

namespace Taxjar\SalesTax\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;
use Taxjar\SalesTax\Model\Transaction\Order;
use Taxjar\SalesTax\Model\Transaction\OrderFactory;
use Taxjar\SalesTax\Model\Transaction\Refund;
use Taxjar\SalesTax\Model\Transaction\RefundFactory;
use Taxjar\SalesTax\Helper\Data as TaxjarHelper;

class SyncTransaction implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Taxjar\SalesTax\Model\Transaction\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Taxjar\SalesTax\Model\Transaction\RefundFactory
     */
    protected $refundFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Taxjar\SalesTax\Helper\Data
     */
    protected $helper;

    /**
     * @param ManagerInterface $messageManager
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderFactory $orderFactory
     * @param RefundFactory $refundFactory
     * @param Registry $registry
     */
    public function __construct(
        ManagerInterface $messageManager,
        OrderRepositoryInterface $orderRepository,
        OrderFactory $orderFactory,
        RefundFactory $refundFactory,
        Registry $registry,
        TaxjarHelper $helper
    ) {
        $this->messageManager = $messageManager;
        $this->orderRepository = $orderRepository;
        $this->orderFactory = $orderFactory;
        $this->refundFactory = $refundFactory;
        $this->registry = $registry;
        $this->helper = $helper;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();

        if ($observer->getData('order_id')) {
            $order = $this->orderRepository->get($observer->getData('order_id'));
        } else {
            $order = $event->getData('order') ?? $event->getData('order_id');
        }

        $forceSync = (bool)$observer->getData('force');
        $eventName = $event->getName();
        /** @var Order $orderTransaction */
        $orderTransaction = $this->orderFactory->create();

        if ($this->helper->isTransactionSyncEnabled($order->getStoreId()) &&
            ($orderTransaction->isSyncable($order) || $forceSync)
        ) {
            if (!$this->registry->registry('taxjar_sync_' . $eventName)) {
                $this->registry->register('taxjar_sync_' . $eventName, true);
            } else {
                if (!$forceSync) {
                    return;
                }
            }

            try {
                $orderTransaction->build($order);
                $orderTransaction->push($forceSync);

                $creditmemos = $order->getCreditmemosCollection();

                foreach ($creditmemos as $creditmemo) {
                    /** @var Refund $refundTransaction */
                    $refundTransaction = $this->refundFactory->create();
                    $refundTransaction->build($order, $creditmemo);
                    $refundTransaction->push($forceSync);
                }

                if ($observer->getData('order_id')) {
                    $this->messageManager->addSuccessMessage(__('Order successfully synced to TaxJar.'));
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        } else {
            if ($observer->getData('order_id')) {
                $this->messageManager->addErrorMessage(__('This order was not synced to TaxJar.'));
            }
        }
    }
}
