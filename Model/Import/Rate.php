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
 * @copyright  Copyright (c) 2016 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Model\Import;

use Magento\Framework\App\CacheInterface;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class Rate
{
    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $_cache;
    
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    
    /**
     * @var \Magento\Tax\Model\Calculation\RateFactory
     */
    protected $_rateFactory;
    
    /**
     * @var \Magento\Tax\Api\TaxRateRepositoryInterface
     */
    protected $_rateService;
    
    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $_regionFactory;
    
    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $_filterBuilder;
    
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;
    
    /**
     * @param CacheInterface $cache
     * @param ScopeConfigInterface $scopeConfig
     * @param MagentoTaxModelCalculationRateFactory $rateFactory
     * @param MagentoTaxModelCalculationFactory $calculationFactory
     * @param TaxRateRepositoryInterface $rateService
     * @param RegionFactory  $regionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     */
    public function __construct(
        CacheInterface $cache,
        ScopeConfigInterface $scopeConfig,
        \Magento\Tax\Model\Calculation\RateFactory $rateFactory,
        \Magento\Tax\Model\CalculationFactory $calculationFactory,
        TaxRateRepositoryInterface $rateService,
        RegionFactory $regionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder
    ) {
        $this->_cache = $cache;
        $this->_scopeConfig = $scopeConfig;
        $this->_calculationFactory = $calculationFactory;
        $this->_rateFactory = $rateFactory;
        $this->_rateService = $rateService;
        $this->_regionFactory = $regionFactory;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_filterBuilder = $filterBuilder;
        return $this;
    }
    
    /**
     * Attempt to create a new rate from JSON data
     *
     * @param string $rateJson
     * @return array
     */
    public function create($rateJson)
    {
        try {
            $zip        = $rateJson['zip'];
            $regionCode = $rateJson['state'];
            $rate       = $rateJson['rate'];

            if (isset($rateJson['country'])) {
                $countryCode = $rateJson['country'];
            } else {
                $countryCode = 'US';
            }

            if ($this->_cache->load('regionId') && $regionCode == $this->_cache->load('regionCode') && $countryCode == $this->_cache->load('countryCode')) {
                $regionId = $this->_cache->load('regionId');
            } else {
                $region = $this->_regionFactory->create();
                $regionId = $region->loadByCode($regionCode, $countryCode)->getId();
                $this->_cache->save($regionId, 'regionId');
                $this->_cache->save($regionCode, 'regionCode');
                $this->_cache->save($countryCode, 'countryCode');
            }

            $rateModel = $this->_rateFactory->create();
            $rateModel->setTaxCountryId($countryCode);
            $rateModel->setTaxRegionId($regionId);
            $rateModel->setTaxPostcode($zip);
            $rateModel->setCode($countryCode . '-' . $regionCode . '-' . $zip);
            $rateModel->setRate($rate);
            $rateModel->save();

            if ($rateJson['freight_taxable']) {
                $shippingRateId = $rateModel->getId();
            } else {
                $shippingRateId = 0;
            }

            return [$rateModel->getId(), $shippingRateId];
        } catch (\Exception $e) {
            unset($rateModel);
            return;
        }
    }
    
    /**
     * Get existing TaxJar rates based on configuration states
     *
     * @return array
     */
    public function getExistingRates()
    {
        $filter = $this->_filterBuilder
            ->setField('tax_region_id')
            ->setValue($this->getRegionFilter())
            ->setConditionType('in')
            ->create();
        $searchCriteria = $this->_searchCriteriaBuilder->addFilters([$filter])->create();

        return $this->_rateService->getList($searchCriteria);
    }
    
    /**
     * Get existing TaxJar rule calculations based on the rate ID
     *
     * @param string $rateId
     * @return array
     */
    public function getCalculationsByRateId($rateId)
    {
        $calculationModel = $this->_calculationFactory->create();
        $calculations = $calculationModel->getCollection()
                        ->addFieldToFilter('tax_calculation_rate_id', $rateId);
        
        return $calculations;
    }

    /**
     * Get region filter for existing configuration states
     *
     * @return array
     */
    private function getRegionFilter()
    {
        $filter = [];
        $states = unserialize($this->_scopeConfig->getValue(TaxjarConfig::TAXJAR_STATES));
        $region = $this->_regionFactory->create();

        foreach (array_unique($states) as $state) {
            $regionId = $region->loadByCode($state, 'US')->getId();
            $filter[] = $regionId;
        }

        return $filter;
    }
}
