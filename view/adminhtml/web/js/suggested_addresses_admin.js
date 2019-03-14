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
], function (ko, $) {
    'use strict';

    $().ready(function () {

        // Watch for suggested address being selected
        $('#tj-suggested-addresses').on('change', 'input[name=suggested-address]:checked', function (event) {
            var data = $.data(document.body);
            var addr = data[this.id];

            if ($.isEmptyObject(addr)) {
                console.log('empty object');
                return;
            }

            if ($('#order-shipping_same_as_billing').is(':checked')) {
                $('input[name*="order[billing_address][street]"]:first').val(addr.street);
                $('input[name="order[billing_address][city]"]').val(addr.city);
                $('input[name="order[billing_address][region_id]"]').val(addr.state);
                $('input[name="order[billing_address][postcode]"]').val(addr.zip);
                $('input[name="order[billing_address][country_id]"]').val(addr.country);
            }

            $('input[name*="order[shipping_address][street]"]:first').val(addr.street);
            $('input[name="order[shipping_address][city]"]').val(addr.city);
            $('input[name="order[shipping_address][region_id]"]').val(addr.state);
            $('input[name="order[shipping_address][postcode]"]').val(addr.zip);
            $('input[name="order[shipping_address][country_id]"]').val(addr.country);

            $('input[name*="street"]:first').val(addr.street);
            $('input[name="city"]').val(addr.city);
            $('input[name="region_id"]').val(addr.state);
            $('input[name="postcode"]').val(addr.zip);
            $('input[name="country_id"]').val(addr.country);

            window.isValidated = true;
        });
    });


    return {

        initialize: function () {
            this._super();
            return this;
        },

        buildHtml: function (response, addr) {
            var addrHTML = '<div><input type="radio" name="suggested-address" id="tj-suggestion-0" value="0" checked="checked"/><label for="0">Original</label></div>';
            var responseJson = {};
            var n = 1;

            if (response.suggestions) {

                responseJson = Object.assign(responseJson, {"tj-suggestion-0": addr.original});

                for (var addr of response.suggestions) {
                    var suggestion = $.extend({}, addr.changes, addr.address);

                    addrHTML += '<div>';
                    addrHTML += '<input type="radio" name="suggested-address" id="tj-suggestion-' + n + '" value="' + n + '" />';
                    addrHTML += '<label for="' + n + '">';
                    addrHTML += '<div class="addr">' + suggestion.street + '</div>';
                    addrHTML += '<div class="city">' + suggestion.city + '</div>';
                    addrHTML += '<div class="state">' + suggestion.state + '</div>';
                    addrHTML += '<div class="postal">' + suggestion.zip + '</div>';
                    addrHTML += '<div class="country">' + suggestion.country + '</div>';
                    addrHTML += '</label></div>';

                    var key = "tj-suggestion-" + n;
                    responseJson = Object.assign(responseJson, {[key]: addr.address});

                    n++;
                }
            }

            $.data(document.body, responseJson);
            return addrHTML;
        },

        hideLoader: function (button) {
            $('body').trigger('processStop');
            button.attr('disabled', false);
        },

        displayError: function (msg) {
            console.log(msg);
        }
    };
});
