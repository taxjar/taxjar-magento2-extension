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

use Magento\Framework\Controller\ResultFactory;

class Save extends \Taxjar\SalesTax\Controller\Adminhtml\Taxclass\Customer
{
    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $postData = $this->getRequest()->getPostValue();
        if ($postData) {
            $taxClass = $this->populateTaxClass($postData);
            try {
                $taxClass = $this->taxClassService->save($taxClass);

                $this->messageManager->addSuccessMessage(__('You saved the customer tax class.'));

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('taxjar/*/edit', ['class' => $taxClass->getId()]);
                }
                return $resultRedirect->setPath('taxjar/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('We can\'t save this customer tax class right now.'));
            }

            $this->_objectManager->get('Magento\Backend\Model\Session')->setClassData($postData);
            return $resultRedirect->setUrl($this->_redirect->getRedirectUrl($this->getUrl('*')));
        }
        return $resultRedirect->setPath('taxjar/taxclass_save');
    }
}
