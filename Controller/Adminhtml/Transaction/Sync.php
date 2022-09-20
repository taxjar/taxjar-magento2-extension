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

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Taxjar\SalesTax\Api\Data\TransactionManagementInterface;

class Sync extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_Tax::manage_tax';

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private \Magento\Sales\Api\OrderRepositoryInterface $orderRepository;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory;

    /**
     * @var \Taxjar\SalesTax\Api\Data\TransactionManagementInterface
     */
    private \Taxjar\SalesTax\Api\Data\TransactionManagementInterface $transactionService;

    /**
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param JsonFactory $resultJsonFactory
     * @param TransactionManagementInterface $transactionService
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        TransactionManagementInterface $transactionService
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->transactionService = $transactionService;
    }

    /**
     * Sync transaction.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $responseContent = ['success' => false, 'error_message' => ''];

        try {
            $orderId = $this->getRequest()->getParam('order_id');
            $order = $this->orderRepository->get($orderId);
            $responseContent['success'] = $this->_syncOrderAndCreditmemos($order);
        } catch (\Exception $e) {
            $responseContent['error_message'] = $e->getMessage();
        }

        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData(['data' => $responseContent]);
    }

    /**
     * Sync order and any related credit memos to TaxJar.
     *
     * @param OrderInterface $order
     *
     * @return bool
     * @throws LocalizedException
     */
    private function _syncOrderAndCreditmemos(OrderInterface $order): bool
    {
        if ($this->transactionService->sync($order, true)) {
            $creditmemos = $order->getCreditmemosCollection();
            if ($creditmemos->getTotalCount() > 0) {
                $results = [];
                /** @var Order\Creditmemo $creditmemo */
                foreach ($creditmemos->getItems() as $creditmemo) {
                    $results[] = $this->transactionService->sync($creditmemo, true);
                }
                return array_sum($results) === count($results);
            }
            return true;
        }
        return false;
    }
}
