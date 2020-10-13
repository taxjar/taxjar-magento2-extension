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

namespace Taxjar\SalesTax\Plugin\Tax\Model\Sales\Total\Quote;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory;

/**
 * Plugin for CommonTaxCollector to apply Tax Class ID from child item for configurable product
 */
class TaxjarCommonTaxCollector
{
    /**
     * Apply Tax Class ID from child item for configurable product
     *
     * @param \Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector $subject
     * @param QuoteDetailsItemInterface $result
     * @param QuoteDetailsItemInterfaceFactory $itemDataObjectFactory
     * @param AbstractItem $item
     * @return QuoteDetailsItemInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterMapItem(
        \Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector $subject,
        QuoteDetailsItemInterface $result,
        QuoteDetailsItemInterfaceFactory $itemDataObjectFactory,
        AbstractItem $item
    ) {
        if ($item->getProduct()->getTypeId() === Configurable::TYPE_CODE) {
            $taxClass = (int) $result->getTaxClassKey()->getValue();

            // If a configurable product has no tax class, attempt to load it from the product
            // This allows a simple product to inherit the tax class of it's configurable parent
            // This tax class is used when calculating tax rates in SmartCalcs
            if ($taxClass === 0) {
                $result->getTaxClassKey()->setValue($item->getProduct()->getTaxClassId());
            }
        }

        return $result;
    }
}
