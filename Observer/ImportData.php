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
 
namespace Taxjar\SalesTax\Observer;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Event\Observer;
use Taxjar\SalesTax\Model\ClientFactory;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\ConfigurationFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImportData implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Config\CacheInterface
     */
    protected $_cache;
    
    /**
     * @var \Magento\Framework\App\Config\ReinitableConfigInterface
     */
    protected $_eventManager;
    
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $_resourceConfig;
    
    /**
     * @var \Magento\Framework\App\Config\ReinitableConfigInterface
     */
    protected $_reinitableConfig;
    
    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $_regionFactory;
    
    /**
     * @var \Taxjar\SalesTax\Model\ClientFactory
     */
    protected $_clientFactory;
    
    /**
     * @var \Taxjar\SalesTax\Model\ConfigurationFactory
     */
    protected $_configFactory;
    
    /**
     * @var string
     */
    protected $_apiKey;
    
    /**
     * @var string
     */
    protected $_client;
    
    /**
     * @param CacheInterface $cache
     * @param ManagerInterface $eventManager
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $resourceConfig
     * @param RegionFactory $regionFactory
     * @param ClientFactory $clientFactory
     * @param ConfigurationFactory $configFactory
     * @param ReinitableConfigInterface $reinitableConfig
     */
    public function __construct(
        CacheInterface $cache,
        ManagerInterface $eventManager,
        ScopeConfigInterface $scopeConfig,
        Config $resourceConfig,
        RegionFactory $regionFactory,
        ClientFactory $clientFactory,
        ConfigurationFactory $configFactory,
        ReinitableConfigInterface $reinitableConfig
    ) {
        $this->_cache = $cache;
        $this->_eventManager = $eventManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_resourceConfig = $resourceConfig;
        $this->_regionFactory = $regionFactory;
        $this->_clientFactory = $clientFactory;
        $this->_configFactory = $configFactory;
        $this->_reinitableConfig = $reinitableConfig;
    }
    
    /**
     * @param  Observer $observer
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Observer $observer)
    {
        $this->_apiKey = trim($this->_scopeConfig->getValue(TaxjarConfig::TAXJAR_APIKEY));
        $region = $this->_getShippingRegion();
        
        if ($this->_apiKey) {
            $this->_client = $this->_clientFactory->create();
            
            if ($region->getCode()) {
                $this->_setConfiguration();
            } else {
                throw new \Magento\Framework\Validator\Exception(__('Please check that you have set a Region/State in Shipping Settings.'));
            }
        }

        return $this;
    }
    
    /**
     * Get shipping region
     *
     * @return string
     */
    private function _getShippingRegion()
    {
        $region = $this->_regionFactory->create();
        $regionId = $this->_scopeConfig->getValue(
            \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_REGION_ID
        );
        $region->load($regionId);
        return $region;
    }
    
    /**
     * Get TaxJar product categories
     *
     * @return string
     */
    private function _getCategoryJson()
    {
        $categoryJson = $this->_client->getResource($this->_apiKey, 'categories');
        return $categoryJson['categories'];
    }
    
    /**
     * Get TaxJar user account configuration
     *
     * @return string
     */
    private function _getConfigJson()
    {
        $configJson = $this->_client->getResource($this->_apiKey, 'config');
        return $configJson['configuration'];
    }
    
    /**
     * Set TaxJar config
     *
     * @param array $configJson
     * @return void
     */
    private function _setConfiguration()
    {
        $config = $this->_configFactory->create();
        $configJson = $this->_getConfigJson();
        $categoryJson = $this->_getCategoryJson();

        $config->setTaxBasis($configJson);
        $config->setDisplaySettings();
        
        $this->_resourceConfig->saveConfig(TaxjarConfig::TAXJAR_CATEGORIES, json_encode($categoryJson), 'default', 0);
        $this->_resourceConfig->saveConfig(TaxjarConfig::TAXJAR_STATES, serialize(explode(',', $configJson['states'])), 'default', 0);
        $this->_resourceConfig->saveConfig(TaxjarConfig::TAXJAR_FREIGHT_TAXABLE, $configJson['freight_taxable'], 'default', 0);
        $this->_reinitableConfig->reinit();
    }
}