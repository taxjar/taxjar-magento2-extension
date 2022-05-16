<?php

namespace Taxjar\SalesTax\Setup\Patch\Data;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Taxjar\SalesTax\Model\Attribute\Source\CustomerExemptionType;
use Taxjar\SalesTax\Model\Attribute\Source\Regions;

class AddExtensionAttributesPatch implements DataPatchInterface, PatchRevertableInterface
{
    public const TJ_EXEMPTION_TYPE_CODE = 'tj_exemption_type';

    public const TJ_REGIONS_CODE = 'tj_regions';

    public const TJ_LAST_SYNC_CODE = 'tj_last_sync';

    private const EAV_ATTRIBUTE_GROUP_GENERAL = 'General';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var Config
     */
    private $eavConfig;
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Config $eavConfig
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Config $eavConfig,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavConfig = $eavConfig;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create([
            'setup' => $this->moduleDataSetup
        ]);

        $this->createTjExemptionTypeAttribute($eavSetup);
        $this->createTjRegionsAttribute($eavSetup);
        $this->createTjLastSyncAttribute($eavSetup);

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheirtDoc
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create([
            'setup' => $this->moduleDataSetup
        ]);

        $this->deleteTjExemptionTypeAttribute($eavSetup);
        $this->deleteTjRegionsAttribute($eavSetup);
        $this->deleteTjLastSyncAttribute($eavSetup);

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @param \Magento\Eav\Setup\EavSetup $eavSetup
     * @return mixed
     */
    protected function getTjExemptionTypeAttribute($eavSetup)
    {
        return $eavSetup->getAttribute(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            self::TJ_EXEMPTION_TYPE_CODE
        );
    }

    /**
     * @param \Magento\Eav\Setup\EavSetup $eavSetup
     * @throws \Magento\Framework\Exception\AlreadyExistsException|\Magento\Framework\Exception\LocalizedException|\Zend_Validate_Exception
     */
    protected function createTjExemptionTypeAttribute($eavSetup)
    {
        $tjExemptionTypeAttribute = $this->getTjExemptionTypeAttribute($eavSetup);

        if (!$tjExemptionTypeAttribute) {
            $eavSetup->addAttribute(
                CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                self::TJ_EXEMPTION_TYPE_CODE,
                [
                    'group' => self::EAV_ATTRIBUTE_GROUP_GENERAL,
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

                    'source' => CustomerExemptionType::class,
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,

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
                self::EAV_ATTRIBUTE_GROUP_GENERAL,
                self::TJ_EXEMPTION_TYPE_CODE
            );

            $exemptionType = $this->eavConfig->getAttribute(
                CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                self::TJ_EXEMPTION_TYPE_CODE
            );

            $exemptionType->setData('used_in_forms', ['adminhtml_customer']);
            $exemptionType->getResource()->save($exemptionType); // phpcs:ignore
        }
    }

    /**
     * @param \Magento\Eav\Setup\EavSetup $eavSetup
     */
    protected function deleteTjExemptionTypeAttribute($eavSetup)
    {
        $tjExemptionTypeAttribute = $this->getTjExemptionTypeAttribute($eavSetup);

        if ($tjExemptionTypeAttribute) {
            $eavSetup->removeAttribute(
                CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                self::TJ_EXEMPTION_TYPE_CODE
            );
        }
    }

    /**
     * @param \Magento\Eav\Setup\EavSetup $eavSetup
     * @return mixed
     */
    protected function getTjRegionsAttribute($eavSetup)
    {
        return $eavSetup->getAttribute(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            self::TJ_REGIONS_CODE
        );
    }

    /**
     * @param \Magento\Eav\Setup\EavSetup $eavSetup
     * @throws \Magento\Framework\Exception\AlreadyExistsException|\Magento\Framework\Exception\LocalizedException|\Zend_Validate_Exception
     */
    protected function createTjRegionsAttribute($eavSetup)
    {
        $tjRegionsAttribute = $this->getTjRegionsAttribute($eavSetup);

        if (!$tjRegionsAttribute) {
            $eavSetup->addAttribute(
                CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                self::TJ_REGIONS_CODE,
                [
                    'group' => self::EAV_ATTRIBUTE_GROUP_GENERAL,
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

                    'source' => Regions::class,
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,

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
                self::EAV_ATTRIBUTE_GROUP_GENERAL,
                self::TJ_REGIONS_CODE
            );

            $regionsCodeId = $this->eavConfig->getAttribute(
                CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                self::TJ_REGIONS_CODE
            );

            $regionsCodeId->setData('used_in_forms', ['adminhtml_customer']);
            $regionsCodeId->getResource()->save($regionsCodeId); // phpcs:ignore
        }
    }

    /**
     * @param \Magento\Eav\Setup\EavSetup $eavSetup
     */
    protected function deleteTjRegionsAttribute($eavSetup)
    {
        $tjRegionsAttribute = $this->getTjRegionsAttribute($eavSetup);

        if ($tjRegionsAttribute) {
            $eavSetup->removeAttribute(
                CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                self::TJ_REGIONS_CODE
            );
        }
    }

    /**
     * @param \Magento\Eav\Setup\EavSetup $eavSetup
     * @return mixed
     */
    protected function getTjLastSyncAttribute($eavSetup)
    {
        return $eavSetup->getAttribute(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            self::TJ_LAST_SYNC_CODE
        );
    }

    /**
     * @param \Magento\Eav\Setup\EavSetup $eavSetup
     * @throws \Magento\Framework\Exception\AlreadyExistsException|\Magento\Framework\Exception\LocalizedException|\Zend_Validate_Exception
     */
    protected function createTjLastSyncAttribute($eavSetup)
    {
        $tjLastSync = $this->getTjLastSyncAttribute($eavSetup);

        if (!$tjLastSync) {
            $eavSetup->addAttribute(
                CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                self::TJ_LAST_SYNC_CODE,
                [
                    'group' => self::EAV_ATTRIBUTE_GROUP_GENERAL,
                    'type' => 'datetime',
                    'label' => 'TaxJar Last Sync Date',
                    'input' => 'date',

                    'required' => false,
                    'visible' => true,
                    'user_defined' => true,
                    'position' => 502,
                    'system' => 0,
                    'sort_order' => 52,

                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,

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
                self::EAV_ATTRIBUTE_GROUP_GENERAL,
                self::TJ_LAST_SYNC_CODE
            );

            $lastSyncCodeId = $this->eavConfig->getAttribute(
                CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                self::TJ_LAST_SYNC_CODE
            );

            $lastSyncCodeId->setData('used_in_forms', ['adminhtml_customer',]);
            $lastSyncCodeId->getResource()->save($lastSyncCodeId); // phpcs:ignore
        }
    }

    /**
     * @param \Magento\Eav\Setup\EavSetup $eavSetup
     */
    protected function deleteTjLastSyncAttribute($eavSetup)
    {
        $tjLastSync = $this->getTjLastSyncAttribute($eavSetup);

        if ($tjLastSync) {
            $eavSetup->removeAttribute(
                CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
                self::TJ_LAST_SYNC_CODE
            );
        }
    }
}
