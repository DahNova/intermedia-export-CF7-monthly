/**
 * CF7 Monthly Export - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        var $testButton = $('#cf7-test-connection');
        var $exportButton = $('#cf7-manual-export');
        var $resultDiv = $('#cf7-export-result');

        /**
         * Show result message
         */
        function showResult(message, type) {
            $resultDiv
                .removeClass('success error info')
                .addClass(type)
                .html('<p>' + message + '</p>')
                .slideDown();

            // Auto-hide after 5 seconds for success/info
            if (type === 'success' || type === 'info') {
                setTimeout(function() {
                    $resultDiv.slideUp();
                }, 5000);
            }
        }

        /**
         * Test Google Sheets connection
         */
        $testButton.on('click', function(e) {
            e.preventDefault();

            var $button = $(this);
            $button.addClass('is-loading').prop('disabled', true);
            $resultDiv.slideUp();

            $.ajax({
                url: cf7ExportAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cf7_test_connection',
                    nonce: cf7ExportAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showResult(response.data.message, 'success');
                    } else {
                        showResult(response.data.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showResult(cf7ExportAjax.strings.error + ': ' + error, 'error');
                },
                complete: function() {
                    $button.removeClass('is-loading').prop('disabled', false);
                }
            });
        });

        /**
         * Manual export
         */
        $exportButton.on('click', function(e) {
            e.preventDefault();

            if (!confirm(cf7ExportAjax.strings.confirm_export)) {
                return;
            }

            var $button = $(this);
            $button.addClass('is-loading').prop('disabled', true);
            $resultDiv.slideUp();

            $.ajax({
                url: cf7ExportAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cf7_manual_export',
                    nonce: cf7ExportAjax.nonce,
                    force_all: false
                },
                success: function(response) {
                    if (response.success) {
                        showResult(response.data.message, 'success');

                        // Reload stats after successful export
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showResult(response.data.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showResult(cf7ExportAjax.strings.error + ': ' + error, 'error');
                },
                complete: function() {
                    $button.removeClass('is-loading').prop('disabled', false);
                }
            });
        });

        /**
         * Auto-save indicator for form changes
         */
        var $form = $('.cf7-export-form');
        var formChanged = false;

        $form.find('input, textarea, select').on('change', function() {
            formChanged = true;
        });

        $(window).on('beforeunload', function(e) {
            if (formChanged) {
                var message = 'You have unsaved changes. Are you sure you want to leave?';
                e.returnValue = message;
                return message;
            }
        });

        $form.on('submit', function() {
            formChanged = false;
        });

        /**
         * Validate JSON credentials on blur
         */
        $('#google_credentials').on('blur', function() {
            var $textarea = $(this);
            var value = $textarea.val().trim();

            if (value === '') {
                $textarea.css('border-color', '');
                return;
            }

            try {
                JSON.parse(value);
                $textarea.css('border-color', '#00a32a');
            } catch (e) {
                $textarea.css('border-color', '#d63638');
                showResult('Invalid JSON format in Google Credentials field.', 'error');
            }
        });

        /**
         * Select/Deselect all forms
         */
        var $formCheckboxes = $('input[name="cf7_monthly_export_settings[forms_to_export][]"]');

        if ($formCheckboxes.length > 1) {
            var $selectAll = $('<label style="font-weight: bold; margin-bottom: 10px; display: block;"><input type="checkbox" id="select-all-forms" /> Select All</label>');
            $formCheckboxes.first().parent().before($selectAll);

            $('#select-all-forms').on('change', function() {
                $formCheckboxes.prop('checked', $(this).prop('checked'));
                formChanged = true;
            });

            $formCheckboxes.on('change', function() {
                var allChecked = $formCheckboxes.length === $formCheckboxes.filter(':checked').length;
                $('#select-all-forms').prop('checked', allChecked);
            });
        }

    });

})(jQuery);
