<?php

namespace Taxjar\SalesTax\Test\Unit\Plugin\Customer\Model\ResourceModel;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use Taxjar\SalesTax\Model\Client;
use Taxjar\SalesTax\Model\ClientFactory;
use Taxjar\SalesTax\Model\Configuration;
use Taxjar\SalesTax\Model\Logger;
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

    /**
     * @var MockObject|Logger
     */
    private $loggerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientFactoryMock = $this->getMockBuilder(ClientFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(Logger::class)
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

    public function testBeforeDeleteByIdFailureLogsError()
    {
        $expectedMessage = 'Could not delete customer #123: Failed to initialize client';

        $mockException = new LocalizedException(__('Failed to initialize client'));

        $subjectMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->clientFactoryMock->expects(static::once())
            ->method('create')
            ->willThrowException($mockException);

        $this->loggerMock->expects(static::once())
            ->method('setFilename')
            ->with(Configuration::TAXJAR_CUSTOMER_LOG);

        $this->loggerMock->expects(static::once())
            ->method('log')
            ->with($expectedMessage);

        $this->setExpectations();

        static::assertSame(123, $this->sut->beforeDeleteById($subjectMock, 123));
    }

    protected function setExpectations()
    {
        $this->sut = new CustomerRepository(
            $this->clientFactoryMock,
            $this->loggerMock
        );
    }
}
