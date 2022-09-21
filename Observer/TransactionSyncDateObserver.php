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
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\AbstractModel;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo as CreditmemoResource;
use Taxjar\SalesTax\Model\Logger;
use Taxjar\SalesTax\Model\Transaction;

/**
 * Transaction sync date observer is responsible for updating TaxJar's sync date timestamp attribute for Magento Sales
 * Order and Creditmemo objects. For legacy reasons, this attribute is stored directly on the corresponding entity's
 * main table rather than an extension attribute on an additional module-specific table.
 */
class TransactionSyncDateObserver implements ObserverInterface
{
    /**
     * @var Logger
     */
    private Logger $_logger;

    /**
     * @var OrderResource
     */
    private OrderResource $_orderResource;

    /**
     * @var CreditmemoResource
     */
    private CreditmemoResource $_creditmemoResource;

    /**
     * @param Logger $logger
     * @param OrderResource $orderResource
     * @param CreditmemoResource $creditmemoResource
     */
    public function __construct(
        Logger $logger,
        OrderResource $orderResource,
        CreditmemoResource $creditmemoResource
    ) {
        $this->_logger = $logger;
        $this->_orderResource = $orderResource;
        $this->_creditmemoResource = $creditmemoResource;
    }

    /**
     * Save TaxJar last sync date to sales order or creditmemo record.
     *
     * @param Observer $observer
     *
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var CreditmemoInterface|OrderInterface|AbstractModel $transaction */
            $transaction = $observer->getEvent()->getTransaction();
            $this->_getResource($transaction)->saveAttribute($transaction, Transaction::FIELD_SYNC_DATE);
        } catch (\Exception|\Error $e) {
            $this->_logger->log($e->getMessage(), 'error');
        }
    }

    /**
     * Get transaction's corresponding resource model.
     *
     * @param CreditmemoInterface|OrderInterface $transaction
     *
     * @return CreditmemoResource|OrderResource
     */
    private function _getResource(CreditmemoInterface|OrderInterface $transaction): CreditmemoResource|OrderResource
    {
        return ($transaction instanceof CreditmemoInterface) ? $this->_creditmemoResource : $this->_orderResource;
    }
}
