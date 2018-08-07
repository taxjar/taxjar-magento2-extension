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

namespace Taxjar\SalesTax\Controller\Adminhtml;

use Magento\Framework\Controller\ResultFactory;
use TaxJar\SalesTax\Model\Configuration;

abstract class Taxclass extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Tax::manage_tax';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Tax\Api\TaxClassRepositoryInterface
     */
    protected $taxClassService;

    /**
     * @var \Magento\Tax\Api\Data\TaxClassInterfaceFactory
     */
    protected $taxClassDataObjectFactory;

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
        $this->coreRegistry = $coreRegistry;
        $this->taxClassService = $taxClassService;
        $this->taxClassDataObjectFactory = $taxClassDataObjectFactory;
        parent::__construct($context);
    }

    /**
     * Initialize action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function initResultPage()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->addBreadcrumb(__('Sales'), __('Sales'))
            ->addBreadcrumb(__('Tax'), __('Tax'));
        return $resultPage;
    }

    /**
     * Validate/Filter Tax Class Name
     *
     * @param string $className
     * @return string processed class name
     * @throws \Magento\Framework\Exception\InputException
     */
    protected function _processClassName($className)
    {
        $className = trim($className);
        if ($className == '') {
            throw new \Magento\Framework\Exception\InputException(__('Invalid name of tax class specified.'));
        }
        return $className;
    }

    /**
     * Check current user permission on resource and privilege
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
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
        $taxClass = $this->taxClassDataObjectFactory->create();
        if (isset($postData['class_id'])) {
            $taxClass->setClassId($postData['class_id']);
        }
        if (isset($postData['class_name'])) {
            $taxClass->setClassName($postData['class_name']);
        }
        if (isset($postData['tj_salestax_code'])) {
            $taxClass->setTjSalestaxCode($postData['tj_salestax_code']);
        }
        if (isset($postData['tj_salestax_code']) && '99999' == $postData['tj_salestax_code'] ) {
            if(isset($postData['tj_salestax_exempt_type'])) {
                $taxClass->setTjSalestaxExemptType($postData['tj_salestax_exempt_type']);
            }
        } else {
            $taxClass->setTjSalestaxExemptType(Configuration::TAXJAR_DEFAULT_EXEMPT_TYPE);
        }

        return $taxClass;
    }
}
