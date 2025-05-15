define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'mage/url'
], function (Component, customerData, urlBuilder) {
    'use strict';

    return Component.extend({
        defaults: {
            cartId: '',
            configUrl: urlBuilder.build('logik_integration/config/get')
        },

        initialize: function () {
            this._super();
            var cart = customerData.get('cart');
            
            console.log('Initial cart data:', cart());
            
            if (cart().data_id) {
                this.cartId = cart().data_id;
                console.log('Initial cart ID set to:', this.cartId);
                this.fetchConfigAndCreateElement();
            } else {
                console.log('No data_id found in initial cart data');
            }

            cart.subscribe(function (updatedCart) {
                console.log('Cart updated:', updatedCart);
                if (updatedCart.data_id) {
                    this.cartId = updatedCart.data_id;
                    console.log('Cart ID updated to:', this.cartId);
                    this.fetchConfigAndCreateElement();
                } else {
                    console.log('No data_id in updated cart data');
                }
            }.bind(this));
        },

        fetchConfigAndCreateElement: function() {
            var self = this;
            fetch(this.configUrl)
                .then(function(response) {
                    return response.json();
                })
                .then(function(config) {
                    console.log('Fetched configuration:', config);
                    self.createLogikElement(config);
                })
                .catch(function(error) {
                    console.error('Error fetching configuration:', error);
                    // Fallback to hardcoded values if fetch fails
                    self.createLogikElement();
                });
        },

        createLogikElement: function(config) {
            var container = document.querySelector('.logik-configurator-content');
            var existingElement = container.querySelector('logik-ui');
            
            if (existingElement) {
                container.removeChild(existingElement);
            }

            var logikElement = document.createElement('logik-ui');
            logikElement.setAttribute('qid', this.cartId);
            
            // Use config values if available, otherwise fallback to hardcoded values
            logikElement.setAttribute('product-id', config?.product_id || 'Test1');
            logikElement.setAttribute('runtime-token', config?.runtime_token || 'TK1yrO3iO21ggIA2IgH4yTuATX6m5-ZCVQ');
            logikElement.setAttribute('tenant-api-url', config?.tenant_api_url || 'https://adobecommerce-dev.dev.logik.io');
            
            container.appendChild(logikElement);
        }
    });
});