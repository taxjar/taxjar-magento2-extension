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
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Taxjar\SalesTax\Model\ClientFactory;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var \Magento\Eav\Model\AttributeRepository
     */
    private $attributeRepository;

    /**
     * @var \Taxjar\SalesTax\Model\Client
     */
    private $client;

    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @var \Magento\Eav\Setup\EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    private $resourceConfig;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param \Magento\Eav\Model\AttributeRepository $attributeRepository
     * @param ClientFactory $clientFactory
     * @param Config $eavConfig
     * @param \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Eav\Model\AttributeRepository $attributeRepository,
        ClientFactory $clientFactory,
        Config $eavConfig,
        \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->client = $clientFactory->create();
        $this->eavConfig = $eavConfig;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eventManager = $eventManager;
        $this->resourceConfig = $resourceConfig;
        $this->scopeConfig = $scopeConfig;
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
                    'label' => 'TaxJar Exemption Type',
                    'input' => 'select',

                    'required' => false,
                    'visible' => true,
                    'user_defined' => true,
                    'position' => 501,
                    'system' => 0,
                    'sort_order' => 50,
                    'default' => 'non_exempt',

                    'source' => 'Taxjar\SalesTax\Model\Attribute\Source\CustomerExemptionType',
                    'global' => 'Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL',

                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
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
                    'label' => 'TaxJar Exempt Regions',
                    'input' => 'multiselect',

                    'required' => false,
                    'visible' => true,
                    'user_defined' => true,
                    'position' => 502,
                    'system' => 0,
                    'sort_order' => 51,
                    'disabled' => false,

                    'source' => 'Taxjar\SalesTax\Model\Attribute\Source\Regions',
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
                    'label' => 'TaxJar Last Sync Date',
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
            $lastSyncCodeId = $this->eavConfig->getAttribute(
                CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                $lastSyncCode);
            $lastSyncCodeId->setData('used_in_forms', ['adminhtml_customer',]);
            $lastSyncCodeId->getResource()->save($lastSyncCodeId);
        }

        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            if ($this->scopeConfig->getValue(TaxjarConfig::TAXJAR_APIKEY)) {
                $configJson = $this->client->getResource('config');

                if (is_array($configJson) && isset($configJson['configuration']) && isset($configJson['configuration']['states'])) {
                    $this->resourceConfig->saveConfig(
                        TaxjarConfig::TAXJAR_STATES,
                        json_encode(explode(',', $configJson['configuration']['states'])),
                        'default',
                        0
                    );
                }
            }
        }

        if (version_compare($context->getVersion(), '1.0.4', '<')) {
            $this->resourceConfig->deleteConfig('tax/taxjar/categories', 'default', '');
            $this->eventManager->dispatch('taxjar_salestax_import_categories');
        }
    }
}
