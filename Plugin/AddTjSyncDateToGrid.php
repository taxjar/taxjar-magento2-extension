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

namespace Taxjar\SalesTax\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Grid\Collection as CreditmemoGridCollection;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Order\Grid\Collection as OrderCreditmemoGridCollection;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;
use Taxjar\SalesTax\Api\Data\Sales\Order\Creditmemo\MetadataInterface as CreditmemoMetadataInterface;
use Taxjar\SalesTax\Api\Data\Sales\Order\MetadataInterface as OrderMetadataInterface;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Creditmemo\Metadata as CreditmemoMetadataResource;
use Taxjar\SalesTax\Model\ResourceModel\Sales\Order\Metadata as OrderMetadataResource;

class AddTjSyncDateToGrid
{
    /**
     * Join TaxJar sync date to sales grid collections.
     *
     * @param $subject
     * @param bool $printQuery
     * @param bool $logQuery
     * @return array
     * @throws LocalizedException
     */
    public function beforeLoad($subject, bool $printQuery = false, bool $logQuery = false): array
    {
        if (!$subject->isLoaded()) {
            $table = $this->_getTable($subject);
            $foreignKey = $this->_getForeignKey($table);
            $column = $this->_getColumn($table);

            if ($foreignKey !== null && $column !== null) {
                $primaryKey = $subject->getResource()->getIdFieldName();
                $tableName = $subject->getResource()->getTable($table);

                $subject->getSelect()->joinLeft(
                    $tableName,
                    $tableName . '.' . $foreignKey . ' = main_table.' . $primaryKey,
                    $tableName . '.' . $column
                );
            }
        }

        return [$printQuery, $logQuery];
    }

    /**
     * Get TaxJar table name based on collection resource type.
     *
     * @param \Magento\Framework\Data\Collection $subject
     * @return string|null
     */
    private function _getTable($subject): ?string
    {
        if ($subject instanceof OrderGridCollection) {
            return OrderMetadataResource::TABLE;
        } elseif ($subject instanceof CreditmemoGridCollection) {
            return CreditmemoMetadataResource::TABLE;
        } elseif ($subject instanceof OrderCreditmemoGridCollection) {
            return CreditmemoMetadataResource::TABLE;
        } else {
            return null;
        }
    }

    /**
     * Determine foreign key column to join on based on TaxJar table.
     *
     * @param string|null $table
     * @return string|null
     */
    private function _getForeignKey(?string $table): ?string
    {
        switch ($table) {
            case $table === OrderMetadataResource::TABLE:
                return OrderMetadataInterface::ORDER_ID;
            case $table === CreditmemoMetadataResource::TABLE:
                return CreditmemoMetadataInterface::CREDITMEMO_ID;
            default:
                return null;
        }
    }

    /**
     * Determine column to return from join based on TaxJar table.
     *
     * @param string|null $table
     * @return string|null
     */
    private function _getColumn(?string $table): ?string
    {
        switch ($table) {
            case $table === OrderMetadataResource::TABLE:
                return OrderMetadataInterface::SYNCED_AT;
            case $table === CreditmemoMetadataResource::TABLE:
                return CreditmemoMetadataInterface::SYNCED_AT;
            default:
                return null;
        }
    }
}
