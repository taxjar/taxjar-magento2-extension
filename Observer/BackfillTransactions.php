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
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Framework\Serialize\SerializerInterface;
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

    /**
     * @var \Taxjar\SalesTax\Model\Transaction\Backfill
     */
    protected $backfill;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $storeManager;

    /**
     * @var \Taxjar\SalesTax\Model\TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var \Taxjar\SalesTax\Model\Transaction\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Taxjar\SalesTax\Model\Transaction\RefundFactory
     */
    protected $refundFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\Framework\Api\Search\FilterGroupBuilder
     */
    protected $filterGroupBuilder;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Taxjar\SalesTax\Model\Logger
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
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TaxjarConfig $taxjarConfig
     * @param Backfill $backfill
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
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        TaxjarConfig $taxjarConfig,
        Backfill $backfill
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
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->taxjarConfig = $taxjarConfig;
        $this->backfill = $backfill;
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
            throw new LocalizedException(__('Could not sync transactions with TaxJar. Please make sure you have an API key.'));
        }

        $statesToMatch = ['complete', 'closed'];
        $fromDate = $this->request->getParam('from_date');
        $toDate = $this->request->getParam('to_date');
        $storeId = $this->request->getParam('store');
        $websiteId = $this->request->getParam('website');

        if (isset($data['from_date'])) {
            $fromDate = $data['from_date'];
        }

        if (isset($data['to_date'])) {
            $toDate = $data['to_date'];
        }

        $this->logger->log('Initializing TaxJar transaction sync');

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

        $this->logger->log('Finding ' . implode(', ', $statesToMatch) . ' transactions from ' . $fromDate->format('m/d/Y') . ' - ' . $toDate->format('m/d/Y'));

        // If the store id is empty but the website id is defined, load stores that match the website id
        if (is_null($storeId) && !is_null($websiteId)) {
            $storeId = [];
            foreach ($this->storeManager->getStores() as $store) {
                if ($store->getWebsiteId() == $websiteId) {
                    $storeId[] = $store->getId();
                }
            }
        }

        // If the store id is defined, build a filter based on it
        if (!is_null($storeId) && !empty($storeId)) {
            $storeFilter = $this->filterBuilder->setField('store_id')
                ->setConditionType(is_array($storeId) ? 'in' : 'eq')
                ->setValue($storeId)
                ->create();

            $storeFilterGroup = $this->filterGroupBuilder
                ->setFilters([$storeFilter])
                ->create();

            $this->logger->log('Limiting transaction sync to store id(s): ' .
                (is_array($storeId) ? implode(',', $storeId) : $storeId));
        }

        $fromDate->setTime(0, 0, 0);
        $toDate->setTime(23, 59, 59);

        $fromFilter = $this->filterBuilder->setField('created_at')
            ->setConditionType('gteq')
            ->setValue($fromDate->format('Y-m-d H:i:s'))
            ->create();

        $fromFilterGroup = $this->filterGroupBuilder->setFilters([$fromFilter])->create();

        $toFilter = $this->filterBuilder->setField('created_at')
            ->setConditionType('lteq')
            ->setValue($toDate->format('Y-m-d H:i:s'))
            ->create();

        $toFilterGroup = $this->filterGroupBuilder->setFilters([$toFilter])->create();

        $stateFilterGroup = $this->filterGroupBuilder
            ->setFilters(array_map([$this, 'orderStateFilter'], $statesToMatch))
            ->create();

        $filterGroups = [$fromFilterGroup, $toFilterGroup, $stateFilterGroup];

        if (isset($storeFilterGroup)) {
            $filterGroups[] = $storeFilterGroup;
        }

        $criteria = $this->searchCriteriaBuilder->setFilterGroups($filterGroups)->create();

        $orderResult = $this->orderRepository->getList($criteria);
        $orders = $orderResult->getItems();

        $this->logger->log(count($orders) . ' transaction(s) found');

        $this->schedule(
            array_chunk($orders, self::BATCH_SIZE),
            TaxjarConfig::TAXJAR_TOPIC_BACKFILL_TRANSACTIONS,
            'TaxJar transaction sync backfill'
        );
    }

    /**
     * Filter orders to sync by order state (e.g. completed, closed)
     *
     * @param string $state
     * @return \Magento\Framework\Api\Filter
     */
    protected function orderStateFilter($state)
    {
        return $this->filterBuilder->setField('state')->setValue($state)->create();
    }
}
