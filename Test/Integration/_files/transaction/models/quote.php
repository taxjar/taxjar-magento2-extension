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

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;

$addressData = include 'data/address_data.php';
$customerId = 0;
$storeId = 1;

/** @var ObjectManager $objectManager */
$objectManager = Bootstrap::getObjectManager();

/** @var Address $shippingAddress */
$shippingAddress = $objectManager->create(Address::class, ['data' => $addressData]);
$shippingAddress->setAddressType('shipping');

$billingAddress = clone $shippingAddress;
$billingAddress->setId(null)
    ->setAddressType('billing');

/** @var Quote $quote */
$quote = $objectManager->create(
    Quote::class,
    [
        'data' => [
            'customer_id' => $customerId,
            'store_id' => $storeId,
            'reserved_order_id' => 'tsg-123456789',
            'is_active' => true,
            'is_multishipping' => false
        ],
    ]
);
$quote->setShippingAddress($shippingAddress)
    ->setBillingAddress($billingAddress);

/** @var CartRepositoryInterface $repository */
$repository = $objectManager->get(CartRepositoryInterface::class);
$repository->save($quote);
