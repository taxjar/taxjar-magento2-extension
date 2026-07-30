<?php
/**
 * Taxjar_SalesTax
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Taxjar
 * @package    Taxjar_SalesTax
 * @copyright  Copyright (c) 2017 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Unit\Observer;

use Exception;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Taxjar\SalesTax\Model\Configuration;
use Taxjar\SalesTax\Model\Logger;
use Taxjar\SalesTax\Observer\BackfillTransactions;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Taxjar\SalesTax\Test\Unit\UnitTestCase;

#[AllowMockObjectsWithoutExpectations]
class BackfillTransactionsTest extends UnitTestCase
{
    /**
     * @var BackfillTransactions
     */
    protected $sut;
    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;
    /**
     * @var Logger|MockObject
     */
    protected $loggerMock;
    /**
     * @var OrderRepositoryInterface|MockObject
     */
    protected $orderRepositoryMock;
    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerMock;
    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    protected $searchCriteriaBuilderMock;
    /**
     * @var BulkManagementInterface|MockObject
     */
    protected $bulkManagementMock;
    /**
     * @var OperationInterfaceFactory|MockObject
     */
    protected $operationFactoryMock;
    /**
     * @var IdentityGeneratorInterface|MockObject
     */
    protected $identityServiceMock;
    /**
     * @var SerializerInterface|MockObject
     */
    protected $serializerMock;
    /**
     * @var UserContextInterface|MockObject
     */
    protected $userContextMock;
    /**
     * @var MockObject|Configuration
     */
    protected $taxjarConfigMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestMock = $this->createMock(RequestInterface::class);

        $this->loggerMock = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);

        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);

        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->bulkManagementMock = $this->createMock(BulkManagementInterface::class);

        $this->operationFactoryMock = $this->getMockBuilder(OperationInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->identityServiceMock = $this->createMock(IdentityGeneratorInterface::class);

        $this->serializerMock = $this->createStub(SerializerInterface::class);

        $this->userContextMock = $this->createStub(UserContextInterface::class);

        $this->taxjarConfigMock = $this->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set constructor expectations
        $this->loggerMock->expects($this->any())->method('setFilename')->with('transactions.log')->willReturnSelf();
        $this->loggerMock->expects($this->any())->method('force')->willReturnSelf();
        $this->identityServiceMock->expects($this->any())->method('generateId')->willReturn('unique-id');

        $this->setExpectations();
    }

    public function testClassConstantBatchSize()
    {
        $this->assertEquals(100, $this->sut::BATCH_SIZE);
    }

    public function testClassConstantSyncableStatuses()
    {
        $this->assertSame(['complete', 'closed'], $this->sut::SYNCABLE_STATUSES);
    }

    public function testExecuteMethodThrowsExceptionWithoutApiKey()
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Could not sync transactions with TaxJar. Please make sure you have an API key.');

        $this->taxjarConfigMock->expects($this->once())->method('getApiKey')->willReturn('');
        $this->setExpectations();

        /** @var Observer|MockObject $observerMock */
        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects($this->any())->method('getData')->willReturnMap([]);

        $this->sut->execute($observerMock);
    }

    /**
     * @dataProvider searchCriteriaDataProvider
     * @param $paramReturnMap
     * @param $dataReturnMap
     * @param $dateRange
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('searchCriteriaDataProvider')]
    public function testGetSearchCriteriaMethod($paramReturnMap, $dataReturnMap, $dateRange)
    {
        $this->requestMock->expects($this->any())->method('getParam')->willReturnMap($paramReturnMap);
        $this->setStoreExpectations();

        $searchCriteriaMock = $this->createStub(SearchCriteriaInterface::class);

        $this->searchCriteriaBuilderMock->expects($this->exactly(3))->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())->method('create')->willReturn($searchCriteriaMock);

        $this->setExpectations();
        /** @var Observer|MockObject $observerMock */
        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects($this->any())->method('getData')->willReturnMap($dataReturnMap);
        $this->sut->observer = $observerMock;

        $this->assertSame($searchCriteriaMock, $this->sut->getSearchCriteria(...$dateRange));
    }

    public static function searchCriteriaDataProvider(): array
    {
        $testDates = static::getTestDates();

        return [
            'request_without_configuration' => [
                [
                    ['store', null, null],
                    ['website', null, null],
                    ['from', null, null],
                    ['to', null, null],
                    ['force', null, '0'],
                ],
                [
                    ['from', null, null],
                    ['to', null, null],
                    ['force', null, null],
                ],
                $testDates,
            ],

            'observer_without_configuration' => [
                [
                    ['store', null, null],
                    ['website', null, null],
                    ['from', null, null],
                    ['to', null, null],
                    ['force', null, null],
                ],
                [
                    ['from', null, null],
                    ['to', null, null],
                    ['force', null, '0'],
                ],
                $testDates,
            ],

            'request_with_force_sync_enabled' => [
                [
                    ['store', null, null],
                    ['website', null, null],
                    ['from', null, null],
                    ['to', null, null],
                    ['force', null, '1'],
                ],
                [
                    ['from', null, null],
                    ['to', null, null],
                    ['force', null, null],
                ],
                $testDates,
            ],

            'observer_with_force_sync_enabled' => [
                [
                    ['store', null, null],
                    ['website', null, null],
                    ['from', null, null],
                    ['to', null, null],
                    ['force', null, null],
                ],
                [
                    ['from', null, null],
                    ['to', null, null],
                    ['force', null, '1'],
                ],
                $testDates,
            ],

            'request_with_date_range' => [
                [
                    ['store', null, null],
                    ['website', null, null],
                    ['from', null, '2021-01-01'],
                    ['to', null, '2021-01-31'],
                    ['force', null, null],
                ],
                [
                    ['from', null, null],
                    ['to', null, null],
                    ['force', null, null],
                ],
                [
                    '2021-01-01 00:00:00',
                    '2021-01-31 23:59:59',
                ],
            ],

            'observer_with_date_range' => [
                [
                    ['store', null, null],
                    ['website', null, null],
                    ['from', null, null],
                    ['to', null, null],
                    ['force', null, null],
                ],
                [
                    ['from', null, '2021-01-01'],
                    ['to', null, '2021-01-31'],
                    ['force', null, null],
                ],
                [
                    '2021-01-01 00:00:00',
                    '2021-01-31 23:59:59',
                ],
            ],

            'request_with_date_range_and_force_sync_enabled' => [
                [
                    ['store', null, null],
                    ['website', null, null],
                    ['from', null, '2021-01-01'],
                    ['to', null, '2021-01-31'],
                    ['force', null, '1'],
                ],
                [
                    ['from', null, null],
                    ['to', null, null],
                    ['force', null, null],
                ],
                [
                    '2021-01-01 00:00:00',
                    '2021-01-31 23:59:59',
                ],
            ],

            'observer_with_date_range_and_force_sync_enabled' => [
                [
                    ['store', null, null],
                    ['website', null, null],
                    ['from', null, null],
                    ['to', null, null],
                    ['force', null, null],
                ],
                [
                    ['from', null, '2021-01-01'],
                    ['to', null, '2021-01-31'],
                    ['force', null, '1'],
                ],
                [
                    '2021-01-01 00:00:00',
                    '2021-01-31 23:59:59',
                ],
            ],

            'request_with_website' => [
                [
                    ['store', null, null],
                    ['website', null, '1'],
                    ['from', null, null],
                    ['to', null, null],
                    ['force', null, null],
                ],
                [
                    ['from', null, null],
                    ['to', null, null],
                    ['force', null, null],
                ],
                $testDates,
            ],
        ];
    }

    /**
     * @dataProvider orderDataProvider
     * @param bool $forceSync
     * @param int $count
     * @param string $updatedAt
     * @param string $syncedAt
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('orderDataProvider')]
    public function testGetOrdersMethod(bool $forceSync, int $count, string $updatedAt, string $syncedAt)
    {
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getUpdatedAt'
            ])
            ->getMock();

        if (!$forceSync) {
            $orderMock->expects($this->once())->method('getUpdatedAt')->willReturn($updatedAt);
            $orderMock->setData('tj_salestax_sync_date', $syncedAt);
        }

        $orderSearchResult = $this->createMock(OrderSearchResultInterface::class);
        $orderSearchResult->expects($this->once())->method('getItems')->willReturn([$orderMock]);
        $this->orderRepositoryMock->expects($this->once())->method('getList')->willReturn($orderSearchResult);

        $this->setExpectations();

        $observerMock = $this->createMock(Observer::class);
        $observerMock->expects($this->any())
            ->method('getData')
            ->willReturnMap([
                ['from', null, null],
                ['to', null, null],
                ['force', null, $forceSync],
            ]);

        $this->sut->observer = $observerMock;

        $criteriaMock = $this->createStub(SearchCriteriaInterface::class);

        $this->assertCount($count, $this->sut->getOrders($criteriaMock));
    }

    public static function orderDataProvider(): array
    {
        return [
            'force_sync_enabled_for_unsyncable_order' => [true, 1, '2021-01-01', '2021-01-01'],
            'force_sync_disabled_for_unsyncable_order' => [false, 0, '2021-01-01', '2021-01-01'],
            'force_sync_enabled_for_syncable_order' => [true, 1, '2021-02-01', '2021-01-01'],
            'force_sync_disabled_for_syncable_order' => [false, 1, '2021-02-01', '2021-01-01'],
        ];
    }

    public function testSyncTransactionMethodThrowsExceptionWhenScheduleBulkReturnsFalse()
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Bulk management encountered an unknown error.');

        $this->bulkManagementMock->expects($this->once())->method('scheduleBulk')->willReturn(false);

        $operationMock = $this->createStub(OperationInterface::class);
        $this->operationFactoryMock->expects($this->once())->method('create')->willReturn($operationMock);

        $this->setExpectations();

        $orderMock = $this->createMock(Order::class);
        $orderMock->expects($this->once())->method('getEntityId')->willReturn(1);

        $this->sut->syncTransactions([$orderMock]);
    }

    /**
     * @dataProvider syncTransactionDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('syncTransactionDataProvider')]
    public function testSyncTransactionMethod(int $orderCount, int $count, bool $force)
    {
        $orders = array_map([$this, 'getOrderStub'], range(1, $orderCount));

        $this->bulkManagementMock->expects($this->once())->method('scheduleBulk')->willReturn(true);

        $operationMock = $this->createStub(OperationInterface::class);
        $this->operationFactoryMock->expects($this->exactly($count))->method('create')->willReturn($operationMock);

        $this->setExpectations();

        $this->sut->syncTransactions($orders);
    }

    public static function syncTransactionDataProvider(): array
    {
        return [
            'single_operation_without_force' => [100, 1, false],
            'single_operation_with_force' => [100, 1, true],
            'multiple_operations_without_force' => [500, 5, false],
            'multiple_operations_with_force' => [500, 5, true],
        ];
    }

    public function testSuccessMethod()
    {
        [$startDate, $endDate] = static::getTestDates();
        $expectedConfig = json_encode([
            'date_start' => $startDate,
            'date_end' => $endDate,
            'force_sync' => false,
        ]);
        $expectedMessage = "No un-synced orders were found!";
        $expectedLogMessage = "$expectedMessage Detail: \"$expectedConfig\"";

        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with($expectedLogMessage)
            ->willReturnSelf();

        $this->setExpectations();

        $this->sut->success();
    }

    public function testFailMethod()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('test-error');

        $payload = json_encode([
            'date_start' => '2021-01-01 00:00:00',
            'date_end' => '2021-01-31 23:59:59',
            'force_sync' => false,
        ]);

        $logMessage = "Failed to schedule transaction sync! Message: \"test-error\" Detail: \"$payload\"";

        $this->loggerMock->expects($this->once())->method('log')->with($logMessage)->willReturnSelf();

        $exception = new Exception('test-error');

        $dataMap = [
            ['from_date', null, '2021-01-01'],
            ['to_date', null, '2021-01-31'],
            ['force', null, '0'],
        ];

        $observerMock = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $observerMock->expects($this->any())->method('getData')->willReturnMap($dataMap);

        $this->setExpectations();
        $this->sut->observer = $observerMock;
        $this->sut->fail($exception);
    }

    protected function expectSearchCriteria(): void
    {
        $searchCriteriaMock = $this->createStub(SearchCriteriaInterface::class);

        $this->searchCriteriaBuilderMock->expects($this->exactly(3))->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())->method('create')->willReturn($searchCriteriaMock);
    }

    /**
     * @param $id
     * @return Order|MockObject
     */
    protected function getOrderStub($id)
    {
        $orderStub = $this->createMock(Order::class);
        $orderStub->expects($this->any())->method('getEntityId')->willReturn($id);
        return $orderStub;
    }

    /**
     * Used when StoreManager class is called while retrieving search criteria.
     */
    protected function setStoreExpectations(): void
    {
        $storeListMock = [];

        if ($this->requestMock->getParam('store') && !$this->requestMock->getParam('website')) {
            $storeMock = $this->createMock(StoreInterface::class);
            $storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
            $storeListMock[] = $storeMock;
        }

        $this->storeManagerMock->expects($this->any())->method('getStores')->willReturn($storeListMock);
    }

    /**
     * Used to set the test subject.
     *
     * When called in the `setUp` method, this sets a new instance of the class under test.
     * If any mock dependencies need to be configured within a test method, calling this method
     * will "refresh" the configured dependencies by creating a new test subject with the updated
     * mock dependencies.
     */
    protected function setExpectations()
    {
        $this->sut = new BackfillTransactions(
            $this->requestMock,
            $this->loggerMock,
            $this->orderRepositoryMock,
            $this->storeManagerMock,
            $this->searchCriteriaBuilderMock,
            $this->bulkManagementMock,
            $this->operationFactoryMock,
            $this->identityServiceMock,
            $this->serializerMock,
            $this->userContextMock,
            $this->taxjarConfigMock
        );
    }

    /**
     * This method is necessary to replicate the hard dependency of DateTime object
     * @return array
     */
    private static function getTestDates(): array
    {
        $date = new \DateTimeImmutable();
        return [
            $date->sub(new \DateInterval('P1D'))->setTime(0, 0)->format('Y-m-d H:i:s'),
            $date->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
        ];
    }
}
