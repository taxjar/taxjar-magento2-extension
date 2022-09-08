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

namespace Taxjar\SalesTax\Controller\Adminhtml\Config;

use Magento\Backend\App\Action\Context;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class Disconnect extends \Magento\Backend\App\AbstractAction
{
    public const ADMIN_RESOURCE = 'Magento_Tax::manage_tax';

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;

    /**
     * @var \Magento\Framework\App\Config\ReinitableConfigInterface
     */
    protected $reinitableConfig;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * @var \Taxjar\SalesTax\Model\ResourceModel\Tax\Category\CollectionFactory
     */
    protected $categoryCollection;

    /**
     * @var \Taxjar\SalesTax\Model\ResourceModel\Tax\Nexus\CollectionFactory
     */
    protected $nexusCollection;

    /**
     * @param Context $context
     * @param Config $resourceConfig
     * @param ReinitableConfigInterface $reinitableConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Taxjar\SalesTax\Model\ResourceModel\Tax\Category\CollectionFactory $categoryCollection
     * @param \Taxjar\SalesTax\Model\ResourceModel\Tax\Nexus\CollectionFactory $nexusCollection
     */
    public function __construct(
        Context $context,
        Config $resourceConfig,
        ReinitableConfigInterface $reinitableConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Taxjar\SalesTax\Model\ResourceModel\Tax\Category\CollectionFactory $categoryCollection,
        \Taxjar\SalesTax\Model\ResourceModel\Tax\Nexus\CollectionFactory $nexusCollection
    ) {
        $this->resourceConfig = $resourceConfig;
        $this->reinitableConfig = $reinitableConfig;
        $this->eventManager = $context->getEventManager();
        $this->storeManager = $storeManager;
        $this->categoryCollection = $categoryCollection;
        $this->nexusCollection = $nexusCollection;
        parent::__construct($context);
    }

    /**
     * Disconnect from TaxJar
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $this->_purgeConfiguration();
        $this->_purgeNexusAddresses();
        $this->_purgeProductTaxCategories();

        $this->messageManager->addSuccessMessage(__('Your TaxJar account has been disconnected.'));

        $this->_redirect('adminhtml/system_config/edit', ['section' => 'tax']);
    }

    /**
     * List of TaxJar core config values to remove on disconnect
     *
     * @return array|string[]
     */
    public function getPurgeableConfigurationPaths(): array
    {
        return [
            TaxjarConfig::TAXJAR_ADDRESS_VALIDATION,
            TaxjarConfig::TAXJAR_APIKEY,
            TaxjarConfig::TAXJAR_BACKUP,
            TaxjarConfig::TAXJAR_BACKUP_RATE_COUNT,
            TaxjarConfig::TAXJAR_CONNECTED,
            TaxjarConfig::TAXJAR_DEBUG,
            TaxjarConfig::TAXJAR_EMAIL,
            TaxjarConfig::TAXJAR_ENABLED,
            TaxjarConfig::TAXJAR_SANDBOX_APIKEY,
            TaxjarConfig::TAXJAR_SANDBOX_ENABLED,
            TaxjarConfig::TAXJAR_TRANSACTION_SYNC,
        ];
    }

    /**
     * Deletes TaxJar core config values for the input scope
     *
     * @param string $scope
     * @param int $scopeId
     *
     * @return void
     */
    private function _purgeScopeConfig(string $scope, int $scopeId): void
    {
        foreach ($this->getPurgeableConfigurationPaths() as $path) {
            $this->resourceConfig->deleteConfig($path, $scope, $scopeId);
        }
    }

    /**
     * Purge core configuration values on disconnect and re-init config.
     *
     * @return void
     */
    private function _purgeConfiguration(): void
    {
        $this->_purgeScopeConfig('default', 0);

        foreach ($this->storeManager->getWebsites() as $websiteId => $website) {
            $this->_purgeScopeConfig(\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES, $websiteId);
        }

        foreach ($this->storeManager->getStores() as $storeId => $store) {
            $this->_purgeScopeConfig(\Magento\Store\Model\ScopeInterface::SCOPE_STORES, $storeId);
        }

        $this->reinitableConfig->reinit();
    }

    /**
     * Purge nexus addresses on disconnect
     *
     * @return void
     */
    private function _purgeNexusAddresses(): void
    {
        $this->nexusCollection->create()->walk('delete');
    }

    /**
     * Purge product tax categories on disconnect
     *
     * @return void
     */
    private function _purgeProductTaxCategories(): void
    {
        $this->categoryCollection->create()->walk('delete');
    }
}
