<?php

namespace Taxjar\SalesTax\Test\Unit\Plugin\Customer\Model\ResourceModel;

use Magento\Customer\Api\CustomerRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Taxjar\SalesTax\Model\Client;
use Taxjar\SalesTax\Model\ClientFactory;
use Taxjar\SalesTax\Plugin\Customer\Model\ResourceModel\CustomerRepository;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class CustomerRepositoryInterfaceTest extends UnitTestCase
{
    /**
     * @var CustomerRepository|mixed
     */
    private $sut;

    /**
     * @var MockObject|ClientFactory
     */
    private $clientFactoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientFactoryMock = $this->getMockBuilder(ClientFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testBeforeDeleteById()
    {
        $subjectMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $clientMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $clientMock->expects(static::once())
            ->method('deleteResource')
            ->with('customers', 123)
            ->willReturn([]);

        $this->clientFactoryMock->expects(static::once())
            ->method('create')
            ->willReturn($clientMock);

        $this->setExpectations();

        static::assertSame(123, $this->sut->beforeDeleteById($subjectMock, 123));
    }

    protected function setExpectations()
    {
        $this->sut = new CustomerRepository(
            $this->clientFactoryMock
        );
    }
}
