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
 * @copyright  Copyright (c) 2022 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Phrase;
use Magento\Shipping\Model\Config as MagentoShippingConfig;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Magento\Backend\Model\UrlInterface;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\Import\RateFactory;

class Backup extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Taxjar_SalesTax::backup.phtml';

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
    protected $rateFactory;

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
     * @param CacheInterface $cache
     * @param Context $context
     * @param RateFactory $rateFactory
     * @param TaxRateRepositoryInterface $rateService
     * @param RegionFactory $regionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param UrlInterface $backendUrl
     * @param TaxjarConfig $taxjarConfig
     * @param array $data
     */
    public function __construct(
        CacheInterface $cache,
        Context $context,
        RateFactory $rateFactory,
        TaxRateRepositoryInterface $rateService,
        RegionFactory $regionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        UrlInterface $backendUrl,
        TaxjarConfig $taxjarConfig,
        array $data = []
    ) {
        $this->cache = $cache;
        $this->scopeConfig = $context->getScopeConfig();
        $this->rateService = $rateService;
        $this->rateFactory = $rateFactory;
        $this->regionFactory = $regionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->backendUrl = $backendUrl;
        $this->taxjarConfig = $taxjarConfig;
        $this->apiKey = $taxjarConfig->getApiKey();

        $region = $this->regionFactory->create();
        $regionId = $this->scopeConfig->getValue(MagentoShippingConfig::XML_PATH_ORIGIN_REGION_ID);
        $this->_regionCode = $region->load($regionId)->getCode();

        parent::__construct($context, $data);
    }

    /**
     * Get the element HTML
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
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
    public function isEnabled(): bool
    {
        return (bool) (int) $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_BACKUP);
    }

    /**
     * Get rate import progress text
     *
     * @return Phrase
     */
    public function getRatesLoadedText(): Phrase
    {
        return __('%1 of %2 expected rates loaded.', $this->getActualRateCount(), $this->getExpectedRateCount());
    }

    /**
     * Get count of TaxJar rates in database
     *
     * @return int
     */
    public function getActualRateCount(): int
    {
        $rateModel = $this->rateFactory->create();
        $rates = $rateModel->getExistingRates() ?? [];

        return count($rates) ?? 0;
    }

    /**
     * Get expected rate count from config
     *
     * @return int
     */
    public function getExpectedRateCount(): int
    {
        return $this->taxjarConfig->getBackupRateCount();
    }

    /**
     * Get last synced date
     *
     * @return Phrase
     */
    public function getLastSyncedDateText(): Phrase
    {
        return __('Last synced on %1', $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_LAST_UPDATE) ?? 'N/A');
    }

    /**
     * Get store URL
     *
     * @param string $route
     * @param array|null $params
     * @return string
     */
    public function getStoreUrl(string $route, ?array $params = []): string
    {
        return $this->backendUrl->getUrl($route, $params);
    }
}
