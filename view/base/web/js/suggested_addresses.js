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
    'uiComponent',
    'Taxjar_SalesTax/js/model/address_validation_core'
], function (ko, $, Component, avCore) {
    'use strict';

    return Component.extend({
        defaults: {
            suggestedAddresses: avCore.suggestedAddresses,
            suggestedAddressRadio: ko.observable(0),
            validatedAddresses: ko.computed(function () {
                return ko.utils.arrayFilter(avCore.suggestedAddresses(), function (addr) {
                    return addr.address.custom_attributes && addr.address.custom_attributes.suggestedAddress === true;
                });
            })
        }
    });
});
