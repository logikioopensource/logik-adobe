define([
    'jquery',
    'Magento_Ui/js/modal/alert'
], function ($, alert) {
    'use strict';

    return function () {
        $('#test-credentials').on('click', function () {
            var logikUrl = $('#logik_url').val();
            var runtimeToken = $('#logik_runtime_token').val();

            if (!logikUrl || !runtimeToken) {
                alert({
                    title: 'Validation Error',
                    content: 'Please enter both Logik URL and Runtime Token before testing.'
                });
                return;
            }

            // Remove trailing slash if present
            logikUrl = logikUrl.replace(/\/$/, '');
            var testUrl = `${logikUrl}/api`;

            $(this).prop('disabled', true);
            var button = $(this);

            const inputBody = {
                sessionContext: {
                    stateful: true
                },
                productId: "fakeProductIdForValidation"
            };

            fetch(testUrl, {
                method: 'POST',
                body: JSON.stringify(inputBody),
                headers: {
                    'Authorization': 'Bearer ' + runtimeToken,
                    'Content-Type': 'application/vnd.logik.cfg-v2+json',
                    'Accept': 'application/vnd.logik.cfg-v2+json',
                    'Origin': window.location.origin
                }
            })
            .then(response => {
                if (response.status === 404) {
                    alert({
                        title: 'Success',
                        content: 'Credentials are valid! Authentication successful.'
                    });
                } else if (response.status === 403) {
                    alert({
                        title: 'Error',
                        content: 'Authentication failed. Please check your Runtime Token and ensure this domain is whitelisted in Logik.'
                    });
                } else {
                    alert({
                        title: 'Error',
                        content: `Unexpected response (Status: ${response.status}). Please verify your Logik URL and try again.`
                    });
                }
            })
            .catch(error => {
                alert({
                    title: 'Error',
                    content: 'Error testing credentials: ' + error.message
                });
            })
            .finally(() => {
                button.prop('disabled', false);
            });
        });
    };
}); 