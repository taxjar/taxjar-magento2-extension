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
    'Magento_Checkout/js/model/shipping-rate-registry',
    'Taxjar_SalesTax/js/model/address_validation_core'
],
function (
    ko,
    $,
    Component,
    stepNavigator,
    quote,
    rateRegistry,
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
            return !quote.isVirtual() && this.checkStepNavigator() && this.suggestedAddresses().length && this.validatedAddresses().length !== this.suggestedAddresses().length;
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

            quote.shippingAddress.subscribe(function (address) {
                avCore.getSuggestedAddresses(quote.shippingAddress());
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
                self.suggestedAddressRadio(0);
                self.toggleDisplay();
            });
        },

        subscribeToSuggestedAddressRadio: function () {
            var self = this;
            this.suggestedAddressRadio.subscribe(function (id) {
                self.updateQuoteAddress(id);
            });
        },

        getSuggestedAddressTemplate: function () {
            return this.suggestedAddressTemplate;
        },

        updateQuoteAddress: function (id) {
            var addrs = avCore.suggestedAddresses();

            if (addrs && addrs.length) {
                var newAddr = $.extend({}, quote.shippingAddress(), addrs[id].address, { custom_attributes: { suggestedAddress: true } });

                // Force shipping rates to recalculate
                // https://alanstorm.com/refresh-shipping-rates-for-the-magento-2-checkout/
                rateRegistry.set(newAddr.getKey(), null);
                rateRegistry.set(newAddr.getCacheKey(), null);

                quote.shippingAddress(newAddr);
                quote.billingAddress(newAddr);
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
