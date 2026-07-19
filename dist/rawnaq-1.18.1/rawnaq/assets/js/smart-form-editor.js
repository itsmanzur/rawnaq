/**
 * Elementor editor: Apply Smart Form layout preset → replace fields repeater.
 */
(function ($) {
    'use strict';

    function getPresets() {
        return (window.rawnaqSmartFormEditor && rawnaqSmartFormEditor.presets) ? rawnaqSmartFormEditor.presets : {};
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
            if (widgetType === 'rawnaq_smart_form') {
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

        var presetKey = settings.get('layout_preset') || '';
        if (!presetKey) {
            if (window.elementor && elementor.notifications) {
                elementor.notifications.showToast({
                    message: (rawnaqSmartFormEditor && rawnaqSmartFormEditor.i18n && rawnaqSmartFormEditor.i18n.pick)
                        ? rawnaqSmartFormEditor.i18n.pick
                        : 'Pick a layout preset, then Apply.'
                });
            }
            return;
        }

        var pack = getPresets()[presetKey];
        if (!pack || !pack.fields) {
            return;
        }

        var next = { fields: pack.fields };

        if (window.$e && $e.run) {
            $e.run('document/elements/settings', {
                container: container,
                settings: next,
                options: { external: true }
            });
        } else if (typeof settings.setExternalChange === 'function') {
            settings.setExternalChange('fields', pack.fields);
        }

        if (window.elementor && elementor.notifications) {
            elementor.notifications.showToast({
                message: (rawnaqSmartFormEditor && rawnaqSmartFormEditor.i18n && rawnaqSmartFormEditor.i18n.applied)
                    ? rawnaqSmartFormEditor.i18n.applied
                    : 'Preset applied — fields updated.'
            });
        }
    }

    $(window).on('elementor:init', function () {
        elementor.channels.editor.on('rawnaq:smartform:applyPreset', applyPreset);
    });

    if (window.elementor && elementor.channels && elementor.channels.editor) {
        elementor.channels.editor.on('rawnaq:smartform:applyPreset', applyPreset);
    }
})(jQuery);
