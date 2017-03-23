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

namespace Taxjar\SalesTax\Model\Tax\Nexus;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException as ModelException;
use Taxjar\SalesTax\Model\Tax\Nexus;
use Taxjar\SalesTax\Model\Tax\NexusRegistry;
use Taxjar\SalesTax\Model\ResourceModel\Tax\Nexus\Collection as NexusCollection;
use Taxjar\SalesTax\Model\ResourceModel\Tax\Nexus\CollectionFactory as NexusCollectionFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Repository implements \Taxjar\SalesTax\Api\Tax\NexusRepositoryInterface
{
    /**
     * @var NexusCollectionFactory
     */
    protected $nexusCollectionFactory;

    /**
     * @var \Magento\Tax\Api\Data\TaxClassSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var NexusRegistry
     */
    protected $nexusRegistry;

    /**
     * Search Criteria Builder
     *
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Filter Builder
     *
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\Tax\Model\ResourceModel\TaxClass
     */
    protected $nexusResource;

    /**
     * @var \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface
     */
    protected $joinProcessor;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param NexusCollectionFactory $nexusCollectionFactory
     * @param \Taxjar\SalesTax\Api\Data\Tax\NexusSearchResultsInterfaceFactory $searchResultsFactory
     * @param NexusRegistry $nexusRegistry
     * @param \Taxjar\SalesTax\Model\ResourceModel\Tax\Nexus $nexusResource
     * @param \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $joinProcessor
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        NexusCollectionFactory $nexusCollectionFactory,
        \Taxjar\SalesTax\Api\Data\Tax\NexusSearchResultsInterfaceFactory $searchResultsFactory,
        NexusRegistry $nexusRegistry,
        \Taxjar\SalesTax\Model\ResourceModel\Tax\Nexus $nexusResource,
        \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $joinProcessor
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->nexusCollectionFactory = $nexusCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->nexusRegistry = $nexusRegistry;
        $this->nexusResource = $nexusResource;
        $this->joinProcessor = $joinProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function save(\Taxjar\SalesTax\Api\Data\Tax\NexusInterface $nexus)
    {
        if ($nexus->getId()) {
            $this->nexusRegistry->retrieve($nexus->getId());
        }
        $this->validateNexusData($nexus);
        try {
            $this->nexusResource->save($nexus);
        } catch (ModelException $e) {
            if (strpos($e->getMessage(), (string)__('Region')) !== false) {
                throw new InputException(
                    __(
                        'A nexus address with the same region already exists.'
                    )
                );
            } else {
                throw $e;
            }
        }
        $this->nexusRegistry->registerNexus($nexus);
        return $nexus->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function get($nexusId)
    {
        return $this->nexusRegistry->retrieve($nexusId);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(\Taxjar\SalesTax\Api\Data\Tax\NexusInterface $nexus)
    {
        $nexusId = $nexus->getClassId();
        try {
            $this->nexusResource->delete($nexus);
        } catch (CouldNotDeleteException $e) {
            throw $e;
        } catch (\Exception $e) {
            return false;
        }
        $this->nexusRegistry->remove($nexusId);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($nexusId)
    {
        $nexusModel = $this->get($nexusId);
        return $this->delete($nexusModel);
    }

    /**
     * Validate nexus Data
     *
     * @param \Taxjar\SalesTax\Api\Data\Tax\NexusInterface $nexus
     * @return void
     * @throws InputException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function validateNexusData(\Taxjar\SalesTax\Api\Data\Tax\NexusInterface $nexus)
    {
        // @codingStandardsIgnoreStart
        $exception = new InputException();
        // @codingStandardsIgnoreEnd

        if (!\Zend_Validate::is(trim($nexus->getStreet()), 'NotEmpty')) {
            $exception->addErrorMessage(__('%fieldName is a required field.', ['fieldName' => Nexus::KEY_STREET]));
        }

        if (!\Zend_Validate::is(trim($nexus->getCity()), 'NotEmpty')) {
            $exception->addErrorMessage(__('%fieldName is a required field.', ['fieldName' => Nexus::KEY_CITY]));
        }

        if (!\Zend_Validate::is(trim($nexus->getCountryId()), 'NotEmpty')) {
            $exception->addErrorMessage(__('%fieldName is a required field.', ['fieldName' => Nexus::KEY_COUNTRY_ID]));
        }

        if (!\Zend_Validate::is(trim($nexus->getPostcode()), 'NotEmpty')) {
            $exception->addErrorMessage(__('%fieldName is a required field.', ['fieldName' => Nexus::KEY_POSTCODE]));
        }

        if (!$nexus->getId()) {
            $filter = $this->filterBuilder
                ->setField('country_id')
                ->setValue($nexus->getCountryId())
                ->create();
            $searchCriteria = $this->searchCriteriaBuilder->addFilters([$filter])->create();

            $countryAddresses = $this->getList($searchCriteria);

            if ($countryAddresses->getTotalCount()
            && $nexus->getCountryId() != 'US'
            && $nexus->getCountryId() != 'CA') {
                $exception->addErrorMessage(__('Only one address per country (outside of US/CA) is currently supported.'));
            }
        }

        if ($exception->wasErrorAdded()) {
            throw $exception;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        /** @var NexusCollection $collection */
        $collection = $this->nexusCollectionFactory->create();
        $this->joinProcessor->process($collection);
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $collection);
        }
        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $searchCriteria->getSortOrders();
        /** @var SortOrder $sortOrder */
        if ($sortOrders) {
            foreach ($searchCriteria->getSortOrders() as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());
        $searchResults->setItems($collection->getItems());
        return $searchResults;
    }

    /**
     * Helper function that adds a FilterGroup to the collection.
     *
     * @param FilterGroup $filterGroup
     * @param NexusCollection $collection
     * @return void
     */
    protected function addFilterGroupToCollection(FilterGroup $filterGroup, NexusCollection $collection)
    {
        $fields = [];
        $conditions = [];
        foreach ($filterGroup->getFilters() as $filter) {
            $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
            $fields[] = $filter->getField();
            $conditions[] = [$condition => $filter->getValue()];
        }
        if ($fields) {
            $collection->addFieldToFilter($fields, $conditions);
        }
    }
}
