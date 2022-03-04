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

namespace Taxjar\SalesTax\Model\Sales\Order;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Taxjar\SalesTax\Api\Data\Sales\Order\MetadataInterface;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata as MetadataResource;

/**
 * Model for storage of Sales Order's TaxJar tax calculation
 *
 * This model is used as the implementation for the tj_tax_result extension attribute on the
 * {@see \Magento\Sales\Api\Data\OrderInterface}
 */
class Metadata extends AbstractModel implements MetadataInterface
{
    const TAX_CALCULATION_STATUS_SUCCESS = 'success';

    const TAX_CALCULATION_STATUS_ERROR = 'error';

    const CACHE_TAG = 'taxjar_salestax_order_metadata';

    protected $_cacheTag = 'taxjar_salestax_order_metadata';

    protected $_eventPrefix = 'taxjar_salestax_order_metadata';

    protected function _construct()
    {
        $this->_init(MetadataResource::class);
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * {@inheritDoc}
     */
    public function setOrderId($orderId): self
    {
        $this->setData(self::ORDER_ID, $orderId);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getTaxCalculationStatus()
    {
        return $this->getData(self::TAX_CALCULATION_STATUS);
    }

    /**
     * {@inheritDoc}
     */
    public function setTaxCalculationStatus($taxCalculationStatus)
    {
        $this->setData(self::TAX_CALCULATION_STATUS, $taxCalculationStatus);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getTaxCalculationMessage()
    {
        return $this->getData(self::TAX_CALCULATION_MESSAGE);
    }

    /**
     * {@inheritDoc}
     */
    public function setTaxCalculationMessage($taxCalculationMessage)
    {
        $this->setData(self::TAX_CALCULATION_MESSAGE, $taxCalculationMessage);

        return $this;
    }
}
