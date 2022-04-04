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

namespace Taxjar\SalesTax\Plugin\Sales\Model\Order;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\Logger;

class ItemRepository
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var TaxClassRepositoryInterface
     */
    protected $taxClassRepository;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param TaxClassRepositoryInterface $taxClassRepository
     * @param Logger $logger
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        TaxClassRepositoryInterface $taxClassRepository,
        Logger $logger
    ) {
        $this->productRepository = $productRepository;
        $this->taxClassRepository = $taxClassRepository;
        $this->logger = $logger;
    }

    /**
     * Include the TaxJar product tax code when creating an order item
     *
     * @param \Magento\Sales\Model\Order\ItemRepository $subject
     * @param \Magento\Sales\Api\Data\OrderItemInterface $item
     */
    public function beforeSave(
        \Magento\Sales\Model\Order\ItemRepository $subject,
        \Magento\Sales\Api\Data\OrderItemInterface $item
    ) {
        // Only set the product tax code when first creating the OrderItem
        if ($item->getItemId() === null) {
            try {
                /** @var Product $product */
                $product = $this->productRepository->getById($item->getProductId());
                $productTaxCode = $this->getProductTaxCode($product);
                $item->setTjPtc($productTaxCode);
            } catch (NoSuchEntityException $e) {
                $msg = 'Product #' . $item->getProductId() . ' does not exist.  ';
                $msg .= 'Order #' . $item->getOrderId() . ' possibly missing PTCs on OrderItems.';
                $this->logger->log($msg, 'error');
            }
        }

        return null;
    }

    /**
     * Retrieve the appropriate TaxJar PTC for a given product.
     *
     * @param Product $product
     * @return mixed|string
     * @throws NoSuchEntityException
     */
    protected function getProductTaxCode($product)
    {
        if ($product->getTjPtc()) {
            return $product->getTjPtc();
        }

        if ($product->getTaxClassId()) {
            $taxClass = $this->taxClassRepository->get($product->getTaxClassId());
            if ($taxClass && $taxClass->getTjSalestaxCode()) {
                return $taxClass->getTjSalestaxCode();
            }
        }

        return TaxjarConfig::TAXJAR_TAXABLE_TAX_CODE;
    }
}
