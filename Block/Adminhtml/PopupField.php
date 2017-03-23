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
use Magento\Config\Block\System\Config\Form\Field;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class PopupField extends Field
{
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
        $this->request = $context->getRequest();
        $this->scopeConfig = $context->getScopeConfig();
        $this->backendUrl = $backendUrl;
        parent::__construct($context, $data);
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
        return $this->backendUrl->getUrl($route, $params);
    }

    /**
     * Get reporting app auth URL
     *
     * @return string
     */
    public function getAuthUrl()
    {
        return TaxjarConfig::TAXJAR_AUTH_URL;
    }

    /**
     * Get current store origin
     *
     * @return string
     */
    protected function getStoreOrigin()
    {
        $protocol = $this->request->isSecure() ? 'https://' : 'http://';
        return $protocol . $this->request->getHttpHost(false);
    }

    /**
     * Get store general contact email if non-default
     *
     * @return string
     */
    protected function getStoreGeneralEmail()
    {
        $email = $this->scopeConfig->getValue('trans_email/ident_general/email');
        if ($email != 'owner@example.com') {
            return $email;
        } else {
            return '';
        }
    }
}
