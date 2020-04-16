/**
 * Taxjar_SalesTax
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Taxjar
 * @package    Taxjar_SalesTax
 * @copyright  Copyright (c) 2017 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

define([
    'ko',
    'jquery'
],
function (ko, $) {
    'use strict';

    return {
        suggestedAddresses: ko.observable([]),
        activeAddress: {},

        getAddressValidationUrl: function () {
            return '/rest/V1/Taxjar/address_validation/';
        },

        getSuggestedAddresses: function (addr, onDone, onFail) {
            var self = this;

            // Skip if non-US shipping address
            if (addr && addr.countryId !== 'US') {
                self.updateSuggestedAddresses([]);

                if (typeof onFail === 'function') {
                    onFail('NON_US_SHIPPING_ADDRESS');
                }
                return;
            }

            if (addr && addr.street && addr.city && addr.regionId) {
                var formattedAddr = {
                    'street0': addr.street[0],
                    'city': addr.city,
                    'region': addr.regionId,
                    'country': addr.countryId,
                    'postcode': addr.postcode
                };

                // Skip if already suggested
                if (formattedAddr == this.activeAddress) {
                    if (typeof onFail === 'function') {
                        onFail('ADDRESS_ALREADY_VALIDATED');
                    }
                    return;
                }

                // Skip if the selected address is a suggestion
                if (addr.custom_attributes && addr.custom_attributes.suggestedAddress) {
                    this.activeAddress = formattedAddr;
                    return;
                }

                $.ajax({
                    type: 'POST',
                    url: this.getAddressValidationUrl(),
                    data: JSON.stringify($.extend({}, {form_key: window.FORM_KEY}, formattedAddr)),
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
                    if (typeof onFail === 'function') {
                        onFail(response);
                    }
                });
            } else {
                if (typeof onFail === 'function') {
                    onFail('MISSING_ADDRESS_FIELDS');
                }
            }
        },

        updateSuggestedAddresses: function (addr) {
            this.suggestedAddresses(addr);
        }
    };
});
