<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Taxjar\SalesTax\Observer\ConfigChanged;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

#[AllowMockObjectsWithoutExpectations]
class ConfigChangedTest extends UnitTestCase
{
    private $observer;
    private $mockCache;
    private $mockEventManager;
    private $mockScopeConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->observer = $this->createStub(Observer::class);
        $this->mockCache = $this->createMock(CacheInterface::class);
        $this->mockEventManager = $this->createMock(ManagerInterface::class);
        $this->mockScopeConfig = $this->createMock(ScopeConfigInterface::class);
    }

    public function testExecuteWithNoEvents()
    {
        // TJ Extension is enabled and Backup Rates feature is disabled
        $this->mockScopeConfig
            ->expects($this->exactly(3))
            ->method('getValue')
            ->willReturnCallback(function ($key) {
                static $callCount = 0;
                $callCount++;

                switch ($callCount) {
                    case 1:
                        $this->assertEquals('tax/taxjar/enabled', $key);
                        return '1';
                    case 2:
                        $this->assertEquals('tax/taxjar/backup', $key);
                        return '0';
                    case 3:
                        $this->assertEquals('tax/taxjar/backup', $key);
                        return '0';
                    default:
                        return null;
                }
            });

        // TJ Extension was enabled and Backup Rates feature was disabled
        $this->mockCache
            ->expects($this->exactly(2))
            ->method('load')
            ->willReturnCallback(function ($key) {
                static $callCount = 0;
                $callCount++;

                switch ($callCount) {
                    case 1:
                        $this->assertEquals('taxjar_salestax_config_enabled', $key);
                        return '1';
                    case 2:
                        $this->assertEquals('taxjar_salestax_config_backup', $key);
                        return '0';
                    default:
                        return null;
                }
            });

        $sut = $this->getTestSubject();
        $sut->execute($this->observer);
    }

    public function testExecuteWhenExtensionChangedToEnabled()
    {
        // TJ Extension is enabled and Backup Rates feature is disabled
        $this->mockScopeConfig
            ->expects($this->exactly(3))
            ->method('getValue')
            ->willReturnCallback(function ($key) {
                static $callCount = 0;
                $callCount++;

                switch ($callCount) {
                    case 1:
                        $this->assertEquals('tax/taxjar/enabled', $key);
                        return '1';
                    case 2:
                        $this->assertEquals('tax/taxjar/backup', $key);
                        return '0';
                    case 3:
                        $this->assertEquals('tax/taxjar/backup', $key);
                        return '0';
                    default:
                        return null;
                }
            });

        // TJ Extension was disabled and Backup Rates feature was disabled
        $this->mockCache
            ->expects($this->exactly(2))
            ->method('load')
            ->willReturnCallback(function ($key) {
                static $callCount = 0;
                $callCount++;

                switch ($callCount) {
                    case 1:
                        $this->assertEquals('taxjar_salestax_config_enabled', $key);
                        return '0';
                    case 2:
                        $this->assertEquals('taxjar_salestax_config_backup', $key);
                        return '0';
                    default:
                        return null;
                }
            });

        // Expect to dispatch events
        $this->mockEventManager
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function ($eventName) {
                static $callCount = 0;
                $callCount++;

                $expectedEvents = [
                    'taxjar_salestax_import_categories',
                    'taxjar_salestax_import_data'
                ];

                $this->assertSame($expectedEvents[$callCount - 1], $eventName);
            });

        $sut = $this->getTestSubject();
        $sut->execute($this->observer);
    }

    public function testExecuteWhenBackupChangedToEnabled()
    {
        // TJ Extension is enabled and Backup Rates feature is enabled
        $this->mockScopeConfig
            ->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnCallback(function ($key) {
                static $callCount = 0;
                $callCount++;

                switch ($callCount) {
                    case 1:
                        $this->assertEquals('tax/taxjar/enabled', $key);
                        return '1';
                    case 2:
                        $this->assertEquals('tax/taxjar/backup', $key);
                        return '1';
                    default:
                        return null;
                }
            });

        // TJ Extension was enabled and Backup Rates feature was disabled
        $this->mockCache
            ->expects($this->exactly(2))
            ->method('load')
            ->willReturnCallback(function ($key) {
                static $callCount = 0;
                $callCount++;

                switch ($callCount) {
                    case 1:
                        $this->assertEquals('taxjar_salestax_config_enabled', $key);
                        return '1';
                    case 2:
                        $this->assertEquals('taxjar_salestax_config_backup', $key);
                        return '0';
                    default:
                        return null;
                }
            });

        // Expect to dispatch events
        $this->mockEventManager
            ->expects($this->exactly(3))
            ->method('dispatch')
            ->willReturnCallback(function ($eventName) {
                static $callCount = 0;
                $callCount++;

                $expectedEvents = [
                    'taxjar_salestax_import_categories',
                    'taxjar_salestax_import_data',
                    'taxjar_salestax_import_rates'
                ];

                $this->assertSame($expectedEvents[$callCount - 1], $eventName);
            });

        $sut = $this->getTestSubject();
        $sut->execute($this->observer);
    }

    public function testExecuteWhenProductTaxClassesChange()
    {
        // TJ Extension is enabled and Backup Rates feature is enabled - PTCs:1,2
        $this->mockScopeConfig
            ->expects($this->exactly(4))
            ->method('getValue')
            ->willReturnCallback(function ($key) {
                static $callCount = 0;
                $callCount++;

                switch ($callCount) {
                    case 1:
                        $this->assertEquals('tax/taxjar/enabled', $key);
                        return '1';
                    case 2:
                        $this->assertEquals('tax/taxjar/backup', $key);
                        return '1';
                    case 3:
                        $this->assertEquals('tax/taxjar/backup', $key);
                        return '1';
                    case 4:
                        $this->assertEquals('tax/taxjar/product_tax_classes', $key);
                        return '1,2';
                    default:
                        return null;
                }
            });

        // TJ Extension was enabled and Backup Rates feature was enabled - PTCs:1
        $this->mockCache
            ->expects($this->exactly(3))
            ->method('load')
            ->willReturnCallback(function ($key) {
                static $callCount = 0;
                $callCount++;

                switch ($callCount) {
                    case 1:
                        $this->assertEquals('taxjar_salestax_config_enabled', $key);
                        return '1';
                    case 2:
                        $this->assertEquals('taxjar_salestax_config_backup', $key);
                        return '1';
                    case 3:
                        $this->assertEquals('taxjar_salestax_backup_rates_ptcs', $key);
                        return '1';
                    default:
                        return null;
                }
            });

        // Expect to dispatch events
        $this->mockEventManager
            ->expects($this->exactly(3))
            ->method('dispatch')
            ->willReturnCallback(function ($eventName) {
                static $callCount = 0;
                $callCount++;

                $expectedEvents = [
                    'taxjar_salestax_import_categories',
                    'taxjar_salestax_import_data',
                    'taxjar_salestax_import_rates'
                ];

                $this->assertSame($expectedEvents[$callCount - 1], $eventName);
            });

        $sut = $this->getTestSubject();
        $sut->execute($this->observer);
    }
    public function testExecuteWhenCustomerTaxClassesChange()
    {
        // TJ Extension is enabled and Backup Rates feature is enabled - PTCs:1,2 - CTCs:2
        $this->mockScopeConfig
            ->expects($this->exactly(5))
            ->method('getValue')
            ->willReturnCallback(function ($key) {
                static $callCount = 0;
                $callCount++;

                switch ($callCount) {
                    case 1:
                        $this->assertEquals('tax/taxjar/enabled', $key);
                        return '1';
                    case 2:
                        $this->assertEquals('tax/taxjar/backup', $key);
                        return '1';
                    case 3:
                        $this->assertEquals('tax/taxjar/backup', $key);
                        return '1';
                    case 4:
                        $this->assertEquals('tax/taxjar/product_tax_classes', $key);
                        return '1,2';
                    case 5:
                        $this->assertEquals('tax/taxjar/customer_tax_classes', $key);
                        return '2';
                    default:
                        return null;
                }
            });

        // TJ Extension was enabled and Backup Rates feature was enabled - PTCs:1,2 - CTCs:1
        $this->mockCache
            ->expects($this->exactly(4))
            ->method('load')
            ->willReturnCallback(function ($key) {
                static $callCount = 0;
                $callCount++;

                switch ($callCount) {
                    case 1:
                        $this->assertEquals('taxjar_salestax_config_enabled', $key);
                        return '1';
                    case 2:
                        $this->assertEquals('taxjar_salestax_config_backup', $key);
                        return '1';
                    case 3:
                        $this->assertEquals('taxjar_salestax_backup_rates_ptcs', $key);
                        return '1,2';
                    case 4:
                        $this->assertEquals('taxjar_salestax_backup_rates_ctcs', $key);
                        return '1';
                    default:
                        return null;
                }
            });

        // Expect to dispatch events
        $this->mockEventManager
            ->expects($this->exactly(3))
            ->method('dispatch')
            ->willReturnCallback(function ($eventName) {
                static $callCount = 0;
                $callCount++;

                $expectedEvents = [
                    'taxjar_salestax_import_categories',
                    'taxjar_salestax_import_data',
                    'taxjar_salestax_import_rates'
                ];

                $this->assertSame($expectedEvents[$callCount - 1], $eventName);
            });

        $sut = $this->getTestSubject();
        $sut->execute($this->observer);
    }

    protected function getTestSubject(): ConfigChanged
    {
        return new ConfigChanged($this->mockCache, $this->mockEventManager, $this->mockScopeConfig);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->observer = null;
        $this->mockCache = null;
        $this->mockEventManager = null;
        $this->mockScopeConfig = null;
    }
}
