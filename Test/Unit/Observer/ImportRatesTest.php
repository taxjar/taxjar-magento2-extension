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
use Magento\Tax\Model\Config as MagentoTaxConfig;
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
    /**
     * @var EventManagerInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $eventManager;
    /**
     * @var MessageManagerInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageManager;
    /**
     * @var ScopeConfigInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfig;
    /**
     * @var Config|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resourceConfig;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|ClientFactory
     */
    private $clientFactory;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|RateFactory
     */
    private $rateFactory;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|RuleFactory
     */
    private $ruleFactory;
    /**
     * @var RateRepository|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $rateRepository;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|TaxjarConfig
     */
    private $taxjarConfig;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|BackupRateOriginAddress
     */
    private $backupRateOriginAddress;
    /**
     * @var IdentityGeneratorInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $identityService;
    /**
     * @var SerializerInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $serializer;
    /**
     * @var OperationInterfaceFactory|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $operationFactory;
    /**
     * @var BulkManagementInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $bulkManagement;
    /**
     * @var UserContextInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $userContext;
    /**
     * @var Manager|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cacheManager;
    /**
     * @var array[]
     */
    private $mockRates;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventManager = $this->createMock(EventManagerInterface::class);
        $this->messageManager = $this->createMock(MessageManagerInterface::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->resourceConfig = $this->createMock(Config::class);
        $this->clientFactory = $this->getMockBuilder(ClientFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->rateFactory = $this->getMockBuilder(RateFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->ruleFactory = $this->getMockBuilder(RuleFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->rateRepository = $this->createMock(RateRepository::class);
        $this->taxjarConfig = $this->createMock(TaxjarConfig::class);
        $this->backupRateOriginAddress = $this->createMock(BackupRateOriginAddress::class);
        $this->identityService = $this->createMock(IdentityGeneratorInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->operationFactory = $this->getMockBuilder(OperationInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->bulkManagement = $this->createMock(BulkManagementInterface::class);
        $this->userContext = $this->createMock(UserContextInterface::class);
        $this->cacheManager = $this->createMock(Manager::class);

        $this->mockRates = [
            [
                'state' => 'TX',
                'zip' => 78758,
            ],
        ];
    }

    public function testExecuteWithBackupRatesEnabledAndValidApiKeyWhenDebugEnabled(): void
    {
        $this->scopeConfig->expects($this->exactly(5))
            ->method('getValue')
            ->withConsecutive(
                [TaxjarConfig::TAXJAR_BACKUP],
                [TaxjarConfig::TAXJAR_CUSTOMER_TAX_CLASSES],
                [TaxjarConfig::TAXJAR_PRODUCT_TAX_CLASSES],
                [MagentoTaxConfig::CONFIG_XML_PATH_SHIPPING_TAX_CLASS],
                [TaxjarConfig::TAXJAR_DEBUG]
            )->willReturnOnConsecutiveCalls(
                '1',
                '1',
                '2,3',
                '',
                '1'
            );

        $this->messageManager->expects($this->once())
            ->method('addNoticeMessage')
            ->with('Debug mode enabled. Backup tax rates have not been altered.');

        $this->clientFactory->expects($this->once())->method('create')->willReturn(
            $this->getMockClient($this->mockRates)
        );
        $this->taxjarConfig->expects($this->once())->method('getApiKey')->willReturn('valid-api-key');
        $this->backupRateOriginAddress->expects($this->once())->method('getShippingZipCode')->willReturn('99999');

        $sut = $this->getTestSubject();
        $result = $sut->execute(new Observer());

        $this->assertNull($result);
    }

    public function testExecuteWithInvalidZipCodeThrowsException(): void
    {
        $this->scopeConfig->expects($this->exactly(4))
            ->method('getValue')
            ->withConsecutive(
                [TaxjarConfig::TAXJAR_BACKUP],
                [TaxjarConfig::TAXJAR_CUSTOMER_TAX_CLASSES],
                [TaxjarConfig::TAXJAR_PRODUCT_TAX_CLASSES],
                [MagentoTaxConfig::CONFIG_XML_PATH_SHIPPING_TAX_CLASS]
            )->willReturnOnConsecutiveCalls(
                '1',
                '1',
                '2,3',
                ''
            );

        $this->clientFactory->expects($this->once())->method('create')->willReturn($this->getMockClient());
        $this->taxjarConfig->expects($this->once())->method('getApiKey')->willReturn('valid-api-key');

        $this->backupRateOriginAddress->expects($this->once())->method('getShippingZipCode')->willReturn('1234567');
        $this->backupRateOriginAddress->expects($this->once())->method('isScopeCountryCodeUS')->willReturn(true);

        $sut = $this->getTestSubject();

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Please check that your zip code is a valid US zip code in Shipping Settings.');

        $sut->execute(new Observer());
    }

    public function testExecuteWithInvalidTaxClassesThrowsException(): void
    {
        $this->scopeConfig->expects($this->exactly(4))
            ->method('getValue')
            ->withConsecutive(
                [TaxjarConfig::TAXJAR_BACKUP],
                [TaxjarConfig::TAXJAR_CUSTOMER_TAX_CLASSES],
                [TaxjarConfig::TAXJAR_PRODUCT_TAX_CLASSES],
                [MagentoTaxConfig::CONFIG_XML_PATH_SHIPPING_TAX_CLASS]
            )->willReturnOnConsecutiveCalls(
                '1',
                '',
                '',
                ''
            );

        $this->clientFactory->expects($this->once())->method('create')->willReturn($this->getMockClient());
        $this->taxjarConfig->expects($this->once())->method('getApiKey')->willReturn('valid-api-key');

        $this->backupRateOriginAddress->expects($this->once())->method('getShippingZipCode')->willReturn('99999');
        $this->backupRateOriginAddress->expects($this->once())->method('isScopeCountryCodeUS')->willReturn(true);

        $sut = $this->getTestSubject();

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage(
            'Please select at least one product tax class and one customer tax class to ' .
            'configure backup rates from TaxJar.'
        );

        $sut->execute(new Observer());
    }

    public function testExecuteWithInvalidShippingTaxClassThrowsException(): void
    {
        $this->scopeConfig->expects($this->exactly(4))
            ->method('getValue')
            ->withConsecutive(
                [TaxjarConfig::TAXJAR_BACKUP],
                [TaxjarConfig::TAXJAR_CUSTOMER_TAX_CLASSES],
                [TaxjarConfig::TAXJAR_PRODUCT_TAX_CLASSES],
                [MagentoTaxConfig::CONFIG_XML_PATH_SHIPPING_TAX_CLASS]
            )->willReturnOnConsecutiveCalls(
                '1',
                '2',
                '1',
                '1'
            );

        $this->clientFactory->expects($this->once())->method('create')->willReturn($this->getMockClient());
        $this->taxjarConfig->expects($this->once())->method('getApiKey')->willReturn('valid-api-key');

        $this->backupRateOriginAddress->expects($this->once())->method('getShippingZipCode')->willReturn('99999');
        $this->backupRateOriginAddress->expects($this->once())->method('isScopeCountryCodeUS')->willReturn(true);

        $sut = $this->getTestSubject();

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage(
            'For backup shipping rates, please use a unique tax class for shipping.'
        );

        $sut->execute(new Observer());
    }

    public function testExecuteThrowsExceptionWhenScheduleBulkOperationFails(): void
    {
        $this->scopeConfig->expects($this->exactly(5))
            ->method('getValue')
            ->withConsecutive(
                [TaxjarConfig::TAXJAR_BACKUP],
                [TaxjarConfig::TAXJAR_CUSTOMER_TAX_CLASSES],
                [TaxjarConfig::TAXJAR_PRODUCT_TAX_CLASSES],
                ['tax/classes/shipping_tax_class'],
                [TaxjarConfig::TAXJAR_DEBUG]
            )->willReturnOnConsecutiveCalls(
                '1',
                '1',
                '2,3',
                '',
                '0'
            );

        $this->clientFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->getMockClient($this->mockRates));
        $this->taxjarConfig->expects($this->once())->method('getApiKey')->willReturn('valid-api-key');
        $this->backupRateOriginAddress->expects($this->once())->method('getShippingZipCode')->willReturn('99999');
        $this->backupRateOriginAddress->expects($this->once())->method('isScopeCountryCodeUS')->willReturn(false);
        $this->identityService->expects($this->once())->method('generateId')->willReturn('unique-identifier');
        $this->serializer->expects($this->once())->method('serialize')->willReturn(json_encode(['data' => 'payload']));
        $this->operationFactory->expects($this->once())->method('create')->withAnyParameters()->willReturn(
            $this->createMock(Operation::class)
        );
        $this->bulkManagement->expects($this->once())->method('scheduleBulk')->withAnyParameters()->willReturn(false);
        $this->userContext->expects($this->once())->method('getUserId')->willReturn('9999999');

        $sut = $this->getTestSubject();

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage(
            'Something went wrong while processing the request.'
        );

        $sut->execute(new Observer());
    }

    public function testExecuteWithExistingRates()
    {
        $this->eventManager->expects($this->once())->method('dispatch')->with('taxjar_salestax_import_rates_after');
        $this->messageManager->expects($this->once())->method('addSuccessMessage');

        $this->scopeConfig->expects($this->exactly(5))
            ->method('getValue')
            ->withConsecutive(
                [TaxjarConfig::TAXJAR_BACKUP],
                [TaxjarConfig::TAXJAR_CUSTOMER_TAX_CLASSES],
                [TaxjarConfig::TAXJAR_PRODUCT_TAX_CLASSES],
                ['tax/classes/shipping_tax_class'],
                [TaxjarConfig::TAXJAR_DEBUG]
            )->willReturnOnConsecutiveCalls(
                '1',
                '1',
                '2,3',
                '',
                '0'
            );

        $this->resourceConfig->expects($this->exactly(2))->method('saveConfig');
        $this->clientFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->getMockClient($this->mockRates));

        $mockRateModel = $this->createMock(Rate::class);
        $mockRateModel->expects($this->once())->method('getExistingRates')->willReturn(['old_rate_1', 'old_rate_2']);

        $this->rateFactory->expects($this->once())->method('create')->willReturn($mockRateModel);
        $this->ruleFactory = $this->createMock(RuleFactory::class);

        $mockRate = $this->createMock(Calculation\Rate::class);
        $mockRate->expects($this->any())->method('getCode')->willReturn('US-TX-*');

        $this->rateRepository->expects($this->any())->method('get')->willReturn($mockRate);
        $this->taxjarConfig->expects($this->once())->method('getApiKey')->willReturn('valid-api-key');
        $this->backupRateOriginAddress->expects($this->once())->method('getShippingZipCode')->willReturn('99999');
        $this->backupRateOriginAddress->expects($this->once())->method('isScopeCountryCodeUS')->willReturn(true);
        $this->identityService->expects($this->exactly(2))->method('generateId')->willReturn('unique-identifier');
        $this->serializer->expects($this->exactly(2))
            ->method('serialize')
            ->willReturn(json_encode(['data' => 'payload']));
        $this->operationFactory->expects($this->exactly(2))
            ->method('create')
            ->withAnyParameters()
            ->willReturn($this->createMock(Operation::class));
        $this->bulkManagement->expects($this->exactly(2))
            ->method('scheduleBulk')
            ->withAnyParameters()
            ->willReturn(true);
        $this->userContext->expects($this->exactly(2))->method('getUserId')->willReturn('9999999');
        $this->cacheManager->expects($this->once())->method('flush')->withAnyParameters()->willReturn(true);

        $sut = $this->getTestSubject();
        $result = $sut->execute(new Observer());

        $this->assertNull($result);
    }

    public function testExecuteWithoutExistingRates()
    {
        $this->eventManager->expects($this->once())->method('dispatch')->with('taxjar_salestax_import_rates_after');
        $this->messageManager->expects($this->once())->method('addSuccessMessage');

        $this->scopeConfig->expects($this->exactly(5))
            ->method('getValue')
            ->withConsecutive(
                [TaxjarConfig::TAXJAR_BACKUP],
                [TaxjarConfig::TAXJAR_CUSTOMER_TAX_CLASSES],
                [TaxjarConfig::TAXJAR_PRODUCT_TAX_CLASSES],
                ['tax/classes/shipping_tax_class'],
                [TaxjarConfig::TAXJAR_DEBUG]
            )->willReturnOnConsecutiveCalls(
                '1',
                '1',
                '2,3',
                '',
                '0'
            );

        $this->resourceConfig->expects($this->exactly(2))->method('saveConfig');
        $this->clientFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->getMockClient($this->mockRates));

        $mockRateModel = $this->createMock(Rate::class);
        $mockRateModel->expects($this->once())->method('getExistingRates')->willReturn([]);

        $this->rateFactory->expects($this->once())->method('create')->willReturn($mockRateModel);
        $this->taxjarConfig->expects($this->once())->method('getApiKey')->willReturn('valid-api-key');
        $this->backupRateOriginAddress->expects($this->once())->method('getShippingZipCode')->willReturn('99999');
        $this->backupRateOriginAddress->expects($this->once())->method('isScopeCountryCodeUS')->willReturn(true);
        $this->identityService->expects($this->once())->method('generateId')->willReturn('unique-identifier');
        $this->serializer->expects($this->once())->method('serialize')->willReturn(json_encode(['data' => 'payload']));
        $this->operationFactory->expects($this->once())->method('create')->withAnyParameters()->willReturn(
            $this->createMock(Operation::class)
        );
        $this->bulkManagement->expects($this->once())->method('scheduleBulk')->withAnyParameters()->willReturn(true);
        $this->userContext->expects($this->once())->method('getUserId')->willReturn('9999999');

        $sut = $this->getTestSubject();
        $result = $sut->execute(new Observer());

        $this->assertNull($result);
    }

    public function testCron()
    {
        $this->eventManager->expects($this->once())->method('dispatch')->with('taxjar_salestax_import_rates_after');
        $this->messageManager->expects($this->once())->method('addSuccessMessage');

        $this->scopeConfig->expects($this->exactly(5))
            ->method('getValue')
            ->withConsecutive(
                [TaxjarConfig::TAXJAR_BACKUP],
                [TaxjarConfig::TAXJAR_CUSTOMER_TAX_CLASSES],
                [TaxjarConfig::TAXJAR_PRODUCT_TAX_CLASSES],
                ['tax/classes/shipping_tax_class'],
                [TaxjarConfig::TAXJAR_DEBUG]
            )->willReturnOnConsecutiveCalls(
                '1',
                '1',
                '2,3',
                '',
                '0'
            );

        $this->resourceConfig->expects($this->any())->method('saveConfig');
        $this->clientFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->getMockClient($this->mockRates));

        $mockRateModel = $this->createMock(Rate::class);
        $mockRateModel->expects($this->once())->method('getExistingRates')->willReturn(['old_rate_1', 'old_rate_2']);

        $this->rateFactory->expects($this->once())->method('create')->willReturn($mockRateModel);

        $mockRate = $this->createMock(Calculation\Rate::class);
        $mockRate->expects($this->any())->method('getCode')->willReturn('US-TX-*');

        $this->rateRepository->expects($this->any())->method('get')->willReturn($mockRate);
        $this->taxjarConfig->expects($this->once())->method('getApiKey')->willReturn('valid-api-key');
        $this->backupRateOriginAddress->expects($this->once())->method('getShippingZipCode')->willReturn('99999');
        $this->backupRateOriginAddress->expects($this->once())->method('isScopeCountryCodeUS')->willReturn(true);
        $this->identityService->expects($this->exactly(2))->method('generateId')->willReturn('unique-identifier');
        $this->serializer->expects($this->exactly(2))
            ->method('serialize')
            ->willReturn(json_encode(['data' => 'payload']));
        $this->operationFactory->expects($this->exactly(2))->method('create')->withAnyParameters()->willReturn(
            $this->createMock(Operation::class)
        );

        $this->bulkManagement->expects($this->exactly(2))
            ->method('scheduleBulk')->withAnyParameters()
            ->willReturn(true);
        $this->userContext->expects($this->exactly(2))->method('getUserId')->willReturn('9999999');

        $sut = $this->getTestSubject();
        $result = $sut->cron();

        $this->assertNull($result);
    }

    public function testExecuteWithoutBackupRatesEnabled()
    {
        $this->messageManager->expects($this->once())->method('addNoticeMessage')
            ->with('Backup rates imported by TaxJar have been queued for removal.');

        $this->scopeConfig->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive(
                [TaxjarConfig::TAXJAR_BACKUP],
                [TaxjarConfig::TAXJAR_DEBUG]
            )->willReturnOnConsecutiveCalls(
                '0',
                '0'
            );

        $this->resourceConfig->expects($this->exactly(2))->method('saveConfig');

        $mockRateModel = $this->createMock(Rate::class);
        $mockRateModel->expects($this->once())
            ->method('getExistingRates')
            ->willReturn([]);

        $this->rateFactory->expects($this->once())->method('create')->willReturn($mockRateModel);

        $sut = $this->getTestSubject();
        $result = $sut->execute(new Observer());

        $this->assertNull($result);
    }

    private function getMockClient(?array $payload = null)
    {
        $mockClient = $this->createMock(Client::class);

        if ($payload) {
            $mockClient->expects($this->once())
                ->method('getResource')
                ->willReturn([
                    'rates' => $payload,
                ]);
        }

        return $mockClient;
    }

    private function getTestSubject(): ImportRates
    {
        return new ImportRates(
            $this->eventManager,
            $this->messageManager,
            $this->scopeConfig,
            $this->resourceConfig,
            $this->clientFactory,
            $this->rateFactory,
            $this->ruleFactory,
            $this->rateRepository,
            $this->taxjarConfig,
            $this->backupRateOriginAddress,
            $this->identityService,
            $this->serializer,
            $this->operationFactory,
            $this->bulkManagement,
            $this->userContext,
            $this->cacheManager
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->eventManager = null;
        $this->messageManager = null;
        $this->scopeConfig = null;
        $this->resourceConfig = null;
        $this->clientFactory = null;
        $this->rateFactory = null;
        $this->ruleFactory = null;
        $this->rateRepository = null;
        $this->taxjarConfig = null;
        $this->backupRateOriginAddress = null;
        $this->identityService = null;
        $this->serializer = null;
        $this->operationFactory = null;
        $this->bulkManagement = null;
        $this->userContext = null;
        $this->cacheManager = null;
    }
}
