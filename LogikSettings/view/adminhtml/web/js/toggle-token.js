define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('logik.toggleToken', {
        _create: function() {
            this._bindClick();
        },

        _bindClick: function() {
            this.element.on('click', function() {
                var input = $(this).siblings('.integration-token-input');
                var type = input.attr('type');
                
                if (type === 'password') {
                    input.attr('type', 'text');
                    $(this).find('span').text('Hide Token');
                } else {
                    input.attr('type', 'password');
                    $(this).find('span').text('Show Token');
                }
            });
        }
    });

    return $.logik.toggleToken;
}); 