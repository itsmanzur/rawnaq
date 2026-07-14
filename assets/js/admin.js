(function($) {
    'use strict';

    $(document).ready(function() {
        // Tab switching logic
        $('.rawnaq-nav .nav-item').on('click', function(e) {
            e.preventDefault();
            var target = $(this).data('tab');

            // Switch Nav active states
            $('.rawnaq-nav .nav-item').removeClass('active');
            $(this).addClass('active');

            // Switch Panel active states
            $('.tab-panel').removeClass('active');
            $('#tab-' + target).addClass('active');

            // Update URL hash
            window.location.hash = target;
        });

        // Trigger tab change from button
        $('.trigger-tab-change').on('click', function(e) {
            var target = $(this).data('target');
            $('.rawnaq-nav .nav-item[data-tab="' + target + '"]').trigger('click');
        });

        // Load active tab from URL hash if exists
        var hash = window.location.hash.substring(1);
        if (hash && $('.rawnaq-nav .nav-item[data-tab="' + hash + '"]').length) {
            $('.rawnaq-nav .nav-item[data-tab="' + hash + '"]').trigger('click');
        }

        // Save modules settings via AJAX
        $('#rawnaq-modules-form').on('submit', function(e) {
            e.preventDefault();

            var $btn = $('#btn-save-settings');
            var $status = $('#save-status-msg');
            var formData = $(this).serialize();

            $btn.prop('disabled', true).text('Saving...');
            $status.removeClass('success error').text('');

            $.ajax({
                url: rawnaq_admin_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'rawnaq_save_modules',
                    security: rawnaq_admin_vars.nonce,
                    form_data: formData
                },
                success: function(response) {
                    $btn.prop('disabled', false).text('Save Changes');
                    if (response.success) {
                        $status.addClass('success').text(response.data);
                    } else {
                        $status.addClass('error').text(response.data || 'Failed to save settings.');
                    }
                    setTimeout(function() {
                        $status.fadeOut(300, function() {
                            $(this).text('').show();
                        });
                    }, 3000);
                },
                error: function() {
                    $btn.prop('disabled', false).text('Save Changes');
                    $status.addClass('error').text('An error occurred. Please try again.');
                }
            });
        });
    });

})(jQuery);
