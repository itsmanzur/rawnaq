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

        /* ── Documentation & Usage Guide Search / Filter / Copy / Accordion Engine ── */
        var $docCards = $('.rawnaq-doc-card');
        var $docsSearch = $('#rawnaq-docs-search');
        var $docsClear = $('#rawnaq-docs-search-clear');
        var $filterBtns = $('.docs-filter-btn');
        var $noResults = $('#rawnaq-docs-no-results');
        var $btnToggleAll = $('#btn-toggle-all-docs');

        // Accordion click toggle
        $(document).on('click', '.rawnaq-doc-card-header', function(e) {
            // If user clicked inside a copy badge or button, don't toggle accordion
            if ($(e.target).closest('.rawnaq-copy-badge, button, a').length) {
                return;
            }
            var $card = $(this).closest('.rawnaq-doc-card');
            $card.toggleClass('is-expanded');
        });

        // Expand All / Collapse All Toggle
        $btnToggleAll.on('click', function(e) {
            e.preventDefault();
            var areAnyCollapsed = $docCards.filter(':visible:not(.is-expanded)').length > 0;
            if (areAnyCollapsed) {
                $docCards.filter(':visible').addClass('is-expanded');
                $btnToggleAll.find('.toggle-text').text('Collapse All');
            } else {
                $docCards.filter(':visible').removeClass('is-expanded');
                $btnToggleAll.find('.toggle-text').text('Expand All');
            }
        });

        function filterDocs() {
            var query = ($docsSearch.val() || '').toLowerCase().trim();
            var activeFilter = $('.docs-filter-btn.active').data('filter') || 'all';
            var visibleCount = 0;

            $docsClear.toggle(query.length > 0);

            $docCards.each(function() {
                var $card = $(this);
                var category = $card.data('category') || '';
                var categories = category.split(' ');
                var cardText = $card.text().toLowerCase();

                var matchesCategory = (activeFilter === 'all') || (categories.indexOf(activeFilter) !== -1);
                var matchesQuery = !query || (cardText.indexOf(query) !== -1);

                if (matchesCategory && matchesQuery) {
                    $card.show();
                    // If searching with query, auto-expand matching cards
                    if (query.length > 0) {
                        $card.addClass('is-expanded');
                    }
                    visibleCount++;
                } else {
                    $card.hide();
                }
            });

            if ($noResults.length) {
                if (visibleCount === 0) {
                    $noResults.show();
                } else {
                    $noResults.hide();
                }
            }
        }

        // Live search typing
        $docsSearch.on('input keyup', function() {
            filterDocs();
        });

        // Clear search
        $docsClear.on('click', function() {
            $docsSearch.val('').trigger('input').focus();
        });

        // Category pills click
        $filterBtns.on('click', function(e) {
            e.preventDefault();
            $filterBtns.removeClass('active');
            $(this).addClass('active');
            filterDocs();
        });

        // Update counts
        var totalGuides = $docCards.length;
        $('.docs-filter-btn[data-filter="all"] .filter-count').text(totalGuides);

        // Copy to clipboard with toast
        function showCopyToast(text) {
            $('.rawnaq-copy-toast').remove();
            var $toast = $('<div class="rawnaq-copy-toast"><span class="dashicons dashicons-yes-alt" style="color:#34d399;"></span> Copied to clipboard: <strong>' + $('<div>').text(text).html() + '</strong></div>');
            $('body').append($toast);
            setTimeout(function() {
                $toast.fadeOut(250, function() {
                    $(this).remove();
                });
            }, 2500);
        }

        $(document).on('click', '.rawnaq-copy-badge, .copyable', function(e) {
            e.preventDefault();
            var textToCopy = $(this).data('copy') || $(this).text().trim();
            // remove any leading icon symbols if copied from text
            textToCopy = textToCopy.replace(/^📋\s*/, '').replace(/\s*📋$/, '');

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(textToCopy).then(function() {
                    showCopyToast(textToCopy);
                });
            } else {
                // fallback
                var $temp = $('<textarea>');
                $('body').append($temp);
                $temp.val(textToCopy).select();
                document.execCommand('copy');
                $temp.remove();
                showCopyToast(textToCopy);
            }
        });
    });

})(jQuery);


