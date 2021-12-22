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

namespace Taxjar\SalesTax\Block\Adminhtml\Order\View\Tab\Taxjar\View\Info;

class Sync extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'Taxjar_SalesTax::order/view/tab/taxjar/info/sync.phtml';

    /**
     * Get order synced at date
     *
     * @param int $syncedAt
     * @return \DateTime
     */
    public function getOrderSyncedAtDate($syncedAt)
    {
        return $this->_localeDate->date(new \DateTime($syncedAt));
    }
}
