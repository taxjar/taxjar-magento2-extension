<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Plugin\Sales\Block\Adminhtml\Order;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;

#[AllowMockObjectsWithoutExpectations]
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
     * @dataProvider beforeSetLayoutMethodDataProvider
     * @param bool $transactionSyncEnabled
     * @param bool $orderIsSyncable
     */
    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
    #[DataProvider('beforeSetLayoutMethodDataProvider')]
    public function testBeforeSetLayoutMethod(bool $transactionSyncEnabled, bool $orderIsSyncable)
    {
        $orderMock = $this->createStub(\Magento\Sales\Model\Order::class);

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

    public static function beforeSetLayoutMethodDataProvider(): array
    {
        return [
            'feature_not_enabled_order_not_syncable' => [false, false],
            'feature_enabled_order_not_syncable' => [true, false],
            'feature_not_enabled_order_syncable' => [false, true],
            'feature_enabled_order_syncable' => [true, true],
        ];
    }

    protected function setExpectations()
    {
        $this->sut = new \Taxjar\SalesTax\Plugin\Sales\Block\Adminhtml\Order\View(
            $this->tjSalesTaxDataMock
        );
    }
}
