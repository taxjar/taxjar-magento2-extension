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

namespace Taxjar\SalesTax\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Taxjar\SalesTax\Model\Transaction\Backfill;

class BackfillTransactions implements ObserverInterface
{
    /**
     * @var \Taxjar\SalesTax\Model\Transaction\Backfill
     */
    protected $backfill;

    /**
     * @param Backfill $backfill
     */
    public function __construct(
        Backfill $backfill
    ) {
        $this->backfill = $backfill;
    }

    /**
     * @param  Observer $observer
     * @return $this
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    // @codingStandardsIgnoreStart
    public function execute(Observer $observer)
    {
        $this->backfill->start();
        return $this;
    }
}
