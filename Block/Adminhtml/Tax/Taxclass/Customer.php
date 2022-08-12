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

namespace Taxjar\SalesTax\Block\Adminhtml\Tax\Taxclass;

class Customer extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * @inheritDoc
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'taxjar_taxclass_customer';
        $this->_headerText = __('Manage Customer Tax Classes');
        $this->_addButtonLabel = __('Add New Customer Tax Class');
        parent::_construct();
    }
}
