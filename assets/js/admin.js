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

        $('#btn-reset-dock-clicks').on('click', function() {
            if (!window.confirm('Reset all Floating Dock click counters?')) {
                return;
            }
            var $btn = $(this);
            var $status = $('#dock-stats-status');
            $btn.prop('disabled', true);
            $status.removeClass('success error').text('Resetting…');

            $.ajax({
                url: rawnaq_admin_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'rawnaq_dock_reset_clicks',
                    nonce: rawnaq_admin_vars.nonce
                },
                success: function(response) {
                    $btn.prop('disabled', false);
                    if (response.success && response.data && response.data.clicks) {
                        var c = response.data.clicks;
                        $('#dock-stat-total').text(String(c.total || 0));
                        $('#dock-stat-fab').text(String(c.fab || 0));
                        $('#dock-stat-agent').text(String(c.agent || 0));
                        $('#dock-stat-web').text(String(c.web || 0));
                        $('#dock-stat-chooser').text(String(c.chooser || 0));
                        $('#dock-stat-secondary').text(String(c.secondary || 0));
                        $('#dock-stat-classic').text(String(c.classic || 0));
                        $('#dock-stat-offline').text(String(c.offline || 0));
                        $status.addClass('success').text('Counters reset.');
                    } else {
                        $status.addClass('error').text('Could not reset counters.');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                    $status.addClass('error').text('Request failed.');
                }
            });
        });
    });

})(jQuery);
