<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Fixture\Sales;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceItemCreationInterfaceFactory;
use Magento\Sales\Api\InvoiceOrderInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Builder to be used by fixtures
 */
class InvoiceBuilder
{
    /**
     * @var InvoiceItemCreationInterfaceFactory
     */
    private $itemFactory;

    /**
     * @var InvoiceOrderInterface
     */
    private $invoiceOrder;

    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @var Order
     */
    private $order;

    /**
     * @var int[]
     */
    private $orderItems;

    public function __construct(
        InvoiceItemCreationInterfaceFactory $itemFactory,
        InvoiceOrderInterface $invoiceOrder,
        InvoiceRepositoryInterface $invoiceRepository,
        Order $order
    ) {
        $this->itemFactory = $itemFactory;
        $this->invoiceOrder = $invoiceOrder;
        $this->invoiceRepository = $invoiceRepository;
        $this->order = $order;

        $this->orderItems = [];
    }

    public static function forOrder(
        Order $order
    ): InvoiceBuilder {
        $objectManager = Bootstrap::getObjectManager();

        return new static(
            $objectManager->create(InvoiceItemCreationInterfaceFactory::class),
            $objectManager->create(InvoiceOrderInterface::class),
            $objectManager->create(InvoiceRepositoryInterface::class),
            $order
        );
    }

    public function withItem(int $orderItemId, int $qty): InvoiceBuilder
    {
        $builder = clone $this;

        $builder->orderItems[$orderItemId] = $qty;

        return $builder;
    }

    public function build(): InvoiceInterface
    {
        $invoiceItems = $this->buildInvoiceItems();

        $invoiceId = $this->invoiceOrder->execute($this->order->getEntityId(), false, $invoiceItems);

        return $this->invoiceRepository->get($invoiceId);
    }

    /**
     * @return \Magento\Sales\Api\Data\InvoiceItemCreationInterface[]
     */
    private function buildInvoiceItems(): array
    {
        $invoiceItems = [];

        foreach ($this->orderItems as $orderItemId => $qty) {
            $invoiceItem = $this->itemFactory->create();
            $invoiceItem->setOrderItemId($orderItemId);
            $invoiceItem->setQty($qty);
            $invoiceItems[] = $invoiceItem;
        }
        return $invoiceItems;
    }
}
