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
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class AddressValidation extends Field
{
    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $cache;

    /**
     * @var string
     */
    // @codingStandardsIgnoreStart
    protected $_template = 'Taxjar_SalesTax::address_validation.phtml';
    // @codingStandardsIgnoreEnd

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        $this->cache = $context->getCache();
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct($context, $data);
    }

    /**
     * Get the element HTML
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        if (!$this->isAuthorized()) {
            $element->setDisabled('disabled');
        }

        $this->_cacheElementValue($element);

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
        $this->cache->save($elementValue, 'taxjar_salestax_config_address_validation');
    }

    /**
     * TaxJar address validation authorization check
     *
     * @return bool
     */
    public function isAuthorized()
    {
        $isAuthorized = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_PLUS);

        if ($isAuthorized) {
            return true;
        }

        return false;
    }

    /**
     * Address validation enabled check
     *
     * @return bool
     */
    public function isEnabled()
    {
        $isEnabled = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_ADDRESS_VALIDATION);

        if ($isEnabled) {
            return true;
        }

        return false;
    }
}
