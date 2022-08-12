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

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Taxjar\SalesTax\Block\Adminhtml\Backup\CustomerTaxClassSelect;
use Taxjar\SalesTax\Block\Adminhtml\Backup\ProductTaxClassSelect;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class ConfigChanged implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $cache;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param CacheInterface $cache
     * @param ManagerInterface $eventManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        CacheInterface $cache,
        ManagerInterface $eventManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->cache = $cache;
        $this->eventManager = $eventManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param Observer $observer
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    // @codingStandardsIgnoreStart
    public function execute(Observer $observer)
    {
        // @codingStandardsIgnoreEnd
        $this->_updateSmartcalcs();
        $this->_updateBackupRates();
        return $this;
    }

    /**
     * @return void
     */
    private function _updateSmartcalcs()
    {
        $currentValue = (int) $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_ENABLED);
        $previousValue = (int) $this->cache->load('taxjar_salestax_config_enabled');

        if ($currentValue === 1 && $currentValue !== $previousValue) {
            $this->eventManager->dispatch('taxjar_salestax_import_categories');
            $this->eventManager->dispatch('taxjar_salestax_import_data');
        }
    }

    /**
     * @return void
     */
    private function _updateBackupRates()
    {
        if ($this->backupRatesEnabledChanged()) {
            $this->eventManager->dispatch('taxjar_salestax_import_categories');
            $this->eventManager->dispatch('taxjar_salestax_import_data');
            $this->eventManager->dispatch('taxjar_salestax_import_rates');

            return;
        }

        $enabled = (bool) (int) $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_BACKUP);

        if ($enabled && ($this->productTaxClassesChanged() || $this->customerTaxClassesChanged())) {
            $this->eventManager->dispatch('taxjar_salestax_import_categories');
            $this->eventManager->dispatch('taxjar_salestax_import_data');
            $this->eventManager->dispatch('taxjar_salestax_import_rates');
        }
    }

    private function backupRatesEnabledChanged(): bool
    {
        $current = (bool) (int) $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_BACKUP);
        $previous = (bool) (int) $this->cache->load('taxjar_salestax_config_backup');

        return $previous != $current;
    }

    private function productTaxClassesChanged(): bool
    {
        $current = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_PRODUCT_TAX_CLASSES);
        $previous = $this->cache->load(ProductTaxClassSelect::CACHE_IDENTIFIER) ?? '';

        return $current != $previous;
    }

    private function customerTaxClassesChanged(): bool
    {
        $current = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_CUSTOMER_TAX_CLASSES);
        $previous = $this->cache->load(CustomerTaxClassSelect::CACHE_IDENTIFIER) ?? '';

        return $current != $previous;
    }
}
