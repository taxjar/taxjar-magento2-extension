define([
    'ko',
    'jquery',
    'mage/storage',
    'Magento_Ui/js/modal/alert',
],
function (ko, $, storage, alert) {
    'use strict';

    return {
        suggestedAddresses: ko.observable([]),
        activeAddress: {},

        getAddressValidationUrl: function () {
            return '/rest/V1/Taxjar/address_validation/';
        },

        getSuggestedAddresses: function (addr, onDone, onFail) {
            var self = this;

            if (addr && addr.street && addr.city && addr.regionId) {
                var formattedAddr = {
                    'street0': addr.street[0],
                    'city': addr.city,
                    'region': addr.regionId,
                    'country': addr.countryId,
                    'postcode': addr.postcode
                };

                // Skip if non-US shipping address
                if (addr.countryId !== 'US') {
                    if (typeof onDone === 'function') {
                        onDone();
                    }
                    return;
                }

                // Skip if already suggested
                if (formattedAddr == this.activeAddress) {
                    if (typeof onDone === 'function') {
                        onDone();
                    }
                    return;
                }

                // Skip if selected address is a suggestion
                if (addr.custom_attributes && addr.custom_attributes.suggestedAddress) {
                    this.activeAddress = formattedAddr;
                    return;
                }

                $.ajax({
                    type: 'POST',
                    url: this.getAddressValidationUrl(),
                    data: JSON.stringify($.extend({}, { form_key: window.FORM_KEY }, formattedAddr)),
                    contentType: 'application/json; charset=utf-8',
                    dataType: 'json',
                    beforeSend: function (xhr) {
                        // Intentionally empty to prevent the form_key from being appended to the body of the request
                    }
                }).done(function (response) {
                    self.activeAddress = formattedAddr;
                    self.updateSuggestedAddresses(response);

                    if (typeof onDone === 'function') {
                        onDone(response);
                    }
                }).fail(function (response) {
                    alert({
                        title: $.mage.__('An error occurred'),
                        content: 'Unfortunately we were unable to validate your address.'
                    });

                    if (typeof onFail === 'function') {
                        onFail(response);
                    }
                });
            }
        },

        updateSuggestedAddresses: function (addr) {
            this.suggestedAddresses(addr);
        }
    };
});
