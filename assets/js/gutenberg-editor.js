(function(blocks, editor, element, components) {
    var el = element.createElement;
    var registerBlockType = blocks.registerBlockType;
    var InspectorControls = editor.InspectorControls;
    var PanelBody = components.PanelBody;
    var TextControl = components.TextControl;
    var TextareaControl = components.TextareaControl;
    var RangeControl = components.RangeControl;
    var Button = components.Button;
    var SelectControl = components.SelectControl;
    var useEffect = element.useEffect;
    var Fragment = element.Fragment;

    function safeParseJson(raw, fallback) {
        try {
            var parsed = JSON.parse(raw || '[]');
            return Array.isArray(parsed) ? parsed : fallback;
        } catch (e) {
            return fallback;
        }
    }

    // ─────────────────────────────────────────────────────────
    // 1. HUB DIAGRAM BLOCK
    // ─────────────────────────────────────────────────────────
    registerBlockType('rawnaq/hub-diagram', {
        title: 'Hub Diagram (Rawnaq)',
        icon: 'networking',
        category: 'design',
        attributes: {
            centerTitle:    { type: 'string', default: 'STUDY 2D & 3D' },
            centerSubtitle: { type: 'string', default: "REVIEW WITH\nCLIENT" },
            lineColor:      { type: 'string', default: '#c2c2c2' },
            seg1Color:      { type: 'string', default: '#E8793A' },
            seg2Color:      { type: 'string', default: '#D4A92A' },
            seg3Color:      { type: 'string', default: '#26B8B8' },
            cardShape:      { type: 'string', default: 'rect' },
            lineStyle:      { type: 'string', default: 'solid' },
            glowLines:      { type: 'string', default: 'no' },
            centerStyle:    { type: 'string', default: 'conic' },
            layoutFlow:     { type: 'string', default: 'horizontal' },
            importJson:     { type: 'string', default: '' },
            height:         { type: 'number', default: 540 },
            topNodesJson:   { type: 'string', default: '[{"label":"Design","color":"#E8793A","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-art","link":"","target":"_self"},{"label":"P&ID","color":"#D4A92A","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-editor-justify","link":"","target":"_self"},{"label":"Sketch","color":"#26B8B8","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-welcome-write-blog","link":"","target":"_self"},{"label":"Specification","color":"#E8793A","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-clipboard","link":"","target":"_self"}]' },
            botNodesJson:   { type: 'string', default: '[{"label":"MTO/BOQ","color":"#E8793A","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-list-view","link":"","target":"_self"},{"label":"3D CAD Model","color":"#D4A92A","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-format-image","link":"","target":"_self"},{"label":"Drawings","color":"#26B8B8","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-portfolio","link":"","target":"_self"},{"label":"Pipe Isometric","color":"#E8793A","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-chart-area","link":"","target":"_self"}]' },
        },
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var uniqueId = 'editor-hub-' + (props.clientId || 'preview');
            var topNodes = safeParseJson(attributes.topNodesJson, []);
            var botNodes = safeParseJson(attributes.botNodesJson, []);

            function updateTopNodes(updated) {
                setAttributes({ topNodesJson: JSON.stringify(updated) });
            }
            function updateBotNodes(updated) {
                setAttributes({ botNodesJson: JSON.stringify(updated) });
            }

            function renderNodeManager(nodes, updateFunc, label) {
                var nodeElements = nodes.map(function(node, index) {
                    return el('div', {
                        style: { background: '#f1f1f1', padding: '10px', marginBottom: '12px', borderRadius: '6px', borderLeft: '4px solid ' + (node.color || '#E8793A') },
                        key: index
                    },
                        el(TextControl, {
                            label: 'Node Label', value: node.label,
                            onChange: function(newVal) { var updated = nodes.slice(); updated[index] = Object.assign({}, updated[index], { label: newVal }); updateFunc(updated); }
                        }),
                        el(TextControl, {
                            label: 'Bar Color', value: node.color || '#E8793A',
                            onChange: function(newVal) { var updated = nodes.slice(); updated[index] = Object.assign({}, updated[index], { color: newVal }); updateFunc(updated); }
                        }),
                        el(TextControl, {
                            label: 'Card Background', value: node.cardBg || '#ffffff',
                            onChange: function(newVal) { var updated = nodes.slice(); updated[index] = Object.assign({}, updated[index], { cardBg: newVal }); updateFunc(updated); }
                        }),
                        el(TextControl, {
                            label: 'Text Color', value: node.cardColor || '#1a1a1a',
                            onChange: function(newVal) { var updated = nodes.slice(); updated[index] = Object.assign({}, updated[index], { cardColor: newVal }); updateFunc(updated); }
                        }),
                        el(TextControl, {
                            label: 'Dashicon Class Name', value: node.icon || '', placeholder: 'dashicons-admin-generic',
                            onChange: function(newVal) { var updated = nodes.slice(); updated[index] = Object.assign({}, updated[index], { icon: newVal }); updateFunc(updated); }
                        }),
                        el(TextControl, {
                            label: 'Redirect URL', value: node.link || '', placeholder: 'https://...',
                            onChange: function(newVal) { var updated = nodes.slice(); updated[index] = Object.assign({}, updated[index], { link: newVal }); updateFunc(updated); }
                        }),
                        el(SelectControl, {
                            label: 'Link Target', value: node.target || '_self',
                            options: [ { label: 'Same Tab', value: '_self' }, { label: 'New Tab', value: '_blank' } ],
                            onChange: function(newVal) { var updated = nodes.slice(); updated[index] = Object.assign({}, updated[index], { target: newVal }); updateFunc(updated); }
                        }),
                        el(Button, {
                            isDestructive: true, isSmall: true,
                            onClick: function() { var updated = nodes.filter(function(_, idx) { return idx !== index; }); updateFunc(updated); }
                        }, 'Remove')
                    );
                });
                return el('div', {},
                    el('h4', { style: { marginTop: '0', marginBottom: '10px' } }, label),
                    nodeElements,
                    el(Button, {
                        isSecondary: true,
                        onClick: function() {
                            var updated = nodes.concat({ label: 'New Node', color: '#E8793A', cardBg: '#ffffff', cardColor: '#1a1a1a', icon: '', link: '', target: '_self' });
                            updateFunc(updated);
                        }
                    }, '+ Add Node')
                );
            }

            useEffect(function() {
                var host = document.getElementById(uniqueId);
                if (!host || !window.HubDiagram) return;
                var map = function(arr) {
                    return arr.map(function(n, i) {
                        return {
                            id: 'n' + i,
                            label: n.label,
                            color: n.color,
                            cardBg: n.cardBg,
                            cardColor: n.cardColor,
                            icon: n.icon,
                            link: n.link,
                            target: n.target
                        };
                    });
                };
                var config = {
                    centerTitle: attributes.centerTitle,
                    centerSubtitle: attributes.centerSubtitle,
                    lineColor: attributes.lineColor,
                    seg1Color: attributes.seg1Color,
                    seg2Color: attributes.seg2Color,
                    seg3Color: attributes.seg3Color,
                    cardShape: attributes.cardShape,
                    lineStyle: attributes.lineStyle,
                    glowLines: attributes.glowLines,
                    centerStyle: attributes.centerStyle,
                    layoutFlow: attributes.layoutFlow,
                    importJson: attributes.importJson,
                    top: map(topNodes),
                    bottom: map(botNodes)
                };
                host.setAttribute('data-hub', JSON.stringify(config));
                window.HubDiagram.init(host);
            }, [
                uniqueId,
                attributes.centerTitle,
                attributes.centerSubtitle,
                attributes.lineColor,
                attributes.seg1Color,
                attributes.seg2Color,
                attributes.seg3Color,
                attributes.cardShape,
                attributes.lineStyle,
                attributes.glowLines,
                attributes.centerStyle,
                attributes.layoutFlow,
                attributes.importJson,
                attributes.topNodesJson,
                attributes.botNodesJson,
                attributes.height
            ]);

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Center Circle Settings', initialOpen: true },
                        el(TextControl, { label: 'Title', value: attributes.centerTitle, onChange: function(newVal) { setAttributes({ centerTitle: newVal }); } }),
                        el(TextareaControl, { label: 'Subtitle', value: attributes.centerSubtitle, onChange: function(newVal) { setAttributes({ centerSubtitle: newVal }); } }),
                        el(TextareaControl, { label: 'JSON Import Override', value: attributes.importJson, placeholder: '[{"label":"Step 1","color":"#E8793A"}]', onChange: function(newVal) { setAttributes({ importJson: newVal }); } })
                    ),
                    el(PanelBody, { title: 'Top Row / Left Col Nodes', initialOpen: false }, renderNodeManager(topNodes, updateTopNodes, 'Top Row / Left Col')),
                    el(PanelBody, { title: 'Bottom Row / Right Col Nodes', initialOpen: false }, renderNodeManager(botNodes, updateBotNodes, 'Bottom Row / Right Col')),
                    el(PanelBody, { title: 'Style & Colors Settings', initialOpen: false },
                        el(RangeControl, { label: 'Diagram Height (px)', value: attributes.height, onChange: function(newVal) { setAttributes({ height: newVal }); }, min: 300, max: 900 }),
                        el(SelectControl, {
                            label: 'Layout Flow direction', value: attributes.layoutFlow,
                            options: [ { label: 'Horizontal Rows', value: 'horizontal' }, { label: 'Vertical Columns (Left/Right)', value: 'vertical' }, { label: 'Radial (360° Circular)', value: 'radial' } ],
                            onChange: function(newVal) { setAttributes({ layoutFlow: newVal }); }
                        }),
                        el(SelectControl, {
                            label: 'Card Shape Style', value: attributes.cardShape,
                            options: [ { label: 'Rectangle Box', value: 'rect' }, { label: 'Rounded Pill', value: 'pill' }, { label: 'Minimal Outline', value: 'outline' } ],
                            onChange: function(newVal) { setAttributes({ cardShape: newVal }); }
                        }),
                        el(SelectControl, {
                            label: 'Connector Line Type', value: attributes.lineStyle,
                            options: [ { label: 'Solid Line', value: 'solid' }, { label: 'Dashed Line', value: 'dashed' }, { label: 'Dotted Line', value: 'dotted' } ],
                            onChange: function(newVal) { setAttributes({ lineStyle: newVal }); }
                        }),
                        el(SelectControl, {
                            label: 'Glow Flow Line Animation', value: attributes.glowLines,
                            options: [ { label: 'Disable', value: 'no' }, { label: 'Enable', value: 'yes' } ],
                            onChange: function(newVal) { setAttributes({ glowLines: newVal }); }
                        }),
                        el(SelectControl, {
                            label: 'Center Circle Ring Style', value: attributes.centerStyle,
                            options: [ { label: 'Conic Segments Ring', value: 'conic' }, { label: 'Solid Color Flat Ring', value: 'solid' } ],
                            onChange: function(newVal) { setAttributes({ centerStyle: newVal }); }
                        }),
                        el(TextControl, { label: 'Line Color (Hex)', value: attributes.lineColor, onChange: function(newVal) { setAttributes({ lineColor: newVal }); } }),
                        el(TextControl, { label: 'Segment 1 / Solid Color (Hex)', value: attributes.seg1Color, onChange: function(newVal) { setAttributes({ seg1Color: newVal }); } }),
                        el(TextControl, { label: 'Segment 2 Color (Hex)', value: attributes.seg2Color, onChange: function(newVal) { setAttributes({ seg2Color: newVal }); } }),
                        el(TextControl, { label: 'Segment 3 Color (Hex)', value: attributes.seg3Color, onChange: function(newVal) { setAttributes({ seg3Color: newVal }); } })
                    )
                ),
                el('div', {
                    id: uniqueId,
                    className: 'hub-diagram-host',
                    style: { height: attributes.height + 'px', border: '1px dashed #ccc', background: '#f9f9f9' }
                })
            );
        },
        save: function() { return null; }
    });

    // ─────────────────────────────────────────────────────────
    // 2. 3D TILT CARD BLOCK
    // ─────────────────────────────────────────────────────────
    registerBlockType('rawnaq/tilt-card', {
        title: '3D Tilt Card (Rawnaq)',
        icon: 'parallax',
        category: 'design',
        attributes: {
            title:   { type: 'string', default: 'Creative Service' },
            desc:    { type: 'string', default: 'We design premium, high-speed interfaces.' },
            icon:    { type: 'string', default: 'dashicons-admin-generic' },
            link:    { type: 'string', default: '' },
            target:  { type: 'string', default: '_self' },
            maxTilt: { type: 'number', default: 15 },
        },
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Card Content Settings', initialOpen: true },
                        el(TextControl, { label: 'Title', value: attributes.title, onChange: function(val) { setAttributes({ title: val }); } }),
                        el(TextareaControl, { label: 'Description', value: attributes.desc, onChange: function(val) { setAttributes({ desc: val }); } }),
                        el(TextControl, { label: 'Dashicon Class Name', value: attributes.icon, onChange: function(val) { setAttributes({ icon: val }); } }),
                        el(TextControl, { label: 'Redirect Link', value: attributes.link, onChange: function(val) { setAttributes({ link: val }); } }),
                        el(SelectControl, {
                            label: 'Link Target', value: attributes.target,
                            options: [ { label: 'Same Tab', value: '_self' }, { label: 'New Tab', value: '_blank' } ],
                            onChange: function(val) { setAttributes({ target: val }); }
                        })
                    ),
                    el(PanelBody, { title: 'Tilt Animation Settings', initialOpen: false },
                        el(RangeControl, { label: 'Max Tilt Angle', value: attributes.maxTilt, onChange: function(val) { setAttributes({ maxTilt: val }); }, min: 5, max: 45 })
                    )
                ),
                el('div', { className: 'rawnaq-tilt-container' },
                    el('div', { className: 'rawnaq-tilt-card', style: { border: '1px dashed #ccc' } },
                        el('span', { className: 'rawnaq-tilt-icon dashicons ' + attributes.icon }),
                        el('div', { className: 'rawnaq-tilt-content' },
                            el('h3', { className: 'rawnaq-tilt-title' }, attributes.title),
                            el('p', { className: 'rawnaq-tilt-desc' }, attributes.desc)
                        )
                    )
                )
            );
        },
        save: function() { return null; }
    });

    // ─────────────────────────────────────────────────────────
    // 3. SCROLL TIMELINE BLOCK
    // ─────────────────────────────────────────────────────────
    registerBlockType('rawnaq/scroll-timeline', {
        title: 'Scroll Sync Timeline (Rawnaq)',
        icon: 'time-line',
        category: 'design',
        attributes: {
            stepsJson: { type: 'string', default: '[{"title":"Step 1: Ideation","desc":"Gather blueprints."},{"title":"Step 2: Prototyping","desc":"Presentation mockups."},{"title":"Step 3: Deployment","desc":"Deploy clean, fast code."}]' },
        },
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var steps = safeParseJson(attributes.stepsJson, []);

            function updateSteps(newSteps) {
                setAttributes({ stepsJson: JSON.stringify(newSteps) });
            }

            var stepFields = steps.map(function(step, idx) {
                return el('div', { style: { background: '#f1f1f1', padding: '10px', marginBottom: '10px', borderRadius: '6px' }, key: idx },
                    el(TextControl, {
                        label: 'Step Title', value: step.title,
                        onChange: function(val) { var updated = steps.slice(); updated[idx] = Object.assign({}, updated[idx], { title: val }); updateSteps(updated); }
                    }),
                    el(TextareaControl, {
                        label: 'Step Description', value: step.desc,
                        onChange: function(val) { var updated = steps.slice(); updated[idx] = Object.assign({}, updated[idx], { desc: val }); updateSteps(updated); }
                    }),
                    el(Button, {
                        isDestructive: true, isSmall: true,
                        onClick: function() { var updated = steps.filter(function(_, i) { return i !== idx; }); updateSteps(updated); }
                    }, 'Remove')
                );
            });

            var timelineElements = steps.map(function(step, idx) {
                var alignment = (idx % 2 === 0) ? 'left-item' : 'right-item';
                return el('div', { className: 'rawnaq-timeline-item ' + alignment, key: idx },
                    el('span', { className: 'rawnaq-timeline-bullet' }),
                    el('div', { className: 'rawnaq-timeline-card' },
                        el('h4', {}, step.title),
                        el('p', {}, step.desc)
                    )
                );
            });

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Timeline Steps', initialOpen: true },
                        stepFields,
                        el(Button, {
                            isSecondary: true,
                            onClick: function() { var updated = steps.concat({ title: 'New step Milestone', desc: 'Step details...' }); updateSteps(updated); }
                        }, '+ Add Milestone')
                    )
                ),
                el('div', { className: 'rawnaq-timeline-wrapper' },
                    el('div', { className: 'rawnaq-timeline-line-bg' }),
                    el('div', { className: 'rawnaq-timeline-line-active', style: { height: '30%' } }),
                    timelineElements
                )
            );
        },
        save: function() { return null; }
    });

    // ─────────────────────────────────────────────────────────
    // 4. FLOATING DOCK BLOCK
    // ─────────────────────────────────────────────────────────
    registerBlockType('rawnaq/floating-dock', {
        title: 'Floating Dock Menu (Rawnaq)',
        icon: 'navigator',
        category: 'design',
        attributes: {
            position:  { type: 'string', default: 'bottom' },
            itemsJson: { type: 'string', default: '[{"label":"Home","icon":"dashicons-admin-home","link":"#","color":"#6366f1"},{"label":"Messages","icon":"dashicons-email-alt","link":"#","color":"#6366f1"},{"label":"Settings","icon":"dashicons-admin-generic","link":"#","color":"#6366f1"}]' },
        },
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var items = safeParseJson(attributes.itemsJson, []);

            function updateItems(newItems) {
                setAttributes({ itemsJson: JSON.stringify(newItems) });
            }

            var itemFields = items.map(function(item, idx) {
                return el('div', { style: { background: '#f1f1f1', padding: '10px', marginBottom: '10px', borderRadius: '6px' }, key: idx },
                    el(TextControl, {
                        label: 'Label', value: item.label,
                        onChange: function(val) { var updated = items.slice(); updated[idx] = Object.assign({}, updated[idx], { label: val }); updateItems(updated); }
                    }),
                    el(TextControl, {
                        label: 'Dashicon Class', value: item.icon,
                        onChange: function(val) { var updated = items.slice(); updated[idx] = Object.assign({}, updated[idx], { icon: val }); updateItems(updated); }
                    }),
                    el(TextControl, {
                        label: 'Link URL', value: item.link,
                        onChange: function(val) { var updated = items.slice(); updated[idx] = Object.assign({}, updated[idx], { link: val }); updateItems(updated); }
                    }),
                    el(TextControl, {
                        label: 'Hover Color (Hex)', value: item.color || '#6366f1',
                        onChange: function(val) { var updated = items.slice(); updated[idx] = Object.assign({}, updated[idx], { color: val }); updateItems(updated); }
                    }),
                    el(Button, {
                        isDestructive: true, isSmall: true,
                        onClick: function() { var updated = items.filter(function(_, i) { return i !== idx; }); updateItems(updated); }
                    }, 'Remove')
                );
            });

            var previewIcons = items.map(function(item, idx) {
                return el('div', { className: 'rawnaq-dock-item', key: idx, style: { width: '42px', height: '42px', background: '#fff', border: '1px solid #ddd', borderRadius: '8px', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 4px' } },
                    el('span', { className: 'dashicons ' + item.icon })
                );
            });

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Dock Orientation Alignment', initialOpen: true },
                        el(SelectControl, {
                            label: 'Position Layout', value: attributes.position,
                            options: [
                                { label: 'Bottom Sheet Center', value: 'bottom' },
                                { label: 'Sidebar Left', value: 'left' },
                                { label: 'Sidebar Right', value: 'right' }
                            ],
                            onChange: function(val) { setAttributes({ position: val }); }
                        })
                    ),
                    el(PanelBody, { title: 'Dock Actions Items', initialOpen: false },
                        itemFields,
                        el(Button, {
                            isSecondary: true,
                            onClick: function() { var updated = items.concat({ label: 'Action', icon: 'dashicons-admin-generic', link: '#', color: '#6366f1' }); updateItems(updated); }
                        }, '+ Add Action Link')
                    )
                ),
                el('div', { style: { display: 'flex', justifyContent: 'center', padding: '16px', background: '#eaeaea', borderRadius: '12px' } },
                    el('div', { style: { display: 'flex', background: 'rgba(255,255,255,0.6)', padding: '6px', borderRadius: '12px' } },
                        previewIcons
                    )
                )
            );
        },
        save: function() { return null; }
    });

})(
    window.wp.blocks,
    window.wp.blockEditor || window.wp.editor,
    window.wp.element,
    window.wp.components
);
