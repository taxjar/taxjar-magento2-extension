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
    'uiComponent',
    'uiRegistry',
    'taxjarModal',
    'Taxjar_SalesTax/js/model/address_validation_core'
], function (ko, $, Component, uiRegistry, taxjarModal, avCore) {
    'use strict';

    // Only extend the Component if validation is enabled in the admin
    if (typeof(taxjar_validate_address) == 'undefined' || taxjar_validate_address !== true) {
        return Component;
    }

    return Component.extend({
        defaults: {
            addressModal: {},
            addressButton: '#tj-validate-address-button',
            suggestedAddresses: avCore.suggestedAddresses || ko.observable([]),
            suggestedAddressRadio: ko.observable(0)
        },

        getTemplate: function () {
            return 'Taxjar_SalesTax/suggested_address_template';
        },

        isVisible: function () {
            return this.suggestedAddresses().length > 1;
        },

        initialize: function () {
            this._super();

            var self = this;

            this.addressModal = taxjarModal({
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
                            var button = $('[data-index="validateAddressButton"]:visible');
                            var form = button.closest('.address-item-edit-content fieldset, .order-shipping-address fieldset');

                            $(form).find('input[name*="[street][0]"]').val(selectedAddress.street);
                            $(form).find('input[name*="[city]"]').val(selectedAddress.city);
                            $(form).find('input[name*="[region_id]"]').val(selectedAddress.regionId);
                            $(form).find('input[name*="[postcode]"]').val(selectedAddress.postcode);
                            $(form).find('input[name*="[country_id]"]').val(selectedAddress.countryId);

                            this.closeModal();
                        }
                    }
                ]
            }, $('#tj-suggested-addresses'));

            if ($(this.addressButton).length) {
                var button = $(this.addressButton).clone();

                $(this.addressButton).remove();
                this.appendButton(button);

                if ('MutationObserver' in window) {
                    var observer = new MutationObserver(function(mutations) {
                        self.appendButton(button);
                    });

                    observer.observe($('#order-shipping_address').get(0), { childList: true });
                }
            }

            return this;
        },

        appendButton: function (button) {
            var self = this;

            $('#order-shipping_address_fields').find(button.attr('id')).remove();

            button.appendTo('#order-shipping_address_fields');

            $(this.addressButton).click(function (e) {
                e.preventDefault();
                self.validateAddress();
            });
        },

        validateAddress: function () {
            var self = this;
            var body = $('body');
            var button = $('[data-index="validateAddressButton"]:visible');
            var form = button.closest('.address-item-edit-content fieldset, .order-shipping-address fieldset');
            var formValues = $(form).serializeArray();
            var addr = {
                street: [this.getAddressFormValue(formValues, '[street][0]')],
                city: this.getAddressFormValue(formValues, '[city]'),
                regionId: this.getAddressFormValue(formValues, '[region_id]'),
                countryId: this.getAddressFormValue(formValues, '[country_id]'),
                postcode: this.getAddressFormValue(formValues, '[postcode]')
            };

            button.attr('disabled', true);
            body.trigger('processStart');

            avCore.getSuggestedAddresses(addr, function (res) {
                button.attr('disabled', false);
                body.trigger('processStop');

                if (uiRegistry.get('addressValidation').isVisible()) {
                    self.addressModal.openModal({ 'form': form });
                }
            }, function (res) {
                button.attr('disabled', false);
                body.trigger('processStop');

                if (uiRegistry.get('addressValidation').isVisible()) {
                    self.addressModal.openModal({ 'form': form });
                }
            });
        },

        getAddressFormValue: function (formValues, key) {
            for (var x = 0; x < formValues.length; x++) {
                if (formValues[x].name.indexOf(key) !== -1) {
                    return formValues[x].value;
                }
            }
        }
    });
});
