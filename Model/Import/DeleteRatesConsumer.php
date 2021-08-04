<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Model\Import;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Tax\Model\Calculation\RateRepository;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class DeleteRatesConsumer extends AbstractRatesConsumer
{
    /**
     * @var RateRepository
     */
    private $rateRepository;

    public function __construct(
        SerializerInterface $serializer,
        ScopeConfigInterface $scopeConfig,
        EntityManager $entityManager,
        TaxjarConfig $taxjarConfig,
        RateFactory $rateFactory,
        RuleFactory $ruleFactory,
        RateRepository $rateRepository
    ) {
        parent::__construct(
            $serializer,
            $scopeConfig,
            $entityManager,
            $taxjarConfig,
            $rateFactory,
            $ruleFactory
        );

        $this->rateRepository = $rateRepository;
    }

    protected function setData(): void
    {
        $serializedData = $this->operation->getSerializedData();
        $data = $this->serializer->unserialize($serializedData);

        $this->rates = $data['rates'];
    }

    protected function processOperation(): void
    {
        foreach ($this->rates as $rate) {
            $this->rateRepository->deleteById($rate);
        }
    }
}
