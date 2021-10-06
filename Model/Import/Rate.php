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

namespace Taxjar\SalesTax\Model\Import;

use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

/**
 * Class used to create Rates or entries on the `tax_calculation_rate` table
 * used in TaxJar's Backup Rates feature.
 */
class Rate
{
    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $cache;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Tax\Model\Calculation\RateFactory
     */
    protected $rateFactory;

    /**
     * @var \Magento\Tax\Api\TaxRateRepositoryInterface
     */
    protected $rateService;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Tax\Model\Calculation\Rule
     */
    protected $rule;

    /**
     * @param CacheInterface $cache
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Tax\Model\Calculation\RateFactory $rateFactory
     * @param \Magento\Tax\Model\CalculationFactory $calculationFactory
     * @param TaxRateRepositoryInterface $rateService
     * @param RegionFactory $regionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param \Magento\Tax\Model\Calculation\Rule $rule
     */
    public function __construct(
        CacheInterface $cache,
        ScopeConfigInterface $scopeConfig,
        \Magento\Tax\Model\Calculation\RateFactory $rateFactory,
        \Magento\Tax\Model\CalculationFactory $calculationFactory,
        TaxRateRepositoryInterface $rateService,
        RegionFactory $regionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        \Magento\Tax\Model\Calculation\Rule $rule
    ) {
        $this->cache = $cache;
        $this->scopeConfig = $scopeConfig;
        $this->_calculationFactory = $calculationFactory;
        $this->rateFactory = $rateFactory;
        $this->rateService = $rateService;
        $this->regionFactory = $regionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->rule = $rule;

        return $this;
    }

    /**
     * Attempt to create a new rate from data.
     * Should return a tuple containing rate model's ID and shipping rate's ID.
     *
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            $rate = $data['rate'];
            $zip = $data['zip'];
            $regionCode = $data['state'];
            $countryCode = $data['country'] ?? 'US';

            if ($this->cache->load('regionId')
                && $regionCode == $this->cache->load('regionCode')
                && $countryCode == $this->cache->load('countryCode')
            ) {
                $regionId = $this->cache->load('regionId');
            } else {
                $region = $this->regionFactory->create();
                $regionId = $region->loadByCode($regionCode, $countryCode)->getId();
                $this->cache->save($regionId, 'regionId');
                $this->cache->save($regionCode, 'regionCode');
                $this->cache->save($countryCode, 'countryCode');
            }

            $code = sprintf('%s-%s-%s', $countryCode, $regionCode, $zip);

            $rateModel = $this->rateFactory->create();

            if ($rateModel->load($code, 'code')->getId()) {
                $rateModel->setRate($rate);
            } else {
                $rateModel->setTaxCountryId($countryCode);
                $rateModel->setTaxRegionId($regionId);
                $rateModel->setTaxPostcode($zip);
                $rateModel->setCode($code);
                $rateModel->setRate($rate);
            }

            $rateModel->save();

            $shippingRateId = $data['freight_taxable'] ? $rateModel->getId() : 0;

            return [$rateModel->getId(), $shippingRateId];
        } catch (\Exception $e) {
            unset($rateModel);
            return [null, null];
        }
    }

    /**
     * Get related Calculation Rule object
     *
     * @return \Magento\Tax\Model\Calculation\Rule
     */
    public function getRule(): \Magento\Tax\Model\Calculation\Rule
    {
        return $this->rule;
    }

    /**
     * Get existing TaxJar rates based on configuration states
     *
     * @return array
     */
    public function getExistingRates(): array
    {
        return array_unique(
            $this->getRule()->load(TaxjarConfig::TAXJAR_BACKUP_RATE_CODE, 'code')->getRates()
        );
    }

    /**
     * Get existing TaxJar rule calculations based on the rate ID
     *
     * @param string $rateId
     * @return AbstractDb|AbstractCollection|null
     */
    public function getCalculationsByRateId(string $rateId)
    {
        $calculationModel = $this->_calculationFactory->create();
        return $calculationModel
            ->getCollection()
            ->addFieldToFilter('tax_calculation_rate_id', $rateId);
    }
}
