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

namespace Taxjar\SalesTax\Controller\Adminhtml\Nexus;

class Sync extends \Taxjar\SalesTax\Controller\Adminhtml\Nexus
{
    /**
     * Sync nexus entities from TaxJar
     *
     * @return void
     */
    public function execute()
    {
        try {
            /** @var \Taxjar\SalesTax\Model\Tax\NexusSync $nexus */
            $nexus = $this->nexusSyncFactory->create();
            $nexus->syncCollection();
            $this->messageManager->addSuccessMessage(__('Your nexus addresses have been synced from TaxJar.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        $this->_redirect('*/*/');
    }
}
