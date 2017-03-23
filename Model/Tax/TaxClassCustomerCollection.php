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
use Magento\Tax\Api\Data\TaxClassInterface as TaxClass;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\Tax\Api\TaxClassManagementInterface;
use Magento\Tax\Model\ClassModel;

/**
 * Tax rate collection for a grid backed by Services
 */

class TaxClassCustomerCollection extends AbstractServiceCollection
{
    /**
     * @var TaxClassRepositoryInterface
     */
    protected $taxClassRepository;

    /**
     * Initialize dependencies.
     *
     * @param EntityFactory $entityFactory
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param TaxClassRepositoryInterface $taxClassService
     */
    public function __construct(
        EntityFactory $entityFactory,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        TaxClassRepositoryInterface $taxClassService
    ) {
        parent::__construct($entityFactory, $filterBuilder, $searchCriteriaBuilder, $sortOrderBuilder);
        $this->taxClassRepository = $taxClassService;
    }

    /**
     * {@inheritdoc}
     */
    // @codingStandardsIgnoreStart
    public function loadData($printQuery = false, $logQuery = false)
    {
        // @codingStandardsIgnoreEnd
        if (!$this->isLoaded()) {
            $this->addFieldToFilter(ClassModel::KEY_TYPE, TaxClassManagementInterface::TYPE_CUSTOMER);
            $searchCriteria = $this->getSearchCriteria();
            $searchResults = $this->taxClassRepository->getList($searchCriteria);
            $this->_totalRecords = $searchResults->getTotalCount();
            foreach ($searchResults->getItems() as $taxClass) {
                $this->_addItem($this->createTaxClassCollectionItem($taxClass));
            }
            $this->_setIsLoaded();
        }
        return $this;
    }

    /**
     * Creates a collection item that represents a tax class for the tax class grid.
     *
     * @param TaxClass $taxClass Input data for creating the item.
     * @return \Magento\Framework\DataObject Collection item that represents a tax class
     */
    protected function createTaxClassCollectionItem(TaxClass $taxClass)
    {
        // @codingStandardsIgnoreStart
        $collectionItem = new \Magento\Framework\DataObject();
        // @codingStandardsIgnoreEnd
        $collectionItem->setClassId($taxClass->getClassId());
        $collectionItem->setClassName($taxClass->getClassName());
        $collectionItem->setClassType($taxClass->getClassType());
        $collectionItem->setTjSalestaxCode($taxClass->getTjSalestaxCode());

        return $collectionItem;
    }
}
