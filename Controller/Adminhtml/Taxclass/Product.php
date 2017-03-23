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

namespace Taxjar\SalesTax\Controller\Adminhtml\Taxclass;

abstract class Product extends \Taxjar\SalesTax\Controller\Adminhtml\Taxclass
{
    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassService
     * @param \Magento\Tax\Api\Data\TaxClassInterfaceFactory $taxClassDataObjectFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassService,
        \Magento\Tax\Api\Data\TaxClassInterfaceFactory $taxClassDataObjectFactory
    ) {
        parent::__construct($context, $coreRegistry, $taxClassService, $taxClassDataObjectFactory);
    }

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
