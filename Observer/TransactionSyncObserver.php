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

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Taxjar\SalesTax\Model\Service\TransactionService;

/**
 * Transaction Sync observer is the primary observer class responsible for handling TaxJar sync-related events.
 */
class TransactionSyncObserver implements ObserverInterface
{
    /**
     * @var ManagerInterface
     */
    private ManagerInterface $messageManager;

    /**
     * @var TransactionService
     */
    private TransactionService $transactionService;

    /**
     * @param ManagerInterface $messageManager
     * @param TransactionService $transactionService
     */
    public function __construct(
        ManagerInterface $messageManager,
        TransactionService $transactionService
    ) {
        $this->messageManager = $messageManager;
        $this->transactionService = $transactionService;
    }

    /**
     * Sync transaction to TaxJar.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $transaction = $this->_getTransaction($observer);
            $forceSync = (bool) $observer->getEvent()->getForceSync();

            if (! $transaction->getId()) {
                return;
            }

            if ($this->transactionService->sync($transaction, $forceSync)) {
                $message = __('Successfully synced %1 to TaxJar.', $this->_getType($transaction));
                $this->messageManager->addSuccessMessage($message);
            } else {
                $message = __(
                    'Unable to sync %1 #%2 to TaxJar. Please review logs for additional details.',
                    $this->_getType($transaction),
                    $transaction->getEntityId()
                );
                $this->messageManager->addNoticeMessage($message);
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
    }

    /**
     * Retrieve transaction from event.
     *
     * @param Observer $observer
     * @return CreditmemoInterface|OrderInterface
     * @throws LocalizedException
     */
    private function _getTransaction($observer): CreditmemoInterface|OrderInterface
    {
        $transaction = $observer->getEvent()->getTransaction() ??
            $observer->getEvent()->getCreditmemo() ??
            $observer->getEvent()->getOrder();

        if ($transaction !== null) {
            return $transaction;
        }

        throw new LocalizedException(
            __('Could not parse transaction for sync with TaxJar.')
        );
    }

    /**
     * Return user-friendly transaction type.
     *
     * @param CreditmemoInterface|OrderInterface $transaction
     * @return string
     */
    private function _getType(CreditmemoInterface|OrderInterface $transaction): string
    {
        return ($transaction instanceof CreditmemoInterface) ? 'credit memo' : 'order';
    }
}
