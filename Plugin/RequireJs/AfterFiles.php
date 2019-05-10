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
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;
use Taxjar\SalesTax\Model\Logger;

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
     * @var Logger
     */
    protected $logger;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param State $state
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        State $state,
        Logger $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->state = $state;
        $this->logger = $logger;
    }

    /**
     * @param Aggregated $subject
     * @param $result
     * @return mixed
     * @throws LocalizedException
     */
    public function afterGetFiles(
        Aggregated $subject,
        $result
    ) {
        $isEnabled = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_ADDRESS_VALIDATION);

        try {
            // If address validation is disabled, remove frontend RequireJs dependencies
            if (!$isEnabled && $this->state->getAreaCode() == 'frontend') {
                foreach ($result as $key => &$file) {
                    if ($file->getModule() == 'Taxjar_SalesTax') {
                        unset($result[$key]);
                    }
                }
            }
        } catch (LocalizedException $e) {
            $this->logger->log($e->getMessage());
        }

        return $result;
    }
}
