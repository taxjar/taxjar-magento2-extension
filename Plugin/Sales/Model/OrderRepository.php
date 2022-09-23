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
 * @copyright  Copyright (c) 2022 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Plugin\Sales\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Taxjar\SalesTax\Api\Data\Sales\Order\MetadataInterface;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata\Collection;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata\CollectionFactory;

/**
 * Class OrderRepository
 *
 * Loads additional TaxJar Sales Order extension data
 */
class OrderRepository
{
    /**
     * @var CollectionFactory
     */
    private $collection;

    /**
     * Get constructor.
     *
     * @param CollectionFactory $collection
     */
    public function __construct(CollectionFactory $collection)
    {
        $this->collection = $collection;
    }

    /**
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @return OrderInterface
     */
    public function afterGet(OrderRepositoryInterface $subject, OrderInterface $order): OrderInterface
    {
        return $this->_setExtensionAttributeData($order);
    }

    /**
     * @param OrderRepositoryInterface $subject
     * @param OrderSearchResultInterface $searchResult
     * @return OrderSearchResultInterface
     */
    public function afterGetList(
        OrderRepositoryInterface $subject,
        OrderSearchResultInterface $searchResult
    ): OrderSearchResultInterface {
        foreach ($searchResult->getItems() as &$order) {
            $this->_setExtensionAttributeData($order);
        }
        return $searchResult;
    }

    /**
     * @param OrderInterface $order
     * @return OrderInterface
     */
    private function _setExtensionAttributeData(OrderInterface $order): OrderInterface
    {
        $metadata = $this->_getMetadata($order);
        $extensionAttributes = $order->getExtensionAttributes();
        $extensionAttributes->setTjSyncedAt($metadata->getSyncedAt());
        return $order->setExtensionAttributes($extensionAttributes);
    }

    /**
     * @param OrderInterface $order
     * @return MetadataInterface|false
     */
    private function _getMetadata(OrderInterface $order)
    {
        /** @var Collection|MetadataInterface[] $collection */
        $collection = $this->collection->create();
        $collection->addFieldToFilter(MetadataInterface::ORDER_ID, $order->getEntityId());
        $collection->setPageSize(1);
        $collection->setCurPage(1);
        return $collection->getFirstItem();
    }
}
