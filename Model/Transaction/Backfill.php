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

    protected const OPERATION_PARAMETER_KEYS = ['orderIds', 'force'];

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Order
     */
    protected $orderTransaction;

    /**
     * @var Refund
     */
    protected $refundTransaction;

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
     * @var \Taxjar\SalesTax\Helper\Data
     */
    private $_tjSalesTaxData;

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
        EntityManager $entityManager,
        \Taxjar\SalesTax\Helper\Data $tjSalesTaxData
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderTransaction = $orderFactory;
        $this->refundTransaction = $refundFactory;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->_tjSalesTaxData = $tjSalesTaxData;

        $this->logger->setFilename(TaxjarConfig::TAXJAR_TRANSACTIONS_LOG)->force();
    }

    /**
     * @param OperationInterface $operation
     * @return void
     * @throws LocalizedException
     */
    public function process(OperationInterface $operation): void
    {
        try {
            [$orderIds, $forceFlag] = $this->getOperationData($operation);
            foreach ($orderIds as $orderId) {
                $order = $this->orderRepository->get($orderId);

                if (!$this->orderTransaction->isSyncable($order) ||
                    !$this->_tjSalesTaxData->isTransactionSyncEnabled($order->getStoreId())
                ) {
                    $this->logger->log('Order #' . $orderId . ' is not syncable', 'skip');
                    continue;
                }

                $this->orderTransaction->build($order);
                $this->orderTransaction->push($forceFlag);

                $creditMemos = $order->getCreditmemosCollection();

                foreach ($creditMemos as $creditMemo) {
                    $this->refundTransaction->build($order, $creditMemo);
                    $this->refundTransaction->push($forceFlag);
                }
            }
            $this->success($operation);
        } catch (\Exception $e) {
            $this->logger->log('Error syncing order #' . ($orderId ?? 'UNKNOWN') . ' - ' . $e->getMessage(), 'error');
            $this->fail($operation, $e->getCode(), $e->getMessage());
        }
    }

    /**
     * @param $operation
     * @return array
     * @throws LocalizedException
     */
    private function getOperationData($operation): array
    {
        $serialized = $operation->getSerializedData();
        $unserialized = $this->serializer->unserialize($serialized);
        $data = $unserialized[self::OPERATION_DATA_KEY];
        foreach (self::OPERATION_PARAMETER_KEYS as $key) {
            if (!array_key_exists($key, $data)) {
                throw new LocalizedException(
                    __('Operation data could not be parsed. Required array key `%1` does not exist.', $key)
                );
            }
        }
        return array_map(function ($key) use ($data) {
            return $data[$key];
        }, self::OPERATION_PARAMETER_KEYS);
    }

    /**
     * @param $operation
     * @throws \Exception
     */
    private function success($operation)
    {
        $operation->setStatus(BulkOperationInterface::STATUS_TYPE_COMPLETE);
        $operation->setErrorCode(null);
        $operation->setResultMessage(null);

        $this->entityManager->save($operation);
    }

    /**
     * @param OperationInterface $operation
     * @param int $code
     * @param string $message
     * @throws \Exception
     */
    private function fail(OperationInterface $operation, int $code, string $message)
    {
        $operation->setStatus(BulkOperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED);
        $operation->setErrorCode($code);
        $operation->setResultMessage($message);

        $this->entityManager->save($operation);
    }
}
