<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Integration\Model\Transaction;

use Taxjar\SalesTax\Model\Transaction\Order as TaxjarOrder;
use Taxjar\SalesTax\Model\Transaction\Refund as TaxjarRefund;
use Taxjar\SalesTax\Test\Integration\IntegrationTestCase;
use Taxjar\SalesTax\Test\Fixture\Sales\CreditmemoBuilder;
use Taxjar\SalesTax\Test\Fixture\Sales\OrderBuilder;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class RefundTest extends IntegrationTestCase
{
    /**
     * @var \Taxjar\SalesTax\Model\Transaction\Order
     */
    protected $taxjarOrder;

    /**
     * @var \Taxjar\SalesTax\Model\Transaction\Refund
     */
    protected $taxjarRefund;

    protected function setUp(): void
    {
        $this->taxjarOrder = $this->objectManager->get(TaxjarOrder::class);
        $this->taxjarRefund = $this->objectManager->get(TaxjarRefund::class);
    }

    public function testCanBuildTaxjarTransactionRefundWithFullCreditmemo()
    {
        $order = OrderBuilder::anOrder()->build();
        $creditmemo = CreditmemoBuilder::forOrder($order)->build();

        $result = $this->taxjarRefund->build($order, $creditmemo);

        $this->assertCount(3, $result['line_items']);
    }

    public function testCanBuildTaxjarTransactionRefundWithPartialCreditmemo()
    {
        $order = OrderBuilder::anOrder()->build();

        $creditmemo = CreditmemoBuilder::forOrder($order)
            ->withItem((int) $order->getItems()[0]->getItemId(), 1)
            ->build();

        $result = $this->taxjarRefund->build($order, $creditmemo);

        $this->assertCount(1, $result['line_items']);
    }
}
