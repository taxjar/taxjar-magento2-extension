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
 * @copyright  Copyright (c) 2017 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace Taxjar\SalesTax\Model\Sales\Order\Creditmemo;

use Magento\Framework\Model\AbstractModel;
use Taxjar\SalesTax\Api\Data\Sales\Order\Creditmemo\MetadataInterface;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Creditmemo\Metadata as MetadataResource;

class Metadata extends AbstractModel implements MetadataInterface
{
    const CACHE_TAG = 'taxjar_salestax_creditmemo_metadata';

    /**
     * @var string
     */
    protected $_cacheTag = 'taxjar_salestax_creditmemo_metadata';

    /**
     * @var string
     */
    protected $_eventPrefix = 'taxjar_salestax_creditmemo_metadata';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(MetadataResource::class);
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * @inheritDoc
     */
    public function getCreditmemoId()
    {
        return $this->getData(self::CREDITMEMO_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCreditmemoId($creditmemoId): self
    {
        $this->setData(self::CREDITMEMO_ID, $creditmemoId);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSyncedAt()
    {
        return $this->getData(self::SYNCED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setSyncedAt($syncedAt): self
    {
        $this->setData(self::SYNCED_AT, $syncedAt);

        return $this;
    }
}
