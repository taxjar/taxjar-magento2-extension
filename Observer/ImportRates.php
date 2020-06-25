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
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File as DriverFile;
use Magento\Tax\Model\Calculation\RateRepository;
use Taxjar\SalesTax\Model\ClientFactory;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\Import\RateFactory;
use Taxjar\SalesTax\Model\Import\RuleFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImportRates implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;

    /**
     * @var \Taxjar\SalesTax\Model\ClientFactory
     */
    protected $clientFactory;

    /**
     * @var \Taxjar\SalesTax\Model\Import\RateFactory
     */
    protected $rateFactory;

    /**
     * @var \Taxjar\SalesTax\Model\Import\RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $driverFile;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var \Taxjar\SalesTax\Model\ClientFactory
     */
    protected $client;

    /**
     * @var string
     */
    protected $storeZip;

    /**
     * @var array
     */
    protected $customerTaxClasses;

    /**
     * @var array
     */
    protected $productTaxClasses;

    /**
     * @var array
     */
    protected $newRates = [];

    /**
     * @var array
     */
    protected $newShippingRates = [];

    /**
     * @var RateRepository
     */
    protected $rateRepository;

    /**
     * @var TaxjarConfig
     */
    protected $taxjarConfig;

    /**
     * @param ManagerInterface $eventManager
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $resourceConfig
     * @param ClientFactory $clientFactory
     * @param RateFactory $rateFactory
     * @param RuleFactory $ruleFactory
     * @param DirectoryList $directoryList
     * @param DriverFile $driverFile
     * @param RateRepository $rateRepository
     * @param TaxjarConfig $taxjarConfig
     */
    public function __construct(
        ManagerInterface $eventManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        ScopeConfigInterface $scopeConfig,
        Config $resourceConfig,
        ClientFactory $clientFactory,
        RateFactory $rateFactory,
        RuleFactory $ruleFactory,
        DirectoryList $directoryList,
        DriverFile $driverFile,
        RateRepository $rateRepository,
        TaxjarConfig $taxjarConfig
    ) {
        $this->eventManager = $eventManager;
        $this->messageManager = $messageManager;
        $this->scopeConfig = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
        $this->clientFactory = $clientFactory;
        $this->rateFactory = $rateFactory;
        $this->ruleFactory = $ruleFactory;
        $this->directoryList = $directoryList;
        $this->driverFile = $driverFile;
        $this->rateRepository = $rateRepository;
        $this->taxjarConfig = $taxjarConfig;
        $this->apiKey = $this->taxjarConfig->getApiKey();
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
        $isEnabled = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_BACKUP);

        if ($isEnabled && $this->apiKey) {
            $this->client = $this->clientFactory->create();
            $this->storeZip = trim($this->scopeConfig->getValue('shipping/origin/postcode'));
            $this->customerTaxClasses = explode(
                ',',
                $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_CUSTOMER_TAX_CLASSES)
            );
            $this->productTaxClasses = explode(
                ',',
                $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_PRODUCT_TAX_CLASSES)
            );
            $this->_importRates();
        } else {
            $states = json_decode($this->scopeConfig->getValue(TaxjarConfig::TAXJAR_STATES), true);

            if (!empty($states)) {
                $this->_purgeRates();
            }

            $this->_setLastUpdateDate(null);
            $this->resourceConfig->saveConfig(TaxjarConfig::TAXJAR_BACKUP, 0, 'default', 0);
            $this->messageManager->addNoticeMessage(__('Backup rates imported by TaxJar have been removed.'));
        }

        $this->eventManager->dispatch('adminhtml_cache_flush_all');

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
     * @throws LocalizedException
     */
    private function _importRates()
    {
        $isDebugMode = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_DEBUG);

        if ($isDebugMode) {
            $this->messageManager->addNoticeMessage(__('Debug mode enabled. Backup tax rates have not been altered.'));
            return;
        }

        if ($this->storeZip && preg_match("/(\d{5}-\d{4})|(\d{5})/", $this->storeZip)) {
            $ratesJson = $this->_getRatesJson();
        } else {
            // @codingStandardsIgnoreStart
            throw new LocalizedException(__('Please check that your zip code is a valid US zip code in Shipping Settings.'));
            // @codingStandardsIgnoreEnd
        }

        if (!count($this->productTaxClasses) || !count($this->customerTaxClasses)) {
            // @codingStandardsIgnoreStart
            throw new LocalizedException(__('Please select at least one product tax class and one customer tax class to import backup rates from TaxJar.'));
            // @codingStandardsIgnoreEnd
        }

        // Purge existing TaxJar rates and remove from rules
        $this->_purgeRates();

        try {
            $dir = $this->directoryList->getPath(DirectoryList::TMP);

            if (!$this->driverFile->isDirectory($dir)) {
                $this->driverFile->createDirectory($dir);
            }

            if ($this->driverFile->filePutContents($this->_getTempRatesFileName(), json_encode($ratesJson)) !== false) {
                ignore_user_abort(true);

                $filename = $this->_getTempRatesFileName();
                $ratesJson = json_decode($this->driverFile->fileGetContents($filename), true);

                // Create new TaxJar rates and rules
                $this->_createRates($ratesJson);
                $this->_createRules();
                $this->_setLastUpdateDate(date('m-d-Y'));

                $this->driverFile->deleteFile($filename);

                $this->messageManager->addSuccessMessage(
                    __('TaxJar has added new rates to your database. Thanks for using TaxJar!')
                );
                $this->eventManager->dispatch('taxjar_salestax_import_rates_after');
            }
        } catch (\Exception $e) {
            // @codingStandardsIgnoreStart
            throw new LocalizedException(__('Could not write to your Magento temp directory under /var/tmp. Please make sure the directory is created and check permissions for %1.', $this->directoryList->getPath('tmp')));
            // @codingStandardsIgnoreEnd
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
        $rate = $this->rateFactory->create();

        foreach ($ratesJson['rates'] as $rateJson) {
            $rateIdWithShippingId = $rate->create($rateJson);

            if ($rateIdWithShippingId[0]) {
                $this->newRates[] = $rateIdWithShippingId[0];
            }

            if ($rateIdWithShippingId[1]) {
                $this->newShippingRates[] = $rateIdWithShippingId[1];
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
        $rule = $this->ruleFactory->create();
        $productTaxClasses = $this->productTaxClasses;
        $shippingClass = $this->scopeConfig->getValue('tax/classes/shipping_tax_class');

        $rule->create(
            TaxjarConfig::TAXJAR_BACKUP_RATE_CODE,
            $this->customerTaxClasses,
            $productTaxClasses,
            1,
            $this->newRates
        );

        if ($shippingClass) {
            if (in_array($shippingClass, $productTaxClasses)) {
                // @codingStandardsIgnoreStart
                $this->messageManager->addErrorMessage(__('For backup shipping rates, please use a unique tax class for shipping.'));
                // @codingStandardsIgnoreEnd
            } else {
                $rule->create(
                    TaxjarConfig::TAXJAR_BACKUP_RATE_CODE . ' (Shipping)',
                    $this->customerTaxClasses,
                    [$shippingClass],
                    2,
                    $this->newShippingRates
                );
            }
        }
    }

    /**
     * Purge existing rule calculations and rates
     *
     * @return void
     */
    private function _purgeRates()
    {
        $rateModel = $this->rateFactory->create();
        $rates = $rateModel->getExistingRates();

        foreach ($rates as $rate) {
            $calculations = $rateModel->getCalculationsByRateId($rate);

            try {
                foreach ($calculations->getItems() as $calculation) {
                    // @codingStandardsIgnoreStart
                    $calculation->delete();
                    // @codingStandardsIgnoreEnd
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }

            try {
                $this->rateRepository->deleteById($rate);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
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
        // @codingStandardsIgnoreStart
        $ratesJson = $this->client->getResource(
            'rates',
            [
                '403' => __('Your last backup rate sync from TaxJar was too recent. Please wait at least 5 minutes and try again.')
            ]
        );
        // @codingStandardsIgnoreEnd
        return $ratesJson;
    }

    /**
     * Get the temp rates filename
     *
     * @return string
     */
    private function _getTempRatesFileName()
    {
        return $this->directoryList->getPath(DirectoryList::TMP) . DIRECTORY_SEPARATOR . 'tj_tmp.dat';
    }

    /**
     * Set the last updated date
     *
     * @param string $date
     * @return void
     */
    private function _setLastUpdateDate($date)
    {
        $this->resourceConfig->saveConfig(TaxjarConfig::TAXJAR_LAST_UPDATE, $date, 'default', 0);
    }
}
