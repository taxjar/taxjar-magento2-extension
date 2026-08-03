<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Plugin\Sales\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Taxjar\SalesTax\Plugin\Sales\Model\OrderRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

#[AllowMockObjectsWithoutExpectations]
class OrderRepositoryTest extends UnitTestCase
{
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|\Taxjar\SalesTax\Helper\OrderMetadata
     */
    private $orderMetadataHelperMock;
    /**
     * @var OrderRepository|mixed
     */
    private $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderMetadataHelperMock = $this->getMockBuilder(\Taxjar\SalesTax\Helper\OrderMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testAfterGet()
    {
        $subjectMock = $this->createStub(OrderRepositoryInterface::class);

        $orderMock = $this->createStub(OrderInterface::class);

        $this->orderMetadataHelperMock->expects(static::once())
            ->method('setOrderExtensionAttributeData')
            ->with($orderMock)
            ->willReturn($orderMock);

        $this->setExpectations();

        static::assertSame($orderMock, $this->sut->afterGet($subjectMock, $orderMock));
    }

    public function testAfterGetList()
    {
        $orderMock = $this->createStub(OrderInterface::class);

        $subjectMock = $this->createStub(OrderRepositoryInterface::class);

        $searchResultMock = $this->createMock(OrderSearchResultInterface::class);
        $searchResultMock->expects(static::once())
            ->method('getItems')
            ->willReturn([$orderMock]);

        $this->orderMetadataHelperMock->expects(static::once())
            ->method('setOrderExtensionAttributeData')
            ->with($orderMock)
            ->willReturn($orderMock);

        $this->setExpectations();

        static::assertSame($searchResultMock, $this->sut->afterGetList($subjectMock, $searchResultMock));
    }

    protected function setExpectations()
    {
        $this->sut = new OrderRepository(
            $this->orderMetadataHelperMock
        );
    }
}
