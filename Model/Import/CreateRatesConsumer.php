<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Model\Import;

use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class CreateRatesConsumer extends AbstractRatesConsumer
{
    protected function setData(): void
    {
        $serializedData = $this->operation->getSerializedData();
        $data = $this->serializer->unserialize($serializedData);

        $this->rates = $data['rates'];
        $this->productTaxClasses = $data['product_tax_classes'];
        $this->customerTaxClasses = $data['customer_tax_classes'];
        $this->shippingClass = $data['shipping_class'];
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException | \Exception
     */
    protected function processOperation(): void
    {
        $this->createRates();
        $this->createRules();
    }

    /**
     * Create new tax rates
     *
     * @return void
     */
    private function createRates()
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
    }

    /**
     * Create or update existing tax rules with new rates
     *
     * @throws \Exception
     * @return void
     */
    private function createRules()
    {
        $rule = $this->ruleFactory->create();

        $rule->create(
            TaxjarConfig::TAXJAR_BACKUP_RATE_CODE,
            $this->customerTaxClasses,
            $this->productTaxClasses,
            1,
            $this->newRates
        );

        if ($this->shippingClass) {
            $rule->create(
                TaxjarConfig::TAXJAR_BACKUP_RATE_CODE . ' (Shipping)',
                $this->customerTaxClasses,
                [$this->shippingClass],
                2,
                $this->newShippingRates
            );
        }
    }
}
