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

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Filesystem\DirectoryList;
use Taxjar\SalesTax\Model\ClientFactory;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\ConfigurationFactory;
use Taxjar\SalesTax\Model\Import\RateFactory;
use Taxjar\SalesTax\Model\Import\RuleFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImportRates implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Config\ReinitableConfigInterface
     */
    protected $_eventManager;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $_resourceConfig;

    /**
     * @var \Taxjar\SalesTax\Model\ClientFactory
     */
    protected $_clientFactory;

    /**
     * @var \Taxjar\SalesTax\Model\Import\RateFactory
     */
    protected $_rateFactory;

    /**
     * @var \Taxjar\SalesTax\Model\Import\RuleFactory
     */
    protected $_ruleFactory;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_directoryList;

    /**
     * @var string
     */
    protected $_apiKey;

    /**
     * @var \Taxjar\SalesTax\Model\ClientFactory
     */
    protected $_client;

    /**
     * @var string
     */
    protected $_storeZip;

    /**
     * @var array
     */
    protected $_customerTaxClasses;

    /**
     * @var array
     */
    protected $_productTaxClasses;

    /**
     * @var array
     */
    protected $_newRates = [];

    /**
     * @var array
     */
    protected $_newShippingRates = [];
    
    /**
     * @param ManagerInterface $eventManager
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $resourceConfig
     * @param ClientFactory $clientFactory
     * @param RateFactory $rateFactory
     * @param RuleFactory $ruleFactory
     * @param DirectoryList $directoryList
     */
    public function __construct(
        ManagerInterface $eventManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        ScopeConfigInterface $scopeConfig,
        Config $resourceConfig,
        ClientFactory $clientFactory,
        RateFactory $rateFactory,
        RuleFactory $ruleFactory,
        DirectoryList $directoryList
    ) {
        $this->_eventManager = $eventManager;
        $this->_messageManager = $messageManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_resourceConfig = $resourceConfig;
        $this->_clientFactory = $clientFactory;
        $this->_rateFactory = $rateFactory;
        $this->_ruleFactory = $ruleFactory;
        $this->_directoryList = $directoryList;
    }
    
    /**
     * @param Observer $observer
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Observer $observer)
    {
        $isEnabled = $this->_scopeConfig->getValue(TaxjarConfig::TAXJAR_BACKUP);
        $this->_apiKey = trim($this->_scopeConfig->getValue(TaxjarConfig::TAXJAR_APIKEY));
        
        if ($isEnabled && $this->_apiKey) {
            $this->_client = $this->_clientFactory->create();
            $this->_storeZip = trim($this->_scopeConfig->getValue('shipping/origin/postcode'));
            $this->_customerTaxClasses = explode(',', $this->_scopeConfig->getValue(TaxjarConfig::TAXJAR_CUSTOMER_TAX_CLASSES));
            $this->_productTaxClasses = explode(',', $this->_scopeConfig->getValue(TaxjarConfig::TAXJAR_PRODUCT_TAX_CLASSES));
            $this->_importRates();
        } else {
            $states = unserialize($this->_scopeConfig->getValue(TaxjarConfig::TAXJAR_STATES));
            
            if (!empty($states)) {
                $this->_purgeRates();
            }

            $this->_setLastUpdateDate(null);
            $this->_resourceConfig->saveConfig(TaxjarConfig::TAXJAR_BACKUP, 0, 'default', 0);
            $this->_messageManager->addNotice(__('Backup rates imported by TaxJar have been removed.'));
        }

        $this->_eventManager->dispatch('adminhtml_cache_flush_all');

        return $this;
    }
    
    /**
     * Execute observer action during cron job
     *
     * @return void
     */
    public function cron()
    {
        $this->execute(new Observer);
    }
    
    /**
     * Import tax rates from TaxJar
     *
     * @return void
     */
    private function _importRates()
    {
        $isDebugMode = $this->_scopeConfig->getValue(TaxjarConfig::TAXJAR_DEBUG);

        if ($isDebugMode) {
            $this->_messageManager->addNotice(__('Debug mode enabled. Backup tax rates have not been altered.'));
            return;
        }

        if ($this->_storeZip && preg_match("/(\d{5}-\d{4})|(\d{5})/", $this->_storeZip)) {
            $ratesJson = $this->_getRatesJson();
        } else {
            throw new \Magento\Framework\Validator\Exception(__('Please check that your zip code is a valid US zip code in Shipping Settings.'));
        }
        
        if (!count($this->_productTaxClasses) || !count($this->_customerTaxClasses)) {
            throw new \Magento\Framework\Validator\Exception(__('Please select at least one product tax class and one customer tax class to import backup rates from TaxJar.'));
        }

        // Purge existing TaxJar rates and remove from rules
        $this->_purgeRates();

        try {
            if (file_put_contents($this->_getTempRatesFileName(), serialize($ratesJson)) !== false) {
                // This process can take awhile
                @set_time_limit(0);
                @ignore_user_abort(true);
                
                $filename = $this->_getTempRatesFileName();
                $ratesJson = unserialize(file_get_contents($filename));

                // Create new TaxJar rates and rules
                $this->_createRates($ratesJson);
                $this->_createRules();
                $this->_setLastUpdateDate(date('m-d-Y'));

                @unlink($filename);

                $this->_messageManager->addSuccess(__('TaxJar has added new rates to your database. Thanks for using TaxJar!'));
                $this->_eventManager->dispatch('taxjar_salestax_import_rates_after');
            }
        } catch (\Exception $e) {
            throw new \Magento\Framework\Validator\Exception(__('Could not write to your Magento temp directory under /var/tmp. Please make sure the directory is created and check permissions for %1.', $this->_directoryList->getPath('tmp')));
        }
    }
    
    /**
     * Create new tax rates
     *
     * @param array $ratesJson
     * @return void
     */
    private function _createRates($ratesJson)
    {
        $rate = $this->_rateFactory->create();

        foreach ($ratesJson['rates'] as $rateJson) {
            $rateIdWithShippingId = $rate->create($rateJson);

            if ($rateIdWithShippingId[0]) {
                $this->_newRates[] = $rateIdWithShippingId[0];
            }

            if ($rateIdWithShippingId[1]) {
                $this->_newShippingRates[] = $rateIdWithShippingId[1];
            }
        }
    }
    
    /**
     * Create or update existing tax rules with new rates
     *
     * @return void
     */
    private function _createRules()
    {
        $rule = $this->_ruleFactory->create();
        $productTaxClasses = $this->_productTaxClasses;
        $shippingClass = $this->_scopeConfig->getValue('tax/classes/shipping_tax_class');
        $backupShipping = in_array($shippingClass, $productTaxClasses);
        
        if ($backupShipping) {
            $productTaxClasses = array_diff($productTaxClasses, [$shippingClass]);
        }

        $rule->create('TaxJar Backup Rates', $this->_customerTaxClasses, $productTaxClasses, 1, $this->_newRates);
        
        if ($backupShipping) {
            $rule->create('TaxJar Backup Rates (Shipping)', $this->_customerTaxClasses, [$shippingClass], 2, $this->_newShippingRates);    
        }
    }
    
    /**
     * Purge existing rule calculations and rates
     *
     * @return void
     */
    private function _purgeRates()
    {
        $rateModel = $this->_rateFactory->create();
        $rates = $rateModel->getExistingRates();
        
        if (!$rates->getTotalCount()) {
            return;
        }

        foreach ($rates->getItems() as $rate) {
            $calculations = $rateModel->getCalculationsByRateId($rate->getId());
            
            try {
                foreach ($calculations->getItems() as $calculation) {
                    $calculation->delete();
                }
            } catch (\Exception $e) {
                $this->_messageManager->addError($e->getMessage());
            }

            try {
                $rate->delete();
            } catch (\Exception $e) {
                $this->_messageManager->addError($e->getMessage());
            }
        }
    }
    
    /**
     * Get TaxJar backup rates
     *
     * @return string
     */
    private function _getRatesJson()
    {
        $ratesJson = $this->_client->getResource($this->_apiKey, 'rates', [
            '403' => __('Your last backup rate sync from TaxJar was too recent. Please wait at least 5 minutes and try again.')
        ]);
        return $ratesJson;
    }

    /**
     * Get the temp rates filename
     *
     * @return string
     */
    private function _getTempRatesFileName()
    {
        return $this->_directoryList->getPath('tmp') . DIRECTORY_SEPARATOR . 'tj_tmp.dat';
    }
    
    /**
     * Set the last updated date
     *
     * @param string $date
     * @return void
     */
    private function _setLastUpdateDate($date)
    {
        $this->_resourceConfig->saveConfig(TaxjarConfig::TAXJAR_LAST_UPDATE, $date, 'default', 0);
    }
}
