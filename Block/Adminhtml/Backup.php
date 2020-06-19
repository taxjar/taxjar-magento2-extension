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

namespace Taxjar\SalesTax\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Magento\Backend\Model\UrlInterface;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\Import\RateFactory;

class Backup extends Field
{
    /**
     * @var string
     */
    // @codingStandardsIgnoreStart
    protected $_template = 'Taxjar_SalesTax::backup.phtml';
    // @codingStandardsIgnoreEnd

    /**
     * @var \Magento\Framework\Config\CacheInterface
     */
    protected $cache;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $backendUrl;

    /**
     * @var \Magento\Tax\Api\TaxRateRepositoryInterface
     */
    protected $rateService;

    /**
     * @var \Taxjar\SalesTax\Model\Import\RateFactory
     */
    protected $importRateFactory;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var TaxjarConfig
     */
    protected $taxjarConfig;

    /**
     * @param Context $context
     * @param RateFactory $importRateFactory
     * @param TaxRateRepositoryInterface $rateService
     * @param RegionFactory $regionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param UrlInterface $backendUrl
     * @param TaxjarConfig $taxjarConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        RateFactory $importRateFactory,
        TaxRateRepositoryInterface $rateService,
        RegionFactory $regionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        UrlInterface $backendUrl,
        TaxjarConfig $taxjarConfig,
        array $data = []
    ) {
        $this->cache = $context->getCache();
        $this->scopeConfig = $context->getScopeConfig();
        $this->rateService = $rateService;
        $this->importRateFactory = $importRateFactory;
        $this->regionFactory = $regionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->backendUrl = $backendUrl;
        $this->taxjarConfig = $taxjarConfig;
        $this->apiKey = $taxjarConfig->getApiKey();

        $region = $this->regionFactory->create();
        $regionId = $this->scopeConfig->getValue('shipping/origin/region_id');
        $this->_regionCode = $region->load($regionId)->getCode();

        parent::__construct($context, $data);
    }

    /**
     * Get the element HTML
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        if ($this->apiKey) {
            $this->_cacheElementValue($element);
        }

        return parent::_getElementHtml($element) . $this->_toHtml();
    }

    /**
     * Cache the element value
     *
     * @param AbstractElement $element
     * @return void
     */
    protected function _cacheElementValue(AbstractElement $element)
    {
        $elementValue = (string) $element->getValue();
        $this->cache->save($elementValue, 'taxjar_salestax_config_backup');
    }

    /**
     * Backup rates enabled check
     *
     * @return bool
     */
    public function isEnabled()
    {
        $isEnabled = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_BACKUP);

        if ($isEnabled) {
            return true;
        }

        return false;
    }

    /**
     * Build HTML list of states
     *
     * @return string
     */
    public function getStateList()
    {
        $states = json_decode($this->scopeConfig->getValue(TaxjarConfig::TAXJAR_STATES), true);
        $states[] = $this->_regionCode;
        $statesHtml = '';
        $lastUpdate = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_LAST_UPDATE);

        sort($states);

        $taxRatesByState = $this->_getNumberOfRatesLoaded($states);

        foreach (array_unique($states) as $state) {
            $statesHtml .= $this->_getStateListItem($taxRatesByState, $state);
        }

        if ($taxRatesByState['rates_loaded'] != $taxRatesByState['total_rates']) {
            $matches = 'error';
        } else {
            $matches = 'success';
        }

        $statesHtml .= '<p class="' . $matches . '-msg" style="background: none !important;">';
        $statesHtml .= '<small>&nbsp;&nbsp;' . number_format($taxRatesByState['total_rates']);
        $statesHtml .= ' of ' . number_format($taxRatesByState['rates_loaded']);
        $statesHtml .= ' expected rates loaded.</small></p>';

        if (!empty($lastUpdate)) {
            $statesHtml .= '<p class="' . $matches . '-msg" style="background: none !important;">';
            $statesHtml .= '<small>&nbsp;&nbsp;' . 'Last synced on ' . $lastUpdate . '.</small>';
            $statesHtml .= '</p><br/>';
        }

        return $statesHtml;
    }

    /**
     * Get store URL
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getStoreUrl($route, $params = [])
    {
        return $this->backendUrl->getUrl($route, $params);
    }

    /**
     * Get region name from region code
     *
     * @param string $regionCode
     * @return string
     */
    private function _getStateName($regionCode)
    {
        $region = $this->regionFactory->create();
        return $region->loadByCode($regionCode, 'US')->getDefaultName();
    }

    /**
     * Generate state list item
     *
     * @param array $rates
     * @param string $state
     * @return string
     */
    private function _getStateListItem($rates, $state)
    {
        $originResourceUrl = 'http://blog.taxjar.com/charging-sales-tax-rates/';
        $nexusUrl = $this->getUrl('taxjar/nexus/index');

        if (($name = $this->_getStateName($state)) && !empty($name)) {
            if ($rates['rates_by_state'][$state] == 1 && ($rates['rates_loaded'] == $rates['total_rates'])) {
                $class = 'success';
                $total = "1 rate (<a href='{$originResourceUrl}' target='_blank'>Origin-based</a>)";
            } elseif ($rates['rates_by_state'][$state] == 0 && ($rates['rates_loaded'] == $rates['total_rates'])) {
                $class = 'error';
                $total = "<a href='{$nexusUrl}'>Click here</a> and add a zip code for this state to load rates.";
            } else {
                $class = 'success';
                $total = number_format($rates['rates_by_state'][$state]) . ' rates';
            }

            $itemClass = "message message-{$class} {$class}";

            return "<div class='{$itemClass}'><span style='font-size: 1.2em'>{$name}</span>: {$total}</div>";
        }

        return '';
    }

    /**
     * Get the number of rates loaded
     *
     * @param array $states
     * @return array
     */
    private function _getNumberOfRatesLoaded($states)
    {
        $ratesByState = [];

        foreach (array_unique($states) as $state) {
            $region = $this->regionFactory->create();
            $regionId = $region->loadByCode($state, 'US')->getId();
            $filter = $this->filterBuilder
                ->setField('tax_region_id')
                ->setValue($regionId)
                ->create();
            $searchCriteria = $this->searchCriteriaBuilder->addFilters([$filter])->create();

            $ratesByState[$state] = $this->rateService->getList($searchCriteria)->getTotalCount();
        }

        $importRateModel = $this->importRateFactory->create();

        $rateCalcs = [
            'total_rates' => array_sum($ratesByState),
            'rates_loaded' => count($importRateModel->getExistingRates()),
            'rates_by_state' => $ratesByState
        ];

        return $rateCalcs;
    }
}
