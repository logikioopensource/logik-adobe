define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'mage/url',
    'Magento_PageBuilder/js/content-type',
    'Magento_PageBuilder/js/events'
], function (Component, customerData, urlBuilder, ContentType, events) {
    'use strict';

    return ContentType.extend({
        defaults: {
            cartId: '',
            configUrl: urlBuilder.build('logik_integration/config/get'),
            productId: '',
            template: 'Logik_Integration/content-type/logik-configurator'
        },

        initialize: function () {
            this._super();
            var cart = customerData.get('cart');
            
            if (cart().data_id) {
                this.cartId = cart().data_id;
                this.fetchConfigAndCreateElement();
            }

            cart.subscribe(function (updatedCart) {
                if (updatedCart.data_id) {
                    this.cartId = updatedCart.data_id;
                    this.fetchConfigAndCreateElement();
                }
            }.bind(this));

            // Subscribe to Page Builder events
            events.on('logik_configurator:updateAfter', function (args) {
                if (args.productId) {
                    this.productId = args.productId;
                    this.fetchConfigAndCreateElement();
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
                    self.createLogikElement(config);
                })
                .catch(function(error) {
                    console.error('Error fetching configuration:', error);
                    self.createLogikElement();
                });
        },

        createLogikElement: function(config) {
            var container = this.element.find('.logik-configurator-content');
            var existingElement = container.find('logik-ui');
            
            if (existingElement.length) {
                existingElement.remove();
            }

            var logikElement = document.createElement('logik-ui');
            logikElement.setAttribute('qid', this.cartId);
            
            // Use product ID from Page Builder if available, otherwise use config or fallback
            logikElement.setAttribute('product-id', this.productId || config?.product_id || 'Test1');
            logikElement.setAttribute('runtime-token', config?.runtime_token || 'TK1yrO3iO21ggIA2IgH4yTuATX6m5-ZCVQ');
            logikElement.setAttribute('tenant-api-url', config?.tenant_api_url || 'https://adobecommerce-dev.dev.logik.io');
            
            container.append(logikElement);
        }
    });
}); 