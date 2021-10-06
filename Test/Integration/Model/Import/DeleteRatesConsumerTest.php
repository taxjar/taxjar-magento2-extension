<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Integration\Model\Import;

use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Config\Model\ResourceModel\Config as MagentoConfig;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\SalesRule\Model\RuleRepository;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Magento\Tax\Api\TaxRuleRepositoryInterface;
use Magento\Tax\Model\Calculation\Rule;
use Taxjar\SalesTax\Model\Import\CreateRatesConsumer;
use Taxjar\SalesTax\Model\Import\DeleteRatesConsumer;
use Taxjar\SalesTax\Test\Integration\IntegrationTestCase;

class DeleteRatesConsumerTest extends IntegrationTestCase
{
    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoConfigFixture default/tax/taxjar/apikey test-api-key
     * @magentoConfigFixture default/tax/taxjar/backup 1
     * @magentoConfigFixture default/tax/taxjar/backup_rate_count 1
     * @magentoConfigFixture default/tax/taxjar/customer_tax_classes 3
     * @magentoConfigFixture default/tax/taxjar/product_tax_classes 2
     */
    public function testProcess()
    {
        $this->setupExistingRates();

        /** @var TaxRuleRepositoryInterface $ruleRepository */
        $ruleRepository = $this->objectManager->get(TaxRuleRepositoryInterface::class);
        /** @var \Magento\Tax\Api\Data\TaxRuleSearchResultsInterface $rules */
        $rules = $ruleRepository->getList(new SearchCriteria());
        $ids = array_values($rules->getItems())[0]->getTaxRateIds();

        $operationFactory = $this->objectManager->get(OperationInterfaceFactory::class);
        $serializer = $this->objectManager->get(SerializerInterface::class);
        $identityService = $this->objectManager->get(IdentityGeneratorInterface::class);

        $uuid = $identityService->generateId();

        $payload = [
            'rates' => $ids,
        ];

        $operation = $operationFactory->create([
            'data' => [
                'bulk_uuid' => $uuid,
                'topic_name' => 'taxjar.backup_rates.delete',
                'serialized_data' => $serializer->serialize($payload),
                'status' => OperationInterface::STATUS_TYPE_OPEN,
            ],
        ]);

        $bulkManagement = $this->objectManager->get(BulkManagementInterface::class);
        $bulkManagement->scheduleBulk($uuid, [$operation], 'bulk-op', null);

        /** @var DeleteRatesConsumer $sut */
        $sut = $this->objectManager->get(DeleteRatesConsumer::class);
        $sut->process($operation);

        $ruleRepository = $this->objectManager->get(TaxRuleRepositoryInterface::class);
        $rules = $ruleRepository->getList(new SearchCriteria());

        // Rule should not be deleted
        self::assertEquals(1, $rules->getTotalCount());

        $rateRepository = $this->objectManager->get(TaxRateRepositoryInterface::class);
        $searchCriteria = ($this->objectManager->get(SearchCriteriaBuilder::class))
            ->addFilter('code', 'US-TX-*')->create();
        $rates = $rateRepository->getList($searchCriteria);

        // Existing TX rate should've been deleted
        self::assertEquals(0, $rates->getTotalCount());
    }

    private function setupExistingRates(): void
    {
        $resourceConfig = $this->objectManager->get(MagentoConfig::class);
        $resourceConfig->saveConfig('tax/taxjar/backup', '1');

        $mockRates = [
            [
                'state' => 'TX',
                'zip' => '*',
                'rate' => 0.0825,
                'freight_taxable' => false,
            ],
        ];

        $operationFactory = $this->objectManager->get(OperationInterfaceFactory::class);
        $serializer = $this->objectManager->get(SerializerInterface::class);

        $uuid = ($this->objectManager->get(IdentityGeneratorInterface::class))->generateId();

        $payload = [
            'rates' => $mockRates,
            'product_tax_classes' => [2],
            'customer_tax_classes' => [3],
            'shipping_tax_class' => '',
        ];

        $operation = $operationFactory->create([
            'data' => [
                'bulk_uuid' => $uuid,
                'topic_name' => 'taxjar.backup_rates.create',
                'serialized_data' => $serializer->serialize($payload),
                'status' => OperationInterface::STATUS_TYPE_OPEN,
            ],
        ]);

        ($this->objectManager->get(BulkManagementInterface::class))
            ->scheduleBulk($uuid, [$operation], 'bulk-op', null);

        ($this->objectManager->get(CreateRatesConsumer::class))
            ->process($operation);
    }
}
