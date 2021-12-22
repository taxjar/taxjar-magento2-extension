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

use Magento\Shipping\Helper\Data as ShippingHelper;
use Magento\Tax\Helper\Data as TaxHelper;

class Sync extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    /**
     * Statuses of orders that may be synced to TaxJar
     */
    protected const SYNCABLE_STATES = [
        \Magento\Sales\Model\Order::STATE_COMPLETE,
        \Magento\Sales\Model\Order::STATE_CLOSED
    ];

    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'Taxjar_SalesTax::order/view/tab/taxjar/info/sync.phtml';

    /**
     * @var \Taxjar\SalesTax\Helper\Data
     */
    private $taxjarHelper;

    /**
     * @param \Taxjar\SalesTax\Helper\Data $taxjarHelper
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param array $data
     * @param ShippingHelper|null $shippingHelper
     * @param TaxHelper|null $taxHelper
     */
    public function __construct(
        \Taxjar\SalesTax\Helper\Data $taxjarHelper,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        array $data = [],
        ?ShippingHelper $shippingHelper = null,
        ?TaxHelper $taxHelper = null
    ) {
        $this->taxjarHelper = $taxjarHelper;
        parent::__construct(
            $context,
            $registry,
            $adminHelper,
            $data,
            $shippingHelper,
            $taxHelper
        );
    }

    /**
     * @return bool
     */
    public function featureEnabled()
    {
        return $this->taxjarHelper->isTransactionSyncEnabled();
    }

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

    /**
     * Get alternate text for non-synced sales orders
     *
     * @param string $state
     * @return \Magento\Framework\Phrase
     */
    public function getOrderStateText($state)
    {
        if (in_array($state, static::SYNCABLE_STATES)) {
            return __('This order has not been synced to TaxJar. You can manually sync this order.');
        }

        return __(
            'Current order state of \'%1\' cannot be synced to TaxJar. ' .
            'This order will automatically sync if Transaction Sync is ' .
            'enabled when it transitions to one of the following states: %2.',
            $state,
            implode(
                ', ',
                array_map([$this, 'enquote'], static::SYNCABLE_STATES)
            )
        );
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getFeatureDisabledText()
    {
        return __('Transaction Sync is disabled. Visit "Stores > Configuration > Sales > Tax > TaxJar" to enable.');
    }

    /**
     * @param $string
     * @return string
     */
    private function enquote($string)
    {
        return "'$string'";
    }
}
