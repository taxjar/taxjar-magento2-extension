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

namespace Taxjar\SalesTax\Observer;

use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Taxjar\SalesTax\Model\ClientFactory;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\ConfigurationFactory;

class ImportCategories implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;

    /**
     * @var \Magento\Framework\App\Config\ReinitableConfigInterface
     */
    protected $reinitableConfig;

    /**
     * @var \Taxjar\SalesTax\Model\ClientFactory
     */
    protected $clientFactory;

    /**
     * @var \Taxjar\SalesTax\Model\ConfigurationFactory
     */
    protected $configFactory;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $client;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $resourceConfig
     * @param ClientFactory $clientFactory
     * @param ConfigurationFactory $configFactory
     * @param ReinitableConfigInterface $reinitableConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Config $resourceConfig,
        ClientFactory $clientFactory,
        ConfigurationFactory $configFactory,
        ReinitableConfigInterface $reinitableConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
        $this->clientFactory = $clientFactory;
        $this->configFactory = $configFactory;
        $this->reinitableConfig = $reinitableConfig;
    }

    /**
     * @param  Observer $observer
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    // @codingStandardsIgnoreStart
    public function execute(Observer $observer)
    {
        // @codingStandardsIgnoreEnd
        $this->apiKey = trim($this->scopeConfig->getValue(TaxjarConfig::TAXJAR_APIKEY));

        if ($this->apiKey) {
            $this->client = $this->clientFactory->create();
            $this->_importCategories();
        }

        return $this;
    }

    /**
     * Get TaxJar product categories
     *
     * @return string
     */
    private function _getCategoryJson()
    {
        $categoryJson = $this->client->getResource('categories');
        return $categoryJson['categories'];
    }

    /**
     * Import TaxJar product categories
     *
     * @return void
     */
    private function _importCategories()
    {
        $categoryJson = $this->_getCategoryJson();
        $this->resourceConfig->saveConfig(TaxjarConfig::TAXJAR_CATEGORIES, json_encode($categoryJson), 'default', 0);
        $this->reinitableConfig->reinit();
    }
}
