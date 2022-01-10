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

namespace Taxjar\SalesTax\Api\Data\Sales\Order;

use Exception;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata as MetadataResourceModel;

/**
 * @api
 */
interface MetadataInterface
{
    public const ID = 'entity_id';
    public const ORDER_ID = 'order_id';
    public const TAX_RESULT = 'tax_result';
    public const CREATED_AT = 'created_at';

    /**
     * Get metadata entry's id
     *
     * @return integer
     */
    public function getId();

    /**
     * Get order id
     *
     * @return integer
     */
    public function getOrderId();

    /**
     * Set order id
     *
     * @param integer $orderId
     *
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * Get tax result
     *
     * @return string
     */
    public function getTaxResult();

    /**
     * Set tax result
     *
     * @param string $taxResult
     *
     * @return $this
     */
    public function setTaxResult($taxResult);

    /**
     * Get created at
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Set created at
     *
     * @param string $createdAt
     *
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Save metadata
     *
     * @return $this
     * @throws Exception
     */
    public function save();

    /**
     * Delete metadata
     *
     * @return $this
     * @throws Exception
     */
    public function delete();

    /**
     * Load metadata
     *
     * @param integer     $modelId
     * @param null|string $field
     *
     * @return $this
     */
    public function load($modelId, $field = null);

    /**
     * Retrieve model resource
     *
     * @return MetadataResourceModel
     */
    public function getResource();
}
