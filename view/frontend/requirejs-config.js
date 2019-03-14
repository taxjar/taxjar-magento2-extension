var config = {
    map: {
        '*': {
            taxjarModal: 'Taxjar_SalesTax/js/modal'
        }
    },
    config: {
        mixins: {
            'Magento_Customer/js/addressValidation': {
                'Taxjar_SalesTax/js/address_validation': true
            },
            'Magento_Checkout/js/view/shipping': {
                'Taxjar_SalesTax/js/view/shipping': true
            }
        }
    }
};
