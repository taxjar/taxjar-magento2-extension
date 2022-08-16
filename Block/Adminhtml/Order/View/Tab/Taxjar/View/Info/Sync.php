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

namespace Taxjar\SalesTax\Block\Adminhtml\Order\View\Tab\Taxjar\View\Info;

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
     */
    public function __construct(
        \Taxjar\SalesTax\Helper\Data $taxjarHelper,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        array $data = []
    ) {
        $this->taxjarHelper = $taxjarHelper;
        parent::__construct(
            $context,
            $registry,
            $adminHelper,
            $data
        );
    }

    /**
     * Validate transaction sync is enabled
     *
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
     * Get status text for sales orders
     *
     * @param mixed $order
     * @return \Magento\Framework\Phrase
     */
    public function getOrderStateText($order)
    {
        $state = $order->getState();

        if (in_array($state, static::SYNCABLE_STATES)) {
            return __('This order has not been synced to TaxJar.');
        }

        if ($state && is_string($state)) {
            return __('Current order state of %1 cannot be synced to TaxJar.', $this->insertPre($state));
        }

        return __('Unable to determine order state. Cannot sync order to TaxJar.');
    }

    /**
     * Get actionable text for sales orders
     *
     * @param mixed $order
     * @return \Magento\Framework\Phrase
     */
    public function getOrderActionableText($order)
    {
        if (!$this->taxjarHelper->isTransactionSyncEnabled($order->getStoreId())) {
            return __('Transaction sync is disabled for the current store.');
        }

        if (in_array($order->getState(), static::SYNCABLE_STATES)) {
            return __('You can manually sync this order.');
        }

        return __(
            'Orders will automatically sync to TaxJar after transitioning to one of the following states: %1.',
            implode(', ', array_map([$this, 'insertPre'], static::SYNCABLE_STATES))
        );
    }

    /**
     * Get user-friendly disabled text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getFeatureDisabledText()
    {
        return __(
            'This feature is currently disabled. Visit %1 to enable Transaction Sync for automated sales tax
            filing and remittance.',
            $this->insertPre('Stores > Configuration > Sales > Tax > TaxJar')
        );
    }

    /**
     * Wrap content in `<pre>` element
     *
     * @param string $htmlContent
     * @return string
     */
    protected function insertPre(string $htmlContent)
    {
        return $this->insertTag('pre', $htmlContent);
    }

    /**
     * Wrap content in specified HTML tag
     *
     * @param string $tagName
     * @param string $htmlContent
     * @return string
     */
    private function insertTag(string $tagName, string $htmlContent)
    {
        return "<$tagName>$htmlContent</$tagName>";
    }
}
