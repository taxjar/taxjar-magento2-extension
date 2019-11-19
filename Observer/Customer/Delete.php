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

use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;

class Delete extends Customer
{
    /**
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $customerId = $observer->getCustomer()->getId();

        if (!$customerId) {
            return;
        }

        try {
            $this->client->deleteResource('customers', $customerId);
        } catch (LocalizedException $e) {
            $this->logger->log('Could not delete customer #' . $customerId . ": " . $e->getMessage(), 'error');
        }
    }
}
