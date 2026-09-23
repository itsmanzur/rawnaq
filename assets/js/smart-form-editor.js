/**
 * Elementor editor: Apply Smart Form layout preset → replace fields repeater.
 */
(function ($) {
    'use strict';

    function getPresets() {
        return (window.rawnaqSmartFormEditor && rawnaqSmartFormEditor.presets) ? rawnaqSmartFormEditor.presets : {};
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
        var settings = (view && view.elementSettingsModel) || (container && container.settings);

        var presetKey = '';
        if (settings && typeof settings.get === 'function') {
            presetKey = settings.get('layout_preset') || '';
        }
        if (!presetKey) {
            var select = document.querySelector('.elementor-control-layout_preset select');
            if (select && select.value) {
                presetKey = select.value;
            }
        }

        if (!presetKey) {
            if (window.elementor && elementor.notifications) {
                elementor.notifications.showToast({
                    message: (window.rawnaqSmartFormEditor && rawnaqSmartFormEditor.i18n && rawnaqSmartFormEditor.i18n.pick)
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

        // Attach unique _id to each repeater item so Elementor Backbone collections diff cleanly
        var fieldsWithIds = pack.fields.map(function (field, index) {
            var item = Object.assign({}, field);
            if (!item._id) {
                if (window.elementorCommon && elementorCommon.helpers && elementorCommon.helpers.getUniqueId) {
                    item._id = elementorCommon.helpers.getUniqueId();
                } else {
                    item._id = Math.random().toString(36).substring(2, 9) + index;
                }
            }
            return item;
        });

        var next = { fields: fieldsWithIds };

        if (window.$e && $e.run && container) {
            $e.run('document/elements/settings', {
                container: container,
                settings: next,
                options: { external: true }
            });
        } else if (settings && typeof settings.setExternalChange === 'function') {
            settings.setExternalChange('fields', fieldsWithIds);
        } else if (settings && typeof settings.set === 'function') {
            settings.set('fields', fieldsWithIds);
        }

        // Re-render current page view so repeater list in sidebar refreshes immediately
        try {
            if (window.elementor && elementor.getPanelView) {
                var panel = elementor.getPanelView();
                var curPage = panel && panel.getCurrentPageView && panel.getCurrentPageView();
                if (curPage && typeof curPage.render === 'function') {
                    curPage.render();
                }
            }
        } catch (err) { /* ignore */ }

        if (window.elementor && elementor.notifications) {
            elementor.notifications.showToast({
                message: (window.rawnaqSmartFormEditor && rawnaqSmartFormEditor.i18n && rawnaqSmartFormEditor.i18n.applied)
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
