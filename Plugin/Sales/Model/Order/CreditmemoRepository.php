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

namespace Taxjar\SalesTax\Plugin\Sales\Model\Order;

use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoSearchResultInterface;
use Taxjar\SalesTax\Api\Data\Sales\Order\Creditmemo\MetadataInterface;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Creditmemo\Metadata\Collection;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Creditmemo\Metadata\CollectionFactory;

/**
 * Class Creditmemo repository.
 *
 * Loads additional TaxJar Sales Creditmemo extension data
 */
class CreditmemoRepository
{
    /**
     * @var CollectionFactory
     */
    private $collection;

    /**
     * @param CollectionFactory $collection
     */
    public function __construct(CollectionFactory $collection)
    {
        $this->collection = $collection;
    }

    /**
     * @param CreditmemoRepositoryInterface $subject
     * @param CreditmemoInterface $creditmemo
     * @return CreditmemoInterface
     */
    public function afterGet(
        CreditmemoRepositoryInterface $subject,
        CreditmemoInterface $creditmemo
    ): CreditmemoInterface {
        return $this->_setExtensionAttributeData($creditmemo);
    }

    /**
     * @param CreditmemoRepositoryInterface $subject
     * @param CreditmemoSearchResultInterface $searchResult
     * @return CreditmemoSearchResultInterface
     */
    public function afterGetList(
        CreditmemoRepositoryInterface $subject,
        CreditmemoSearchResultInterface $searchResult
    ): CreditmemoSearchResultInterface {
        foreach ($searchResult->getItems() as &$creditmemo) {
            $this->_setExtensionAttributeData($creditmemo);
        }
        return $searchResult;
    }

    /**
     * @param CreditmemoInterface $creditmemo
     * @return CreditmemoInterface
     */
    private function _setExtensionAttributeData(CreditmemoInterface $creditmemo): CreditmemoInterface
    {
        $metadata = $this->_getMetadata($creditmemo);
        $extensionAttributes = $creditmemo->getExtensionAttributes();
        $extensionAttributes->setTjSyncedAt($metadata->getSyncedAt());
        return $creditmemo->setExtensionAttributes($extensionAttributes);
    }

    /**
     * @param CreditmemoInterface $creditmemo
     * @return MetadataInterface|false
     */
    private function _getMetadata(CreditmemoInterface $creditmemo)
    {
        /** @var Collection|MetadataInterface[] $collection */
        $collection = $this->collection->create();
        $collection->addFieldToFilter(MetadataInterface::CREDITMEMO_ID, $creditmemo->getEntityId());
        $collection->setPageSize(1);
        $collection->setCurPage(1);
        return $collection->getFirstItem();
    }
}
