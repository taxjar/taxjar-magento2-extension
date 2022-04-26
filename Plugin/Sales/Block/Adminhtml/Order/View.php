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
 * @copyright  Copyright (c) 2020 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace Taxjar\SalesTax\Plugin\Sales\Block\Adminhtml\Order;

class View
{
    /**
     * @var \Taxjar\SalesTax\Helper\Data
     */
    protected $_tjSalesTaxData;

    /**
     * @param \Taxjar\SalesTax\Helper\Data $tjSalesTaxData
     */
    public function __construct(\Taxjar\SalesTax\Helper\Data $tjSalesTaxData)
    {
        $this->_tjSalesTaxData = $tjSalesTaxData;
    }

    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\View $subject
     */
    public function beforeSetLayout(\Magento\Sales\Block\Adminhtml\Order\View $subject)
    {
        if ($this->shouldRender($subject->getOrder())) {
            $subject->addButton('taxjar_sync', [
                'label' => __('Sync to TaxJar'),
                'class' => 'taxjar-sync primary',
                'onclick' => 'syncTransaction(\'' . $subject->getOrderId() . '\')'
            ]);
        }
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return bool
     */
    protected function shouldRender(\Magento\Sales\Api\Data\OrderInterface $order): bool
    {
        return $this->_tjSalesTaxData->isTransactionSyncEnabled($order->getStoreId())
            && $this->_tjSalesTaxData->isSyncableOrder($order);
    }
}
