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
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
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
     * @var CollectionFactory
     */
    private CollectionFactory $_orderCollection;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $_serializer;

    /**
     * @var \Taxjar\SalesTax\Api\Data\TransactionManagementInterface
     */
    private \Taxjar\SalesTax\Api\Data\TransactionManagementInterface $_transactionService;

    /**
     * @var array
     */
    private array $orderIds;

    /**
     * @var string|null
     */
    private ?string $startDate;

    /**
     * @var string|null
     */
    private ?string $endDate;

    /**
     * @var bool
     */
    private bool $forceSync;

    /**
     * @param Logger $logger
     * @param OperationManagementInterface $operationManagement
     * @param CollectionFactory $orderCollection
     * @param SerializerInterface $serializer
     * @param TransactionManagementInterface $transactionService
     */
    public function __construct(
        Logger $logger,
        OperationManagementInterface $operationManagement,
        CollectionFactory $orderCollection,
        SerializerInterface $serializer,
        TransactionManagementInterface $transactionService
    ) {
        $this->_logger = $logger;
        $this->_operationManagement = $operationManagement;
        $this->_orderCollection = $orderCollection;
        $this->_serializer = $serializer;
        $this->_transactionService = $transactionService;
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
            $this->_parseOperationData($operation);
            $orderCollection = $this->_orderCollection->create();
            $orderCollection->addFieldToFilter('entity_id', ['in' => $unserializedData['order_ids']]);
            $orderCollection->walk([$this, '_sync'], [
                $orderCollection->getSelect(),
                $unserializedData['force_sync'] ?: false
            ]);
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
     * @param OperationInterface $operation
     *
     * @throws LocalizedException
     */
    private function _parseOperationData(OperationInterface $operation): void
    {
        $serializedData = $operation->getSerializedData();
        $unserializedData = $this->_serializer->unserialize($serializedData);

        $this->_validateOperationData($unserializedData);

        $this->orderIds = $unserializedData['order_ids'];
        $this->startDate = $unserializedData['start_date'] ?: null;
        $this->endDate = $unserializedData['end_date'] ?: null;
        $this->forceSync = $unserializedData['force_sync'] ?: null;
    }

    /**
     * @param array $unserializedData
     *
     * @throws LocalizedException
     */
    private function _validateOperationData(array $unserializedData)
    {
        if (empty($unserializedData['order_ids'])) {
            throw new LocalizedException(
                __('Transaction ')
            );
        }
    }

    /**
     * Sync order and related creditmemos.
     *
     * @param CreditmemoInterface|OrderInterface $transaction
     *
     * @throws LocalizedException
     */
    private function _sync(CreditmemoInterface|OrderInterface $transaction): void
    {
        if ($this->_transactionService->sync($transaction, $this->forceSync)) {
            foreach ($transaction->getCreditmemosCollection() as $creditmemo) {
                $this->_transactionService->sync($creditmemo, $this->forceSync);
            }
        }
    }
}
