define([
        'Magento_Checkout/js/action/select-shipping-address',
        'Magento_Checkout/js/checkout-data',
        'Taxjar_SalesTax/js/model/address_validation_core',
        'Magento_Checkout/js/model/shipping-address/form-popup-state',
    ],
    function (selectShippingAddressAction, checkoutData, avCore, formPopUpState) {
        'use strict';

        var mixin = {
            initObservable: function () {
                this._super();
                avCore.getSuggestedAddresses();
                return this;
            },
            selectAddress: function (orig) {
                this._super();
                avCore.getSuggestedAddresses();
            }
        };

        return function (target) {
            return target.extend(mixin);
        };
    }
);
