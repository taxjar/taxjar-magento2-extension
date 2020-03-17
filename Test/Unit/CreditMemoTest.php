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
 * @copyright  Copyright (c) 2020 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

// @codingStandardsIgnoreStart

namespace Taxjar\SalesTax\Test\Unit;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\OrderFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Item\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Item\Collection as ItemCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;

class CreditMemoTest extends \PHPUnit\Framework\TestCase
{
    // Taxjar\SalesTax\Model\Transaction
//    protected $transaction;

    // Taxjar\SalesTax\Model\Transaction\Refund
//    protected $refund;

    protected $order;
    protected $creditmemo;


    protected function setUp()
    {
//        $this->transaction = $this->createMock('Taxjar\SalesTax\Model\Transaction');
//        $this->refund = $this->createMock('Taxjar\SalesTax\Model\Transaction\Refund');
//
//        // build invoice mock ?
//
//        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
//        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
//
//        $objectManagerHelper = new ObjectManagerHelper($this);
//        $this->cmItemCollectionFactoryMock = $this->getMockBuilder(
//            \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Item\CollectionFactory::class
//        )->disableOriginalConstructor()
//            ->setMethods(['create'])
//            ->getMock();
//
//        $arguments = [
//            'context' => $this->createMock(\Magento\Framework\Model\Context::class),
//            'registry' => $this->createMock(\Magento\Framework\Registry::class),
//            'localeDate' => $this->createMock(
//                \Magento\Framework\Stdlib\DateTime\TimezoneInterface::class
//            ),
//            'dateTime' => $this->createMock(\Magento\Framework\Stdlib\DateTime::class),
//            'creditmemoConfig' => $this->createMock(
//                \Magento\Sales\Model\Order\Creditmemo\Config::class
//            ),
//            'cmItemCollectionFactory' => $this->cmItemCollectionFactoryMock,
//            'calculatorFactory' => $this->createMock(\Magento\Framework\Math\CalculatorFactory::class),
//            'storeManager' => $this->createMock(\Magento\Store\Model\StoreManagerInterface::class),
//            'commentFactory' => $this->createMock(\Magento\Sales\Model\Order\Creditmemo\CommentFactory::class),
//            'commentCollectionFactory' => $this->createMock(
//                \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Comment\CollectionFactory::class
//            ),
//            'scopeConfig' => $this->scopeConfigMock,
//            'orderRepository' => $this->orderRepository,
//        ];
//        $this->creditmemo = $objectManagerHelper->getObject(
//            \Magento\Sales\Model\Order\Creditmemo::class,
//            $arguments
//        );

//        $this->setUpOrder();
//        $this->setUpCreditmemo();
    }

