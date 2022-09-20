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

namespace Taxjar\SalesTax\Api\Data;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * @api
 */
interface TransactionManagementInterface
{
    /**
     * Sync transaction to TaxJar.
     *
     * @param CreditmemoInterface|OrderInterface $transaction
     * @param bool $force
     *
     * @return bool
     * @throws LocalizedException
     */
    public function sync(CreditmemoInterface|OrderInterface $transaction, bool $force = false): bool;
}
