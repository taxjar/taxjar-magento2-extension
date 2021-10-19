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

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManager;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\Logger;

class BackfillTransactions implements ObserverInterface
{
    /**
     * The default batch size used for bulk operations
     */
    protected const BATCH_SIZE = 100;

    protected const SYNCABLE_STATUSES = ['complete', 'closed'];

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var TaxjarConfig
     */
    protected $taxjarConfig;

    /**
     * @var BulkManagementInterface
     */
    protected $bulkManagement;

    /**
     * @var OperationInterfaceFactory
     */
    protected $operationFactory;

    /**
     * @var IdentityGeneratorInterface
     */
    protected $identityService;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    protected $serializer;

    /**
     * @var \Magento\Authorization\Model\UserContextInterface
     */
    protected $userContext;

    /**
     * @param RequestInterface $request
     * @param StoreManager $storeManager
     * @param Logger $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TaxjarConfig $taxjarConfig
     * @param BulkManagementInterface $bulkManagement
     * @param OperationInterfaceFactory $operationFactory
     * @param IdentityGeneratorInterface $identityService
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Magento\Authorization\Model\UserContextInterface $userContext
     */
    public function __construct(
        RequestInterface $request,
        StoreManager $storeManager,
        Logger $logger,
        OrderRepositoryInterface $orderRepository,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TaxjarConfig $taxjarConfig,
        \Magento\Framework\Bulk\BulkManagementInterface $bulkManagement,
        \Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory $operationFactory,
        \Magento\Framework\DataObject\IdentityGeneratorInterface $identityService,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Authorization\Model\UserContextInterface $userContext
    ) {
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->logger = $logger->setFilename(TaxjarConfig::TAXJAR_TRANSACTIONS_LOG)->force();
        $this->orderRepository = $orderRepository;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->taxjarConfig = $taxjarConfig;
        $this->bulkManagement = $bulkManagement;
        $this->operationFactory = $operationFactory;
        $this->identityService = $identityService;
        $this->serializer = $serializer;
        $this->userContext = $userContext;
    }

    /**
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        $this->apiKey = $this->taxjarConfig->getApiKey();

        if (!$this->apiKey) {
            throw new LocalizedException(
                __('Could not sync transactions with TaxJar. Please make sure you have an API key.')
            );
        }

        $this->logger->log(__('Initializing TaxJar transaction sync'));

        $criteria = $this->getSearchCriteria($observer->getData());
        $orderResult = $this->orderRepository->getList($criteria);

        $this->logger->log(sprintf('%s transaction(s) found', $orderResult->getTotalCount()));

        $orderIds = $this->getOrderIds($orderResult->getItems());
        $orderIdsChunks = array_chunk($orderIds, self::BATCH_SIZE);
        $bulkUuid = $this->identityService->generateId();
        $bulkDescription = __('TaxJar Transaction Sync Backfill');
        $operations = [];

        foreach ($orderIdsChunks as $orderIdsChunk) {
            $operations[] = $this->makeOperation($bulkUuid, [
                'orderIds' => $orderIdsChunk,
                'force' => $observer->getData('force'),
            ]);
        }

        if (!empty($operations)) {
            $result = $this->bulkManagement->scheduleBulk(
                $bulkUuid,
                $operations,
                $bulkDescription,
                $this->userContext->getUserId()
            );
            if (!$result) {
                throw new LocalizedException(
                    __('Something went wrong while processing the request.')
                );
            }
            $this->logger->log(sprintf('Action scheduled. Bulk UUID: %s %s', $bulkUuid, PHP_EOL));
        }
    }

    /**
     * @param array $data
     * @return SearchCriteria
     * @throws LocalizedException
     */
    public function getSearchCriteria(array $data = []): SearchCriteria
    {
        $fromDate = $data['from_date'] ?? $this->request->getParam('from_date');
        $toDate = $data['to_date'] ?? $this->request->getParam('to_date');
        $storeId = $this->request->getParam('store');
        $websiteId = $this->request->getParam('website');

        // If the store id is empty but the website id is defined, load stores that match the website id
        if (is_null($storeId) && !is_null($websiteId)) {
            $storeId = $this->getWebsiteStoreIds($websiteId);
        }

        // If the store id is defined, build a filter based on it
        if (!empty($storeId)) {
            $conditionType = is_array($storeId) ? 'in' : 'eq';
            $this->searchCriteriaBuilder->addFilter('store_id', $storeId, $conditionType);
            $idString = (is_array($storeId) ? implode(',', $storeId) : $storeId);
            $this->logger->log(
                sprintf('Limiting transaction sync to store id(s): %s', $idString)
            );
        }

        if (!empty($fromDate)) {
            $fromDate = (new \DateTime($fromDate));
        } else {
            $fromDate = (new \DateTime())->sub(new \DateInterval('P1D'));
        }

        if (!empty($toDate)) {
            $toDate = (new \DateTime($toDate));
        } else {
            $toDate = (new \DateTime());
        }

        if ($fromDate > $toDate) {
            throw new LocalizedException(__("To date can't be earlier than from date."));
        }

        $this->logger->log(
            sprintf(
                'Finding transactions with statuses of %s from %s - %s',
                implode(', ', self::SYNCABLE_STATUSES),
                $fromDate->format('m/d/Y'),
                $toDate->format('m/d/Y')
            )
        );

        $fromDate->setTime(0, 0, 0)->format('Y-m-d H:i:s');
        $toDate->setTime(23, 59, 59)->format('Y-m-d H:i:s');

        return $this->searchCriteriaBuilder->addFilter('created_at', $fromDate, 'gteq')
            ->addFilter('created_at', $toDate, 'lteq')
            ->addFilters(array_map([$this, 'getOrderStateFilter'], self::SYNCABLE_STATUSES))
            ->create();
    }

    /**
     * @param string $state
     * @return Filter
     */
    protected function getOrderStateFilter(string $state): Filter
    {
        return $this->filterBuilder->setField('state')
            ->setValue($state)
            ->setConditionType('eq')
            ->create();
    }

    /**
     * @param $websiteId
     * @return array
     */
    protected function getWebsiteStoreIds($websiteId): array
    {
        $storeIds = [];

        foreach ($this->storeManager->getStores() as $store) {
            if ($store->getWebsiteId() == $websiteId) {
                $storeIds[] = $store->getId();
            }
        }

        return $storeIds;
    }

    /**
     * @param array $orders
     * @return array
     */
    protected function getOrderIds(array $orders): array
    {
        return array_map(function ($order) {
            return $order->getIncrementId();
        }, $orders);
    }

    /**
     * @param string $bulkUuid
     * @param $body
     * @return OperationInterface
     */
    protected function makeOperation(
        string $bulkUuid,
        $body
    ): OperationInterface {
        $dataToEncode = [
            'meta_information' => $body,
        ];
        $data = [
            'data' => [
                'bulk_uuid' => $bulkUuid,
                'topic_name' => TaxjarConfig::TAXJAR_TOPIC_SYNC_TRANSACTIONS,
                'serialized_data' => $this->serializer->serialize($dataToEncode),
                'status' => \Magento\Framework\Bulk\OperationInterface::STATUS_TYPE_OPEN,
            ]
        ];

        return $this->operationFactory->create($data);
    }
}
