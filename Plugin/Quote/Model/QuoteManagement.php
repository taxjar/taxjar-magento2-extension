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
 * @copyright  Copyright (c) 2020 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Plugin\Quote\Model;

use Magento\Quote\Api\CartManagementInterface;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Taxjar\SalesTax\Api\Data\Sales\MetadataRepositoryInterface;
use Taxjar\SalesTax\Model\Sales\Order\Metadata;
use Taxjar\SalesTax\Model\Sales\Order\MetadataFactory;

/**
 * Class QuoteManagement
 *
 * Save additional TaxJar Sales Order extension data
 */
class QuoteManagement
{
    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var MetadataRepositoryInterface
     */
    private $metadataRepository;

    /**
     * QuoteManagement constructor.
     *
     * @param MetadataFactory $metadataFactory
     * @param MetadataRepositoryInterface $metadataRepository
     */
    public function __construct(
        MetadataFactory $metadataFactory,
        MetadataRepositoryInterface $metadataRepository
    ) {
        $this->metadata = $metadataFactory->create();
        $this->metadataRepository = $metadataRepository;
    }

    /**
     * Parse order extension data and persist metadata entity
     *
     * @param CartManagementInterface $subject
     * @param OrderInterface|null $order
     * @return OrderInterface|null
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function afterSubmit(
        CartManagementInterface $subject,
        ?OrderInterface $order
    ): ?OrderInterface {
        if ($order instanceof OrderInterface) {
            /** @var OrderExtensionInterface $extensionAttributes */
            $extensionAttributes = $order->getExtensionAttributes();
            if ($extensionAttributes) {
                if ($extensionAttributes->getTjTaxCalculationStatus()) {
                    $this->metadata->setOrderId($order->getEntityId());
                    $this->metadata->setTaxCalculationStatus($extensionAttributes->getTjTaxCalculationStatus());
                }
                if ($extensionAttributes->getTjTaxCalculationMessage()) {
                    $this->metadata->setOrderId($order->getEntityId());
                    $this->metadata->setTaxCalculationMessage($extensionAttributes->getTjTaxCalculationMessage());
                }
                if ($this->metadata->getOrderId() !== null) {
                    $this->metadataRepository->save($this->metadata);
                }
            }
        }
        return $order;
    }
}
