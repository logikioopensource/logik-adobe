define([
    'Magento_PageBuilder/js/content-type/master',
    'Magento_Customer/js/customer-data',
    'mage/url'
], function (MasterBase, customerData, urlBuilder) {
    'use strict';

    function Master(parent, config, stageId) {
        MasterBase.call(this, parent, config, stageId);
        
        this.cartId = '';
        this.configUrl = urlBuilder.build('logik_integration/config/get');
        this.productId = config.data.product_id || '';
        
        this.initializeCart();
    }

    Master.prototype = Object.create(MasterBase.prototype);

    Master.prototype.initializeCart = function() {
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
    };

    Master.prototype.fetchConfigAndCreateElement = function() {
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
    };

    Master.prototype.createLogikElement = function(config) {
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
    };

    return Master;
}); 