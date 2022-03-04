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

use Magento\Catalog\Model\Product\Type;
use Magento\TestFramework\ObjectManager;

$objectManager = ObjectManager::getInstance();

$productData = [
    [
        'type_id' => Type::TYPE_SIMPLE,
        'name' => 'Sprite Stasis Ball 65 cm',
        'sku' => '24-WG082-blue',
        'price' => 27.0,
        'sales_tax' => 2.2437,
        'weight' => 1,
    ],
    [
        'type_id' => Type::TYPE_SIMPLE,
        'name' => 'Sprite Foam Yoga Brick',
        'sku' => '24-WG084',
        'price' => 5.0,
        'sales_tax' => .4155,
        'weight' => 1,
    ],
    [
        'type_id' => Type::TYPE_SIMPLE,
        'name' => 'Sprite Yoga Strap 8 foot',
        'sku' => '24-WG086',
        'price' => 17.0,
        'sales_tax' => 1.4127,
        'weight' => 1,
    ],
    [
        'type_id' => Type::TYPE_SIMPLE,
        'name' => 'Sprite Foam Roller',
        'sku' => '24-WG088',
        'price' => 19.0,
        'sales_tax' => 1.5789,
        'weight' => 1,
    ]
];

$x = 1;
$products = [];

foreach ($productData as $data) {
    /** @var $product \Magento\Catalog\Model\Product */
    $product = $objectManager->create(\Magento\Catalog\Model\Product::class);

    $product->isObjectNew(true);
    $product->setTypeId($data['type_id'])
        ->setId($x++)
        ->setAttributeSetId(4)
        ->setWebsiteIds([1])
        ->setName($data['name'])
        ->setSku($data['sku'])
        ->setPrice($data['price'])
        ->setMetaTitle('meta title')
        ->setMetaKeyword('meta keyword')
        ->setMetaDescription('meta description')
        ->setShortDescription('Short description')
        ->setTaxClassId(0)
        ->setDescription('Description with <b>html tag</b>')
        ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
        ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
        ->setCanSaveCustomOptions(true)
        ->setHasOptions(false)
        ->setPriceView(1)
        ->setWeight($data['weight'])
        ->setStockData(
            [
                'use_config_manage_stock' => 1,
                'qty' => 100,
                'is_qty_decimal' => 0,
                'is_in_stock' => 1,
            ]
        );

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
    $productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
    $productRepository->save($product);

    $products[] = $product;
}
