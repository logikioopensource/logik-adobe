define([
    'uiComponent',
    'Magento_Customer/js/customer-data'
], function (Component, customerData) {
    'use strict';

    console.log('Loading logik-configurator component');

    return Component.extend({
        defaults: {
            cartId: null
        },

        initialize: function () {
            console.log('Initializing logik-configurator component');
            this._super();
            this.getCartId();
            return this;
        },

        getCartId: function () {
            var self = this;
            var cart = customerData.get('cart');
            
            console.log('Cart data:', cart());
            
            if (cart().quote_id) {
                self.cartId = cart().quote_id;
                console.log('Cart ID set to:', self.cartId);
            } else {
                console.log('No cart ID available yet');
            }

            cart.subscribe(function (updatedCart) {
                console.log('Cart updated:', updatedCart);
                if (updatedCart.quote_id) {
                    self.cartId = updatedCart.quote_id;
                    console.log('Cart ID updated to:', self.cartId);
                }
            });
        }
    });
}); 