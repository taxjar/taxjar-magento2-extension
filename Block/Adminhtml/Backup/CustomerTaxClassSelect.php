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

namespace Taxjar\SalesTax\Block\Adminhtml\Backup;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Taxjar\SalesTax\Block\Adminhtml\Multiselect;

/**
 * Definition of HTML `<select>` element for displaying and caching customer tax classes (CTCs).
 */
class CustomerTaxClassSelect extends Multiselect
{
    /**
     * Defines the cache identifier for customer tax classes (CTCs)
     * Used in determining if configuration has changed.
     */
    public const CACHE_IDENTIFIER = 'taxjar_salestax_backup_rates_ctcs';

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        $this->cache = $context->getCache();
        parent::__construct($context, $data);
    }

    /**
     * Get the element HTML
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $this->cache->save((string) $element->getValue(), self::CACHE_IDENTIFIER);
        return parent::_getElementHtml($element);
    }
}
