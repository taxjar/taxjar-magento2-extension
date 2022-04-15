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
 * @copyright  Copyright (c) 2020 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Ui\Component\Listing\Column;

use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Framework\Stdlib\DateTime\Timezone;
use \Magento\Framework\Exception\NoSuchEntityException;

class SyncedOrder extends Column
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteria;

    /**
     * @var Timezone
     */
    protected $timezone;

    /**
     * @var \Taxjar\SalesTax\Model\Logger
     */
    protected $logger;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $criteria
     * @param Timezone $timezone
     * @param \Taxjar\SalesTax\Model\Logger $logger
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $criteria,
        Timezone $timezone,
        \Taxjar\SalesTax\Model\Logger $logger,
        array $components = [],
        array $data = []
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteria  = $criteria;
        $this->timezone = $timezone;
        $this->logger = $logger->setFilename(TaxjarConfig::TAXJAR_DEFAULT_LOG);
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare data source sync date
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $orderSyncDate = '';

                try {
                    if (isset($item['tj_salestax_sync_date'])) {
                        $orderSyncDate = $this->timezone->formatDate(
                            new \DateTime($item['tj_salestax_sync_date']),
                            \IntlDateFormatter::MEDIUM,
                            true
                        );
                    }
                } catch (NoSuchEntityException $e) {
                    $this->logger->log($e->getMessage() . ', entity id: ' . $item['entity_id']);
                }

                $item[$this->getName()] = $orderSyncDate;
            }
        }

        return $dataSource;
    }
}
