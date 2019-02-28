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
    'ko',
    'jquery'
], function (ko, $) {
    'use strict';

    //TODO: is this file still needed
    console.log('address_validation.js still being used!');

    return function (addressValidation) {

        if (taxjar_validate_address !== true) {
            return $.mage.addressValidation;
        }

        window.isValidated = false;

        $.widget('mage.addressValidation', {
            options: {
                selectors: {
                    button: '[data-action=save-address]'
                }
            },

            /**
             * Validation creation
             * @protected
             */
            _create: function () {

                let body = $('body');
                let button = $(this.options.selectors.button, this.element);

                this.element.validation({

                    /**
                     * Submit Handler
                     * @param {Element} form - address form
                     */
                    submitHandler: function (form) {
                        button.attr('disabled', true);
                        body.trigger('processStart');
                    }

                });
            }
        });


        return $.mage.addressValidation;
    }
});
