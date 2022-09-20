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
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;

class TransactionSyncAfterObserver implements ObserverInterface
{
    /**
     * @var ManagerInterface
     */
    private ManagerInterface $eventManager;

    /**
     * @param ManagerInterface $eventManager
     */
    public function __construct(ManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * Sync transaction to TaxJar.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $transaction = $observer->getEvent()->getTransaction();
        $forceSync = $observer->getEvent()->getForceSync();
        $success = $observer->getEvent()->getSuccess();

        if ($transaction instanceof OrderInterface && $success === true) {
            $collection = $transaction->getCreditmemosCollection();
            $collection->walk([$this, 'dispatch'], [$forceSync]);
        }
    }

    /**
     * Dispatch transaction sync event.
     *
     * @param CreditmemoInterface|OrderInterface $transaction
     * @param bool $forceSync
     * @return void
     */
    public function dispatch(CreditmemoInterface|OrderInterface $transaction, $forceSync): void
    {
        $this->eventManager->dispatch('taxjar_salestax_transaction_sync', [
            'transaction' => $transaction,
            'force_sync' => $forceSync,
        ]);
    }
}
