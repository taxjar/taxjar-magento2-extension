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

class TransactionSync extends PopupField
{
    /**
     * @var string
     */
    // @codingStandardsIgnoreStart
    protected $_template = 'Taxjar_SalesTax::transaction_sync.phtml';
    // @codingStandardsIgnoreEnd

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $backendUrl;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

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
        $this->scopeConfig = $context->getScopeConfig();
        $this->backendUrl = $backendUrl;
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
        if (!$this->isAuthorized()) {
            $element->setDisabled('disabled');
        }

        return parent::_getElementHtml($element) . $this->_toHtml();
    }

    /**
     * Transaction sync authorization check
     *
     * @return bool
     */
    public function isAuthorized()
    {
        $isAuthorized = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_TRANSACTION_AUTH);

        if ($isAuthorized) {
            return true;
        }

        return false;
    }

    /**
     * Connected check
     *
     * @return bool
     */
    public function isConnected()
    {
        $isConnected = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_CONNECTED);

        if ($isConnected) {
            return true;
        }

        return false;
    }

    /**
     * Transaction sync enabled check
     *
     * @return bool
     */
    public function isEnabled()
    {
        $isEnabled = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_TRANSACTION_SYNC);

        if ($isEnabled) {
            return true;
        }

        return false;
    }

    /**
     * Get popup URL
     *
     * @return string
     */
    public function getPopupUrl()
    {
        $popupUrl = $this->getAuthUrl()
                        . '/smartcalcs/connect/magento/upgrade_account/?store='
                        . urlencode($this->getStoreOrigin());

        if ($this->getStoreGeneralEmail()) {
            $popupUrl .= '&email=' . urlencode($this->getStoreGeneralEmail());
        }

        $popupUrl .= '&plugin=magento2&version=' . TaxjarConfig::TAXJAR_VERSION;

        return $popupUrl;
    }
}
