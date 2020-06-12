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
use Magento\Customer\Model\Customer;

$customerData = include 'data/customer_data.php';

$objectManager = ObjectManager::getInstance();

/** @var Customer $customer */
$customer = $objectManager->create(Customer::class);
$customer->setWebsiteId(1)
    ->setEmail($customerData['email'])
    ->setPassword('password')
    ->setGroupId(1)
    ->setStoreId(1)
    ->setIsActive(1)
    ->setPrefix('Dr')
    ->setFirstname($customerData['firstname'])
    ->setMiddlename('')
    ->setLastname($customerData['lastname'])
    ->setSuffix('Esq.')
    ->setTaxvat('12')
    ->setGender(0)
    ->setId(1);

$customer->save();
