/**
 * Elementor editor: Apply Scroll Timeline agency preset → replace steps repeater.
 */
(function ($) {
    'use strict';

    function getPresets() {
        return (window.rawnaqTimelineEditor && rawnaqTimelineEditor.presets)
            ? rawnaqTimelineEditor.presets
            : {};
    }

    function resolveWidgetContainer(view) {
        var container = null;

        if (view) {
            if (view.container) {
                container = view.container;
            } else if (view.options && view.options.container) {
                container = view.options.container;
            } else if (view.getOption && typeof view.getOption === 'function') {
                container = view.getOption('container');
            }
        }

        if (!container && window.elementor) {
            if (typeof elementor.getCurrentElementContainer === 'function') {
                container = elementor.getCurrentElementContainer();
            } else if (elementor.selection && typeof elementor.selection.getElements === 'function') {
                var selected = elementor.selection.getElements();
                if (selected && selected[0]) {
                    container = selected[0];
                }
            } else if (elementor.selection && elementor.selection.elements && elementor.selection.elements[0]) {
                container = elementor.selection.elements[0];
            }
        }

        if (!container && window.elementor && elementor.getPanelView) {
            try {
                var panel = elementor.getPanelView();
                var page = panel && panel.getCurrentPageView && panel.getCurrentPageView();
                if (page) {
                    if (page.getContainer && typeof page.getContainer === 'function') {
                        container = page.getContainer();
                    } else if (page.container) {
                        container = page.container;
                    } else if (page.getOption && typeof page.getOption === 'function') {
                        var edited = page.getOption('editedElementView');
                        if (edited && edited.getContainer) {
                            container = edited.getContainer();
                        }
                    }
                }
            } catch (e) { /* ignore */ }
        }

        // Verify or traverse
        var curr = container;
        while (curr) {
            var model = curr.model;
            var widgetType = model && model.get && model.get('widgetType');
            if (widgetType === 'rawnaq_scroll_timeline') {
                return curr;
            }
            if (curr.type === 'widget' && widgetType) {
                return curr;
            }
            curr = curr.parent;
        }

        return container;
    }

    function applyPreset(view) {
        var container = resolveWidgetContainer(view);
        var settings = (view && view.elementSettingsModel) || (container && container.settings);

        if (!settings && window.elementor && elementor.getPanelView) {
            try {
                var panel = elementor.getPanelView();
                var page = panel && panel.getCurrentPageView && panel.getCurrentPageView();
                if (page && page.model && page.model.get('settings')) {
                    settings = page.model.get('settings');
                }
            } catch (e) { /* ignore */ }
        }

        var presetKey = '';
        if (settings && typeof settings.get === 'function') {
            presetKey = settings.get('agency_preset') || '';
        }
        if (!presetKey) {
            var select = document.querySelector('.elementor-control-agency_preset select, [data-setting="agency_preset"]');
            if (select && select.value) {
                presetKey = select.value;
            }
        }

        if (!presetKey) {
            if (window.elementor && elementor.notifications) {
                elementor.notifications.showToast({
                    message: (window.rawnaqTimelineEditor && rawnaqTimelineEditor.i18n && rawnaqTimelineEditor.i18n.pickHint)
                        ? rawnaqTimelineEditor.i18n.pickHint
                        : 'Choose an agency preset, then Apply.'
                });
            }
            return;
        }

        var presets = getPresets();
        var pack = presets[presetKey] || presets[presetKey.replace(/_/g, '-')] || presets[presetKey.replace(/-/g, '_')];
        if (!pack || !pack.steps) {
            return;
        }

        // Attach unique _id to each repeater item so Elementor Backbone collections diff cleanly
        var stepsWithIds = pack.steps.map(function (step, index) {
            var item = Object.assign({}, step);
            if (!item._id) {
                if (window.elementorCommon && elementorCommon.helpers && elementorCommon.helpers.getUniqueId) {
                    item._id = elementorCommon.helpers.getUniqueId();
                } else {
                    item._id = Math.random().toString(36).substring(2, 9) + index;
                }
            }
            return item;
        });

        var next = { steps: stepsWithIds };

        // 1. Update through Elementor $e commands API
        if (window.$e && $e.run && container) {
            try {
                $e.run('document/elements/settings', {
                    container: container,
                    settings: next,
                    options: { external: true }
                });
            } catch (err) { /* ignore */ }
        }

        // 2. Update Backbone settings models
        if (settings && typeof settings.setExternalChange === 'function') {
            settings.setExternalChange('steps', stepsWithIds);
        } else if (settings && typeof settings.set === 'function') {
            settings.set('steps', stepsWithIds);
        }

        if (container && container.settings && container.settings !== settings) {
            if (typeof container.settings.setExternalChange === 'function') {
                container.settings.setExternalChange('steps', stepsWithIds);
            } else if (typeof container.settings.set === 'function') {
                container.settings.set('steps', stepsWithIds);
            }
        }

        // 3. Re-render panel view so the repeater list in sidebar refreshes immediately
        try {
            if (window.elementor && elementor.getPanelView) {
                var panelView = elementor.getPanelView();
                var curPage = panelView && panelView.getCurrentPageView && panelView.getCurrentPageView();
                if (curPage && typeof curPage.render === 'function') {
                    curPage.render();
                }
            }
        } catch (err) { /* ignore */ }

        if (window.elementor && elementor.notifications) {
            elementor.notifications.showToast({
                message: (window.rawnaqTimelineEditor && rawnaqTimelineEditor.i18n && rawnaqTimelineEditor.i18n.applied)
                    ? rawnaqTimelineEditor.i18n.applied
                    : 'Preset applied — steps updated.'
            });
        }
    }

    $(window).on('elementor:init', function () {
        elementor.channels.editor.on('rawnaq:timeline:applyPreset', applyPreset);
    });

    if (window.elementor && elementor.channels && elementor.channels.editor) {
        elementor.channels.editor.on('rawnaq:timeline:applyPreset', applyPreset);
    }

    // Direct click fallback
    $(document).on('click', '.elementor-control-apply_agency_preset button, [data-event="rawnaq:timeline:applyPreset"]', function (e) {
        e.preventDefault();
        applyPreset();
    });
})(jQuery);
