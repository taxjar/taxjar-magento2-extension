<?php

namespace Taxjar\SalesTax\Model\Import;

use Magento\Tax\Model\Calculation\Rule;
use Magento\Framework\Model\AbstractExtensibleModel;

class RuleModel extends Rule
{

    /**
     * Triggers after save events
     * Re-declared to prevent saveCalculationData from running after saving
     * saveCalculationData causes all rates to be re-associated with the rule in the database
     * Associating new rates already occurs in Taxjar\SalesTax\Model\Import\Rule::saveCalculationData
     * Only associating new rates greatly increases performance during large imports
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