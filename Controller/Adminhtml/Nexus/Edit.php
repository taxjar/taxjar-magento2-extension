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

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Controller\ResultFactory;

class Edit extends \Taxjar\SalesTax\Controller\Adminhtml\Nexus
{
    /**
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $addressId = (int)$this->getRequest()->getParam('address');
        $this->coreRegistry->register('nexus_address_id', $addressId);
        /** @var \Magento\Backend\Model\Session $backendSession */
        $backendSession = $this->_objectManager->get('Magento\Backend\Model\Session');

        if ($addressId) {
            try {
                $nexus = $this->nexusService->get($addressId);
                $nexusCountry = $this->countryFactory->create()->load($nexus->getCountryId());
                $nexusTitle = $nexus->getRegion() ? $nexus->getRegion() : $nexusCountry->getName();
                $pageTitle = sprintf("%s", $nexusTitle);
            } catch (NoSuchEntityException $e) {
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath("taxjar/*/");
            }
        } else {
            $pageTitle = __('New Nexus Address');
        }
        $data = $backendSession->getNexusData(true);
        if (!empty($data)) {
            $this->coreRegistry->register('nexus_form_data', $data);
        }
        $breadcrumb = $addressId ? __('Edit Nexus Address') : __('New Nexus Address');
        $resultPage = $this->initResultPage();
        $resultPage->addBreadcrumb($breadcrumb, $breadcrumb);
        $resultPage->getConfig()->getTitle()->prepend(__('Nexus Addresses'));
        $resultPage->getConfig()->getTitle()->prepend($pageTitle);
        return $resultPage;
    }
}
