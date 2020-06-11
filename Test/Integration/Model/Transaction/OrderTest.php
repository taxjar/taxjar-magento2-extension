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

namespace Taxjar\SalesTax\Test\Integration\Model\Transaction;

use Magento\InventoryReservationsApi\Model\CleanupReservationsInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @see https://app.hiptest.com/projects/69435/test-plan/folders/419534/scenarios/2587535
 */
class OrderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CleanupReservationsInterface
     */
    protected $cleanupReservations;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var \Taxjar\SalesTax\Model\Transaction\Order
     */
    protected $transactionOrder;

    protected function setUp()
    {
        $this->cleanupReservations = Bootstrap::getObjectManager()->get(CleanupReservationsInterface::class);
        $this->order = Bootstrap::getObjectManager()->get(Order::class);
        $this->transactionOrder = Bootstrap::getObjectManager()->get('Taxjar\SalesTax\Model\Transaction\Order');
    }

    /**
     * @magentoDataFixture ../../../../app/code/Taxjar/SalesTax/Test/Integration/_files/transaction/order_simple.php
     */
    public function testDefaultOrder()
    {
        $order = $this->order->loadByIncrementId('100000002');
        $result = $this->transactionOrder->build($order);

        $this->assertEquals('api', $result['provider'], 'Invalid provider');
        $this->assertEquals(68, $result['amount'], 'Invalid total amount');
        $this->assertEquals(0, $result['shipping'], 'Invalid shipping amount');
        $this->assertEquals(0, $result['sales_tax'], 'Invalid sales tax amount');
    }

    /**
     * @magentoDataFixture ../../../../app/code/Taxjar/SalesTax/Test/Integration/_files/transaction/order_simple.php
     */
    public function testPositiveDecimals()
    {
        $order = $this->order->loadByIncrementId('100000002');
        $result = $this->transactionOrder->build($order);

        $this->assertEquals(27.0, $result['line_items'][0]['unit_price'], 'Invalid provider');
    }

    /**
     * @magentoDataFixture ../../../../app/code/Taxjar/SalesTax/Test/Integration/_files/transaction/order_simple.php
     */
    public function testOrderNoShipping()
    {
        $order = $this->order->loadByIncrementId('100000002');
        $result = $this->transactionOrder->build($order);

        $this->assertEquals(0, $result['shipping'], 'Invalid shipping amount');
    }

    /**
     * @magentoDataFixture ../../../../app/code/Taxjar/SalesTax/Test/Integration/_files/transaction/order_simple_AU.php
     */
    public function testAddressAU()
    {
        $order = $this->order->loadByIncrementId('100000003');
        $result = $this->transactionOrder->isSyncable($order);

        $this->assertFalse($result, 'Non-US order sync');
    }

    /**
     * @magentoDataFixture ../../../../app/code/Taxjar/SalesTax/Test/Integration/_files/transaction/order_simple_AUD.php
     */
    public function testCurrencyAUD()
    {
        $order = $this->order->loadByIncrementId('100000004');
        $result = $this->transactionOrder->isSyncable($order);

        $this->assertFalse($result, 'Non-USD order sync');
    }

    /**
     * @magentoDataFixture ../../../../app/code/Taxjar/SalesTax/Test/Integration/_files/transaction/order_simple_shipping.php
     */
    public function testOrderShipping()
    {
        $order = $this->order->loadByIncrementId('100000005');
        $result = $this->transactionOrder->build($order);

        $this->assertEquals(5.0, $result['shipping'], 'Invalid shipping amount');
    }

    /**
     * @magentoDataFixture ../../../../app/code/Taxjar/SalesTax/Test/Integration/_files/transaction/order_simple_customer.php
     */
    public function testExemptCustomer()
    {
        $order = $this->order->loadByIncrementId('100000002');
        $result = $this->transactionOrder->build($order);

        $this->assertEquals(1, $result['customer_id'], 'Invalid customer ID');
    }

    /**
     * @magentoDataFixture ../../../../app/code/Taxjar/SalesTax/Test/Integration/_files/transaction/order_simple_ptc.php
     */
    public function testExemptProductTaxClass()
    {
        $order = $this->order->loadByIncrementId('100000006');
        $result = $this->transactionOrder->build($order);

        $this->assertEquals('20010', $result['line_items'][0]['product_tax_code'], 'Invalid product tax class');
    }

    /**
     * @magentoDataFixture ../../../../app/code/Taxjar/SalesTax/Test/Integration/_files/transaction/order_giftcard.php
     */
    public function testExemptGiftCard()
    {
        $order = $this->order->loadByIncrementId('100000006');
        $result = $this->transactionOrder->build($order);

        $this->assertEquals('14111803A0001', $result['line_items'][0]['product_tax_code'], 'Invalid gift card');
    }

    /**
     * @magentoDataFixture ../../../../app/code/Taxjar/SalesTax/Test/Integration/_files/transaction/order_bundle.php
     */
    public function testBundledProductsOrder()
    {
        $order = $this->order->loadByIncrementId('100000001');
        $result = $this->transactionOrder->build($order);
        $lineItems = $result['line_items'];

        $this->assertNotEmpty($lineItems, 'No line items exist.');
        $this->assertEquals(4, count($lineItems), 'Number of line items is incorrect');
        $this->assertEquals(1, $lineItems[0]['quantity'], 'Invalid quantity');
        $this->assertEquals('24-WG082-blue', $lineItems[0]['product_identifier'], 'Invalid sku.');
        $this->assertEquals(1, $lineItems[1]['quantity'], 'Invalid quantity');
        $this->assertEquals('24-WG084', $lineItems[1]['product_identifier'], 'Invalid sku.');
        $this->assertEquals(1, $lineItems[2]['quantity'], 'Invalid quantity');
        $this->assertEquals('24-WG086', $lineItems[2]['product_identifier'], 'Invalid sku.');
        $this->assertEquals(1, $lineItems[3]['quantity'], 'Invalid quantity');
        $this->assertEquals('24-WG088', $lineItems[3]['product_identifier'], 'Invalid sku.');
    }

    protected function tearDown()
    {
        $this->cleanupReservations->execute();
    }
}
