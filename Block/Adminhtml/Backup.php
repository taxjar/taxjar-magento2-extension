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

namespace Taxjar\SalesTax\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Phrase;
use Taxjar\SalesTax\Model\Configuration;
use Taxjar\SalesTax\Model\Import\RateFactory;

class Backup extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Taxjar_SalesTax::backup.phtml';

    /**
     * @var \Magento\Framework\Config\CacheInterface
     */
    protected $cache;

    /**
     * @var \Taxjar\SalesTax\Model\Import\RateFactory
     */
    protected $rateFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Configuration
     */
    protected $taxjarConfig;

    /**
     * @param Context $context
     * @param CacheInterface $cache
     * @param RateFactory $rateFactory
     * @param Configuration $taxjarConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        CacheInterface $cache,
        RateFactory $rateFactory,
        Configuration $taxjarConfig,
        array $data = []
    ) {
        $this->cache = $cache;
        $this->rateFactory = $rateFactory;
        $this->scopeConfig = $context->getScopeConfig();
        $this->taxjarConfig = $taxjarConfig;

        parent::__construct($context, $data);
    }

    /**
     * Backup rates enabled check
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(Configuration::TAXJAR_BACKUP);
    }

    /**
     * @return Phrase
     */
    public function getRatesLoadedText(): Phrase
    {
        return __('%1 of %2 expected rates loaded.', $this->getActualRateCount(), $this->getExpectedRateCount());
    }

    /**
     * @return Phrase
     */
    public function getLastSyncedDateText(): Phrase
    {
        return __('Last synced on %1', $this->scopeConfig->getValue(Configuration::TAXJAR_LAST_UPDATE) ?? 'N/A');
    }

    /**
     * Get the element HTML
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        if ($this->taxjarConfig->getApiKey() !== null) {
            $this->_cacheElementValue($element);
        }

        return parent::_getElementHtml($element) . $this->_toHtml();
    }

    /**
     * Cache the element value
     *
     * @param AbstractElement $element
     * @return void
     */
    protected function _cacheElementValue(AbstractElement $element)
    {
        $elementValue = (string) $element->getValue();
        $this->cache->save($elementValue, 'taxjar_salestax_config_backup');
    }

    /**
     * @return int
     */
    protected function getActualRateCount(): int
    {
        $rateModel = $this->rateFactory->create();
        $rates = $rateModel->getExistingRates() ?? [];
        return count($rates) ?? 0;
    }

    /**
     * @return int
     */
    protected function getExpectedRateCount(): int
    {
        return $this->taxjarConfig->getBackupRateCount();
    }
}
