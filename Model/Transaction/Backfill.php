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
declare(strict_types=1);

namespace Taxjar\SalesTax\Model\Transaction;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Framework\Bulk\OperationInterface as BulkOperationInterface;
use Magento\Framework\Bulk\OperationManagementInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\Logger;

class Backfill
{
    protected const OPERATION_DATA_KEY = 'meta_information';

    /**
     * @var OrderRepositoryInterface
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
     * @var \Taxjar\SalesTax\Model\Logger
     */
    protected $logger;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var OperationManagementInterface
     */
    protected $operationManagement;
    /**
     * @var OperationInterfaceFactory
     */
    protected $operationInterfaceFactory;

    /**
     * @var EntityManager
     */
    protected $entityManager;
    /**
     * @var Order
     */
    private $orderTransaction;
    /**
     * @var Refund
     */
    private $refundTransaction;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param Order $orderFactory
     * @param Refund $refundFactory
     * @param Logger $logger
     * @param SerializerInterface $serializer
     * @param EntityManager $entityManager
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Order $orderFactory,
        Refund $refundFactory,
        Logger $logger,
        SerializerInterface $serializer,
        EntityManager $entityManager
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderTransaction = $orderFactory;
        $this->refundTransaction = $refundFactory;
        $this->logger = $logger->setFilename(TaxjarConfig::TAXJAR_TRANSACTIONS_LOG)->force();
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
    }

    /**
     * @param OperationInterface $operation
     * @return void
     * @throws LocalizedException
     */
    public function process(OperationInterface $operation): void
    {
        $orderIds = $this->getOperationData($operation);

        try {
            foreach ($orderIds as $orderId) {
                $order = $this->orderRepository->get($orderId);

                if (! $this->orderTransaction->isSyncable($order)) {
                    $this->logger->log('Order #' . $orderId . ' is not syncable', 'skip');
                    continue;
                }

                $this->orderTransaction->build($order);
                $this->orderTransaction->push();

                $creditMemos = $order->getCreditmemosCollection();

                foreach ($creditMemos as $creditMemo) {
                    $this->refundTransaction->build($order, $creditMemo);
                    $this->refundTransaction->push();
                }
            }
            $this->success($operation);
        } catch (\Exception $e) {
            $this->logger->log('Error syncing order #' . ($orderId ?? 'UNKNOWN') . ' - ' . $e->getMessage(), 'error');
            $this->fail($operation, $e->getCode(), $e->getMessage());
        }
    }

    private function getOperationData($operation)
    {
        $serialized = $operation->getSerializedData();
        $unserialized = $this->serializer->unserialize($serialized);
        return $unserialized[self::OPERATION_DATA_KEY];
    }

    private function success($operation)
    {
        $operation->setStatus(BulkOperationInterface::STATUS_TYPE_COMPLETE);
        $operation->setErrorCode(null);
        $operation->setResultMessage(null);

        $this->entityManager->save($operation);
    }

    private function fail($operation, $code, $message)
    {
        $operation->setStatus(BulkOperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED);
        $operation->setErrorCode($code);
        $operation->setResultMessage($message);

        $this->entityManager->save($operation);
    }
}
