/**
 * Elementor editor: refresh Flow Chart parent SELECT options from current Node IDs.
 */
(function ($) {
    'use strict';

    function collectOptions(nodes) {
        var options = { '': (window.rawnaqFlowEditor && rawnaqFlowEditor.i18n && rawnaqFlowEditor.i18n.root)
            ? rawnaqFlowEditor.i18n.root
            : '— Root (no parent) —' };
        (nodes || []).forEach(function (n) {
            var id = (n && n.node_id) ? String(n.node_id).replace(/[^a-zA-Z0-9_-]/g, '') : '';
            if (!id) {
                return;
            }
            var title = (n.title || id).toString();
            options[id] = title + ' (' + id + ')';
        });
        return options;
    }

    function applyOptionsToModel(model, options) {
        if (!model || !model.controls || !model.controls.nodes) {
            return;
        }
        var fields = model.controls.nodes.fields;
        if (!fields || !fields.parent_id) {
            return;
        }
        fields.parent_id.options = options;
    }

    function refreshFromView(view) {
        try {
            var model = view && (view.getEditModel ? view.getEditModel() : view.model);
            if (!model) {
                return;
            }
            var settings = model.get('settings');
            var nodes = (settings && settings.get) ? settings.get('nodes') : null;
            if (!nodes && settings && settings.attributes) {
                nodes = settings.attributes.nodes;
            }
            var options = collectOptions(nodes || []);
            applyOptionsToModel(model, options);
            // Ensure current parent values remain selectable even if renamed mid-edit.
            (nodes || []).forEach(function (n) {
                var pid = n && n.parent_id ? String(n.parent_id) : '';
                if (pid && !options[pid]) {
                    options[pid] = pid;
                }
            });
            applyOptionsToModel(model, options);
        } catch (e) { /* ignore */ }
    }

    $(window).on('elementor:init', function () {
        elementor.hooks.addAction('panel/open_editor/widget/rawnaq_flow_chart', function (panel, model, view) {
            refreshFromView(view);
            if (!view || view.__rawnaqFlowParentBound) {
                return;
            }
            view.__rawnaqFlowParentBound = true;
            view.listenTo(view.getEditModel().get('settings'), 'change:nodes', function () {
                refreshFromView(view);
            });
        });
    });
})(jQuery);
