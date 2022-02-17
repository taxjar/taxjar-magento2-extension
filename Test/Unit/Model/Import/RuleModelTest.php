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
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class RuleModelTest extends UnitTestCase
{
    public function testAfterSaveDispatchesEvents()
    {
        $mockEventManager = $this->createMock(ManagerInterface::class);
        $mockEventManager->expects($this->exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                ['model_save_after'],
                ['clean_cache_by_tags'],
                ['tax_rule_save_after'],
                ['tax_settings_change_after']
            );

        $mockContext = $this->createMock(Context::class);
        $mockContext->expects($this->once())->method('getEventDispatcher')->willReturn($mockEventManager);

        $sut = $this->getMockBuilder(RuleModel::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                $mockContext,
                $this->createMock(Registry::class),
                $this->createMock(ExtensionAttributesFactory::class),
                $this->createMock(AttributeValueFactory::class),
                $this->createMock(ClassModel::class),
                $this->createMock(Calculation::class),
                $this->createMock(Validator::class),
                $this->createMock(AbstractResource::class),
                $this->createMock(AbstractDb::class)
            ])
            ->setMethods(['_init'])
            ->getMock();

        $sut->method('_init')->will($this->returnValue(true));

        $sut->afterSave();
    }
}
