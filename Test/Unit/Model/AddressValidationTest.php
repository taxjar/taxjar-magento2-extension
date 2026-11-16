<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Model;

use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Taxjar\SalesTax\Model\AddressValidation;
use Taxjar\SalesTax\Model\Client;
use Taxjar\SalesTax\Model\ClientFactory;
use Taxjar\SalesTax\Model\Logger;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

#[AllowMockObjectsWithoutExpectations]
class AddressValidationTest extends UnitTestCase
{
    private const PAYLOAD = '<img src=x onerror=alert(1)>';
    private const ESCAPED = '&lt;img src=x onerror=alert(1)&gt;';

    /**
     * @var Client|\PHPUnit\Framework\MockObject\MockObject
     */
    private $clientMock;

    /**
     * @var AddressValidation
     */
    private $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientMock = $this->createMock(Client::class);
        $clientFactoryMock = $this->createMock(ClientFactory::class);
        $clientFactoryMock->method('create')->willReturn($this->clientMock);

        $loggerMock = $this->createMock(Logger::class);
        $loggerMock->method('setFilename')->willReturnSelf();

        $scopeConfigMock = $this->createStub(ScopeConfigInterface::class);
        $scopeConfigMock->method('getValue')->willReturn(true);

        $regionMock = $this->getMockBuilder(Region::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load', 'loadByCode', 'getId'])
            ->getMock();
        $regionMock->method('load')->willReturnSelf();
        $regionMock->method('loadByCode')->willReturnSelf();
        $regionMock->method('getId')->willReturn('12');
        $regionMock->setData('code', 'CA');
        $regionFactoryMock = $this->createMock(RegionFactory::class);
        $regionFactoryMock->method('create')->willReturn($regionMock);

        $cacheMock = $this->createStub(CacheInterface::class);
        $cacheMock->method('load')->willReturn(false);

        $this->sut = new AddressValidation(
            $clientFactoryMock,
            $loggerMock,
            $scopeConfigMock,
            $regionFactoryMock,
            $this->createStub(CountryFactory::class),
            $cacheMock,
            $this->createStub(StoreManagerInterface::class)
        );
    }

    public function testOriginalAddressChangesAreEscaped()
    {
        $this->clientMock->method('postResource')
            ->willThrowException(new \Exception('{"status":503,"error":"unavailable","detail":"test"}'));

        $results = $this->sut->validateAddress(
            '123 Main St ' . self::PAYLOAD,
            null,
            'San Mateo ' . self::PAYLOAD,
            '12',
            'US',
            '94401' . self::PAYLOAD
        );

        static::assertCount(1, $results);
        static::assertSame('123 Main St ' . self::ESCAPED, $results[0]['changes']['street'][0]);
        static::assertSame('San Mateo ' . self::ESCAPED, $results[0]['changes']['city']);
        static::assertSame('94401' . self::ESCAPED, $results[0]['changes']['postcode']);
        static::assertSame('123 Main St ' . self::PAYLOAD, $results[0]['address']['street'][0]);
    }

    public function testSuggestedAddressChangesAreEscaped()
    {
        $this->clientMock->method('postResource')->willReturn([
            'addresses' => [[
                'street' => '123 Main St ' . self::PAYLOAD,
                'city' => 'San Mateo',
                'state' => 'CA',
                'country' => 'US',
                'zip' => '94401-1234',
            ]],
        ]);

        $results = $this->sut->validateAddress('123 Main St', null, 'San Mateo', '12', 'US', '94401');

        static::assertCount(2, $results);
        static::assertSame(
            '123 Main St <span class="suggested-address-diff">' . self::ESCAPED . '</span> ',
            $results[1]['changes']['street'][0]
        );
        static::assertSame('San Mateo', $results[1]['changes']['city']);
    }

    public function testHtmlDiffEscapesWords()
    {
        static::assertSame(
            '1 B St <span class="suggested-address-diff">' . self::ESCAPED . '</span> ',
            $this->sut->htmlDiff('1 B St', '1 B St ' . self::PAYLOAD)
        );
        static::assertSame('A &amp; B St ', $this->sut->htmlDiff('A & B St', 'A & B St'));
    }
}
