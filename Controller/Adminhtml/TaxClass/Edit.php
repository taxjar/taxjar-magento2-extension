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
 * @copyright  Copyright (c) 2016 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Controller\Adminhtml\TaxClass;

use Magento\Framework\Controller\ResultFactory;

class Edit extends \Taxjar\SalesTax\Controller\Adminhtml\TaxClass
{
    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $taxClassId = $this->getRequest()->getParam('class');
        $this->_coreRegistry->register('tax_class_id', $taxClassId);
        /** @var \Magento\Backend\Model\Session $backendSession */
        $backendSession = $this->_objectManager->get('Magento\Backend\Model\Session');
        if ($taxClassId) {
            try {
                $taxClass = $this->taxClassService->get($taxClassId);
                $pageTitle = sprintf("%s", $taxClass->getClassName());
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $backendSession->unsClassData();
                $this->messageManager->addError(__('This tax class no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('taxjar/*/');
            }
        } else {
            $pageTitle = __('New Product Tax Class');
        }
        $data = $backendSession->getRuleData(true);
        if (!empty($data)) {
            $this->_coreRegistry->register('tax_class_form_data', $data);
        }
        $breadcrumb = $taxClassId ? __('Edit Product Tax Class') : __('New Product Tax Class');
        $resultPage = $this->initResultPage();
        $layout = $resultPage->getLayout();
        $toolbarSaveBlock = $layout->createBlock('Taxjar\SalesTax\Block\Adminhtml\Tax\TaxClass\Toolbar\Save')
            ->assign('header', __('Add New Product Tax Class'))
            ->assign('form', $layout->createBlock('Taxjar\SalesTax\Block\Adminhtml\Tax\TaxClass\Form', 'tax_class_form'));
        $resultPage->addBreadcrumb($breadcrumb, $breadcrumb)->addContent($toolbarSaveBlock);
        $resultPage->getConfig()->getTitle()->prepend(__('Product Tax Classes'));
        $resultPage->getConfig()->getTitle()->prepend($pageTitle);
        return $resultPage;
    }
}