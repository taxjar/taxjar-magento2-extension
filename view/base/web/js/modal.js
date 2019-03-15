define([
    'jquery',
    'jquery/ui',
    'Magento_Ui/js/modal/modal'
], function ($) {
    $.widget('taxjar.modal', $.mage.modal, {
        data: {},
        openModal: function (data) {
            if (data) {
                this.data = data;
            }
            this._super();
        }
    });

    return $.taxjar.modal;
});
