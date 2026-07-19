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
        var container = view && (view.container || (view.getOption && view.getOption('container')));

        if (!container && window.elementor && elementor.selection) {
            var selected = elementor.selection.getElements && elementor.selection.getElements();
            if (selected && selected[0]) {
                container = selected[0];
            }
        }

        if (!container && window.elementor && elementor.getPanelView) {
            try {
                var panel = elementor.getPanelView();
                var page = panel && panel.getCurrentPageView && panel.getCurrentPageView();
                var edited = page && page.getOption && page.getOption('editedElementView');
                if (edited && edited.getContainer) {
                    container = edited.getContainer();
                }
            } catch (e) { /* ignore */ }
        }

        while (container && container.parent) {
            var model = container.model;
            var widgetType = model && model.get && model.get('widgetType');
            if (widgetType === 'rawnaq_scroll_timeline') {
                return container;
            }
            if (container.type === 'widget' && widgetType) {
                return container;
            }
            container = container.parent;
        }

        return container;
    }

    function applyPreset(view) {
        var container = resolveWidgetContainer(view);
        if (!container) {
            return;
        }

        var settings = container.settings;
        if (!settings || typeof settings.get !== 'function') {
            return;
        }

        var presetKey = settings.get('agency_preset') || '';
        if (!presetKey) {
            if (window.elementor && elementor.notifications) {
                elementor.notifications.showToast({
                    message: (rawnaqTimelineEditor && rawnaqTimelineEditor.i18n && rawnaqTimelineEditor.i18n.pickHint)
                        ? rawnaqTimelineEditor.i18n.pickHint
                        : 'Choose an agency preset, then Apply.'
                });
            }
            return;
        }

        var pack = getPresets()[presetKey];
        if (!pack || !pack.steps) {
            return;
        }

        var next = { steps: pack.steps };

        if (window.$e && $e.run) {
            $e.run('document/elements/settings', {
                container: container,
                settings: next,
                options: { external: true }
            });
        } else if (typeof settings.setExternalChange === 'function') {
            settings.setExternalChange('steps', pack.steps);
        }

        if (window.elementor && elementor.notifications) {
            elementor.notifications.showToast({
                message: (rawnaqTimelineEditor && rawnaqTimelineEditor.i18n && rawnaqTimelineEditor.i18n.applied)
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
})(jQuery);
