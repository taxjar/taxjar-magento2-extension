define([
        'ko',
        'jquery',
        'Magento_Checkout/js/action/create-shipping-address',
        'Magento_Checkout/js/action/select-shipping-address',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'Magento_Checkout/js/checkout-data',
        'Taxjar_SalesTax/js/model/address_validation_core',
    ], function (ko, $, createShippingAddress, selectShippingAddress, checkoutDataResolver, checkoutData, avCore) {
        'use strict';

        var mixin = {

            initialize: function () {
                this.isVisible = ko.observable(false);
                this._super();
                return this;
            },

            // called when directly typing #shipping into url bar
            navigate: function (step) {
                step && step.isVisible(true);
            },

            saveNewAddress: function () {
                // start original code
                var addressData,
                    newShippingAddress;

                this.source.set('params.invalid', false);
                this.triggerShippingDataValidateEvent();

                if (!this.source.get('params.invalid')) {
                    addressData = this.source.get('shippingAddress');
                    // if user clicked the checkbox, its value is true or false. Need to convert.
                    addressData['save_in_address_book'] = this.saveInAddressBook ? 1 : 0;

                    // New address must be selected as a shipping address
                    newShippingAddress = createShippingAddress(addressData);
                    selectShippingAddress(newShippingAddress);
                    checkoutData.setSelectedShippingAddress(newShippingAddress.getKey());
                    checkoutData.setNewCustomerShippingAddress($.extend(true, {}, addressData));
                    this.getPopUp().closeModal();
                    this.isNewAddressAdded(true);
                }
                // end original code

                // Call AV when adding or editing a new address
                avCore.getSuggestedAddresses();
            }
        };

        return function (target) {
            return target.extend(mixin);
        };
    }
);