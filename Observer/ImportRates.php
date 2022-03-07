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
 * @category Taxjar
 * @package Taxjar_SalesTax
 * @copyright Copyright (c) 2017 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace Taxjar\SalesTax\Observer;

use Magento\Framework\Bulk\OperationInterface as OperationInterfaceAlias;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\AsynchronousOperations\Model\Operation;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use \Magento\Framework\App\Cache\Type\Config as CacheTypeConfig;
use Magento\Framework\App\Cache\Manager as CacheManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Tax\Model\Calculation\RateRepository;
use Magento\Tax\Model\Config as MagentoTaxConfig;
use Symfony\Component\HttpFoundation\Response;
use Taxjar\SalesTax\Api\Client\ClientInterface;
use Taxjar\SalesTax\Model\BackupRateOriginAddress;
use Taxjar\SalesTax\Model\Client;
use Taxjar\SalesTax\Model\ClientFactory;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\Import\Rate;
use Taxjar\SalesTax\Model\Import\RateFactory;
use Taxjar\SalesTax\Model\Import\RuleFactory;

class ImportRates implements ObserverInterface
{
    /**
     * The default batch size used for bulk operations in `ImportRates::class`
     */
    private const BATCH_SIZE = 1000;

    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     * @var MessageManagerInterface
     */
    private $messageManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ResourceConfig
     */
    private $resourceConfig;

    /**
     * @var RateFactory
     */
    private $rateFactory;

    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    /**
     * @var RateRepository
     */
    private $rateRepository;

    /**
     * @var TaxjarConfig
     */
    private $taxjarConfig;

    /**
     * @var BackupRateOriginAddress
     */
    private $backupRateOriginAddress;

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
     * @var ClientInterface
     */
    public $client;

    /**
     * @var array|null
     */
    private $customerTaxClasses;

    /**
     * @var array|null
     */
    private $productTaxClasses;

    /**
     * @var string|null
     */
    private $shippingTaxClass;

    /**
     * @var string|null
     */
    private $zipCode;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @param EventManagerInterface $eventManager
     * @param MessageManagerInterface $messageManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ResourceConfig $resourceConfig
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
     * @param CacheManager $cacheManager
     */
    public function __construct(
        EventManagerInterface $eventManager,
        MessageManagerInterface $messageManager,
        ScopeConfigInterface $scopeConfig,
        ResourceConfig $resourceConfig,
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
        UserContextInterface $userContext,
        CacheManager $cacheManager
    ) {
        $this->eventManager = $eventManager;
        $this->messageManager = $messageManager;
        $this->scopeConfig = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
        $this->client = $clientFactory->create();
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
        $this->cacheManager = $cacheManager;
    }

    /**
     * Retrieve date string in required format for config update
     * @return string
     */
    private function getDate(): string
    {
        return date('m-d-Y');
    }

    /**
     * @param string $value A comma-delimited string of customer tax classes
     * @return self
     */
    private function setCustomerTaxClasses(string $value): self
    {
        $this->customerTaxClasses = array_filter(
            explode(',', $value)
        );

        return $this;
    }

    /**
     * @param string $value A comma-delimited string of product tax classes
     * @return self
     */
    private function setProductTaxClasses(string $value): self
    {
        $this->productTaxClasses = array_filter(
            explode(',', $value)
        );

        return $this;
    }

    /**
     * @param string $value Magento store's configured shipping tax class
     * @return self
     */
    private function setShippingTaxClass(string $value): self
    {
        $this->shippingTaxClass = $value;

        return $this;
    }

    /**
     * @param string $value The Magento store's shipping zip code
     * @return self
     */
    private function setZipCode(string $value): self
    {
        $this->zipCode = $value;

        return $this;
    }

    /**
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $updated = null;
        $rates = $codes = [];

        $isActive = $this->backupRatesEnabled() && $this->taxjarConfig->getApiKey();

        if ($isActive) {
            $this->setConfiguration();
            $this->validateConfiguration();

            $rates = $this->getRates();
            $updated = $this->getDate();
        }

        $rateCount = count($rates);

        if ($rateCount) {
            foreach($rates as $rate) {
                $codes[] = self::getRateCode($rate);
            }
        }

        if ($this->debugEnabled()) {
            $this->messageManager->addNoticeMessage(
                __('Debug mode enabled. Backup tax rates have not been altered.')
            );
            return;
        }

        $this->setRateCount($rateCount);
        $this->setLastUpdate($updated);

        $this->resourceConfig->saveConfig(TaxjarConfig::TAXJAR_BACKUP, (int) $isActive);

        $this->upsertRates($rates);
        $this->purgeRates($codes);

        if ($rateCount === 0) {
            $this->messageManager->addNoticeMessage(
                __('Backup rates imported by TaxJar have been queued for removal.')
            );
        }

        $this->cacheManager->flush([CacheTypeConfig::TYPE_IDENTIFIER]);
        $this->eventManager->dispatch('taxjar_salestax_import_rates_after');
    }

    /**
     * @throws LocalizedException
     */
    protected function upsertRates(array $rates)
    {
        if (! empty($rates)) {
            $this->createRates($rates);

            $this->messageManager->addSuccessMessage(
                __('TaxJar has successfully queued backup tax rate sync. Thanks for using TaxJar!')
            );
        }
    }

