define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'Magento_PageBuilder/js/events',
    'Magento_PageBuilder/js/content-type-collection'
], function (Component, customerData, events, ContentTypeCollection) {
    'use strict';

    return Component.extend({
        defaults: {
            cartId: null,
            template: 'Logik_Integration/logik-configurator',
            content: '',
            classes: '',
            styles: {}
        },

        initialize: function () {
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
            }

            cart.subscribe(function (updatedCart) {
                console.log('Cart updated:', updatedCart);
                if (updatedCart.quote_id) {
                    self.cartId = updatedCart.quote_id;
                    console.log('Cart ID updated to:', self.cartId);
                }
            });
        },

        /**
         * Get the styles for the main element
         *
         * @returns {Object}
         */
        getStyles: function () {
            return this.styles;
        },

        /**
         * Get the classes for the main element
         *
         * @returns {String}
         */
        getClasses: function () {
            return this.classes;
        },

        /**
         * Get template
         *
         * @returns {String}
         */
        getTemplate: function () {
            return this.template;
        }
    });
}); 