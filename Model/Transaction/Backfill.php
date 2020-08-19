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

use Magento\Framework\Api\Filter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\Logger;
use Taxjar\SalesTax\Model\TransactionFactory;
use Taxjar\SalesTax\Model\Transaction\OrderFactory;
use Taxjar\SalesTax\Model\Transaction\RefundFactory;

class Backfill
{
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
     */
    public function __construct(
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
        TaxjarConfig $taxjarConfig
    ) {
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
    }

    /**
     * Start the transaction backfill process
     *
     * @param array $data
     * @return $this
     */
    public function start(
        array $data = []
    ) {
        // @codingStandardsIgnoreEnd

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

        $fromFilterGroup = $this->filterGroupBuilder
            ->setFilters([$fromFilter])
            ->create();

        $toFilter = $this->filterBuilder->setField('created_at')
            ->setConditionType('lteq')
            ->setValue($toDate->format('Y-m-d H:i:s'))
            ->create();

        $toFilterGroup = $this->filterGroupBuilder
            ->setFilters([$toFilter])
            ->create();

        $stateFilterGroup = $this->filterGroupBuilder
            ->setFilters(array_map([$this, 'orderStateFilter'], $statesToMatch))
            ->create();

        $filterGroups = [$fromFilterGroup, $toFilterGroup, $stateFilterGroup];

        if (isset($storeFilterGroup)) {
            $filterGroups[] = $storeFilterGroup;
        }

        $criteria = $this->searchCriteriaBuilder
            ->setFilterGroups($filterGroups)
            ->create();

        $orderResult = $this->orderRepository->getList($criteria);
        $orders = $orderResult->getItems();

        $this->logger->log(count($orders) . ' transaction(s) found');

        // This process can take awhile
        set_time_limit(0);
        ignore_user_abort(true);

        foreach ($orders as $order) {
            $orderTransaction = $this->orderFactory->create();

            if ($orderTransaction->isSyncable($order)) {
                $orderTransaction->build($order);
                $orderTransaction->push();

                $creditMemos = $order->getCreditmemosCollection();

                foreach ($creditMemos as $creditMemo) {
                    $refundTransaction = $this->refundFactory->create();
                    $refundTransaction->build($order, $creditMemo);
                    $refundTransaction->push();
                }
            } else {
                $this->logger->log('Order #' . $order->getIncrementId() . ' is not syncable', 'skip');
            }
        }

        return $this;
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
