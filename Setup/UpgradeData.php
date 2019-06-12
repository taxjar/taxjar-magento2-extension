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

namespace Taxjar\SalesTax\Setup;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeData implements UpgradeDataInterface
{
    private $eavSetupFactory;
    private $attributeRepository;
    private $eavConfig;

    public function __construct(
        \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory,
        \Magento\Eav\Model\AttributeRepository $attributeRepository,
        Config $eavConfig
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->attributeRepository = $attributeRepository;
        $this->eavConfig = $eavConfig;
    }

    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        if (version_compare($context->getVersion(), '1.0.2', '<')) {

            /** @var EavSetup $eavSetup */
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

            $exemptionTypeCode = 'tj_exemption_type';
            $eavSetup->addAttribute(
                CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                $exemptionTypeCode,
                [
                    'group' => 'General',
                    'type' => 'varchar',
                    'label' => 'Exemption Type',
                    'input' => 'select',

                    'required' => false,
                    'visible' => true,
                    'user_defined' => true,
                    'position' => 501,
                    'system' => 0,
                    'sort_order' => 50,
                    'default' => 'non_exempt',

                    'source' => 'Taxjar\SalesTax\Model\Attribute\Source\CustomerExemptionType',
                    'backend' => 'Taxjar\SalesTax\Model\Attribute\Backend\CustomerExemptionType',
                    'frontend' => 'Taxjar\SalesTax\Model\Attribute\Frontend\CustomerExemptionType',
                    'global' => 'Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL',

                    'is_used_in_grid' => false,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'is_html_allowed_on_front' => true,
                    'visible_on_front' => true
                ]
            );
            $eavSetup->addAttributeToSet(
                CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
                null,
                $exemptionTypeCode);
            $exemptionType = $this->eavConfig->getAttribute(
                CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                $exemptionTypeCode);
            $exemptionType->setData('used_in_forms', ['adminhtml_customer']);
            $exemptionType->getResource()->save($exemptionType);


            $regionsCode = 'tj_regions';
            $eavSetup->addAttribute(
                CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                $regionsCode,
                [
                    'group' => 'General',
                    'type' => 'text',
                    'label' => 'Regions',
                    'input' => 'multiselect',

                    'required' => false,
                    'visible' => true,
                    'user_defined' => true,
                    'position' => 502,
                    'system' => 0,
                    'sort_order' => 51,

                    'source' => 'Taxjar\SalesTax\Model\Attribute\Source\Regions',
                    'frontend' => 'Taxjar\SalesTax\Model\Attribute\Frontend\Regions',
//                    'backend' => 'Taxjar\SalesTax\Model\Attribute\Backend\Regions',
                    'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                    'global' => 'Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL',

                    'is_used_in_grid' => false,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'is_html_allowed_on_front' => true,
                    'visible_on_front' => true
                ]
            );

            $eavSetup->addAttributeToSet(
                CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
                null,
                $regionsCode);

            $regionsCodeId = $this->eavConfig->getAttribute(
                CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                $regionsCode);
            $regionsCodeId->setData('used_in_forms', ['adminhtml_customer']);

            $regionsCodeId->getResource()->save($regionsCodeId);


            $lastSyncCode = 'tj_last_sync';
            $eavSetup->addAttribute(
                CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                $lastSyncCode,
                [
                    'group' => 'General',
                    'type' => 'datetime',
                    'label' => 'Last Sync Date',
                    'input' => 'date',

                    'required' => false,
                    'visible' => true,
                    'user_defined' => true,
                    'position' => 502,
                    'system' => 0,
                    'sort_order' => 52,

                    'global' => 'Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL',

                    'is_used_in_grid' => false,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'is_html_allowed_on_front' => true,
                    'visible_on_front' => false
                ]
            );

            $eavSetup->addAttributeToSet(
                CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
                null,
                $lastSyncCode);

            $lastSyncCodeId = $this->eavConfig->getAttribute(CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                $lastSyncCode);
            $lastSyncCodeId->setData('used_in_forms', ['adminhtml_customer',]);

            $lastSyncCodeId->getResource()->save($lastSyncCodeId);

        }
    }
}