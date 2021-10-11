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

namespace Taxjar\SalesTax\Model\Transaction;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Framework\Exception\LocalizedException;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\Logger;

class Backfill
{
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
     * @var Serialize
     */
    protected $serializer;

    /**
     * @param OrderFactory $orderFactory
     * @param RefundFactory $refundFactory
     * @param Logger $logger
     * @param Serialize $serializer
     */
    public function __construct(
        OrderFactory $orderFactory,
        RefundFactory $refundFactory,
        Logger $logger,
        Serialize $serializer
    ) {
        $this->orderFactory = $orderFactory;
        $this->refundFactory = $refundFactory;
        $this->logger = $logger->setFilename(TaxjarConfig::TAXJAR_TRANSACTIONS_LOG)->force();
        $this->serializer = $serializer;
    }

    /**
     * @param OperationInterface $operation
     * @return void
     * @throws LocalizedException
     */
    public function process(OperationInterface $operation): void
    {
        $data = $operation->getSerializedData();
        $orders = $this->serializer->unserialize($data);

        foreach ($orders as $order) {
            try {
                $orderTransaction = $this->orderFactory->create();

                if (! $orderTransaction->isSyncable($order)) {
                    $this->logger->log('Order #' . $order->getIncrementId() . ' is not syncable', 'skip');
                    continue;
                }

                $orderTransaction->build($order);
                $orderTransaction->push();

                $creditMemos = $order->getCreditmemosCollection();

                foreach ($creditMemos as $creditMemo) {
                    $refundTransaction = $this->refundFactory->create();
                    $refundTransaction->build($order, $creditMemo);
                    $refundTransaction->push();
                }
            } catch (\Exception $e) {
                $this->logger->log('Error syncing order #' . $order->getIncrementId() . ' - ' . $e->getMessage(), 'error');
            }
        }
    }
}
