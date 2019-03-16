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

namespace Taxjar\SalesTax\Ui\Component\Customer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Ui\Component\Form\Field;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class ValidateAddressButton extends Field
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        ScopeConfigInterface $scopeConfig,
        array $components = [],
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepare()
    {
        parent::prepare();

        $isEnabled = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_ADDRESS_VALIDATION);

        $newData = array_replace_recursive(
            ['componentDisabled' => !$isEnabled],
            (array) $this->getData('config')
        );

        $this->setData('config', $newData);
    }
}
