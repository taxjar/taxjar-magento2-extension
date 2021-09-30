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

use Magento\Tax\Api\TaxRuleRepositoryInterface;

class Rule
{
    /**
     * @var RuleModelFactory
     */
    protected $ruleFactory;

    /**
     * @var TaxRuleRepositoryInterface
     */
    protected $ruleRepository;

    /**
     * @param RuleModelFactory $ruleFactory
     * @param TaxRuleRepositoryInterface $ruleRepository
     */
    public function __construct(
        RuleModelFactory $ruleFactory,
        TaxRuleRepositoryInterface $ruleRepository
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->ruleRepository = $ruleRepository;
    }

    /**
     * Create new tax rule based on code
     *
     * @param string $code
     * @param array $customerClasses
     * @param array $productClasses
     * @param integer $position
     * @param array $rates
     * @throws \Exception
     */
    public function create($code, $customerClasses, $productClasses, $position, $rates)
    {
        $existingRateIds = $rates;

        $ruleModel = $this->ruleFactory->create();
        $ruleModel->load($code, 'code');

        if ($existingRates = $ruleModel->getRates()) {
            $existingRateIds = array_merge($existingRates, $rates);
        }

        $ruleModel->setTaxRateIds(array_unique($existingRateIds));
        $ruleModel->setCode($code);
        $ruleModel->setCustomerTaxClassIds($customerClasses);
        $ruleModel->setProductTaxClassIds($productClasses);
        $ruleModel->setPosition($position);
        $ruleModel->setPriority(1);
        $ruleModel->setCalculateSubtotal(0);

        $this->ruleRepository->save($ruleModel);
        $this->saveCalculationData($ruleModel, $rates);

        return $ruleModel;
    }

    /**
     * @param $ruleModel
     * @param $rates
     */
    public function saveCalculationData($ruleModel, $rates)
    {
        $ctc = $ruleModel->getData('customer_tax_class_ids');
        $ptc = $ruleModel->getData('product_tax_class_ids');

        foreach ($ctc as $c) {
            foreach ($ptc as $p) {
                foreach ($rates as $r) {
                    $dataArray = [
                        'tax_calculation_rule_id' => $ruleModel->getId(),
                        'tax_calculation_rate_id' => $r,
                        'customer_tax_class_id' => $c,
                        'product_tax_class_id' => $p,
                    ];

                    $calculation = $ruleModel->getCalculationModel();

                    $calculationModel = $calculation->getCollection()
                        ->addFieldToFilter('tax_calculation_rate_id', $r)
                        ->addFieldToFilter('customer_tax_class_id', $c)
                        ->addFieldToFilter('product_tax_class_id', $p)
                        ->getFirstItem();

                    if ($calculationModel->getId()) {
                        $calculationModel->delete();
                    }

                    $calculation->setData($dataArray)->save();
                }
            }
        }
    }
}
