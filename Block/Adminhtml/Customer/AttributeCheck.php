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
 * @copyright  Copyright (c) 2025 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Block\Adminhtml\Customer;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Taxjar\SalesTax\Setup\Patch\Data\AddExtensionAttributesPatch;

class AttributeCheck extends Template
{
    /**
     * @var Config
     */
    protected $eavConfig;

    /**
     * @param Context $context
     * @param Config $eavConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $eavConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->eavConfig = $eavConfig;
    }

    /**
     * Check if any TaxJar customer attributes are missing
     *
     * @return bool
     */
    public function hasMissingAttributes(): bool
    {
        // For this critical feature, always check live to avoid caching issues
        $missingAttributes = $this->getMissingAttributes();
        return !empty($missingAttributes);
    }

    /**
     * Get a list of missing TaxJar customer attributes
     *
     * @return array
     */
    public function getMissingAttributes(): array
    {
        $missingAttributes = [];
        $attributeCodes = [
            AddExtensionAttributesPatch::TJ_EXEMPTION_TYPE_CODE,
            AddExtensionAttributesPatch::TJ_REGIONS_CODE,
            AddExtensionAttributesPatch::TJ_LAST_SYNC_CODE
        ];

        foreach ($attributeCodes as $attributeCode) {
            try {
                $attribute = $this->eavConfig->getAttribute(
                    CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                    $attributeCode
                );
                
                if (!$attribute || !$attribute->getAttributeId()) {
                    $missingAttributes[] = $attributeCode;
                }
            } catch (LocalizedException $e) {
                $missingAttributes[] = $attributeCode;
            }
        }

        return $missingAttributes;
    }

    /**
     * Get URL to install TaxJar customer attributes
     *
     * @return string
     */
    public function getInstallUrl(): string
    {
        return $this->getUrl('taxjar/attributes/install');
    }
}
