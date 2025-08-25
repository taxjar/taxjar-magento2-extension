<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Integration\Model\Import;

use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Config\Model\ResourceModel\Config as MagentoConfig;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Magento\Tax\Api\TaxRuleRepositoryInterface;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\Import\CreateRatesConsumer;
use Taxjar\SalesTax\Test\Integration\IntegrationTestCase;

class CreateRatesConsumerTest extends IntegrationTestCase
{
    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoConfigFixture default/tax/taxjar/apikey test-api-key
     * @magentoConfigFixture default/tax/taxjar/backup 1
     * @magentoConfigFixture default/tax/taxjar/backup_rate_count 0
     * @magentoConfigFixture default/tax/taxjar/customer_tax_classes 3
     * @magentoConfigFixture default/tax/taxjar/product_tax_classes 2
     */
    public function testProcess()
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
        $identityService = $this->objectManager->get(IdentityGeneratorInterface::class);

        $uuid = $identityService->generateId();

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

        $bulkManagement = $this->objectManager->get(BulkManagementInterface::class);
        $bulkManagement->scheduleBulk($uuid, [$operation], 'bulk-op', null);

        /** @var CreateRatesConsumer $sut */
        $sut = $this->objectManager->get(CreateRatesConsumer::class);
        $sut->process($operation);

        /** @var TaxRuleRepositoryInterface $ruleRepository */
        $ruleRepository = $this->objectManager->get(TaxRuleRepositoryInterface::class);
        $searchCriteria = ($this->objectManager->get(SearchCriteriaBuilder::class))
            ->addFilter('code', TaxjarConfig::TAXJAR_BACKUP_RATE_CODE)
            ->create();
        /** @var \Magento\Tax\Api\Data\TaxRuleSearchResultsInterface $rules */
        $rules = $ruleRepository->getList($searchCriteria);
        $ruleItems = array_values($rules->getItems());
        $ids = $ruleItems ? $ruleItems[0]->getTaxRateIds() : [];

        self::assertEquals(1, $rules->getTotalCount());
        self::assertEquals(1, count($ids));

        /** @var TaxRateRepositoryInterface $rateRepository */
        $rateRepository = $this->objectManager->get(TaxRateRepositoryInterface::class);
        $rate = $rateRepository->get($ids[0]);

        self::assertEquals('US-TX-*', $rate->getCode());
    }
}
