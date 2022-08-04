<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Plugin\Quote\Model;

use Magento\Quote\Api\CartManagementInterface;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Taxjar\SalesTax\Api\Data\Sales\MetadataRepositoryInterface;
use Taxjar\SalesTax\Model\Sales\Order\Metadata;
use Taxjar\SalesTax\Model\Sales\Order\MetadataFactory;
use Taxjar\SalesTax\Plugin\Quote\Model\QuoteManagement;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class QuoteManagementTest extends UnitTestCase
{
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|MetadataFactory
     */
    private $metadataFactoryMock;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|MetadataRepositoryInterface
     */
    private $metadataRepositoryMock;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|Metadata
     */
    private $metadataMock;
    /**
     * @var QuoteManagement|mixed
     */
    private $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metadataFactoryMock = $this->getMockBuilder(MetadataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataRepositoryMock = $this->getMockBuilder(MetadataRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataMock = $this->getMockBuilder(Metadata::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getOrderId',
                'setOrderId',
                'setTaxCalculationStatus',
                'setTaxCalculationMessage'
            ])
            ->getMockForAbstractClass();
        $this->metadataFactoryMock->expects(static::any())
            ->method('create')
            ->willReturn($this->metadataMock);
    }

    public function testAfterSubmit()
    {
        $subjectMock = $this->getMockBuilder(CartManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadataMock->expects(static::exactly(2))
            ->method('setOrderId')
            ->with(999)
            ->willReturnSelf();
        $this->metadataMock->expects(static::once())
            ->method('setTaxCalculationStatus')
            ->with('error')
            ->willReturnSelf();
        $this->metadataMock->expects(static::once())
            ->method('setTaxCalculationMessage')
            ->with('Whoops!')
            ->willReturnSelf();
        $this->metadataMock->expects(static::once())
            ->method('getOrderId')
            ->willReturn(999);

        $extensionAttributesMock = $this->getMockBuilder(OrderExtensionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttributesMock->expects(static::exactly(2))
            ->method('getTjTaxCalculationStatus')
            ->willReturn('error');
        $extensionAttributesMock->expects(static::exactly(2))
            ->method('getTjTaxCalculationMessage')
            ->willReturn('Whoops!');

        $orderMock = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $orderMock->expects(static::once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributesMock);
        $orderMock->expects(static::any())
            ->method('getEntityId')
            ->willReturn(999);

        $this->metadataRepositoryMock->expects(static::once())
            ->method('save')
            ->with($this->metadataMock)
            ->willReturn(true);

        $this->setExpectations();

        static::assertSame($orderMock, $this->sut->afterSubmit($subjectMock, $orderMock));
    }

    public function testAfterSubmitHandlesNullOrder()
    {
        $subjectMock = $this->getMockBuilder(CartManagementInterface::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $orderMock = null;

        $this->setExpectations();

        static::assertNull($this->sut->afterSubmit($subjectMock, $orderMock));
    }

    protected function setExpectations()
    {
        $this->sut = new QuoteManagement(
            $this->metadataFactoryMock,
            $this->metadataRepositoryMock
        );
    }
}
