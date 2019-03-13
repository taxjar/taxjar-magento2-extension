define([
        'ko',
        'jquery',
        'mage/storage',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/modal/alert',
    ],
    function (ko, $, storage, quote, alert) {
        'use strict';

        return {
            suggestedAddresses: ko.observable([]),
            activeAddress: {},

            getAddressValidationUrl: function () {
                return '/rest/V1/Taxjar/address_validation/';
            },

            getSuggestedAddresses: function () {
                let self = this;
                let addr = quote.shippingAddress();

                if (addr && addr.street && addr.city && addr.regionId) {
                    let formattedAddr = JSON.stringify({
                        'street0': addr.street[0],
                        'city': addr.city,
                        'region': addr.regionId,
                        'country': addr.countryId,
                        'postcode': addr.postcode
                    });

                    // Skip if already suggested
                    if (formattedAddr == this.activeAddress) {
                        return;
                    }

                    // Skip if selected address is a suggestion
                    if (addr.custom_attributes && addr.custom_attributes.suggestedAddress) {
                        this.activeAddress = formattedAddr;
                        return;
                    }

                    // Post to the API and handle the response
                    storage.post(
                        this.getAddressValidationUrl(),
                        formattedAddr,
                        false
                    ).done(function (response) {
                        self.activeAddress = formattedAddr;
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
            }
        };
    }
);
