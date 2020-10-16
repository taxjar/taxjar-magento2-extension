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

namespace Taxjar\SalesTax\Model\Attribute\Source;

class Category extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /** @var \Taxjar\SalesTax\Model\ResourceModel\Tax\Category\Collection */
    private $categories;

    /**
     * @param \Taxjar\SalesTax\Model\ResourceModel\Tax\Category\CollectionFactory $categoryFactory
     */
    public function __construct(
        \Taxjar\SalesTax\Model\ResourceModel\Tax\Category\CollectionFactory $categoryFactory
    ) {
        $this->categories = $categoryFactory;
        $this->_options = [
            [
                'label' => 'Fully Taxable',
                'value' => ''
            ]
        ];
    }

    /**
     * Get all options
     * @return array
     */
    public function getAllOptions()
    {
        /** @var \Taxjar\SalesTax\Model\ResourceModel\Tax\Category\Collection $categories */
        $categories = $this->categories->create();
        $categories->setOrder('name', 'ASC');

        /** @var Category $category */
        foreach ($categories as $category) {
            $this->_options[] = ['label' => __($category->getName()), 'value' => $category->getProductTaxCode()];
        }

        return $this->_options;
    }
}
