<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Taxjar\SalesTax\Model\Configuration;

class DataTest extends \Taxjar\SalesTax\Test\Unit\UnitTestCase
{
    /**
     * @var \Magento\Framework\App\Helper\Context|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextMock;
    /**
     * @var \Magento\Framework\App\Request\Http|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $requestMock;
    /**
     * @var \Magento\Framework\App\ProductMetadataInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $productMetadataMock;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $storeManagerMock;
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $priceCurrencyMock;
    /**
     * @var \Taxjar\SalesTax\Helper\Data
     */
    protected $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contextMock = $this->getMockBuilder(\Magento\Framework\App\Helper\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\Request\Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productMetadataMock = $this->getMockBuilder(\Magento\Framework\App\ProductMetadataInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManagerMock = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->priceCurrencyMock = $this->getMockBuilder(\Magento\Framework\Pricing\PriceCurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->setExpectations();
    }

    public function testIsEnabled()
    {
        $scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $scopeConfigMock->expects(static::atLeastOnce())
            ->method('getValue')
            ->with(Configuration::TAXJAR_ENABLED, 'store', null)
            ->willReturn(true);

        $this->contextMock->expects(static::atLeastOnce())
            ->method('getScopeConfig')
            ->willReturn($scopeConfigMock);

        $this->setExpectations();

        static::assertTrue($this->sut->isEnabled());
    }

    /**
     * @param $isVirtual
     * @param $expectedMethod
     * @param $notExpected
     * @dataProvider orderAddressMethodDataProvider
     */
    public function testGetOrderAddressMethod($isVirtual, $expectedMethod, $notExpected)
    {
        $addressMock = $this->getMockBuilder(\Magento\Sales\Api\Data\OrderAddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects(static::once())
            ->method('getIsVirtual')
            ->willReturn($isVirtual);
        $orderMock->expects(static::once())
            ->method($expectedMethod)
            ->willReturn($addressMock);
        $orderMock->expects(static::never())
            ->method($notExpected);

        $this->setExpectations();
        $this->sut->getOrderAddress($orderMock);
    }

    public function orderAddressMethodDataProvider(): array
    {
        return [
            'virtual_order' => [
                'is_virtual' => true,
                'expect' => 'getBillingAddress',
                'not' => 'getShippingAddress',
            ],
            'non_virtual_order' => [
                'is_virtual' => false,
                'expect' => 'getShippingAddress',
                'not' => 'getBillingAddress',
            ],
        ];
    }

    public function testGetOrderValidationMethod()
    {
        $addressMock = $this->getMockBuilder(\Magento\Sales\Api\Data\OrderAddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $addressMock->expects(static::once())
            ->method('getCountryId')
            ->willReturn('invalid');
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects(static::once())
            ->method('getState')
            ->willReturn('invalid-status');
        $orderMock->expects(static::once())
            ->method('getOrderCurrencyCode')
            ->willReturn('invalid-currency');
        $orderMock->expects(static::once())
            ->method('getIsVirtual')
            ->willReturn(false);
        $orderMock->expects(static::once())
            ->method('getShippingAddress')
            ->willReturn($addressMock);

        $this->setExpectations();

        $validation = $this->sut->getOrderValidation($orderMock);

        static::assertCount(3, $validation);
        static::assertArrayHasKey('state', $validation);
        static::assertFalse($validation['state']);
        static::assertArrayHasKey('order_currency_code', $validation);
        static::assertFalse($validation['order_currency_code']);
        static::assertArrayHasKey('country', $validation);
        static::assertFalse($validation['country']);
    }

    /**
     * @param $state
     * @param $expect
     * @dataProvider isSyncableOrderStateDataProvider
     */
    public function testIsSyncableOrderStateMethod($state, $expect)
    {
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects(static::once())
            ->method('getState')
            ->willReturn($state);

        static::assertEquals($expect, $this->sut->isSyncableOrderState($orderMock));
    }

    public function isSyncableOrderStateDataProvider(): array
    {
        return [
            ['canceled', false],
            ['closed', true],
            ['complete', true],
            ['fraud', false],
            ['holded', false],
            ['payment_review', false],
            ['paypal_canceled_reversal', false],
            ['paypal_reversed', false],
            ['pending', false],
            ['pending_payment', false],
            ['pending_paypal', false],
            ['processing', false],
        ];
    }

    /**
     * @param $currencyCode
     * @param $expect
     * @dataProvider isSyncableOrderCurrencyDataProvider
     */
    public function testIsSyncableOrderCurrencyMethod($currencyCode, $expect)
    {
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects(static::once())
            ->method('getOrderCurrencyCode')
            ->willReturn($currencyCode);

        static::assertEquals($expect, $this->sut->isSyncableOrderCurrency($orderMock));
    }

    public function isSyncableOrderCurrencyDataProvider(): array
    {
        return [
            'syncable_currency' => ['USD', true],
            'non_syncable_currency' => ['ZIM', false]
        ];
    }

    /**
     * @param $countryId
     * @param $expect
     * @dataProvider isSyncableOrderCountryDataProvider
     */
    public function testIsSyncableOrderCountryMethod($countryId, $expect)
    {
        $addressMock = $this->getMockBuilder(\Magento\Sales\Api\Data\OrderAddressInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $addressMock->expects(static::once())
            ->method('getCountryId')
            ->willReturn($countryId);

        static::assertEquals($expect, $this->sut->isSyncableOrderCountry($addressMock));
    }

    public function isSyncableOrderCountryDataProvider(): array
    {
        return [
            'syncable_country' => ['US', true],
            'non_syncable_country' => ['YZ', false]
        ];
    }

    protected function setExpectations()
    {
        $this->sut = new \Taxjar\SalesTax\Helper\Data(
            $this->contextMock,
            $this->requestMock,
            $this->productMetadataMock,
            $this->storeManagerMock,
            $this->priceCurrencyMock
        );
    }
}
