<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Model\Import;

use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\EntityManager\EntityManager;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

abstract class AbstractRatesConsumer
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

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
    protected $taxjarConfig;

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
    protected $operation;

    /**
     * @var array
     */
    protected $rates;

    /**
     * @var array
     */
    protected $newRates;

    /**
     * @var array
     */
    protected $newShippingRates;

    /**
     * @var array
     */
    protected $productTaxClasses;

    /**
     * @var array
     */
    protected $customerTaxClasses;

    /**
     * @var integer
     */
    protected $shippingClass;

    /**
     * @var string|null
     */
    protected $topicName;

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
    ) {
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
            $this->validate();
            $this->setData();
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
    protected function reset()
    {
        $this->newRates = [];
        $this->newShippingRates = [];
    }

    /**
     * Validate that backup rates should be imported
     *
     * @throws LocalizedException
     */
    protected function validate()
    {
        $this->validateBackupRatesAreEnabled();
        $this->validateAPIKeyIsSet();
    }

    /**
     * Validate that settings are configured to allow backup rates
     *
     * @throws LocalizedException
     */
    protected function validateBackupRatesAreEnabled()
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
    protected function validateAPIKeyIsSet()
    {
        if (!$this->taxjarConfig->getApiKey()) {
            throw new LocalizedException(__('TaxJar account is not linked or API Token is invalid.'));
        }
    }

    /**
     * Log and handled operation failures
     *
     * @param \Exception $e
     * @param $message
     */
    protected function fail(\Exception $e, $message)
    {
        $this->logger->critical($e->getMessage());
        $this->operation->setStatus(OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED);
        $this->operation->setErrorCode($e->getCode());
        $this->operation->setResultMessage($message);
    }

    /**
     * Set operation status to completed
     */
    protected function success()
    {
        $this->operation->setStatus(OperationInterface::STATUS_TYPE_COMPLETE);
        $this->operation->setErrorCode(null);
        $this->operation->setResultMessage(null);
    }

    /**
     * Set member variables from operation
     */
    abstract protected function setData(): void;

    /**
     * @throws LocalizedException
     */
    abstract protected function processOperation(): void;
}
