<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Block\Adminhtml\Backup;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Taxjar\SalesTax\Block\Adminhtml\Multiselect;
use Taxjar\SalesTax\Block\CachesConfiguration;

/**
 * Abstract definition for HTML `<select>` element for displaying and caching tax classes.
 */
abstract class AbstractTaxClassSelect extends Multiselect
{
    use CachesConfiguration;

    /**
     * @var CacheInterface
     */
    protected $cache;

    public function __construct(
        CacheInterface $cache,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->cache = $cache;
    }

    /**
     * Get the element HTML
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $element->setSize($this->getSize());

        $this->onCache($this->cache)
            ->cacheValue(
                (string) $element->getValue(),
                $this->getCacheIdentifier()
            );

        return parent::_getElementHtml($element);
    }

    /**
     * Returns the multi-select element's length
     *
     * @return int
     */
    abstract public function getSize(): int;

    /**
     * Returns the cache key (aka identifier) for the data source
     *
     * @return string
     */
    abstract public function getCacheIdentifier(): string;
}
