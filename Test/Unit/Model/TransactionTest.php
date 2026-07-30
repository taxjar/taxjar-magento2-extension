<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Model;

use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\Transaction;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

#[AllowMockObjectsWithoutExpectations]
class TransactionTest extends UnitTestCase
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfig;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|\Taxjar\SalesTax\Model\ClientFactory
     */
    private $clientFactory;
    /**
     * @var \Magento\Catalog\Model\ProductRepository|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $productRepository;
    /**
     * @var \Magento\Directory\Model\RegionFactory|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $regionFactory;
    /**
     * @var \Magento\Tax\Api\TaxClassRepositoryInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $taxClassRepository;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|\Taxjar\SalesTax\Model\Logger
     */
    private $logger;
    /**
     * @var \Magento\Framework\ObjectManagerInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockObjectManager;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|\Taxjar\SalesTax\Helper\Data
     */
    private $helper;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|TaxjarConfig
     */
    private $taxjarConfig;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|\Taxjar\SalesTax\Model\Client
     */
    private $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scopeConfig = $this->createStub(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $this->clientFactory =  $this->createMock(\Taxjar\SalesTax\Model\ClientFactory::class);
        $this->productRepository =  $this->createStub(\Magento\Catalog\Model\ProductRepository::class);
        $this->regionFactory =  $this->createStub(\Magento\Directory\Model\RegionFactory::class);
        $this->taxClassRepository =  $this->createStub(\Magento\Tax\Api\TaxClassRepositoryInterface::class);
        $this->logger =  $this->createStub(\Taxjar\SalesTax\Model\Logger::class);
        $this->mockObjectManager =  $this->createStub(\Magento\Framework\ObjectManagerInterface::class);
        $this->helper =  $this->createStub(\Taxjar\SalesTax\Helper\Data::class);
        $this->taxjarConfig =  $this->createStub(\Taxjar\SalesTax\Model\Configuration::class);
        $this->client = $this->createStub(\Taxjar\SalesTax\Model\Client::class);
        $this->clientFactory->expects($this->once())->method('create')->willReturn($this->client);
    }

    public function testBuildLineItems()
    {
        $mockOrder = $this->createStub(\Magento\Sales\Model\Order::class);
        $mockItem = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getItemId',
                'getProductType',
                'getPrice',
                'getQtyInvoiced',
                'getTaxInvoiced',
                'getSku',
                'getName'
            ])
            ->getMock();
        $mockItem->setData('tj_ptc', '22222');
        $mockItem->expects($this->once())->method('getItemId')->willReturn(9);
        $mockItem->expects($this->once())->method('getPrice')->willReturn(60.0);
        $mockItem->expects($this->once())->method('getQtyInvoiced')->willReturn(2);
        $mockItem->expects($this->once())->method('getProductType')->willReturn('simple');
        $mockItem->expects($this->once())->method('getTaxInvoiced')->willReturn(5.0);
        $mockItem->expects($this->any())->method('getSku')->willReturn('some-sku');
        $mockItem->expects($this->any())->method('getName')->willReturn('A great product');

        $sut = $this->getTestSubject();
        $result = $this->callMethod($sut, 'buildLineItems', [$mockOrder, [$mockItem]]);

        self::assertSame([
            'line_items' => [
                [
                    'id' => 9,
                    'quantity' => 2,
                    'product_identifier' => 'some-sku',
                    'description' => 'A great product',
                    'unit_price' => 60.0,
                    'discount' => 0.0,
                    'sales_tax' => 5.0,
                    'product_tax_code' => '22222',
                ],
            ],
        ], $result);
    }

    protected function getTestSubject(): Transaction
    {
        return new Transaction(
            $this->scopeConfig,
            $this->clientFactory,
            $this->productRepository,
            $this->regionFactory,
            $this->taxClassRepository,
            $this->logger,
            $this->mockObjectManager,
            $this->helper,
            $this->taxjarConfig
        );
    }
}
