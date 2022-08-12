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

use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Block\Adminhtml\Order\AbstractOrder;
use Taxjar\SalesTax\Model\Sales\Order\Metadata;

/**
 * Class Calculation
 *
 * This block is used to display order's tax calculation status.
 */
class Calculation extends AbstractOrder
{
    public const CALCULATION_SUCCESS = 'Tax was calculated in realtime through the TaxJar API.';

    public const CALCULATION_ERROR = 'TaxJar did not or was unable to perform a tax calculation on this order.';

    public const CALCULATION_NULL = 'No TaxJar calculation data is present on the order. This may indicate that
        TaxJar was not enabled when this order was placed or tax was calculated using a prior version of the
        TaxJar extension.';

    /**
     * @var string
     */
    protected $_template = 'Taxjar_SalesTax::order/view/tab/taxjar/info/calculation.phtml';

    /**
     * @var OrderExtensionFactory
     */
    private $extensionFactory;

    /**
     * @param OrderExtensionFactory $extensionFactory
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param array $data
     */
    public function __construct(
        OrderExtensionFactory $extensionFactory,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        array $data = []
    ) {
        $this->extensionFactory = $extensionFactory;
        parent::__construct(
            $context,
            $registry,
            $adminHelper,
            $data
        );
    }

    /**
     * Returns UI-friendly tax calculation status text.
     *
     * @param OrderInterface $order
     * @return string
     */
    public function getOrderCalculationStatus(OrderInterface $order): string
    {
        $extensionAttributes = $order->getExtensionAttributes() ?: $this->extensionFactory->create();
        return $this->getStatusText($extensionAttributes->getTjTaxCalculationStatus());
    }

    /**
     * Map calculation value to string constants.
     *
     * @param string|null $status
     * @return string
     */
    protected function getStatusText(?string $status): string
    {
        switch ($status) {
            case Metadata::TAX_CALCULATION_STATUS_SUCCESS:
                return self::CALCULATION_SUCCESS;
            case Metadata::TAX_CALCULATION_STATUS_ERROR:
                return self::CALCULATION_ERROR;
            default:
                return self::CALCULATION_NULL;
        }
    }

    /**
     * Returns any addition tax calculation result info.
     *
     * @param OrderInterface $order
     * @return string|null
     */
    public function getOrderCalculationMessage(OrderInterface $order): ?string
    {
        $extensionAttributes = $order->getExtensionAttributes() ?: $this->extensionFactory->create();
        return $extensionAttributes->getTjTaxCalculationMessage() ?? null;
    }
}
