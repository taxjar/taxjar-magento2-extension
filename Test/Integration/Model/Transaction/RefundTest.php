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
     * @magentoDataFixture ../../../../app/code/Taxjar/SalesTax/Test/Integration/_files/transaction/refund_test.php
     */
    public function testBundledProductsRefund()
    {
        $order = $this->order->loadByIncrementId('100000001');

        foreach ($order->getCreditmemosCollection() as $creditmemo) {
            $result = $this->transactionRefund->build($order, $creditmemo);
            $this->verifyLineItems($result);
        }
    }

    protected function verifyLineItems($result) {
        $lineItems = $result['line_items'];

        $this->assertNotEmpty($lineItems, 'No line items exist.');
        $this->assertEquals(2, count($lineItems), 'Number of line items is incorrect');

        $this->assertEquals(1, $lineItems[0]['quantity'], 'quantity');
        $this->assertEquals('24-WG082-blue', $lineItems[0]['product_identifier'], 'product_identifier (sku)');
        $this->assertEquals(1, $lineItems[1]['quantity'], 'quantity');
        $this->assertEquals('24-WG084', $lineItems[1]['product_identifier'], 'product_identifier (sku)');
        $this->assertArrayNotHasKey(2, $lineItems);
        $this->assertArrayNotHasKey(3, $lineItems);
    }

    protected function tearDown()
    {
        $this->cleanupReservations->execute();
    }
}
