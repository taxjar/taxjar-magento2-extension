<?php

namespace Taxjar\SalesTax\Model\Import;

use Magento\Tax\Model\Calculation\Rule;
use Magento\Framework\Model\AbstractExtensibleModel;

/**
 * Class used to override Magento's Tax module's native `Rule::afterSave` method
 * and prevent `Rule::saveCalculationData` from being executed on save.
 */
class RuleModel extends Rule
{
    /**
     * This method explicitly does not call `::saveCalculationData` like the native
     * Magento Tax's Rule class that this class extends from.
     *
     * TaxJar's tax extension manages backup rates asynchronously, and since calling
     * `saveCalculationData` re-associates Rates with the Rule (by re-creating Calculations),
     * without bypassing the method call, existing calculations would be deleted when
     * new calculations are added.
     *
     * @return $this
     */
    public function afterSave()
    {
        AbstractExtensibleModel::afterSave();
        $this->_eventManager->dispatch('tax_settings_change_after');
        return $this;
    }
}
