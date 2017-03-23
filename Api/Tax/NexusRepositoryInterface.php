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

namespace Taxjar\SalesTax\Api\Tax;

/**
 * Nexus CRUD interface.
 * @api
 */
interface NexusRepositoryInterface
{
    /**
     * Get a nexus address with the given nexus id.
     *
     * @param int $nexusId
     * @return \Taxjar\SalesTax\Api\Data\Tax\NexusInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException If nexus address with $id does not exist
     */
    public function get($nexusId);

    /**
     * Retrieve nexus addresses which match a specific criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Tax\Api\Data\NexusSearchResultsInterface containing Data\NexusInterface
     * @throws \Magento\Framework\Exception\InputException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Create a nexus address
     *
     * @param \Taxjar\SalesTax\Api\Data\Tax\NexusInterface $nexus
     * @return string id for the newly created nexus address
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(\Taxjar\SalesTax\Api\Data\Tax\NexusInterface $nexus);

    /**
     * Delete a nexus address
     *
     * @param \Taxjar\SalesTax\Api\Data\Tax\NexusInterface $nexus
     * @return bool True if the nexus was deleted, false otherwise
     * @throws \Magento\Framework\Exception\NoSuchEntityException If nexus address with $nexus does not exist
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(\Taxjar\SalesTax\Api\Data\Tax\NexusInterface $nexus);

    /**
     * Delete a nexus address with the given nexus id.
     *
     * @param int $nexusId
     * @return bool True if the nexus was deleted, false otherwise
     * @throws \Magento\Framework\Exception\NoSuchEntityException If nexus address with $nexus does not exist
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById($nexusId);
}
