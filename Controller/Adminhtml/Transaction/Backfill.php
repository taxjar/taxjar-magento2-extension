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

namespace Taxjar\SalesTax\Controller\Adminhtml\Transaction;

use Magento\Framework\Controller\ResultFactory;

class Backfill extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_Tax::manage_tax';

    /**
     * Dispatch transaction backfill event.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        try {
            $this->_eventManager->dispatch('taxjar_salestax_backfill_transactions', [
                'start_date' => $this->_request->getParam('start_date'),
                'end_date' => $this->_request->getParam('end_date'),
                'force_sync' => $this->_request->getParam('force_sync'),
                'store_id' => $this->_request->getParam('store_id'),
                'website_id' => $this->_request->getParam('website_id')
            ]);

            $responseContent = [
                'success' => true,
                'error_message' => '',
                'result' => __('Successfully scheduled TaxJar transaction backfill.'),
            ];
        } catch (\Exception $e) {
            $responseContent = [
                'success' => false,
                'error_message' => $e->getMessage(),
            ];
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        return $resultJson->setData($responseContent);
    }
}
