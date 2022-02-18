<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Observer;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterfaceFactory;
use Taxjar\SalesTax\Observer\SaveOrderMetadata;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class SaveOrderMetadataTest extends UnitTestCase
{
    /**
     * @var CheckoutSession|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $checkoutSessionMock;
    /**
     * @var OrderRepositoryInterfaceFactory|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderRepositoryFactoryMock;
    /**
     * @var OrderExtensionFactory|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $extensionFactoryMock;
    /**
     * @var OrderRepositoryInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var SaveOrderMetadata|mixed
     */
    private $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->checkoutSessionMock = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderRepositoryFactoryMock = $this->getMockBuilder(OrderRepositoryInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->extensionFactoryMock = $this->getMockBuilder(OrderExtensionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderRepositoryMock = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->orderRepositoryFactoryMock->expects(static::any())
            ->method('create')
            ->willReturn($this->orderRepositoryMock);
    }

    public function testExecuteSuccess()
    {
        $orderMock = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $orderMock->expects(static::any())
            ->method('getExtensionAttributes')
            ->willReturn(null);
        $orderMock->expects(static::once())
            ->method('setExtensionAttributes');

        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder'])
            ->getMock();
        $observerMock->expects(static::any())
            ->method('getOrder')
            ->willReturn($orderMock);

        $orderExtensionInterfaceMock = $this->getMockBuilder(OrderExtensionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $orderExtensionInterfaceMock->expects(static::once())
            ->method('setTjTaxCalculationStatus')
            ->with('success')
            ->willReturnSelf();

        $this->extensionFactoryMock->expects(static::once())
            ->method('create')
            ->willReturn($orderExtensionInterfaceMock);

        $metadataMock = ['tax_calculation_status' => 'success'];

        $this->checkoutSessionMock->expects(static::once())
            ->method('getData')
            ->with('taxjar_salestax_order_metadata')
            ->willReturn(json_encode($metadataMock));

        $this->orderRepositoryMock->expects(static::once())
            ->method('save')
            ->with($orderMock);

        $this->setExpectations();

        $this->sut->execute($observerMock);
    }

    public function testExecuteError()
    {
        $orderMock = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $orderMock->expects(static::any())
            ->method('getExtensionAttributes')
            ->willReturn(null);
        $orderMock->expects(static::once())
            ->method('setExtensionAttributes');

        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder'])
            ->getMock();
        $observerMock->expects(static::any())
            ->method('getOrder')
            ->willReturn($orderMock);

        $orderExtensionInterfaceMock = $this->getMockBuilder(OrderExtensionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $orderExtensionInterfaceMock->expects(static::once())
            ->method('setTjTaxCalculationStatus')
            ->with('error')
            ->willReturnSelf();
        $orderExtensionInterfaceMock->expects(static::once())
            ->method('setTjTaxCalculationMessage')
            ->with('Idk man, stuff breaks..')
            ->willReturnSelf();

        $this->extensionFactoryMock->expects(static::once())
            ->method('create')
            ->willReturn($orderExtensionInterfaceMock);

        $metadataMock = [
            'tax_calculation_status' => 'error',
            'tax_calculation_message' => 'Idk man, stuff breaks..',
        ];

        $this->checkoutSessionMock->expects(static::once())
            ->method('getData')
            ->with('taxjar_salestax_order_metadata')
            ->willReturn(json_encode($metadataMock));

        $this->orderRepositoryMock->expects(static::once())
            ->method('save')
            ->with($orderMock);

        $this->setExpectations();

        $this->sut->execute($observerMock);
    }

    protected function setExpectations()
    {
        $this->sut = new SaveOrderMetadata(
            $this->checkoutSessionMock,
            $this->orderRepositoryFactoryMock,
            $this->extensionFactoryMock
        );
    }
}
