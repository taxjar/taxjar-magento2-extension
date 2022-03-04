<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Model\Import;

use Magento\AsynchronousOperations\Model\Operation;
use Magento\Config\Model\ResourceModel\Config\Data\Collection;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DB\LoggerInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\Import\CreateRatesConsumer;
use Taxjar\SalesTax\Model\Import\RateFactory;
use Taxjar\SalesTax\Model\Import\RuleFactory;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class CreateRatesConsumerTest extends UnitTestCase
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
     * @var CollectionFactory
     */
    private $configCollection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->taxjarConfig = $this->createMock(TaxjarConfig::class);
        $this->rateFactory = $this->getMockBuilder(RateFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->ruleFactory = $this->getMockBuilder(RuleFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->configCollection = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
    }

    public function testValidatePayloadReturnsSelfWithValidPayload()
    {
        $sut = $this->getTestSubject();
        $sut->setPayload([
            'rates' => '',
            'product_tax_classes' => '',
            'customer_tax_classes' => '',
            'shipping_tax_class' => '',
        ]);
        $result = $this->callMethod($sut, 'validatePayload');

        self::assertInstanceOf(CreateRatesConsumer::class, $result);
    }

    public function testValidatePayloadThrowsExceptionWithInvalidPayload()
    {
        $sut = $this->getTestSubject();
        $sut->setPayload(null);

        self::expectException(LocalizedException::class);

        $this->callMethod($sut, 'validatePayload');
    }

    public function testValidateBackupRatesEnabledReturnsSelfWhenEnabled()
    {
        $dataObject = new DataObject();
        $dataObject->setData('value', '1');

        $mockCollection = $this->createMock(Collection::class);
        $mockCollection->expects($this->once())->method('addFieldToFilter')->willReturnSelf();
        $mockCollection->expects($this->once())->method('getFirstItem')->willReturn($dataObject);

        $this->configCollection->expects($this->once())
            ->method('create')
            ->willReturn($mockCollection);

        $sut = $this->getTestSubject();
        $result = $this->callMethod($sut, 'validateBackupRatesEnabled');

        self::assertInstanceOf(CreateRatesConsumer::class, $result);
    }

    public function testValidateBackupRatesEnabledThrowsExceptionWhenDisabled()
    {
        $dataObject = new DataObject();
        $dataObject->setData('value', '0');

        $mockCollection = $this->createMock(Collection::class);
        $mockCollection->expects($this->once())->method('addFieldToFilter')->willReturnSelf();
        $mockCollection->expects($this->once())->method('getFirstItem')->willReturn($dataObject);

        $this->configCollection->expects($this->once())
            ->method('create')
            ->willReturn($mockCollection);

        $sut = $this->getTestSubject();

        self::expectException(LocalizedException::class);

        $this->callMethod($sut, 'validateBackupRatesEnabled');
    }

    public function testValidateApiKeyReturnsSelfWhenApiKeyIsSet()
    {
        $this->taxjarConfig->expects($this->once())
            ->method('getApiKey')
            ->willReturn('valid-api-key');

        $sut = $this->getTestSubject();
        $result = $this->callMethod($sut, 'validateApiKey');

        self::assertInstanceOf(CreateRatesConsumer::class, $result);
    }

    public function testValidateApiKeyThrowsExceptionWhenApiKeyEmpty()
    {
        $this->taxjarConfig->expects($this->once())
            ->method('getApiKey')
            ->willReturn('');

        $sut = $this->getTestSubject();

        self::expectException(LocalizedException::class);

        $this->callMethod($sut, 'validateApiKey');
    }

    public function testBackupRatesEnabled()
    {
        $dataObject = new DataObject();
        $dataObject->setData('value', '1');

        $mockCollection = $this->createMock(Collection::class);
        $mockCollection->expects($this->once())->method('addFieldToFilter')->willReturnSelf();
        $mockCollection->expects($this->once())->method('getFirstItem')->willReturn($dataObject);

        $this->configCollection->expects($this->once())
            ->method('create')
            ->willReturn($mockCollection);

        $sut = $this->getTestSubject();
        $result = $this->callMethod($sut, 'backupRatesEnabled');

        self::assertTrue($result);
    }

    protected function getTestSubject(): CreateRatesConsumer
    {
        return new CreateRatesConsumer(
            $this->serializer,
            $this->scopeConfig,
            $this->logger,
            $this->entityManager,
            $this->taxjarConfig,
            $this->rateFactory,
            $this->ruleFactory,
            $this->configCollection
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->serializer = null;
        $this->scopeConfig = null;
        $this->logger = null;
        $this->entityManager = null;
        $this->taxjarConfig = null;
        $this->rateFactory = null;
        $this->ruleFactory = null;
        $this->configCollection = null;
    }
}
