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
 * @copyright  Copyright (c) 2017 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc.
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Taxjar\SalesTax\Api\Data\Sales\Order\MetadataInterface;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata\CollectionFactory;

class OrderMetadata extends AbstractHelper
{
    /**
     * @var OrderExtensionFactory
     */
    private $extensionFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * OrderMetadata constructor.
     *
     * @param OrderExtensionFactory $extensionFactory
     * @param CollectionFactory $collectionFactory
     * @param Context $context
     */
    public function __construct(
        OrderExtensionFactory $extensionFactory,
        CollectionFactory $collectionFactory,
        Context $context
    ) {
        $this->extensionFactory = $extensionFactory;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    /**
     * @param OrderInterface $order
     * @return \Taxjar\SalesTax\Model\Sales\Order\Metadata|false
     */
    public function getOrderMetadata(OrderInterface $order)
    {
        $items = $this->collectionFactory
            ->create()
            ->addFieldToFilter(MetadataInterface::ORDER_ID, ['eq' => $order->getEntityId()])
            ->setPageSize(1)
            ->setCurPage(1)
            ->getItems();

        return current($items);
    }

    /**
     * @param OrderInterface $order
     * @return OrderInterface
     */
    public function setOrderExtensionAttributeData(OrderInterface $order): OrderInterface
    {
        $extensionAttributes = $order->getExtensionAttributes() ?: $this->extensionFactory->create();
        $orderMetadata = $this->getOrderMetadata($order);
        if ($orderMetadata) {
            $extensionAttributes->setTjTaxCalculationStatus($orderMetadata->getTaxCalculationStatus());
            $extensionAttributes->setTjTaxCalculationMessage($orderMetadata->getTaxCalculationMessage());
        }
        return $order->setExtensionAttributes($extensionAttributes);
    }
}
