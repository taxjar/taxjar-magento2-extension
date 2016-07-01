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

namespace Taxjar\SalesTax\Block\Adminhtml;

use Magento\Directory\Model\RegionFactory;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Backend\Model\UrlInterface;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class Backup extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Taxjar_SalesTax::backup.phtml';
    
    /**
     * @var \Magento\Framework\Config\CacheInterface
     */
    protected $_cache;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $_backendUrl;

    /**
     * @var \Magento\Tax\Api\TaxRateRepositoryInterface
     */
    protected $_rateService;

    /**
     * @var \Taxjar\SalesTax\Model\Import\RateFactory
     */
    protected $_importRateFactory;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $_regionFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $_filterBuilder;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;
    
    /**
     * @param Context $context
     * @param TaxjarSalesTaxModelImportRateFactory $importRateFactory
     * @param TaxRateRepositoryInterface $rateService
     * @param RegionFactory $regionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param UrlInterface $backendUrl
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Taxjar\SalesTax\Model\Import\RateFactory $importRateFactory,
        TaxRateRepositoryInterface $rateService,
        RegionFactory $regionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        UrlInterface $backendUrl,
        array $data = []
    ) {
        $this->_cache = $context->getCache();
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_rateService = $rateService;
        $this->_importRateFactory = $importRateFactory;
        $this->_regionFactory = $regionFactory;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_filterBuilder = $filterBuilder;
        $this->_backendUrl = $backendUrl;

        $region = $this->_regionFactory->create();
        $regionId = $this->_scopeConfig->getValue('shipping/origin/region_id');
        $this->_regionCode = $region->load($regionId)->getCode();
        
        parent::__construct($context, $data);
    }
    
    /**
     * Get the element HTML
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $apiKey = trim($this->_scopeConfig->getValue(TaxjarConfig::TAXJAR_APIKEY));
        
        if ($apiKey) {
            $this->_cacheElementValue($element);
        }

        return parent::_getElementHtml($element) . $this->_toHtml();
    }
    
    /**
     * Cache the element value
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return void
     */
    protected function _cacheElementValue(AbstractElement $element)
    {
        $elementValue = (string) $element->getValue();
        $this->_cache->save($elementValue, 'taxjar_salestax_config_backup');
    }
    
    /**
     * Backup rates enabled check
     *
     * @return bool
     */
    public function isEnabled()
    {
        $isEnabled = $this->_scopeConfig->getValue(TaxjarConfig::TAXJAR_BACKUP); 

        if ($isEnabled) {
            return true;
        }

        return false;
    }

    /**
     * Build HTML list of states
     *
     * @param string $states
     * @param string $regionCode
     * @return string
     */
    public function getStateList()
    {
        $states = unserialize($this->_scopeConfig->getValue(TaxjarConfig::TAXJAR_STATES));
        $states[] = $this->_regionCode;
        $statesHtml = '';
        $lastUpdate = $this->_scopeConfig->getValue(TaxjarConfig::TAXJAR_LAST_UPDATE);

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
        return $this->_backendUrl->getUrl($route, $params);
    }
    
    /**
     * Get region name from region code
     *
     * @param string $regionCode
     * @return string
     */
    private function _getStateName($regionCode)
    {
        $region = $this->_regionFactory->create();
        return $region->loadByCode($regionCode, 'US')->getDefaultName();
    }
    
    /**
     * Generate state list item
     * 
     * @param array $taxRatesByState
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
            $region = $this->_regionFactory->create();
            $regionId = $region->loadByCode($state, 'US')->getId();
            $filter = $this->_filterBuilder
                ->setField('tax_region_id')
                ->setValue($regionId)
                ->create();
            $searchCriteria = $this->_searchCriteriaBuilder->addFilters([$filter])->create();
            
            $ratesByState[$state] = $this->_rateService->getList($searchCriteria)->getTotalCount();
        }
        
        $importRateModel = $this->_importRateFactory->create();

        $rateCalcs = [
            'total_rates' => array_sum($ratesByState),
            'rates_loaded' => $importRateModel->getExistingRates()->getTotalCount(),
            'rates_by_state' => $ratesByState
        ];

        return $rateCalcs;
    }
}
