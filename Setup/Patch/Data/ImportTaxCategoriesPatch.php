<?php

namespace Taxjar\SalesTax\Setup\Patch\Data;

use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class ImportTaxCategoriesPatch implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var Config
     */
    private $resourceConfig;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Config $resourceConfig
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Config $resourceConfig,
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $eventManager
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->resourceConfig = $resourceConfig;
        $this->scopeConfig = $scopeConfig;
        $this->eventManager = $eventManager;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        if ($this->scopeConfig->getValue('tax/taxjar/categories')) {
            $this->resourceConfig->deleteConfig('tax/taxjar/categories', 'default', '');
            $this->eventManager->dispatch('taxjar_salestax_import_categories');
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [
            UpdateTaxjarStatesPatch::class,
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
