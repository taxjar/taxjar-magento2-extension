<?php

namespace Taxjar\SalesTax\Test\Unit\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Taxjar\SalesTax\Block\Adminhtml\Backup;
use Taxjar\SalesTax\Model\Configuration;
use Taxjar\SalesTax\Model\Import\Rate;
use Taxjar\SalesTax\Model\Import\RateFactory;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class BackupTest extends UnitTestCase
{
    /**
     * @var ScopeConfigInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Context|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contextMock;

    /**
     * @var CacheInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cacheMock;

    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|RateFactory
     */
    private $rateFactoryMock;

    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|Configuration
     */
    private $taxjarConfigMock;

    /**
     * @var array
     */
    private $dataMock;

    /**
     * @var Backup
     */
    private $sut;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cacheMock = $this->getMockBuilder(CacheInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->rateFactoryMock = $this->getMockBuilder(RateFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->taxjarConfigMock = $this->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataMock = [];

        parent::setUp();
    }

    /**
     * @param $value
     * @dataProvider backupRatesConfigDataProvider
     */
    public function testIsEnabled($value)
    {
        $this->scopeConfigMock->expects(static::any())
            ->method('getValue')
            ->with('tax/taxjar/backup')
            ->willReturn($value);

        $this->setExpectations();

        self::assertEquals($value, $this->sut->isEnabled());
    }

    /**
     * @return array
     */
    public function backupRatesConfigDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }

    /**
     * @param $rateValue
     * @param $rateCount
     * @param $expected
     * @dataProvider getRatesLoadedDataProvider
     */
    public function testGetRatesLoadedText($rateValue, $rateCount, $expected)
    {
        $rateMock = $this->getMockBuilder(Rate::class)
            ->disableOriginalConstructor()
            ->getMock();

        $rateMock->expects(static::once())
            ->method('getExistingRates')
            ->willReturn($rateValue);

        $this->rateFactoryMock->expects(static::once())
            ->method('create')
            ->willReturn($rateMock);

        $this->taxjarConfigMock->expects(static::once())
            ->method('getBackupRateCount')
            ->willReturn($rateCount);

        $this->setExpectations();

        self::assertEquals($expected, $this->sut->getRatesLoadedText());
    }

    /**
     * @return array
     */
    public function getRatesLoadedDataProvider(): array
    {
        return [
            [[], 0, '0 of 0 expected rates loaded.'],
            [['rate-1'], 3, '1 of 3 expected rates loaded.'],
            [['rate-1', 'rate-2'], 3, '2 of 3 expected rates loaded.'],
            [['rate-1', 'rate-2', 'rate-3'], 3, '3 of 3 expected rates loaded.']
        ];
    }

    /**
     * @param $value
     * @param $expected
     * @dataProvider lastSyncedDateDataProvider
     */
    public function testGetLastSyncedDateTest($value, $expected)
    {
        $this->scopeConfigMock->expects(static::any())
            ->method('getValue')
            ->with('tax/taxjar/last_update')
            ->willReturn($value);

        $this->setExpectations();

        self::assertEquals($expected, $this->sut->getLastSyncedDateText());
    }

    /**
     * @return array
     */
    public function lastSyncedDateDataProvider(): array
    {
        return [
            ['some_date_value', 'Last synced on some_date_value'],
            [null, 'Last synced on N/A']
        ];
    }

    protected function setExpectations(): void
    {
        $this->contextMock->expects(static::any())
            ->method('getScopeConfig')
            ->willReturn($this->scopeConfigMock);

        $this->sut = new Backup(
            $this->contextMock,
            $this->cacheMock,
            $this->rateFactoryMock,
            $this->taxjarConfigMock,
            $this->dataMock
        );
    }
}
