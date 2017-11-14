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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\Transaction\OrderFactory;
use Taxjar\SalesTax\Model\Transaction\RefundFactory;

class SyncRefund implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

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
     * @param ScopeConfigInterface $scopeConfig
     * @param ManagerInterface $messageManager
     * @param OrderFactory $orderFactory
     * @param RefundFactory $refundFactory
     * @param Registry $registry
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $messageManager,
        OrderFactory $orderFactory,
        RefundFactory $refundFactory,
        Registry $registry
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->messageManager = $messageManager;
        $this->orderFactory = $orderFactory;
        $this->refundFactory = $refundFactory;
        $this->registry = $registry;
    }

    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(
        Observer $observer
    ) {
        $syncEnabled = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_TRANSACTION_SYNC);
        $eventName = $observer->getEvent()->getName();

        if (!$syncEnabled) {
            return $this;
        }

        if (!$this->registry->registry('taxjar_sync_' . $eventName)) {
            $this->registry->register('taxjar_sync_' . $eventName, true);
        } else {
            return $this;
        }

        $creditmemo = $observer->getEvent()->getCreditmemo();
        $order = $creditmemo->getOrder();

        $orderTransaction = $this->orderFactory->create();

        if ($orderTransaction->isSyncable($order)) {
            try {
                $refundTransaction = $this->refundFactory->create();
                $refundTransaction->build($order, $creditmemo);
                $refundTransaction->push();
            } catch(\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }

        return $this;
    }
}
