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
     * @var \Taxjar\SalesTax\Model\Transaction\Refund
     */
    protected $transactionRefund;

    protected function setUp()
    {
        $this->cleanupReservations = Bootstrap::getObjectManager()->get(CleanupReservationsInterface::class);
        $this->order = Bootstrap::getObjectManager()->get(Order::class);
        $this->transactionRefund = Bootstrap::getObjectManager()->get('Taxjar\SalesTax\Model\Transaction\Refund');
    }

    /**
     * @magentoDataFixture ../../../../app/code/Taxjar/SalesTax/Test/Integration/_files/transaction/refund_test_bundle_partial.php
     */
    public function testBundledProductsPartialRefund()
    {
        $order = $this->order->loadByIncrementId('100000001');

        foreach ($order->getCreditmemosCollection() as $creditmemo) {
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
    }

    /**
     * @magentoDataFixture ../../../../app/code/Taxjar/SalesTax/Test/Integration/_files/transaction/refund_test_adjustment_fee.php
     */
    public function testPartialRefundAdjustments()
    {
        $order = $this->order->loadByIncrementId('100000002');

        foreach ($order->getCreditmemosCollection() as $creditmemo) {
            $result = $this->transactionRefund->build($order, $creditmemo);
            $lineItems = $result['line_items'];

            $this->assertEquals(1, count($lineItems), 'Number of line items is incorrect');
            $this->assertEquals(5, $lineItems[0]['quantity'], 'Invalid quantity');
            $this->assertEquals(33.25, $lineItems[0]['unit_price'], 'Invalid unit price');
        }
    }

    protected function tearDown()
    {
        $this->cleanupReservations->execute();
    }
}
