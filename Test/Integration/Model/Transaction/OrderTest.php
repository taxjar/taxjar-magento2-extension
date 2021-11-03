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

namespace Taxjar\SalesTax\Test\Integration\Model\Transaction;

use Taxjar\SalesTax\Model\Transaction\Order as TaxjarOrder;
use Taxjar\SalesTax\Test\Fixture\Sales\InvoiceBuilder;
use Taxjar\SalesTax\Test\Integration\IntegrationTestCase;
use Taxjar\SalesTax\Test\Fixture\Catalog\ProductBuilder;
use Taxjar\SalesTax\Test\Fixture\Customer\AddressBuilder;
use Taxjar\SalesTax\Test\Fixture\Customer\CustomerBuilder;
use Taxjar\SalesTax\Test\Fixture\Sales\OrderBuilder;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)/**
 *
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class OrderTest extends IntegrationTestCase
{
    /**
     * @var TaxjarOrder
     */
    protected $taxjarOrder;

    protected function setUp(): void
    {
        $this->taxjarOrder = $this->objectManager->get(TaxjarOrder::class);
    }

    public function testBuildTaxjarOrderTransaction()
    {
        $order = OrderBuilder::anOrder()->build();
        InvoiceBuilder::forOrder($order)->build();

        $result = $this->taxjarOrder->build($order);

        $this->assertEquals('api', $result['provider'], 'Invalid provider');
        $this->assertEquals(45, $result['amount'], 'Invalid total amount');
        $this->assertEquals(15, $result['shipping'], 'Invalid shipping amount');
        $this->assertEquals(0, $result['sales_tax'], 'Invalid sales tax amount');
    }

    public function testBuildTaxjarOrderTransactionLineItems()
    {
        $order = OrderBuilder::anOrder()->build();
        $result = $this->taxjarOrder->build($order);

        $this->assertEquals(10.0, $result['line_items'][0]['unit_price'], 'Invalid unit price');
    }

    public function testBuildTaxjarOrderTransactionWithoutShipping()
    {
        $order = OrderBuilder::anOrder()
            ->withProducts(ProductBuilder::aVirtualProduct())
            ->build();

        $result = $this->taxjarOrder->build($order);

        $this->assertEquals(0, $result['shipping'], 'Invalid shipping amount');
    }

    public function testBuildTaxjarOrderTransactionWithShipping()
    {
        $order = OrderBuilder::anOrder()->build();
        $order->setShippingAmount(20.0);
        InvoiceBuilder::forOrder($order)->build();

        $result = $this->taxjarOrder->build($order);

        $this->assertEquals(20.0, $result['shipping'], 'Invalid shipping amount');
    }

    public function testBuildTaxjarOrderTransactionNonUSIsNotSyncable()
    {
        $order = OrderBuilder::anOrder()
            ->withCustomer(
                CustomerBuilder::aCustomer()
                    ->withAddresses(
                        AddressBuilder::anAddress('en_AU')->asDefaultBilling()->asDefaultShipping()
                    )
            )
            ->build();

        $result = $this->taxjarOrder->isSyncable($order);

        $this->assertFalse($result, 'Non-US order sync');
    }

    public function testBuildTaxjarOrderTransactionRelatesToCustomer()
    {
        $order = OrderBuilder::anOrder()->build();
        $result = $this->taxjarOrder->build($order);

        $this->assertEquals($order->getCustomerId(), $result['customer_id'], 'Invalid customer ID');
    }

    public function testBuildTaxjarOrderTransactionWithoutProductTaxClass()
    {
        $order = OrderBuilder::anOrder()->build();
        $result = $this->taxjarOrder->build($order);

        $this->assertEquals('', $result['line_items'][0]['product_tax_code'], 'Invalid product tax class');
    }

    public function testBuildTaxjarOrderTransactionForGiftCard()
    {
        // Giftcard only exists in Commerce version
        $classGiftcardExists = class_exists('\Magento\GiftCard\Model\Catalog\Product\Type\Giftcard');

        if ($classGiftcardExists) {
            $order = OrderBuilder::anOrder()
                ->withProducts(
                    ProductBuilder::aGiftcardProduct()
                )
                ->build();

            $result = $this->taxjarOrder->build($order);

            $this->assertEquals('14111803A0001', $result['line_items'][0]['product_tax_code'], 'Invalid gift card');
        }

        // Smoke test for non-Commerce Magento versions
        $this->assertTrue(true);
    }

    // TODO: test building a TaxJar Transaction Order with an exempt product tax class
    // TODO: test building a TaxJar Transaction Order with an bundled products
}
