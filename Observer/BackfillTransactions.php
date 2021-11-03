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
        $this->logger = $logger->setFilename(\Taxjar\SalesTax\Model\Configuration::TAXJAR_TRANSACTIONS_LOG)->force();
        $this->orderRepository = $orderRepository;
        $this->storeManager = $storeManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->bulkManagement = $bulkManagement;
        $this->operationFactory = $operationFactory;
        $this->serializer = $serializer;
        $this->userContext = $userContext;
        $this->taxjarConfig = $taxjarConfig;
        $this->uuid = $identityService->generateId();
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
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

            $criteria = $this->getSearchCriteria();
            $orders = $this->getOrders($criteria, $this->getInput('force'));

            if (!empty($orders)) {
                $this->syncTransactions($orders);
            }

            $this->success();
        } catch (\Exception $e) {
            $this->fail($e);
        }
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function success()
    {
        $message = 'Transaction sync successfully scheduled. ';
        $message .= json_encode($this->getConfiguration());

        $this->logger->log($message);
    }

    /**
     * @param \Exception $e
     * @throws \Exception
     */
    public function fail($e): void {
        $message = 'Failed to schedule transaction sync! ';
        $message .= $e->getMessage() . ' - ';
        $message .= json_encode($this->getConfiguration());

        $this->logger->log($message);

        throw $e;
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
     * @param bool $force
     * @return array
     */
    public function getOrders(\Magento\Framework\Api\SearchCriteriaInterface $criteria, ?bool $force): array
    {
        $orders = $this->orderRepository->getList($criteria)->getItems();

        if (!$force) {
            $orders = array_filter($orders, [$this, 'isOrderSyncable']);
        }

        return $orders;
    }

    /**
     * @return \Magento\Framework\Api\SearchCriteriaInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSearchCriteria(): \Magento\Framework\Api\SearchCriteriaInterface
    {
        // Limit orders to specific store
        $storeId = $this->request->getParam('store');
        $websiteId = $this->request->getParam('website');

        // If the store id is empty but the website id is defined, load stores that match the website id
        if (is_null($storeId) && !is_null($websiteId)) {
            $stores = $this->storeManager->getStores();
            $storeId = array_filter($stores, [$this, 'compareStoreWebsiteId']);
        }

        // If the store id is defined, build a filter based on it
        if (!empty($storeId)) {
            $storeId = is_array($storeId) ? $storeId : [$storeId];
            $this->searchCriteriaBuilder->addFilter('store_id', $storeId, 'in');
            $this->logger->log(sprintf('Limiting transaction sync to store id(s): %s', implode(',', $storeId)));
        }

        // Limit orders to within date range
        $from = $this->getInput('from') ?? 'now';
        $to = $this->getInput('to') ?? 'now';
        $fromDt = new \DateTime($from);
        $toDt = new \DateTime($to);

        if ($from == 'now') {
            $fromDt->sub(new \DateInterval('P1D'));
        }

        $fromDt->setTime(0,0)->format('Y-m-d H:i:s');
        $toDt->setTime(23, 59, 59)->format('Y-m-d H:i:s');

        return $this->searchCriteriaBuilder
            ->addFilter('created_at', $fromDt, 'gteq')
            ->addFilter('created_at', $toDt, 'lteq')
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
                'force' => $this->getInput('force'),
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
        $websiteId = $store->getWebsiteId();
        return (!is_null($websiteId) && $websiteId == $this->getInput('websiteId'));
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
        return [
            'from' => $this->getInput('from'),
            'to' => $this->getInput('to'),
            'force_sync' => $this->getInput('force'),
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
