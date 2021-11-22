<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Plugin\Sales\Block\Adminhtml\Order;

class ViewTest extends \Taxjar\SalesTax\Test\Unit\UnitTestCase
{
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|\Taxjar\SalesTax\Helper\Data
     */
    protected $tjSalesTaxDataMock;
    /**
     * @var \Taxjar\SalesTax\Plugin\Sales\Block\Adminhtml\Order\View
     */
    protected $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tjSalesTaxDataMock = $this->getMockBuilder(\Taxjar\SalesTax\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->setExpectations();
    }

    public function testBeforeSetLayoutMethodWithSyncableOrder()
    {
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $viewMock = $this->getMockBuilder(\Magento\Sales\Block\Adminhtml\Order\View::class)
            ->disableOriginalConstructor()
            ->getMock();
        $viewMock->expects(static::once())
            ->method('getOrder')
            ->willReturn($orderMock);
        $viewMock->expects(static::once())
            ->method('addButton')
            ->with('taxjar_sync', [
                'label' => __('Sync to TaxJar'),
                'class' => 'taxjar-sync primary',
                'onclick' => 'syncTransaction(\'9\')'
            ])
            ->willReturn($orderMock);
        $viewMock->expects(static::once())
            ->method('getOrderId')
            ->willReturn(9);
        $this->tjSalesTaxDataMock->expects(static::once())
            ->method('isSyncableOrder')
            ->with($orderMock)
            ->willReturn(true);

        $this->setExpectations();

        $this->sut->beforeSetLayout($viewMock);
    }

    public function testBeforeSetLayoutMethodWithNonSyncableOrder()
    {
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $viewMock = $this->getMockBuilder(\Magento\Sales\Block\Adminhtml\Order\View::class)
            ->disableOriginalConstructor()
            ->getMock();
        $viewMock->expects(static::once())
            ->method('getOrder')
            ->willReturn($orderMock);
        $viewMock->expects(static::never())
            ->method('addButton');
        $viewMock->expects(static::never())
            ->method('getOrderId');
        $this->tjSalesTaxDataMock->expects(static::once())
            ->method('isSyncableOrder')
            ->with($orderMock)
            ->willReturn(false);

        $this->setExpectations();

        $this->sut->beforeSetLayout($viewMock);
    }

    protected function setExpectations()
    {
        $this->sut = new \Taxjar\SalesTax\Plugin\Sales\Block\Adminhtml\Order\View(
            $this->tjSalesTaxDataMock
        );
    }
}
