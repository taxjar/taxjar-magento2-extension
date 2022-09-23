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

namespace Taxjar\SalesTax\Api\Data\Sales\Order\Creditmemo;

use Exception;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Creditmemo\Metadata as MetadataResourceModel;

/**
 * @api
 */
interface MetadataInterface
{
    const ID = 'entity_id';

    const CREDITMEMO_ID = 'creditmemo_id';

    const SYNCED_AT = 'synced_at';

    /**
     * Get metadata entry's id
     *
     * @return integer
     */
    public function getId();

    /**
     * Get creditmemo id
     *
     * @return integer
     */
    public function getCreditmemoId();

    /**
     * Set creditmemo id
     *
     * @param integer $creditmemoId
     *
     * @return $this
     */
    public function setCreditmemoId($creditmemoId);

    /**
     * Get synced at date
     *
     * @return mixed
     */
    public function getSyncedAt();

    /**
     * Set synced at date
     *
     * @param string $syncedAt
     *
     * @return $this
     */
    public function setSyncedAt($syncedAt);

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
