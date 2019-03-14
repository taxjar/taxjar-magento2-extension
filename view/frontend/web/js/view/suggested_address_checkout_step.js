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

        // Only extend the Component if validation` is enabled in the admin
        if (typeof(taxjar_validate_address) == 'undefined' || taxjar_validate_address !== true) {
            return Component;
        }

        return Component.extend({
            defaults: {
                template: 'Taxjar_SalesTax/suggested_address_checkout_step',
                suggestedAddressTemplate: 'Taxjar_SalesTax/suggested_address_template',
                suggestedAddresses: avCore.suggestedAddresses,
                suggestedAddressRadio: ko.observable(0)
            },

            isVisible: function () {
                return !quote.isVirtual() && !stepNavigator.isProcessed('shipping') && this.suggestedAddresses().length > 1;
            },

            /**
             *
             * @returns {*}
             */
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

                if (addrs !== undefined) {
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
                $('#shipping').after($('#address-validation'));
            },

            toggleDisplay: function () {
                if (this.isVisible()) {
                    $('#address-validation').show();
                } else {
                    $('#address-validation').hide();
                }
            }
        });
    }
);
