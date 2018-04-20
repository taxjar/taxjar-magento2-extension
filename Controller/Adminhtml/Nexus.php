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

abstract class Nexus extends \Magento\Backend\App\Action
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
     * @var \Taxjar\SalesTax\Api\Tax\NexusRepositoryInterface
     */
    protected $nexusService;

    /**
     * @var \Taxjar\SalesTax\Api\Data\Tax\NexusInterfaceFactory
     */
    protected $nexusDataObjectFactory;

    /**
     * @var \Taxjar\SalesTax\Model\Tax\NexusSyncFactory
     */
    protected $nexusSyncFactory;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $countryFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Taxjar\SalesTax\Api\Tax\NexusRepositoryInterface $nexusService
     * @param \Taxjar\SalesTax\Api\Data\Tax\NexusInterfaceFactory $nexusDataObjectFactory
     * @param \Taxjar\SalesTax\Model\Tax\NexusSyncFactory $nexusSyncFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Taxjar\SalesTax\Api\Tax\NexusRepositoryInterface $nexusService,
        \Taxjar\SalesTax\Api\Data\Tax\NexusInterfaceFactory $nexusDataObjectFactory,
        \Taxjar\SalesTax\Model\Tax\NexusSyncFactory $nexusSyncFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->nexusService = $nexusService;
        $this->nexusDataObjectFactory = $nexusDataObjectFactory;
        $this->nexusSyncFactory = $nexusSyncFactory;
        $this->regionFactory = $regionFactory;
        $this->countryFactory = $countryFactory;
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
        $resultPage->setActiveMenu('Taxjar_SalesTax::nexus_addresses')
            ->addBreadcrumb(__('Sales'), __('Sales'))
            ->addBreadcrumb(__('Tax'), __('Tax'));
        return $resultPage;
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
     * Review nexus addresses for data issues
     *
     * @return \Magento\Framework\Message\ManagerInterface|void
     */
    protected function _reviewNexusAddresses()
    {
        $nexus = $this->nexusDataObjectFactory->create();
        $nexusMissingPostcode = $nexus->getCollection()->addFieldToFilter('postcode', ['null' => true]);

        if ($nexusMissingPostcode->getSize()) {
            // @codingStandardsIgnoreStart
            return $this->messageManager->addNoticeMessage(__('One or more of your nexus addresses are missing a zip/post code. Please provide accurate data for each nexus address.'));
            // @codingStandardsIgnoreEnd
        }
    }

    /**
     * Initialize nexus address service object with form data.
     *
     * @param array $postData
     * @return \Taxjar\SalesTax\Api\Data\Tax\NexusInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function populateNexus($postData)
    {
        $nexus = $this->nexusDataObjectFactory->create();
        if (isset($postData['id'])) {
            $nexus->setId($postData['id']);
        }
        if (isset($postData['api_id'])) {
            $nexus->setApiId($postData['api_id']);
        }
        if (isset($postData['street'])) {
            $nexus->setStreet($postData['street']);
        }
        if (isset($postData['city'])) {
            $nexus->setCity($postData['city']);
        }
        if (isset($postData['country_id'])) {
            $nexus->setCountryId($postData['country_id']);
        }
        if (isset($postData['region'])) {
            $nexus->setRegion($postData['region']);
        }
        if (isset($postData['region_id'])) {
            $nexus->setRegionId($postData['region_id']);
        }
        if (isset($postData['region_code'])) {
            $nexus->setRegionCode($postData['region_code']);
        }
        if (isset($postData['postcode'])) {
            $nexus->setPostcode($postData['postcode']);
        }
        if (isset($postData['store_id'])) {
            $nexus->setStoreId($postData['store_id']);
        }
        return $nexus;
    }
}
