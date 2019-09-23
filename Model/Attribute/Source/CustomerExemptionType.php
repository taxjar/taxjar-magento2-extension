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

namespace Taxjar\SalesTax\Model\Attribute\Source;

class CustomerExemptionType extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    const WHOLESALE = 'wholesale';
    const GOVERNMENT = 'government';
    const OTHER = 'other';
    const NONEXEMPT = 'non_exempt';

    /**
     * Get all options
     * @return array
     */
    public function getAllOptions()
    {
        if (!$this->_options) {
            $this->_options = [
                ['label' => __('Wholesale'), 'value' => $this::WHOLESALE],
                ['label' => __('Government'), 'value' => $this::GOVERNMENT],
                ['label' => __('Other'), 'value' => $this::OTHER],
                ['label' => __('Non-Exempt'), 'value' => $this::NONEXEMPT]
            ];
        }
        return $this->_options;
    }
}