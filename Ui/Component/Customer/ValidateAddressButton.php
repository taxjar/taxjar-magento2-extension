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
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Form\Field;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class ValidateAddressButton extends Field
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        ScopeConfigInterface $scopeConfig,
        ProductMetadataInterface $productMetadata,
        array $components = [],
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->productMetadata = $productMetadata;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @throws LocalizedException
     */
    public function prepare()
    {
        parent::prepare();

        $isEnabled = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_ADDRESS_VALIDATION);

        // If address validation is disabled or version >= 2.3, hide this button
        if (!$isEnabled || floatval($this->productMetadata->getVersion()) >= 2.3) {
            $newData = array_replace_recursive(
                ['componentDisabled' => true],
                (array) $this->getData('config')
            );

            $this->setData('config', $newData);
        }
    }
}
