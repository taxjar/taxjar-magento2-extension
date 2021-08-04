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

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Tax\Model\Calculation\RateRepository;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Authorization\Model\UserContextInterface;
use Taxjar\SalesTax\Model\BackupRateOriginAddress;
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
     * @var BackupRateOriginAddress
     */
    protected $backupRateOriginAddress;

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
     * @var integer
     */
    private $batchSize;

    /**
     * @var IdentityGeneratorInterface
     */
    private $identityService;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var OperationInterfaceFactory
     */
    private $operationFactory;

    /**
     * @var BulkManagementInterface
     */
    private $bulkManagement;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @param ManagerInterface $eventManager
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $resourceConfig
     * @param ClientFactory $clientFactory
     * @param RateFactory $rateFactory
     * @param RuleFactory $ruleFactory
     * @param RateRepository $rateRepository
     * @param TaxjarConfig $taxjarConfig
     * @param BackupRateOriginAddress $backupRateOriginAddress
     * @param IdentityGeneratorInterface $identityService
     * @param SerializerInterface $serializer
     * @param OperationInterfaceFactory $operationFactory
     * @param BulkManagementInterface $bulkManagement
     * @param UserContextInterface $userContext
     */
    public function __construct(
        ManagerInterface $eventManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        ScopeConfigInterface $scopeConfig,
        Config $resourceConfig,
        ClientFactory $clientFactory,
        RateFactory $rateFactory,
        RuleFactory $ruleFactory,
        RateRepository $rateRepository,
        TaxjarConfig $taxjarConfig,
        BackupRateOriginAddress $backupRateOriginAddress,
        IdentityGeneratorInterface $identityService,
        SerializerInterface $serializer,
        OperationInterfaceFactory $operationFactory,
        BulkManagementInterface $bulkManagement,
        UserContextInterface $userContext
    ) {
        $this->eventManager = $eventManager;
        $this->messageManager = $messageManager;
        $this->scopeConfig = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
        $this->clientFactory = $clientFactory;
        $this->rateFactory = $rateFactory;
        $this->ruleFactory = $ruleFactory;
        $this->rateRepository = $rateRepository;
        $this->taxjarConfig = $taxjarConfig;
        $this->backupRateOriginAddress = $backupRateOriginAddress;
        $this->identityService = $identityService;
        $this->serializer = $serializer;
        $this->operationFactory = $operationFactory;
        $this->bulkManagement = $bulkManagement;
        $this->userContext = $userContext;
        $this->apiKey = $this->taxjarConfig->getApiKey();
        $this->batchSize = 1000;
    }

    /**
     * @param Observer $observer
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws LocalizedException
     */
    // @codingStandardsIgnoreStart
    public function execute(Observer $observer)
    {
        // @codingStandardsIgnoreEnd
        $isEnabled = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_BACKUP);

        if ($isEnabled && $this->apiKey) {
            $this->client = $this->clientFactory->create();
            $this->storeZip = $this->backupRateOriginAddress->getShippingZipCode();
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
                $this->purgeRates();
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
     * @throws LocalizedException
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
            $this->messageManager->addNoticeMessage(
                __('Debug mode enabled. Backup tax rates have not been altered.')
            );
            return;
        }

        if (! $this->isZipCodeValid()) {
            // @codingStandardsIgnoreStart
            throw new LocalizedException(
                __('Please check that your zip code is a valid US zip code in Shipping Settings.')
            );
            // @codingStandardsIgnoreEnd
        }

        if (! count($this->productTaxClasses) || ! count($this->customerTaxClasses)) {
            // @codingStandardsIgnoreStart
            throw new LocalizedException(
                __('Please select at least one product tax class and one customer tax class to import backup rates from TaxJar.')
            );
            // @codingStandardsIgnoreEnd
        }

        $ratesJson = $this->_getRatesJson();

        $this->shippingClass = $this->scopeConfig->getValue('tax/classes/shipping_tax_class');

        if ($this->shippingClass && in_array($this->shippingClass, $this->productTaxClasses)) {
            throw new LocalizedException(
                __('For backup shipping rates, please use a unique tax class for shipping.')
            );
        }

        $this->purgeRates();
        $this->createRates($ratesJson['rates']);

        $this->_setLastUpdateDate(date('m-d-Y'));

        $this->messageManager->addSuccessMessage(
            __('TaxJar has added new rates to your database. Thanks for using TaxJar!')
        );

        $this->eventManager->dispatch('taxjar_salestax_import_rates_after');

    }

    /**
     * Build asynchronous operation
     *
     * @param array $rates
     * @param int $bulkUuid
     *
     * @return OperationInterface
     */
    private function makeOperation($rates, $bulkUuid, $topic): OperationInterface
    {
        $dataToEncode = [
            'rates' => $rates,
            'product_tax_classes' => $this->productTaxClasses,
            'customer_tax_classes' => $this->customerTaxClasses,
            'shipping_class' => $this->shippingClass,
        ];

        $data = [
            'data' => [
                'bulk_uuid' => $bulkUuid,
                'topic_name' => $topic,
                'serialized_data' => $this->serializer->serialize($dataToEncode),
                'status' => \Magento\Framework\Bulk\OperationInterface::STATUS_TYPE_OPEN,
            ]
        ];

        return $this->operationFactory->create($data);
    }

    /**
     * @param string $bulkUuid
     * @param array $operations
     * @param string $bulkDescription
     * @throws LocalizedException
     */
    private function schedule($bulkUuid, $operations, $bulkDescription): void
    {
        $userId = $this->userContext->getUserId();
        $result = $this->bulkManagement->scheduleBulk($bulkUuid, $operations, $bulkDescription, $userId);

        if (! $result) {
            throw new LocalizedException(
                __('Something went wrong while processing the request.')
            );
        }
    }

    /**
     * Purge existing rule calculations and rates
     *
     * @return void
     * @throws LocalizedException
     */
    private function purgeRates()
    {
        $rateModel = $this->rateFactory->create();
        $rates = $rateModel->getExistingRates();

        if ($rule = $rateModel->getRule()) {
            $rule->getCalculationModel()->deleteByRuleId($rule->getId());
            $rule->delete();
        }

        if (! empty($rates)) {
            $chunkedRatesForDeletion = array_chunk($rates, $this->batchSize);
            $bulkUuid = $this->identityService->generateId();

            $operations = [];
            foreach ($chunkedRatesForDeletion as $rateDeleteChunk) {
                $operations[] = $this->makeOperation(
                    $rateDeleteChunk,
                    $bulkUuid,
                    TaxjarConfig::TAXJAR_TOPIC_DELETE_RATES
                );
            }

            if (! empty($operations)) {
                $bulkDescription = __('Delete %1 TaxJar backup tax rates.', $this->batchSize);
                $this->schedule($bulkUuid, $operations, $bulkDescription);
            }
        }
    }

    private function createRates(array $rates)
    {
        $rateChunks = array_chunk($rates, $this->batchSize);
        $bulkUuid = $this->identityService->generateId();

        $operations = [];
        foreach ($rateChunks as $rateChunk) {
            $operations[] = $this->makeOperation(
                $rateChunk,
                $bulkUuid,
                TaxjarConfig::TAXJAR_TOPIC_CREATE_RATES
            );
        }

        if (!empty($operations)) {
            $bulkDescription = __('Create ' . $this->batchSize . ' TaxJar backup tax rates.');
            $this->schedule($bulkUuid, $operations, $bulkDescription);
        }
    }

    /**
     * Get TaxJar backup rates
     *
     * @return array
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
     * Set the last updated date
     *
     * @param string $date
     * @return void
     */
    private function _setLastUpdateDate($date)
    {
        $this->resourceConfig->saveConfig(TaxjarConfig::TAXJAR_LAST_UPDATE, $date, 'default', 0);
    }

    /**
     * Checks whether the zip code is valid to import rates
     *
     * @return bool
     */
    private function isZipCodeValid() {
        $is_valid = false;

        if ($this->backupRateOriginAddress->isScopeCountryCodeUS()) {
            if ($this->storeZip && preg_match("/(\d{5}-\d{4})|(\d{5})/", $this->storeZip)){
                $is_valid = true;
            }
        } else {
            if ($this->storeZip) {
                $is_valid = true;
            }
        }

       return $is_valid;
    }
}
