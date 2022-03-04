<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Block\Adminhtml\Backup;

/**
 * Definition of HTML `<select>` element for displaying and caching product tax classes (PTCs).
 */
class ProductTaxClassSelect extends AbstractTaxClassSelect
{
    /**
     * Defines the "size" of the HTML select element
     */
    protected const SELECT_LENGTH = 4;

    /**
     * Defines the cache identifier for customer tax classes (CTCs)
     */
    protected const CACHE_IDENTIFIER = 'taxjar_salestax_backup_rates_ptcs';

    /**
     * Returns the multi-select element's length
     *
     * @return int
     */
    public function getSize(): int
    {
        return self::SELECT_LENGTH;
    }

    /**
     * Returns the cache key (aka identifier) for the data source
     *
     * @return string
     */
    public function getCacheIdentifier(): string
    {
        return self::CACHE_IDENTIFIER;
    }
}
