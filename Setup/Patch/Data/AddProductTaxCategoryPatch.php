<?php

namespace Taxjar\SalesTax\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Taxjar\SalesTax\Model\Attribute\Source\Category;

class AddProductTaxCategoryPatch implements DataPatchInterface, PatchRevertableInterface
{
    public const TJ_PTC_CODE = 'tj_ptc';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $tjPtcAttribute = $eavSetup->getAttribute(
            Product::ENTITY,
            self::TJ_PTC_CODE
        );

        if (!$tjPtcAttribute) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                self::TJ_PTC_CODE,
                [
                    'group' => 'General',
                    'type' => 'text',
                    'label' => 'TaxJar Category',
                    'input' => 'select',

                    'required' => false,
                    'visible' => true,
                    'user_defined' => true,
                    'position' => 504,
                    'system' => 0,
                    'sort_order' => 54,

                    'source' => Category::class,
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,

                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_html_allowed_on_front' => false,
                    'visible_on_front' => false
                ]
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [
            ImportTaxCategoriesPatch::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $tjPtcAttribute = $eavSetup->getAttribute(
            Product::ENTITY,
            self::TJ_PTC_CODE
        );

        if ($tjPtcAttribute) {
            $eavSetup->removeAttribute(
                Product::ENTITY,
                self::TJ_PTC_CODE
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }
}
