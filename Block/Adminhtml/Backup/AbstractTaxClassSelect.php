<?php

declare(strict_types=1);

namespace Taxjar\SalesTax\Block\Adminhtml\Backup;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Taxjar\SalesTax\Block\Adminhtml\Multiselect;
use Taxjar\SalesTax\Block\CachesConfiguration;

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
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct($context, $data, $secureRenderer);
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

    abstract function getSize(): int;

    abstract function getCacheIdentifier(): string;
}
