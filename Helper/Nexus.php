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
 * @copyright  Copyright (c) 2017 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc.
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Helper;

class Nexus extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Taxjar\SalesTax\Model\Tax\NexusFactory
     */
    private $nexusFactory;

    /**
     * Nexus constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Taxjar\SalesTax\Model\Tax\NexusFactory $nexusFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Taxjar\SalesTax\Model\Tax\NexusFactory $nexusFactory
    ) {
        parent::__construct($context);
        $this->nexusFactory = $nexusFactory;
    }

    /**
     * Get nexus collection formatted as an array of address data arrays
     *
     * @param string|int|null $storeId optionally filter result by store_id
     * @return array
     */
    public function getNexusAddresses($storeId): array
    {
        /** @var array|\Taxjar\SalesTax\Api\Data\Tax\NexusInterface[] $nexusArray */
        $nexusArray = array_values($this->getNexusCollection($storeId)->getItems());
        return array_map([$this, 'getNexusData'], $nexusArray);
    }

    /**
     * Get international nexus addresses
     *
     * @param string|int|null $storeId optionally filter result by store_id
     * @return \Magento\Framework\Data\Collection\AbstractDb|\Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection|null
     */
    public function getNexusCollection($storeId)
    {
        $nexusModel = $this->nexusFactory->create();
        $nexusCollection = $nexusModel->getCollection();

        if (!empty($storeId)) {
            $nexusCollection->addStoreFilter($storeId);
        }

        return $nexusCollection;
    }

    /**
     * Get array representation of NexusInterface object
     *
     * @param \Taxjar\SalesTax\Model\Tax\Nexus|\Taxjar\SalesTax\Api\Data\Tax\NexusInterface $nexus
     * @return array
     */
    public function getNexusData($nexus): array
    {
        return [
            'id' => $nexus->getId(),
            'country' => $nexus->getCountryId(),
            'zip' => $nexus->getPostcode(),
            'state' => $nexus->getRegionCode(),
            'city' => $nexus->getCity(),
            'street' => $nexus->getStreet()
        ];
    }

    /**
     * Determine if location has nexus by country/region code
     *
     * @param $storeId
     * @param $regionCode
     * @param $country
     * @return bool
     */
    public function hasNexusByLocation($storeId, $regionCode, $country): bool
    {
        if ($country == 'US' && empty($regionCode)) {
            return false;
        }

        $nexusCollection = $this->getNexusCollection($storeId);

        if ($country == 'US') {
            $nexusCollection->addRegionCodeFilter($regionCode);
        } else {
            $nexusCollection->addCountryFilter($country);
        }

        return (bool)$nexusCollection->getSize();
    }
}
