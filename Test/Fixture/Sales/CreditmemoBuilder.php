<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Test\Fixture\Sales;

use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoItemCreationInterfaceFactory;
use Magento\Sales\Api\RefundOrderInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Builder to be used by fixtures
 */
class CreditmemoBuilder
{
    /**
     * @var CreditmemoItemCreationInterfaceFactory
     */
    private $itemFactory;

    /**
     * @var RefundOrderInterface
     */
    private $refundOrder;

    /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    /**
     * @var Order
     */
    private $order;

    /**
     * @var float[]
     */
    private $orderItems;

    public function __construct(
        CreditmemoItemCreationInterfaceFactory $itemFactory,
        RefundOrderInterface $refundOrder,
        CreditmemoRepositoryInterface $creditmemoRepository,
        Order $order
    ) {
        $this->itemFactory = $itemFactory;
        $this->refundOrder = $refundOrder;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->order = $order;

        $this->orderItems = [];
    }

    public static function forOrder(
        Order $order
    ): CreditmemoBuilder {
        $objectManager = Bootstrap::getObjectManager();

        return new static(
            $objectManager->create(CreditmemoItemCreationInterfaceFactory::class),
            $objectManager->create(RefundOrderInterface::class),
            $objectManager->create(CreditmemoRepositoryInterface::class),
            $order
        );
    }

    public function withItem(int $orderItemId, int $qty): CreditmemoBuilder
    {
        $builder = clone $this;

        $builder->orderItems[$orderItemId] = $qty;

        return $builder;
    }

    public function build(): CreditmemoInterface
    {
        // order must be invoiced before a refund can be created.
        if ($this->order->canInvoice()) {
            InvoiceBuilder::forOrder($this->order)->build();
        }

        // refund items must be explicitly set
        if (empty($this->orderItems)) {
            foreach ($this->order->getItems() as $item) {
                $this->orderItems[$item->getItemId()] = (float)$item->getQtyOrdered();
            }
        }

        $creditmemoItems = $this->buildCreditmemoItems();

        $creditmemoId = $this->refundOrder->execute($this->order->getEntityId(), $creditmemoItems);

        return $this->creditmemoRepository->get($creditmemoId);
    }

    /**
     * @return \Magento\Sales\Api\Data\CreditmemoItemCreationInterface[]
     */
    private function buildCreditmemoItems(): array
    {
        $creditmemoItems = [];
        foreach ($this->orderItems as $orderItemId => $qty) {
            $creditmemoItem = $this->itemFactory->create();
            $creditmemoItem->setOrderItemId($orderItemId);
            $creditmemoItem->setQty($qty);
            $creditmemoItems[] = $creditmemoItem;
        }
        return $creditmemoItems;
    }
}
