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

namespace Taxjar\SalesTax\Api\Data\Tax;

/**
 * Nexus interface.
 * @api
 */
interface NexusInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Get nexus ID.
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set nexus ID.
     *
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get nexus API ID.
     *
     * @return int|null
     */
    public function getApiId();

    /**
     * Set nexus API ID.
     *
     * @param int $apiId
     * @return $this
     */
    public function setApiId($apiId);

    /**
     * Get nexus street.
     *
     * @return string
     */
    public function getStreet();

    /**
     * Set nexus street.
     *
     * @param string $street
     * @return $this
     */
    public function setStreet($street);

    /**
     * Get nexus city.
     *
     * @return string
     */
    public function getCity();

    /**
     * Set nexus city.
     *
     * @param string $city
     * @return $this
     */
    public function setCity($city);

    /**
     * Get nexus country ID.
     *
     * @return int|null
     */
    public function getCountryId();

    /**
     * Set nexus country ID.
     *
     * @param int $countryId
     * @return $this
     */
    public function setCountryId($countryId);

    /**
     * Get region.
     *
     * @return string
     */
    public function getRegion();

    /**
     * Set region.
     *
     * @param string $region
     * @return $this
     */
    public function setRegion($region);

    /**
     * Get region ID.
     *
     * @return int|null
     */
    public function getRegionId();

    /**
     * Set region ID.
     *
     * @param int $regionId
     * @return $this
     */
    public function setRegionId($regionId);

    /**
     * Get region code.
     *
     * @return int|null
     */
    public function getRegionCode();

    /**
     * Set region code.
     *
     * @param int $regionCode
     * @return $this
     */
    public function setRegionCode($regionCode);

    /**
     * Get postal code.
     *
     * @return string
     */
    public function getPostcode();

    /**
     * Set postal code.
     *
     * @param string $postcode
     * @return $this
     */
    public function setPostcode($postcode);

    /**
     * Get store ID.
     *
     * @return int
     */
    public function getStoreId();

    /**
     * Set store ID.
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId);

    /**
     * Set created at.
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Get created at.
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Set updated at.
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Get updated at.
     *
     * @return string
     */
    public function getUpdatedAt();

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Taxjar\SalesTax\Api\Data\Tax\NexusExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Taxjar\SalesTax\Api\Data\Tax\NexusExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Taxjar\SalesTax\Api\Data\Tax\NexusExtensionInterface $extensionAttributes);
}
