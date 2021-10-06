<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Taxjar\SalesTax\Observer\ConfigChanged;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class ConfigChangedTest extends UnitTestCase
{
    private $observer;
    private $mockCache;
    private $mockEventManager;
    private $mockScopeConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->observer = $this->createMock(Observer::class);
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
            ->withConsecutive(
                ['tax/taxjar/enabled'],
                ['tax/taxjar/backup'],
                ['tax/taxjar/backup']
            )
            ->willReturnOnConsecutiveCalls('1', '0', '0');

        // TJ Extension was enabled and Backup Rates feature was disabled
        $this->mockCache
            ->expects($this->exactly(2))
            ->method('load')
            ->withConsecutive(
                ['taxjar_salestax_config_enabled'],
                ['taxjar_salestax_config_backup']
            )
            ->willReturnOnConsecutiveCalls('1', '0');

        $sut = $this->getTestSubject();
        $sut->execute($this->observer);
    }

    public function testExecuteWhenExtensionChangedToEnabled()
    {
        // TJ Extension is enabled and Backup Rates feature is disabled
        $this->mockScopeConfig
            ->expects($this->exactly(3))
            ->method('getValue')
            ->withConsecutive(
                ['tax/taxjar/enabled'],
                ['tax/taxjar/backup'],
                ['tax/taxjar/backup']
            )
            ->willReturnOnConsecutiveCalls('1', '0', '0');

        // TJ Extension was disabled and Backup Rates feature was disabled
        $this->mockCache
            ->expects($this->exactly(2))
            ->method('load')
            ->withConsecutive(
                ['taxjar_salestax_config_enabled'],
                ['taxjar_salestax_config_backup']
            )
            ->willReturnOnConsecutiveCalls('0', '0');

        // Expect to dispatch events
        $this->mockEventManager
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                ['taxjar_salestax_import_categories'],
                ['taxjar_salestax_import_data']
            );

        $sut = $this->getTestSubject();
        $sut->execute($this->observer);
    }

    public function testExecuteWhenBackupChangedToEnabled()
    {
        // TJ Extension is enabled and Backup Rates feature is enabled
        $this->mockScopeConfig
            ->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive(
                ['tax/taxjar/enabled'],
                ['tax/taxjar/backup']
            )
            ->willReturnOnConsecutiveCalls('1', '1');

        // TJ Extension was enabled and Backup Rates feature was disabled
        $this->mockCache
            ->expects($this->exactly(2))
            ->method('load')
            ->withConsecutive(
                ['taxjar_salestax_config_enabled'],
                ['taxjar_salestax_config_backup']
            )
            ->willReturnOnConsecutiveCalls('1', '0');

        // Expect to dispatch events
        $this->mockEventManager
            ->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                ['taxjar_salestax_import_categories'],
                ['taxjar_salestax_import_data'],
                ['taxjar_salestax_import_rates']
            );

        $sut = $this->getTestSubject();
        $sut->execute($this->observer);
    }

    public function testExecuteWhenProductTaxClassesChange()
    {
        // TJ Extension is enabled and Backup Rates feature is enabled - PTCs:1,2
        $this->mockScopeConfig
            ->expects($this->exactly(4))
            ->method('getValue')
            ->withConsecutive(
                ['tax/taxjar/enabled'],
                ['tax/taxjar/backup'],
                ['tax/taxjar/backup'],
                ['tax/taxjar/product_tax_classes']
            )
            ->willReturnOnConsecutiveCalls('1', '1', '1', '1,2');

        // TJ Extension was enabled and Backup Rates feature was enabled - PTCs:1
        $this->mockCache
            ->expects($this->exactly(3))
            ->method('load')
            ->withConsecutive(
                ['taxjar_salestax_config_enabled'],
                ['taxjar_salestax_config_backup'],
                ['taxjar_salestax_backup_rates_ptcs']
            )
            ->willReturnOnConsecutiveCalls('1', '1', '1');

        // Expect to dispatch events
        $this->mockEventManager
            ->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                ['taxjar_salestax_import_categories'],
                ['taxjar_salestax_import_data'],
                ['taxjar_salestax_import_rates']
            );

        $sut = $this->getTestSubject();
        $sut->execute($this->observer);
    }
    public function testExecuteWhenCustomerTaxClassesChange()
    {
        // TJ Extension is enabled and Backup Rates feature is enabled - PTCs:1,2 - CTCs:2
        $this->mockScopeConfig
            ->expects($this->exactly(5))
            ->method('getValue')
            ->withConsecutive(
                ['tax/taxjar/enabled'],
                ['tax/taxjar/backup'],
                ['tax/taxjar/backup'],
                ['tax/taxjar/product_tax_classes'],
                ['tax/taxjar/customer_tax_classes']
            )
            ->willReturnOnConsecutiveCalls('1', '1', '1', '1,2', '2');

        // TJ Extension was enabled and Backup Rates feature was enabled - PTCs:1,2 - CTCs:1
        $this->mockCache
            ->expects($this->exactly(4))
            ->method('load')
            ->withConsecutive(
                ['taxjar_salestax_config_enabled'],
                ['taxjar_salestax_config_backup'],
                ['taxjar_salestax_backup_rates_ptcs'],
                ['taxjar_salestax_backup_rates_ctcs']
            )
            ->willReturnOnConsecutiveCalls('1', '1', '1,2', '1');

        // Expect to dispatch events
        $this->mockEventManager
            ->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                ['taxjar_salestax_import_categories'],
                ['taxjar_salestax_import_data'],
                ['taxjar_salestax_import_rates']
            );

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