    /**
     * @param string $className
     * @param array $variables
     * @dataProvider dataProviderCreditMemo
     */
    public function testCreditMemo($className = null, $variables = null)
    {
        echo "classname: $className \n";
        echo print_r($variables, 1) . "\n";

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $refund = $objectManager->getObject($className);

        $order = $objectManager->getObject('Magento\Sales\Model\Order');

        /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
        $creditmemo = $objectManager->getObject('Magento\Sales\Model\Order\Creditmemo');

        $item1 = $objectManager->getObject('\Magento\Sales\Model\Order\Creditmemo\Item');
        $item1->setData([

        ]);
        $item2 = $objectManager->getObject('\Magento\Sales\Model\Order\Creditmemo\Item');

        $creditmemo->setItems([$item1, $item2]);

        $build = call_user_func([$refund, 'build'], $order, $creditmemo);
//        $build = call_user_func([$refund, 'build'], $this->order, $this->creditmemo);

        // trigger observer SyncTransaction (only if buildLineItems can't be called directly)
        // pass order w/ attached credit memo

        // test buildLineItems w/ group product
        // assert configurable parent is absent, simple children are present

//        $this->transaction->buildLineItems();
        $items = [];
//        $this->refund->build($this->order, $items);

        // fails because of mock classes
//        $this->assertTrue(get_class($this->transaction) === 'Taxjar\SalesTax\Model\Transaction', 'TEST');
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProviderCreditMemo()
    {
        return [
            [
                'Taxjar\SalesTax\Model\Transaction\Refund',
                [
//                    'tmp' => [
//                        [
//                            'Magento\Sales\Model\Order',
//                            []
//                        ],
//                        [
//                            'Magento\Sales\Model\Order\Creditmemo',
//                            []
//                        ]
//                    ],
                    'items' => [
                        ['id' => 1],
                        ['id' => 2],
                        ['id' => 3]
                    ]
                ]
            ]
        ];
//        return [
//            [
//                'Taxjar\SalesTax\Model\Transaction\Refund',
//                [
//                    'items' => [
//                        ['id' => 1], ['id' => 2], ['id' => 3]
//                    ]
//                ]
//            ]
//        ];
    }




    protected function setUpOrder()
    {
        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->paymentCollectionFactoryMock = $this->createPartialMock(
            \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory::class,
            ['create']
        );
        $this->orderItemCollectionFactoryMock = $this->createPartialMock(
            \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory::class,
            ['create']
        );
        $this->historyCollectionFactoryMock = $this->createPartialMock(
            \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory::class,
            ['create']
        );
        $this->productCollectionFactoryMock = $this->createPartialMock(
            \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class,
            ['create']
        );
        $this->salesOrderCollectionFactoryMock = $this->createPartialMock(
            \Magento\Sales\Model\ResourceModel\Order\CollectionFactory::class,
            ['create']
        );
        $this->item = $this->createPartialMock(
            \Magento\Sales\Model\ResourceModel\Order\Item::class,
            [
                'isDeleted',
                'getQtyToInvoice',
                'getParentItemId',
                'getQuoteItemId',
                'getLockedDoInvoice',
                'getProductId',
            ]
        );
        $this->salesOrderCollectionMock = $this->getMockBuilder(
            \Magento\Sales\Model\ResourceModel\Order\Collection::class
        )->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'load', 'getFirstItem'])
            ->getMock();
        $collection = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\Item\Collection::class);
        $collection->expects($this->any())->method('setOrderFilter')->willReturnSelf();
        $collection->expects($this->any())->method('getItems')->willReturn([$this->item]);
        $collection->expects($this->any())->method('getIterator')->willReturn(new \ArrayIterator([$this->item]));
        $this->orderItemCollectionFactoryMock->expects($this->any())->method('create')->willReturn($collection);

        $this->priceCurrency = $this->getMockForAbstractClass(
            \Magento\Framework\Pricing\PriceCurrencyInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['round']
        );
        $this->localeResolver = $this->createMock(ResolverInterface::class);
        $this->timezone = $this->createMock(TimezoneInterface::class);
        $this->incrementId = '#00000001';
        $this->eventManager = $this->createMock(\Magento\Framework\Event\Manager::class);
        $context = $this->createPartialMock(\Magento\Framework\Model\Context::class, ['getEventDispatcher']);
        $context->expects($this->any())->method('getEventDispatcher')->willReturn($this->eventManager);

        $this->itemRepository = $this->getMockBuilder(OrderItemRepositoryInterface::class)
            ->setMethods(['getList'])
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->setMethods(['addFilter', 'create'])
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->order = $helper->getObject(
            \Magento\Sales\Model\Order::class,
            [
                'paymentCollectionFactory' => $this->paymentCollectionFactoryMock,
                'orderItemCollectionFactory' => $this->orderItemCollectionFactoryMock,
                'data' => ['increment_id' => $this->incrementId],
                'context' => $context,
                'historyCollectionFactory' => $this->historyCollectionFactoryMock,
                'salesOrderCollectionFactory' => $this->salesOrderCollectionFactoryMock,
                'priceCurrency' => $this->priceCurrency,
                'productListFactory' => $this->productCollectionFactoryMock,
                'localeResolver' => $this->localeResolver,
                'timezone' => $this->timezone,
                'itemRepository' => $this->itemRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'scopeConfig' => $this->scopeConfigMock
            ]
        );
    }

    protected function setUpCreditmemo()
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);

        $objectManagerHelper = new ObjectManagerHelper($this);
//        $this->cmItemCollectionFactoryMock = $this->getMockBuilder(
//            \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Item\CollectionFactory::class
//        )->disableOriginalConstructor()
//            ->setMethods(['create'])
//            ->getMock();


        $item1 = $this->setUpItem();
        $item2 = $this->setUpItem();

        /** @var \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Item\Collection $collection */
        $collection = $objectManagerHelper->getObject('\Magento\Sales\Model\ResourceModel\Order\Creditmemo\Item\Collection', [$item1, $item2]);
        $collection->setItems([$item1, $item2]);
        $this->cmItemCollectionFactoryMock = $collection;

        $arguments = [
            'context' => $this->createMock(\Magento\Framework\Model\Context::class),
            'registry' => $this->createMock(\Magento\Framework\Registry::class),
            'localeDate' => $this->createMock(
                \Magento\Framework\Stdlib\DateTime\TimezoneInterface::class
            ),
            'dateTime' => $this->createMock(\Magento\Framework\Stdlib\DateTime::class),
            'creditmemoConfig' => $this->createMock(
                \Magento\Sales\Model\Order\Creditmemo\Config::class
            ),
            'cmItemCollectionFactory' => $this->cmItemCollectionFactoryMock,
            'calculatorFactory' => $this->createMock(\Magento\Framework\Math\CalculatorFactory::class),
            'storeManager' => $this->createMock(\Magento\Store\Model\StoreManagerInterface::class),
            'commentFactory' => $this->createMock(\Magento\Sales\Model\Order\Creditmemo\CommentFactory::class),
            'commentCollectionFactory' => $this->createMock(
                \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Comment\CollectionFactory::class
            ),
            'scopeConfig' => $this->scopeConfigMock,
            'orderRepository' => $this->orderRepository,
        ];
        $this->creditmemo = $objectManagerHelper->getObject(
            \Magento\Sales\Model\Order\Creditmemo::class,
            $arguments
        );
    }


    protected function setUpItem()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $orderItemFactoryMock = $this->getMockBuilder(\Magento\Sales\Model\Order\ItemFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $item  = $objectManager->getObject(
            \Magento\Sales\Model\Order\Creditmemo\Item::class,
            [
                'orderItemFactory' => $orderItemFactoryMock
            ]
        );

        return $item;
    }
}
