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

class Nexus extends \Magento\Framework\Model\AbstractExtensibleModel implements
    \Taxjar\SalesTax\Api\Data\Tax\NexusInterface
{
    /**#@+
     * Constants defined for keys of array, makes typos less likely
     */
    const KEY_ID          = 'id';
    const KEY_API_ID      = 'api_id';
    const KEY_STREET      = 'street';
    const KEY_CITY        = 'city';
    const KEY_COUNTRY_ID  = 'country_id';
    const KEY_REGION      = 'region';
    const KEY_REGION_ID   = 'region_id';
    const KEY_REGION_CODE = 'region_code';
    const KEY_POSTCODE    = 'postcode';
    const KEY_STORE_ID    = 'store_id';
    const KEY_CREATED_AT  = 'created_at';
    const KEY_UPDATED_AT  = 'updated_at';
    /**#@-*/

    /**
     * @return void
     */
    public function _construct()
    {
        $this->_init('Taxjar\SalesTax\Model\ResourceModel\Tax\Nexus');
    }

    /**
     * Get nexus ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->getData(self::KEY_ID);
    }

    /**
     * Get nexus API ID
     *
     * @return int
     */
    public function getApiId()
    {
        return $this->getData(self::KEY_API_ID);
    }

    /**
     * Get nexus street
     *
     * @return string
     */
    public function getStreet()
    {
        return $this->getData(self::KEY_STREET);
    }

    /**
     * Get nexus city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->getData(self::KEY_CITY);
    }

    /**
     * Get country ID
     *
     * @return int
     */
    public function getCountryId()
    {
        return $this->getData(self::KEY_COUNTRY_ID);
    }

    /**
     * Get nexus region
     *
     * @return string
     */
    public function getRegion()
    {
        return $this->getData(self::KEY_REGION);
    }

    /**
     * Get nexus region ID
     *
     * @return int
     */
    public function getRegionId()
    {
        return $this->getData(self::KEY_REGION_ID);
    }

    /**
     * Get nexus region code
     *
     * @return string
     */
    public function getRegionCode()
    {
        return $this->getData(self::KEY_REGION_CODE);
    }

    /**
     * Get nexus postcode
     *
     * @return string
     */
    public function getPostcode()
    {
        return $this->getData(self::KEY_POSTCODE);
    }

    /**
     * Get nexus store ID
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->getData(self::KEY_STORE_ID);
    }

    /**
     * Get nexus created at timestamp
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData(self::KEY_CREATED_AT);
    }

    /**
     * Get nexus updated at timestamp
     *
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::KEY_UPDATED_AT);
    }

    /**
     * Set nexus ID
     *
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        return $this->setData(self::KEY_ID, $id);
    }

    /**
     * Set nexus API ID
     *
     * @param int $apiId
     * @return $this
     */
    public function setApiId($apiId)
    {
        return $this->setData(self::KEY_API_ID, $apiId);
    }

    /**
     * Set nexus street
     *
     * @param string $street
     * @return $this
     */
    public function setStreet($street)
    {
        return $this->setData(self::KEY_STREET, $street);
    }

    /**
     * Set nexus city
     *
     * @param string $city
     * @return $this
     */
    public function setCity($city)
    {
        return $this->setData(self::KEY_CITY, $city);
    }

    /**
     * Set nexus country ID
     *
     * @param int $countryId
     * @return $this
     */
    public function setCountryId($countryId)
    {
        return $this->setData(self::KEY_COUNTRY_ID, $countryId);
    }

    /**
     * Set nexus region
     *
     * @param string $region
     * @return $this
     */
    public function setRegion($region)
    {
        return $this->setData(self::KEY_REGION, $region);
    }

    /**
     * Set nexus region ID
     *
     * @param int $regionId
     * @return $this
     */
    public function setRegionId($regionId)
    {
        return $this->setData(self::KEY_REGION_ID, $regionId);
    }

    /**
     * Set nexus region code
     *
     * @param string $regionCode
     * @return $this
     */
    public function setRegionCode($regionCode)
    {
        return $this->setData(self::KEY_REGION_CODE, $regionCode);
    }

    /**
     * Set nexus postcode
     *
     * @param string $postcode
     * @return $this
     */
    public function setPostcode($postcode)
    {
        return $this->setData(self::KEY_POSTCODE, $postcode);
    }

    /**
     * Set nexus store ID
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::KEY_STORE_ID, $storeId);
    }

    /**
     * Set nexus created at timestamp
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::KEY_CREATED_AT, $createdAt);
    }

    /**
     * Set nexus updated at timestamp
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::KEY_UPDATED_AT, $updatedAt);
    }

    /**
     * {@inheritdoc}
     *
     * @return \Magento\Tax\Api\Data\TaxClassExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Tax\Api\Data\TaxClassExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Taxjar\SalesTax\Api\Data\Tax\NexusExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
