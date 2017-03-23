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
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\Tax\NexusFactory;

class Disconnect extends \Magento\Backend\App\AbstractAction
{
    const ADMIN_RESOURCE = 'Magento_Tax::manage_tax';

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;

    /**
     * @var \Magento\Framework\App\Config\ReinitableConfigInterface
     */
    protected $reinitableConfig;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Taxjar\SalesTax\Model\Tax\NexusFactory
     */
    protected $nexusFactory;

    /**
     * @param Context $context
     * @param Config $resourceConfig
     * @param ReinitableConfigInterface $reinitableConfig
     * @param NexusFactory $nexusFactory
     */
    public function __construct(
        Context $context,
        Config $resourceConfig,
        ReinitableConfigInterface $reinitableConfig,
        NexusFactory $nexusFactory
    ) {
        $this->resourceConfig = $resourceConfig;
        $this->reinitableConfig = $reinitableConfig;
        $this->eventManager = $context->getEventManager();
        $this->nexusFactory = $nexusFactory;
        parent::__construct($context);
    }

    /**
     * Disconnect from TaxJar
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $this->resourceConfig->deleteConfig(TaxjarConfig::TAXJAR_APIKEY, 'default', 0);
        $this->resourceConfig->deleteConfig(TaxjarConfig::TAXJAR_EMAIL, 'default', 0);
        $this->resourceConfig->deleteConfig(TaxjarConfig::TAXJAR_CONNECTED, 'default', 0);
        $this->resourceConfig->deleteConfig(TaxjarConfig::TAXJAR_ENABLED, 'default', 0);
        $this->resourceConfig->deleteConfig(TaxjarConfig::TAXJAR_BACKUP, 'default', 0);
        $this->resourceConfig->deleteConfig(TaxjarConfig::TAXJAR_TRANSACTION_AUTH, 'default', 0);
        $this->resourceConfig->deleteConfig(TaxjarConfig::TAXJAR_TRANSACTION_SYNC, 'default', 0);
        $this->reinitableConfig->reinit();

        $this->_purgeNexusAddresses();

        $this->messageManager->addSuccessMessage(__('Your TaxJar account has been disconnected.'));

        $this->_redirect('adminhtml/system_config/edit', ['section' => 'tax']);
    }

    /**
     * Purge nexus addresses on disconnect
     *
     * @return void
     */
    private function _purgeNexusAddresses()
    {
        $nexusAddresses = $this->nexusFactory->create()->getCollection();
        foreach ($nexusAddresses as $nexusAddress) {
            // @codingStandardsIgnoreStart
            $nexusAddress->delete();
            // @codingStandardsIgnoreEnd
        }
    }
}
