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

namespace Taxjar\SalesTax\Model\ResourceModel\Transaction\Sync;

use Exception;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\Bulk\OperationManagementInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use Taxjar\SalesTax\Api\Data\TransactionManagementInterface;
use Taxjar\SalesTax\Model\Logger;

class Consumer
{
    /**
     * @var Logger
     */
    private Logger $_logger;

    /**
     * @var OperationManagementInterface
     */
    private OperationManagementInterface $_operationManagement;

    /**
     * @var OrderRepository
     */
    private OrderRepository $_orderRepository;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $_serializer;

    /**
     * @var \Taxjar\SalesTax\Api\Data\TransactionManagementInterface
     */
    private \Taxjar\SalesTax\Api\Data\TransactionManagementInterface $transactionManagement;

    /**
     * @param Logger $logger
     * @param OperationManagementInterface $operationManagement
     * @param OrderRepository $orderRepository
     * @param SerializerInterface $serializer
     * @param TransactionManagementInterface $transactionManagement
     */
    public function __construct(
        Logger $logger,
        OperationManagementInterface $operationManagement,
        OrderRepository $orderRepository,
        SerializerInterface $serializer,
        TransactionManagementInterface $transactionManagement
    ) {
        $this->_logger = $logger;
        $this->_operationManagement = $operationManagement;
        $this->_orderRepository = $orderRepository;
        $this->_serializer = $serializer;
        $this->transactionManagement = $transactionManagement;
    }

    /**
     * Process transaction sync operation.
     *
     * @param OperationInterface $operation
     *
     * @return void
     * @throws LocalizedException
     */
    public function process(OperationInterface $operation): void
    {
        $status = OperationInterface::STATUS_TYPE_COMPLETE;
        $errorCode = null;
        $message = null;
        $serializedData = $operation->getSerializedData();
        $unserializedData = $this->_serializer->unserialize($serializedData);

        try {
            if (empty($unserializedData['entity_id'])) {
                throw new LocalizedException(
                    __('Invalid operation data in TaxJar transaction backfill.')
                );
            }
            $order = $this->_orderRepository->get($unserializedData['entity_id']);
            $this->sync($order, $unserializedData['force_sync'] ?: false);
        } catch (Exception $e) {
            $this->_logger->log($e->getMessage());
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = ($e instanceof LocalizedException)
                ? $e->getMessage()
                : __('Sorry, something went wrong during TaxJar transaction sync. Please see log for details.');
        }

        $this->_operationManagement->changeOperationStatus(
            $operation->getId(),
            $status,
            $errorCode,
            $message,
            $serializedData
        );
    }

    /**
     * Sync order and related creditmemo(s).
     *
     * @param OrderInterface $transaction
     * @param bool $forceSync
     *
     * @throws LocalizedException
     */
    public function sync(OrderInterface $transaction, bool $forceSync = false): void
    {
        if ($this->transactionManagement->sync($transaction, $forceSync)) {
            $synced = [];

            foreach ($transaction->getCreditmemosCollection() as $creditmemo) {
                $synced[] = $this->transactionManagement->sync($creditmemo, $forceSync);
            }

            if (in_array(false, $synced)) {
                throw new LocalizedException(
                    __('One or more credit memos could not be synced to TaxJar. See logs for additional details.')
                );
            }
        }
    }
}
