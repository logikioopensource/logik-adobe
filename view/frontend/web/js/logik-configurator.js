define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'ko'
], function (Component, customerData, ko) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Logik_Integration/logik-configurator',
            cartId: ko.observable('')
        },

        initialize: function () {
            this._super();
            var self = this;
            var cart = customerData.get('cart');
            
            // Initial cart ID set
            if (cart().quote_id) {
                self.cartId(cart().quote_id);
            }

            // Subscribe to cart updates
            cart.subscribe(function (updatedCart) {
                if (updatedCart.quote_id) {
                    self.cartId(updatedCart.quote_id);
                }
            });

            return this;
        }
    });
});