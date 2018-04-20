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

namespace Taxjar\SalesTax\Model\Tax;

use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\Api\AbstractServiceCollection;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Taxjar\SalesTax\Api\Data\Tax\NexusInterface;
use Taxjar\SalesTax\Api\Tax\NexusRepositoryInterface;

/**
 * Tax rate collection for a grid backed by Services
 */

class NexusCollection extends AbstractServiceCollection
{
    /**
     * @var TaxRateRepositoryInterface
     */
    protected $nexusRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param EntityFactory $entityFactory
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param NexusRepositoryInterface $nexusService
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        EntityFactory $entityFactory,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        NexusRepositoryInterface $nexusService,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($entityFactory, $filterBuilder, $searchCriteriaBuilder, $sortOrderBuilder);
        $this->nexusRepository = $nexusService;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    // @codingStandardsIgnoreStart
    public function loadData($printQuery = false, $logQuery = false)
    {
        // @codingStandardsIgnoreEnd
        if (!$this->isLoaded()) {
            $searchCriteria = $this->getSearchCriteria();
            $searchResults = $this->nexusRepository->getList($searchCriteria);
            $this->_totalRecords = $searchResults->getTotalCount();
            foreach ($searchResults->getItems() as $nexus) {
                $this->_addItem($this->createNexusCollectionItem($nexus));
            }
            $this->_setIsLoaded();
        }
        return $this;
    }

    /**
     * Creates a collection item that represents a nexus address for the nexus grid.
     *
     * @param NexusInterface $nexus Input data for creating the item.
     * @return \Magento\Framework\DataObject Collection item that represents a nexus address
     */
    protected function createNexusCollectionItem(NexusInterface $nexus)
    {
        // @codingStandardsIgnoreStart
        $collectionItem = new \Magento\Framework\DataObject();
        // @codingStandardsIgnoreEnd

        if ($nexus->getStoreId()) {
            $storeName = $this->storeManager->getStore($nexus->getStoreId())->getName();
        } else {
            $storeName = 'All Store Views';
        }

        $collectionItem->setId($nexus->getId());
        $collectionItem->setApiId($nexus->getApiId());
        $collectionItem->setStreet($nexus->getStreet());
        $collectionItem->setCity($nexus->getCity());
        $collectionItem->setCountryId($nexus->getCountryId());
        $collectionItem->setRegion($nexus->getRegion());
        $collectionItem->setRegionId($nexus->getRegionId());
        $collectionItem->setRegionCode($nexus->getRegionCode());
        $collectionItem->setPostcode($nexus->getPostcode());
        $collectionItem->setStore($storeName);

        return $collectionItem;
    }
}
