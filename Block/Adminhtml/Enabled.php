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

namespace Taxjar\SalesTax\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class Enabled extends PopupField
{
    /**
     * @var string
     */
    // @codingStandardsIgnoreStart
    protected $_template = 'Taxjar_SalesTax::enabled.phtml';
    // @codingStandardsIgnoreEnd

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $backendUrl;

    /**
     * @var \Magento\Framework\Config\CacheInterface
     */
    protected $cache;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $apiEmail;

    /**
     * @var TaxjarConfig
     */
    protected $taxjarConfig;

    /**
     * @param Context $context
     * @param UrlInterface $backendUrl
     * @param TaxjarConfig $taxjarConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        UrlInterface $backendUrl,
        TaxjarConfig $taxjarConfig,
        array $data = []
    ) {
        $this->cache = $context->getCache();
        $this->request = $context->getRequest();
        $this->scopeConfig = $context->getScopeConfig();
        $this->backendUrl = $backendUrl;
        $this->taxjarConfig = $taxjarConfig;
        $this->apiKey = $this->taxjarConfig->getApiKey();
        parent::__construct($context, $backendUrl, $data);
    }

    /**
     * Get the element HTML
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->apiEmail = trim($this->scopeConfig->getValue(TaxjarConfig::TAXJAR_EMAIL));

        if (!$this->apiKey) {
            $element->setDisabled('disabled');
        } else {
            $this->_cacheElementValue($element);
        }

        return parent::_getElementHtml($element) . $this->_toHtml();
    }

    /**
     * Cache the element value
     *
     * @param AbstractElement $element
     * @return void
     */
    protected function _cacheElementValue(AbstractElement $element)
    {
        $elementValue = (string) $element->getValue();
        $this->cache->save($elementValue, 'taxjar_salestax_config_enabled');
    }

    /**
     * Connected check
     *
     * @return bool
     */
    public function isConnected()
    {
        if ($this->apiKey) {
            return true;
        }
        return false;
    }

    /**
     * Get API email
     *
     * @return string
     */
    public function getApiEmail()
    {
        return $this->apiEmail;
    }

    /**
     * Get popup URL
     *
     * @return string
     */
    public function getPopupUrl()
    {
        $popupUrl = $this->getAuthUrl() . '/smartcalcs/connect/magento/?store=' . urlencode($this->getStoreOrigin());
        $popupUrl .= '&plugin=magento2&version=' . TaxjarConfig::TAXJAR_VERSION;
        return $popupUrl;
    }
}
