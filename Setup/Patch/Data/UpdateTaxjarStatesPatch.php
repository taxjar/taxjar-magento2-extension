<?php

namespace Taxjar\SalesTax\Setup\Patch\Data;

use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\ModuleResource;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Taxjar\SalesTax\Model\ClientFactory;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\Logger;

class UpdateTaxjarStatesPatch implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var \Taxjar\SalesTax\Model\Client
     */
    private $client;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    private $resourceConfig;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ClientFactory $clientFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $resourceConfig
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ClientFactory $clientFactory,
        ScopeConfigInterface $scopeConfig,
        Config $resourceConfig
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->client = $clientFactory->create();
        $this->scopeConfig = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        if ($this->scopeConfig->getValue(TaxjarConfig::TAXJAR_APIKEY)) {
            try {
                $configJson = $this->client->getResource('config');
                if (is_array($configJson) &&
                    isset($configJson['configuration']) &&
                    isset($configJson['configuration']['states'])
                ) {
                    $explodedConfigStates = explode(',', $configJson['configuration']['states']);
                    $this->resourceConfig->saveConfig(
                        TaxjarConfig::TAXJAR_STATES,
                        json_encode($explodedConfigStates),
                        'default'
                    );
                }
            } catch (LocalizedException $e) { // phpcs:ignore
                // no-op
            }

        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [
            AddExtensionAttributesPatch::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
