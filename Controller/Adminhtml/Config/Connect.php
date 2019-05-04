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
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;

    /**
     * @var \Magento\Framework\App\Config\ReinitableConfigInterface
     */
    protected $reinitableConfig;

    /**
     * @var \Taxjar\SalesTax\Model\Client $client
     */
    protected $client;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $resourceConfig
     * @param ReinitableConfigInterface $reinitableConfig
     * @param \Taxjar\SalesTax\Model\ClientFactory $clientFactory
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Config $resourceConfig,
        ReinitableConfigInterface $reinitableConfig,
        \Taxjar\SalesTax\Model\ClientFactory $clientFactory
    ) {
        $this->eventManager = $context->getEventManager();
        $this->scopeConfig = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
        $this->reinitableConfig = $reinitableConfig;
        $this->client = $clientFactory->create();
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
        $reportingAccess = (string) $this->getRequest()->getParam('reporting_access');

        if ($apiKey && $apiEmail && $this->isVerified($apiKey)) {
            $this->resourceConfig->saveConfig(TaxjarConfig::TAXJAR_APIKEY, $apiKey, 'default', 0);
            $this->resourceConfig->saveConfig(TaxjarConfig::TAXJAR_EMAIL, $apiEmail, 'default', 0);
            $this->resourceConfig->saveConfig(TaxjarConfig::TAXJAR_CONNECTED, 1, 'default', 0);

            if ($reportingAccess == 'true') {
                $this->resourceConfig->saveConfig(TaxjarConfig::TAXJAR_TRANSACTION_AUTH, 1, 'default', 0);
            }

            $this->reinitableConfig->reinit();
            $this->messageManager->addSuccessMessage(__('TaxJar account for %1 is now connected.', $apiEmail));
            $this->eventManager->dispatch('taxjar_salestax_import_categories');
        } else {
            // @codingStandardsIgnoreStart
            $this->messageManager->addErrorMessage(__('Could not connect your TaxJar account. Please make sure you have a valid API token and try again.'));
            // @codingStandardsIgnoreEnd
        }

        $this->_redirect('adminhtml/system_config/edit', ['section' => 'tax']);
    }

    /**
     * Verify TaxJar API token and designate Plus subscription
     *
     * @param string $apiKey
     * @return boolean
     */
    protected function isVerified($apiKey)
    {
        try {
            $this->client->setApiKey($apiKey);

            $response = $this->client->postResource('verify', ['token' => $apiKey]);

            if ($response['enabled'] && $response['valid']) {
                if ($response['plus']) {
                    $this->resourceConfig->saveConfig(TaxjarConfig::TAXJAR_PLUS, true, 'default', 0);
                }

                return true;
            }
        } catch (\Exception $e) {
            // Noop
        }

        return false;
    }
}
