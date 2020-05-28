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

use Exception;
use Magento\Backend\App\AbstractAction;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Taxjar\SalesTax\Model\Client;
use Taxjar\SalesTax\Model\ClientFactory;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\Logger;

class Connect extends AbstractAction
{
    const ADMIN_RESOURCE = 'Magento_Tax::manage_tax';

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Config
     */
    protected $resourceConfig;

    /**
     * @var ReinitableConfigInterface
     */
    protected $reinitableConfig;

    /**
     * @var Client $client
     */
    protected $client;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $resourceConfig
     * @param ReinitableConfigInterface $reinitableConfig
     * @param ClientFactory $clientFactory
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Config $resourceConfig,
        ReinitableConfigInterface $reinitableConfig,
        ClientFactory $clientFactory,
        Logger $logger
    ) {
        $this->eventManager = $context->getEventManager();
        $this->scopeConfig = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
        $this->reinitableConfig = $reinitableConfig;
        $this->client = $clientFactory->create();
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Connect to TaxJar
     *
     * @return Page|Redirect
     */
    public function execute()
    {
        $apiKey = (string) $this->getRequest()->getParam('api_key');
        $apiEmail = (string) $this->getRequest()->getParam('api_email');

        if ($apiKey && $apiEmail && $this->isVerified($apiKey)) {
            $this->resourceConfig->saveConfig(TaxjarConfig::TAXJAR_APIKEY, $apiKey, 'default', 0);
            $this->resourceConfig->saveConfig(TaxjarConfig::TAXJAR_EMAIL, $apiEmail, 'default', 0);
            $this->resourceConfig->saveConfig(TaxjarConfig::TAXJAR_CONNECTED, 1, 'default', 0);

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
     * Verify if user has a valid subscription
     *
     * @param string $apiKey
     * @return bool
     * @throws LocalizedException
     */
    protected function isVerified($apiKey)
    {
        try {
            $this->client->setApiKey($apiKey);

            $response = $this->client->postResource('verify', ['token' => $apiKey]);
            $valid = isset($response['valid']) ? $response['valid'] : false;
            $enabled = isset($response['enabled']) ? $response['enabled'] : false;
            $plus = isset($response['plus']) ? $response['plus'] : false;

            if ($valid && $enabled) {
                if ($plus) {
                    $this->resourceConfig->saveConfig(TaxjarConfig::TAXJAR_PLUS, true, 'default', 0);
                }

                return true;
            }
        } catch (Exception $e) {
            $this->logger->log($e->getMessage());
        }

        return false;
    }
}
