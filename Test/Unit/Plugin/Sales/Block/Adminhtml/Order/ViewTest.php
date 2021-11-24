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

    /**
     * @param bool $transactionSyncEnabled
     * @param bool $orderIsSyncable
     * @dataProvider beforeSetLayoutMethodDataProvider
     */
    public function testBeforeSetLayoutMethod(bool $transactionSyncEnabled, bool $orderIsSyncable)
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

        $rule = ($transactionSyncEnabled && $orderIsSyncable) ? static::atLeastOnce() : static::never();
        $viewMock->expects($rule)->method('addButton');
        $viewMock->expects($rule)->method('getOrderId');

        $this->tjSalesTaxDataMock->expects(static::once())
            ->method('isTransactionSyncEnabled')
            ->willReturn($transactionSyncEnabled);
        $this->tjSalesTaxDataMock->expects($transactionSyncEnabled ? static::once() : static::never())
            ->method('isSyncableOrder')
            ->with($orderMock)
            ->willReturn($orderIsSyncable);

        $this->setExpectations();

        $this->sut->beforeSetLayout($viewMock);
    }

    public function beforeSetLayoutMethodDataProvider(): array
    {
        return [
            'feature_not_enabled_order_not_syncable' => [
                'is_transaction_sync_enabled' => false,
                'is_syncable_order' => false,
            ],
            'feature_enabled_order_not_syncable' => [
                'is_transaction_sync_enabled' => true,
                'is_syncable_order' => false,
            ],
            'feature_not_enabled_order_syncable' => [
                'is_transaction_sync_enabled' => false,
                'is_syncable_order' => true,
            ],
            'feature_enabled_order_syncable' => [
                'is_transaction_sync_enabled' => true,
                'is_syncable_order' => true,
            ],
        ];
    }

    protected function setExpectations()
    {
        $this->sut = new \Taxjar\SalesTax\Plugin\Sales\Block\Adminhtml\Order\View(
            $this->tjSalesTaxDataMock
        );
    }
}
