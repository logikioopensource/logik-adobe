define([
    'uiComponent',
    'Magento_Customer/js/customer-data'
], function (Component, customerData) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Adobe_Composer/logik-configurator',
            cartId: ''
        },

        initialize: function () {
            this._super();
            var cart = customerData.get('cart');
            this.cartId = cart().data_id || '';
            
            cart.subscribe(function (updatedCart) {
                this.cartId = updatedCart.data_id || '';
            }.bind(this));
        }
    });
});