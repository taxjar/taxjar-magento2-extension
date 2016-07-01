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
 * @copyright  Copyright (c) 2016 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Controller\Adminhtml\Config;

use Magento\Backend\App\Action\Context;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class Connect extends \Magento\Backend\App\AbstractAction
{
    const ADMIN_RESOURCE = 'Magento_Tax::manage_tax';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $_resourceConfig;
    
    /**
     * @var \Magento\Framework\App\Config\ReinitableConfigInterface
     */
    protected $_reinitableConfig;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $resourceConfig
     * @param ReinitableConfigInterface $reinitableConfig
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Config $resourceConfig,
        ReinitableConfigInterface $reinitableConfig
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_resourceConfig = $resourceConfig;
        $this->_reinitableConfig = $reinitableConfig;
        parent::__construct($context);
    }

    /**
     * Connect to TaxJar
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $apiKey = (string) $this->getRequest()->getParam('api_key');
        $apiEmail = (string) $this->getRequest()->getParam('api_email');
        
        if ($apiKey && $apiEmail) {
            $this->_resourceConfig->saveConfig(TaxjarConfig::TAXJAR_APIKEY, $apiKey, 'default', 0);
            $this->_resourceConfig->saveConfig(TaxjarConfig::TAXJAR_EMAIL, $apiEmail, 'default', 0);
            $this->_resourceConfig->saveConfig(TaxjarConfig::TAXJAR_CONNECTED, 1, 'default', 0);
            $this->_reinitableConfig->reinit();
            $this->messageManager->addSuccess(__('TaxJar account for %1 is now connected.', $apiEmail));
        } else {
            $this->messageManager->addError(__('Could not connect your TaxJar account. Please make sure you have a valid API token and try again.'));
        }

        $this->_redirect('adminhtml/system_config/edit', ['section' => 'tax']);
    }
}