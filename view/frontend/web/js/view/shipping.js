define([
        'ko',
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/address-list'
    ], function (
        ko,
        $,
        quote,
        addressList
    ) {
        'use strict';

        var mixin = {
            validateShippingInformation: function () {
                if (addressList().length === 0) {
                    let originalAddress = quote.shippingAddress();

                    this.source.set('shippingAddress.street.0', originalAddress.street[0]);
                    this.source.set('shippingAddress.city', originalAddress.city);
                    this.source.set('shippingAddress.region_id', originalAddress.regionId);
                    this.source.set('shippingAddress.postcode', originalAddress.postcode);
                    this.source.set('shippingAddress.country_id', originalAddress.countryId);
                }

                return this._super();
            }
        };

        return function (target) {
            return target.extend(mixin);
        };
    }
);
