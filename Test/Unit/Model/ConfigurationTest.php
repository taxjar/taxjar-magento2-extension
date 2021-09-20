<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Model;

use Magento\Config\Model\ResourceModel\Config as MagentoConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Taxjar\SalesTax\Model\Configuration;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class ConfigurationTest extends UnitTestCase
{
    public function testGetApiUrl()
    {
        $mockMagentoConfig = $this->createMock(MagentoConfig::class);
        $mockScopeConfig = $this->createMock(ScopeConfigInterface::class);
        $mockScopeConfig
            ->expects($this->once())
            ->method('getValue')
            ->with('tax/taxjar/sandbox')
            ->willReturn('0');

        $sut = new Configuration($mockMagentoConfig, $mockScopeConfig);
        $result = $sut->getApiUrl();

        self::assertSame('https://api.taxjar.com/v2', $result);
    }

    public function testGetApiUrlWithSandboxEnabled()
    {
        $mockMagentoConfig = $this->createMock(MagentoConfig::class);
        $mockScopeConfig = $this->createMock(ScopeConfigInterface::class);
        $mockScopeConfig
            ->expects($this->once())
            ->method('getValue')
            ->with('tax/taxjar/sandbox')
            ->willReturn('1');

        $sut = new Configuration($mockMagentoConfig, $mockScopeConfig);
        $result = $sut->getApiUrl();

        self::assertSame('https://api.sandbox.taxjar.com/v2', $result);
    }

    public function testGetApiKey()
    {
        $mockMagentoConfig = $this->createMock(MagentoConfig::class);
        $mockScopeConfig = $this->createMock(ScopeConfigInterface::class);
        $mockScopeConfig
            ->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive(
                ['tax/taxjar/sandbox'],
                ['tax/taxjar/apikey', 'default', null]
            )
            ->willReturnOnConsecutiveCalls('0', ' some -api- key');

        $sut = new Configuration($mockMagentoConfig, $mockScopeConfig);
        $result = $sut->getApiKey();

        self::assertEquals('some-api-key', $result);
    }

    public function testSandboxEnabled()
    {
        $mockMagentoConfig = $this->createMock(MagentoConfig::class);
        $mockScopeConfig = $this->createMock(ScopeConfigInterface::class);
        $mockScopeConfig
            ->expects($this->once())
            ->method('getValue')
            ->with('tax/taxjar/sandbox')
            ->willReturn('1');

        $sut = new Configuration($mockMagentoConfig, $mockScopeConfig);

        self::assertTrue($sut->isSandboxEnabled());
    }

    public function testSandboxNotEnabled()
    {
        $mockMagentoConfig = $this->createMock(MagentoConfig::class);
        $mockScopeConfig = $this->createMock(ScopeConfigInterface::class);
        $mockScopeConfig
            ->expects($this->once())
            ->method('getValue')
            ->with('tax/taxjar/sandbox')
            ->willReturn('0');

        $sut = new Configuration($mockMagentoConfig, $mockScopeConfig);

        self::assertFalse($sut->isSandboxEnabled());
    }

    public function testSetTaxBasisShipping()
    {
        $mockMagentoConfig = $this->createMock(MagentoConfig::class);
        $mockMagentoConfig
            ->expects($this->once())
            ->method('saveConfig')
            ->with('tax/calculation/based_on', 'shipping', 'default');

        $mockScopeConfig = $this->createMock(ScopeConfigInterface::class);

        $sut = new Configuration($mockMagentoConfig, $mockScopeConfig);
        $sut->setTaxBasis(['tax_source' => null]);
    }

    public function testSetTaxBasisOrigin()
    {
        $mockMagentoConfig = $this->createMock(MagentoConfig::class);
        $mockMagentoConfig
            ->expects($this->once())
            ->method('saveConfig')
            ->with('tax/calculation/based_on', 'origin', 'default');

        $mockScopeConfig = $this->createMock(ScopeConfigInterface::class);

        $sut = new Configuration($mockMagentoConfig, $mockScopeConfig);
        $sut->setTaxBasis(['tax_source' => 'origin']);
    }

    public function testGetBackupRateCount()
    {
        $mockMagentoConfig = $this->createMock(MagentoConfig::class);
        $mockScopeConfig = $this->createMock(ScopeConfigInterface::class);
        $mockScopeConfig
            ->expects($this->once())
            ->method('getValue')
            ->with('tax/taxjar/backup_rate_count')
            ->willReturn('55');

        $sut = new Configuration($mockMagentoConfig, $mockScopeConfig);

        self::assertSame(55, $sut->getBackupRateCount());
    }

    public function testSetBackupRateCount()
    {
        $mockMagentoConfig = $this->createMock(MagentoConfig::class);
        $mockMagentoConfig
            ->expects($this->once())
            ->method('saveConfig')
            ->with('tax/taxjar/backup_rate_count', 99, 'default');

        $mockScopeConfig = $this->createMock(ScopeConfigInterface::class);

        $sut = new Configuration($mockMagentoConfig, $mockScopeConfig);
        $sut->setBackupRateCount(99);
    }

    public function testSetDisplaySettings()
    {
        $mockMagentoConfig = $this->createMock(MagentoConfig::class);
        $mockMagentoConfig
            ->expects($this->exactly(5))
            ->method('saveConfig')
            ->withConsecutive(
                ['tax/display/type', 1],
                ['tax/display/shipping', 1],
                ['tax/cart_display/price', 1],
                ['tax/cart_display/subtotal', 1],
                ['tax/cart_display/shipping', 1]
            )->willReturnOnConsecutiveCalls(null, null, null, null, null);

        $mockScopeConfig = $this->createMock(ScopeConfigInterface::class);

        $sut = new Configuration($mockMagentoConfig, $mockScopeConfig);
        $sut->setDisplaySettings();
    }
}
