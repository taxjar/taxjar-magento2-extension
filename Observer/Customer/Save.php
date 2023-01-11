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

namespace Taxjar\SalesTax\Observer\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;

class Save extends Customer
{
    /**
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var CustomerInterface $customer */
        $customer = $observer->getCustomer();

        if ($customer === null || !$customer->getId()) {
            return;
        }

        $customerAddress = $customer->getAddresses() ?: [];
        $customerAddress = reset($customerAddress);

        try {
            $shippingAddressId = $customer->getDefaultShipping();

            if (!empty($shippingAddressId)) {
                $customerAddress = $this->addressRepository->getById($shippingAddressId);
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->log($e->getMessage());
        }

        // Null values are used to delete old address data
        $data = [
            'customer_id' => $customer->getId(),
            'exemption_type' => $customer->getCustomAttribute('tj_exemption_type')->getValue(),
            'name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
            'exempt_regions' => $this->getRegionsArray($customer->getCustomAttribute('tj_regions')->getValue()),
            'country' => null,
            'state' => null,
            'zip' => null,
            'city' => null,
            'street' => null
        ];

        if ($customerAddress) {
            $data = array_merge($data, [
                'country' => $customerAddress->getCountryId(),
                'zip' => $customerAddress->getPostcode(),
                'city' => $customerAddress->getCity(),
                'street' => implode(", ", $customerAddress->getStreet())
            ]);

            if (get_class($customerAddress) == \Magento\Customer\Model\Address::class) {
                $data['state'] = $customerAddress->getRegionCode();
            } elseif (get_class($customerAddress) == \Magento\Customer\Model\Data\Address::class) {
                $data['state'] = $customerAddress->getRegion()->getRegionCode();
            }
        }

        $response = $this->updateTaxjar($customer->getCustomAttribute('tj_last_sync')->getValue(), $data);

        if (isset($response)) {
            $this->logger->log('Successful API response: ' . json_encode($response), 'success');
            $customer->setData('tj_last_sync', $this->date->timestamp());
        }
    }
}
