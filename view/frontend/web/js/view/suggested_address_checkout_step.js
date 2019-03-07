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
                this._super();
                this.subscribeToSuggestedAddesses();
                this.subscribeToSuggestedAddressRadio();

                quote.shippingAddress.subscribe(function (address) {
                    if (!address.custom_attributes || !address.custom_attributes.suggestedAddress) {
                        avCore.getSuggestedAddresses();
                    }
                });

                return this;
            },

            subscribeToSuggestedAddesses: function () {
                let self = this;
                this.suggestedAddresses.subscribe(function (newValue) {
                    self.suggestedAddressRadio(0);

                    if (self.isVisible()) {
                        $('#address-validation').show();
                    }
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
                    let newAddr = $.extend({}, quote.shippingAddress(), addrs[id].address, { custom_attributes: { suggestedAddress: true } });

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
            }
        });
    }
);
