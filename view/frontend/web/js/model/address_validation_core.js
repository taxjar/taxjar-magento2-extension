define([
        'ko',
        'jquery',
        'mage/storage',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/modal/alert',
    ],
    function (ko, $, storage, quote, alert) {
        'use strict';

        var suggestedAddresses = ko.observable([]);

        return {

            suggestedAddresses: suggestedAddresses,

            getAddressValidationUrl: function () {
                return '/rest/V1/Taxjar/address_validation/';
            },

            getSuggestedAddresses: function () {
                var self = this;
                let addr = quote.shippingAddress();

                if (addr !== null) {
                    addr = {
                        'street0': addr.street[0],
                        'city': addr.city,
                        'region': addr.regionId,
                        'country': addr.countryId,
                        'postcode': addr.postcode
                    };

                    storage.post(
                        this.getAddressValidationUrl(),
                        JSON.stringify(addr),
                        false
                    ).done(function (response) {
                        self.updateSuggestedAddresses(response);
                    }).fail(function (response) {
                        alert({
                            title: $.mage.__('An error occurred'),
                            content: 'Unfortunately we were unable to validate your address.'
                        });
                    });
                }
            },

            updateSuggestedAddresses: function (addr) {
                this.suggestedAddresses(addr);
            },

        };
    }
);
