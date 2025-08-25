<?php

namespace Taxjar\SalesTax\Plugin;

use Magento\Sales\Model\ResourceModel\Order\Grid\Collection;

/**
 * TEMPORARY FIX for PayPal Braintree module double-prefixing bug
 *
 * Issue: PayPal Braintree v4.7.0 blindly adds main_table. prefix to created_at,
 * causing main_table.main_table.created_at when field is already prefixed.
 *
 * TODO: Remove this plugin when PayPal fixes the bug in their module
 * Check for updates: composer outdated paypal/module-braintree-core
 */
class FixBraintreeDoublePrefix
{
    /**
     * Fix double-prefixing issue caused by Braintree plugin
     * Runs AFTER Braintree plugin due to higher sortOrder
     *
     * @param Collection $subject
     * @param bool $printQuery
     * @param bool $logQuery
     * @return array
     */
    public function beforeLoad(Collection $subject, bool $printQuery = false, bool $logQuery = false): array
    {
        if (!$subject->isLoaded()) {
            $wherePart = $subject->getSelect()->getPart('where');
            if (!empty($wherePart)) {
                $needsUpdate = false;
                foreach ($wherePart as $key => $condition) {
                    // Fix double-prefixing: main_table.main_table.created_at -> main_table.created_at
                    if (str_contains($condition, '`main_table`.`main_table`.`created_at`')) {
                        $wherePart[$key] = str_replace(
                            '`main_table`.`main_table`.`created_at`',
                            '`main_table`.`created_at`',
                            $condition
                        );
                        $needsUpdate = true;
                    }
                }

                if ($needsUpdate) {
                    $subject->getSelect()->setPart('where', $wherePart);
                }
            }
        }

        return [$printQuery, $logQuery];
    }
}
