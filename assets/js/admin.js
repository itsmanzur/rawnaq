(function($) {
    'use strict';

    function updateModuleCardState($input) {
        var $card = $input.closest('.module-card');
        if (!$card.length) {
            return;
        }
        $card.toggleClass('is-on', $input.is(':checked'));
    }

    function refreshActiveCount() {
        var count = $('.module-toggle-input:checked').length;
        $('#modules-active-count').text(String(count));
    }

    $(document).ready(function() {
        $('.module-toggle-input').each(function() {
            updateModuleCardState($(this));
        });
        refreshActiveCount();

        $('.module-toggle-input').on('change', function() {
            updateModuleCardState($(this));
            refreshActiveCount();
        });

        $('.rawnaq-nav .nav-item').on('click', function(e) {
            e.preventDefault();
            var target = $(this).data('tab');

            $('.rawnaq-nav .nav-item').removeClass('active');
            $(this).addClass('active');

            $('.tab-panel').removeClass('active');
            $('#tab-' + target).addClass('active');

            window.location.hash = target;
        });

        $('.trigger-tab-change').on('click', function() {
            var target = $(this).data('target');
            $('.rawnaq-nav .nav-item[data-tab="' + target + '"]').trigger('click');
        });

        var hash = window.location.hash.substring(1);
        if (hash && $('.rawnaq-nav .nav-item[data-tab="' + hash + '"]').length) {
            $('.rawnaq-nav .nav-item[data-tab="' + hash + '"]').trigger('click');
        }

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
