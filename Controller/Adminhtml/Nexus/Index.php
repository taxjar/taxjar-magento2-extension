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

namespace Taxjar\SalesTax\Controller\Adminhtml\Nexus;

class Index extends \Taxjar\SalesTax\Controller\Adminhtml\Nexus
{
    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->initResultPage();
        $resultPage->addBreadcrumb(__('Manage Nexus Addresses'), __('Manage Nexus Addresses'));
        $resultPage->getConfig()->getTitle()->prepend(__('Nexus Addresses'));

//        $this->_reviewNexusAddresses();

        return $resultPage;
    }
}
