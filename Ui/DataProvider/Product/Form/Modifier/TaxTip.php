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

namespace Taxjar\SalesTax\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;

class TaxTip extends AbstractModifier
{
    /**
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        if (isset($meta['product-details']['children']['container_tax_class_id']['children']['tax_class_id'])) {
            $url = 'https://www.taxjar.com/guides/integrations/magento2/#product-sales-tax-exemptions';
            $desc = 'TaxJar requires a product tax class assigned to a TaxJar category in order to exempt products from 
                     sales tax. <a href="' . $url . '" target="_blank">Click here</a> to learn more.';
            $meta['product-details']['children']['container_tax_class_id']['children']['tax_class_id']['arguments']
            ['data']['config']['tooltip'] = ['description' => $desc];
        }

        return $meta;
    }

    /**
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        return $data;
    }
}