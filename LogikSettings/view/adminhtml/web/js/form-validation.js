define([
    'jquery',
    'mage/validation'
], function ($, validation) {
    'use strict';

    return function () {
        $('#logik-settings-form').on('submit', function (e) {
            var form = $(this);
            var isValid = true;
            
            // Clear previous errors
            $('.field-error').hide();
            
            // Validate Logik URL
            var logikUrl = $('#logik_url').val();
            if (!logikUrl) {
                $('#logik_url').siblings('.field-error').text('Logik URL is required').show();
                isValid = false;
            } else if (!validation.validateUrl(logikUrl)) {
                $('#logik_url').siblings('.field-error').text('Please enter a valid URL').show();
                isValid = false;
            }
            
            // Validate Runtime Token
            var runtimeToken = $('#logik_runtime_token').val();
            if (!runtimeToken) {
                $('#logik_runtime_token').siblings('.field-error').text('Runtime Token is required').show();
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
        });
    };
}); 