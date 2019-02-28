var config = {
    config: {
        mixins: {
            'Magento_Customer/js/addressValidation': {
                'Taxjar_SalesTax/js/address_validation': true
            },
            'Magento_Checkout/js/view/shipping': {
                'Taxjar_SalesTax/js/view/shipping': true
            },
            'Magento_Checkout/js/view/shipping-address/address-renderer/default': {
                'Taxjar_SalesTax/js/view/shipping-address/address-renderer/default': true
            }
        }
    }
};
