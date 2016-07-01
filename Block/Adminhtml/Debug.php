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
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ModuleListInterface;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class Debug extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Taxjar_SalesTax::debug.phtml';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    
    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $_moduleList;
    
    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    protected $_productMetadata;
    
    /**
     * @param Context $context
     * @param ModuleListInterface $moduleList
     * @param array $data
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList,
        ProductMetadata $productMetadata,
        array $data = []
    ) {
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_moduleList = $moduleList;
        $this->_productMetadata = $productMetadata;
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
        return parent::_getElementHtml($element) . $this->_toHtml();
    }
    
    /**
     * Debug mode enabled check
     *
     * @return bool
     */
    public function isEnabled()
    {
        $isEnabled = $this->_scopeConfig->getValue(TaxjarConfig::TAXJAR_DEBUG); 

        if ($isEnabled) {
            return true;
        }

        return false;
    }
    
    /**
     * Get list of backup rate states
     *
     * @return string
     */
    public function getBackupStates()
    {
        return implode(', ', unserialize($this->_scopeConfig->getValue(TaxjarConfig::TAXJAR_STATES)));
    }
    
    /**
     * Get backup rate last updated date
     *
     * @return string
     */
    public function getBackupUpdate()
    {
        return $this->_scopeConfig->getValue(TaxjarConfig::TAXJAR_LAST_UPDATE);
    }
    
    /**
     * Get extension version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->_moduleList->getOne('Taxjar_SalesTax')['setup_version'];
    }
    
    /**
     * Get PHP version
     *
     * @return string
     */
    public function getPhpVersion()
    {
        return @phpversion();
    }
    
    /**
     * Get Magento version
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->_productMetadata->getVersion();
    }
    
    /**
     * Get PHP memory limit
     *
     * @return string
     */
    public function getMemoryLimit()
    {
        return @ini_get('memory_limit');
    }
}
