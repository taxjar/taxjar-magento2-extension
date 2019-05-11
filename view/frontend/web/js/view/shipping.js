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
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/address-list',
    'Taxjar_SalesTax/js/model/address_validation_core'
], function (
    ko,
    $,
    quote,
    addressList,
    avCore
) {
    'use strict';

    var mixin = {
        initialize: function () {
            this._super();

            var self = this;

            $(document).on('change', '[name="street[0]"], [name="city"], [name="region_id"]', function () {
                avCore.getSuggestedAddresses({
                    street: [self.source.get('shippingAddress.street.0')],
                    city: self.source.get('shippingAddress.city'),
                    regionId: self.source.get('shippingAddress.region_id'),
                    postcode: self.source.get('shippingAddress.postcode'),
                    countryId: self.source.get('shippingAddress.country_id')
                });
            });
        },

        setShippingAddressForm: function () {
            if (addressList().length === 0) {
                var originalAddress = quote.shippingAddress();

                this.source.set('shippingAddress.street.0', originalAddress.street[0]);
                this.source.set('shippingAddress.city', originalAddress.city);
                this.source.set('shippingAddress.region_id', originalAddress.regionId);
                this.source.set('shippingAddress.postcode', originalAddress.postcode);
                this.source.set('shippingAddress.country_id', originalAddress.countryId);
            }
        },

        // Aheadworks One Step Checkout
        validate: function () {
            this.setShippingAddressForm();
            return this._super();
        },

        // Native Checkout
        validateShippingInformation: function () {
            this.setShippingAddressForm();
            return this._super();
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
