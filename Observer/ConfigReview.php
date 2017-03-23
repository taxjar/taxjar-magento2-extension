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

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Taxjar\SalesTax\Model\Tax\NexusFactory;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class ConfigReview implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $cache;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Taxjar\SalesTax\Model\Tax\NexusFactory
     */
    protected $nexusFactory;

    /**
     * @param \Magento\Framework\App\Request\Http $request
     * @param CacheInterface $cache
     * @param ManagerInterface $eventManager
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param ScopeConfigInterface $scopeConfig
     * @param NexusFactory $nexusFactory
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        CacheInterface $cache,
        ManagerInterface $eventManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        ScopeConfigInterface $scopeConfig,
        NexusFactory $nexusFactory
    ) {
        $this->request = $request;
        $this->cache = $cache;
        $this->eventManager = $eventManager;
        $this->messageManager = $messageManager;
        $this->scopeConfig = $scopeConfig;
        $this->nexusFactory = $nexusFactory;
    }

    /**
     * @param  Observer $observer
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    // @codingStandardsIgnoreStart
    public function execute(Observer $observer)
    {
        // @codingStandardsIgnoreEnd
        $configSection = $this->request->getParam('section');

        if ($configSection == 'tax') {
            $enabled = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_ENABLED);

            if ($enabled) {
                $this->_reviewNexusAddresses();
            }
        }

        return $this;
    }

    /**
     * @return void
     * @SuppressWarnings(Generic.Files.LineLength.TooLong)
     */
    private function _reviewNexusAddresses()
    {
        $nexusAddresses = $this->nexusFactory->create()->getCollection();

        if (!$nexusAddresses->getSize()) {
            // @codingStandardsIgnoreStart
            $this->messageManager->addErrorMessage(__('You have no nexus addresses loaded in Magento. Go to Stores > Nexus Addresses to sync from your TaxJar account or add a new address.'));
            // @codingStandardsIgnoreEnd
        }
    }
}
