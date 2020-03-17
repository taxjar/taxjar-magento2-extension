<?php // @codingStandardsIgnoreStart
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

namespace Taxjar\SalesTax\Test\Integration\Model\Tax\Sales\Total\Quote;

use Magento\Tax\Model\Calculation;
use Magento\TestFramework\Helper\Bootstrap;


use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\OrderFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Item\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Item\Collection as ItemCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;

//require_once __DIR__ . '/SetupUtil.php.OLD';
require_once __DIR__ . '/../../../_files/tax_calculation_data_aggregated.php';

class CreditMemoTest extends \PHPUnit\Framework\TestCase {

    protected $setupUtil = null;

    public function setUp()
    {
        // mock order, invoice, credit memo

        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->cmItemCollectionFactoryMock = $this->getMockBuilder(
            \Magento\Sales\Model\ResourceModel\Order\Creditmemo\Item\CollectionFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

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

    //TODO: determine why arguments aren't passed to function
    public function testCreditMemo()
    {
        $configData = [];
        $creditMemoData = [];
        $expectedResults = [];

        $incrementId = '000000042';  //TODO: pull from data

        /** @var  \Magento\Framework\ObjectManagerInterface $objectManager */
        $objectManager = Bootstrap::getObjectManager();

        /** @var  \Magento\Sales\Model\Order $order */
        $order = $objectManager->create('Magento\Sales\Model\Order');
        $order->loadByIncrementId($incrementId);

        /** @var  \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $objectManager->create('Magento\Sales\Model\Order\Invoice');
        //invoice->setData();


        /** @var  \Magento\Sales\Model\Order\Creditmemo $totalsCollector */
        $creditMemo = $objectManager->create('Magento\Sales\Model\Order\Creditmemo');

        //Setup tax configurations
        $this->setupUtil = new SetupUtil($objectManager);
        $this->setupUtil->setupTax($configData);

        $quote = $this->setupUtil->setupQuote($creditMemoData);
        $quoteAddress = $quote->getShippingAddress();
        $this->verifyResult($quoteAddress, $expectedResults);

        $this->assertTrue(get_class($order) === 'Magento\Sales\Model\Order', 'TEST');
        $this->assertTrue(get_class($invoice) === 'Magento\Sales\Model\Order\Invoice', 'TEST');
        $this->assertTrue(get_class($creditMemo) === 'Magento\Sales\Model\Order\Creditmemo', 'TEST');
    }

    protected function verifyResult($quoteAddress, $expectedResults)
    {

    }

    public function taxDataProvider()
    {
        global $taxCalculationData;
        return $taxCalculationData;
    }
}
