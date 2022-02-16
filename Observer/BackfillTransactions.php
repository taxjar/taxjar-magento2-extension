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

namespace Taxjar\SalesTax\Observer;

class BackfillTransactions implements \Magento\Framework\Event\ObserverInterface
{
    public const BATCH_SIZE = 100;

    public const SYNCABLE_STATUSES = ['complete', 'closed'];

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;
    /**
     * @var \Taxjar\SalesTax\Model\Logger
     */
    protected $logger;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var \Magento\Framework\Bulk\BulkManagementInterface
     */
    protected $bulkManagement;
    /**
     * @var \Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory
     */
    protected $operationFactory;
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    protected $serializer;
    /**
     * @var \Magento\Authorization\Model\UserContextInterface
     */
    protected $userContext;
    /**
     * @var \Taxjar\SalesTax\Model\Configuration
     */
    protected $taxjarConfig;
    /**
     * @var \Magento\Framework\Event\Observer|null
     */
    public $observer;
    /**
     * @var string|null
     */
    public $uuid;
    /**
     * @var string[]|array|null
     */
    protected $dateRange;

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Taxjar\SalesTax\Model\Logger $logger
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Bulk\BulkManagementInterface $bulkManagement
     * @param \Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory $operationFactory
     * @param \Magento\Framework\DataObject\IdentityGeneratorInterface $identityService
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Magento\Authorization\Model\UserContextInterface $userContext
     * @param \Taxjar\SalesTax\Model\Configuration $taxjarConfig
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Taxjar\SalesTax\Model\Logger $logger,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Bulk\BulkManagementInterface $bulkManagement,
        \Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory $operationFactory,
        \Magento\Framework\DataObject\IdentityGeneratorInterface $identityService,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Authorization\Model\UserContextInterface $userContext,
        \Taxjar\SalesTax\Model\Configuration $taxjarConfig
    ) {
        $this->request = $request;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->storeManager = $storeManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->bulkManagement = $bulkManagement;
        $this->operationFactory = $operationFactory;
        $this->serializer = $serializer;
        $this->userContext = $userContext;
        $this->taxjarConfig = $taxjarConfig;
        $this->uuid = $identityService->generateId();

        $this->logger->setFilename(\Taxjar\SalesTax\Model\Configuration::TAXJAR_TRANSACTIONS_LOG)->force();
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception|\Throwable
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->observer = $observer;

        try {
            if (!$this->taxjarConfig->getApiKey()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Could not sync transactions with TaxJar. Please make sure you have an API key.')
                );
            }

            $dateRange = $this->getDateRange();
            $criteria = $this->getSearchCriteria(...$dateRange);
            $orders = $this->getOrders($criteria);

            if (!empty($orders)) {
                $this->syncTransactions($orders);
            }

            $this->success(count($orders));
        } catch (\Exception $e) {
            $this->fail($e);
        }
    }

    public function getDateRange(): array
    {
        if (empty($this->dateRange)) {
            $this->setDateRange();
        }

        return $this->dateRange;
    }

    public function setDateRange(): BackfillTransactions
    {
        $from = $this->getInput('from_date') ?? 'now';
        $to = $this->getInput('to_date') ?? 'now';
        $fromDt = new \DateTime($from);
        $toDt = new \DateTime($to);

        if ($from == 'now') {
            $fromDt->sub(new \DateInterval('P1D'));
        }

        $this->dateRange = [
            $fromDt->setTime(0, 0)->format('Y-m-d H:i:s'),
            $toDt->setTime(23, 59, 59)->format('Y-m-d H:i:s')
        ];

        return $this;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function success(int $count = 0): void
    {
        $message = 'No un-synced orders were found!';

        if ($count > 0) {
            $message = "Successfully scheduled $count order(s) and any related credit memos for sync with TaxJar!";
        }

        $this->log($message);
    }

    /**
     * @param \Magento\Framework\Exception\LocalizedException|\Exception|\Throwable $e
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception|\Throwable
     */
    public function fail($e): void
    {
        $exceptionMessage = $e->getMessage();

        $this->log("Failed to schedule transaction sync! Message: \"$exceptionMessage\"");

        throw $e;
    }

    /**
     * Because the transaction backfill process can be triggered via UI or CLI, and the process that handles the
     * HTTP backfill request relies on the `Logger::class`'s `playback()` value to create the UI message, it is
     * necessary to manually override the `playback` value after writing to the log file in order to prevent
     * displaying unnecessary JSON configuration details in the web UI.
     *
     * @param $message
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function log($message): void
    {
        $configuration = $this->getConfiguration();
        $encodedConfig = json_encode($configuration);
        $detail = "Detail: \"$encodedConfig\"";

        $playback = $this->logger->playback();
        $playback[] = $message;

        $this->logger->log("$message $detail");
        $this->logger->setPlayback($playback);
    }

    /**
     * @param array|\Magento\Sales\Api\Data\OrderInterface[] $orders
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function syncTransactions(array $orders): void
    {
        $orderIds = array_map([$this, 'getEntityId'], $orders);
        $chunkedOrderIds = array_chunk($orderIds, self::BATCH_SIZE);
        $operations = array_map([$this, 'makeOperation'], $chunkedOrderIds);

        if (!empty($operations)) {
            $result = $this->bulkManagement->scheduleBulk(
                $this->uuid,
                $operations,
                __('TaxJar Transaction Sync Backfill'),
                $this->userContext->getUserId()
            );

            if (!$result) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Bulk management encountered an unknown error.')
                );
            }
        }
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return array
     */
    public function getOrders(\Magento\Framework\Api\SearchCriteriaInterface $criteria): array
    {
        $orders = $this->orderRepository->getList($criteria)->getItems();

        if (!$this->getInput('force')) {
            $orders = array_filter($orders, [$this, 'isOrderSyncable']);
        }

        return $orders;
    }

    /**
     * @param string $from
     * @param string $to
     * @return \Magento\Framework\Api\SearchCriteriaInterface|\Magento\Framework\Api\SearchCriteria
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSearchCriteria(string $from, string $to): \Magento\Framework\Api\SearchCriteriaInterface
    {
        // Limit orders to specific store
        $storeId = $this->request->getParam('store');
        $websiteId = $this->request->getParam('website');

        // If the store id is empty but the website id is defined, load stores that match the website id
        if ($websiteId && $storeId === null) {
            $stores = $this->storeManager->getStores();
            $storeId = array_filter($stores, [$this, 'compareStoreWebsiteId']);
        }

        // If the store id is defined, build a filter based on it
        if (!empty($storeId)) {
            $storeId = is_array($storeId) ? $storeId : [$storeId];
            $this->searchCriteriaBuilder->addFilter('store_id', $storeId, 'in');
            $this->logger->log(sprintf('Limiting transaction sync to store id(s): %s', implode(',', $storeId)));
        }

        return $this->searchCriteriaBuilder
            ->addFilter('created_at', $from, 'gteq')
            ->addFilter('created_at', $to, 'lteq')
            ->addFilter('state', self::SYNCABLE_STATUSES, 'in')
            ->create();
    }

    /**
     * @param $body
     * @return  \Magento\AsynchronousOperations\Api\Data\OperationInterface
     */
    protected function makeOperation($body):  \Magento\AsynchronousOperations\Api\Data\OperationInterface
    {
        $dataToEncode = [
            'meta_information' => [
                'orderIds' => $body,
                'force' => (bool)$this->getInput('force'),
            ],
        ];

        $data = [
            'data' => [
                'bulk_uuid' => $this->uuid,
                'topic_name' => \Taxjar\SalesTax\Model\Configuration::TAXJAR_TOPIC_SYNC_TRANSACTIONS,
                'serialized_data' => $this->serializer->serialize($dataToEncode),
                'status' => \Magento\Framework\Bulk\OperationInterface::STATUS_TYPE_OPEN,
            ]
        ];

        return $this->operationFactory->create($data);
    }

    /**
     * @param \Magento\Store\Api\Data\StoreInterface $store
     * @return bool
     */
    private function compareStoreWebsiteId(\Magento\Store\Api\Data\StoreInterface $store): bool
    {
        $storeWebsiteId = $store->getWebsiteId();
        return $storeWebsiteId !== null && $storeWebsiteId == $this->getInput('websiteId');
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return bool
     */
    private function isOrderSyncable(\Magento\Sales\Api\Data\OrderInterface $order): bool
    {
        return $order->getUpdatedAt() > $order->getTjSalestaxSyncDate();
    }

    /**
     * @param \Magento\Sales\Model\AbstractModel $object
     * @return int|null
     */
    private function getEntityId(\Magento\Sales\Model\AbstractModel $object): ?int
    {
        return (int)$object->getEntityId();
    }

    /**
     * @return array
     */
    private function getConfiguration(): array
    {
        [$startDate, $endDate] = $this->getDateRange();

        return [
            'date_start' => $startDate,
            'date_end' => $endDate,
            'force_sync' => (bool)$this->getInput('force'),
        ];
    }

    /**
     * @param string $key
     * @return array|mixed
     */
    private function getInput(string $key)
    {
        if ($this->observer) {
            return $this->observer->getData($key) ?? $this->request->getParam($key);
        }

        return $this->request->getParam($key);
    }
}
