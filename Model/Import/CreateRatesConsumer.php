<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Model\Import;

use Exception;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\LoggerInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class CreateRatesConsumer extends AbstractRatesConsumer
{
    /**
     * @var array
     */
    private $newRates;

    /**
     * @var array
     */
    private $newShippingRates;

    /**
     * @var array
     */
    private $customerTaxClasses;

    /**
     * @var array
     */
    private $productTaxClasses;

    /**
     * @var string
     */
    private $shippingTaxClass;

    /**
     * @var CollectionFactory
     */
    private $configCollection;

    public function __construct(
        SerializerInterface $serializer,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        EntityManager $entityManager,
        TaxjarConfig $taxjarConfig,
        RateFactory $rateFactory,
        RuleFactory $ruleFactory,
        CollectionFactory $configCollection
    ) {
        parent::__construct(
            $serializer,
            $scopeConfig,
            $logger,
            $entityManager,
            $taxjarConfig,
            $rateFactory,
            $ruleFactory
        );

        $this->configCollection = $configCollection;
    }

    /**
     * @param string[] $values List of configured customer tax class names
     * @return $this
     */
    private function setCustomerTaxClasses(array $values): self
    {
        $this->customerTaxClasses = $values;

        return $this;
    }

    /**
     * @param string[] $values List of configured product tax class names
     * @return $this
     */
    private function setProductTaxClasses(array $values): self
    {
        $this->productTaxClasses = $values;

        return $this;
    }

    /**
     * @param string $value Configured shipping tax class name
     * @return $this
     */
    private function setShippingTaxClass(string $value): self
    {
        $this->shippingTaxClass = $value;

        return $this;
    }

    /**
     * @return $this
     */
    protected function setMemberData(): self
    {
        return $this->setRates($this->payload['rates'])
            ->setProductTaxClasses($this->payload['product_tax_classes'])
            ->setCustomerTaxClasses($this->payload['customer_tax_classes'])
            ->setShippingTaxClass($this->payload['shipping_tax_class']);
    }

    /**
     * @throws LocalizedException | Exception
     */
    protected function handle(): self
    {
        return $this->reset()
            ->validate()
            ->setMemberData()
            ->createRates()
            ->createRules();
    }

    /**
     * Reset member variables as class is not re-instantiated between operations
     */
    private function reset(): self
    {
        $this->newRates = $this->newShippingRates = [];

        return $this;
    }

    /**
     * Validate that backup rates can be imported
     *
     * @throws LocalizedException
     */
    private function validate(): self
    {
        return $this->validatePayload()
            ->validateBackupRatesEnabled()
            ->validateAPIKey();
    }

    /**
     * Validate that operation payload is an array containing the expected keys
     *
     * @throws LocalizedException
     */
    private function validatePayload(): self
    {
        if (is_array($this->payload)
            && array_key_exists('rates', $this->payload)
            && array_key_exists('product_tax_classes', $this->payload)
            && array_key_exists('customer_tax_classes', $this->payload)
            && array_key_exists('shipping_tax_class', $this->payload)
        ) {
            return $this;
        }

        throw new LocalizedException(__('Consumer %1 received invalid payload.', self::class));
    }

    /**
     * Validate that settings are configured to allow backup rates
     *
     * @throws LocalizedException
     */
    private function validateBackupRatesEnabled(): self
    {
        if ($this->backupRatesEnabled()) {
            return $this;
        }

        throw new LocalizedException(__('Backup rates are not enabled.'));
    }

    /**
     * Validate API key is set
     *
     * @throws LocalizedException
     */
    private function validateApiKey(): self
    {
        if ($this->taxjarConfig->getApiKey()) {
            return $this;
        }

        throw new LocalizedException(__('TaxJar account is not linked or API Token is invalid.'));
    }

    /**
     * Create new tax rates
     *
     * @return self
     */
    private function createRates(): self
    {
        $rate = $this->rateFactory->create();

        foreach ($this->rates as $newRate) {
            [$rateId, $shippingRateId] = $rate->create($newRate);

            if ($rateId) {
                $this->newRates[] = $rateId;
            }

            if ($shippingRateId) {
                $this->newShippingRates[] = $shippingRateId;
            }
        }

        return $this;
    }

    /**
     * Create or update existing tax rules with new rates
     *
     * @return self
     * @throws Exception
     */
    private function createRules(): self
    {
        $rule = $this->ruleFactory->create();

        $ruleModel = $rule->create(
            TaxjarConfig::TAXJAR_BACKUP_RATE_CODE,
            $this->customerTaxClasses,
            $this->productTaxClasses,
            1,
            $this->newRates
        );

        if ($this->shippingTaxClass) {
            $rule->create(
                TaxjarConfig::TAXJAR_BACKUP_RATE_CODE . ' (Shipping)',
                $this->customerTaxClasses,
                [$this->shippingTaxClass],
                2,
                $this->newShippingRates
            );
        }

        $this->purgeStaleCalculations($ruleModel);

        return $this;
    }

    /**
     * Deletes existing calculation entries that are no longer related to a configured
     * product or customer tax class.
     *
     * @param $ruleModel
     */
    private function purgeStaleCalculations($ruleModel): void
    {
        $productTaxClassIds = $this->productTaxClasses;

        if ($this->shippingTaxClass) {
            $productTaxClassIds[] = $this->shippingTaxClass;
        }

        $productCalculations = $ruleModel->getCalculationModel()->getCollection()
            ->addFieldToFilter('product_tax_class_id', ['nin' => $productTaxClassIds])
            ->getItems();

        foreach ($productCalculations as $calculation) {
            $calculation->delete();
        }

        $customerCalculations = $ruleModel->getCalculationModel()->getCollection()
            ->addFieldToFilter('customer_tax_class_id', ['nin' => $this->customerTaxClasses])
            ->getItems();

        foreach ($customerCalculations as $calculation) {
            $calculation->delete();
        }
    }

    /**
     * Return boolean value whether TaxJar extension's Backup Rates feature is enabled.
     *
     * @return bool
     */
    private function backupRatesEnabled(): bool
    {
        $collection = $this->configCollection->create();

        $config = $collection
            ->addFieldToFilter("path", TaxjarConfig::TAXJAR_BACKUP)
            ->getFirstItem();

        return (bool) (int) $config->getData('value');
    }
}
