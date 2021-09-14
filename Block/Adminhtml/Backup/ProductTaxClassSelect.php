<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Block\Adminhtml\Backup;

class ProductTaxClassSelect extends AbstractTaxClassSelect
{
    protected const SELECT_LENGTH = 4;
    protected const CACHE_IDENTIFIER = 'taxjar_salestax_backup_rates_ptcs';

    function getSize(): int
    {
        return self::SELECT_LENGTH;
    }

    function getCacheIdentifier(): string
    {
        return self::CACHE_IDENTIFIER;
    }
}
