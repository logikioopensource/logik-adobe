define([
    'uiComponent',
    'Magento_Customer/js/customer-data'
], function (Component, customerData) {
    'use strict';

    return Component.extend({
        defaults: {
            cartId: null,
            template: 'Logik_Integration/logik-configurator'
        },

        initialize: function () {
            this._super();
            this.getCartId();
            return this;
        },

        getCartId: function () {
            var self = this;
            var cart = customerData.get('cart');
            
            if (cart().quote_id) {
                self.cartId = cart().quote_id;
            }

            cart.subscribe(function (updatedCart) {
                if (updatedCart.quote_id) {
                    self.cartId = updatedCart.quote_id;
                }
            });
        }
    });
}); 