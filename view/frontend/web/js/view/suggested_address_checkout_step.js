define([
        'ko',
        'jquery',
        'uiComponent',
        'mage/storage',
        'Magento_Checkout/js/model/step-navigator',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/shipping-rate-registry',
        'Taxjar_SalesTax/js/model/address_validation_core'
    ],
    function (ko, $, Component, storage, stepNavigator, quote, rateRegistry, avCore) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Taxjar_SalesTax/suggested_address_checkout_step',
                suggestedAddressTemplate: 'Taxjar_SalesTax/suggested_address_template',
                suggestedAddresses: avCore.suggestedAddresses,
                suggestedAddressRadio: ko.observable(0)
                //listens:
            },

            isVisible: function () {
                //TODO: hide Suggested Addresses when first loading the page
                return !quote.isVirtual() && !stepNavigator.isProcessed('shipping');
            },

            /**
             *
             * @returns {*}
             */
            initialize: function () {
                this._super();
                this.subscribeToSuggestedAddesses();
                this.subscribeToSuggestedAddressRadio();

                return this;
            },

            subscribeToSuggestedAddesses: function () {
                let self = this;
                this.suggestedAddresses.subscribe(function (newValue) {
                    self.suggestedAddressRadio(0);
                });
            },

            subscribeToSuggestedAddressRadio: function () {
                let self = this;
                this.suggestedAddressRadio.subscribe(function (id) {
                    self.updateQuoteAddress(id);
                });
            },

            getSuggestedAddressTemplate: function () {
                return this.suggestedAddressTemplate;
            },

            updateQuoteAddress: function (id) {
                let addrs = avCore.suggestedAddresses();

                if (addrs !== undefined) {
                    let newAddr = $.extend({}, quote.shippingAddress(), addrs[id].address);

                    // Force shipping rates to recalculate
                    // https://alanstorm.com/refresh-shipping-rates-for-the-magento-2-checkout/
                    rateRegistry.set(newAddr.getKey(), null);
                    rateRegistry.set(newAddr.getCacheKey(), null);

                    quote.shippingAddress(newAddr);
                }

                return true;
            },

            rearrangeSteps: function () {
                $('#shipping').after($('#address-validation'));
                $('#address-validation').show();
            }
        });
    }
);
