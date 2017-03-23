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

namespace Taxjar\SalesTax\Model\Config\Taxclass\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

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

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param \Taxjar\SalesTax\Helper\Data $helper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Taxjar\SalesTax\Helper\Data $helper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $categories = json_decode($this->scopeConfig->getValue(TaxjarConfig::TAXJAR_CATEGORIES), true);
        $categories = $this->helper->sortArray($categories, 'product_tax_code', SORT_ASC);

        $output = [
            [
                'label' => 'None',
                'value' => ''
            ]
        ];

        foreach ($categories as $category) {
            $output[] = [
                'label' => $category['name'] . ' (' . $category['product_tax_code'] . ')',
                'value' => $category['product_tax_code']
            ];
        }

        return $output;
    }
}
