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
 * @copyright  Copyright (c) 2017 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc.
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Taxjar\SalesTax\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Request\Http;
use Magento\Store\Model\ScopeInterface;
use Taxjar\SalesTax\Model\Configuration as TaxjarConfig;

class Data extends AbstractHelper
{
    protected $request;

    /**
     * @param Context $context
     * @param Http $request
     */
    public function __construct(Context $context, Http $request)
    {
        $this->request = $request;
        parent::__construct($context);
    }

    /**
     * Sort a multidimensional array by key
     *
     * @param array $array
     * @param string $on
     * @param const $order
     * @return array
     */
    public function sortArray($array, $on, $order = SORT_ASC)
    {
        $newArray = [];
        $sortableArray = [];

        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortableArray[$k] = $v2;
                        }
                    }
                } else {
                    $sortableArray[$k] = $v;
                }
            }

            switch ($order) {
                case SORT_ASC:
                    asort($sortableArray);
                    break;
                case SORT_DESC:
                    arsort($sortableArray);
                    break;
            }

            foreach ($sortableArray as $k => $v) {
                $newArray[$k] = $array[$k];
            }
        }

        return $newArray;
    }

    /**
     * Transaction Sync enabled check
     *
     * @param int $scopeCode
     * @param string $scope
     * @return bool
     */
    public function isTransactionSyncEnabled($scopeCode = 0, $scope = ScopeInterface::SCOPE_STORE)
    {
        $scopeCode = $scopeCode ?: (int) $this->request->getParam($scope, 0);
        $syncEnabled = $this->scopeConfig->getValue(TaxjarConfig::TAXJAR_TRANSACTION_SYNC, $scope, $scopeCode);
        return (bool) $syncEnabled;
    }
}
