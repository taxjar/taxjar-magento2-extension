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

namespace Taxjar\SalesTax\Controller\Adminhtml\Taxclass;

abstract class Product extends \Taxjar\SalesTax\Controller\Adminhtml\Taxclass
{
    /**
     * Initialize action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function initResultPage()
    {
        $resultPage = parent::initResultPage();
        $resultPage->setActiveMenu('Taxjar_SalesTax::product_tax_classes');
        return $resultPage;
    }

    /**
     * Initialize tax class service object with form data.
     *
     * @param array $postData
     * @return \Magento\Tax\Api\Data\TaxClassInterface
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function populateTaxClass($postData)
    {
        $taxClass = parent::populateTaxClass($postData);
        $taxClass->setClassType('PRODUCT');
        return $taxClass;
    }
}
