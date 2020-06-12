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
 * @copyright  Copyright (c) 2020 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

use Magento\TestFramework\ObjectManager;

$productData = [
    [
        'type_id' => \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE,
        'name' => 'Sprite Yoga Companion Kit',
        'sku' => '24-WG080',
        'price' => 68.0,
        'sales_tax' => 5.65,
        'weight' => 1,
    ]
];

$objectManager = ObjectManager::getInstance();
$id = 0;

foreach($productData as $data) {
    /** @var $bundleProduct \Magento\Catalog\Model\Product */
    $bundleProduct = $objectManager->create(\Magento\Catalog\Model\Product::class);

    $bundleProduct->isObjectNew(true);
    $bundleProduct->setTypeId($data['type_id'])
        ->setId($id++)
        ->setAttributeSetId(4)
        ->setWebsiteIds([1])
        ->setName($data['name'])
        ->setSku($data['sku'])
        ->setPrice($data['price'])
        ->setWeight($data['weight'])
        ->setShortDescription('Short description')
        ->setTaxClassId(0)
        ->setDescription('Description with <b>html tag</b>')
        ->setMetaTitle('meta title')
        ->setMetaKeyword('meta keyword')
        ->setMetaDescription('meta description')
        ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
        ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
        ->setCanSaveCustomOptions(true)
        ->setHasOptions(false)
        ->setStockData(
            [
                'use_config_manage_stock' => 1,
                'qty' => 100,
                'is_qty_decimal' => 0,
                'is_in_stock' => 1,
            ]
        );

        //TODO: missing bundle options

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
    $productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
    $productRepository->save($bundleProduct);
    $products[] = $bundleProduct;
}
