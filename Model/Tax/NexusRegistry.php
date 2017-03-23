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

use Magento\Framework\Exception\NoSuchEntityException;
use Taxjar\SalesTax\Model\Tax\Nexus as Nexus;
use Taxjar\SalesTax\Model\Tax\NexusFactory as NexusFactory;

/**
 * Registry for the nexus address models
 */
class NexusRegistry
{
    /**
     * Nexus model factory
     *
     * @var \Taxjar\SalesTax\Model\Tax\NexusFactory
     */
    private $nexusFactory;

    /**
     * Neuxs models
     *
     * @var array
     */
    private $nexusRegistryById = [];

    /**
     * Initialize dependencies
     *
     * @param NexusFactory $nexusFactory
     */
    public function __construct(NexusFactory $nexusFactory)
    {
        $this->nexusFactory = $nexusFactory;
    }

    /**
     * Add nexus model to the registry
     *
     * @param Nexus $nexusModel
     * @return void
     */
    public function registerNexus(Nexus $nexusModel)
    {
        $this->nexusRegistryById[$nexusModel->getId()] = $nexusModel;
    }

    /**
     * Retrieve nexus model from the registry
     *
     * @param int $nexusId
     * @return \Taxjar\SalesTax\Model\Tax\Nexus
     * @throws NoSuchEntityException
     */
    public function retrieve($nexusId)
    {
        if (isset($this->nexusRegistryById[$nexusId])) {
            return $this->nexusRegistryById[$nexusId];
        }
        /** @var \Taxjar\SalesTax\Model\Tax\Nexus $nexusModel */
        $nexusModel = $this->nexusFactory->create()->load($nexusId);
        if (!$nexusModel->getId()) {
            // Nexus address does not exist
            throw NoSuchEntityException::singleField(Nexus::KEY_ID, $nexusId);
        }
        $this->nexusRegistryById[$nexusModel->getId()] = $nexusModel;
        return $nexusModel;
    }

    /**
     * Remove an instance of the nexus model from the registry
     *
     * @param int $nexusId
     * @return void
     */
    public function remove($nexusId)
    {
        unset($this->nexusRegistryById[$nexusId]);
    }
}
