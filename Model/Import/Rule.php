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

namespace Taxjar\SalesTax\Model\Import;

use Magento\Tax\Model\Calculation\RuleFactory as CalculationRuleFactory;

class Rule
{
    /**
     * @var \Magento\Tax\Model\Calculation\RuleFactory
     */
    protected $ruleFactory;

    /**
     * @param CalculationRuleFactory $ruleFactory
     */
    public function __construct(
        CalculationRuleFactory $ruleFactory
    ) {
        $this->ruleFactory = $ruleFactory;
        return $this;
    }

    /**
     * Create new tax rule based on code
     *
     * @param string $code
     * @param array $customerClasses
     * @param array $productClasses
     * @param integer $position
     * @param array $rates
     * @return void
     */
    public function create($code, $customerClasses, $productClasses, $position, $rates)
    {
        $rule = $this->ruleFactory->create();
        $ruleModel = $this->ruleFactory->create();
        $rule->load($code, 'code');

        if (isset($rule)) {
            $ruleModel->setTaxRateIds(array_merge($rule->getRates(), $rates));
            $rule->delete();
        } else {
            $ruleModel->setTaxRateIds($rates);
        }

        $ruleModel->setCode($code);
        $ruleModel->setCustomerTaxClassIds($customerClasses);
        $ruleModel->setProductTaxClassIds($productClasses);
        $ruleModel->setPosition($position);
        $ruleModel->setPriority(1);
        $ruleModel->setCalculateSubtotal(0);
        $ruleModel->save();
        $ruleModel->saveCalculationData();
    }
}
