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
use Magento\Tax\Model\CalculationFactory;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class DeleteRatesConsumer extends AbstractRatesConsumer
{
    /**
     * @var RateRepository
     */
    private $rateRepository;

    /**
     * @var CalculationFactory
     */
    private $calculationFactory;

    /**
     * @param SerializerInterface $serializer
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param EntityManager $entityManager
     * @param TaxjarConfig $taxjarConfig
     * @param RateFactory $rateFactory
     * @param RuleFactory $ruleFactory
     * @param RateRepository $rateRepository
     * @param CalculationFactory $calculationFactory
     */
    public function __construct(
        SerializerInterface $serializer,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        EntityManager $entityManager,
        TaxjarConfig $taxjarConfig,
        RateFactory $rateFactory,
        RuleFactory $ruleFactory,
        RateRepository $rateRepository,
        CalculationFactory $calculationFactory
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
        $this->calculationFactory = $calculationFactory;
    }

    /**
     * Delete backup sales tax rates
     *
     * @return $this
     * @throws LocalizedException|NoSuchEntityException
     */
    protected function handle()
    {
        return $this->validate()
            ->setMemberData()
            ->deleteRates();
    }

    /**
     * Set rates for deletion
     *
     * @return $this
     */
    protected function setMemberData()
    {
        $this->setRates($this->payload['rates']);

        return $this;
    }

    /**
     * Throw exception if payload does not adhere to expected format
     *
     * @return $this
     * @throws LocalizedException
     */
    private function validate()
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
    private function deleteRates()
    {
        $calculationModel = $this->calculationFactory->create();

        foreach ($this->rates as $rate) {
            $calculations = $calculationModel->getCollection()
                ->addFieldToFilter('tax_calculation_rate_id', $rate)
                ->getItems();

            foreach ($calculations as $calculation) {
                $calculation->delete();
            }

            $this->rateRepository->deleteById($rate);
        }

        return $this;
    }
}
