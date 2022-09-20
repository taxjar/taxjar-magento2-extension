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

namespace Taxjar\SalesTax\Model\Transaction;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Creditmemo\Item as CreditmemoItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

abstract class LineItem
{
    public const PRODUCT_TAX_CODE_TAXABLE = '';

    /**
     * @var ProductRepository
     */
    private ProductRepository $productRepository;

    /**
     * @var TaxClassRepositoryInterface
     */
    private $taxClassRepository;

    /**
     * @var OrderItem|CreditmemoItem
     */
    protected $item;

    /**
     * Line item constructor.
     *
     * @param ProductRepository $productRepository
     * @param TaxClassRepositoryInterface $taxClassRepository
     * @param OrderItem|CreditmemoItem $item
     */
    public function __construct(
        ProductRepository $productRepository,
        TaxClassRepositoryInterface $taxClassRepository,
        mixed $item
    ) {
        $this->productRepository = $productRepository;
        $this->taxClassRepository = $taxClassRepository;
        $this->item = $item;
    }

    /**
     * Get product tax code for order or creditmemo item.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getProductTaxCode(): string
    {
        return $this->getTaxCodeFromItem() ??
               $this->getTaxCodeFromProduct() ??
               $this->getTaxCodeFromTaxClass() ??
               self::PRODUCT_TAX_CODE_TAXABLE;
    }

    /**
     * Retrieve TaxJar product tax code from item (or child item for configurable products).
     *
     * @return string|null
     */
    private function getTaxCodeFromItem(): ?string
    {
        if ($this->_isConfigurableProduct() && $this->item->getHasChildren()) {
            $item = $this->_getConfigurableChild();
        } else {
            $item = $this->item;
        }

        return ($item->getTjPtc() != TaxjarConfig::TAXJAR_TAXABLE_TAX_CODE) ? $item->getTjPtc() : null;
    }

    /**
     * Retrieve TaxJar product tax code configured at product-level.
     *
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getTaxCodeFromProduct(): ?string
    {
        return $this->_getProduct()->getTjPtc();
    }

    /**
     * Retrieve TaxJar tax code configured at tax-class-level.
     *
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getTaxCodeFromTaxClass()
    {
        return $this->taxClassRepository->get($this->_getTaxClassId())?->getTjSalestaxCode() ?: '';
    }

    /**
     * Return item's product tax class as configured on product.
     *
     * @return string|null
     * @throws NoSuchEntityException
     */
    private function _getTaxClassId(): ?string
    {
        $product = $this->_isConfigurableProduct() ? $this->_getConfigurableChild() : $this->_getProduct();
        return $product?->getTaxClassId();
    }

    /**
     * Return first child item of configurable product.
     *
     * @return ProductInterface|null
     */
    private function _getConfigurableChild(): ?ProductInterface
    {
        if ($this->_isConfigurableProduct()) {
            $children = $this->item->getChildrenItems();
            if (!empty($children) && is_array($children)) {
                return reset($children);
            }
        }

        return null;
    }

    /**
     * Return if item's related product type is configurable.
     *
     * @return bool
     */
    private function _isConfigurableProduct(): bool
    {
        return $this->item->getProductType() == 'configurable';
    }

    /**
     * Return item's product.
     *
     * @return ProductInterface|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function _getProduct(): ?ProductInterface
    {
        return $this->productRepository->getById(
            $this->item->getProductId(),
            false,
            $this->_getOrder()->getStoreId()
        );
    }

    /**
     * Retrieve order from line item.
     *
     * @return OrderInterface|null
     */
    private function _getOrder(): ?OrderInterface
    {
        if ($this->item instanceof OrderItemInterface) {
            return $this->item->getOrder();
        } elseif ($this->item instanceof CreditmemoItemInterface) {
            return $this->item->getCreditmemo()->getOrder();
        } else {
            return null;
        }
    }
}
