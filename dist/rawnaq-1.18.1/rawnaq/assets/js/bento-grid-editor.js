/**
 * Elementor editor: Apply Bento Grid preset → replace cells repeater.
 */
(function ($) {
    'use strict';

    function getPresets() {
        return (window.rawnaqBentoEditor && rawnaqBentoEditor.presets) ? rawnaqBentoEditor.presets : {};
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
            if (widgetType === 'rawnaq_bento_grid') {
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

        var presetKey = settings.get('preset') || 'featured';
        if (presetKey === 'custom') {
            if (window.elementor && elementor.notifications) {
                elementor.notifications.showToast({
                    message: (rawnaqBentoEditor && rawnaqBentoEditor.i18n && rawnaqBentoEditor.i18n.customHint)
                        ? rawnaqBentoEditor.i18n.customHint
                        : 'Pick a layout preset (not Custom), then Apply.'
                });
            }
            return;
        }

        var pack = getPresets()[presetKey];
        if (!pack || !pack.cells) {
            return;
        }

        var next = {
            cells: pack.cells
        };
        if (pack.columns) {
            next.columns = String(pack.columns);
        }

        if (window.$e && $e.run) {
            $e.run('document/elements/settings', {
                container: container,
                settings: next,
                options: { external: true }
            });
        } else if (typeof settings.setExternalChange === 'function') {
            Object.keys(next).forEach(function (key) {
                settings.setExternalChange(key, next[key]);
            });
        }

        if (window.elementor && elementor.notifications) {
            elementor.notifications.showToast({
                message: (rawnaqBentoEditor && rawnaqBentoEditor.i18n && rawnaqBentoEditor.i18n.applied)
                    ? rawnaqBentoEditor.i18n.applied
                    : 'Preset applied — cells updated.'
            });
        }
    }

    $(window).on('elementor:init', function () {
        elementor.channels.editor.on('rawnaq:bento:applyPreset', applyPreset);
    });

    // Late bind if Elementor already initialized
    if (window.elementor && elementor.channels && elementor.channels.editor) {
        elementor.channels.editor.on('rawnaq:bento:applyPreset', applyPreset);
    }
})(jQuery);
