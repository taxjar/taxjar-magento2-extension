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

namespace Taxjar\SalesTax\Model\Sales\Order;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Taxjar\SalesTax\Api\Data\Sales\Order\MetadataInterface;
use Taxjar\SalesTax\Api\Data\Sales\Order\MetadataSearchResultInterfaceFactory;
use Taxjar\SalesTax\Api\Data\Sales\MetadataRepositoryInterface;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata\CollectionFactory as MetadataCollectionFactory;

class MetadataRepository implements MetadataRepositoryInterface
{
    /**
     * @var MetadataFactory
     */
    private $metadataFactory;

    /**
     * @var Metadata
     */
    private $metadataResource;

    /**
     * @var MetadataCollectionFactory
     */
    private $metadataCollectionFactory;

    /**
     * @var MetadataSearchResultInterfaceFactory
     */
    private $searchResultFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    public function __construct(
        MetadataFactory $metadataFactory,
        Metadata $metadataResource,
        MetadataCollectionFactory $metadataCollectionFactory,
        MetadataSearchResultInterfaceFactory $metadataSearchResultInterfaceFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->metadataFactory = $metadataFactory;
        $this->metadataResource = $metadataResource;
        $this->metadataCollectionFactory = $metadataCollectionFactory;
        $this->searchResultFactory = $metadataSearchResultInterfaceFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @param int $id
     * @return \Taxjar\SalesTax\Api\Data\Sales\Order\MetadataInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id)
    {
        $metadata = $this->metadataFactory->create();
        $this->metadataResource->load($metadata, $id);
        if (!$metadata->getId()) {
            throw new NoSuchEntityException(__('Unable to find Order Metadata with ID "%1"', $id));
        }
        return $metadata;
    }

    /**
     * @param \Taxjar\SalesTax\Api\Data\Sales\Order\MetadataInterface $metadata
     * @return \Taxjar\SalesTax\Api\Data\Sales\Order\MetadataInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(MetadataInterface $metadata)
    {
        $this->metadataResource->save($metadata);
        return $metadata;
    }

    /**
     * @param \Taxjar\SalesTax\Api\Data\Sales\Order\MetadataInterface $metadata
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(MetadataInterface $metadata)
    {
        try {
            $this->metadataResource->delete($metadata);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the entry: %1', $exception->getMessage())
            );
        }

        return true;
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Taxjar\SalesTax\Api\Data\Sales\Order\MetadataSearchResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->metadataCollectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);
        $searchResults = $this->searchResultFactory->create();

        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }
}
