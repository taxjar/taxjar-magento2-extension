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

use Magento\Framework\Controller\ResultFactory;

class Delete extends \Taxjar\SalesTax\Controller\Adminhtml\Nexus
{
    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $addressId = (int)$this->getRequest()->getParam('address');
        try {
            $nexus = $this->nexusSyncFactory->create()->load($addressId);
            // if ($nexus->getCountryId() == 'US') {
            //     $nexus->syncDelete();
            // }
            $this->nexusService->deleteById($addressId);
            $this->messageManager->addSuccessMessage(__('The nexus address has been deleted.'));
            return $resultRedirect->setPath('taxjar/*/');
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This nexus address no longer exists.'));
            return $resultRedirect->setPath('taxjar/*/');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Something went wrong deleting this nexus address.'));
        }

        return $resultRedirect->setUrl($this->_redirect->getRedirectUrl($this->getUrl('*')));
    }
}
