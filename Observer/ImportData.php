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
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Taxjar\SalesTax\Model\ClientFactory;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\ConfigurationFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImportData implements ObserverInterface
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
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

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
     * @var TaxjarConfig
     */
    protected $taxjarConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $resourceConfig
     * @param RegionFactory $regionFactory
     * @param ClientFactory $clientFactory
     * @param ConfigurationFactory $configFactory
     * @param ReinitableConfigInterface $reinitableConfig
     * @param TaxjarConfig $taxjarConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Config $resourceConfig,
        RegionFactory $regionFactory,
        ClientFactory $clientFactory,
        ConfigurationFactory $configFactory,
        ReinitableConfigInterface $reinitableConfig,
        TaxjarConfig $taxjarConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
        $this->regionFactory = $regionFactory;
        $this->clientFactory = $clientFactory;
        $this->configFactory = $configFactory;
        $this->reinitableConfig = $reinitableConfig;
        $this->taxjarConfig = $taxjarConfig;
        $this->apiKey = $this->taxjarConfig->getApiKey();
    }

    /**
     * @param  Observer $observer
     * @return $this
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    // @codingStandardsIgnoreStart
    public function execute(Observer $observer)
    {
        // @codingStandardsIgnoreEnd
        $region = $this->_getShippingRegion();

        if ($this->apiKey) {
            $this->client = $this->clientFactory->create();

            if ($region->getCode()) {
                $this->_setConfiguration();
            } else {
                throw new LocalizedException(__('Please check that you have set a Region/State in Shipping Settings.'));
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
        $region = $this->regionFactory->create();
        $regionId = $this->scopeConfig->getValue(
            \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_REGION_ID
        );
        $region->load($regionId);
        return $region;
    }

    /**
     * Get TaxJar user account configuration
     *
     * @return string
     */
    private function _getConfigJson()
    {
        $configJson = $this->client->getResource('config');
        return $configJson['configuration'];
    }

    /**
     * Set TaxJar config
     *
     * @return void
     */
    private function _setConfiguration()
    {
        $config = $this->configFactory->create();
        $configJson = $this->_getConfigJson();

        $config->setTaxBasis($configJson);
        $config->setDisplaySettings();

        $this->resourceConfig->saveConfig(
            TaxjarConfig::TAXJAR_STATES,
            json_encode(explode(',', $configJson['states'])),
            'default',
            0
        );
        $this->resourceConfig->saveConfig(
            TaxjarConfig::TAXJAR_FREIGHT_TAXABLE,
            $configJson['freight_taxable'],
            'default',
            0
        );
        $this->reinitableConfig->reinit();
    }
}
