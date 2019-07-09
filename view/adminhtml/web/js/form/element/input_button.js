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

define([
    'Magento_Ui/js/form/element/abstract',
    'uiRegistry',
    'mageUtils'
], function(Abstract, registry, utils) {
    return Abstract.extend({
        defaults: {
            visible: true,
            disabled: false,
            title: ''
        },

        /**
         * Initializes component, invokes initialize method of Abstract class.
         *
         *  @returns {Object} Chainable.
         */
        initialize: function () {
            return this._super();
        },

        /**
         * Init observables
         *
         * @returns {Object} Chainable.
         */
        initObservable: function () {
            return this._super()
                .observe([
                    'visible',
                    'disabled',
                    'title',
                    'childError'
                ]);
        },

        /**
         * Performs configured actions
         */
        action: function () {
            this.actions.forEach(this.applyAction, this);
        },

        /**
         * Apply action on target component
         *
         * @param {Object} action - action configuration
         */
        applyAction: function (action) {
            var targetName = action.targetName,
                params = utils.copy(action.params) || [],
                actionName = action.actionName,
                target;

            target = registry.async(targetName);

            if (target && typeof target === 'function' && actionName) {
                params.unshift(actionName);
                target.apply(target, params);
            }
        }
    });
});
