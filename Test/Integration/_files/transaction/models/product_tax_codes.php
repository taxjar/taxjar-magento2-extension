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
use Magento\Tax\Api\Data\TaxClassInterface;
use Magento\Tax\Api\TaxClassRepositoryInterface;

$objectManager = ObjectManager::getInstance();

/** @var \Magento\Tax\Api\Data\TaxClassInterface $taxClass */
$taxClass = $objectManager->create(TaxClassInterface::class);
$taxClass
    ->setClassName('Clothing')
    ->setClassType('PRODUCT')
    ->setTjSalestaxCode('20010');

/** @var \Magento\Tax\Api\TaxClassRepositoryInterface $taxClassRepository */
$taxClassRepository = $objectManager->create(TaxClassRepositoryInterface::class);

$ptcID = $taxClassRepository->save($taxClass);

