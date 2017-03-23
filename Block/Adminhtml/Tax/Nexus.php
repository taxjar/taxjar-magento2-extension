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

/**
 * Admin nexus content block
 */
namespace Taxjar\SalesTax\Block\Adminhtml\Tax;

class Nexus extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'taxjar_nexus';
        $this->_headerText = __('Nexus Addresses');
        $this->_addButtonLabel = __('Add New Nexus Address');

        parent::_construct();

        $this->buttonList->add(
            'sync',
            [
                'label' => __('Sync from TaxJar'),
                'onclick' => 'window.location.href=\'' . $this->getUrl('taxjar/nexus/sync') . '\'',
                'class' => 'add primary sync-nexus'
            ],
            0,
            -1
        );
    }
}
