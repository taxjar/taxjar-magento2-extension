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

namespace Taxjar\SalesTax\Plugin\RequireJs;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\RequireJs\Config\File\Collector\Aggregated;
use Magento\Theme\Model\Theme;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class AfterFiles
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var State
     */
    protected $state;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param State $state
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        State $state
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->state = $state;
    }

    /**
     * @param Aggregated $subject
     * @param array $result
     * @param Theme $theme
     * @return mixed
     * @throws LocalizedException
     */
    public function afterGetFiles(
        Aggregated $subject,
        $result,
        Theme $theme
    ) {
        $isEnabled = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_ADDRESS_VALIDATION);
        $areaCode = '';

        try {
            $areaCode = $theme->getArea();
        } catch (LocalizedException $e) {
            // no-op
        }

        // If address validation is disabled, remove frontend RequireJs dependencies
        if (!$isEnabled && $areaCode == 'frontend') {
            foreach ($result as $key => &$file) {
                if ($file->getModule() == 'Taxjar_SalesTax') {
                    unset($result[$key]);
                }
            }
        }

        return $result;
    }
}
