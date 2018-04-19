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

namespace Taxjar\SalesTax\Model\ResourceModel\Tax\Nexus;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Taxjar\SalesTax\Model\Tax\Nexus', 'Taxjar\SalesTax\Model\ResourceModel\Tax\Nexus');
    }

    /**
     * Retrieve option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->_toOptionArray('id', 'region');
    }

    /**
     * Retrieve option hash
     *
     * @return array
     */
    public function toOptionHash()
    {
        return $this->_toOptionHash('id', 'region');
    }

    /**
     * Filter by region_id
     *
     * @param string|array $regionId
     * @return $this
     */
    public function addRegionFilter($regionId)
    {
        if (!empty($regionId)) {
            if (is_array($regionId)) {
                $this->addFieldToFilter('main_table.region_id', ['in' => $regionId]);
            } else {
                $this->addFieldToFilter('main_table.region_id', $regionId);
            }
        }
        return $this;
    }

    /**
     * Filter by region_code
     *
     * @param string|array $regionCode
     * @return $this
     */
    public function addRegionCodeFilter($regionCode)
    {
        if (!empty($regionCode)) {
            if (is_array($regionCode)) {
                $this->addFieldToFilter('main_table.region_code', ['in' => $regionCode]);
            } else {
                $this->addFieldToFilter('main_table.region_code', $regionCode);
            }
        }
        return $this;
    }

    /**
     * Filter by country_id
     *
     * @param string|array $countryId
     * @return $this
     */
    public function addCountryFilter($countryId)
    {
        if (!empty($countryId)) {
            if (is_array($countryId)) {
                $this->addFieldToFilter('main_table.country_id', ['in' => $countryId]);
            } else {
                $this->addFieldToFilter('main_table.country_id', $countryId);
            }
        }
        return $this;
    }

    /**
     * Filter by store_id
     *
     * @param int $storeId
     * @return $this
     */
    public function addStoreFilter($storeId)
    {
        if (!empty($storeId)) {
            // Always include global nexus addresses
            $storeIds = [0, $storeId];
            $this->addFieldToFilter('main_table.store_id', ['in' => $storeIds]);
        }
        return $this;
    }
}
