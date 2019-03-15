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
    'Taxjar_SalesTax/js/suggested_addresses_admin'
], function (ko, $, suggestedAddresses) {
    'use strict';

    $().ready(function () {

        $('#tj-test-button').on('click', function (event) {
            var form = $('#edit_form');
            var body = $('body');
            var button = $('#tj-test-button');

            button.attr('disabled', true);
            body.trigger('processStart');


            if (window.isValidated === true) {
                form.submit();
            }

            var addr = {};
            if ($(location).attr('href').indexOf('order_create') !== -1) {
                addr = {
                    'street0': $("#edit_form input[name='order[shipping_address][street][0]']").val(),
                    'city': $("#edit_form input[name='order[shipping_address][city]']").val(),
                    'region': $("#edit_form select[name='order[shipping_address][region_id]']").val(),
                    'country': $("#edit_form select[name='order[shipping_address][country_id]']").val(),
                    'postcode': $("#edit_form input[name='order[shipping_address][postcode]']").val()
                };
            } else {
                addr = {
                    'street0': $("#edit_form input[name='street[0]']").val(),
                    'city': $("#edit_form input[name='city']").val(),
                    'region': $("#edit_form select[name='region_id']").val(),
                    'country': $("#edit_form select[name='country_id']").val(),
                    'postcode': $("#edit_form input[name='postcode']").val()
                };
            }

            $.ajax({
                type: 'POST',
                url: '/rest/V1/Taxjar/address_validation/',  //TODO: make global variable
                data: JSON.stringify(addr),
                contentType: 'application/json; charset=utf-8',
                dataType: 'json',
                beforeSend: function (xhr) {
                    // Intentionally empty to prevent the form_key from being appended to the body of the request
                },
                success: function (response) {
                    if (response.error === true) {
                        suggestedAddresses.hideLoader(button);
                        suggestedAddresses.displayError(response.error_msg);
                        return;
                    }

                    var tj_suggested_addresses = $('#tj-suggested-addresses');
                    var addrHTML = suggestedAddresses.buildHtml(response, addr);

                    tj_suggested_addresses.html('');
                    tj_suggested_addresses.append('<ul>' + addrHTML + '</ul>');
                    $('#form-validate fieldset:nth-child(2) > div:last-child').after(tj_suggested_addresses);

                    suggestedAddresses.hideLoader(button);
                    window.isValidated = true;
                },
                failure: function (err) {
                    suggestedAddresses.hideLoader(button);
                    suggestedAddresses.displayError(err);
                }
            });
        });
    });
});
