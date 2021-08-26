<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Observer;

use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\AsynchronousOperations\Model\Operation;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\Calculation\RateRepository;
use Magento\Tax\Model\Calculation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Taxjar\SalesTax\Model\BackupRateOriginAddress;
use Taxjar\SalesTax\Model\Client;
use Taxjar\SalesTax\Model\ClientFactory;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\Import\Rate;
use Taxjar\SalesTax\Model\Import\RateFactory;
use Taxjar\SalesTax\Model\Import\RuleFactory;
use Taxjar\SalesTax\Observer\ImportRates;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class ImportRatesTest extends UnitTestCase
{
    public function testExecuteWithBackupRatesEnabledAndValidApiKeyWhenDebugEnabled(): void
    {
        $mockEventManager = $this->createMock(EventManagerInterface::class);

        $mockMessageManager = $this->createMock(MessageManagerInterface::class);
        $mockMessageManager->expects($this->once())
            ->method('addNoticeMessage')
            ->withAnyParameters();

        $mockScopeConfigInterface = $this->createMock(ScopeConfigInterface::class);
        $mockScopeConfigInterface->expects($this->exactly(5))
            ->method('getValue')
            ->withConsecutive(
                [TaxjarConfig::TAXJAR_BACKUP],
                [TaxjarConfig::TAXJAR_CUSTOMER_TAX_CLASSES],
                [TaxjarConfig::TAXJAR_PRODUCT_TAX_CLASSES],
                ['tax/classes/shipping_tax_class'],
                [TaxjarConfig::TAXJAR_DEBUG]
            )->willReturnOnConsecutiveCalls(
                1,
                'customer_class_1,customer_class_1',
                'product_class_1,product_class_2',
                'shipping_product_tax_class',
                1
            );

        $mockResourceConfig = $this->createMock(Config::class);

        $mockClient = $this->createMock(Client::class);

        $mockClientFactory = $this->createMock(ClientFactory::class);
        $mockClientFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockClient);

        $mockRateFactory = $this->createMock(RateFactory::class);
        $mockRuleFactory = $this->createMock(RuleFactory::class);
        $mockRateRepository = $this->createMock(RateRepository::class);

        $mockTaxjarConfig = $this->createMock(TaxjarConfig::class);
        $mockTaxjarConfig->expects($this->once())
            ->method('getApiKey')
            ->willReturn('valid-api-key');

        $mockBackupRateOriginAddress = $this->createMock(BackupRateOriginAddress::class);
        $mockBackupRateOriginAddress->expects($this->once())
            ->method('getShippingZipCode')
            ->willReturn('99999');

        $mockIdentityGeneratorInterface = $this->createMock(IdentityGeneratorInterface::class);
        $mockSerializerInterface = $this->createMock(SerializerInterface::class);
        $mockOperationInterfaceFactory = $this->createMock(OperationInterfaceFactory::class);
        $mockBulkManagementInterface = $this->createMock(BulkManagementInterface::class);
        $mockUserContextInterface = $this->createMock(UserContextInterface::class);

        $mockCacheManager = $this->createMock(Manager::class);
        $mockCacheManager->expects($this->once())->method('getAvailableTypes')->willReturn(['type']);
        $mockCacheManager->expects($this->once())->method('flush')->with(['type'])->willReturn(true);

        $sut = new ImportRates(
            $mockEventManager,
            $mockMessageManager,
            $mockScopeConfigInterface,
            $mockResourceConfig,
            $mockClientFactory,
            $mockRateFactory,
            $mockRuleFactory,
            $mockRateRepository,
            $mockTaxjarConfig,
            $mockBackupRateOriginAddress,
            $mockIdentityGeneratorInterface,
            $mockSerializerInterface,
            $mockOperationInterfaceFactory,
            $mockBulkManagementInterface,
            $mockUserContextInterface,
            $mockCacheManager
        );

        $result = $sut->execute(new Observer());

        $this->assertInstanceOf(ImportRates::class, $result);
    }

    public function testExecuteWithInvalidZipCodeThrowsException(): void
    {
        $mockEventManager = $this->createMock(EventManagerInterface::class);
        $mockMessageManager = $this->createMock(MessageManagerInterface::class);

        $mockScopeConfigInterface = $this->createMock(ScopeConfigInterface::class);
        $mockScopeConfigInterface->expects($this->exactly(5))
            ->method('getValue')
            ->withConsecutive(
                [TaxjarConfig::TAXJAR_BACKUP],
                [TaxjarConfig::TAXJAR_CUSTOMER_TAX_CLASSES],
                [TaxjarConfig::TAXJAR_PRODUCT_TAX_CLASSES],
                ['tax/classes/shipping_tax_class'],
                [TaxjarConfig::TAXJAR_DEBUG]
            )->willReturnOnConsecutiveCalls(
                1,
                'customer_class_1,customer_class_1',
                'product_class_1,product_class_2',
                'shipping_product_tax_class',
                0
            );

        $mockResourceConfig = $this->createMock(Config::class);

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('getResource')
            ->with('rates', [
                Response::HTTP_FORBIDDEN => __(
                    'Your last backup rate sync from TaxJar was too recent. ' .
                    'Please wait at least 5 minutes and try again.'
                ),
            ])
            ->willReturn([
                'rates' => [
                    'some',
                    'list',
                    'of',
                    'values',
                ],
            ]);

        $mockClientFactory = $this->createMock(ClientFactory::class);
        $mockClientFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockClient);

        $mockRateFactory = $this->createMock(RateFactory::class);
        $mockRuleFactory = $this->createMock(RuleFactory::class);
        $mockRateRepository = $this->createMock(RateRepository::class);

        $mockTaxjarConfig = $this->createMock(TaxjarConfig::class);
        $mockTaxjarConfig->expects($this->once())
            ->method('getApiKey')
            ->willReturn('valid-api-key');

        $mockBackupRateOriginAddress = $this->createMock(BackupRateOriginAddress::class);
        $mockBackupRateOriginAddress->expects($this->once())
            ->method('getShippingZipCode')
            ->willReturn('1234567');
        $mockBackupRateOriginAddress->expects($this->once())
            ->method('isScopeCountryCodeUS')
            ->willReturn(true);

        $mockIdentityGeneratorInterface = $this->createMock(IdentityGeneratorInterface::class);
        $mockSerializerInterface = $this->createMock(SerializerInterface::class);
        $mockOperationInterfaceFactory = $this->createMock(OperationInterfaceFactory::class);
        $mockBulkManagementInterface = $this->createMock(BulkManagementInterface::class);
        $mockUserContextInterface = $this->createMock(UserContextInterface::class);
        $mockCacheManager = $this->createMock(Manager::class);

        $sut = new ImportRates(
            $mockEventManager,
            $mockMessageManager,
            $mockScopeConfigInterface,
            $mockResourceConfig,
            $mockClientFactory,
            $mockRateFactory,
            $mockRuleFactory,
            $mockRateRepository,
            $mockTaxjarConfig,
            $mockBackupRateOriginAddress,
            $mockIdentityGeneratorInterface,
            $mockSerializerInterface,
            $mockOperationInterfaceFactory,
            $mockBulkManagementInterface,
            $mockUserContextInterface,
            $mockCacheManager
        );

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Please check that your zip code is a valid US zip code in Shipping Settings.');

        $sut->execute(new Observer());
    }

    public function testExecuteWithInvalidTaxClassesThrowsException(): void
    {
        $mockEventManager = $this->createMock(EventManagerInterface::class);
        $mockMessageManager = $this->createMock(MessageManagerInterface::class);

        $mockScopeConfigInterface = $this->createMock(ScopeConfigInterface::class);
        $mockScopeConfigInterface->expects($this->exactly(5))
            ->method('getValue')
            ->withConsecutive(
                [TaxjarConfig::TAXJAR_BACKUP],
                [TaxjarConfig::TAXJAR_CUSTOMER_TAX_CLASSES],
                [TaxjarConfig::TAXJAR_PRODUCT_TAX_CLASSES],
                ['tax/classes/shipping_tax_class'],
                [TaxjarConfig::TAXJAR_DEBUG]
            )->willReturnOnConsecutiveCalls(
                1,
                '',
                '',
                'shipping_product_tax_class',
                0
            );

        $mockResourceConfig = $this->createMock(Config::class);

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('getResource')
            ->with('rates', [
                Response::HTTP_FORBIDDEN => __(
                    'Your last backup rate sync from TaxJar was too recent. ' .
                    'Please wait at least 5 minutes and try again.'
                ),
            ])
            ->willReturn([
                'rates' => [
                    'some',
                    'list',
                    'of',
                    'values',
                ],
            ]);

        $mockClientFactory = $this->createMock(ClientFactory::class);
        $mockClientFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockClient);

        $mockRateFactory = $this->createMock(RateFactory::class);
        $mockRuleFactory = $this->createMock(RuleFactory::class);
        $mockRateRepository = $this->createMock(RateRepository::class);

        $mockTaxjarConfig = $this->createMock(TaxjarConfig::class);
        $mockTaxjarConfig->expects($this->once())
            ->method('getApiKey')
            ->willReturn('valid-api-key');

        $mockBackupRateOriginAddress = $this->createMock(BackupRateOriginAddress::class);
        $mockBackupRateOriginAddress->expects($this->once())
            ->method('getShippingZipCode')
            ->willReturn('99999');
        $mockBackupRateOriginAddress->expects($this->once())
            ->method('isScopeCountryCodeUS')
            ->willReturn(true);

        $mockIdentityGeneratorInterface = $this->createMock(IdentityGeneratorInterface::class);
        $mockSerializerInterface = $this->createMock(SerializerInterface::class);
        $mockOperationInterfaceFactory = $this->createMock(OperationInterfaceFactory::class);
        $mockBulkManagementInterface = $this->createMock(BulkManagementInterface::class);
        $mockUserContextInterface = $this->createMock(UserContextInterface::class);
        $mockCacheManager = $this->createMock(Manager::class);

        $sut = new ImportRates(
            $mockEventManager,
            $mockMessageManager,
            $mockScopeConfigInterface,
            $mockResourceConfig,
            $mockClientFactory,
            $mockRateFactory,
            $mockRuleFactory,
            $mockRateRepository,
            $mockTaxjarConfig,
            $mockBackupRateOriginAddress,
            $mockIdentityGeneratorInterface,
            $mockSerializerInterface,
            $mockOperationInterfaceFactory,
            $mockBulkManagementInterface,
            $mockUserContextInterface,
            $mockCacheManager
        );

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage(
            'Please select at least one product tax class and one customer tax class to ' .
            'configure backup rates from TaxJar.'
        );

        $sut->execute(new Observer());
    }

    public function testExecuteWithInvalidShippingTaxClassThrowsException(): void
    {
        $mockEventManager = $this->createMock(EventManagerInterface::class);
        $mockMessageManager = $this->createMock(MessageManagerInterface::class);

        $mockScopeConfigInterface = $this->createMock(ScopeConfigInterface::class);
        $mockScopeConfigInterface->expects($this->exactly(5))
            ->method('getValue')
            ->withConsecutive(
                [TaxjarConfig::TAXJAR_BACKUP],
                [TaxjarConfig::TAXJAR_CUSTOMER_TAX_CLASSES],
                [TaxjarConfig::TAXJAR_PRODUCT_TAX_CLASSES],
                ['tax/classes/shipping_tax_class'],
                [TaxjarConfig::TAXJAR_DEBUG]
            )->willReturnOnConsecutiveCalls(
                1,
                'customer_class_1,customer_class_1',
                'product_class_1,product_class_2',
                'product_class_1',
                0
            );

        $mockResourceConfig = $this->createMock(Config::class);

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('getResource')
            ->with('rates', [
                Response::HTTP_FORBIDDEN => __(
                    'Your last backup rate sync from TaxJar was too recent. ' .
                    'Please wait at least 5 minutes and try again.'
                ),
            ])
            ->willReturn([
                'rates' => [
                    'some',
                    'list',
                    'of',
                    'values',
                ],
            ]);

        $mockClientFactory = $this->createMock(ClientFactory::class);
        $mockClientFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockClient);

        $mockRateFactory = $this->createMock(RateFactory::class);
        $mockRuleFactory = $this->createMock(RuleFactory::class);
        $mockRateRepository = $this->createMock(RateRepository::class);

        $mockTaxjarConfig = $this->createMock(TaxjarConfig::class);
        $mockTaxjarConfig->expects($this->once())
            ->method('getApiKey')
            ->willReturn('valid-api-key');

        $mockBackupRateOriginAddress = $this->createMock(BackupRateOriginAddress::class);
        $mockBackupRateOriginAddress->expects($this->once())
            ->method('getShippingZipCode')
            ->willReturn('99999');
        $mockBackupRateOriginAddress->expects($this->once())
            ->method('isScopeCountryCodeUS')
            ->willReturn(true);

        $mockIdentityGeneratorInterface = $this->createMock(IdentityGeneratorInterface::class);
        $mockSerializerInterface = $this->createMock(SerializerInterface::class);
        $mockOperationInterfaceFactory = $this->createMock(OperationInterfaceFactory::class);
        $mockBulkManagementInterface = $this->createMock(BulkManagementInterface::class);
        $mockUserContextInterface = $this->createMock(UserContextInterface::class);
        $mockCacheManager = $this->createMock(Manager::class);

        $sut = new ImportRates(
            $mockEventManager,
            $mockMessageManager,
            $mockScopeConfigInterface,
            $mockResourceConfig,
            $mockClientFactory,
            $mockRateFactory,
            $mockRuleFactory,
            $mockRateRepository,
            $mockTaxjarConfig,
            $mockBackupRateOriginAddress,
            $mockIdentityGeneratorInterface,
            $mockSerializerInterface,
            $mockOperationInterfaceFactory,
            $mockBulkManagementInterface,
            $mockUserContextInterface,
            $mockCacheManager
        );

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage(
            'For backup shipping rates, please use a unique tax class for shipping.'
        );

        $sut->execute(new Observer());
    }

    public function testExecuteThrowsExceptionWhenScheduleBulkOperationFails(): void
    {
        $mockEventManager = $this->createMock(EventManagerInterface::class);
        $mockMessageManager = $this->createMock(MessageManagerInterface::class);
        $mockScopeConfigInterface = $this->createMock(ScopeConfigInterface::class);
        $mockScopeConfigInterface->expects($this->exactly(5))
            ->method('getValue')
            ->withConsecutive(
                [TaxjarConfig::TAXJAR_BACKUP],
                [TaxjarConfig::TAXJAR_CUSTOMER_TAX_CLASSES],
                [TaxjarConfig::TAXJAR_PRODUCT_TAX_CLASSES],
                ['tax/classes/shipping_tax_class'],
                [TaxjarConfig::TAXJAR_DEBUG]
            )->willReturnOnConsecutiveCalls(
                1,
                'customer_class_1,customer_class_1',
                'product_class_1,product_class_2',
                'shipping_class_1',
                0
            );

        $mockResourceConfig = $this->createMock(Config::class);
        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('getResource')
            ->with('rates', [
                Response::HTTP_FORBIDDEN => __(
                    'Your last backup rate sync from TaxJar was too recent. ' .
                    'Please wait at least 5 minutes and try again.'
                ),
            ])
            ->willReturn([
                'rates' => [
                    'some',
                    'list',
                    'of',
                    'values',
                ],
            ]);

        $mockClientFactory = $this->createMock(ClientFactory::class);
        $mockClientFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockClient);

        $mockCalculation = $this->createMock(Calculation::class);
        $mockCalculation->expects($this->once())
            ->method('deleteByRuleId')
            ->with(42)
            ->willReturnSelf();

        $mockRule = $this->createMock(Rule::class);
        $mockRule->expects($this->once())
            ->method('getId')
            ->willReturn(42);
        $mockRule->expects($this->once())
            ->method('getCalculationModel')
            ->willReturn($mockCalculation);
        $mockRule->expects($this->once())
            ->method('delete');

        $mockRateModel = $this->createMock(Rate::class);
        $mockRateModel->expects($this->once())
            ->method('getExistingRates')
            ->willReturn(['old_rate_1', 'old_rate_2']);
        $mockRateModel->expects($this->once())
            ->method('getRule')
            ->willReturn($mockRule);

        $mockRateFactory = $this->createMock(RateFactory::class);
        $mockRateFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockRateModel);

        $mockRuleFactory = $this->createMock(RuleFactory::class);
        $mockRateRepository = $this->createMock(RateRepository::class);

        $mockTaxjarConfig = $this->createMock(TaxjarConfig::class);
        $mockTaxjarConfig->expects($this->once())
            ->method('getApiKey')
            ->willReturn('valid-api-key');

        $mockBackupRateOriginAddress = $this->createMock(BackupRateOriginAddress::class);
        $mockBackupRateOriginAddress->expects($this->once())
            ->method('getShippingZipCode')
            ->willReturn('99999');
        $mockBackupRateOriginAddress->expects($this->once())
            ->method('isScopeCountryCodeUS')
            ->willReturn(false);

        $mockIdentityGeneratorInterface = $this->createMock(IdentityGeneratorInterface::class);
        $mockIdentityGeneratorInterface->expects($this->once())
            ->method('generateId')
            ->willReturn('unique-identifier');

        $mockSerializerInterface = $this->createMock(SerializerInterface::class);
        $mockSerializerInterface->expects($this->once())
            ->method('serialize')
            ->willReturn(json_encode(['data' => 'payload']));

        $mockOperationInterfaceFactory = $this->createMock(OperationInterfaceFactory::class);
        $mockOperationInterfaceFactory->expects($this->once())
            ->method('create')
            ->withAnyParameters()
            ->willReturn($this->createMock(Operation::class));

        $mockBulkManagementInterface = $this->createMock(BulkManagementInterface::class);
        $mockBulkManagementInterface->expects($this->once())
            ->method('scheduleBulk')
            ->withAnyParameters()
            ->willReturn(false);

        $mockUserContextInterface = $this->createMock(UserContextInterface::class);
        $mockUserContextInterface->expects($this->once())
            ->method('getUserId')
            ->willReturn('9999999');

        $mockCacheManager = $this->createMock(Manager::class);

        $sut = new ImportRates(
            $mockEventManager,
            $mockMessageManager,
            $mockScopeConfigInterface,
            $mockResourceConfig,
            $mockClientFactory,
            $mockRateFactory,
            $mockRuleFactory,
            $mockRateRepository,
            $mockTaxjarConfig,
            $mockBackupRateOriginAddress,
            $mockIdentityGeneratorInterface,
            $mockSerializerInterface,
            $mockOperationInterfaceFactory,
            $mockBulkManagementInterface,
            $mockUserContextInterface,
            $mockCacheManager
        );

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage(
            'Something went wrong while processing the request.'
        );

        $sut->execute(new Observer());
    }

    public function testExecuteWithExistingRates()
    {
        $mockEventManager = $this->createMock(EventManagerInterface::class);
        $mockEventManager->expects($this->once())
            ->method('dispatch')
            ->with('taxjar_salestax_import_rates_after');

        $mockMessageManager = $this->createMock(MessageManagerInterface::class);
        $mockMessageManager->expects($this->once())->method('addSuccessMessage');

        $mockScopeConfigInterface = $this->createMock(ScopeConfigInterface::class);
        $mockScopeConfigInterface->expects($this->exactly(5))
            ->method('getValue')
            ->withConsecutive(
                [TaxjarConfig::TAXJAR_BACKUP],
                [TaxjarConfig::TAXJAR_CUSTOMER_TAX_CLASSES],
                [TaxjarConfig::TAXJAR_PRODUCT_TAX_CLASSES],
                ['tax/classes/shipping_tax_class'],
                [TaxjarConfig::TAXJAR_DEBUG]
            )->willReturnOnConsecutiveCalls(
                1,
                'customer_class_1,customer_class_1',
                'product_class_1,product_class_2',
                'shipping_class_1',
                0
            );

        $mockResourceConfig = $this->createMock(Config::class);
        $mockResourceConfig->expects($this->once())->method('saveConfig');

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('getResource')
            ->with('rates', [
                Response::HTTP_FORBIDDEN => __(
                    'Your last backup rate sync from TaxJar was too recent. ' .
                    'Please wait at least 5 minutes and try again.'
                ),
            ])
            ->willReturn([
                'rates' => [
                    'some',
                    'list',
                    'of',
                    'values',
                ],
            ]);

        $mockClientFactory = $this->createMock(ClientFactory::class);
        $mockClientFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockClient);

        $mockCalculation = $this->createMock(Calculation::class);
        $mockCalculation->expects($this->once())
            ->method('deleteByRuleId')
            ->with(42)
            ->willReturnSelf();

        $mockRule = $this->createMock(Rule::class);
        $mockRule->expects($this->once())
            ->method('getId')
            ->willReturn(42);
        $mockRule->expects($this->once())
            ->method('getCalculationModel')
            ->willReturn($mockCalculation);
        $mockRule->expects($this->once())
            ->method('delete');

        $mockRateModel = $this->createMock(Rate::class);
        $mockRateModel->expects($this->once())
            ->method('getExistingRates')
            ->willReturn(['old_rate_1', 'old_rate_2']);
        $mockRateModel->expects($this->once())
            ->method('getRule')
            ->willReturn($mockRule);

        $mockRateFactory = $this->createMock(RateFactory::class);
        $mockRateFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockRateModel);

        $mockRuleFactory = $this->createMock(RuleFactory::class);
        $mockRateRepository = $this->createMock(RateRepository::class);

        $mockTaxjarConfig = $this->createMock(TaxjarConfig::class);
        $mockTaxjarConfig->expects($this->once())
            ->method('getApiKey')
            ->willReturn('valid-api-key');

        $mockBackupRateOriginAddress = $this->createMock(BackupRateOriginAddress::class);
        $mockBackupRateOriginAddress->expects($this->once())
            ->method('getShippingZipCode')
            ->willReturn('99999');
        $mockBackupRateOriginAddress->expects($this->once())
            ->method('isScopeCountryCodeUS')
            ->willReturn(true);

        $mockIdentityGeneratorInterface = $this->createMock(IdentityGeneratorInterface::class);
        $mockIdentityGeneratorInterface->expects($this->exactly(2))
            ->method('generateId')
            ->willReturn('unique-identifier');

        $mockSerializerInterface = $this->createMock(SerializerInterface::class);
        $mockSerializerInterface->expects($this->exactly(2))
            ->method('serialize')
            ->willReturn(json_encode(['data' => 'payload']));

        $mockOperationInterfaceFactory = $this->createMock(OperationInterfaceFactory::class);
        $mockOperationInterfaceFactory->expects($this->exactly(2))
            ->method('create')
            ->withAnyParameters()
            ->willReturn($this->createMock(Operation::class));

        $mockBulkManagementInterface = $this->createMock(BulkManagementInterface::class);
        $mockBulkManagementInterface->expects($this->exactly(2))
            ->method('scheduleBulk')
            ->withAnyParameters()
            ->willReturn(true);

        $mockUserContextInterface = $this->createMock(UserContextInterface::class);
        $mockUserContextInterface->expects($this->exactly(2))
            ->method('getUserId')
            ->willReturn('9999999');

        $mockCacheManager = $this->createMock(Manager::class);
        $mockCacheManager->expects($this->once())->method('flush')->withAnyParameters()->willReturn(true);
        $mockCacheManager->expects($this->once())->method('getAvailableTypes')->withAnyParameters()->willReturn([]);

        $sut = new ImportRates(
            $mockEventManager,
            $mockMessageManager,
            $mockScopeConfigInterface,
            $mockResourceConfig,
            $mockClientFactory,
            $mockRateFactory,
            $mockRuleFactory,
            $mockRateRepository,
            $mockTaxjarConfig,
            $mockBackupRateOriginAddress,
            $mockIdentityGeneratorInterface,
            $mockSerializerInterface,
            $mockOperationInterfaceFactory,
            $mockBulkManagementInterface,
            $mockUserContextInterface,
            $mockCacheManager
        );

        $result = $sut->execute(new Observer());

        $this->assertInstanceOf(ImportRates::class, $result);
    }

    public function testExecuteWithoutExistingRates()
    {
        $mockEventManager = $this->createMock(EventManagerInterface::class);
        $mockEventManager->expects($this->once())
            ->method('dispatch')
            ->with('taxjar_salestax_import_rates_after');

        $mockMessageManager = $this->createMock(MessageManagerInterface::class);
        $mockMessageManager->expects($this->once())->method('addSuccessMessage');

        $mockScopeConfigInterface = $this->createMock(ScopeConfigInterface::class);
        $mockScopeConfigInterface->expects($this->exactly(5))
            ->method('getValue')
            ->withConsecutive(
                [TaxjarConfig::TAXJAR_BACKUP],
                [TaxjarConfig::TAXJAR_CUSTOMER_TAX_CLASSES],
                [TaxjarConfig::TAXJAR_PRODUCT_TAX_CLASSES],
                ['tax/classes/shipping_tax_class'],
                [TaxjarConfig::TAXJAR_DEBUG]
            )->willReturnOnConsecutiveCalls(
                1,
                'customer_class_1,customer_class_1',
                'product_class_1,product_class_2',
                'shipping_class_1',
                0
            );

        $mockResourceConfig = $this->createMock(Config::class);
        $mockResourceConfig->expects($this->once())->method('saveConfig');

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('getResource')
            ->with('rates', [
                Response::HTTP_FORBIDDEN => __(
                    'Your last backup rate sync from TaxJar was too recent. ' .
                    'Please wait at least 5 minutes and try again.'
                ),
            ])
            ->willReturn([
                'rates' => [
                    'some',
                    'list',
                    'of',
                    'values',
                ],
            ]);

        $mockClientFactory = $this->createMock(ClientFactory::class);
        $mockClientFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockClient);

        $mockCalculation = $this->createMock(Calculation::class);
        $mockCalculation->expects($this->once())
            ->method('deleteByRuleId')
            ->with(42)
            ->willReturnSelf();

        $mockRule = $this->createMock(Rule::class);
        $mockRule->expects($this->once())
            ->method('getId')
            ->willReturn(42);
        $mockRule->expects($this->once())
            ->method('getCalculationModel')
            ->willReturn($mockCalculation);
        $mockRule->expects($this->once())
            ->method('delete');

        $mockRateModel = $this->createMock(Rate::class);
        $mockRateModel->expects($this->once())
            ->method('getExistingRates')
            ->willReturn([]);
        $mockRateModel->expects($this->once())
            ->method('getRule')
            ->willReturn($mockRule);

        $mockRateFactory = $this->createMock(RateFactory::class);
        $mockRateFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockRateModel);

        $mockRuleFactory = $this->createMock(RuleFactory::class);
        $mockRateRepository = $this->createMock(RateRepository::class);

        $mockTaxjarConfig = $this->createMock(TaxjarConfig::class);
        $mockTaxjarConfig->expects($this->once())
            ->method('getApiKey')
            ->willReturn('valid-api-key');

        $mockBackupRateOriginAddress = $this->createMock(BackupRateOriginAddress::class);
        $mockBackupRateOriginAddress->expects($this->once())
            ->method('getShippingZipCode')
            ->willReturn('99999');
        $mockBackupRateOriginAddress->expects($this->once())
            ->method('isScopeCountryCodeUS')
            ->willReturn(true);

        $mockIdentityGeneratorInterface = $this->createMock(IdentityGeneratorInterface::class);
        $mockIdentityGeneratorInterface->expects($this->once())
            ->method('generateId')
            ->willReturn('unique-identifier');

        $mockSerializerInterface = $this->createMock(SerializerInterface::class);
        $mockSerializerInterface->expects($this->once())
            ->method('serialize')
            ->willReturn(json_encode(['data' => 'payload']));

        $mockOperationInterfaceFactory = $this->createMock(OperationInterfaceFactory::class);
        $mockOperationInterfaceFactory->expects($this->once())
            ->method('create')
            ->withAnyParameters()
            ->willReturn($this->createMock(Operation::class));

        $mockBulkManagementInterface = $this->createMock(BulkManagementInterface::class);
        $mockBulkManagementInterface->expects($this->once())
            ->method('scheduleBulk')
            ->withAnyParameters()
            ->willReturn(true);

        $mockUserContextInterface = $this->createMock(UserContextInterface::class);
        $mockUserContextInterface->expects($this->once())
            ->method('getUserId')
            ->willReturn('9999999');

        $mockCacheManager = $this->createMock(Manager::class);
        $mockCacheManager->expects($this->once())->method('getAvailableTypes')->willReturn(['type']);
        $mockCacheManager->expects($this->once())->method('flush')->with(['type'])->willReturn(true);

        $sut = new ImportRates(
            $mockEventManager,
            $mockMessageManager,
            $mockScopeConfigInterface,
            $mockResourceConfig,
            $mockClientFactory,
            $mockRateFactory,
            $mockRuleFactory,
            $mockRateRepository,
            $mockTaxjarConfig,
            $mockBackupRateOriginAddress,
            $mockIdentityGeneratorInterface,
            $mockSerializerInterface,
            $mockOperationInterfaceFactory,
            $mockBulkManagementInterface,
            $mockUserContextInterface,
            $mockCacheManager
        );

        $result = $sut->execute(new Observer());

        $this->assertInstanceOf(ImportRates::class, $result);
    }

    public function testCron()
    {
        $mockEventManager = $this->createMock(EventManagerInterface::class);
        $mockEventManager->expects($this->once())
            ->method('dispatch')
            ->with('taxjar_salestax_import_rates_after');

        $mockMessageManager = $this->createMock(MessageManagerInterface::class);
        $mockMessageManager->expects($this->once())->method('addSuccessMessage');

        $mockScopeConfigInterface = $this->createMock(ScopeConfigInterface::class);
        $mockScopeConfigInterface->expects($this->exactly(5))
            ->method('getValue')
            ->withConsecutive(
                [TaxjarConfig::TAXJAR_BACKUP],
                [TaxjarConfig::TAXJAR_CUSTOMER_TAX_CLASSES],
                [TaxjarConfig::TAXJAR_PRODUCT_TAX_CLASSES],
                ['tax/classes/shipping_tax_class'],
                [TaxjarConfig::TAXJAR_DEBUG]
            )->willReturnOnConsecutiveCalls(
                1,
                'customer_class_1,customer_class_1',
                'product_class_1,product_class_2',
                'shipping_class_1',
                0
            );

        $mockResourceConfig = $this->createMock(Config::class);
        $mockResourceConfig->expects($this->once())->method('saveConfig');

        $mockClient = $this->createMock(Client::class);
        $mockClient->expects($this->once())
            ->method('getResource')
            ->with('rates', [
                Response::HTTP_FORBIDDEN => __(
                    'Your last backup rate sync from TaxJar was too recent. ' .
                    'Please wait at least 5 minutes and try again.'
                ),
            ])
            ->willReturn([
                'rates' => [
                    'some',
                    'list',
                    'of',
                    'values',
                ],
            ]);

        $mockClientFactory = $this->createMock(ClientFactory::class);
        $mockClientFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockClient);

        $mockCalculation = $this->createMock(Calculation::class);
        $mockCalculation->expects($this->once())
            ->method('deleteByRuleId')
            ->with(42)
            ->willReturnSelf();

        $mockRule = $this->createMock(Rule::class);
        $mockRule->expects($this->once())
            ->method('getId')
            ->willReturn(42);
        $mockRule->expects($this->once())
            ->method('getCalculationModel')
            ->willReturn($mockCalculation);
        $mockRule->expects($this->once())
            ->method('delete');

        $mockRateModel = $this->createMock(Rate::class);
        $mockRateModel->expects($this->once())
            ->method('getExistingRates')
            ->willReturn(['old_rate_1', 'old_rate_2']);
        $mockRateModel->expects($this->once())
            ->method('getRule')
            ->willReturn($mockRule);

        $mockRateFactory = $this->createMock(RateFactory::class);
        $mockRateFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockRateModel);

        $mockRuleFactory = $this->createMock(RuleFactory::class);
        $mockRateRepository = $this->createMock(RateRepository::class);

        $mockTaxjarConfig = $this->createMock(TaxjarConfig::class);
        $mockTaxjarConfig->expects($this->once())
            ->method('getApiKey')
            ->willReturn('valid-api-key');

        $mockBackupRateOriginAddress = $this->createMock(BackupRateOriginAddress::class);
        $mockBackupRateOriginAddress->expects($this->once())
            ->method('getShippingZipCode')
            ->willReturn('99999');
        $mockBackupRateOriginAddress->expects($this->once())
            ->method('isScopeCountryCodeUS')
            ->willReturn(true);

        $mockIdentityGeneratorInterface = $this->createMock(IdentityGeneratorInterface::class);
        $mockIdentityGeneratorInterface->expects($this->exactly(2))
            ->method('generateId')
            ->willReturn('unique-identifier');

        $mockSerializerInterface = $this->createMock(SerializerInterface::class);
        $mockSerializerInterface->expects($this->exactly(2))
            ->method('serialize')
            ->willReturn(json_encode(['data' => 'payload']));

        $mockOperationInterfaceFactory = $this->createMock(OperationInterfaceFactory::class);
        $mockOperationInterfaceFactory->expects($this->exactly(2))
            ->method('create')
            ->withAnyParameters()
            ->willReturn($this->createMock(Operation::class));

        $mockBulkManagementInterface = $this->createMock(BulkManagementInterface::class);
        $mockBulkManagementInterface->expects($this->exactly(2))
            ->method('scheduleBulk')
            ->withAnyParameters()
            ->willReturn(true);

        $mockUserContextInterface = $this->createMock(UserContextInterface::class);
        $mockUserContextInterface->expects($this->exactly(2))
            ->method('getUserId')
            ->willReturn('9999999');

        $mockCacheManager = $this->createMock(Manager::class);
        $mockCacheManager->expects($this->once())->method('getAvailableTypes')->willReturn(['type']);
        $mockCacheManager->expects($this->once())->method('flush')->with(['type'])->willReturn(true);


        $sut = new ImportRates(
            $mockEventManager,
            $mockMessageManager,
            $mockScopeConfigInterface,
            $mockResourceConfig,
            $mockClientFactory,
            $mockRateFactory,
            $mockRuleFactory,
            $mockRateRepository,
            $mockTaxjarConfig,
            $mockBackupRateOriginAddress,
            $mockIdentityGeneratorInterface,
            $mockSerializerInterface,
            $mockOperationInterfaceFactory,
            $mockBulkManagementInterface,
            $mockUserContextInterface,
            $mockCacheManager
        );

        $sut->cron();
    }

    public function testExecuteWithoutBackupRatesEnabled()
    {
        $mockEventManager = $this->createMock(EventManagerInterface::class);

        $mockMessageManager = $this->createMock(MessageManagerInterface::class);
        $mockMessageManager->expects($this->once())
            ->method('addNoticeMessage')
            ->with(__('Backup rates imported by TaxJar have been queued for removal.'));

        $mockScopeConfigInterface = $this->createMock(ScopeConfigInterface::class);
        $mockScopeConfigInterface->expects($this->once())
            ->method('getValue')
            ->with(TaxjarConfig::TAXJAR_BACKUP)
            ->willReturn(0);

        $mockResourceConfig = $this->createMock(Config::class);
        $mockResourceConfig->expects($this->exactly(2))->method('saveConfig');

        $mockClientFactory = $this->createMock(ClientFactory::class);

        $mockCalculation = $this->createMock(Calculation::class);
        $mockCalculation->expects($this->once())
            ->method('deleteByRuleId')
            ->with(42)
            ->willReturnSelf();

        $mockRule = $this->createMock(Rule::class);
        $mockRule->expects($this->once())
            ->method('getId')
            ->willReturn(42);
        $mockRule->expects($this->once())
            ->method('getCalculationModel')
            ->willReturn($mockCalculation);
        $mockRule->expects($this->once())
            ->method('delete');

        $mockRateModel = $this->createMock(Rate::class);
        $mockRateModel->expects($this->once())
            ->method('getExistingRates')
            ->willReturn([]);
        $mockRateModel->expects($this->once())
            ->method('getRule')
            ->willReturn($mockRule);

        $mockRateFactory = $this->createMock(RateFactory::class);
        $mockRateFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockRateModel);

        $mockRuleFactory = $this->createMock(RuleFactory::class);
        $mockRateRepository = $this->createMock(RateRepository::class);
        $mockTaxjarConfig = $this->createMock(TaxjarConfig::class);
        $mockBackupRateOriginAddress = $this->createMock(BackupRateOriginAddress::class);
        $mockIdentityGeneratorInterface = $this->createMock(IdentityGeneratorInterface::class);
        $mockSerializerInterface = $this->createMock(SerializerInterface::class);
        $mockOperationInterfaceFactory = $this->createMock(OperationInterfaceFactory::class);
        $mockBulkManagementInterface = $this->createMock(BulkManagementInterface::class);
        $mockUserContextInterface = $this->createMock(UserContextInterface::class);

        $mockCacheManager = $this->createMock(Manager::class);
        $mockCacheManager->expects($this->once())->method('getAvailableTypes')->willReturn(['type']);
        $mockCacheManager->expects($this->once())->method('flush')->with(['type'])->willReturn(true);

        $sut = new ImportRates(
            $mockEventManager,
            $mockMessageManager,
            $mockScopeConfigInterface,
            $mockResourceConfig,
            $mockClientFactory,
            $mockRateFactory,
            $mockRuleFactory,
            $mockRateRepository,
            $mockTaxjarConfig,
            $mockBackupRateOriginAddress,
            $mockIdentityGeneratorInterface,
            $mockSerializerInterface,
            $mockOperationInterfaceFactory,
            $mockBulkManagementInterface,
            $mockUserContextInterface,
            $mockCacheManager
        );

        $result = $sut->execute(new Observer());

        $this->assertInstanceOf(ImportRates::class, $result);
    }
}
