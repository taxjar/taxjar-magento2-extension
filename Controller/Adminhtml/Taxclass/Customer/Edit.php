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

namespace Taxjar\SalesTax\Controller\Adminhtml\Taxclass\Customer;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Controller\ResultFactory;

class Edit extends \Taxjar\SalesTax\Controller\Adminhtml\Taxclass\Customer
{
    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $taxClassId = $this->getRequest()->getParam('class');
        $this->coreRegistry->register('tax_class_id', $taxClassId);
        /** @var \Magento\Backend\Model\Session $backendSession */
        $backendSession = $this->_objectManager->get('Magento\Backend\Model\Session');

        if ($taxClassId) {
            try {
                $taxClass = $this->taxClassService->get($taxClassId);
                $pageTitle = sprintf("%s", $taxClass->getClassName());
            } catch (NoSuchEntityException $e) {
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('taxjar/*/');
            }
        } else {
            $pageTitle = __('New Customer Tax Class');
        }
        $data = $backendSession->getRuleData(true);
        if (!empty($data)) {
            $this->coreRegistry->register('tax_class_form_data', $data);
        }
        $breadcrumb = $taxClassId ? __('Edit Customer Tax Class') : __('New Customer Tax Class');
        $resultPage = $this->initResultPage();
        $resultPage->addBreadcrumb($breadcrumb, $breadcrumb);
        $resultPage->getConfig()->getTitle()->prepend(__('Customer Tax Classes'));
        $resultPage->getConfig()->getTitle()->prepend($pageTitle);
        return $resultPage;
    }
}
