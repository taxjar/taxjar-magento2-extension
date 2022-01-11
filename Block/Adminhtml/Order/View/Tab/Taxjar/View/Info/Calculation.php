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

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Block\Adminhtml\Order\AbstractOrder;
use Magento\Sales\Helper\Admin as AdminHelper;
use Magento\Shipping\Helper\Data as ShippingHelper;
use Magento\Tax\Helper\Data as TaxHelper;
use Taxjar\SalesTax\Helper\Data as DataHelper;

/**
 * Class Calculation
 *
 * This block is used to display order tax calculation data from tax_result value in
 * {@see \Taxjar\SalesTax\Model\OrderMetadata}
 */
class Calculation extends AbstractOrder
{
    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'Taxjar_SalesTax::order/view/tab/taxjar/info/calculation.phtml';

    /**
     * @var OrderExtensionFactory $extensionFactory
     */
    private OrderExtensionFactory $extensionFactory;

    /**
     * @var DataHelper
     */
    private DataHelper $taxjarHelper;

    /**
     * Calculation constructor.
     *
     * @param OrderExtensionFactory $extensionFactory
     * @param DataHelper $taxjarHelper
     * @param Context $context
     * @param Registry $registry
     * @param AdminHelper $adminHelper
     * @param array $data
     * @param ShippingHelper|null $shippingHelper
     * @param TaxHelper|null $taxHelper
     */
    public function __construct(
        OrderExtensionFactory $extensionFactory,
        DataHelper $taxjarHelper,
        Context $context,
        Registry $registry,
        AdminHelper $adminHelper,
        array $data = [],
        ?ShippingHelper $shippingHelper = null,
        ?TaxHelper $taxHelper = null
    ) {
        $this->extensionFactory = $extensionFactory;
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

    public function getOrderCalculationStatus($order): string
    {
        $extensionAttributes = $order->getExtensionAttributes();
        if ($extensionAttributes && $extensionAttributes->getTjTaxResult()) {
            return 'Tax was calculated for this order using the TaxJar API.';
        }

        return 'Tax was NOT calculated for this order using the TaxJar API.';
    }

    /**
     * @param OrderInterface $order
     * @return mixed
     */
    public function getOrderTaxResult(OrderInterface $order)
    {
        $extensionAttributes = $order->getExtensionAttributes();
        if ($extensionAttributes && $extensionAttributes->getTjTaxResult()) {
            return (object)$extensionAttributes->getTjTaxResult();
        }

        return 'No result found!';
    }
}
