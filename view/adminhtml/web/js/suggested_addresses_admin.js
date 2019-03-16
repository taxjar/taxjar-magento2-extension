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

    return Component.extend({
        defaults: {
            addressModal: {},
            suggestedAddresses: avCore.suggestedAddresses || ko.observable([]),
            suggestedAddressRadio: ko.observable(0)
        },

        getTemplate: function () {
            return 'Taxjar_SalesTax/suggested_address_template';
        },

        isVisible: function () {
            return this.suggestedAddresses().length > 1;
        },

        initialize: function (config) {
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
                            var forms = [this.data.form];

                            if (window.order && window.order.shippingAsBilling) {
                                forms.push($('.order-shipping-address > fieldset'));
                            }

                            for (var x = 0; x < forms.length; x++) {
                                forms[x].find('input[name*="[street][0]"]').val(selectedAddress.street);
                                forms[x].find('input[name*="[city]"]').val(selectedAddress.city);
                                forms[x].find('input[name*="[region_id]"]').val(selectedAddress.regionId);
                                forms[x].find('input[name*="[postcode]"]').val(selectedAddress.postcode);
                                forms[x].find('input[name*="[country_id]"]').val(selectedAddress.countryId);
                            }

                            this.closeModal();
                        }
                    }
                ]
            }, $('#tj-suggested-addresses'));

            if (config.controller === 'order_create') {
                var button = $('<button class="action-basic" data-index="validateAddressButton">Validate Address</button>');

                this.appendButtonToOrder(button);

                if ('MutationObserver' in window) {
                    var observer = new MutationObserver(function(mutations) {
                        self.appendButtonToOrder(button);
                    });

                    observer.observe($('#order-shipping_address').get(0), {
                        attributes: true
                    });
                }
            }

            return this;
        },

        appendButtonToOrder: function (button) {
            var self = this;

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
