<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Model\Import;

use Exception;
use Magento\Framework\Exception\LocalizedException;
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
            $rateIdWithShippingId = $rate->create($newRate);

            if ($rateIdWithShippingId[0]) {
                $this->newRates[] = $rateIdWithShippingId[0];
            }

            if ($rateIdWithShippingId[1]) {
                $this->newShippingRates[] = $rateIdWithShippingId[1];
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

        $rule->create(
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

        return $this;
    }

    /**
     * Return boolean value whether TaxJar extension's Backup Rates feature is enabled.
     *
     * @return bool
     */
    private function backupRatesEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_BACKUP);
    }
}
