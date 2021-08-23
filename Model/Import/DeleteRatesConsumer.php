<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Model\Import;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\LoggerInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Tax\Model\Calculation\RateRepository;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class DeleteRatesConsumer extends AbstractRatesConsumer
{
    /**
     * @var RateRepository
     */
    private $rateRepository;

    /**
     * DeleteRatesConsumer constructor.
     * @param SerializerInterface $serializer
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param EntityManager $entityManager
     * @param TaxjarConfig $taxjarConfig
     * @param RateFactory $rateFactory
     * @param RuleFactory $ruleFactory
     * @param RateRepository $rateRepository
     */
    public function __construct(
        SerializerInterface $serializer,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        EntityManager $entityManager,
        TaxjarConfig $taxjarConfig,
        RateFactory $rateFactory,
        RuleFactory $ruleFactory,
        RateRepository $rateRepository
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

        $this->rateRepository = $rateRepository;
    }

    /**
     * @return self
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function handle(): self
    {
        return $this->validate()
            ->setMemberData()
            ->deleteRates();
    }

    /**
     * @return self
     */
    protected function setMemberData(): self
    {
        $this->setRates($this->payload['rates']);

        return $this;
    }

    /**
     * Throw exception if payload does not adhere to expected format
     *
     * @return self
     * @throws LocalizedException
     */
    private function validate(): self
    {
        if (is_array($this->payload) && array_key_exists('rates', $this->payload)) {
            return $this;
        }

        throw new LocalizedException(__('Consumer %1 received invalid payload.', self::class));
    }

    /**
     * Delete `tax_calculation_rates` by ID
     *
     * @throws NoSuchEntityException
     */
    private function deleteRates(): self
    {
        foreach ($this->rates as $rate) {
            $this->rateRepository->deleteById($rate);
        }

        return $this;
    }
}
