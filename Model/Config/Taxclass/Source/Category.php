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
 * @copyright  Copyright (c) 2019 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Model\Config\Taxclass\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Category implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Taxjar\SalesTax\Helper\Data
     */
    protected $helper;

    /** @var \Taxjar\SalesTax\Model\ResourceModel\Tax\Category\Collection  */
    protected $categories;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param \Taxjar\SalesTax\Helper\Data $helper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Taxjar\SalesTax\Helper\Data $helper,
        \Taxjar\SalesTax\Model\ResourceModel\Tax\Category\Collection $categories
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->categories = $categories;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $this->categories->setOrder('name', 'ASC');

        $output = [
            [
                'label' => 'Fully Taxable',
                'value' => ''
            ]
        ];

        foreach ($this->categories as $category) {
            $output[] = [
                'label' => $category->getName() . ' (' . $category->getProductTaxCode() . ')',
                'value' => $category->getProductTaxCode()
            ];
        }

        return $output;
    }
}
