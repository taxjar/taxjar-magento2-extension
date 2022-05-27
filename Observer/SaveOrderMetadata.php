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

namespace Taxjar\SalesTax\Observer;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterfaceFactory;
use Taxjar\SalesTax\Api\Data\Sales\Order\MetadataInterface;

/**
 * Class SaveOrderMetadata
 *
 * Transfers stored tax calculation metadata from quote to order
 */
class SaveOrderMetadata implements ObserverInterface
{
    const ORDER_METADATA = 'taxjar_salestax_order_metadata';

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderExtensionFactory
     */
    private $extensionFactory;

    /**
     * SaveOrderMetadata constructor.
     *
     * @param CheckoutSession $checkoutSession
     * @param OrderRepositoryInterfaceFactory $orderRepositoryFactory
     * @param OrderExtensionFactory $extensionFactory
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        OrderRepositoryInterfaceFactory $orderRepositoryFactory,
        OrderExtensionFactory $extensionFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepositoryFactory->create();
        $this->extensionFactory = $extensionFactory;
    }

    /**
     * Retrieve tax calculation result from checkout session and update order
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $encodedMetadata = $this->checkoutSession->getData(self::ORDER_METADATA);

        if ($encodedMetadata) {
            $metadata = json_decode($encodedMetadata, true);

            /** @var OrderInterface $order */
            $order = $observer->getOrder();
            $extensionAttributes = $order->getExtensionAttributes() ?? $this->extensionFactory->create();

            if (isset($metadata[MetadataInterface::TAX_CALCULATION_STATUS])) {
                $extensionAttributes->setTjTaxCalculationStatus($metadata[MetadataInterface::TAX_CALCULATION_STATUS]);
            }

            if (isset($metadata[MetadataInterface::TAX_CALCULATION_MESSAGE])) {
                $extensionAttributes->setTjTaxCalculationMessage($metadata[MetadataInterface::TAX_CALCULATION_MESSAGE]);
            }

            $order->setExtensionAttributes($extensionAttributes);

            $this->orderRepository->save($order);
        }
    }
}
