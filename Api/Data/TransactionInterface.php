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
 * @copyright  Copyright (c) 2022 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Api\Data;

/**
 * Interface for syncable transactions.
 *
 * @api
 */
interface TransactionInterface
{
    /**
     * Return if transaction is syncable given current order/creditmemo state and store configuration.
     *
     * @return bool
     */
    public function canSync(): bool;

    /**
     * Return if transaction has previously synced to TaxJar.
     *
     * @return bool
     */
    public function hasSynced(): bool;

    /**
     * Determine if transaction should sync in current execution context.
     *
     * @param bool $force
     *
     * @return bool
     */
    public function shouldSync(bool $force = false): bool;

    /**
     * Return the pre-formatted API resource request body.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRequestBody(): array;

    /**
     * Return the corresponding API resource name.
     *
     * @return string
     */
    public function getResourceName(): string;

    /**
     * Return the corresponding API resource ID.
     *
     * @return string|null
     */
    public function getResourceId(): ?string;

    /**
     * Set TaxJar last synced date.
     *
     * @param string $datetime
     *
     * @return void
     */
    public function setLastSyncDate($datetime): void;
}
