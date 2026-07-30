<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Model\Import;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\Calculation\Rule\Validator;
use Magento\Tax\Model\ClassModel;
use Taxjar\SalesTax\Model\Import\RuleModel;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

#[AllowMockObjectsWithoutExpectations]
class RuleModelTest extends UnitTestCase
{
    public function testAfterSaveDispatchesEvents()
    {
        $mockEventManager = $this->createMock(ManagerInterface::class);
        $mockEventManager->expects($this->exactly(4))
            ->method('dispatch')
            ->willReturnCallback(function ($eventName) {
                static $callCount = 0;
                $callCount++;

                $expectedEvents = [
                    'model_save_after',
                    'clean_cache_by_tags',
                    'tax_rule_save_after',
                    'tax_settings_change_after'
                ];

                $this->assertEquals($expectedEvents[$callCount - 1], $eventName);
            });

        $mockContext = $this->createMock(Context::class);
        $mockContext->expects($this->once())->method('getEventDispatcher')->willReturn($mockEventManager);

        $sut = $this->getMockBuilder(RuleModel::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                $mockContext,
                $this->createStub(Registry::class),
                $this->createStub(ExtensionAttributesFactory::class),
                $this->createStub(AttributeValueFactory::class),
                $this->createStub(ClassModel::class),
                $this->createStub(Calculation::class),
                $this->createStub(Validator::class),
                $this->createStub(AbstractResource::class),
                $this->createStub(AbstractDb::class)
            ])
            ->onlyMethods(['_init'])
            ->getMock();

        $sut->method('_init')->willReturn(true);

        $sut->afterSave();
    }
}
