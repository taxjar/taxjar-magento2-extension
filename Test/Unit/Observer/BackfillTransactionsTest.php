<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Observer;

use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManager;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\Logger;
use Taxjar\SalesTax\Model\Transaction\OrderFactory;
use Taxjar\SalesTax\Model\Transaction\RefundFactory;
use Taxjar\SalesTax\Model\TransactionFactory;
use Taxjar\SalesTax\Observer\BackfillTransactions;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

class BackfillTransactionsTest extends UnitTestCase
{
    /**
     * @var RequestInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $request;
    /**
     * @var StoreManager|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $storeManager;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|Logger
     */
    private $logger;
    /**
     * @var OrderRepositoryInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderRepository;
    /**
     * @var FilterBuilder|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $filterBuilder;
    /**
     * @var SearchCriteriaBuilder|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $searchCriteriaBuilder;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|TaxjarConfig
     */
    private $taxjarConfig;
    /**
     * @var BulkManagementInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $bulkManagement;
    /**
     * @var OperationInterfaceFactory|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $operationInterfaceFactory;
    /**
     * @var IdentityService|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $identityService;
    /**
     * @var SerializerInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $serializer;
    /**
     * @var UserContextInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $userContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = $this->createMock(RequestInterface::class);
        $this->storeManager = $this->createMock(StoreManager::class);
        $this->logger = $this->createMock(Logger::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->filterBuilder = $this->createMock(FilterBuilder::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->taxjarConfig = $this->createMock(TaxjarConfig::class);
        $this->bulkManagement = $this->createMock(BulkManagementInterface::class);
        $this->operationInterfaceFactory = $this->getMockBuilder(OperationInterfaceFactory::class)->disableOriginalConstructor()->getMock();
        $this->identityService = $this->createMock(IdentityService::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->userContext = $this->createMock(UserContextInterface::class);

        $this->logger->expects($this->once())->method('setFilename')->with('transactions.log')->willReturnSelf();
        $this->logger->expects($this->once())->method('force')->willReturnSelf();
    }

    public function testExecuteThrowsExceptionWithoutApiKey()
    {
        $this->taxjarConfig->expects($this->once())->method('getApiKey')->willReturn('');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Could not sync transactions with TaxJar. Please make sure you have an API key.');

        $sut = $this->getTestSubject();
        $sut->execute(new Observer());
    }

    public function testGetSearchCriteria()
    {
        $this->request->expects($this->any())->method('getParam')->willReturn(null);

        $mockFilter = $this->createMock(Filter::class);
        $this->filterBuilder->expects($this->any())->method('setField')->willReturnSelf();
        $this->filterBuilder->expects($this->any())->method('setValue')->willReturnSelf();
        $this->filterBuilder->expects($this->any())->method('setConditionType')->willReturnSelf();
        $this->filterBuilder->expects($this->any())->method('create')->willReturn($mockFilter);

        $mockCriteria = $this->createMock(SearchCriteria::class);
        $this->searchCriteriaBuilder->expects($this->exactly(2))->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->exactly(1))->method('addFilters')->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->exactly(1))->method('create')->willReturn($mockCriteria);

        $sut = $this->getTestSubject();
        $result = $sut->getSearchCriteria();

        self::assertInstanceOf(SearchCriteria::class, $result);
    }

    protected function getTestSubject(): BackfillTransactions
    {
        return new BackfillTransactions(
            $this->request,
            $this->storeManager,
            $this->logger,
            $this->orderRepository,
            $this->filterBuilder,
            $this->searchCriteriaBuilder,
            $this->taxjarConfig,
            $this->bulkManagement,
            $this->operationInterfaceFactory,
            $this->identityService,
            $this->serializer,
            $this->userContext
        );
    }
}
