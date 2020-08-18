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

namespace Taxjar\SalesTax\Observer;

use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Taxjar\SalesTax\Model\ClientFactory;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\ConfigurationFactory;
use Taxjar\SalesTax\Model\ResourceModel\Tax\Category;
use Taxjar\SalesTax\Model\Tax\CategoryFactory;

class ImportCategories implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;

    /**
     * @var \Taxjar\SalesTax\Model\ClientFactory
     */
    protected $clientFactory;

    /**
     * @var \Taxjar\SalesTax\Model\ConfigurationFactory
     */
    protected $configFactory;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $client;

    /**
     * @var \Taxjar\SalesTax\Model\Tax\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Taxjar\SalesTax\Model\ResourceModel\Tax\Category
     */
    protected $categoryResourceModel;

    /**
     * @var TaxjarConfig
     */
    protected $taxjarConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $resourceConfig
     * @param ClientFactory $clientFactory
     * @param ConfigurationFactory $configFactory
     * @param CategoryFactory $categoryFactory
     * @param Category $categoryResourceModel
     * @param TaxjarConfig $taxjarConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Config $resourceConfig,
        ClientFactory $clientFactory,
        ConfigurationFactory $configFactory,
        CategoryFactory $categoryFactory,
        Category $categoryResourceModel,
        TaxjarConfig $taxjarConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
        $this->clientFactory = $clientFactory;
        $this->configFactory = $configFactory;
        $this->categoryFactory = $categoryFactory;
        $this->categoryResourceModel = $categoryResourceModel;
        $this->taxjarConfig = $taxjarConfig;
    }

    /**
     * @param  Observer $observer
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    // @codingStandardsIgnoreStart
    public function execute(Observer $observer)
    {
        $this->apiKey = $this->taxjarConfig->getApiKey();

        // @codingStandardsIgnoreEnd
        if ($this->apiKey) {
            $this->client = $this->clientFactory->create();
            $this->_importCategories();
        }

        return $this;
    }

    /**
     * Execute observer action during cron job
     *
     * @return void
     */
    public function cron()
    {
        $this->execute(new Observer);
    }

    /**
     * Get TaxJar product categories
     *
     * @return array
     */
    private function _getCategoryJson()
    {
        $categoryJson = $this->client->getResource('categories');
        return $categoryJson['categories'];
    }

    /**
     * Import TaxJar product categories
     *
     * @return void
     */
    private function _importCategories()
    {
        $categoryJson = $this->_getCategoryJson();

        foreach ($categoryJson as $categoryData) {
            $category = $this->categoryFactory->create();

            // Load the category by product tax code to prevent creating duplicates
            $this->categoryResourceModel->load($category, $categoryData['product_tax_code'], 'product_tax_code');
            $category->setProductTaxCode($categoryData['product_tax_code']);
            $category->setName($categoryData['name']);
            $category->setDescription($categoryData['description']);
            $this->categoryResourceModel->save($category);
        }
    }
}
