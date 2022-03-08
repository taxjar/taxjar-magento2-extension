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
 * @copyright  Copyright (c) 2020 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Model;

use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

class BackupRateOriginAddress
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    private $scopeRegionCode;
    private $scopeCountryCode;
    private $scopeZipCode;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param RegionFactory $regionFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RegionFactory $regionFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->regionFactory = $regionFactory;

        $this->scopeRegionCode = $this->getShippingRegionCodeFromScope();
        $this->scopeCountryCode = $this->getShippingCountryCodeFromScope();
        $this->scopeZipCode = $this->getShippingZipCodeFromScope();
    }

    /**
     * Get shipping region code from scope
     *
     * @return string
     */
    private function getShippingRegionCodeFromScope()
    {
        $region = $this->regionFactory->create();
        $regionId = $this->scopeConfig->getValue(
            \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_REGION_ID
        );
        $region->load($regionId);
        return $region->getCode();
    }

    /**
     * Get shipping country code from scope
     *
     * @return string
     */
    private function getShippingCountryCodeFromScope()
    {
        $shippingCountryId = $this->scopeConfig->getValue(
            \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_COUNTRY_ID
        );
        return $shippingCountryId;
    }

    /**
     * Get shipping zip code from Scope
     *
     * @return string
     */
    private function getShippingZipCodeFromScope()
    {
        return trim((string) $this->scopeConfig->getValue('shipping/origin/postcode'));
    }

    /**
     * Get shipping region code to use for backup rates requests
     *
     * @return string
     */
    public function getShippingRegionCode()
    {
        if ($this->isScopeCountryCodeUS()) {
            return $this->scopeRegionCode;
        }

        return $this->getPlaceholderCodeForInternationalOriginAddress();
    }

    /**
     * Get shipping country code to use for backup rates requests
     * @return string
     */
    public function getShippingCountryCode()
    {
        return $this->scopeCountryCode;
    }

    /**
     * Get shipping zip code to use for backup rates requests
     *
     * @return string
     */
    public function getShippingZipCode()
    {
        if ($this->isScopeCountryCodeUS()) {
            return $this->scopeZipCode;
        }

        return $this->getPlaceholderCodeForInternationalOriginAddress();
    }

    /**
     * Determine if the origin country code for the scope is for the United States
     *
     * @return bool
     */
    public function isScopeCountryCodeUS()
    {
        return $this->scopeCountryCode === 'US';
    }

    /**
     * Get an invalid region or zip code
     *
     * @return string
     */
    private function getPlaceholderCodeForInternationalOriginAddress()
    {
        // Using a region code or zip code that doesn't exist in TaxJar for requests to the TaxJar API Magento
        // backup rates endpoints (plugins/magento/configuration and plugins/magento/rates) will cause
        // the endpoints to return the rates, sourcing, and freight taxability without performing any sourcing
        // overrides. This is necessary for providing backup US rates for stores with international from addresses.
        $invalidCode = 'AA';
        return $invalidCode;
    }
}
