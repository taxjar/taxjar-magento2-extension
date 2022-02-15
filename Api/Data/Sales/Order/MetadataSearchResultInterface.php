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

use Magento\Framework\Api\SearchResultsInterface;

/**
 * @api
 */
interface MetadataSearchResultInterface extends SearchResultsInterface
{
    /**
     * @return \Taxjar\SalesTax\Api\Data\Sales\Order\MetadataInterface[]
     */
    public function getItems();

    /**
     * @param \Taxjar\SalesTax\Api\Data\Sales\Order\MetadataInterface[] $items
     * @return void
     */
    public function setItems(array $items);
}
