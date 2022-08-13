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

class Sync extends \Taxjar\SalesTax\Controller\Adminhtml\Transaction
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Taxjar\SalesTax\Model\Logger $logger
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Taxjar\SalesTax\Model\Logger $logger,
        \Magento\Framework\App\RequestInterface $request
    ) {
        parent::__construct($context, $logger);

        $this->request = $request;
    }

    /**
     * Sync transactions
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $responseContent = [
            'success' => false,
            'error_message' => '',
        ];

        try {
            $this->eventManager->dispatch('taxjar_salestax_sync_transaction', [
                'order_id' => $this->request->getParam('order_id'),
                'force' => true,
            ]);
            $responseContent['success'] = true;
        } catch (\Exception $e) {
            $responseContent['error_message'] = $e->getMessage();
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
        $resultJson->setData(['data' => $responseContent]);
        return $resultJson;
    }
}
