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
    'jquery',
    'Taxjar_SalesTax/js/suggested_addresses',
    'Magento_Checkout/js/action/create-shipping-address',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Checkout/js/checkout-data',
    // 'Magento_Checkout/js/model/quote'

], function ($, suggestedAddresses, createShippingAddress, selectShippingAddress, checkoutDataResolver, checkoutData) {

    'use strict';

    return function (originalAddressValidation) {

        if (taxjar_validate_address !== true) {
            return originalAddressValidation;
        }

        window.isValidated = false;

        return originalAddressValidation.extend({

            /**
             * Save new shipping address
             */

            saveNewAddress: function (event) {
                var addressData,
                    newShippingAddress;
                addressData = this.source.get('shippingAddress');  //todo: move this

                if (window.isValidated) {
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

                    return;
                }

                // event.preventDefault();  //TODO: remove this?

                let addr = {
                    'street0': addressData.street[0],
                    'city': addressData.city,
                    'region': addressData.region_id,
                    'country': addressData.country_id,
                    'postcode': addressData.postcode
                };

                $.ajax({
                    type: 'POST',
                    url: '/rest/V1/Taxjar/address_validation/',  //TODO: make global variable
                    data: JSON.stringify(addr),
                    contentType: 'application/json; charset=utf-8',
                    dataType: 'json',
                    success: function (response) {
                        response = JSON.parse(response);

                        if (response.error === true) {
                            // suggestedAddresses.hideLoader(button);
                            suggestedAddresses.displayError(response.error_msg);
                            return;
                        }

                        let tj_suggested_addresses = $('#tj-suggested-addresses');
                        let addrHTML = suggestedAddresses.buildHtml(response, addr);

                        tj_suggested_addresses.html('');
                        tj_suggested_addresses.append('<ul>' + addrHTML + '</ul>');
                        $('#co-shipping-form #shipping-new-address-form > div:last-child').after(tj_suggested_addresses);

                        // suggestedAddresses.hideLoader(button);
                        window.isValidated = true;
                    },
                    failure: function (err) {
                        // suggestedAddresses.hideLoader(button);
                        suggestedAddresses.displayError(err);
                    }
                });
            },
        });
    };
});