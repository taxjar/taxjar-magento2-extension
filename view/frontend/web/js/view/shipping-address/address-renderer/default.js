define([
        'Magento_Checkout/js/action/select-shipping-address',
        'Magento_Checkout/js/checkout-data',
        'Taxjar_SalesTax/js/model/address_validation_core',
        'Magento_Checkout/js/model/shipping-address/form-popup-state',
    ],
    function (selectShippingAddressAction, checkoutData, avCore, formPopUpState) {
        'use strict';

        var mixin = {
            selectAddress: function (orig) {

                // start original code
                selectShippingAddressAction(this.address());
                checkoutData.setSelectedShippingAddress(this.address().getKey());
                // end original code

                // Call AV when switching between saved addresses
                avCore.getSuggestedAddresses();
            },
        };

        return function (target) {
            return target.extend(mixin);  // returns uiComponent
        };
    }
);