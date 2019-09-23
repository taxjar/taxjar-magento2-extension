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
    'Magento_Ui/js/modal/alert',
    'Taxjar_SalesTax/js/modal',
    'Taxjar_SalesTax/js/model/address_validation_core'
], function (ko, $, Component, uiRegistry, alert, modal, avCore) {
    'use strict';

    return Component.extend({
        defaults: {
            addressModal: {},
            suggestedAddresses: avCore.suggestedAddresses || ko.observable([]),
            suggestedAddressRadio: ko.observable(0),
            validatedAddresses: ko.computed(function () {
                return ko.utils.arrayFilter(avCore.suggestedAddresses(), function (addr) {
                    return addr.address.custom_attributes && addr.address.custom_attributes.suggestedAddress === true;
                });
            })
        },

        getTemplate: function () {
            return 'Taxjar_SalesTax/suggested_address_template';
        },

        initialize: function (config) {
            this._super();

            var self = this;

            this.addressModal = $('#tj-suggested-addresses').modal({
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
                            var forms = [this.data.form];
                            var formScope = 'customer_address_form.customer_address_form.general';

                            if (window.order && window.order.shippingAsBilling) {
                                forms.push($('.order-shipping-address > fieldset'));
                            }

                            for (var x = 0; x < forms.length; x++) {
                                forms[x].find('input[name*="[street][0]"]').val(selectedAddress.street);
                                forms[x].find('input[name*="[city]"]').val(selectedAddress.city);
                                forms[x].find('select[name*="[region_id]"]').val(selectedAddress.regionId);
                                forms[x].find('input[name*="[postcode]"]').val(selectedAddress.postcode);
                                forms[x].find('select[name*="[country_id]"]').val(selectedAddress.countryId);
                            }

                            if (uiRegistry.get(formScope)) {
                                uiRegistry.get(formScope + '.street.street_0').value(selectedAddress.street[0]);
                                uiRegistry.get(formScope + '.city').value(selectedAddress.city);
                                uiRegistry.get(formScope + '.region_id').value(selectedAddress.regionId);
                                uiRegistry.get(formScope + '.country_id').value(selectedAddress.countryId);
                                uiRegistry.get(formScope + '.postcode').value(selectedAddress.postcode);
                            }

                            this.closeModal();
                        }
                    }
                ]
            });

            if (config.controller === 'order_create') {
                this.appendButtonToOrder();

                if ('MutationObserver' in window) {
                    var orderObserver = new MutationObserver(function (mutations) {
                        var addressObserver = new MutationObserver(function (mutations) {
                            self.appendButtonToOrder();
                            orderObserver.disconnect();
                        });

                        self.appendButtonToOrder();

                        if ($('.order-billing-address, .order-shipping-address').length) {
                            addressObserver.observe($('.order-billing-address').get(0), {childList: true});
                            addressObserver.observe($('.order-shipping-address').get(0), {childList: true});
                        }
                    });

                    orderObserver.observe($('#order-data').get(0), {childList: true});
                }
            }

            return this;
        },

        appendButtonToOrder: function () {
            var self = this;
            var button = $('<button type="button" class="action-basic" data-index="validateAddressButton">Validate Address</button>');

            $('[data-index="validateAddressButton"]:visible').remove();

            button.unbind('click').bind('click', function (e) {
                e.preventDefault();
                self.validateAddress($(this));
            });

            if (window.order && window.order.shippingAsBilling) {
                button.appendTo('.order-billing-address > fieldset');
            } else {
                button.appendTo('.order-billing-address > fieldset, .order-shipping-address > fieldset');
            }
        },

        validateAddress: function (button) {
            var self = this;
            var body = $('body');
            button = button || $('[data-index="validateAddressButton"]:visible');
            var form = button.closest('fieldset');
            var formScope = 'customer_address_form.customer_address_form.general';
            var formValues = $(form).serializeArray();
            var addr = {};

            if (uiRegistry.get(formScope)) {
                addr = {
                    street: [uiRegistry.get(formScope + '.street.street_0').value()],
                    city: uiRegistry.get(formScope + '.city').value(),
                    regionId: uiRegistry.get(formScope + '.region_id').value(),
                    countryId: uiRegistry.get(formScope + '.country_id').value(),
                    postcode: uiRegistry.get(formScope + '.postcode').value()
                };
            } else {
                addr = {
                    street: [this.getAddressFormValue(formValues, '[street][0]')],
                    city: this.getAddressFormValue(formValues, '[city]'),
                    regionId: this.getAddressFormValue(formValues, '[region_id]'),
                    countryId: this.getAddressFormValue(formValues, '[country_id]'),
                    postcode: this.getAddressFormValue(formValues, '[postcode]')
                };
            }

            button.attr('disabled', true);
            body.trigger('processStart');

            avCore.getSuggestedAddresses(addr, function (res) {
                button.attr('disabled', false);
                body.trigger('processStop');
                self.addressModal.data('mage-modal').openModal({'form': form});
            }, function (res) {
                button.attr('disabled', false);
                body.trigger('processStop');

                if (res === 'NON_US_SHIPPING_ADDRESS') {
                    return alert({
                        title: $.mage.__('Address Validation'),
                        content: $.mage.__('At this time only US addresses can be validated.')
                    });
                }

                if (res === 'MISSING_ADDRESS_FIELDS') {
                    return alert({
                        title: $.mage.__('Address Validation'),
                        content: $.mage.__('Please provide a street address, city, and state before validating an address.')
                    });
                }

                if (res === 'ADDRESS_ALREADY_VALIDATED') {
                    return alert({
                        title: $.mage.__('Address Validation'),
                        content: $.mage.__('This address has already been validated.')
                    });
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
