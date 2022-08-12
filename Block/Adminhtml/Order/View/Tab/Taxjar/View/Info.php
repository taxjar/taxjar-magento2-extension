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

namespace Taxjar\SalesTax\Block\Adminhtml\Order\View\Tab\Taxjar\View;

use Taxjar\SalesTax\Helper\Data as TaxjarHelper;

class Info extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var string
     */
    protected $_template = 'Taxjar_SalesTax::order/view/tab/taxjar/info.phtml';

    /**
     * @var TaxjarHelper
     */
    private $tjHelper;

    /**
     * @param TaxjarHelper $tjHelper
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        TaxjarHelper $tjHelper,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->tjHelper = $tjHelper;

        parent::__construct($context, $data);
    }

    /**
     * Return Sales Order view tab label
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getTabLabel()
    {
        return __('TaxJar Information');
    }

    /**
     * Return Sales Order view tab title
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getTabTitle()
    {
        return __('TaxJar Information');
    }

    /**
     * Validate tab should be present
     *
     * @return bool
     */
    public function canShowTab()
    {
        return $this->tjHelper->isEnabled() || $this->tjHelper->isTransactionSyncEnabled();
    }

    /**
     * Validate tab should be visible
     *
     * @return bool
     */
    public function isHidden()
    {
        return !$this->tjHelper->isEnabled() && !$this->tjHelper->isTransactionSyncEnabled();
    }
}
