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
 * @copyright  Copyright (c) 2019 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Taxjar\SalesTax\Model\Sales\Order\Metadata;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata as MetadataResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected $_eventPrefix = 'taxjar_salestax_order_metadata_collection';

    protected $_eventObject = 'order_metadata_collection';

    protected function _construct()
    {
        $this->_init(Metadata::class, MetadataResourceModel::class);
    }
}
