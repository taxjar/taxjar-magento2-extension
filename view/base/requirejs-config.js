var config = {
    map: {
        '*': {
            'taxjar_suggested_addresses': 'Taxjar_SalesTax/js/suggested_addresses'
        }
    },
    config: {
        mixins: {
            'Magento_Customer/js/addressValidation': {
                'Taxjar_SalesTax/js/address_validation': true
            },
            'Magento_Checkout/js/view/shipping': {
                'Taxjar_SalesTax/js/view/checkout_address_validation': true
            }
        }
    }
};
