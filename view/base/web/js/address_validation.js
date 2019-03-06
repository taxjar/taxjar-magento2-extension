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
    'jquery',
    'Taxjar_SalesTax/js/suggested_addresses',
], function (ko, $, suggestedAddresses) {
    'use strict';

    console.log('address_validation');


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


                        if (window.isValidated === true) {
                            form.submit();
                        }

                        let addr = {
                            'street0': form.street_1.value,
                            'city': form.city.value,
                            'region': form.region_id.value,
                            'country': form.country_id.value,
                            'postcode': form.postcode.value
                        };

                        $.ajax({
                            type: 'POST',
                            url: '/rest/V1/Taxjar/address_validation/',  //TODO: make global variable
                            data: JSON.stringify(addr),
                            contentType: 'application/json; charset=utf-8',
                            dataType: 'json',
                            success: function (response) {
                                if (response.error === true) {
                                    suggestedAddresses.hideLoader(button);
                                    suggestedAddresses.displayError(response.error_msg);
                                    return;
                                }

                                let tj_suggested_addresses = $('#tj-suggested-addresses');
                                let addrHTML = suggestedAddresses.buildHtml(response, addr);

                                tj_suggested_addresses.html('');
                                tj_suggested_addresses.append('<ul>' + addrHTML + '</ul>');
                                $('#form-validate fieldset:nth-child(2) > div:last-child').after(tj_suggested_addresses);

                                suggestedAddresses.hideLoader(button);
                                window.isValidated = true;
                            },
                            failure: function (err) {
                                suggestedAddresses.hideLoader(button);
                                suggestedAddresses.displayError(err);
                            }
                        });
                    }
                });
            }
        });


        return $.mage.addressValidation;
    }
});
