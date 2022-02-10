<?php

namespace Taxjar\SalesTax\Test\Unit\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Taxjar\SalesTax\Helper\OrderMetadata;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata\Collection as MetadataResourceCollection;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata\CollectionFactory;
use Taxjar\SalesTax\Model\Sales\Order\Metadata;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class OrderMetadataTest extends UnitTestCase
{
    /**sma
     * @var OrderExtensionFactory|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $extensionFactoryMock;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|CollectionFactory
     */
    private $collectionFactoryMock;
    /**
     * @var Context|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contextMock;
    /**
     * @var OrderMetadata|mixed
     */
    private $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extensionFactoryMock = $this->getMockBuilder(OrderExtensionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetOrderMetadata()
    {
        $orderMock = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects(static::once())->method('getEntityId')->willReturn(789);

        $metadataMock = $this->getMockBuilder(Metadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $collection = $this->getMockBuilder(MetadataResourceCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection->expects(static::once())->method('addFieldToFilter')
            ->with('order_id', ['eq' => 789])
            ->willReturnSelf();
        $collection->expects(static::once())->method('setPageSize')->with(1)->willReturnSelf();
        $collection->expects(static::once())->method('setCurPage')->with(1)->willReturnSelf();
        $collection->expects(static::once())->method('getItems')->willReturn([$metadataMock]);

        $this->collectionFactoryMock->expects(static::once())
            ->method('create')
            ->willReturn($collection);

        $this->setExpectations();

        static::assertSame($metadataMock, $this->sut->getOrderMetadata($orderMock));
    }

    public function testSetOrderExtensionAttributeData()
    {
        $orderMock = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects(static::once())->method('getEntityId')->willReturn(789);
        $orderMock->expects(static::once())->method('getExtensionAttributes')->willReturn(null);
        $orderMock->expects(static::once())->method('setExtensionAttributes')->willReturnSelf();

        $extensionAttributeMock = $this->getMockBuilder(OrderExtensionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttributeMock->expects(static::once())
            ->method('setTjTaxCalculationStatus')
            ->with('error')
            ->willReturnSelf();
        $extensionAttributeMock->expects(static::once())
            ->method('setTjTaxCalculationMessage')
            ->with('Oh captain my captain')
            ->willReturnSelf();

        $this->extensionFactoryMock->expects(static::once())
            ->method('create')
            ->willReturn($extensionAttributeMock);

        $metadataMock = $this->getMockBuilder(Metadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadataMock->expects(static::once())
            ->method('getTaxCalculationStatus')
            ->willReturn('error');
        $metadataMock->expects(static::once())
            ->method('getTaxCalculationMessage')
            ->willReturn('Oh captain my captain');

        $collection = $this->getMockBuilder(MetadataResourceCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection->expects(static::once())->method('addFieldToFilter')
            ->with('order_id', ['eq' => 789])
            ->willReturnSelf();
        $collection->expects(static::once())->method('setPageSize')->with(1)->willReturnSelf();
        $collection->expects(static::once())->method('setCurPage')->with(1)->willReturnSelf();
        $collection->expects(static::once())->method('getItems')->willReturn([$metadataMock]);

        $this->collectionFactoryMock->expects(static::once())
            ->method('create')
            ->willReturn($collection);

        $this->setExpectations();

        static::assertSame($orderMock, $this->sut->setOrderExtensionAttributeData($orderMock));
    }

    protected function setExpectations(): void
    {
        $this->sut = new OrderMetadata(
            $this->extensionFactoryMock,
            $this->collectionFactoryMock,
            $this->contextMock
        );
    }
}
