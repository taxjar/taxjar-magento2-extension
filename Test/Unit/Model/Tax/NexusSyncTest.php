<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Model\Tax;

use Magento\Directory\Model\Country;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Taxjar\SalesTax\Model\Client;
use Taxjar\SalesTax\Model\ClientFactory;
use Taxjar\SalesTax\Model\ResourceModel\Tax\Nexus as NexusResource;
use Taxjar\SalesTax\Model\ResourceModel\Tax\Nexus\Collection;
use Taxjar\SalesTax\Model\Tax\Nexus;
use Taxjar\SalesTax\Model\Tax\NexusFactory;
use Taxjar\SalesTax\Model\Tax\NexusSync;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class NexusSyncTest extends UnitTestCase
{
    /**
     * @var \Magento\Framework\Api\ExtensionAttributesFactory|\Magento\Framework\Model\Context|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contextMock;
    /**
     * @var AttributeValueFactory|\Magento\Framework\Registry|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registryMock;
    /**
     * @var \Magento\Framework\Api\ExtensionAttributesFactory|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $extensionFactoryMock;
    /**
     * @var AttributeValueFactory|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customAttributeFactoryMock;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|ClientFactory
     */
    private $clientFactoryMock;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|NexusFactory
     */
    private $nexusFactoryMock;
    /**
     * @var RegionFactory|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $regionFactoryMock;
    /**
     * @var CountryFactory|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $countryFactoryMock;
    /**
     * @var ScopeConfigInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigMock;
    /**
     * @var NexusSync $sut
     */
    private $sut;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|NexusResource
     */
    private $nexusResourceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contextMock = $this->getMockBuilder(\Magento\Framework\Model\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registryMock = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->extensionFactoryMock = $this->getMockBuilder(\Magento\Framework\Api\ExtensionAttributesFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customAttributeFactoryMock = $this->getMockBuilder(AttributeValueFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->clientFactoryMock = $this->getMockBuilder(ClientFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->nexusFactoryMock = $this->getMockBuilder(NexusFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->regionFactoryMock = $this->getMockBuilder(RegionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->countryFactoryMock = $this->getMockBuilder(CountryFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->nexusResourceMock = $this->getMockBuilder(NexusResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->setExpectations();
    }

    public function testSyncNewNexusAddress()
    {
        $dataMock = [
            'street' => '123 Test Dr.',
            'city' => 'South San Francisco',
            'state' => 'CA',
            'zip' => '94080',
            'country' => 'US'
        ];

        $clientMock = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->getMock();
        $clientMock->expects(static::once())
            ->method('postResource')
            ->with('nexus', $dataMock, [
                '400' => __(
                    'Your nexus address contains invalid data. ' .
                    'Please verify the address in order to sync with TaxJar.'
                ),
                '409' => __(
                    'A nexus address already exists for this state/region. ' .
                    'TaxJar currently supports one address per region.'
                ),
                '422' => __(
                    'Your nexus address is missing one or more required fields. ' .
                    'Please verify the address in order to sync with TaxJar.'
                ),
                '500' => __(
                    'Something went wrong while syncing your address with TaxJar. ' .
                    'Please verify the address and contact support@taxjar.com if the problem persists.'
                )
            ])
            ->willReturn(['id' => 101]);

        $this->clientFactoryMock->expects(static::once())->method('create')->willReturn($clientMock);

        $this->setExpectations();

        $this->sut->addData([
            'api_id' => 'test_id',
            'street' => '123 Test Dr.',
            'city' => 'South San Francisco',
            'region_code' => 'CA',
            'postcode' => '94080',
            'country_id' => 'US'
        ]);

        $this->sut->sync();
    }

    public function testSyncExistingNexusAddress()
    {
        $dataMock = [
            'street' => '123 Test Dr.',
            'city' => 'South San Francisco',
            'state' => 'CA',
            'zip' => '94080',
            'country' => 'US'
        ];

        $clientMock = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->getMock();
        $clientMock->expects(static::once())
            ->method('putResource')
            ->with('nexus', 'test_id', $dataMock, [
                '400' => __(
                    'Your nexus address contains invalid data. ' .
                    'Please verify the address in order to sync with TaxJar.'
                ),
                '409' => __(
                    'A nexus address already exists for this state/region. ' .
                    'TaxJar currently supports one address per region.'
                ),
                '422' => __(
                    'Your nexus address is missing one or more required fields. ' .
                    'Please verify the address in order to sync with TaxJar.'
                ),
                '500' => __(
                    'Something went wrong while syncing your address with TaxJar. ' .
                    'Please verify the address and contact support@taxjar.com if the problem persists.'
                )
            ]);

        $this->clientFactoryMock->expects(static::once())->method('create')->willReturn($clientMock);

        $this->setExpectations();

        $this->sut->addData([
            'id' => '42',
            'api_id' => 'test_id',
            'street' => '123 Test Dr.',
            'city' => 'South San Francisco',
            'region_code' => 'CA',
            'postcode' => '94080',
            'country_id' => 'US'
        ]);

        $this->sut->sync();
    }

    public function testSyncDelete()
    {
        $clientMock = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->getMock();
        $clientMock->expects(static::once())
            ->method('deleteResource')
            ->with('nexus', 'test_id', [
                '409' => __('A nexus address with this ID could not be found in TaxJar.'),
                '500' => __(
                    'Something went wrong while deleting your address in TaxJar. ' .
                    'Please contact support@taxjar.com if the problem persists.'
                )
            ])
            ->willReturn(null);

        $this->clientFactoryMock->expects(static::once())->method('create')->willReturn($clientMock);

        $this->setExpectations();

        // Faking existence of entity
        $this->sut->addData([
            'id' => '42',
            'api_id' => 'test_id',
        ]);

        $this->sut->syncDelete();
    }

    public function testSyncCollection()
    {
        $clientMock = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->getMock();
        $clientMock->expects(static::once())
            ->method('getResource')
            ->with('nexus')
            ->willReturn([
                'addresses' => [
                    [
                        'id' => 1,
                        'country' => 'US',
                        'state' => 'TX',
                        'street' => '',
                        'city' => '',
                        'zip' => ''
                    ],
                    [
                        'id' => 2,
                        'country' => 'UK',
                        'state' => '',
                        'street' => '',
                        'city' => '',
                        'zip' => ''
                    ],
                ],
            ]);

        $this->clientFactoryMock->expects(static::once())->method('create')->willReturn($clientMock);

        $regionMock = $this->getMockBuilder(Region::class)->disableOriginalConstructor()->getMock();
        $regionMock->expects(static::exactly(2))
            ->method('loadByCode')
            ->withConsecutive(['TX', 'US'], ['', 'GB'])
            ->willReturnSelf();
        $regionMock->expects(static::atLeast(2))->method('getId')->willReturn(99);
        $this->regionFactoryMock->expects(static::atLeast(2))->method('create')->willReturn($regionMock);

        $countryMock = $this->getMockBuilder(Country::class)->disableOriginalConstructor()->getMock();
        $countryMock->expects(static::exactly(2))
            ->method('loadByCode')
            ->withConsecutive(['US'], ['GB'])
            ->willReturnSelf();
        $countryMock->expects(static::atLeast(2))->method('getId')->willReturn(77);
        $this->countryFactoryMock->expects(static::atLeast(2))->method('create')->willReturn($countryMock);
        $this->nexusResourceMock->expects(static::any())->method('getIdFieldName')->willReturn('id');

        $nexusResult = $this->getMockBuilder(Nexus::class)->disableOriginalConstructor()->getMock();
        $nexusResult->expects(static::any())->method('getIdFieldName')->willReturn('id');
        $nexusResult->expects(static::any())->method('getId')->willReturn(55);
        $nexusResult->expects(static::any())->method('setData')->willReturnSelf();
        $nexusResult->expects(static::any())->method('save')->willReturnSelf();

        $nexusCollectionMock = $this->getMockBuilder(Collection::class)->disableOriginalConstructor()->getMock();
        $nexusCollectionMock->expects(static::once())->method('addFieldToFilter')->willReturnSelf();
        $nexusCollectionMock->expects(static::once())->method('each')->willReturnSelf();
        $nexusCollectionMock->expects(static::any())->method('getFirstItem')->willReturn($nexusResult);

        $nexusMock = $this->getMockBuilder(Nexus::class)->disableOriginalConstructor()->getMock();
        $nexusMock->expects(static::any())->method('getIdFieldName')->willReturn('id');
        $nexusMock->expects(static::exactly(1))->method('getCollection')->willReturn($nexusCollectionMock);

        $this->nexusFactoryMock->expects(static::exactly(3))->method('create')->willReturn($nexusMock);

        $this->setExpectations();
        $this->sut->syncCollection();
    }

    protected function setExpectations()
    {
        $this->sut = new NexusSync(
            $this->contextMock,
            $this->registryMock,
            $this->extensionFactoryMock,
            $this->customAttributeFactoryMock,
            $this->clientFactoryMock,
            $this->nexusFactoryMock,
            $this->regionFactoryMock,
            $this->countryFactoryMock,
            $this->scopeConfigMock,
            $this->nexusResourceMock,
            null,
            []
        );
    }
}
