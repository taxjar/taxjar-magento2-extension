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
    'uiRegistry',
    'Taxjar_SalesTax/js/modal',
    'Taxjar_SalesTax/js/model/address_validation_core'
], function (ko, $, uiRegistry, modal, avCore) {
    'use strict';

    return function (addressValidation) {
        if (!$('#tj-suggested-addresses').length) {
            return $.mage.addressValidation;
        }

        $.widget('mage.addressValidation', $.mage.addressValidation, {
            /**
             * Validation creation
             * @protected
             */
            _create: function () {
                var body = $('body');
                var button = $(this.options.selectors.button, this.element);
                var addressModal = $('#tj-suggested-addresses').modal({
                    buttons: [
                        {
                            text: $.mage.__('Edit Address'),
                            class: '',
                            click: function () {
                                this.closeModal();
                            }
                        },
                        {
                            text: $.mage.__('Save Address'),
                            class: 'action primary',
                            click: function () {
                                var addrs = avCore.suggestedAddresses();
                                var selectedAddressId = uiRegistry.get('addressValidation').suggestedAddressRadio();
                                var selectedAddress = addrs[selectedAddressId].address;

                                this.data.form.street_1.value = selectedAddress.street;
                                this.data.form.city.value = selectedAddress.city;
                                this.data.form.region_id.value = selectedAddress.regionId;
                                this.data.form.postcode.value = selectedAddress.postcode;
                                this.data.form.country_id.value = selectedAddress.countryId;
                                this.data.form.submit();
                            }
                        }
                    ]
                });

                this.element.validation({
                    /**
                     * Submit Handler
                     * @param {Element} form - address form
                     */
                    submitHandler: function (form) {
                        var addr = {
                            street: [form.street_1.value],
                            city: form.city.value,
                            regionId: form.region_id.value,
                            countryId: form.country_id.value,
                            postcode: form.postcode.value
                        };

                        button.attr('disabled', true);
                        body.trigger('processStart');

                        avCore.getSuggestedAddresses(addr, function (res) {
                            button.attr('disabled', false);
                            body.trigger('processStop');
                            addressModal.data('mage-modal').openModal({'form': form});
                        }, function (res) {
                            button.attr('disabled', false);
                            body.trigger('processStop');
                            form.submit();
                        });
                    }
                });
            }
        });

        return $.mage.addressValidation;
    };
});
