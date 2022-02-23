<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Helper;

use Taxjar\SalesTax\Helper\Nexus;

class NexusTest extends \Taxjar\SalesTax\Test\Unit\UnitTestCase
{
    /**
     * @var \Magento\Framework\App\Helper\Context|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contextMock;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|\Taxjar\SalesTax\Model\Tax\NexusFactory
     */
    private $nexusFactoryMock;
    /**
     * @var Nexus
     */
    private $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contextMock = $this->getMockBuilder(\Magento\Framework\App\Helper\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->nexusFactoryMock = $this->getMockBuilder(\Taxjar\SalesTax\Model\Tax\NexusFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetNexusAddresses()
    {
        $nexusInterfaceMock = $this->getMockBuilder(\Taxjar\SalesTax\Api\Data\Tax\NexusInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $nexusInterfaceMock->expects(static::any())->method('getId')->willReturn('99');
        $nexusInterfaceMock->expects(static::any())->method('getCountryId')->willReturn('US');
        $nexusInterfaceMock->expects(static::any())->method('getPostcode')->willReturn('94080');
        $nexusInterfaceMock->expects(static::any())->method('getRegionCode')->willReturn('CA');
        $nexusInterfaceMock->expects(static::any())->method('getCity')->willReturn('San Francisco');
        $nexusInterfaceMock->expects(static::any())->method('getStreet')->willReturn('354 Oyster Point Blvd.');

        $nexusCollectionMock = $this->getMockBuilder(\Taxjar\SalesTax\Model\ResourceModel\Tax\Nexus\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nexusCollectionMock->expects(static::once())
            ->method('getItems')
            ->willReturn(['99' => $nexusInterfaceMock]);

        $nexusMock = $this->getMockBuilder(\Taxjar\SalesTax\Model\Tax\Nexus::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nexusMock->expects(static::once())
            ->method('getCollection')
            ->willReturn($nexusCollectionMock);

        $this->nexusFactoryMock->expects(static::any())
            ->method('create')
            ->willReturn($nexusMock);

        $this->setExpectations();

        static::assertSame(
            [
                0 => [
                    'id' => '99',
                    'country' => 'US',
                    'zip' => '94080',
                    'state' => 'CA',
                    'city' => 'San Francisco',
                    'street' => '354 Oyster Point Blvd.'
                ]
            ],
            $this->sut->getNexusAddresses(null)
        );
    }

    public function testGetNexusCollection()
    {
        $nexusCollectionMock = $this->getMockBuilder(\Taxjar\SalesTax\Model\ResourceModel\Tax\Nexus\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nexusCollectionMock->expects(static::once())
            ->method('addStoreFilter')
            ->with(1)
            ->willReturnSelf();

        $nexusMock = $this->getMockBuilder(\Taxjar\SalesTax\Model\Tax\Nexus::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nexusMock->expects(static::once())
            ->method('getCollection')
            ->willReturn($nexusCollectionMock);

        $this->nexusFactoryMock->expects(static::any())
            ->method('create')
            ->willReturn($nexusMock);

        $this->setExpectations();

        static::assertSame($nexusCollectionMock, $this->sut->getNexusCollection(1));
    }

    public function testGetNexusData()
    {
        $nexusMock = $this->getMockBuilder(\Taxjar\SalesTax\Api\Data\Tax\NexusInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $nexusMock->expects(static::any())->method('getId')->willReturn(1);
        $nexusMock->expects(static::any())->method('getCountryId')->willReturn('US');
        $nexusMock->expects(static::any())->method('getPostcode')->willReturn('94080');
        $nexusMock->expects(static::any())->method('getRegionCode')->willReturn('CA');
        $nexusMock->expects(static::any())->method('getCity')->willReturn('San Francisco');
        $nexusMock->expects(static::any())->method('getStreet')->willReturn('354 Oyster Point Blvd.');

        $this->setExpectations();

        $actual = $this->sut->getNexusData($nexusMock);

        static::assertSame([
            'id' => 1,
            'country' => 'US',
            'zip' => '94080',
            'state' => 'CA',
            'city' => 'San Francisco',
            'street' => '354 Oyster Point Blvd.'
        ], $actual);
    }

    public function testHasNexusByLocationUsaWithoutRegionCode()
    {
        $this->setExpectations();

        $result = $this->sut->hasNexusByLocation(1, null, 'US');

        static::assertFalse($result);
    }

    public function testHasNexusByLocationUsaWithRegionCode()
    {
        $nexusCollectionMock = $this->getMockBuilder(\Taxjar\SalesTax\Model\ResourceModel\Tax\Nexus\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nexusCollectionMock->expects(static::once())
            ->method('addStoreFilter')
            ->with(1)
            ->willReturnSelf();
        $nexusCollectionMock->expects(static::once())
            ->method('addRegionCodeFilter')
            ->with('CA')
            ->willReturnSelf();
        $nexusCollectionMock->expects(static::never())
            ->method('addCountryFilter');
        $nexusCollectionMock->expects(static::once())
            ->method('getSize')
            ->willReturn(1);

        $nexusMock = $this->getMockBuilder(\Taxjar\SalesTax\Model\Tax\Nexus::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nexusMock->expects(static::once())
            ->method('getCollection')
            ->willReturn($nexusCollectionMock);

        $this->nexusFactoryMock->expects(static::any())
            ->method('create')
            ->willReturn($nexusMock);

        $this->setExpectations();

        static::assertTrue($this->sut->hasNexusByLocation(1, 'CA', 'US'));
    }

    public function testHasNexusByLocationNonUsa()
    {
        $nexusCollectionMock = $this->getMockBuilder(\Taxjar\SalesTax\Model\ResourceModel\Tax\Nexus\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nexusCollectionMock->expects(static::once())
            ->method('addStoreFilter')
            ->with(1)
            ->willReturnSelf();
        $nexusCollectionMock->expects(static::never())
            ->method('addRegionCodeFilter');
        $nexusCollectionMock->expects(static::once())
            ->method('addCountryFilter')
            ->with('GB')
            ->willReturnSelf();
        $nexusCollectionMock->expects(static::once())
            ->method('getSize')
            ->willReturn(0);

        $nexusMock = $this->getMockBuilder(\Taxjar\SalesTax\Model\Tax\Nexus::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nexusMock->expects(static::once())
            ->method('getCollection')
            ->willReturn($nexusCollectionMock);

        $this->nexusFactoryMock->expects(static::any())
            ->method('create')
            ->willReturn($nexusMock);

        $this->setExpectations();

        static::assertFalse($this->sut->hasNexusByLocation(1, null, 'GB'));
    }

    protected function setExpectations()
    {
        $this->sut = new Nexus(
            $this->contextMock,
            $this->nexusFactoryMock
        );
    }
}
