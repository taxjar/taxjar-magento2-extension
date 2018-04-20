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

class Save extends \Taxjar\SalesTax\Controller\Adminhtml\Nexus
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
            $nexus = $this->populateNexus($postData);
            $region = $this->regionFactory->create()->load($nexus->getRegionId());
            $nexus->setRegion($region->getName());
            $nexus->setRegionCode($region->getCode());

            try {
                // if ($nexus->getCountryId() == 'US') {
                //     $nexusSync = $this->nexusSyncFactory->create(['data' => $nexus->getData()]);
                //     $nexusSync->sync();
                // }
                $nexus = $this->nexusService->save($nexus);

                $this->messageManager->addSuccessMessage(__('You saved the nexus address.'));

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('taxjar/*/edit', ['address' => $nexus->getId()]);
                }
                return $resultRedirect->setPath('taxjar/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('We can\'t save this nexus address right now.'));
            }

            $this->_objectManager->get('Magento\Backend\Model\Session')->setNexusData($postData);
            return $resultRedirect->setUrl($this->_redirect->getRedirectUrl($this->getUrl('*')));
        }
        return $resultRedirect->setPath('taxjar/nexus');
    }
}
