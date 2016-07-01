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

namespace Taxjar\SalesTax\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Model\UrlInterface;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class Enabled extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Taxjar_SalesTax::enabled.phtml';
  
    /**
     * @var \Magento\Framework\Config\CacheInterface
     */
    protected $_cache;
    
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    
    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $_backendUrl;
    
    /**
     * @var string
     */
    protected $_apiKey;
    
    /**
     * @var string
     */
    protected $_apiEmail;
    
    /**
     * @param Context $context
     * @param UrlInterface $backendUrl
     * @param array $data
     */
    public function __construct(
        Context $context,
        UrlInterface $backendUrl,
        array $data = []
    ) {
        $this->_cache = $context->getCache();
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_backendUrl = $backendUrl;
        parent::__construct($context, $data);
    }
    
    /**
     * Get the element HTML
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->_apiKey = trim($this->_scopeConfig->getValue(TaxjarConfig::TAXJAR_APIKEY));
        $this->_apiEmail = trim($this->_scopeConfig->getValue(TaxjarConfig::TAXJAR_EMAIL));
        
        if (!$this->_apiKey) {
            $element->setDisabled('disabled');
        } else {
            $this->_cacheElementValue($element);
        }

        return parent::_getElementHtml($element) . $this->_toHtml();
    }
    
    /**
     * Cache the element value
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return void
     */
    protected function _cacheElementValue(AbstractElement $element)
    {
        $elementValue = (string) $element->getValue();
        $this->_cache->save($elementValue, 'taxjar_salestax_config_enabled');
    }
    
    /**
     * Connected check
     *
     * @return bool
     */
    public function isConnected()
    {
        if ($this->_apiKey) {
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
        return $this->_apiEmail;
    }
    
    /**
     * Get store URL
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getStoreUrl($route, $params = [])
    {
        return $this->_backendUrl->getUrl($route, $params);
    }
    
    /**
     * Get reporting app auth URL
     *
     * @return string
     */
    public function getAuthUrl()
    {
        return 'https://app.taxjar.com';
    }
    
    /**
     * Get popup URL
     *
     * @return string
     */
    public function getPopupUrl()
    {
        $popupUrl = $this->getAuthUrl() . '/smartcalcs/connect/magento/?store=' . urlencode($this->_getStoreOrigin());
      
        if ($this->_getStoreGeneralEmail()) {
            $popupUrl .= '&email=' . urlencode($this->_getStoreGeneralEmail());
        }
      
        return $popupUrl;
    }
    
    /**
     * Get current store origin
     *
     * @return string
     */
    private function _getStoreOrigin()
    {
        $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
        return $protocol . $_SERVER['HTTP_HOST'];
    }
    
    /**
     * Get store general contact email if non-default
     *
     * @return string
     */
    private function _getStoreGeneralEmail()
    {
        $email = $this->_scopeConfig->getValue('trans_email/ident_general/email');
        if ($email != 'owner@example.com') {
            return $email;
        } else {
            return '';
        }
    }
}
