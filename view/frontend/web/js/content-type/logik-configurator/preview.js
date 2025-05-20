define([
    'Magento_PageBuilder/js/content-type/preview',
    'Magento_PageBuilder/js/events'
], function (PreviewBase, events) {
    'use strict';

    function Preview(parent, config, stageId) {
        PreviewBase.call(this, parent, config, stageId);
    }

    Preview.prototype = Object.create(PreviewBase.prototype);

    Preview.prototype.initializeObservable = function () {
        var self = this;
        this._super();
        this.observe('productId');

        return this;
    };

    Preview.prototype.getData = function () {
        var data = this._super();
        data.product_id = this.productId();
        return data;
    };

    Preview.prototype.afterObservableUpdated = function () {
        this._super();
        events.trigger('logik_configurator:updateAfter', {
            productId: this.productId()
        });
    };

    return Preview;
}); 