    /**
     * @throws LocalizedException
     * @throws \Exception
     */
    protected function purgeRates(array $codes)
    {
        /** @var Rate $rateModel */
        $rateModel = $this->rateFactory->create();

        // filter to only rates where code not in $codes
        $nonInclusiveRates = array_filter($rateModel->getExistingRates(), function ($rate) use ($codes) {
            return (! in_array($this->rateRepository->get($rate)->getCode(), $codes));
        });

        if (! empty($nonInclusiveRates)) {
            $this->scheduleBulkOperation(
                $this->getDeleteRatesPayload($nonInclusiveRates),
                TaxjarConfig::TAXJAR_TOPIC_DELETE_RATES,
                'Delete TaxJar backup tax rates.'
            );
        }
    }

    private function setConfiguration(): void
    {
        $zipCode = $this->backupRateOriginAddress->getShippingZipCode();
        $customerTaxClassConfig = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_CUSTOMER_TAX_CLASSES);
        $productTaxClassConfig = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_PRODUCT_TAX_CLASSES);
        $shippingTaxClass = $this->scopeConfig->getValue(MagentoTaxConfig::CONFIG_XML_PATH_SHIPPING_TAX_CLASS);

        $this->setZipCode($zipCode)
            ->setCustomerTaxClasses($customerTaxClassConfig)
            ->setProductTaxClasses($productTaxClassConfig)
            ->setShippingTaxClass($shippingTaxClass);
    }

    /**
     * @throws LocalizedException
     */
    private function validateConfiguration(): void
    {
        $this->validateZipCode()
            ->validateTaxClasses()
            ->validateShippingClass();
    }

    private static function getRateCode($rateData): string
    {
        return sprintf(
            '%s-%s-%s',
            $rateData['country'] ?? 'US',
            $rateData['state'],
            $rateData['zip']
        );
    }

    /**
     * Execute observer action during cron job.
     *
     * @return void
     * @throws LocalizedException
     */
    public function cron(): void
    {
        $this->execute(new Observer());
    }

    /**
     * Return boolean value whether TaxJar extension's Backup Rates feature is enabled.
     *
     * @return bool
     */
    private function backupRatesEnabled(): bool
    {
        return (bool) (int) $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_BACKUP);
    }

    /**
     * Return boolean value whether TaxJar extension's debug mode is enabled.
     *
     * @return bool
     */
    private function debugEnabled(): bool
    {
        return (bool) (int) $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_DEBUG);
    }

    /**
     * Create asynchronous operation for provided topic with an initial status of "open".
     *
     * @param string $bulkUuid Unique identifier for the current topic instance
     * @param string $topic Message queue topic
     * @param array $payload Topic data for serialization
     * @return OperationInterface
     */
    private function createOperation(string $bulkUuid, string $topic, array $payload): OperationInterface
    {
        $data = [
            'data' => [
                'bulk_uuid' => $bulkUuid,
                'topic_name' => $topic,
                'serialized_data' => $this->serializer->serialize($payload),
                'status' => OperationInterfaceAlias::STATUS_TYPE_OPEN,
            ]
        ];

        return $this->operationFactory->create($data);
    }

    /**
     * Retrieve chunked payload for topic `taxjar.backup_rates.delete` with given array of rate IDs.
     *
     * @param array $rates List of `tax_calculation_rates` IDs
     * @return array[]
     */
    private function getDeleteRatesPayload(array $rates): array
    {
        $data = [];

        foreach (array_chunk($rates, self::BATCH_SIZE) as $ratesChunk) {
            $data[] = [
                'rates' => $ratesChunk,
            ];
        }

        return $data;
    }

    /**
     * Retrieve chunked payload for topic `taxjar.backup_rates.create` with given array of rate IDs.
     *
     * @param array $rates List of `tax_calculation_rates` IDs
     * @return array[]
     */
    private function getCreateRatesPayload(array $rates): array
    {
        $data = [];

        foreach (array_chunk($rates, self::BATCH_SIZE) as $ratesChunk) {
            $data[] = [
                'rates' => $ratesChunk,
                'product_tax_classes' => $this->productTaxClasses,
                'customer_tax_classes' => $this->customerTaxClasses,
                'shipping_tax_class' => $this->shippingTaxClass,
            ];
        }

        return $data;
    }

    /**
     * Schedule asynchronous operation(s) with the given UUID and description.
     *
     * @param string $uuid Unique identifier for the current topic instance
     * @param array|Operation[] $operations List of operations to be scheduled
     * @param string $description User-friendly description of the bulk operation
     * @return bool
     */
    private function schedule(string $uuid, array $operations, string $description): bool
    {
        $userId = $this->userContext->getUserId();
        return $this->bulkManagement->scheduleBulk($uuid, $operations, $description, $userId);
    }

    /**
     * Updates the current backup rate count stored in global config.
     *
     * @param int $value
     * @return void
     */
    private function setRateCount(int $value): void
    {
        $this->taxjarConfig->setBackupRateCount($value);
    }

    /**
     * Schedule bulk operation(s) to asynchronously create `tax_calculation_rates` entries and relate rates to new
     * TaxJar `tax_calculation_rules` entry by way of `tax_calculations` entries.
     *
     * @param array $rates List of rates to create
     * @return ImportRates
     * @throws LocalizedException
     */
    private function createRates(array $rates): self
    {
        $payload = $this->getCreateRatesPayload($rates);

        $this->scheduleBulkOperation(
            $payload,
            TaxjarConfig::TAXJAR_TOPIC_CREATE_RATES,
            'Create TaxJar backup tax rates.'
        );

        return $this;
    }

    /**
     * Creates and schedules bulk operation(s) prescribed by input topic.
     *
     * @param array[] $data List of operation payloads
     * @param string $topic Message queue topic name
     * @param string $description User-friendly description of the bulk operation
     * @throws LocalizedException
     */
    private function scheduleBulkOperation(array $data, string $topic, string $description): void
    {
        $operations = [];
        $bulkUuid = $this->identityService->generateId();

        foreach ($data as $datum) {
            $operations[] = $this->createOperation($bulkUuid, $topic, $datum);
        }

        if (! empty($operations)) {
            $result = $this->schedule($bulkUuid, $operations, $description);

            if (! $result) {
                throw new LocalizedException(
                    __('Something went wrong while processing the request.')
                );
            }
        }
    }

    /**
     * Retrieve TaxJar backup rates from client response.
     *
     * @return array
     */
    private function getRates(): array
    {
        $rates = $this->client->getResource('rates', [
            Response::HTTP_FORBIDDEN => __(
                'Your last backup rate sync from TaxJar was too recent. ' .
                'Please wait at least 5 minutes and try again.'
            )
        ]);

        return $rates['rates'];
    }

    /**
     * Set the last updated value in framework configuration.
     *
     * @param string|null $value Value to set as last update; Either a date in "m-d-Y" or NULL
     * @return void
     */
    private function setLastUpdate(?string $value): void
    {
        $this->resourceConfig->saveConfig(TaxjarConfig::TAXJAR_LAST_UPDATE, $value);
    }

    /**
     * Throw exception when zip code is determined to be invalid.
     *
     * A valid shipping address zip code is required for backup rate accuracy.
     *
     * @throws LocalizedException
     */
    private function validateZipCode(): self
    {
        if ($this->zipCodeIsValid()) {
            return $this;
        }

        throw new LocalizedException(
            __('Please check that your zip code is a valid US zip code in Shipping Settings.')
        );
    }

    /**
     * Return boolean representing whether the zip code is valid.
     *
     * A valid zip code for US is determined by format Zip 5 or Zip 5+4 while all other regions only validate the
     * existence of a value for the parameter.
     *
     * @return bool
     */
    private function zipCodeIsValid(): bool
    {
        if ($this->backupRateOriginAddress->isScopeCountryCodeUS()) {
            return $this->zipCode && preg_match("/^\d{5}(-?\d{4})?$/", (string) $this->zipCode);
        }

        return (bool) $this->zipCode;
    }

    /**
     * Throw exception when product tax or customer tax classes are not selected.
     *
     * At least one product tax class and one customer tax class are required to properly store backup rates.
     *
     * @return self
     * @throws LocalizedException
     */
    private function validateTaxClasses(): self
    {
        if (! empty($this->productTaxClasses) && ! empty($this->customerTaxClasses)) {
            return $this;
        }

        throw new LocalizedException(
            __(
                'Please select at least one product tax class and one customer tax class to ' .
                'configure backup rates from TaxJar.'
            )
        );
    }

    /**
     * Throws exception if selected shipping tax class exists in product tax classes array.
     *
     * Shipping tax class must be unique from product tax classes.
     *
     * @return self
     * @throws LocalizedException
     */
    private function validateShippingClass(): self
    {
        if ($this->shippingTaxClass && in_array($this->shippingTaxClass, $this->productTaxClasses)) {
            throw new LocalizedException(
                __('For backup shipping rates, please use a unique tax class for shipping.')
            );
        }

        return $this;
    }
}
