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
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/quote',
    'uiRegistry',
    'Taxjar_SalesTax/js/model/address_validation_core'
],
function (
    ko,
    $,
    Component,
    stepNavigator,
    quote,
    registry,
    avCore
) {
    'use strict';

    return Component.extend({
        defaults: {
            suggestedAddresses: avCore.suggestedAddresses,
            suggestedAddressRadio: ko.observable(0),
            validatedAddresses: ko.computed(function() {
                return ko.utils.arrayFilter(avCore.suggestedAddresses(), function(addr) {
                    return addr.address && addr.address.custom_attributes && addr.address.custom_attributes.suggestedAddress === true;
                });
            })
        },

        isVisible: function () {
            return !quote.isVirtual() && this.checkStepNavigator() && this.suggestedAddresses().length;
        },

        isOneStepCheckout: function () {
            return $('.am-checkout, .aw-onestep').length;
        },

        checkStepNavigator: function () {
            if (this.isOneStepCheckout()) {
                return true;
            }

            return !stepNavigator.isProcessed('shipping');
        },

        initialize: function () {
            var self = this;

            this._super();
            this.subscribeToSuggestedAddesses();
            this.subscribeToSuggestedAddressRadio();

            quote.shippingAddress.subscribe(function () {
                var quote_address = quote.shippingAddress();
                var store_code = quote.getStoreCode();

                avCore.getSuggestedAddresses({
                    country_id: quote_address.countryId,
                    region_id: quote_address.regionId,
                    postcode: quote_address.postcode,
                    city: quote_address.city,
                    street: quote_address.street,
                    store_code: store_code
                });
            });

            quote.shippingMethod.subscribe(function () {
                self.rearrangeSteps();
            });

            $(window).on('hashchange', function () {
                self.toggleDisplay();
            });

            return this;
        },

        subscribeToSuggestedAddesses: function () {
            var self = this;
            this.suggestedAddresses.subscribe(function (newValue) {
                if (avCore.isRefresh) {
                    self.suggestedAddressRadio(0);
                    avCore.isRefresh = false;
                } else {
                    self.suggestedAddressRadio();
                }

                self.toggleDisplay();
            });
        },

        subscribeToSuggestedAddressRadio: function () {
            var self = this;
            this.suggestedAddressRadio.subscribe(function (id) {
                self.updateQuoteAddress(id);
            });
        },

        updateQuoteAddress: function (id) {
            var addrs = avCore.suggestedAddresses();

            if (addrs && addrs.length) {
                var checkoutProvider = registry.get('checkoutProvider');
                var originalAddress = $.extend({}, checkoutProvider.get('shippingAddress'));

                originalAddress.city = addrs[id].address.city;
                originalAddress.region_id = addrs[id].address.regionId;
                originalAddress.country_id = addrs[id].address.countryId;
                originalAddress.postcode = addrs[id].address.postcode;

                if (originalAddress.telephone == null || originalAddress.telephone === '') {
                    originalAddress.telephone = addrs[id].address.telephone ?? quote.shippingAddress().telephone;
                }

                addrs[id].address.street.forEach(function(item, index) {
                   originalAddress.street[index] = item;
                });

                if (id !== 0) {
                    originalAddress.taxjar_attributes = {
                        suggestedAddress: true
                    };
                } else {
                    originalAddress.taxjar_attributes = {
                        suggestedAddress: false
                    };
                }

                // street address does not update when updating the checkout provider shipping address
                // that input must be updated individually
                $('.form-shipping-address input[name="street[0]"]').val(originalAddress.street[0]);

                checkoutProvider.set('shippingAddress', originalAddress);
                checkoutProvider.trigger('shippingAddress', originalAddress);
            }

            return true;
        },

        rearrangeSteps: function () {
            $('#shipping, .onestep-shipping-address').after($('#address-validation'));
        },

        toggleDisplay: function () {
            if (this.isVisible()) {
                $('#address-validation').show();
            } else {
                $('#address-validation').hide();
            }
        }
    });
});
