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

use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManager;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\Logger;
use Taxjar\SalesTax\Model\Transaction\Backfill;
use Taxjar\SalesTax\Model\Transaction\OrderFactory;
use Taxjar\SalesTax\Model\Transaction\RefundFactory;
use Taxjar\SalesTax\Model\TransactionFactory;

class BackfillTransactions extends AsynchronousObserver
{
    /**
     * The default batch size used for bulk operations
     */
    protected const BATCH_SIZE = 100;

    protected const SYNCABLE_STATUSES = ['complete', 'closed'];

    /**
     * @var Backfill
     */
    protected $backfill;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var RefundFactory
     */
    protected $refundFactory;

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
     * @var Logger
     */
    protected $logger;

    /**
     * @var TaxjarConfig
     */
    protected $taxjarConfig;

    /**
     * @var Serialize
     */
    protected $serializer;

    /**
     * @param IdentityService $identityService
     * @param OperationInterfaceFactory $operationInterfaceFactory
     * @param SerializerInterface $serializer
     * @param BulkManagementInterface $bulkManagement
     * @param ScopeConfigInterface $scopeConfig
     * @param RequestInterface $request
     * @param StoreManager $storeManager
     * @param TransactionFactory $transactionFactory
     * @param OrderFactory $orderFactory
     * @param RefundFactory $refundFactory
     * @param Logger $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TaxjarConfig $taxjarConfig
     */
    public function __construct(
        IdentityService $identityService,
        OperationInterfaceFactory $operationInterfaceFactory,
        SerializerInterface $serializer,
        BulkManagementInterface $bulkManagement,
        ScopeConfigInterface $scopeConfig,
        RequestInterface $request,
        StoreManager $storeManager,
        TransactionFactory $transactionFactory,
        OrderFactory $orderFactory,
        RefundFactory $refundFactory,
        Logger $logger,
        OrderRepositoryInterface $orderRepository,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TaxjarConfig $taxjarConfig
    ) {
        parent::__construct(
            $identityService,
            $operationInterfaceFactory,
            $serializer,
            $bulkManagement
        );

        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->transactionFactory = $transactionFactory;
        $this->orderFactory = $orderFactory;
        $this->refundFactory = $refundFactory;
        $this->logger = $logger->setFilename(TaxjarConfig::TAXJAR_TRANSACTIONS_LOG)->force();
        $this->orderRepository = $orderRepository;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->taxjarConfig = $taxjarConfig;
    }

    /**
     * @param  Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        $data = $observer->getData();

        $this->apiKey = $this->taxjarConfig->getApiKey();

        if (!$this->apiKey) {
            throw new LocalizedException(
                __('Could not sync transactions with TaxJar. Please make sure you have an API key.')
            );
        }

        $this->logger->log('Initializing TaxJar transaction sync');

        $criteria = $this->getSearchCriteria($data);
        $orderResult = $this->orderRepository->getList($criteria);
        $orders = $orderResult->getItems();

        $this->logger->log(count($orders) . ' transaction(s) found');

        $this->schedule(
            array_chunk($orders, self::BATCH_SIZE),
            TaxjarConfig::TAXJAR_TOPIC_BACKFILL_TRANSACTIONS,
            'TaxJar transaction sync backfill'
        );
    }

    public function getSearchCriteria($data): SearchCriteria
    {
        $fromDate = $data['from_date'] ?: $this->request->getParam('from_date');
        $toDate = $data['to_date'] ?: $this->request->getParam('to_date');
        $storeId = $this->request->getParam('store');
        $websiteId = $this->request->getParam('website');

        // If the store id is empty but the website id is defined, load stores that match the website id
        if (is_null($storeId) && ! is_null($websiteId)) {
            $storeId = $this->getWebsiteStoreIds($websiteId);
        }

        // If the store id is defined, build a filter based on it
        if (! empty($storeId)) {
            $conditionType = is_array($storeId) ? 'in' : 'eq';
            $this->searchCriteriaBuilder->addFilter('store_id', $storeId, $conditionType);
            $idString = (is_array($storeId) ? implode(',', $storeId) : $storeId);
            $this->logger->log(
                sprintf('Limiting transaction sync to store id(s): %s', $idString)
            );
        }

        if (! empty($fromDate)) {
            $fromDate = (new \DateTime($fromDate));
        } else {
            $fromDate = (new \DateTime())->sub(new \DateInterval('P1D'));
        }

        if (! empty($toDate)) {
            $toDate = (new \DateTime($toDate));
        } else {
            $toDate = (new \DateTime());
        }

        if ($fromDate > $toDate) {
            throw new LocalizedException(__("To date can't be earlier than from date."));
        }

        $this->logger->log(
            sprintf(
                'Finding %s transactions from %s - %s',
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

    protected function getOrderStateFilter(string $state): Filter
    {
        return $this->filterBuilder->setField('state')
            ->setValue($state)
            ->setConditionType('eq')
            ->create();
    }

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
}
