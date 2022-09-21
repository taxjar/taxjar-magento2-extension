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

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Taxjar\SalesTax\Helper\Data;
use Taxjar\SalesTax\Model\Logger;

/**
 * Transaction backfill consumer is responsible for parsing transaction backfill event data into bulk operations which
 * can be handled asynchronously in the queue.
 */
class TransactionBackfillObserver implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Bulk\BulkManagementInterface
     */
    private \Magento\Framework\Bulk\BulkManagementInterface $_bulkManagement;

    /**
     * @var \Magento\Framework\DataObject\IdentityGeneratorInterface
     */
    private \Magento\Framework\DataObject\IdentityGeneratorInterface $_identityService;

    /**
     * @var \Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory
     */
    private \Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory $_operationFactory;

    /**
     * @var \Magento\Authorization\Model\UserContextInterface
     */
    private \Magento\Authorization\Model\UserContextInterface $_userContext;

    /**
     * @var  \Magento\Framework\App\RequestInterface
     */
    private \Magento\Framework\App\RequestInterface $_request;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $_orderCollection;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private \Magento\Store\Model\StoreManagerInterface $_storeManager;

    /**
     * @var \Taxjar\SalesTax\Model\Logger
     */
    private \Taxjar\SalesTax\Model\Logger $_logger;

    /**
     * @var \Taxjar\SalesTax\Helper\Data
     */
    private \Taxjar\SalesTax\Helper\Data $_helper;

    /**
     * @param BulkManagementInterface $bulkManagement
     * @param IdentityGeneratorInterface $identityService
     * @param OperationInterfaceFactory $operationFactory
     * @param RequestInterface $request
     * @param UserContextInterface $userContext
     * @param CollectionFactory $orderCollection
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param Data $helper
     */
    public function __construct(
        \Magento\Framework\Bulk\BulkManagementInterface $bulkManagement,
        \Magento\Framework\DataObject\IdentityGeneratorInterface $identityService,
        \Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory $operationFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Authorization\Model\UserContextInterface $userContext,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Taxjar\SalesTax\Model\Logger $logger,
        \Taxjar\SalesTax\Helper\Data $helper
    ) {
        $this->_bulkManagement = $bulkManagement;
        $this->_identityService = $identityService;
        $this->_operationFactory = $operationFactory;
        $this->_request = $request;
        $this->_userContext = $userContext;
        $this->_orderCollection = $orderCollection;
        $this->_storeManager = $storeManager;
        $this->_logger = $logger;
        $this->_helper = $helper;
    }

    /**
     * Parse backfill event and queue transaction sync operation(s).
     *
     * @param Observer $observer
     *
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        try {
            $userId = $this->_userContext->getUserId();
            $bulkUuid = $this->_identityService->generateId();
            $bulkDescription = __('TaxJar Transaction Backfill');
            $forceSync = (bool) $observer->getEvent()->getForceSync();

            $collection = $this->_getCollection($observer);

            if ($collection->getTotalCount() > 0) {
                $operations = $collection->walk([$this, 'makeOperation'], [
                    $collection->getSelect(),
                    $bulkUuid,
                    $forceSync
                ]);

                if (!$this->_bulkManagement->scheduleBulk($bulkUuid, $operations, $bulkDescription, $userId)) {
                    throw new LocalizedException(
                        __('Something went wrong while processing the request.')
                    );
                }

                $message = __('Successfully scheduled %1 orders for sync with TaxJar!', $collection->getTotalCount());
                $label = 'success';
            } else {
                $message = __('No un-synced orders were found!');
                $label = 'skip';
            }
        } catch (\Exception $e) {
            $message = __('Failed to schedule transaction sync: %1', $e->getMessage());
            $label = 'error';
        } catch (\Error $e) {
            $message = __('Failed to schedule transaction sync: %1', $e->getMessage());
            $label = 'error';
        } finally {
            $this->_logger->log($message, $label);
        }
    }

    /**
     * Map order and event data to bulk operation. Method public signature necessary as collection callable.
     *
     * @param OrderInterface $order
     * @param string $bulkUuid
     * @param bool $forceSync
     *
     * @return OperationInterface
     */
    public function makeOperation(OrderInterface $order, string $bulkUuid, bool $forceSync): OperationInterface
    {
        $operation = $this->_operationFactory->create();
        $operation->setBulkUuid($bulkUuid);
        $operation->setTopicName(\Taxjar\SalesTax\Model\Configuration::TAXJAR_TOPIC_SYNC_TRANSACTIONS);
        $operation->setStatus(\Magento\Framework\Bulk\OperationInterface::STATUS_TYPE_OPEN);
        $operation->setSerializedData(json_encode([
            'entity_id' => $order->getEntityId(),
            'force_sync' => $forceSync,
        ]));
        return $operation;
    }

    /**
     * Retrieve collection of syncable order IDs filtered by date range.
     *
     * @param Observer $observer
     *
     * @return Collection
     * @throws \Exception
     */
    private function _getCollection(Observer $observer): Collection
    {
        $startDate = $this->_getStartDate($observer);
        $endDate = $this->_getEndDate($observer);

        $collection = $this->_orderCollection->create();
        $collection->addFieldToFilter(OrderInterface::STATE, ['in' => $this->_getOrderStates()]);
        $collection->addFieldToFilter(OrderInterface::CREATED_AT, ['gteq' => $startDate]);
        $collection->addFieldToFilter(OrderInterface::CREATED_AT, ['lteq' => $endDate]);
        return $this->_filterCollectionByStore($collection, $observer);
    }

    /**
     * Retrieve date range start-date value.
     *
     * @param Observer $observer
     *
     * @return string
     * @throws \Exception
     */
    private function _getStartDate(Observer $observer): string
    {
        $startDate = $observer->getEvent()->getStartDate() ?? 'now';
        $dateTime = new \DateTime($startDate);

        if ($startDate === 'now') {
            $interval = new \DateInterval('P1D');
            $dateTime->sub($interval);
        }

        return $dateTime->setTime(0, 0)->format('Y-m-d H:i:s');
    }

    /**
     * Retrieve date range end-date value.
     *
     * @param Observer $observer
     *
     * @return string
     * @throws \Exception
     */
    private function _getEndDate(Observer $observer): string
    {
        $endDate = $observer->getEvent()->getEndDate() ?? 'now';
        $dateTime = new \DateTime($endDate);
        return $dateTime->setTime(23, 59, 59)->format('Y-m-d H:i:s');
    }

    /**
     * Retrieve syncable order states from environment.
     *
     * @return array
     */
    private function _getOrderStates(): array
    {
        return $this->_helper->getSyncableOrderStates();
    }

    /**
     * Filter order collection by store when included in request.
     *
     * @param Collection $collection
     * @param Observer $observer
     *
     * @return Collection
     */
    private function _filterCollectionByStore(Collection $collection, Observer $observer): Collection
    {
        $storeId = $observer->getEvent()->getStoreId();
        $websiteId = $observer->getEvent()->getWebsiteId();

        if ($storeId === null && $websiteId !== null) {
            $stores = [];
            foreach ($this->_storeManager->getStores() as $store) {
                if ($store->getWebsiteId() == $websiteId) {
                    $stores[] = $store->getId();
                }
            }
            $storeId = $stores;
        }

        if (!empty($storeId)) {
            $storeId = is_array($storeId) ? $storeId : [$storeId];
            $collection->addFieldToFilter(OrderInterface::STORE_ID, ['in' => $storeId]);
        }

        return $collection;
    }
}
