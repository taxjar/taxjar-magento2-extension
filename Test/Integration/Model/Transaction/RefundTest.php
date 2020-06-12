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
class RefundTest extends \PHPUnit\Framework\TestCase
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

    /**
     * @var \Taxjar\SalesTax\Model\Transaction\Refund
     */
    protected $transactionRefund;

    protected function setUp()
    {
        $this->cleanupReservations = Bootstrap::getObjectManager()->get(CleanupReservationsInterface::class);
        $this->order = Bootstrap::getObjectManager()->get(Order::class);
        $this->transactionOrder = Bootstrap::getObjectManager()->get('Taxjar\SalesTax\Model\Transaction\Order');
        $this->transactionRefund = Bootstrap::getObjectManager()->get('Taxjar\SalesTax\Model\Transaction\Refund');
    }

    /**
     * @magentoDataFixture ../../../../app/code/Taxjar/SalesTax/Test/Integration/_files/transaction/refund_bundle_partial.php
     */
    public function testDefaultRefund()
    {
        $order = $this->order->loadByIncrementId('100000001');
        $creditmemo = $order->getCreditmemosCollection()->getFirstItem();
        $result = $this->transactionRefund->build($order, $creditmemo);
        $lineItems = $result['line_items'];

        $this->assertEquals(2, count($lineItems), 'Number of line items is incorrect');
        $this->assertEquals(1, $lineItems[0]['quantity'], 'Invalid quantity');
        $this->assertEquals('24-WG082-blue', $lineItems[0]['product_identifier'], 'Invalid sku.');
        $this->assertEquals(1, $lineItems[1]['quantity'], 'Invalid quantity');
        $this->assertEquals('24-WG084', $lineItems[1]['product_identifier'], 'Invalid sku.');
        $this->assertArrayNotHasKey(2, $lineItems);
        $this->assertArrayNotHasKey(3, $lineItems);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Taxjar/SalesTax/Test/Integration/_files/transaction/refund_simple.php
     */
    public function testFullRefund()
    {
        $order = $this->order->loadByIncrementId('100000002');
        $creditmemo = $order->getCreditmemosCollection()->getFirstItem();
        $result = $this->transactionRefund->build($order, $creditmemo);

        $this->assertEquals(81.0, $result['amount'], 'Incorrect refund amount');
    }

    /**
     * @magentoDataFixture ../../../../app/code/Taxjar/SalesTax/Test/Integration/_files/transaction/refund_simple_partial.php
     */
    public function testPartialRefund()
    {
        $order = $this->order->loadByIncrementId('100000004');
        $creditmemo = $order->getCreditmemosCollection()->getFirstItem();
        $result = $this->transactionRefund->build($order, $creditmemo);

        $this->assertEquals(27.0, $result['amount'], 'Incorrect refund amount');
    }

    /**
     * @magentoDataFixture ../../../../app/code/Taxjar/SalesTax/Test/Integration/_files/transaction/refund_simple_partial.php
     */
    public function testPartialRefundLineItems()
    {
        $order = $this->order->loadByIncrementId('100000004');
        $creditmemo = $order->getCreditmemosCollection()->getFirstItem();
        $creditmemoResult = $this->transactionRefund->build($order, $creditmemo);

        $this->assertEquals(1, count($creditmemoResult['line_items']), 'Invalid number of line items');
        $this->assertEquals(1, $creditmemoResult['line_items'][0]['quantity'], 'Invalid quantity');
    }

    /**
     * @magentoDataFixture ../../../../app/code/Taxjar/SalesTax/Test/Integration/_files/transaction/refund_simple.php
     */
    public function testShippingNotRefunded()
    {
        $order = $this->order->loadByIncrementId('100000002');
        $creditmemo = $order->getCreditmemosCollection()->getFirstItem();
        $result = $this->transactionRefund->build($order, $creditmemo);

        $this->assertEquals(0, $result['shipping'], 'Incorrect refund amount');
    }

    /**
     * @magentoDataFixture ../../../../app/code/Taxjar/SalesTax/Test/Integration/_files/transaction/refund_simple_shipping.php
     */
    public function testShippingRefunded()
    {
        $order = $this->order->loadByIncrementId('100000005');
        $creditmemo = $order->getCreditmemosCollection()->getFirstItem();
        $result = $this->transactionRefund->build($order, $creditmemo);

        $this->assertEquals(5, $result['shipping'], 'Invalid shipping refunded');
    }

    /**
     * @magentoDataFixture ../../../../app/code/Taxjar/SalesTax/Test/Integration/_files/transaction/refund_bundle_partial.php
     */
    public function testBundledProductsPartialRefund()
    {
        $order = $this->order->loadByIncrementId('100000001');
        $creditmemo = $order->getCreditmemosCollection()->getFirstItem();
        $result = $this->transactionRefund->build($order, $creditmemo);
        $lineItems = $result['line_items'];

        $this->assertEquals(2, count($lineItems), 'Number of line items is incorrect');
        $this->assertEquals(1, $lineItems[0]['quantity'], 'Invalid quantity');
        $this->assertEquals('24-WG082-blue', $lineItems[0]['product_identifier'], 'Invalid sku.');
        $this->assertEquals(1, $lineItems[1]['quantity'], 'Invalid quantity');
        $this->assertEquals('24-WG084', $lineItems[1]['product_identifier'], 'Invalid sku.');
        $this->assertArrayNotHasKey(2, $lineItems);
        $this->assertArrayNotHasKey(3, $lineItems);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Taxjar/SalesTax/Test/Integration/_files/transaction/refund_simple_adjustment_fee.php
     */
    public function testPartialRefundAdjustments()
    {
        $order = $this->order->loadByIncrementId('100000002');
        $creditmemo = $order->getCreditmemosCollection()->getFirstItem();
        $result = $this->transactionRefund->build($order, $creditmemo);
        $lineItems = $result['line_items'];

        $this->assertEquals(2, count($lineItems), 'Number of line items is incorrect');
        $this->assertEquals(3, $lineItems[0]['quantity'], 'Invalid quantity');
        $this->assertEquals(50.0, $lineItems[0]['discount'], 'Invalid discount amount');
        $this->assertEquals("adjustment-refund", $lineItems[1]['id'], 'Adjustment refund missing');
        $this->assertEquals(25.0, $lineItems[1]['unit_price'], 'Adjustment refund invalid');
    }

    protected function tearDown()
    {
        $this->cleanupReservations->execute();
    }
}
