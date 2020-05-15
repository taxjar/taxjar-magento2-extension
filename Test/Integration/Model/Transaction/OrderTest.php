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
     * @magentoDataFixture ../../../../app/code/Taxjar/SalesTax/Test/Integration/_files/transaction/order_test.php
     */
    public function testBundledProductsOrder()
    {
        $order = $this->order->loadByIncrementId('100000001');
        $result = $this->transactionOrder->build($order);

        $this->verifyLineItems($result);
    }

    /**
     * Verify line items match expected results
     *
     * @param array $result
     */
    protected function verifyLineItems($result)
    {
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
