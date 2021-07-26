<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Model\Import;

use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\EntityManager\EntityManager;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\Import\RateFactory;
use Taxjar\SalesTax\Model\Import\RuleFactory;

class Consumer
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var EntityManager $entityManager
     */
    protected $entityManager;

    /**
     * @var TaxjarConfig
     */
    private $taxjarConfig;

    /**
     * @var RateFactory
     */
    protected $rateFactory;

    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var \Magento\AsynchronousOperations\Api\Data\OperationInterface
     */
    private $operation;

    /**
     * @var array
     */
    private $rates;

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
    private $productTaxClasses;

    /**
     * @var array
     */
    private $customerTaxClasses;

    /**
     * @var integer
     */
    private $shippingClass;

    /**
     * @param SerializerInterface $serializer
     * @param ScopeConfigInterface $scopeConfig
     * @param EntityManager $entityManager
     * @param TaxjarConfig $taxjarConfig
     * @param RateFactory $rateFactory
     * @param RuleFactory $ruleFactory
     */
    public function __construct(
        SerializerInterface $serializer,
        ScopeConfigInterface $scopeConfig,
        EntityManager $entityManager,
        TaxjarConfig $taxjarConfig,
        RateFactory $rateFactory,
        RuleFactory $ruleFactory
    )
    {
        $this->serializer = $serializer;
        $this->scopeConfig = $scopeConfig;
        $this->entityManager = $entityManager;
        $this->taxjarConfig = $taxjarConfig;
        $this->rateFactory = $rateFactory;
        $this->ruleFactory = $ruleFactory;
    }

    /**
     * Process asynchronous bulk operation
     *
     * @param \Magento\AsynchronousOperations\Api\Data\OperationInterface $operation
     * @throws \Exception
     */
    public function process(\Magento\AsynchronousOperations\Api\Data\OperationInterface $operation)
    {
        $this->operation = $operation;
        $this->reset();

        try {
            $this->processOperation();
        } catch (LocalizedException $e) {
            $this->fail($e, $e->getMessage());
        } catch (\Exception $e) {
            $this->fail(
                $e,
                __('Sorry, something went wrong during product attributes update. Please see log for details.')
            );
        }

        $this->success();
        $this->entityManager->save($this->operation);
    }

    /**
     * Reset member variables as class is not reinstantiated between operations
     */
    private function reset()
    {
        $this->newRates = [];
        $this->newShippingRates = [];
    }

    /**
     * @throws LocalizedException
     */
    private function processOperation()
    {
        $this->validate();
        $this->setData();
        $this->createRatesAndRules();
    }

    /**
     * Validate that backup rates should be imported
     *
     * @throws LocalizedException
     */
    private function validate()
    {
        $this->validateBackupRatesAreEnabled();
        $this->validateAPIKeyIsSet();
    }

    /**
     * Validate that settings are configured to allow backup rates
     *
     * @throws LocalizedException
     */
    private function validateBackupRatesAreEnabled()
    {
        if (!$this->scopeConfig->getValue(TaxjarConfig::TAXJAR_BACKUP)) {
            throw new LocalizedException(__('Backup rates are not enabled.'));
        }
    }

    /**
     * Validate API key is set
     *
     * @throws LocalizedException
     */
    private function validateAPIKeyIsSet()
    {
        if (!$this->taxjarConfig->getApiKey()) {
            throw new LocalizedException(__('TaxJar account is not linked or API Token is invalid.'));
        }
    }

    /**
     * Set member variables from operation
     */
    private function setData()
    {
        $serializedData = $this->operation->getSerializedData();
        $data = $this->serializer->unserialize($serializedData);
        $this->setRates($data['rates']);
        $this->productTaxClasses = $data['product_tax_classes'];
        $this->customerTaxClasses = $data['customer_tax_classes'];
        $this->shippingClass = $data['shipping_class'];
    }

    /**
     * Set rates
     *
     * @param array $rates
     */
    private function setRates(array $rates)
    {
        $this->rates = $rates;
    }

    /**
     * Create tax rates and rules
     *
     * @throws \Exception
     */
    private function createRatesAndRules()
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

    /**
     * Log and handled operation failures
     *
     * @param \Exception $e
     * @param $message
     */
    private function fail(\Exception $e, $message)
    {
        $this->logger->critical($e->getMessage());
        $this->operation->setStatus(OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED);
        $this->operation->setErrorCode($e->getCode());
        $this->operation->setResultMessage($message);
    }

    /**
     * Set operation status to completed
     */
    private function success()
    {
        $this->operation->setStatus(OperationInterface::STATUS_TYPE_COMPLETE);
        $this->operation->setErrorCode(null);
        $this->operation->setResultMessage(null);
    }
}