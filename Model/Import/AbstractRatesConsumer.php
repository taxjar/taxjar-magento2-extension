<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Model\Import;

use Exception;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Model\Operation;
use Magento\Framework\DB\LoggerInterface;
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
     * @var LoggerInterface
     */
    private $logger;

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
     * @var Operation|null
     */
    protected $operation;

    /**
     * @var array|null
     */
    protected $payload;

    /**
     * @var array|null
     */
    protected $rates;

    /**
     * @param SerializerInterface $serializer
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param EntityManager $entityManager
     * @param TaxjarConfig $taxjarConfig
     * @param RateFactory $rateFactory
     * @param RuleFactory $ruleFactory
     */
    public function __construct(
        SerializerInterface $serializer,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        EntityManager $entityManager,
        TaxjarConfig $taxjarConfig,
        RateFactory $rateFactory,
        RuleFactory $ruleFactory
    ) {
        $this->serializer = $serializer;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->taxjarConfig = $taxjarConfig;
        $this->rateFactory = $rateFactory;
        $this->ruleFactory = $ruleFactory;
    }

    /**
     * Set member variables from operation
     */
    abstract protected function setMemberData();

    /**
     * Execute the primary Consumer functionality of the class
     *
     * @throws LocalizedException
     */
    abstract protected function handle();

    /**
     * @param Operation $value
     * @return $this
     */
    public function setOperation(Operation $value): self
    {
        $this->operation = $value;

        return $this;
    }

    /**
     * @return $this
     */
    public function setPayload(): self
    {
        $this->payload = $this->serializer->unserialize(
            $this->operation->getSerializedData()
        );

        return $this;
    }

    /**
     * @param array $values
     * @return $this
     */
    public function setRates(array $values): self
    {
        $this->rates = $values;

        return $this;
    }

    /**
     * Process asynchronous bulk operation
     *
     * @param OperationInterface $operation
     * @throws Exception
     */
    public function process(OperationInterface $operation): void
    {
        try {
            $this->setOperation($operation)
                ->setPayload()
                ->handle()
                ->success();
        } catch (LocalizedException $e) {
            $this->fail($e, $e->getMessage());
        } catch (Exception $e) {
            $this->fail($e, __('Sorry, something went wrong during backup rate sync.'));
        }

        $this->entityManager->save($this->operation);
    }

    /**
     * Log and handle operation failures
     *
     * @param Exception $e
     * @param $message
     */
    protected function fail(Exception $e, $message)
    {
        $this->logger->critical($e);
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
}
