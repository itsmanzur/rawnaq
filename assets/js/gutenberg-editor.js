(function(blocks, editor, element, components, data) {
    var el = element.createElement;
    var registerBlockType = blocks.registerBlockType;
    var createBlock = blocks.createBlock;
    var InspectorControls = editor.InspectorControls;
    var InnerBlocks = editor.InnerBlocks;
    var MediaUpload = editor.MediaUpload;
    var MediaUploadCheck = editor.MediaUploadCheck;
    var PanelBody = components.PanelBody;
    var TextControl = components.TextControl;
    var TextareaControl = components.TextareaControl;
    var RangeControl = components.RangeControl;
    var Button = components.Button;
    var SelectControl = components.SelectControl;
    var ToggleControl = components.ToggleControl;
    var ColorPalette = components.ColorPalette;
    var useEffect = element.useEffect;
    var useRef = element.useRef;
    var Fragment = element.Fragment;
    var useSelect = data && data.useSelect;
    var useDispatch = data && data.useDispatch;
    if (!useSelect) {
        useSelect = function(fn) { return fn({ getBlock: function() { return null; } }); };
    }
    if (!useDispatch) {
        useDispatch = function() { return { replaceInnerBlocks: function() {} }; };
    }

    function safeParseJson(raw, fallback) {
        try {
            var parsed = JSON.parse(raw || '[]');
            return Array.isArray(parsed) ? parsed : fallback;
        } catch (e) {
            return fallback;
        }
    }

    function bentoParseVideo(url) {
        var raw = (url || '').toString().trim();
        if (!raw) {
            return null;
        }
        var yt = raw.match(/(?:youtube\.com\/(?:watch\?(?:[^#]*&)?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/i);
        if (yt) {
            return {
                kind: 'youtube',
                embed: 'https://www.youtube-nocookie.com/embed/' + yt[1] + '?rel=0&modestbranding=1'
            };
        }
        var vim = raw.match(/(?:player\.)?vimeo\.com\/(?:video\/)?(\d+)/i);
        if (vim) {
            return {
                kind: 'vimeo',
                embed: 'https://player.vimeo.com/video/' + vim[1] + '?title=0&byline=0&portrait=0'
            };
        }
        return { kind: 'file', src: raw };
    }

    function bentoTagEl(el, cell) {
        if (!cell.tag) {
            return null;
        }
        var cls = 'rawnaq-bento-tag';
        var style = {};
        if (cell.tagBg || cell.tagColor) {
            cls += ' has-custom';
            if (cell.tagBg) {
                style.background = cell.tagBg;
                style['--bento-tag-cell-bg'] = cell.tagBg;
            }
            if (cell.tagColor) {
                style.color = cell.tagColor;
                style['--bento-tag-cell-color'] = cell.tagColor;
            }
        }
        return el('div', { className: cls, style: style }, cell.tag);
    }

    function bentoCellLayout(cell) {
        var col = Math.max(1, parseInt(cell.col, 10) || 1);
        var row = Math.max(1, parseInt(cell.row, 10) || 1);
        var order = parseInt(cell.order, 10) || 0;
        var colMd = parseInt(cell.colMd, 10) || 0;
        var rowMd = parseInt(cell.rowMd, 10) || 0;
        var orderMd = parseInt(cell.orderMd, 10) || 0;
        var colSm = parseInt(cell.colSm, 10) || 0;
        var rowSm = parseInt(cell.rowSm, 10) || 0;
        var orderSm = parseInt(cell.orderSm, 10) || 0;
        var style = {
            gridColumn: 'span ' + col,
            gridRow: 'span ' + row,
            '--bento-span-col': String(col),
            '--bento-span-row': String(row)
        };
        if (order !== 0) {
            style.order = String(order);
            style['--bento-order'] = String(order);
        }
        if (colMd > 0) style['--bento-span-col-md'] = String(colMd);
        if (rowMd > 0) style['--bento-span-row-md'] = String(rowMd);
        if (orderMd !== 0) style['--bento-order-md'] = String(orderMd);
        if (colSm > 0) style['--bento-span-col-sm'] = String(colSm);
        if (rowSm > 0) style['--bento-span-row-sm'] = String(rowSm);
        if (orderSm !== 0) style['--bento-order-sm'] = String(orderSm);
        return { style: style, hasSmSpan: colSm > 0 };
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
            showExport:     { type: 'boolean', default: true },
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
                    export: attributes.showExport !== false,
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
                attributes.showExport,
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
                        el(ToggleControl, {
                            label: 'Show PNG / SVG export',
                            checked: attributes.showExport !== false,
                            onChange: function(v) { setAttributes({ showExport: !!v }); }
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
            title:         { type: 'string', default: 'Creative Service' },
            desc:          { type: 'string', default: 'We design premium, high-speed interfaces.' },
            icon:          { type: 'string', default: 'dashicons-star-filled' },
            imageUrl:      { type: 'string', default: '' },
            imageId:       { type: 'number', default: 0 },
            imageAlt:      { type: 'string', default: '' },
            badge:         { type: 'string', default: '' },
            ctaText:       { type: 'string', default: 'Learn more' },
            ctaLink:       { type: 'string', default: '' },
            link:          { type: 'string', default: '' },
            target:        { type: 'string', default: '_self' },
            maxTilt:       { type: 'number', default: 15 },
            contentAlign:  { type: 'string', default: 'bottom' },
            overlay:       { type: 'number', default: 0.7 },
            glare:         { type: 'number', default: 0.45 },
            hoverScale:    { type: 'number', default: 1.03 },
            radius:        { type: 'number', default: 20 },
            height:        { type: 'number', default: 380 },
            cardBg:        { type: 'string', default: '#ffffff' },
            badgeBg:       { type: 'string', default: '#6366f1' },
            badgeColor:    { type: 'string', default: '#ffffff' },
            titleColor:    { type: 'string', default: '' },
            descColor:     { type: 'string', default: '' },
            iconColor:     { type: 'string', default: '' },
            btnBg:         { type: 'string', default: '#6366f1' },
            btnColor:      { type: 'string', default: '#ffffff' },
            enableFlip:    { type: 'boolean', default: false },
            flipTrigger:   { type: 'string', default: 'hover' },
            backTitle:     { type: 'string', default: 'Why choose us' },
            backDesc:      { type: 'string', default: 'Add the extra detail or a persuasive reason on the reverse side.' },
            backCtaText:   { type: 'string', default: 'Get started' },
            backCtaLink:   { type: 'string', default: '' },
            backBg:        { type: 'string', default: '#4338ca' },
            backColor:     { type: 'string', default: '#ffffff' }
        },
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var hasImage = !!attributes.imageUrl;
            var ctaUrl = attributes.ctaLink || attributes.link;
            var enableFlip = !!attributes.enableFlip;
            var flipTrigger = attributes.flipTrigger === 'click' ? 'click' : 'hover';
            var cardStyle = {
                border: '1px dashed #ccc',
                borderRadius: attributes.radius + 'px',
                height: attributes.height + 'px',
                '--overlay': String(attributes.overlay),
                '--glare': String(attributes.glare),
                '--hover-scale': String(attributes.hoverScale),
                '--tilt-card-bg': attributes.cardBg || '#ffffff',
                '--tilt-badge-bg': attributes.badgeBg || '#6366f1',
                '--tilt-badge-color': attributes.badgeColor || '#ffffff',
                '--tilt-btn-bg': attributes.btnBg || '#6366f1',
                '--tilt-btn-color': attributes.btnColor || '#ffffff'
            };
            if (attributes.titleColor) cardStyle['--tilt-title'] = attributes.titleColor;
            if (attributes.descColor) cardStyle['--tilt-desc'] = attributes.descColor;
            if (attributes.iconColor) cardStyle['--tilt-icon'] = attributes.iconColor;

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Card Content', initialOpen: true },
                        el(MediaUploadCheck || 'div', {},
                            el(MediaUpload, {
                                onSelect: function(media) {
                                    setAttributes({
                                        imageUrl: media.url || '',
                                        imageId: media.id || 0,
                                        imageAlt: media.alt || attributes.title || ''
                                    });
                                },
                                allowedTypes: ['image'],
                                value: attributes.imageId,
                                render: function(obj) {
                                    return el('div', { style: { marginBottom: '16px' } },
                                        el('p', { style: { margin: '0 0 8px', fontWeight: 600 } }, 'Card Image'),
                                        hasImage ? el('img', {
                                            src: attributes.imageUrl,
                                            alt: attributes.imageAlt || '',
                                            style: { width: '100%', borderRadius: '8px', marginBottom: '8px', display: 'block' }
                                        }) : null,
                                        el(Button, {
                                            isSecondary: !hasImage,
                                            isPrimary: !!hasImage,
                                            onClick: obj.open
                                        }, hasImage ? 'Replace Image' : 'Upload Image'),
                                        hasImage ? el(Button, {
                                            isLink: true,
                                            isDestructive: true,
                                            style: { marginLeft: '10px' },
                                            onClick: function() {
                                                setAttributes({ imageUrl: '', imageId: 0, imageAlt: '' });
                                            }
                                        }, 'Remove') : null
                                    );
                                }
                            })
                        ),
                        el(TextControl, { label: 'Badge / Eyebrow', value: attributes.badge, onChange: function(val) { setAttributes({ badge: val }); } }),
                        el(TextControl, { label: 'Title', value: attributes.title, onChange: function(val) { setAttributes({ title: val }); } }),
                        el(TextareaControl, { label: 'Description', value: attributes.desc, onChange: function(val) { setAttributes({ desc: val }); } }),
                        el(TextControl, { label: 'Dashicon Class', value: attributes.icon, onChange: function(val) { setAttributes({ icon: val }); } }),
                        el(TextControl, { label: 'CTA Button Text', value: attributes.ctaText, onChange: function(val) { setAttributes({ ctaText: val }); } }),
                        el(TextControl, { label: 'CTA Link', value: attributes.ctaLink, onChange: function(val) { setAttributes({ ctaLink: val }); } }),
                        el(TextControl, { label: 'Card Stretch Link', value: attributes.link, onChange: function(val) { setAttributes({ link: val }); } }),
                        el(SelectControl, {
                            label: 'Link Target', value: attributes.target,
                            options: [ { label: 'Same Tab', value: '_self' }, { label: 'New Tab', value: '_blank' } ],
                            onChange: function(val) { setAttributes({ target: val }); }
                        })
                    ),
                    el(PanelBody, { title: 'Style & Colors', initialOpen: true },
                        el(TextControl, { label: 'Card Background (Hex)', value: attributes.cardBg || '#ffffff', onChange: function(val) { setAttributes({ cardBg: val }); } }),
                        el(TextControl, { label: 'Badge Background (Hex)', value: attributes.badgeBg || '#6366f1', onChange: function(val) { setAttributes({ badgeBg: val }); } }),
                        el(TextControl, { label: 'Badge Text (Hex)', value: attributes.badgeColor || '#ffffff', onChange: function(val) { setAttributes({ badgeColor: val }); } }),
                        el(TextControl, {
                            label: 'Title Color (Hex)',
                            value: attributes.titleColor || '',
                            help: 'Leave empty for auto (dark / white on image)',
                            onChange: function(val) { setAttributes({ titleColor: val }); }
                        }),
                        el(TextControl, {
                            label: 'Description Color (Hex)',
                            value: attributes.descColor || '',
                            help: 'Leave empty for auto',
                            onChange: function(val) { setAttributes({ descColor: val }); }
                        }),
                        el(TextControl, {
                            label: 'Icon Color (Hex)',
                            value: attributes.iconColor || '',
                            help: 'Leave empty for auto',
                            onChange: function(val) { setAttributes({ iconColor: val }); }
                        }),
                        el(TextControl, { label: 'Button Background (Hex)', value: attributes.btnBg || '#6366f1', onChange: function(val) { setAttributes({ btnBg: val }); } }),
                        el(TextControl, { label: 'Button Text (Hex)', value: attributes.btnColor || '#ffffff', onChange: function(val) { setAttributes({ btnColor: val }); } })
                    ),
                    el(PanelBody, { title: 'Layout & Motion', initialOpen: false },
                        el(SelectControl, {
                            label: 'Content Align', value: attributes.contentAlign,
                            options: [
                                { label: 'Top', value: 'top' },
                                { label: 'Center', value: 'center' },
                                { label: 'Bottom', value: 'bottom' }
                            ],
                            onChange: function(val) { setAttributes({ contentAlign: val }); }
                        }),
                        el(RangeControl, { label: 'Card Height', value: attributes.height, onChange: function(val) { setAttributes({ height: val }); }, min: 220, max: 560 }),
                        el(RangeControl, { label: 'Border Radius', value: attributes.radius, onChange: function(val) { setAttributes({ radius: val }); }, min: 0, max: 48 }),
                        el(RangeControl, { label: 'Max Tilt', value: attributes.maxTilt, onChange: function(val) { setAttributes({ maxTilt: val }); }, min: 0, max: 45 }),
                        el(RangeControl, {
                            label: 'Overlay Strength',
                            value: Math.round(attributes.overlay * 100),
                            onChange: function(val) { setAttributes({ overlay: (val || 0) / 100 }); },
                            min: 0, max: 100
                        }),
                        el(RangeControl, {
                            label: 'Glare Intensity',
                            value: Math.round(attributes.glare * 100),
                            onChange: function(val) { setAttributes({ glare: (val || 0) / 100 }); },
                            min: 0, max: 100
                        }),
                        el(RangeControl, {
                            label: 'Hover Scale (%)',
                            value: Math.round(attributes.hoverScale * 100),
                            onChange: function(val) { setAttributes({ hoverScale: (val || 100) / 100 }); },
                            min: 100, max: 108
                        })
                    ),
                    el(PanelBody, { title: 'Flip / Back Face', initialOpen: false },
                        el(ToggleControl, {
                            label: 'Enable flip',
                            checked: enableFlip,
                            help: '3D tilt pauses while flip is on.',
                            onChange: function(v) { setAttributes({ enableFlip: !!v }); }
                        }),
                        enableFlip ? el(SelectControl, {
                            label: 'Flip trigger',
                            value: flipTrigger,
                            options: [ { label: 'Hover', value: 'hover' }, { label: 'Click / Tap', value: 'click' } ],
                            onChange: function(v) { setAttributes({ flipTrigger: v }); }
                        }) : null,
                        enableFlip ? el(TextControl, { label: 'Back title', value: attributes.backTitle, onChange: function(v) { setAttributes({ backTitle: v }); } }) : null,
                        enableFlip ? el(TextareaControl, { label: 'Back description', value: attributes.backDesc, onChange: function(v) { setAttributes({ backDesc: v }); } }) : null,
                        enableFlip ? el(TextControl, { label: 'Back CTA text', value: attributes.backCtaText, onChange: function(v) { setAttributes({ backCtaText: v }); } }) : null,
                        enableFlip ? el(TextControl, { label: 'Back CTA link', value: attributes.backCtaLink, onChange: function(v) { setAttributes({ backCtaLink: v }); } }) : null,
                        enableFlip ? el(TextControl, { label: 'Back background (Hex)', value: attributes.backBg || '#4338ca', onChange: function(v) { setAttributes({ backBg: v }); } }) : null,
                        enableFlip ? el(TextControl, { label: 'Back text (Hex)', value: attributes.backColor || '#ffffff', onChange: function(v) { setAttributes({ backColor: v }); } }) : null
                    )
                ),
                (function() {
                    var frontChildren = [
                        hasImage ? el('img', {
                            key: 'img',
                            className: 'rawnaq-tilt-image',
                            src: attributes.imageUrl,
                            alt: attributes.imageAlt || attributes.title || ''
                        }) : null,
                        hasImage ? el('span', { key: 'ov', className: 'rawnaq-tilt-overlay' }) : null,
                        el('span', { key: 'gl', className: 'rawnaq-tilt-glare' }),
                        attributes.badge ? el('span', { key: 'bd', className: 'rawnaq-tilt-badge' }, attributes.badge) : null,
                        attributes.icon ? el('span', { key: 'ic', className: 'rawnaq-tilt-icon dashicons ' + attributes.icon }) : null,
                        el('div', { key: 'ct', className: 'rawnaq-tilt-content' },
                            attributes.title ? el('h3', { className: 'rawnaq-tilt-title' }, attributes.title) : null,
                            attributes.desc ? el('p', { className: 'rawnaq-tilt-desc' }, attributes.desc) : null,
                            attributes.ctaText && ctaUrl
                                ? el('a', { className: 'rawnaq-tilt-btn', href: ctaUrl }, attributes.ctaText)
                                : (attributes.ctaText ? el('span', { className: 'rawnaq-tilt-btn is-static' }, attributes.ctaText) : null)
                        )
                    ];

                    var cardClass = 'rawnaq-tilt-card align-' + attributes.contentAlign + (hasImage ? ' has-image' : '');
                    if (enableFlip) { cardClass += ' is-flip flip-' + flipTrigger; }

                    var cardChildren;
                    if (enableFlip) {
                        var backCta = attributes.backCtaText || '';
                        cardChildren = [
                            el('div', { key: 'flip', className: 'rawnaq-tilt-flip' },
                                el('div', { className: 'rawnaq-tilt-face rawnaq-tilt-front' }, frontChildren),
                                el('div', {
                                    className: 'rawnaq-tilt-back',
                                    style: { '--tilt-back-bg': attributes.backBg || '#4338ca', '--tilt-back-color': attributes.backColor || '#ffffff' }
                                },
                                    el('div', { className: 'rawnaq-tilt-back-inner' },
                                        attributes.backTitle ? el('h3', { className: 'rawnaq-tilt-back-title' }, attributes.backTitle) : null,
                                        attributes.backDesc ? el('p', { className: 'rawnaq-tilt-back-desc' }, attributes.backDesc) : null,
                                        backCta && attributes.backCtaLink
                                            ? el('a', { className: 'rawnaq-tilt-btn rawnaq-tilt-back-btn', href: attributes.backCtaLink }, backCta)
                                            : (backCta ? el('span', { className: 'rawnaq-tilt-btn is-static' }, backCta) : null)
                                    )
                                )
                            )
                        ];
                    } else {
                        cardChildren = frontChildren;
                    }

                    return el('div', { className: 'rawnaq-tilt-container' },
                        el('div', {
                            className: cardClass,
                            style: cardStyle,
                            'data-tilt-max': attributes.maxTilt,
                            'data-hover-scale': attributes.hoverScale,
                            'data-glare': attributes.glare
                        }, cardChildren)
                    );
                })()
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
            stepsJson: {
                type: 'string',
                default: '[{"meta":"Phase 1","title":"Ideation & Sketching","desc":"Gather initial ideas and draft blueprints.","icon":"","imageUrl":"","imageId":0,"video":"","ctaText":"","ctaLink":""},{"meta":"Phase 2","title":"Prototype Review","desc":"Interactive mockups and client reviews.","icon":"","imageUrl":"","imageId":0,"video":"","ctaText":"","ctaLink":""},{"meta":"Phase 3","title":"Development & Coding","desc":"Build, test, and deploy clean code.","icon":"","imageUrl":"","imageId":0,"video":"","ctaText":"","ctaLink":""}]'
            },
            layout: { type: 'string', default: 'alternating' },
            showNumbers: { type: 'boolean', default: true },
            timelineName: { type: 'string', default: '' },
            source: { type: 'string', default: 'manual' },
            postType: { type: 'string', default: 'post' },
            postsPerPage: { type: 'number', default: 6 },
            orderby: { type: 'string', default: 'date' },
            order: { type: 'string', default: 'DESC' },
            taxonomy: { type: 'string', default: '' },
            terms: { type: 'string', default: '' },
            includeIds: { type: 'string', default: '' },
            excludeIds: { type: 'string', default: '' },
            lineBg: { type: 'string', default: '#e2e8f0' },
            lineActive: { type: 'string', default: '#6366f1' },
            lineWidth: { type: 'number', default: 4 },
            bulletBorder: { type: 'string', default: '#cbd5e1' },
            bulletActive: { type: 'string', default: '#6366f1' },
            cardBg: { type: 'string', default: '#ffffff' },
            metaColor: { type: 'string', default: '#6366f1' },
            titleColor: { type: 'string', default: '#1a1a1a' },
            descColor: { type: 'string', default: '#666666' },
            ctaColor: { type: 'string', default: '#6366f1' },
            cardRadius: { type: 'number', default: 16 },
            bulletSize: { type: 'number', default: 28 },
            itemGap: { type: 'number', default: 20 },
            initialVisible: { type: 'number', default: 0 },
            loadChunk: { type: 'number', default: 3 },
            loadMoreText: { type: 'string', default: 'Load more' },
            agencyPreset: { type: 'string', default: '' }
        },
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var steps = safeParseJson(attributes.stepsJson, []);
            var layout = attributes.layout || 'alternating';
            var showNumbers = attributes.showNumbers !== false;
            var source = attributes.source || 'manual';
            var initialVisible = parseInt(attributes.initialVisible, 10) || 0;
            var loadChunk = parseInt(attributes.loadChunk, 10) || 3;
            var customTl = (attributes.timelineName || '').toString().replace(/[^a-zA-Z0-9_-]/g, '');
            var tlName = customTl || 'rawnaq-tl-editor';
            if (/^[0-9]/.test(tlName)) { tlName = 'tl-' + tlName; }
            var wrapStyle = {
                'scroll-timeline-name': '--' + tlName,
                '--tl-line-bg': attributes.lineBg || '#e2e8f0',
                '--tl-line-active': attributes.lineActive || '#6366f1',
                '--tl-line-width': (attributes.lineWidth || 4) + 'px',
                '--tl-bullet-border': attributes.bulletBorder || '#cbd5e1',
                '--tl-bullet-active': attributes.bulletActive || '#6366f1',
                '--tl-card-bg': attributes.cardBg || '#ffffff',
                '--tl-meta': attributes.metaColor || '#6366f1',
                '--tl-title': attributes.titleColor || '#1a1a1a',
                '--tl-desc': attributes.descColor || '#666666',
                '--tl-cta': attributes.ctaColor || '#6366f1',
                '--tl-card-radius': (attributes.cardRadius || 16) + 'px',
                '--tl-bullet-size': (attributes.bulletSize || 28) + 'px',
                '--tl-item-pad-y': (attributes.itemGap || 20) + 'px'
            };

            function updateSteps(newSteps) {
                setAttributes({ stepsJson: JSON.stringify(newSteps) });
            }

            function patchStep(idx, patch) {
                var updated = steps.slice();
                updated[idx] = Object.assign({}, updated[idx], patch);
                updateSteps(updated);
            }

            function sideClass(idx) {
                if (layout === 'horizontal') return 'h-item';
                if (layout === 'left') return 'left-item';
                if (layout === 'right') return 'right-item';
                return (idx % 2 === 0) ? 'left-item' : 'right-item';
            }

            var stepFields = steps.map(function(step, idx) {
                var hasImage = !!step.imageUrl;
                return el('div', { style: { background: '#f1f1f1', padding: '10px', marginBottom: '10px', borderRadius: '6px' }, key: idx },
                    el('p', { style: { margin: '0 0 8px', fontWeight: 700 } }, 'Step ' + (idx + 1)),
                    el(TextControl, {
                        label: 'Date / Label', value: step.meta || '',
                        onChange: function(val) { patchStep(idx, { meta: val }); }
                    }),
                    el(TextControl, {
                        label: 'Step Title', value: step.title || '',
                        onChange: function(val) { patchStep(idx, { title: val }); }
                    }),
                    el(TextareaControl, {
                        label: 'Step Description', value: step.desc || '',
                        onChange: function(val) { patchStep(idx, { desc: val }); }
                    }),
                    el(TextControl, {
                        label: 'Dashicon Class', value: step.icon || '',
                        help: 'e.g. dashicons-star-filled',
                        onChange: function(val) { patchStep(idx, { icon: val }); }
                    }),
                    el(TextControl, {
                        label: 'Video URL', value: step.video || '',
                        help: 'YouTube, Vimeo, or mp4/webm — prefers over image when set.',
                        onChange: function(val) { patchStep(idx, { video: val }); }
                    }),
                    el(MediaUploadCheck || 'div', {},
                        el(MediaUpload, {
                            onSelect: function(media) {
                                patchStep(idx, {
                                    imageUrl: media.url || '',
                                    imageId: media.id || 0
                                });
                            },
                            allowedTypes: ['image'],
                            value: step.imageId || 0,
                            render: function(obj) {
                                return el('div', { style: { marginBottom: '12px' } },
                                    el('p', { style: { margin: '0 0 6px', fontWeight: 600 } }, 'Step Image'),
                                    hasImage ? el('img', {
                                        src: step.imageUrl,
                                        alt: '',
                                        style: { width: '100%', borderRadius: '8px', marginBottom: '8px', display: 'block' }
                                    }) : null,
                                    el(Button, {
                                        isSecondary: !hasImage,
                                        isPrimary: !!hasImage,
                                        onClick: obj.open
                                    }, hasImage ? 'Replace Image' : 'Upload Image'),
                                    hasImage ? el(Button, {
                                        isLink: true,
                                        isDestructive: true,
                                        style: { marginLeft: '10px' },
                                        onClick: function() { patchStep(idx, { imageUrl: '', imageId: 0 }); }
                                    }, 'Remove') : null
                                );
                            }
                        })
                    ),
                    el(TextControl, {
                        label: 'CTA Text', value: step.ctaText || '',
                        onChange: function(val) { patchStep(idx, { ctaText: val }); }
                    }),
                    el(TextControl, {
                        label: 'CTA Link', value: step.ctaLink || '',
                        onChange: function(val) { patchStep(idx, { ctaLink: val }); }
                    }),
                    el(TextControl, {
                        label: 'Case-Study project ID',
                        value: step.projectId || '',
                        help: 'e.g. post-123 — highlights matching Case-Study card on scroll',
                        onChange: function(val) { patchStep(idx, { projectId: val }); }
                    }),
                    el(TextControl, {
                        label: 'Case-Study project slug',
                        value: step.projectSlug || '',
                        onChange: function(val) { patchStep(idx, { projectSlug: val }); }
                    }),
                    el(Button, {
                        isDestructive: true, isSmall: true,
                        onClick: function() { updateSteps(steps.filter(function(_, i) { return i !== idx; })); }
                    }, 'Remove')
                );
            });

            var timelineElements = steps.map(function(step, idx) {
                var num = (idx + 1 < 10) ? ('0' + (idx + 1)) : String(idx + 1);
                var children = [];
                var videoParsed = bentoParseVideo(step.video);
                if (videoParsed) {
                    if (videoParsed.kind === 'file') {
                        children.push(el('div', { className: 'rawnaq-timeline-media', key: 'vid' },
                            el('video', { className: 'rawnaq-bento-video', src: videoParsed.src, muted: true, playsInline: true })
                        ));
                    } else {
                        children.push(el('div', { className: 'rawnaq-timeline-media', key: 'vid' },
                            el('iframe', {
                                className: 'rawnaq-bento-embed',
                                src: videoParsed.embed,
                                title: 'Video',
                                loading: 'lazy'
                            })
                        ));
                    }
                } else if (step.imageUrl) {
                    children.push(el('img', { className: 'rawnaq-timeline-thumb', src: step.imageUrl, alt: '', key: 'img' }));
                }
                if (step.meta) {
                    children.push(el('span', { className: 'rawnaq-timeline-meta', key: 'meta' }, step.meta));
                }
                if (step.icon) {
                    children.push(el('span', { className: 'rawnaq-timeline-icon', key: 'icon' },
                        el('span', { className: 'dashicons ' + step.icon, 'aria-hidden': true })
                    ));
                }
                if (step.title) children.push(el('h4', { key: 'title' }, step.title));
                if (step.desc) children.push(el('p', { key: 'desc' }, step.desc));
                if (step.ctaText && step.ctaLink) {
                    children.push(el('a', { className: 'rawnaq-timeline-cta', href: step.ctaLink, key: 'cta' }, step.ctaText));
                }
                return el('div', { className: 'rawnaq-timeline-item ' + sideClass(idx) + ' item-active', key: idx },
                    el('span', { className: 'rawnaq-timeline-bullet' },
                        showNumbers ? el('span', { className: 'num' }, num) : null
                    ),
                    el('div', { className: 'rawnaq-timeline-card' }, children)
                );
            });

            var wrapClass = 'rawnaq-timeline-wrapper layout-' + layout + ' is-editor' + (showNumbers ? ' show-numbers' : '');
            try {
                if (window.CSS && CSS.supports && (CSS.supports('animation-timeline: view()') || CSS.supports('animation-timeline: scroll()'))) {
                    wrapClass += ' tl-css-driven';
                } else {
                    wrapClass += ' tl-js-driven';
                }
            } catch (eDetect) {
                wrapClass += ' tl-js-driven';
            }
            var showLoadMore = initialVisible > 0 && steps.length > initialVisible;

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Layout', initialOpen: true },
                        el(SelectControl, {
                            label: 'Steps Source',
                            value: source,
                            options: [
                                { label: 'Manual steps', value: 'manual' },
                                { label: 'Posts / CPT query', value: 'query' }
                            ],
                            onChange: function(val) { setAttributes({ source: val }); }
                        }),
                        source === 'manual' ? el(SelectControl, {
                            label: 'Agency Preset',
                            value: attributes.agencyPreset || '',
                            options: (function() {
                                var opts = [{ label: '— Choose a preset —', value: '' }];
                                var packs = window.rawnaqTimelinePresets || {};
                                Object.keys(packs).forEach(function(key) {
                                    opts.push({ label: packs[key].label || key, value: key });
                                });
                                return opts;
                            })(),
                            onChange: function(val) { setAttributes({ agencyPreset: val }); }
                        }) : null,
                        source === 'manual' && attributes.agencyPreset ? el(Button, {
                            isPrimary: true,
                            style: { marginBottom: '12px' },
                            onClick: function() {
                                var packs = window.rawnaqTimelinePresets || {};
                                var pack = packs[attributes.agencyPreset];
                                if (pack && pack.steps && pack.steps.length) {
                                    updateSteps(pack.steps);
                                }
                            }
                        }, 'Apply Preset to Steps') : null,
                        el(SelectControl, {
                            label: 'Layout Mode',
                            value: layout,
                            options: [
                                { label: 'Alternating (Left / Right)', value: 'alternating' },
                                { label: 'All Left', value: 'left' },
                                { label: 'All Right', value: 'right' },
                                { label: 'Horizontal', value: 'horizontal' }
                            ],
                            onChange: function(val) { setAttributes({ layout: val }); }
                        }),
                        el(TextControl, {
                            label: 'Named Timeline ID',
                            value: attributes.timelineName || '',
                            help: 'Optional. Paste the same ID into Bento cells to sync scroll animations.',
                            onChange: function(val) { setAttributes({ timelineName: val }); }
                        }),
                        el(ToggleControl, {
                            label: 'Show Step Numbers',
                            checked: showNumbers,
                            onChange: function(val) { setAttributes({ showNumbers: !!val }); }
                        }),
                        el(RangeControl, {
                            label: 'Initial Visible Steps',
                            help: '0 = show all. Otherwise Load More reveals the rest.',
                            value: initialVisible,
                            onChange: function(val) { setAttributes({ initialVisible: val || 0 }); },
                            min: 0, max: 50
                        }),
                        initialVisible > 0 ? el(RangeControl, {
                            label: 'Load More Chunk Size',
                            value: loadChunk,
                            onChange: function(val) { setAttributes({ loadChunk: val || 3 }); },
                            min: 1, max: 20
                        }) : null,
                        initialVisible > 0 ? el(TextControl, {
                            label: 'Load More Label',
                            value: attributes.loadMoreText || 'Load more',
                            onChange: function(val) { setAttributes({ loadMoreText: val }); }
                        }) : null
                    ),
                    source === 'query' ? el(PanelBody, { title: 'Query', initialOpen: true },
                        el(TextControl, {
                            label: 'Post Type',
                            value: attributes.postType || 'post',
                            onChange: function(val) { setAttributes({ postType: val }); }
                        }),
                        el(RangeControl, {
                            label: 'Posts Per Page',
                            value: attributes.postsPerPage || 6,
                            onChange: function(val) { setAttributes({ postsPerPage: val || 6 }); },
                            min: 1, max: 50
                        }),
                        el(SelectControl, {
                            label: 'Order By',
                            value: attributes.orderby || 'date',
                            options: [
                                { label: 'Date', value: 'date' },
                                { label: 'Title', value: 'title' },
                                { label: 'Menu order', value: 'menu_order' },
                                { label: 'Modified', value: 'modified' },
                                { label: 'Random', value: 'rand' },
                                { label: 'ID', value: 'ID' }
                            ],
                            onChange: function(val) { setAttributes({ orderby: val }); }
                        }),
                        el(SelectControl, {
                            label: 'Order',
                            value: attributes.order || 'DESC',
                            options: [
                                { label: 'Descending', value: 'DESC' },
                                { label: 'Ascending', value: 'ASC' }
                            ],
                            onChange: function(val) { setAttributes({ order: val }); }
                        }),
                        el(TextControl, {
                            label: 'Taxonomy',
                            value: attributes.taxonomy || '',
                            help: 'e.g. category, post_tag',
                            onChange: function(val) { setAttributes({ taxonomy: val }); }
                        }),
                        el(TextControl, {
                            label: 'Term Slugs',
                            value: attributes.terms || '',
                            help: 'Comma-separated',
                            onChange: function(val) { setAttributes({ terms: val }); }
                        }),
                        el(TextControl, {
                            label: 'Include IDs',
                            value: attributes.includeIds || '',
                            onChange: function(val) { setAttributes({ includeIds: val }); }
                        }),
                        el(TextControl, {
                            label: 'Exclude IDs',
                            value: attributes.excludeIds || '',
                            onChange: function(val) { setAttributes({ excludeIds: val }); }
                        })
                    ) : null,
                    el(PanelBody, { title: 'Style & Colors', initialOpen: true },
                        el(TextControl, { label: 'Line Background (Hex)', value: attributes.lineBg || '#e2e8f0', onChange: function(val) { setAttributes({ lineBg: val }); } }),
                        el(TextControl, { label: 'Active Line (Hex)', value: attributes.lineActive || '#6366f1', onChange: function(val) { setAttributes({ lineActive: val }); } }),
                        el(RangeControl, {
                            label: 'Line Thickness (px)',
                            value: attributes.lineWidth || 4,
                            onChange: function(val) { setAttributes({ lineWidth: val }); },
                            min: 1, max: 12
                        }),
                        el(TextControl, { label: 'Bullet Border (Hex)', value: attributes.bulletBorder || '#cbd5e1', onChange: function(val) { setAttributes({ bulletBorder: val }); } }),
                        el(TextControl, { label: 'Active Bullet (Hex)', value: attributes.bulletActive || '#6366f1', onChange: function(val) { setAttributes({ bulletActive: val }); } }),
                        el(TextControl, { label: 'Card Background (Hex)', value: attributes.cardBg || '#ffffff', onChange: function(val) { setAttributes({ cardBg: val }); } }),
                        el(TextControl, { label: 'Meta Color (Hex)', value: attributes.metaColor || '#6366f1', onChange: function(val) { setAttributes({ metaColor: val }); } }),
                        el(TextControl, { label: 'Title Color (Hex)', value: attributes.titleColor || '#1a1a1a', onChange: function(val) { setAttributes({ titleColor: val }); } }),
                        el(TextControl, { label: 'Description Color (Hex)', value: attributes.descColor || '#666666', onChange: function(val) { setAttributes({ descColor: val }); } }),
                        el(TextControl, { label: 'CTA Color (Hex)', value: attributes.ctaColor || '#6366f1', onChange: function(val) { setAttributes({ ctaColor: val }); } }),
                        el(RangeControl, {
                            label: 'Card Radius (px)',
                            value: attributes.cardRadius || 16,
                            onChange: function(val) { setAttributes({ cardRadius: val }); },
                            min: 0, max: 40
                        }),
                        el(RangeControl, {
                            label: 'Bullet Size (px)',
                            value: attributes.bulletSize || 28,
                            onChange: function(val) { setAttributes({ bulletSize: val }); },
                            min: 16, max: 48
                        }),
                        el(RangeControl, {
                            label: 'Item Spacing (px)',
                            value: attributes.itemGap || 20,
                            onChange: function(val) { setAttributes({ itemGap: val }); },
                            min: 8, max: 80
                        })
                    ),
                    source === 'manual' ? el(PanelBody, { title: 'Timeline Steps', initialOpen: false },
                        stepFields,
                        el(Button, {
                            isSecondary: true,
                            onClick: function() {
                                updateSteps(steps.concat({
                                    meta: '',
                                    title: 'New Milestone',
                                    desc: 'Step details...',
                                    icon: '',
                                    imageUrl: '',
                                    imageId: 0,
                                    video: '',
                                    ctaText: '',
                                    ctaLink: ''
                                }));
                            }
                        }, '+ Add Milestone')
                    ) : null
                ),
                el('div', {
                    className: wrapClass,
                    'data-show-numbers': showNumbers ? '1' : '0',
                    'data-tl-name': tlName,
                    'data-initial-visible': '0',
                    style: wrapStyle
                },
                    el('div', {
                        className: 'rawnaq-timeline-engine-badge',
                        'aria-hidden': true
                    }, 'Native CSS scroll animations — no motion JS in this browser.'),
                    el('div', { className: 'rawnaq-timeline-line-bg' }),
                    el('div', { className: 'rawnaq-timeline-line-active', style: layout === 'horizontal' ? { width: '30%' } : { height: '30%' } }),
                    source === 'query'
                        ? el('div', { className: 'rawnaq-timeline-item left-item item-active' },
                            el('span', { className: 'rawnaq-timeline-bullet' },
                                showNumbers ? el('span', { className: 'num' }, '01') : null
                            ),
                            el('div', { className: 'rawnaq-timeline-card' },
                                el('span', { className: 'rawnaq-timeline-meta' }, 'Query mode'),
                                el('h4', {}, 'Posts load on the frontend'),
                                el('p', {}, 'Preview the published page to see live CPT / post results.'),
                                el('p', { style: { marginTop: '8px', fontSize: '12px', opacity: 0.8 } }, 'Named timeline: ' + tlName)
                            )
                        )
                        : timelineElements,
                    source !== 'query' && showLoadMore ? el('div', { className: 'rawnaq-timeline-load-more' },
                        el('button', { type: 'button' }, attributes.loadMoreText || 'Load more')
                    ) : null
                )
            );
        },
        save: function() { return null; }
    });

    // ─────────────────────────────────────────────────────────
    // 4. FLOATING DOCK BLOCK
    // ─────────────────────────────────────────────────────────
    var defaultWaSchedule = '{"mon":{"enabled":true,"open":"09:00","close":"18:00"},"tue":{"enabled":true,"open":"09:00","close":"18:00"},"wed":{"enabled":true,"open":"09:00","close":"18:00"},"thu":{"enabled":true,"open":"09:00","close":"18:00"},"fri":{"enabled":true,"open":"09:00","close":"18:00"},"sat":{"enabled":false,"open":"09:00","close":"18:00"},"sun":{"enabled":false,"open":"09:00","close":"18:00"}}';

    registerBlockType('rawnaq/floating-dock', {
        title: 'Floating Dock Menu (Rawnaq)',
        icon: 'navigator',
        category: 'design',
        attributes: {
            position: { type: 'string', default: 'bottom' },
            itemsJson: {
                type: 'string',
                default: '[{"label":"Home","icon":"dashicons-admin-home","link":"#","target":"_self","badge":"","color":"#6366f1"},{"label":"Messages","icon":"dashicons-email-alt","link":"#","target":"_self","badge":"3","color":"#6366f1"},{"label":"Settings","icon":"dashicons-admin-generic","link":"#","target":"_self","badge":"","color":"#6366f1"}]'
            },
            offset: { type: 'number', default: 20 },
            hideMobile: { type: 'boolean', default: false },
            mobileLabels: { type: 'boolean', default: false },
            dockBg: { type: 'string', default: 'rgba(255,255,255,0.55)' },
            dockBorder: { type: 'string', default: 'rgba(255,255,255,0.5)' },
            dockBlur: { type: 'number', default: 16 },
            dockRadius: { type: 'number', default: 24 },
            dockGap: { type: 'number', default: 12 },
            dockPad: { type: 'number', default: 10 },
            itemBg: { type: 'string', default: '#ffffff' },
            iconColor: { type: 'string', default: '#444444' },
            itemSize: { type: 'number', default: 48 },
            itemRadius: { type: 'number', default: 12 },
            badgeBg: { type: 'string', default: '#ef4444' },
            badgeColor: { type: 'string', default: '#ffffff' },
            magnify: { type: 'boolean', default: true },
            maxScale: { type: 'number', default: 1.6 },
            whatsappMode: { type: 'boolean', default: false },
            positionWa: { type: 'string', default: 'right' },
            primaryChannel: { type: 'string', default: 'whatsapp' },
            agentsJson: {
                type: 'string',
                default: '[{"name":"Customer Support","role":"Live Support","number":"8801700000000","avatar":"","msg":"আসসালামু আলাইকুম, আমি {pageTitle} পেজ থেকে লিখছি ({url})।"}]'
            },
            defaultMsg: {
                type: 'string',
                default: 'আসসালামু আলাইকুম, আমি {pageTitle} থেকে যোগাযোগ করছি।'
            },
            secCall: { type: 'string', default: '' },
            secMessenger: { type: 'string', default: '' },
            secEmail: { type: 'string', default: '' },
            secTelegram: { type: 'string', default: '' },
            timezone: { type: 'string', default: 'Asia/Dhaka' },
            scheduleJson: { type: 'string', default: defaultWaSchedule },
            offHoursBehavior: { type: 'string', default: 'offline_badge' },
            offHoursRedirect: { type: 'string', default: '' },
            offHoursEmail: { type: 'string', default: '' },
            offHoursFormNote: { type: 'string', default: 'We are offline right now. Leave a message and we will reply by email.' },
            qrFallback: { type: 'boolean', default: true },
            desktopAction: { type: 'string', default: 'choice' },
            triggerDelay: { type: 'number', default: 0 },
            triggerScroll: { type: 'number', default: 0 },
            greetingText: { type: 'string', default: 'আসসালামু আলাইকুম, সাহায্য লাগবে?' },
            hideDesktop: { type: 'boolean', default: false },
            safeOffset: { type: 'number', default: 0 },
            visMode: { type: 'string', default: 'all' },
            visIds: { type: 'string', default: '' },
            visIncludeFront: { type: 'boolean', default: false },
            visIncludeProducts: { type: 'boolean', default: false },
            trackClicks: { type: 'boolean', default: true }
        },
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var items = safeParseJson(attributes.itemsJson, []);
            var agents = safeParseJson(attributes.agentsJson, []);
            var magnify = attributes.magnify !== false;
            var isWaMode = !!attributes.whatsappMode;
            var schedule;
            try {
                schedule = JSON.parse(attributes.scheduleJson || defaultWaSchedule);
            } catch (e) {
                schedule = JSON.parse(defaultWaSchedule);
            }

            function updateItems(newItems) {
                setAttributes({ itemsJson: JSON.stringify(newItems) });
            }

            function patchItem(idx, patch) {
                var updated = items.slice();
                updated[idx] = Object.assign({}, updated[idx], patch);
                updateItems(updated);
            }

            function updateAgents(next) {
                setAttributes({ agentsJson: JSON.stringify(next) });
            }

            function patchAgent(idx, patch) {
                var updated = agents.slice();
                updated[idx] = Object.assign({}, updated[idx], patch);
                updateAgents(updated);
            }

            function patchSchedule(day, patch) {
                var next = Object.assign({}, schedule);
                next[day] = Object.assign({}, next[day] || { enabled: true, open: '09:00', close: '18:00' }, patch);
                setAttributes({ scheduleJson: JSON.stringify(next) });
            }

            var wrapStyle = {
                '--dock-offset': (attributes.offset || 20) + 'px',
                '--dock-safe-offset': (attributes.safeOffset || 0) + 'px',
                '--dock-bg': attributes.dockBg || 'rgba(255,255,255,0.55)',
                '--dock-border': attributes.dockBorder || 'rgba(255,255,255,0.5)',
                '--dock-blur': (attributes.dockBlur || 16) + 'px',
                '--dock-radius': (attributes.dockRadius || 24) + 'px',
                '--dock-gap': (attributes.dockGap || 12) + 'px',
                '--dock-pad': (attributes.dockPad || 10) + 'px',
                '--dock-item-bg': attributes.itemBg || '#ffffff',
                '--dock-icon': attributes.iconColor || '#444444',
                '--dock-item-size': (attributes.itemSize || 48) + 'px',
                '--dock-item-radius': (attributes.itemRadius || 12) + 'px',
                '--dock-badge-bg': attributes.badgeBg || '#ef4444',
                '--dock-badge-color': attributes.badgeColor || '#ffffff',
                position: 'relative',
                left: 'auto',
                right: 'auto',
                bottom: 'auto',
                transform: 'none',
                margin: '0 auto'
            };

            var activePos = isWaMode ? (attributes.positionWa || 'right') : (attributes.position || 'bottom');
            var className = 'rawnaq-dock-container pos-' + activePos +
                (isWaMode ? ' rawnaq-whatsapp-dock-mode' : '') +
                (attributes.hideMobile ? ' hide-mobile' : '') +
                (attributes.hideDesktop ? ' hide-desktop' : '') +
                (attributes.mobileLabels ? ' mobile-labels' : '');

            var itemFields = items.map(function(item, idx) {
                return el('div', { style: { background: '#f1f1f1', padding: '10px', marginBottom: '10px', borderRadius: '6px' }, key: idx },
                    el('p', { style: { margin: '0 0 8px', fontWeight: 700 } }, 'Item ' + (idx + 1)),
                    el(TextControl, {
                        label: 'Label / Tooltip', value: item.label || '',
                        onChange: function(val) { patchItem(idx, { label: val }); }
                    }),
                    el(TextControl, {
                        label: 'Dashicon Class', value: item.icon || '',
                        help: 'e.g. dashicons-admin-home',
                        onChange: function(val) { patchItem(idx, { icon: val }); }
                    }),
                    el(TextControl, {
                        label: 'Link URL', value: item.link || '',
                        onChange: function(val) { patchItem(idx, { link: val }); }
                    }),
                    el(SelectControl, {
                        label: 'Link Target', value: item.target || '_self',
                        options: [
                            { label: 'Same Tab', value: '_self' },
                            { label: 'New Tab', value: '_blank' }
                        ],
                        onChange: function(val) { patchItem(idx, { target: val }); }
                    }),
                    el(TextControl, {
                        label: 'Badge', value: item.badge || '',
                        placeholder: '3',
                        onChange: function(val) { patchItem(idx, { badge: val }); }
                    }),
                    el(TextControl, {
                        label: 'Hover Color (Hex)', value: item.color || '#6366f1',
                        onChange: function(val) { patchItem(idx, { color: val }); }
                    }),
                    el(Button, {
                        isDestructive: true, isSmall: true,
                        onClick: function() { updateItems(items.filter(function(_, i) { return i !== idx; })); }
                    }, 'Remove')
                );
            });

            var agentFields = agents.map(function(agent, idx) {
                return el('div', { style: { background: '#ecfdf5', padding: '10px', marginBottom: '10px', borderRadius: '6px' }, key: 'ag-' + idx },
                    el('p', { style: { margin: '0 0 8px', fontWeight: 700 } }, 'Agent ' + (idx + 1)),
                    el(TextControl, { label: 'Name', value: agent.name || '', onChange: function(val) { patchAgent(idx, { name: val }); } }),
                    el(TextControl, { label: 'Role', value: agent.role || '', onChange: function(val) { patchAgent(idx, { role: val }); } }),
                    el(TextControl, {
                        label: 'WhatsApp Number', value: agent.number || '',
                        help: 'Country code, no + or spaces (e.g. 88017…)',
                        onChange: function(val) { patchAgent(idx, { number: val }); }
                    }),
                    el(TextControl, { label: 'Avatar URL', value: agent.avatar || '', onChange: function(val) { patchAgent(idx, { avatar: val }); } }),
                    el(TextareaControl, {
                        label: 'Prefilled Message',
                        value: agent.msg || '',
                        help: 'Tokens: {pageTitle} {url} {siteTitle} {date} {time} · Woo: {productName} {price} {sku} {productUrl}',
                        onChange: function(val) { patchAgent(idx, { msg: val }); }
                    }),
                    el(Button, {
                        isDestructive: true, isSmall: true,
                        onClick: function() { updateAgents(agents.filter(function(_, i) { return i !== idx; })); }
                    }, 'Remove')
                );
            });

            var dayKeys = [
                { key: 'mon', label: 'Mon' }, { key: 'tue', label: 'Tue' }, { key: 'wed', label: 'Wed' },
                { key: 'thu', label: 'Thu' }, { key: 'fri', label: 'Fri' }, { key: 'sat', label: 'Sat' },
                { key: 'sun', label: 'Sun' }
            ];
            var scheduleFields = dayKeys.map(function(d) {
                var day = schedule[d.key] || { enabled: true, open: '09:00', close: '18:00' };
                return el('div', { key: d.key, style: { marginBottom: '8px', paddingBottom: '8px', borderBottom: '1px solid #e5e7eb' } },
                    el(ToggleControl, {
                        label: d.label + ' open',
                        checked: !!day.enabled,
                        onChange: function(val) { patchSchedule(d.key, { enabled: !!val }); }
                    }),
                    day.enabled ? el(TextControl, {
                        label: 'Hours (open–close)',
                        value: (day.open || '09:00') + '–' + (day.close || '18:00'),
                        help: 'Format: 09:00–18:00',
                        onChange: function(val) {
                            var parts = String(val || '').split(/[–\-]/);
                            patchSchedule(d.key, {
                                open: (parts[0] || '09:00').trim(),
                                close: (parts[1] || '18:00').trim()
                            });
                        }
                    }) : null
                );
            });

            var previewItems;
            if (isWaMode) {
                var waIconClass = 'dashicons-whatsapp';
                if (attributes.primaryChannel === 'call') {
                    waIconClass = 'dashicons-phone';
                } else if (attributes.primaryChannel === 'messenger') {
                    waIconClass = 'dashicons-admin-comments';
                }
                var waBrandColor = '#25d366';
                if (attributes.primaryChannel === 'call') {
                    waBrandColor = '#3b82f6';
                } else if (attributes.primaryChannel === 'messenger') {
                    waBrandColor = '#0084ff';
                }

                previewItems = [
                    el('button', {
                        type: 'button',
                        className: 'rawnaq-wa-main-trigger is-online',
                        key: 'wa-primary',
                        style: { backgroundColor: waBrandColor },
                        'aria-label': attributes.greetingText || 'Contact Us'
                    },
                        el('span', { className: 'dashicons ' + waIconClass, 'aria-hidden': true }),
                        el('span', { className: 'online-dot', 'aria-hidden': true })
                    )
                ];
            } else {
                previewItems = items.map(function(item, idx) {
                    return el('a', {
                        className: 'rawnaq-dock-item',
                        key: idx,
                        href: '#',
                        onClick: function(e) { e.preventDefault(); },
                        style: { '--hover-color': item.color || '#6366f1' },
                        'aria-label': item.label || ''
                    },
                        el('span', { className: 'rawnaq-dock-icon' },
                            el('span', { className: 'dashicons ' + (item.icon || 'dashicons-admin-generic'), 'aria-hidden': true })
                        ),
                        item.badge ? el('span', { className: 'rawnaq-dock-badge' }, item.badge) : null,
                        item.label ? el('span', { className: 'rawnaq-dock-tooltip' }, item.label) : null,
                        item.label ? el('span', { className: 'rawnaq-dock-mobile-label' }, item.label) : null
                    );
                });
            }

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: 'WhatsApp Contact Mode', initialOpen: true },
                        el(ToggleControl, {
                            label: 'Enable WhatsApp Mode',
                            checked: isWaMode,
                            onChange: function(val) { setAttributes({ whatsappMode: !!val }); }
                        }),
                        isWaMode ? el(SelectControl, {
                            label: 'Position',
                            value: attributes.positionWa || 'right',
                            options: [
                                { label: 'Bottom Right', value: 'right' },
                                { label: 'Bottom Left', value: 'left' }
                            ],
                            onChange: function(val) { setAttributes({ positionWa: val }); }
                        }) : null,
                        isWaMode ? el(SelectControl, {
                            label: 'Primary Channel',
                            value: attributes.primaryChannel || 'whatsapp',
                            options: [
                                { label: 'WhatsApp Chat', value: 'whatsapp' },
                                { label: 'Phone Call', value: 'call' },
                                { label: 'FB Messenger', value: 'messenger' }
                            ],
                            onChange: function(val) { setAttributes({ primaryChannel: val }); }
                        }) : null,
                        isWaMode ? el(TextControl, {
                            label: 'Greeting Bubble',
                            value: attributes.greetingText || '',
                            onChange: function(val) { setAttributes({ greetingText: val }); }
                        }) : null,
                        isWaMode ? el(SelectControl, {
                            label: 'Desktop Chat Action',
                            value: attributes.desktopAction || 'choice',
                            options: [
                                { label: 'Show options (Web + QR)', value: 'choice' },
                                { label: 'Open WhatsApp Web directly', value: 'web' },
                                { label: 'QR first (Web still available)', value: 'qr' }
                            ],
                            help: 'Phones always open the app. Desktop can offer both.',
                            onChange: function(val) { setAttributes({ desktopAction: val, qrFallback: val !== 'web' }); }
                        }) : null,
                        isWaMode ? el(RangeControl, {
                            label: 'Trigger Delay (sec)',
                            value: attributes.triggerDelay || 0,
                            onChange: function(val) { setAttributes({ triggerDelay: val || 0 }); },
                            min: 0, max: 60
                        }) : null,
                        isWaMode ? el(RangeControl, {
                            label: 'Trigger Scroll (%)',
                            value: attributes.triggerScroll || 0,
                            onChange: function(val) { setAttributes({ triggerScroll: val || 0 }); },
                            min: 0, max: 100
                        }) : null
                    ),
                    isWaMode ? el(PanelBody, { title: 'WhatsApp Agents', initialOpen: true },
                        agentFields,
                        el(Button, {
                            isSecondary: true,
                            onClick: function() {
                                updateAgents(agents.concat({
                                    name: 'Agent',
                                    role: 'Support',
                                    number: '8801700000000',
                                    avatar: '',
                                    msg: 'আসসালামু আলাইকুম, আমি {pageTitle} পেজ থেকে লিখছি ({url})।'
                                }));
                            }
                        }, '+ Add Agent'),
                        el(TextareaControl, {
                            label: 'Default Prefilled Message',
                            value: attributes.defaultMsg || '',
                            help: 'Used when an agent has no message. Same tokens as above.',
                            onChange: function(val) { setAttributes({ defaultMsg: val }); }
                        })
                    ) : null,
                    isWaMode ? el(PanelBody, { title: 'Secondary Channels', initialOpen: false },
                        el(TextControl, { label: 'Call Number', value: attributes.secCall || '', onChange: function(val) { setAttributes({ secCall: val }); } }),
                        el(TextControl, { label: 'Messenger Username', value: attributes.secMessenger || '', onChange: function(val) { setAttributes({ secMessenger: val }); } }),
                        el(TextControl, { label: 'Email', value: attributes.secEmail || '', onChange: function(val) { setAttributes({ secEmail: val }); } }),
                        el(TextControl, { label: 'Telegram Username', value: attributes.secTelegram || '', onChange: function(val) { setAttributes({ secTelegram: val }); } })
                    ) : null,
                    isWaMode ? el(PanelBody, { title: 'Business Hours', initialOpen: false },
                        el(SelectControl, {
                            label: 'Business Timezone',
                            value: attributes.timezone || 'Asia/Dhaka',
                            help: 'DST-aware IANA zones. Legacy UTC offsets still work.',
                            options: [
                                { label: 'UTC (GMT)', value: 'UTC' },
                                { label: 'New York (Eastern)', value: 'America/New_York' },
                                { label: 'Chicago (Central)', value: 'America/Chicago' },
                                { label: 'Los Angeles (Pacific)', value: 'America/Los_Angeles' },
                                { label: 'São Paulo', value: 'America/Sao_Paulo' },
                                { label: 'London', value: 'Europe/London' },
                                { label: 'Paris / Berlin', value: 'Europe/Paris' },
                                { label: 'Istanbul', value: 'Europe/Istanbul' },
                                { label: 'Cairo', value: 'Africa/Cairo' },
                                { label: 'Dubai', value: 'Asia/Dubai' },
                                { label: 'Riyadh', value: 'Asia/Riyadh' },
                                { label: 'Karachi', value: 'Asia/Karachi' },
                                { label: 'India (Kolkata)', value: 'Asia/Kolkata' },
                                { label: 'Bangladesh (Dhaka)', value: 'Asia/Dhaka' },
                                { label: 'Jakarta', value: 'Asia/Jakarta' },
                                { label: 'Singapore', value: 'Asia/Singapore' },
                                { label: 'China (Shanghai)', value: 'Asia/Shanghai' },
                                { label: 'Tokyo', value: 'Asia/Tokyo' },
                                { label: 'Sydney', value: 'Australia/Sydney' }
                            ],
                            onChange: function(val) { setAttributes({ timezone: val }); }
                        }),
                        scheduleFields,
                        el(SelectControl, {
                            label: 'Off-hours Behavior',
                            value: attributes.offHoursBehavior || 'offline_badge',
                            options: [
                                { label: 'Offline badge', value: 'offline_badge' },
                                { label: 'Hide dock', value: 'hide' },
                                { label: 'Offline lead / email form', value: 'lead_form' },
                                { label: 'Redirect URL', value: 'redirect' }
                            ],
                            onChange: function(val) { setAttributes({ offHoursBehavior: val }); }
                        }),
                        attributes.offHoursBehavior === 'redirect' ? el(TextControl, {
                            label: 'Redirect URL',
                            value: attributes.offHoursRedirect || '',
                            onChange: function(val) { setAttributes({ offHoursRedirect: val }); }
                        }) : null,
                        attributes.offHoursBehavior === 'lead_form' ? el(TextControl, {
                            label: 'Offline Lead Email',
                            value: attributes.offHoursEmail || '',
                            help: 'Falls back to Secondary Email if empty.',
                            onChange: function(val) { setAttributes({ offHoursEmail: val }); }
                        }) : null,
                        attributes.offHoursBehavior === 'lead_form' ? el(TextareaControl, {
                            label: 'Offline Form Note',
                            value: attributes.offHoursFormNote || 'We are offline right now. Leave a message and we will reply by email.',
                            onChange: function(val) { setAttributes({ offHoursFormNote: val }); }
                        }) : null
                    ) : null,
                    el(PanelBody, { title: 'Layout', initialOpen: !isWaMode },
                        !isWaMode ? el(SelectControl, {
                            label: 'Position', value: attributes.position || 'bottom',
                            options: [
                                { label: 'Bottom Center', value: 'bottom' },
                                { label: 'Sidebar Left', value: 'left' },
                                { label: 'Sidebar Right', value: 'right' }
                            ],
                            onChange: function(val) { setAttributes({ position: val }); }
                        }) : null,
                        el(RangeControl, {
                            label: 'Edge Offset (px)',
                            value: attributes.offset || 20,
                            onChange: function(val) { setAttributes({ offset: val }); },
                            min: 0, max: 80
                        }),
                        el(RangeControl, {
                            label: 'Extra Bottom Offset (cookie/banners)',
                            value: attributes.safeOffset || 0,
                            onChange: function(val) { setAttributes({ safeOffset: val || 0 }); },
                            min: 0, max: 160
                        }),
                        el(ToggleControl, {
                            label: 'Hide on Mobile',
                            checked: !!attributes.hideMobile,
                            onChange: function(val) { setAttributes({ hideMobile: !!val }); }
                        }),
                        el(ToggleControl, {
                            label: 'Hide on Desktop',
                            checked: !!attributes.hideDesktop,
                            onChange: function(val) { setAttributes({ hideDesktop: !!val }); }
                        }),
                        !attributes.hideMobile && !isWaMode ? el(ToggleControl, {
                            label: 'Show Labels on Mobile',
                            checked: !!attributes.mobileLabels,
                            onChange: function(val) { setAttributes({ mobileLabels: !!val }); }
                        }) : null
                    ),
                    el(PanelBody, { title: 'Page Visibility', initialOpen: false },
                        el(SelectControl, {
                            label: 'Show Dock On',
                            value: attributes.visMode || 'all',
                            options: [
                                { label: 'Entire site', value: 'all' },
                                { label: 'Only selected pages', value: 'include' },
                                { label: 'Everywhere except selected', value: 'exclude' }
                            ],
                            onChange: function(val) { setAttributes({ visMode: val }); }
                        }),
                        (attributes.visMode || 'all') !== 'all' ? el(TextControl, {
                            label: 'Page / Post IDs',
                            value: attributes.visIds || '',
                            help: 'Comma-separated IDs, e.g. 12, 45, 88',
                            onChange: function(val) { setAttributes({ visIds: val }); }
                        }) : null,
                        (attributes.visMode || 'all') !== 'all' ? el(ToggleControl, {
                            label: 'Also match Front Page',
                            checked: !!attributes.visIncludeFront,
                            onChange: function(val) { setAttributes({ visIncludeFront: !!val }); }
                        }) : null,
                        (attributes.visMode || 'all') !== 'all' ? el(ToggleControl, {
                            label: 'Also match WooCommerce Products',
                            checked: !!attributes.visIncludeProducts,
                            onChange: function(val) { setAttributes({ visIncludeProducts: !!val }); }
                        }) : null,
                        el(ToggleControl, {
                            label: 'Track Clicks (site-wide)',
                            checked: attributes.trackClicks !== false,
                            help: 'Totals show under Rawnaq → Dock Stats',
                            onChange: function(val) { setAttributes({ trackClicks: !!val }); }
                        })
                    ),
                    !isWaMode ? el(PanelBody, { title: 'Style & Colors', initialOpen: true },
                        el(TextControl, { label: 'Dock Background', value: attributes.dockBg || '', onChange: function(val) { setAttributes({ dockBg: val }); } }),
                        el(TextControl, { label: 'Dock Border', value: attributes.dockBorder || '', onChange: function(val) { setAttributes({ dockBorder: val }); } }),
                        el(RangeControl, { label: 'Glass Blur (px)', value: attributes.dockBlur || 16, onChange: function(val) { setAttributes({ dockBlur: val }); }, min: 0, max: 40 }),
                        el(RangeControl, { label: 'Dock Radius', value: attributes.dockRadius || 24, onChange: function(val) { setAttributes({ dockRadius: val }); }, min: 0, max: 40 }),
                        el(RangeControl, { label: 'Item Gap', value: attributes.dockGap || 12, onChange: function(val) { setAttributes({ dockGap: val }); }, min: 0, max: 32 }),
                        el(RangeControl, { label: 'Dock Padding', value: attributes.dockPad || 10, onChange: function(val) { setAttributes({ dockPad: val }); }, min: 4, max: 28 }),
                        el(TextControl, { label: 'Item Background (Hex)', value: attributes.itemBg || '#ffffff', onChange: function(val) { setAttributes({ itemBg: val }); } }),
                        el(TextControl, { label: 'Icon Color (Hex)', value: attributes.iconColor || '#444444', onChange: function(val) { setAttributes({ iconColor: val }); } }),
                        el(RangeControl, { label: 'Item Size', value: attributes.itemSize || 48, onChange: function(val) { setAttributes({ itemSize: val }); }, min: 32, max: 72 }),
                        el(RangeControl, { label: 'Item Radius', value: attributes.itemRadius || 12, onChange: function(val) { setAttributes({ itemRadius: val }); }, min: 0, max: 24 }),
                        el(TextControl, { label: 'Badge Background (Hex)', value: attributes.badgeBg || '#ef4444', onChange: function(val) { setAttributes({ badgeBg: val }); } }),
                        el(TextControl, { label: 'Badge Text (Hex)', value: attributes.badgeColor || '#ffffff', onChange: function(val) { setAttributes({ badgeColor: val }); } })
                    ) : null,
                    !isWaMode ? el(PanelBody, { title: 'Magnify Effect', initialOpen: false },
                        el(ToggleControl, {
                            label: 'Enable Magnify',
                            checked: magnify,
                            onChange: function(val) { setAttributes({ magnify: !!val }); }
                        }),
                        magnify ? el(RangeControl, {
                            label: 'Max Scale',
                            value: Math.round((attributes.maxScale || 1.6) * 100),
                            onChange: function(val) { setAttributes({ maxScale: (val || 160) / 100 }); },
                            min: 110, max: 200
                        }) : null
                    ) : null,
                    !isWaMode ? el(PanelBody, { title: 'Dock Items', initialOpen: false },
                        itemFields,
                        el(Button, {
                            isSecondary: true,
                            onClick: function() {
                                updateItems(items.concat({
                                    label: 'Action',
                                    icon: 'dashicons-admin-generic',
                                    link: '#',
                                    target: '_self',
                                    badge: '',
                                    color: '#6366f1'
                                }));
                            }
                        }, '+ Add Item')
                    ) : null
                ),
                el('div', { style: { padding: '24px', background: '#e8ecf1', borderRadius: '12px' } },
                    el('nav', {
                        className: className,
                        style: wrapStyle,
                        'aria-label': 'Floating dock',
                        'data-magnify': magnify ? '1' : '0',
                        'data-max-scale': String(attributes.maxScale || 1.6),
                        'data-base-size': String(attributes.itemSize || 48)
                    }, previewItems)
                )
            );
        },
        save: function() { return null; }
    });

    // ─────────────────────────────────────────────────────────
    // 5. FLOW CHART BLOCK
    // ─────────────────────────────────────────────────────────
    registerBlockType('rawnaq/flow-chart', {
        title: 'Flow Chart (Rawnaq)',
        icon: 'networking',
        category: 'design',
        attributes: {
            mode: { type: 'string', default: 'org' },
            connector: { type: 'string', default: 'curved' },
            direction: { type: 'string', default: 'tb' },
            shape: { type: 'string', default: 'rect' },
            avatarShape: { type: 'string', default: 'rounded' },
            avatarSize: { type: 'number', default: 30 },
            avatarGap: { type: 'number', default: 8 },
            avatarBg: { type: 'string', default: '#FEF3C7' },
            avatarIconColor: { type: 'string', default: '#92400E' },
            avatarIconSize: { type: 'number', default: 14 },
            avatarBorderColor: { type: 'string', default: '' },
            avatarBorderWidth: { type: 'number', default: 0 },
            avatarObjectFit: { type: 'string', default: 'cover' },
            avatarShadow: { type: 'boolean', default: false },
            showExport: { type: 'boolean', default: true },
            enableZoom: { type: 'boolean', default: true },
            nodesJson: {
                type: 'string',
                default: '[{"id":"ceo","parent":"","title":"Founder / CEO","role":"Leadership","icon":"★","image":"","detail":"Leads the company.","link":"","decision":false,"x_pos":0,"y_pos":0,"badge":"","status":"default"}]'
            },
            accentColor:    { type: 'string', default: '#FBBF24' },
            rootColorFrom:  { type: 'string', default: '#4338CA' },
            rootColorTo:    { type: 'string', default: '#7C3AED' },
            lineColor:      { type: 'string', default: '#E6E2F0' },
            dataSource: { type: 'string', default: 'manual' },
            usersRole: { type: 'string', default: '' },
            usersNumber: { type: 'number', default: 20 },
            nodeBg:         { type: 'string', default: '#ffffff' },
            nodeRadius:     { type: 'number', default: 14 }
        },
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var nodes = safeParseJson(attributes.nodesJson, []);
            var chartRef = useRef ? useRef(null) : { current: null };

            function updateNodes(next) {
                setAttributes({ nodesJson: JSON.stringify(next) });
            }

            function patchNode(idx, patch) {
                var updated = nodes.slice();
                updated[idx] = Object.assign({}, updated[idx], patch);
                updateNodes(updated);
            }

            var mappedNodes = nodes.map(function(n) {
                return Object.assign({}, n, {
                    x: typeof n.x === 'number' ? n.x : (parseFloat(n.x_pos) || 10),
                    y: typeof n.y === 'number' ? n.y : (parseFloat(n.y_pos) || 10)
                });
            });
            var shapeVal = attributes.shape === 'hexagon' ? 'hex' : (attributes.shape || 'rect');
            var avatarShape = attributes.avatarShape || 'rounded';
            if (avatarShape !== 'circle' && avatarShape !== 'square' && avatarShape !== 'rounded') {
                avatarShape = 'rounded';
            }
            var cfg = {
                mode: attributes.mode || 'org',
                connector: attributes.connector || 'curved',
                nodes: mappedNodes,
                direction: attributes.direction || 'tb',
                shape: shapeVal,
                avatarShape: avatarShape,
                zoom: attributes.enableZoom !== false,
                export: attributes.showExport !== false
            };
            var flowAttr = encodeURIComponent(JSON.stringify(cfg));

            useEffect(function() {
                var t = setTimeout(function() {
                    var el = chartRef.current;
                    if (el && typeof window.rawnaqFlowChartMount === 'function') {
                        window.rawnaqFlowChartMount(el);
                    } else if (typeof window.rawnaqFlowChartBoot === 'function') {
                        window.rawnaqFlowChartBoot();
                    }
                }, 80);
                return function() { clearTimeout(t); };
            }, [attributes.nodesJson, attributes.mode, attributes.connector, attributes.direction, attributes.shape, attributes.avatarShape, attributes.avatarSize, attributes.avatarGap, attributes.avatarBg, attributes.avatarIconColor, attributes.avatarIconSize, attributes.avatarBorderColor, attributes.avatarBorderWidth, attributes.avatarObjectFit, attributes.avatarShadow, attributes.showExport, attributes.enableZoom, attributes.accentColor, attributes.rootColorFrom, attributes.rootColorTo, attributes.lineColor, attributes.nodeBg, attributes.nodeRadius, flowAttr]);

            var fields = nodes.map(function(node, idx) {
                var parentOptions = [{ label: '— Root (no parent) —', value: '' }].concat(
                    nodes.filter(function(n, i) { return i !== idx && n.id; }).map(function(n) {
                        return { label: (n.title || n.id) + ' (' + n.id + ')', value: n.id };
                    })
                );
                return el('div', { key: idx, style: { background: '#f3f4f6', padding: '10px', marginBottom: '10px', borderRadius: '8px' } },
                    el('p', { style: { margin: '0 0 8px', fontWeight: 700 } }, 'Node ' + (idx + 1)),
                    el(TextControl, { label: 'ID', value: node.id || '', onChange: function(v) { patchNode(idx, { id: v }); } }),
                    el(SelectControl, {
                        label: 'Parent',
                        value: node.parent || '',
                        options: parentOptions,
                        onChange: function(v) { patchNode(idx, { parent: v }); }
                    }),
                    el(TextControl, { label: 'Title', value: node.title || '', onChange: function(v) { patchNode(idx, { title: v }); } }),
                    el(TextControl, { label: 'Role / Subtitle', value: node.role || '', onChange: function(v) { patchNode(idx, { role: v }); } }),
                    el(TextControl, { label: 'Connector Label', value: node.edgeLabel || '', help: 'Text on the line from the parent (e.g. Yes / No).', onChange: function(v) { patchNode(idx, { edgeLabel: v }); } }),
                    el(TextControl, { label: 'Swimlane', value: node.lane || '', help: 'Same lane name groups nodes into a labelled band.', onChange: function(v) { patchNode(idx, { lane: v }); } }),
                    el(SelectControl, {
                        label: 'Select Icon',
                        value: node.icon || '●',
                        options: [
                            { label: 'Default Dot', value: '●' },
                            { label: 'Leader Star', value: '★' },
                            { label: 'Engineering Gear', value: 'dashicons-admin-generic' },
                            { label: 'Analytics Chart', value: 'dashicons-chart-area' },
                            { label: 'Team Group', value: 'dashicons-admin-users' },
                            { label: 'Sketch Book', value: 'dashicons-welcome-write-blog' },
                            { label: 'Check Milestone', value: 'dashicons-yes' },
                            { label: 'Heart Favorite', value: 'dashicons-heart' },
                            { label: 'Custom (type below)', value: 'custom' }
                        ],
                        onChange: function(v) { patchNode(idx, { icon: v }); }
                    }),
                    el(TextControl, {
                        label: 'Custom Icon Class or Emoji',
                        value: node.icon || '',
                        onChange: function(v) { patchNode(idx, { icon: v }); }
                    }),
                    el('div', { style: { marginBottom: '12px' } },
                        el('label', { style: { display: 'block', marginBottom: '6px', fontSize: '11px', fontWeight: 600, textTransform: 'uppercase', color: '#1e1e1e' } }, 'Image (optional)'),
                        el('p', { style: { margin: '0 0 8px', fontSize: '12px', color: '#757575' } }, 'Replaces the icon badge. Cropped to fit.'),
                        el(MediaUploadCheck || 'div', {},
                            el(MediaUpload, {
                                onSelect: function(media) {
                                    patchNode(idx, {
                                        image: media.url || '',
                                        imageUrl: media.url || '',
                                        imageId: media.id || 0
                                    });
                                },
                                allowedTypes: ['image'],
                                value: node.imageId || 0,
                                render: function(obj) {
                                    var imgSrc = node.image || node.imageUrl || '';
                                    return el(Fragment, {},
                                        imgSrc
                                            ? el('div', { style: { display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '8px' } },
                                                el('img', {
                                                    src: imgSrc,
                                                    alt: '',
                                                    style: {
                                                        width: '40px',
                                                        height: '40px',
                                                        objectFit: 'cover',
                                                        borderRadius: '50%',
                                                        display: 'block',
                                                        flexShrink: 0
                                                    }
                                                }),
                                                el(Button, {
                                                    isSecondary: true,
                                                    isSmall: true,
                                                    onClick: obj.open
                                                }, 'Replace'),
                                                el(Button, {
                                                    isDestructive: true,
                                                    isSmall: true,
                                                    onClick: function() {
                                                        patchNode(idx, { image: '', imageUrl: '', imageId: 0 });
                                                    }
                                                }, 'Remove')
                                            )
                                            : el(Button, {
                                                isSecondary: true,
                                                isSmall: true,
                                                onClick: obj.open
                                            }, 'Upload image')
                                    );
                                }
                            })
                        )
                    ),
                    el(TextareaControl, { label: 'Detail', value: node.detail || '', onChange: function(v) { patchNode(idx, { detail: v }); } }),
                    el(TextControl, { label: 'Link', value: node.link || '', onChange: function(v) { patchNode(idx, { link: v }); } }),
                    el(ToggleControl, {
                        label: 'Decision node',
                        checked: !!node.decision,
                        onChange: function(v) { patchNode(idx, { decision: !!v }); }
                    }),
                    (attributes.mode === 'freeform') && el(RangeControl, {
                        label: 'Freeform X (%)',
                        value: typeof node.x === 'number' ? node.x : (parseFloat(node.x_pos) || 10),
                        min: 0, max: 100,
                        onChange: function(v) { patchNode(idx, { x: v, x_pos: v }); }
                    }),
                    (attributes.mode === 'freeform') && el(RangeControl, {
                        label: 'Freeform Y (%)',
                        value: typeof node.y === 'number' ? node.y : (parseFloat(node.y_pos) || 10),
                        min: 0, max: 100,
                        onChange: function(v) { patchNode(idx, { y: v, y_pos: v }); }
                    }),
                    el(Button, {
                        isDestructive: true, isSmall: true,
                        onClick: function() { updateNodes(nodes.filter(function(_, i) { return i !== idx; })); }
                    }, 'Remove')
                );
            });

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Layout', initialOpen: true },
                        el(SelectControl, {
                            label: 'Nodes Source',
                            value: attributes.dataSource || 'manual',
                            options: [
                                { label: 'Manual nodes', value: 'manual' },
                                { label: 'WordPress users (org chart)', value: 'wp_users' }
                            ],
                            help: 'Users mode builds on the frontend from WP users. Optional meta: rawnaq_reports_to.',
                            onChange: function(v) { setAttributes({ dataSource: v }); }
                        }),
                        attributes.dataSource === 'wp_users' ? el(TextControl, {
                            label: 'Filter by Role',
                            value: attributes.usersRole || '',
                            onChange: function(v) { setAttributes({ usersRole: v }); }
                        }) : null,
                        attributes.dataSource === 'wp_users' ? el(RangeControl, {
                            label: 'Max Users',
                            value: attributes.usersNumber || 20,
                            min: 1, max: 50,
                            onChange: function(v) { setAttributes({ usersNumber: v || 20 }); }
                        }) : null,
                        el(SelectControl, {
                            label: 'Mode',
                            value: attributes.mode || 'org',
                            options: [
                                { label: 'Org (tree)', value: 'org' },
                                { label: 'Process (flow)', value: 'process' },
                                { label: 'Freeform (manual X-Y)', value: 'freeform' }
                            ],
                            onChange: function(v) { setAttributes({ mode: v }); }
                        }),
                        (attributes.mode !== 'freeform') && el(SelectControl, {
                            label: 'Direction',
                            value: attributes.direction || 'tb',
                            options: [
                                { label: 'Top to Bottom', value: 'tb' },
                                { label: 'Left to Right', value: 'lr' },
                                { label: 'Right to Left', value: 'rl' }
                            ],
                            onChange: function(v) { setAttributes({ direction: v }); }
                        }),
                        el(SelectControl, {
                            label: 'Node Shape',
                            value: attributes.shape === 'hexagon' ? 'hex' : (attributes.shape || 'rect'),
                            options: [
                                { label: 'Rounded Rectangle', value: 'rect' },
                                { label: 'Circle', value: 'circle' },
                                { label: 'Hexagon', value: 'hex' }
                            ],
                            onChange: function(v) { setAttributes({ shape: v }); }
                        }),
                        el(SelectControl, {
                            label: 'Avatar Shape (icon / image)',
                            value: attributes.avatarShape || 'rounded',
                            options: [
                                { label: 'Rounded square', value: 'rounded' },
                                { label: 'Circle', value: 'circle' },
                                { label: 'Square', value: 'square' }
                            ],
                            onChange: function(v) { setAttributes({ avatarShape: v }); }
                        }),
                        el(SelectControl, {
                            label: 'Connector',
                            value: attributes.connector || 'curved',
                            options: [
                                { label: 'Curved', value: 'curved' },
                                { label: 'Elbow', value: 'elbow' },
                                { label: 'Straight', value: 'straight' },
                                { label: 'Dashed', value: 'dashed' }
                            ],
                            onChange: function(v) { setAttributes({ connector: v }); }
                        }),
                        el(ToggleControl, {
                            label: 'Show PNG / SVG export',
                            checked: attributes.showExport !== false,
                            onChange: function(v) { setAttributes({ showExport: !!v }); }
                        }),
                        el(ToggleControl, {
                            label: 'Enable zoom / pan',
                            checked: attributes.enableZoom !== false,
                            onChange: function(v) { setAttributes({ enableZoom: !!v }); }
                        })
                    ),
                    el(PanelBody, { title: 'Avatar (Icon / Image)', initialOpen: false },
                        el(RangeControl, {
                            label: 'Avatar Size',
                            value: attributes.avatarSize || 30,
                            min: 24, max: 96,
                            onChange: function(v) { setAttributes({ avatarSize: v || 30 }); }
                        }),
                        el(RangeControl, {
                            label: 'Avatar Spacing',
                            value: typeof attributes.avatarGap === 'number' ? attributes.avatarGap : 8,
                            min: 0, max: 24,
                            onChange: function(v) { setAttributes({ avatarGap: v }); }
                        }),
                        el('div', { style: { marginBottom: '12px' } },
                            el('label', { style: { display: 'block', marginBottom: '6px', fontSize: '12px', fontWeight: 600 } }, 'Avatar Background'),
                            el(ColorPalette, {
                                value: attributes.avatarBg || '#FEF3C7',
                                onChange: function(v) { setAttributes({ avatarBg: v || '#FEF3C7' }); }
                            })
                        ),
                        el('div', { style: { marginBottom: '12px' } },
                            el('label', { style: { display: 'block', marginBottom: '6px', fontSize: '12px', fontWeight: 600 } }, 'Icon Color'),
                            el(ColorPalette, {
                                value: attributes.avatarIconColor || '#92400E',
                                onChange: function(v) { setAttributes({ avatarIconColor: v || '#92400E' }); }
                            })
                        ),
                        el(RangeControl, {
                            label: 'Icon Size',
                            value: attributes.avatarIconSize || 14,
                            min: 10, max: 48,
                            onChange: function(v) { setAttributes({ avatarIconSize: v || 14 }); }
                        }),
                        el('div', { style: { marginBottom: '12px' } },
                            el('label', { style: { display: 'block', marginBottom: '6px', fontSize: '12px', fontWeight: 600 } }, 'Avatar Border Color'),
                            el(ColorPalette, {
                                value: attributes.avatarBorderColor || '',
                                onChange: function(v) { setAttributes({ avatarBorderColor: v || '' }); }
                            })
                        ),
                        el(RangeControl, {
                            label: 'Avatar Border Width',
                            value: attributes.avatarBorderWidth || 0,
                            min: 0, max: 8,
                            onChange: function(v) { setAttributes({ avatarBorderWidth: v || 0 }); }
                        }),
                        el(SelectControl, {
                            label: 'Image Fit',
                            value: attributes.avatarObjectFit || 'cover',
                            options: [
                                { label: 'Cover (crop)', value: 'cover' },
                                { label: 'Contain (full image)', value: 'contain' },
                                { label: 'Fill (stretch)', value: 'fill' }
                            ],
                            onChange: function(v) { setAttributes({ avatarObjectFit: v }); }
                        }),
                        el(ToggleControl, {
                            label: 'Avatar Shadow',
                            checked: !!attributes.avatarShadow,
                            onChange: function(v) { setAttributes({ avatarShadow: !!v }); }
                        })
                    ),
                    el(PanelBody, { title: 'Styles & Colors', initialOpen: false },
                        el('div', { style: { marginBottom: '15px' } },
                            el('label', { style: { display: 'block', marginBottom: '8px', fontSize: '13px', fontWeight: 'bold' } }, 'Line Color'),
                            el(ColorPalette, {
                                value: attributes.lineColor,
                                onChange: function(v) { setAttributes({ lineColor: v || '#E6E2F0' }); }
                            })
                        ),
                        el('div', { style: { marginBottom: '15px' } },
                            el('label', { style: { display: 'block', marginBottom: '8px', fontSize: '13px', fontWeight: 'bold' } }, 'Accent / Glow Color'),
                            el(ColorPalette, {
                                value: attributes.accentColor,
                                onChange: function(v) { setAttributes({ accentColor: v || '#FBBF24' }); }
                            })
                        ),
                        el('div', { style: { marginBottom: '15px' } },
                            el('label', { style: { display: 'block', marginBottom: '8px', fontSize: '13px', fontWeight: 'bold' } }, 'Root Gradient From'),
                            el(ColorPalette, {
                                value: attributes.rootColorFrom,
                                onChange: function(v) { setAttributes({ rootColorFrom: v || '#4338CA' }); }
                            })
                        ),
                        el('div', { style: { marginBottom: '15px' } },
                            el('label', { style: { display: 'block', marginBottom: '8px', fontSize: '13px', fontWeight: 'bold' } }, 'Root Gradient To'),
                            el(ColorPalette, {
                                value: attributes.rootColorTo,
                                onChange: function(v) { setAttributes({ rootColorTo: v || '#7C3AED' }); }
                            })
                        ),
                        el('div', { style: { marginBottom: '15px' } },
                            el('label', { style: { display: 'block', marginBottom: '8px', fontSize: '13px', fontWeight: 'bold' } }, 'Node Background'),
                            el(ColorPalette, {
                                value: attributes.nodeBg,
                                onChange: function(v) { setAttributes({ nodeBg: v || '#ffffff' }); }
                            })
                        ),
                        el(RangeControl, { label: 'Node Border Radius (px)', value: attributes.nodeRadius, min: 0, max: 40, onChange: function(v) { setAttributes({ nodeRadius: v }); } })
                    ),
                    attributes.dataSource !== 'wp_users' && el(PanelBody, { title: 'Nodes', initialOpen: true },
                        fields,
                        el(Button, {
                            isSecondary: true,
                            onClick: function() {
                                updateNodes(nodes.concat({
                                    id: 'node-' + (nodes.length + 1),
                                    parent: nodes.length ? (nodes[0].id || 'ceo') : '',
                                    title: 'New node',
                                    role: '',
                                    icon: '●',
                                    image: '',
                                    imageUrl: '',
                                    imageId: 0,
                                    detail: '',
                                    link: '',
                                    decision: false,
                                    x: 20,
                                    y: 20,
                                    x_pos: 20,
                                    y_pos: 20
                                }));
                            }
                        }, '+ Add Node')
                    )
                ),
                el('div', {
                    className: 'rawnaq-flow-chart avatar-' + avatarShape + (attributes.avatarShadow ? ' has-avatar-shadow' : ''),
                    'data-flow': flowAttr,
                    ref: chartRef,
                    style: {
                        minHeight: '200px',
                        '--fc-amber': attributes.accentColor || '#FBBF24',
                        '--fc-indigo': attributes.rootColorFrom || '#4338CA',
                        '--fc-violet': attributes.rootColorTo || '#7C3AED',
                        '--fc-line': attributes.lineColor || '#E6E2F0',
                        '--fc-panel': attributes.nodeBg || '#ffffff',
                        '--fc-radius': (attributes.nodeRadius || 14) + 'px',
                        '--fc-avatar': (attributes.avatarSize || 30) + 'px',
                        '--fc-avatar-gap': (typeof attributes.avatarGap === 'number' ? attributes.avatarGap : 8) + 'px',
                        '--fc-avatar-bg': attributes.avatarBg || '#FEF3C7',
                        '--fc-avatar-icon': attributes.avatarIconColor || '#92400E',
                        '--fc-avatar-icon-size': (attributes.avatarIconSize || 14) + 'px',
                        '--fc-avatar-border': attributes.avatarBorderColor || 'transparent',
                        '--fc-avatar-border-w': (attributes.avatarBorderWidth || 0) + 'px',
                        '--fc-avatar-fit': attributes.avatarObjectFit || 'cover'
                    }
                },
                    el('div', { className: 'rawnaq-flow-viewport' },
                        el('div', { className: 'rawnaq-flow-stage is-responsive' })
                    )
                )
            );
        },
        save: function() { return null; }
    });

    // ─────────────────────────────────────────────────────────
    // 6. SCROLL PROGRESS + TOC BLOCK
    // ─────────────────────────────────────────────────────────
    registerBlockType('rawnaq/scroll-progress-toc', {
        title: 'Scroll Progress + TOC (Rawnaq)',
        icon: 'list-view',
        category: 'design',
        attributes: {
            progress: { type: 'string', default: 'both' },
            barPosition: { type: 'string', default: 'top' },
            tocPosition: { type: 'string', default: 'sticky' },
            tocTitle: { type: 'string', default: 'Contents' },
            source: { type: 'string', default: 'auto' },
            levels: { type: 'string', default: 'h2,h3' },
            contentSelector: { type: 'string', default: '' },
            hideIfShort: { type: 'boolean', default: true },
            scrollOffset: { type: 'number', default: 80 },
            smooth: { type: 'boolean', default: true },
            readingTime: { type: 'boolean', default: true },
            showPercent: { type: 'boolean', default: true },
            mobileCollapse: { type: 'boolean', default: true },
            manualJson: { type: 'string', default: '[]' },
            collapseSubs: { type: 'boolean', default: false },
            showSearch: { type: 'boolean', default: false },
            dockAttach: { type: 'boolean', default: false },
            syncTimeline: { type: 'string', default: '' },
            ringSize: { type: 'number', default: 56 }
        },
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var manual = safeParseJson(attributes.manualJson, []);

            function updateManual(next) {
                setAttributes({ manualJson: JSON.stringify(next) });
            }

            var manualFields = manual.map(function(item, idx) {
                return el('div', { key: idx, style: { background: '#f3f4f6', padding: '8px', marginBottom: '8px', borderRadius: '6px' } },
                    el(TextControl, {
                        label: 'Label', value: item.title || '',
                        onChange: function(v) {
                            var u = manual.slice();
                            u[idx] = Object.assign({}, u[idx], { title: v });
                            updateManual(u);
                        }
                    }),
                    el(TextControl, {
                        label: 'Anchor ID', value: item.id || '',
                        onChange: function(v) {
                            var u = manual.slice();
                            u[idx] = Object.assign({}, u[idx], { id: v });
                            updateManual(u);
                        }
                    }),
                    el(Button, {
                        isDestructive: true, isSmall: true,
                        onClick: function() { updateManual(manual.filter(function(_, i) { return i !== idx; })); }
                    }, 'Remove')
                );
            });

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Progress', initialOpen: true },
                        el(SelectControl, {
                            label: 'Progress Style',
                            value: attributes.progress || 'both',
                            options: [
                                { label: 'Bar', value: 'bar' },
                                { label: 'Ring', value: 'ring' },
                                { label: 'Both', value: 'both' },
                                { label: 'None', value: 'none' }
                            ],
                            onChange: function(v) { setAttributes({ progress: v }); }
                        }),
                        el(SelectControl, {
                            label: 'Bar Position',
                            value: attributes.barPosition || 'top',
                            options: [
                                { label: 'Top', value: 'top' },
                                { label: 'Bottom', value: 'bottom' }
                            ],
                            onChange: function(v) { setAttributes({ barPosition: v }); }
                        }),
                        el(ToggleControl, {
                            label: 'Show % in ring',
                            checked: attributes.showPercent !== false,
                            onChange: function(v) { setAttributes({ showPercent: !!v }); }
                        }),
                        (attributes.progress === 'ring' || attributes.progress === 'both') && el(RangeControl, {
                            label: 'Ring size (px)',
                            value: attributes.ringSize || 56,
                            min: 40, max: 96,
                            onChange: function(v) { setAttributes({ ringSize: v || 56 }); }
                        })
                    ),
                    el(PanelBody, { title: 'Table of Contents', initialOpen: true },
                        el(SelectControl, {
                            label: 'TOC Position',
                            value: attributes.tocPosition || 'sticky',
                            options: [
                                { label: 'Sticky sidebar', value: 'sticky' },
                                { label: 'Floating', value: 'floating' },
                                { label: 'Inline', value: 'inline' },
                                { label: 'None', value: 'none' }
                            ],
                            onChange: function(v) { setAttributes({ tocPosition: v }); }
                        }),
                        el(TextControl, {
                            label: 'TOC Title',
                            value: attributes.tocTitle || 'Contents',
                            onChange: function(v) { setAttributes({ tocTitle: v }); }
                        }),
                        attributes.tocPosition !== 'none' && el(TextControl, {
                            label: 'Sync Timeline ID',
                            value: attributes.syncTimeline || '',
                            help: 'Optional. Same Named Timeline ID as Scroll Sync Timeline — shows active step as Chapter near TOC.',
                            onChange: function(v) { setAttributes({ syncTimeline: v || '' }); }
                        }),
                        el(SelectControl, {
                            label: 'Source',
                            value: attributes.source || 'auto',
                            options: [
                                { label: 'Auto headings', value: 'auto' },
                                { label: 'Manual', value: 'manual' }
                            ],
                            onChange: function(v) { setAttributes({ source: v }); }
                        }),
                        el(TextControl, {
                            label: 'Heading levels (comma)',
                            value: attributes.levels || 'h2,h3',
                            help: 'e.g. h2,h3,h4',
                            onChange: function(v) { setAttributes({ levels: v }); }
                        }),
                        (attributes.source || 'auto') === 'auto' ? el(TextControl, {
                            label: 'Content container (CSS selector)',
                            value: attributes.contentSelector || '',
                            help: 'Blank = smart auto-detect. Set for FSE/block themes, e.g. .wp-block-post-content',
                            onChange: function(v) { setAttributes({ contentSelector: v }); }
                        }) : null,
                        el(RangeControl, {
                            label: 'Scroll offset',
                            value: attributes.scrollOffset || 80,
                            onChange: function(v) { setAttributes({ scrollOffset: v }); },
                            min: 0, max: 200
                        }),
                        el(ToggleControl, {
                            label: 'Hide on short pages',
                            checked: attributes.hideIfShort !== false,
                            onChange: function(v) { setAttributes({ hideIfShort: !!v }); }
                        }),
                        el(ToggleControl, {
                            label: 'Smooth scroll',
                            checked: attributes.smooth !== false,
                            onChange: function(v) { setAttributes({ smooth: !!v }); }
                        }),
                        el(ToggleControl, {
                            label: 'Reading time',
                            checked: attributes.readingTime !== false,
                            onChange: function(v) { setAttributes({ readingTime: !!v }); }
                        }),
                        el(ToggleControl, {
                            label: 'Mobile FAB',
                            checked: attributes.mobileCollapse !== false,
                            onChange: function(v) { setAttributes({ mobileCollapse: !!v }); }
                        }),
                        (attributes.tocPosition === 'floating') && el(ToggleControl, {
                            label: 'Attach TOC to Floating Dock',
                            checked: !!attributes.dockAttach,
                            help: 'If a Floating Dock exists on the page, inject a Contents button and hide the TOC FAB.',
                            onChange: function(v) { setAttributes({ dockAttach: !!v }); }
                        }),
                        el(ToggleControl, {
                            label: 'Collapse Sub-headings',
                            checked: !!attributes.collapseSubs,
                            onChange: function(v) { setAttributes({ collapseSubs: !!v }); }
                        }),
                        el(ToggleControl, {
                            label: 'Show Search Bar',
                            checked: !!attributes.showSearch,
                            onChange: function(v) { setAttributes({ showSearch: !!v }); }
                        })
                    ),
                    attributes.source === 'manual' ? el(PanelBody, { title: 'Manual Items', initialOpen: false },
                        manualFields,
                        el(Button, {
                            isSecondary: true,
                            onClick: function() {
                                updateManual(manual.concat({ title: 'Section', id: 'section', level: '2' }));
                            }
                        }, '+ Add Item')
                    ) : null
                ),
                el('div', {
                    style: {
                        border: '1px dashed #c5c5d0',
                        borderRadius: '12px',
                        padding: '16px',
                        background: '#faf9fc'
                    }
                },
                    el('strong', {}, 'Scroll Progress + TOC'),
                    el('p', { style: { margin: '8px 0 0', fontSize: '13px', color: '#6b6478' } },
                        'Progress: ' + (attributes.progress || 'both') + ' · TOC: ' + (attributes.tocPosition || 'sticky') + ' · Source: ' + (attributes.source || 'auto')
                    ),
                    el('p', { style: { margin: '6px 0 0', fontSize: '12px', color: '#6b6478' } },
                        'Preview on the frontend — headings are detected from the page content.'
                    )
                )
            );
        },
        save: function() { return null; }
    });

    // ─────────────────────────────────────────────────────────
    // 7. BENTO GRID BLOCK
    // ─────────────────────────────────────────────────────────
    function bentoCellAttrsFromJson(cell) {
        cell = cell || {};
        return {
            cellType: cell.type || 'text',
            colSpan: Math.max(1, parseInt(cell.col, 10) || 1),
            rowSpan: Math.max(1, parseInt(cell.row, 10) || 1),
            colMd: parseInt(cell.colMd, 10) || 0,
            rowMd: parseInt(cell.rowMd, 10) || 0,
            colSm: parseInt(cell.colSm, 10) || 0,
            rowSm: parseInt(cell.rowSm, 10) || 0,
            orderDesk: parseInt(cell.order, 10) || 0,
            orderMd: parseInt(cell.orderMd, 10) || 0,
            orderSm: parseInt(cell.orderSm, 10) || 0,
            align: cell.align || '',
            tag: cell.tag || '',
            link: cell.link || '',
            mediaUrl: cell.image || cell.video || '',
            tagBg: cell.tagBg || '',
            tagColor: cell.tagColor || ''
        };
    }

    function bentoCellInnerTemplate(cell) {
        cell = cell || {};
        var inners = [];
        if (cell.image && (cell.type === 'image' || cell.type === 'featured')) {
            inners.push([ 'core/image', { url: cell.image } ]);
        }
        if (cell.tag) {
            inners.push([ 'core/paragraph', { content: cell.tag, className: 'rawnaq-bento-tag-p' } ]);
        }
        if (cell.title) {
            inners.push([ 'core/heading', { level: 3, content: cell.title } ]);
        }
        var body = cell.subtitle || '';
        if (cell.stat) {
            body = (cell.prefix || '') + cell.stat + (cell.suffix || '') + (body ? ' — ' + body : '');
        }
        if (body) {
            inners.push([ 'core/paragraph', { content: body } ]);
        }
        if (cell.ctaText) {
            inners.push([ 'core/paragraph', { content: '<a href="' + (cell.ctaLink || cell.link || '#') + '">' + cell.ctaText + '</a>' } ]);
        }
        if (!inners.length) {
            inners.push([ 'core/paragraph', { placeholder: 'Add cell content…' } ]);
        }
        return inners;
    }

    function bentoCellsToTemplate(cellsJson) {
        return safeParseJson(cellsJson, []).map(function(cell) {
            return [ 'rawnaq/bento-cell', bentoCellAttrsFromJson(cell), bentoCellInnerTemplate(cell) ];
        });
    }

    function bentoCellsToBlocks(cells) {
        return (cells || []).map(function(cell) {
            var inners = bentoCellInnerTemplate(cell).map(function(t) {
                return createBlock(t[0], t[1] || {});
            });
            return createBlock('rawnaq/bento-cell', bentoCellAttrsFromJson(cell), inners);
        });
    }

    registerBlockType('rawnaq/bento-cell', {
        title: 'Bento Cell (Rawnaq)',
        icon: 'screenoptions',
        category: 'design',
        parent: [ 'rawnaq/bento-grid' ],
        attributes: {
            cellType: { type: 'string', default: 'text' },
            colSpan: { type: 'number', default: 1 },
            rowSpan: { type: 'number', default: 1 },
            colMd: { type: 'number', default: 0 },
            rowMd: { type: 'number', default: 0 },
            colSm: { type: 'number', default: 0 },
            rowSm: { type: 'number', default: 0 },
            orderDesk: { type: 'number', default: 0 },
            orderMd: { type: 'number', default: 0 },
            orderSm: { type: 'number', default: 0 },
            align: { type: 'string', default: '' },
            tag: { type: 'string', default: '' },
            link: { type: 'string', default: '' },
            mediaUrl: { type: 'string', default: '' },
            tagBg: { type: 'string', default: '' },
            tagColor: { type: 'string', default: '' }
        },
        edit: function(props) {
            var a = props.attributes;
            var setAttributes = props.setAttributes;
            var type = a.cellType || 'text';
            var classes = [ 'rawnaq-bento-cell', 'is-editor-cell' ];
            if (type === 'featured') classes.push('is-featured');
            if (type === 'image') classes.push('is-image');
            if (type === 'video') classes.push('is-video');
            if (type === 'stat') classes.push('is-stat');
            if (type === 'testimonial') classes.push('is-testimonial');
            if (a.align === 'center') classes.push('align-center');
            if (a.align === 'bottom') classes.push('align-bottom');

            var style = {
                gridColumn: 'span ' + (a.colSpan || 1),
                gridRow: 'span ' + (a.rowSpan || 1)
            };
            if (a.orderDesk) style.order = a.orderDesk;

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Cell layout', initialOpen: true },
                        el(SelectControl, {
                            label: 'Type',
                            value: type,
                            options: [
                                { label: 'Icon + Text', value: 'text' },
                                { label: 'Featured', value: 'featured' },
                                { label: 'Image', value: 'image' },
                                { label: 'Video', value: 'video' },
                                { label: 'Stat', value: 'stat' },
                                { label: 'Testimonial', value: 'testimonial' }
                            ],
                            onChange: function(v) { setAttributes({ cellType: v }); }
                        }),
                        el(RangeControl, {
                            label: 'Column span',
                            value: a.colSpan || 1,
                            min: 1, max: 6,
                            onChange: function(v) { setAttributes({ colSpan: v || 1 }); }
                        }),
                        el(RangeControl, {
                            label: 'Row span',
                            value: a.rowSpan || 1,
                            min: 1, max: 4,
                            onChange: function(v) { setAttributes({ rowSpan: v || 1 }); }
                        }),
                        el(SelectControl, {
                            label: 'Content align',
                            value: a.align || '',
                            options: [
                                { label: 'Top', value: '' },
                                { label: 'Center', value: 'center' },
                                { label: 'Bottom', value: 'bottom' }
                            ],
                            onChange: function(v) { setAttributes({ align: v || '' }); }
                        }),
                        el(TextControl, {
                            label: 'Link (optional)',
                            value: a.link || '',
                            onChange: function(v) { setAttributes({ link: v || '' }); }
                        })
                    )
                ),
                el('div', { className: classes.join(' '), style: style },
                    el('div', { className: 'rawnaq-bento-cell-inner' },
                        el(InnerBlocks, {
                            allowedBlocks: [
                                'core/paragraph', 'core/heading', 'core/image', 'core/list',
                                'core/quote', 'core/buttons', 'core/button', 'core/spacer', 'core/video'
                            ],
                            templateLock: false
                        })
                    )
                )
            );
        },
        save: function(props) {
            var a = props.attributes;
            var type = a.cellType || 'text';
            var classes = [ 'rawnaq-bento-cell' ];
            if (type === 'featured') classes.push('is-featured');
            if (type === 'image') classes.push('is-image');
            if (type === 'video') classes.push('is-video');
            if (type === 'stat') classes.push('is-stat');
            if (type === 'testimonial') classes.push('is-testimonial');
            if (a.align === 'center') classes.push('align-center');
            if (a.align === 'bottom') classes.push('align-bottom');
            if (a.colMd) classes.push('has-md-col-' + a.colMd);
            if (a.rowMd) classes.push('has-md-row-' + a.rowMd);
            if (a.colSm) classes.push('has-sm-col-' + a.colSm);
            if (a.rowSm) classes.push('has-sm-row-' + a.rowSm);

            var style = {
                gridColumn: 'span ' + (a.colSpan || 1),
                gridRow: 'span ' + (a.rowSpan || 1)
            };
            if (a.orderDesk) style.order = String(a.orderDesk);

            var Tag = a.link ? 'a' : 'div';
            var tagProps = {
                className: classes.join(' '),
                style: style,
                role: 'listitem'
            };
            if (a.link) {
                tagProps.href = a.link;
            }

            return el(Tag, tagProps,
                el('div', { className: 'rawnaq-bento-cell-inner rawnaq-bento-body' },
                    el(InnerBlocks.Content)
                )
            );
        }
    });

    var defaultBentoCells = (function() {
        var pack = window.rawnaqBentoPresets && rawnaqBentoPresets.featured;
        if (pack && pack.cells && pack.cells.length) {
            return JSON.stringify(pack.cells);
        }
        return '[{"type":"featured","col":2,"row":2,"tag":"Highlight","title":"Zero-jQuery performance","subtitle":"Per-page assets, clean output","icon":"dashicons-star-filled","image":"","video":"","stat":"42","suffix":"+","prefix":"","link":""},{"type":"image","col":2,"row":1,"tag":"Showcase","title":"Project gallery","subtitle":"Client work highlights","icon":"","image":"","video":"","stat":"","suffix":"","prefix":"","link":""},{"type":"stat","col":1,"row":1,"tag":"","title":"","subtitle":"Active installs","icon":"","image":"","video":"","stat":"42","suffix":"+","prefix":"","link":""},{"type":"text","col":1,"row":1,"tag":"","title":"Fast setup","subtitle":"Ready in minutes","icon":"dashicons-performance","image":"","video":"","stat":"","suffix":"","prefix":"","link":""}]';
    })();

    registerBlockType('rawnaq/bento-grid', {
        title: 'Bento Grid (Rawnaq)',
        icon: 'grid-view',
        category: 'design',
        attributes: {
            preset: { type: 'string', default: 'featured' },
            columns: { type: 'number', default: 4 },
            rowHeight: { type: 'number', default: 140 },
            gap: { type: 'number', default: 16 },
            columnGap: { type: 'number', default: 16 },
            rowGap: { type: 'number', default: 16 },
            radius: { type: 'number', default: 18 },
            reveal: { type: 'boolean', default: true },
            hoverEffect: { type: 'string', default: 'lift' },
            hairline: { type: 'boolean', default: false },
            overlayOpacity: { type: 'number', default: 100 },
            tagBg: { type: 'string', default: '#fef3c7' },
            tagColor: { type: 'string', default: '#92400e' },
            titleColor: { type: 'string', default: '#13231c' },
            subColor: { type: 'string', default: '#5c6f66' },
            iconColor: { type: 'string', default: '#0f766e' },
            statColor: { type: 'string', default: '#0f766e' },
            cellBg: { type: 'string', default: '#ffffff' },
            cellBorder: { type: 'string', default: '#d7e2dc' },
            featuredFrom: { type: 'string', default: '#0f766e' },
            featuredTo: { type: 'string', default: '#134e4a' },
            ctaBg: { type: 'string', default: '#fbbf24' },
            ctaColor: { type: 'string', default: '#92400e' },
            cellsJson: { type: 'string', default: defaultBentoCells }
        },
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var clientId = props.clientId;
            var replaceInnerBlocks = (useDispatch('core/block-editor') || {}).replaceInnerBlocks;
            var hasInner = useSelect(function(select) {
                var b = select('core/block-editor').getBlock(clientId);
                return !!(b && b.innerBlocks && b.innerBlocks.length);
            }, [clientId]);

            function applyBentoPreset() {
                var key = attributes.preset || 'featured';
                if (key === 'custom') {
                    return;
                }
                var pack = window.rawnaqBentoPresets && rawnaqBentoPresets[key];
                if (!pack || !pack.cells) {
                    return;
                }
                var next = {};
                if (pack.columns) {
                    next.columns = pack.columns;
                }
                next.cellsJson = JSON.stringify(pack.cells);
                setAttributes(next);
                if (replaceInnerBlocks && clientId) {
                    replaceInnerBlocks(clientId, bentoCellsToBlocks(pack.cells), false);
                }
            }

            var cols = 4;
            if (attributes.preset === 'wide') {
                cols = 3;
            } else if (attributes.preset === 'custom') {
                cols = Math.max(2, Math.min(6, attributes.columns || 4));
            }

            var gridStyle = {
                '--bento-row': (attributes.rowHeight || 140) + 'px',
                '--bento-gap-col': (typeof attributes.columnGap === 'number' ? attributes.columnGap : (attributes.gap || 16)) + 'px',
                '--bento-gap-row': (typeof attributes.rowGap === 'number' ? attributes.rowGap : (attributes.gap || 16)) + 'px',
                '--bento-radius': (attributes.radius || 18) + 'px',
                '--bento-tag-bg': attributes.tagBg || '#fef3c7',
                '--bento-tag-color': attributes.tagColor || '#92400e',
                '--bento-title-color': attributes.titleColor || '#13231c',
                '--bento-sub-color': attributes.subColor || '#5c6f66',
                '--bento-icon-color': attributes.iconColor || '#0f766e',
                '--bento-stat-color': attributes.statColor || '#0f766e',
                '--bento-panel': attributes.cellBg || '#ffffff',
                '--bento-line': attributes.cellBorder || '#d7e2dc',
                '--bento-featured-from': attributes.featuredFrom || '#0f766e',
                '--bento-featured-to': attributes.featuredTo || '#134e4a',
                '--bento-accent': attributes.iconColor || '#0f766e',
                '--bento-overlay-opacity': String(
                    Math.max(0, Math.min(100, typeof attributes.overlayOpacity === 'number' ? attributes.overlayOpacity : 100)) / 100
                ),
                '--bento-cta-bg': attributes.ctaBg || '#fbbf24',
                '--bento-cta-color': attributes.ctaColor || '#92400e'
            };

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Layout', initialOpen: true },
                        el(SelectControl, {
                            label: 'Preset',
                            value: attributes.preset || 'featured',
                            options: [
                                { label: 'Featured', value: 'featured' },
                                { label: 'Equal', value: 'equal' },
                                { label: 'Wide', value: 'wide' },
                                { label: 'Custom', value: 'custom' }
                            ],
                            onChange: function(v) { setAttributes({ preset: v }); }
                        }),
                        el(Button, {
                            isPrimary: true,
                            onClick: applyBentoPreset,
                            style: { marginBottom: '12px' }
                        }, 'Apply Preset → cells'),
                        attributes.preset === 'custom' && el(RangeControl, {
                            label: 'Columns',
                            value: attributes.columns || 4,
                            min: 2, max: 6,
                            onChange: function(v) { setAttributes({ columns: v }); }
                        }),
                        el(RangeControl, {
                            label: 'Row height (px)',
                            value: attributes.rowHeight || 140,
                            min: 80, max: 280,
                            onChange: function(v) { setAttributes({ rowHeight: v }); }
                        }),
                        el(RangeControl, {
                            label: 'Column gap',
                            value: typeof attributes.columnGap === 'number' ? attributes.columnGap : (attributes.gap || 16),
                            min: 0, max: 48,
                            onChange: function(v) { setAttributes({ columnGap: v }); }
                        }),
                        el(RangeControl, {
                            label: 'Row gap',
                            value: typeof attributes.rowGap === 'number' ? attributes.rowGap : (attributes.gap || 16),
                            min: 0, max: 48,
                            onChange: function(v) { setAttributes({ rowGap: v }); }
                        }),
                        el(RangeControl, {
                            label: 'Radius',
                            value: attributes.radius || 18,
                            min: 0, max: 40,
                            onChange: function(v) { setAttributes({ radius: v }); }
                        }),
                        el(SelectControl, {
                            label: 'Hover',
                            value: attributes.hoverEffect || 'lift',
                            options: [
                                { label: 'Lift', value: 'lift' },
                                { label: 'Zoom', value: 'zoom' },
                                { label: 'Tint', value: 'tint' },
                                { label: 'None', value: 'none' }
                            ],
                            onChange: function(v) { setAttributes({ hoverEffect: v }); }
                        }),
                        el(ToggleControl, {
                            label: 'Hairline borders',
                            checked: !!attributes.hairline,
                            onChange: function(v) { setAttributes({ hairline: !!v }); }
                        }),
                        el(ToggleControl, {
                            label: 'Reveal on scroll',
                            checked: attributes.reveal !== false,
                            onChange: function(v) { setAttributes({ reveal: !!v }); }
                        }),
                        el('p', { style: { fontSize: '12px', color: '#5c6f66', marginTop: '8px' } },
                            'Cells are InnerBlocks — nest headings, images, and buttons inside each Bento Cell.' + (hasInner ? '' : '')
                        )
                    ),
                    el(PanelBody, { title: 'Colors', initialOpen: false },
                        el(TextControl, { label: 'Tag BG', value: attributes.tagBg || '#fef3c7', onChange: function(val) { setAttributes({ tagBg: val }); } }),
                        el(TextControl, { label: 'Tag Text', value: attributes.tagColor || '#92400e', onChange: function(val) { setAttributes({ tagColor: val }); } }),
                        el(TextControl, { label: 'Title', value: attributes.titleColor || '#13231c', onChange: function(val) { setAttributes({ titleColor: val }); } }),
                        el(TextControl, { label: 'Subtitle', value: attributes.subColor || '#5c6f66', onChange: function(val) { setAttributes({ subColor: val }); } }),
                        el(TextControl, { label: 'Icon', value: attributes.iconColor || '#0f766e', onChange: function(val) { setAttributes({ iconColor: val }); } }),
                        el(TextControl, { label: 'Cell BG', value: attributes.cellBg || '#ffffff', onChange: function(val) { setAttributes({ cellBg: val }); } }),
                        el(TextControl, { label: 'Cell Border', value: attributes.cellBorder || '#d7e2dc', onChange: function(val) { setAttributes({ cellBorder: val }); } }),
                        el(TextControl, { label: 'Featured From', value: attributes.featuredFrom || '#0f766e', onChange: function(val) { setAttributes({ featuredFrom: val }); } }),
                        el(TextControl, { label: 'Featured To', value: attributes.featuredTo || '#134e4a', onChange: function(val) { setAttributes({ featuredTo: val }); } }),
                        el(TextControl, { label: 'CTA BG', value: attributes.ctaBg || '#fbbf24', onChange: function(val) { setAttributes({ ctaBg: val }); } }),
                        el(TextControl, { label: 'CTA Text', value: attributes.ctaColor || '#92400e', onChange: function(val) { setAttributes({ ctaColor: val }); } })
                    )
                ),
                el('div', { className: 'rawnaq-bento-editor-frame' },
                    el('div', {
                        className: 'rawnaq-bento-grid is-innerblocks' + (attributes.hairline ? ' rawnaq-bento-hairline' : ''),
                        style: gridStyle,
                        'data-cols': String(cols),
                        'data-reveal': '0',
                        'data-hover': attributes.hoverEffect || 'lift',
                        role: 'list'
                    },
                        el(InnerBlocks, {
                            allowedBlocks: [ 'rawnaq/bento-cell' ],
                            template: bentoCellsToTemplate(attributes.cellsJson || defaultBentoCells),
                            templateLock: false,
                            renderAppender: InnerBlocks.ButtonBlockAppender
                        })
                    )
                )
            );
        },
        save: function() {
            return el(InnerBlocks.Content);
        }
    });

    registerBlockType('rawnaq/scroll-story', {
        title: 'Scroll Story Chapters (Rawnaq)',
        icon: 'book-alt',
        category: 'design',
        attributes: {
            mediaSide: { type: 'string', default: 'left' },
            accent: { type: 'string', default: '#0f766e' },
            pinTop: { type: 'number', default: 96 },
            chaptersJson: {
                type: 'string',
                default: '[{"title":"The challenge","body":"Set the scene. What problem or opportunity opens the story?","image":"","caption":"","ctaText":"","ctaUrl":""},{"title":"The approach","body":"Explain the turning point — method, insight, or decision.","image":"","caption":"","ctaText":"","ctaUrl":""},{"title":"The outcome","body":"Close with the result readers should remember.","image":"","caption":"","ctaText":"","ctaUrl":""}]'
            }
        },
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var chapters = safeParseJson(attributes.chaptersJson, []);

            function updateChapters(next) {
                setAttributes({ chaptersJson: JSON.stringify(next) });
            }

            function patchChapter(idx, patch) {
                var next = chapters.slice();
                next[idx] = Object.assign({}, next[idx], patch);
                updateChapters(next);
            }

            var fields = chapters.map(function(ch, idx) {
                return el('div', {
                    key: idx,
                    style: { background: '#f3f4f6', padding: '10px', marginBottom: '10px', borderRadius: '8px' }
                },
                    el(TextControl, {
                        label: 'Title',
                        value: ch.title || '',
                        onChange: function(v) { patchChapter(idx, { title: v }); }
                    }),
                    el(TextareaControl, {
                        label: 'Body',
                        value: ch.body || '',
                        help: 'Rich text allowed: <a>, <strong>, <em>, <ul>/<ol>/<li>.',
                        onChange: function(v) { patchChapter(idx, { body: v }); }
                    }),
                    el(TextControl, {
                        label: 'Anchor / deep-link ID',
                        value: ch.anchor || '',
                        help: 'Optional #anchor. Defaults to a slug of the title.',
                        onChange: function(v) { patchChapter(idx, { anchor: v }); }
                    }),
                    el(TextControl, {
                        label: 'Video URL (MP4)',
                        value: ch.video || '',
                        help: 'Optional. Autoplays muted while active; image = poster.',
                        onChange: function(v) { patchChapter(idx, { video: v }); }
                    }),
                    el(TextControl, {
                        label: 'Caption',
                        value: ch.caption || '',
                        onChange: function(v) { patchChapter(idx, { caption: v }); }
                    }),
                    el(TextControl, {
                        label: 'Image URL',
                        value: ch.image || '',
                        onChange: function(v) { patchChapter(idx, { image: v }); }
                    }),
                    el(MediaUploadCheck, {},
                        el(MediaUpload, {
                            onSelect: function(media) {
                                patchChapter(idx, { image: (media && media.url) ? media.url : '' });
                            },
                            allowedTypes: ['image'],
                            render: function(obj) {
                                return el(Button, {
                                    isSecondary: true,
                                    onClick: obj.open,
                                    style: { marginBottom: '8px' }
                                }, ch.image ? 'Replace Image' : 'Select Image');
                            }
                        })
                    ),
                    el(TextControl, {
                        label: 'CTA Text',
                        value: ch.ctaText || '',
                        onChange: function(v) { patchChapter(idx, { ctaText: v }); }
                    }),
                    el(TextControl, {
                        label: 'CTA URL',
                        value: ch.ctaUrl || '',
                        onChange: function(v) { patchChapter(idx, { ctaUrl: v }); }
                    }),
                    el(TextControl, {
                        label: 'Case-Study project ID',
                        value: ch.projectId || '',
                        onChange: function(v) { patchChapter(idx, { projectId: v }); }
                    }),
                    el(TextControl, {
                        label: 'Case-Study project slug',
                        value: ch.projectSlug || '',
                        onChange: function(v) { patchChapter(idx, { projectSlug: v }); }
                    }),
                    el(Button, {
                        isDestructive: true,
                        isSmall: true,
                        onClick: function() { updateChapters(chapters.filter(function(_, i) { return i !== idx; })); }
                    }, 'Remove chapter')
                );
            });

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Layout', initialOpen: true },
                        el(SelectControl, {
                            label: 'Pinned media side',
                            value: attributes.mediaSide || 'left',
                            options: [
                                { label: 'Left', value: 'left' },
                                { label: 'Right', value: 'right' }
                            ],
                            onChange: function(v) { setAttributes({ mediaSide: v }); }
                        }),
                        el(RangeControl, {
                            label: 'Pin offset (px)',
                            value: typeof attributes.pinTop === 'number' ? attributes.pinTop : 96,
                            min: 40, max: 180,
                            help: 'Sticky top offset for the pinned media.',
                            onChange: function(v) { setAttributes({ pinTop: v || 96 }); }
                        }),
                        el('div', { style: { marginBottom: '12px' } },
                            el('label', { style: { display: 'block', marginBottom: '6px', fontSize: '13px', fontWeight: '600' } }, 'Accent'),
                            el(ColorPalette, {
                                value: attributes.accent || '#0f766e',
                                onChange: function(v) { setAttributes({ accent: v || '#0f766e' }); }
                            })
                        )
                    ),
                    el(PanelBody, { title: 'Chapters', initialOpen: true },
                        fields,
                        el(Button, {
                            isSecondary: true,
                            onClick: function() {
                                updateChapters(chapters.concat({
                                    title: 'New chapter',
                                    body: '',
                                    image: '',
                                    caption: '',
                                    ctaText: '',
                                    ctaUrl: ''
                                }));
                            }
                        }, '+ Add Chapter')
                    )
                ),
                el('div', {
                    className: 'rawnaq-story',
                    style: { '--story-accent': attributes.accent || '#0f766e', border: '1px dashed #c5d0cb', borderRadius: '12px', padding: '16px' }
                },
                    el('strong', {}, 'Scroll Story Chapters'),
                    el('p', { style: { margin: '8px 0 0', fontSize: '13px', color: '#5c6f66' } },
                        chapters.length + ' chapter(s) · media ' + (attributes.mediaSide || 'left') + ' · frontend pins media while scrolling'
                    ),
                    el('ol', { style: { margin: '12px 0 0', paddingLeft: '18px', fontSize: '13px' } },
                        chapters.map(function(ch, i) {
                            return el('li', { key: i, style: { marginBottom: '6px' } }, ch.title || ('Chapter ' + (i + 1)));
                        })
                    )
                )
            );
        },
        save: function() { return null; }
    });

    registerBlockType('rawnaq/smart-form', {
        title: 'Smart Form (Rawnaq)',
        icon: 'email-alt',
        category: 'design',
        attributes: {
            fieldsJson: {
                type: 'string',
                default: '[{"id":"name","type":"text","label":"Name","placeholder":"","required":true,"options":"","width":"50","step":1},{"id":"email","type":"email","label":"Email","placeholder":"","required":true,"options":"","width":"50","step":1},{"id":"phone","type":"phone","label":"Phone","placeholder":"","required":false,"options":"","width":"100","step":1},{"id":"message","type":"textarea","label":"Message","placeholder":"","required":true,"options":"","width":"100","step":1}]'
            },
            deliveryEmail: { type: 'boolean', default: true },
            deliveryWhatsapp: { type: 'boolean', default: true },
            emailTo: { type: 'string', default: '' },
            emailSubject: { type: 'string', default: 'New website inquiry' },
            waNumber: { type: 'string', default: '' },
            waTemplate: { type: 'string', default: 'New inquiry:\nName: {name}\nPhone: {phone}\nEmail: {email}\nMessage: {message}\nPage: {pageTitle}\nURL: {url}' },
            afterSubmit: { type: 'string', default: 'message' },
            redirectUrl: { type: 'string', default: '' },
            submitLabel: { type: 'string', default: 'Send message' },
            successMessage: { type: 'string', default: 'Message sent successfully.' },
            errorMessage: { type: 'string', default: 'Please fill in the required fields correctly.' },
            consentEnabled: { type: 'boolean', default: false },
            consentText: { type: 'string', default: 'I agree to the processing of my data.' },
            logSubmissions: { type: 'boolean', default: true },
            recaptchaEnabled: { type: 'boolean', default: false },
            webhookEnabled: { type: 'boolean', default: false },
            webhookUrl: { type: 'string', default: '' },
            emailHtml: { type: 'boolean', default: true },
            crmProvider: { type: 'string', default: 'none' },
            crmAudience: { type: 'string', default: '' },
            buttonFullWidth: { type: 'boolean', default: false },
            accent: { type: 'string', default: '#fbbf24' },
            accentDeep: { type: 'string', default: '#0f766e' },
            buttonText: { type: 'string', default: '#92400e' },
            labelColor: { type: 'string', default: '' },
            inputBg: { type: 'string', default: '' },
            inputBorder: { type: 'string', default: '' }
        },
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var fields = safeParseJson(attributes.fieldsJson, []);
            var SF_PRESETS = {
                side_by_side: [
                    { id: 'name', type: 'text', label: 'Name', required: true, width: '50', step: 1, options: '' },
                    { id: 'email', type: 'email', label: 'Email', required: true, width: '50', step: 1, options: '' },
                    { id: 'phone', type: 'phone', label: 'Phone', required: false, width: '100', step: 1, options: '' },
                    { id: 'message', type: 'textarea', label: 'Message', required: true, width: '100', step: 1, options: '' }
                ],
                compact_lead: [
                    { id: 'name', type: 'text', label: 'Name', required: true, width: '50', step: 1, options: '' },
                    { id: 'phone', type: 'phone', label: 'Phone', required: true, width: '50', step: 1, options: '' },
                    { id: 'message', type: 'textarea', label: 'How can we help?', required: true, width: '100', step: 1, options: '' }
                ],
                full_contact: [
                    { id: 'name', type: 'text', label: 'Name', required: true, width: '50', step: 1, options: '' },
                    { id: 'email', type: 'email', label: 'Email', required: true, width: '50', step: 1, options: '' },
                    { id: 'phone', type: 'phone', label: 'Phone', required: false, width: '50', step: 1, options: '' },
                    { id: 'company', type: 'text', label: 'Company', required: false, width: '50', step: 1, options: '' },
                    { id: 'subject', type: 'text', label: 'Subject', required: false, width: '100', step: 1, options: '' },
                    { id: 'message', type: 'textarea', label: 'Message', required: true, width: '100', step: 1, options: '' }
                ],
                multi_project: [
                    { id: 'project_type', type: 'select', label: 'Project type', required: true, width: '100', step: 1, options: 'New build, Renovation, Consulting' },
                    { id: 'budget', type: 'select', label: 'Budget', required: false, width: '100', step: 1, options: 'Under 50k, 50–150k, 150k+', showIf: 'project_type', showIfValue: 'Renovation' },
                    { id: 'scope', type: 'textarea', label: 'Project scope', required: true, width: '100', step: 2, options: '' },
                    { id: 'name', type: 'text', label: 'Name', required: true, width: '50', step: 3, options: '' },
                    { id: 'email', type: 'email', label: 'Email', required: true, width: '50', step: 3, options: '' },
                    { id: 'phone', type: 'phone', label: 'Phone', required: false, width: '100', step: 3, options: '' }
                ]
            };

            function updateFields(next) {
                setAttributes({ fieldsJson: JSON.stringify(next) });
            }

            function patchField(idx, patch) {
                var next = fields.slice();
                next[idx] = Object.assign({}, next[idx], patch);
                updateFields(next);
            }

            var fieldEditors = fields.map(function(f, idx) {
                return el('div', {
                    key: idx,
                    style: { background: '#f3f7f4', padding: '10px', marginBottom: '10px', borderRadius: '8px' }
                },
                    el(TextControl, {
                        label: 'Field ID',
                        value: f.id || '',
                        onChange: function(v) { patchField(idx, { id: v }); }
                    }),
                    el(SelectControl, {
                        label: 'Type',
                        value: f.type || 'text',
                        options: [
                            { label: 'Text', value: 'text' },
                            { label: 'Email', value: 'email' },
                            { label: 'Phone', value: 'phone' },
                            { label: 'Textarea', value: 'textarea' },
                            { label: 'Select', value: 'select' },
                            { label: 'Checkbox', value: 'checkbox' },
                            { label: 'Date', value: 'date' },
                            { label: 'Number', value: 'number' },
                            { label: 'URL', value: 'url' },
                            { label: 'Hidden', value: 'hidden' },
                            { label: 'Rating', value: 'rating' },
                            { label: 'File upload', value: 'file' }
                        ],
                        onChange: function(v) { patchField(idx, { type: v }); }
                    }),
                    el(TextControl, {
                        label: 'Label',
                        value: f.label || '',
                        onChange: function(v) { patchField(idx, { label: v }); }
                    }),
                    el(TextControl, {
                        label: 'Placeholder',
                        value: f.placeholder || '',
                        onChange: function(v) { patchField(idx, { placeholder: v }); }
                    }),
                    (f.type === 'select') && el(TextControl, {
                        label: 'Options (comma-separated)',
                        value: f.options || '',
                        onChange: function(v) { patchField(idx, { options: v }); }
                    }),
                    el(SelectControl, {
                        label: 'Field width',
                        value: f.width || '100',
                        options: [
                            { label: 'Full (100%)', value: '100' },
                            { label: 'Three quarters (75%)', value: '75' },
                            { label: 'Two thirds (66%)', value: '66' },
                            { label: 'Half (50%)', value: '50' },
                            { label: 'One third (33%)', value: '33' },
                            { label: 'Quarter (25%)', value: '25' }
                        ],
                        onChange: function(v) { patchField(idx, { width: v || '100' }); }
                    }),
                    el(RangeControl, {
                        label: 'Step (multi-step)',
                        value: parseInt(f.step, 10) || 1,
                        min: 1,
                        max: 8,
                        onChange: function(v) { patchField(idx, { step: v || 1 }); }
                    }),
                    el(TextControl, {
                        label: 'Show if field ID',
                        value: f.showIf || '',
                        help: 'Blank = always visible',
                        onChange: function(v) { patchField(idx, { showIf: v || '' }); }
                    }),
                    el(TextControl, {
                        label: 'Equals value',
                        value: f.showIfValue || '',
                        onChange: function(v) { patchField(idx, { showIfValue: v || '' }); }
                    }),
                    (f.type === 'hidden') && el(TextControl, {
                        label: 'Hidden value',
                        value: f.defaultValue || '',
                        onChange: function(v) { patchField(idx, { defaultValue: v || '' }); }
                    }),
                    (f.type === 'file') && el(RangeControl, {
                        label: 'Max MB',
                        value: parseInt(f.maxMb, 10) || 5,
                        min: 1,
                        max: 25,
                        onChange: function(v) { patchField(idx, { maxMb: v || 5 }); }
                    }),
                    el(ToggleControl, {
                        label: 'Required',
                        checked: !!f.required,
                        onChange: function(v) { patchField(idx, { required: !!v }); }
                    }),
                    el(Button, {
                        isDestructive: true,
                        isSmall: true,
                        onClick: function() { updateFields(fields.filter(function(_, i) { return i !== idx; })); }
                    }, 'Remove')
                );
            });

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Layout preset', initialOpen: true },
                        el(SelectControl, {
                            label: 'Apply preset',
                            value: '',
                            options: [
                                { label: '— Choose —', value: '' },
                                { label: 'Name + Email side by side', value: 'side_by_side' },
                                { label: 'Compact lead', value: 'compact_lead' },
                                { label: 'Full contact', value: 'full_contact' },
                                { label: 'Multi-step project inquiry', value: 'multi_project' }
                            ],
                            onChange: function(v) {
                                if (v && SF_PRESETS[v]) {
                                    updateFields(SF_PRESETS[v]);
                                }
                            }
                        })
                    ),
                    el(PanelBody, { title: 'Fields', initialOpen: true },
                        fieldEditors,
                        el(Button, {
                            isSecondary: true,
                            onClick: function() {
                                updateFields(fields.concat({
                                    id: 'field_' + (fields.length + 1),
                                    type: 'text',
                                    label: 'New field',
                                    placeholder: '',
                                    required: false,
                                    options: '',
                                    width: '100',
                                    step: 1
                                }));
                            }
                        }, '+ Add Field')
                    ),
                    el(PanelBody, { title: 'Delivery', initialOpen: true },
                        el(ToggleControl, {
                            label: 'Email delivery',
                            checked: attributes.deliveryEmail !== false,
                            onChange: function(v) { setAttributes({ deliveryEmail: !!v }); }
                        }),
                        attributes.deliveryEmail !== false && el(TextControl, {
                            label: 'Send email to',
                            value: attributes.emailTo || '',
                            help: 'Blank = site admin email',
                            onChange: function(v) { setAttributes({ emailTo: v || '' }); }
                        }),
                        attributes.deliveryEmail !== false && el(TextControl, {
                            label: 'Email subject',
                            value: attributes.emailSubject || 'New website inquiry',
                            help: '{name}, {pageTitle}, {url}…',
                            onChange: function(v) { setAttributes({ emailSubject: v }); }
                        }),
                        attributes.deliveryEmail !== false && el(ToggleControl, {
                            label: 'Branded HTML email',
                            checked: attributes.emailHtml !== false,
                            onChange: function(v) { setAttributes({ emailHtml: !!v }); }
                        }),
                        el(ToggleControl, {
                            label: 'WhatsApp delivery (wa.me redirect)',
                            checked: attributes.deliveryWhatsapp !== false,
                            help: 'Blank number uses site default from Rawnaq settings.',
                            onChange: function(v) { setAttributes({ deliveryWhatsapp: !!v }); }
                        }),
                        attributes.deliveryWhatsapp !== false && el(TextControl, {
                            label: 'WhatsApp number',
                            value: attributes.waNumber || '',
                            placeholder: '8801XXXXXXXXX',
                            onChange: function(v) { setAttributes({ waNumber: v || '' }); }
                        }),
                        attributes.deliveryWhatsapp !== false && el(TextareaControl, {
                            label: 'WhatsApp template',
                            value: attributes.waTemplate || '',
                            help: '{field_id}, {pageTitle}, {url}',
                            onChange: function(v) { setAttributes({ waTemplate: v || '' }); }
                        }),
                        el(SelectControl, {
                            label: 'After submit',
                            value: attributes.afterSubmit || 'message',
                            options: [
                                { label: 'Thank-you message', value: 'message' },
                                { label: 'Redirect to URL', value: 'redirect' },
                                { label: 'Open WhatsApp', value: 'whatsapp' }
                            ],
                            onChange: function(v) { setAttributes({ afterSubmit: v }); }
                        }),
                        attributes.afterSubmit === 'redirect' && el(TextControl, {
                            label: 'Redirect URL',
                            value: attributes.redirectUrl || '',
                            onChange: function(v) { setAttributes({ redirectUrl: v || '' }); }
                        }),
                        el(TextControl, {
                            label: 'Submit button',
                            value: attributes.submitLabel || 'Send message',
                            onChange: function(v) { setAttributes({ submitLabel: v }); }
                        }),
                        el(ToggleControl, {
                            label: 'Consent checkbox',
                            checked: !!attributes.consentEnabled,
                            onChange: function(v) { setAttributes({ consentEnabled: !!v }); }
                        }),
                        attributes.consentEnabled && el(TextControl, {
                            label: 'Consent text',
                            value: attributes.consentText || '',
                            onChange: function(v) { setAttributes({ consentText: v }); }
                        }),
                        el(ToggleControl, {
                            label: 'Log submissions in admin',
                            checked: attributes.logSubmissions !== false,
                            onChange: function(v) { setAttributes({ logSubmissions: !!v }); }
                        }),
                        el(ToggleControl, {
                            label: 'reCAPTCHA v3',
                            checked: !!attributes.recaptchaEnabled,
                            help: 'Keys in Rawnaq → Elements Manager',
                            onChange: function(v) { setAttributes({ recaptchaEnabled: !!v }); }
                        }),
                        el(ToggleControl, {
                            label: 'Webhook / Slack',
                            checked: !!attributes.webhookEnabled,
                            onChange: function(v) { setAttributes({ webhookEnabled: !!v }); }
                        }),
                        attributes.webhookEnabled && el(TextControl, {
                            label: 'Webhook URL',
                            value: attributes.webhookUrl || '',
                            onChange: function(v) { setAttributes({ webhookUrl: v || '' }); }
                        }),
                        el(SelectControl, {
                            label: 'CRM / ESP',
                            value: attributes.crmProvider || 'none',
                            options: [
                                { label: 'None', value: 'none' },
                                { label: 'Mailchimp', value: 'mailchimp' },
                                { label: 'HubSpot', value: 'hubspot' }
                            ],
                            help: 'Add Mailchimp API key / HubSpot portal ID in Rawnaq settings. Other CRMs: webhook or rawnaq_smart_form_submission hook.',
                            onChange: function(v) { setAttributes({ crmProvider: v }); }
                        }),
                        (attributes.crmProvider === 'mailchimp') && el(TextControl, {
                            label: 'Mailchimp Audience ID',
                            value: attributes.crmAudience || '',
                            onChange: function(v) { setAttributes({ crmAudience: v || '' }); }
                        }),
                        (attributes.crmProvider === 'hubspot') && el(TextControl, {
                            label: 'HubSpot Form GUID',
                            value: attributes.crmAudience || '',
                            help: 'A HubSpot form GUID in the portal set under Rawnaq settings.',
                            onChange: function(v) { setAttributes({ crmAudience: v || '' }); }
                        })
                    ),
                    el(PanelBody, { title: 'Style', initialOpen: false },
                        el(ToggleControl, {
                            label: 'Full-width button',
                            checked: !!attributes.buttonFullWidth,
                            onChange: function(v) { setAttributes({ buttonFullWidth: !!v }); }
                        }),
                        el('div', { style: { marginBottom: '10px' } },
                            el('label', { style: { display: 'block', marginBottom: '6px', fontWeight: 600 } }, 'Button background'),
                            el(ColorPalette, {
                                value: attributes.accent || '#fbbf24',
                                onChange: function(v) { setAttributes({ accent: v || '#fbbf24' }); }
                            })
                        ),
                        el('div', { style: { marginBottom: '10px' } },
                            el('label', { style: { display: 'block', marginBottom: '6px', fontWeight: 600 } }, 'Deep accent'),
                            el(ColorPalette, {
                                value: attributes.accentDeep || '#0f766e',
                                onChange: function(v) { setAttributes({ accentDeep: v || '#0f766e' }); }
                            })
                        ),
                        el('div', {},
                            el('label', { style: { display: 'block', marginBottom: '6px', fontWeight: 600 } }, 'Button text'),
                            el(ColorPalette, {
                                value: attributes.buttonText || '#92400e',
                                onChange: function(v) { setAttributes({ buttonText: v || '#92400e' }); }
                            })
                        )
                    )
                ),
                el('div', {
                    className: 'rawnaq-smart-form',
                    style: {
                        '--sf-accent': attributes.accent || '#fbbf24',
                        '--sf-accent-deep': attributes.accentDeep || '#0f766e',
                        border: '1px dashed #c5d0cb',
                        borderRadius: '12px',
                        padding: '16px'
                    }
                },
                    el('strong', {}, 'Smart Form'),
                    el('p', { style: { margin: '8px 0 0', fontSize: '13px', color: '#5c6f66' } },
                        fields.length + ' field(s) · ' +
                        (attributes.deliveryEmail !== false ? 'Email ' : '') +
                        (attributes.deliveryWhatsapp !== false ? 'WhatsApp ' : '') +
                        '· after: ' + (attributes.afterSubmit || 'message')
                    ),
                    el('ul', { style: { margin: '10px 0 0', paddingLeft: '18px', fontSize: '13px' } },
                        fields.map(function(f, i) {
                            return el('li', { key: i },
                                (f.label || f.id || 'field') + ' (' + (f.type || 'text') + ') · ' + (f.width || '100') + '% · step ' + (f.step || 1)
                            );
                        })
                    )
                )
            );
        },
        save: function() { return null; }
    });

    function caseStudyCardAttrsFromProject(p) {
        var gallery = Array.isArray(p.gallery) ? p.gallery : [];
        return {
            title: p.title || 'Project',
            image: p.image || '',
            galleryJson: JSON.stringify(gallery),
            projectId: p.id || p.projectId || '',
            projectSlug: p.slug || p.projectSlug || '',
            sector: p.sector || '',
            size: p.size || '',
            budget: p.budget || '',
            year: p.year || '',
            client: p.client || '',
            services: p.services || '',
            excerpt: p.excerpt || '',
            detail: p.detail || '',
            link: p.link || '',
            featured: !!p.featured,
            col: p.col || (p.featured ? 2 : 1),
            row: p.row || (p.featured ? 2 : 1)
        };
    }

    function caseStudyProjectsToBlocks(list) {
        return (list || []).map(function(p) {
            return createBlock('rawnaq/case-study-card', caseStudyCardAttrsFromProject(p));
        });
    }

    var defaultCaseStudyProjects = [
        { title: 'Riverfront Civic Center', image: '', gallery: [], sector: 'Civic', size: '120,000 sq ft', budget: '$45–60M', year: '2024', client: 'City Planning Board', services: 'Architecture, Structural, MEP', excerpt: 'A mixed-use civic hub along the waterfront.', detail: 'Full scope included schematic design through CA.', link: '', featured: true, col: 2, row: 2 },
        { title: 'Northline Transit Hub', image: '', gallery: [], sector: 'Infrastructure', size: '18 platforms', budget: '$28M', year: '2023', client: 'Regional Transit Authority', services: 'Civil, Structural', excerpt: 'Intermodal station upgrade.', detail: 'Coordinated with active rail operations.', link: '', featured: false, col: 1, row: 1 },
        { title: 'Oakridge Adaptive Reuse', image: '', gallery: [], sector: 'Adaptive Reuse', size: '64 units', budget: '$12M', year: '2022', client: 'Private Developer', services: 'Architecture, Interior', excerpt: 'Mill building converted to housing.', detail: 'Historic fabric retained where feasible.', link: '', featured: false, col: 1, row: 1 },
        { title: 'Summit Laboratory Annex', image: '', gallery: [], sector: 'Science & Tech', size: '42,000 sq ft', budget: '$22M', year: '2025', client: 'University Facilities', services: 'Architecture, Lab Planning, MEP', excerpt: 'Flexible wet-lab annex.', detail: 'Designed for future reconfiguration.', link: '', featured: false, col: 1, row: 1 }
    ];

    registerBlockType('rawnaq/case-study-card', {
        title: 'Case-Study Card',
        icon: 'portfolio',
        category: 'design',
        parent: [ 'rawnaq/case-study-grid' ],
        attributes: {
            title: { type: 'string', default: 'Project' },
            image: { type: 'string', default: '' },
            galleryJson: { type: 'string', default: '[]' },
            projectId: { type: 'string', default: '' },
            projectSlug: { type: 'string', default: '' },
            sector: { type: 'string', default: '' },
            size: { type: 'string', default: '' },
            budget: { type: 'string', default: '' },
            year: { type: 'string', default: '' },
            client: { type: 'string', default: '' },
            services: { type: 'string', default: '' },
            excerpt: { type: 'string', default: '' },
            detail: { type: 'string', default: '' },
            link: { type: 'string', default: '' },
            featured: { type: 'boolean', default: false },
            col: { type: 'number', default: 1 },
            row: { type: 'number', default: 1 }
        },
        edit: function(props) {
            var a = props.attributes;
            var setAttributes = props.setAttributes;
            var gallery = safeParseJson(a.galleryJson, []);

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Project', initialOpen: true },
                        el(TextControl, {
                            label: 'Title',
                            value: a.title || '',
                            onChange: function(v) { setAttributes({ title: v }); }
                        }),
                        el(TextControl, {
                            label: 'Project ID (sync)',
                            value: a.projectId || '',
                            onChange: function(v) { setAttributes({ projectId: v }); }
                        }),
                        el(TextControl, {
                            label: 'Project slug (sync)',
                            value: a.projectSlug || '',
                            onChange: function(v) { setAttributes({ projectSlug: v }); }
                        }),
                        el(TextControl, {
                            label: 'Sector',
                            value: a.sector || '',
                            onChange: function(v) { setAttributes({ sector: v }); }
                        }),
                        el(MediaUploadCheck, {},
                            el(MediaUpload, {
                                onSelect: function(media) {
                                    setAttributes({ image: (media && media.url) ? media.url : '' });
                                },
                                allowedTypes: ['image'],
                                render: function(obj) {
                                    return el(Button, {
                                        isSecondary: true,
                                        onClick: obj.open
                                    }, a.image ? 'Change cover image' : 'Select cover image');
                                }
                            })
                        ),
                        a.image ? el('p', { style: { margin: '6px 0', fontSize: '12px', color: '#5c6f66' } }, a.image) : null,
                        el(MediaUploadCheck, {},
                            el(MediaUpload, {
                                onSelect: function(medias) {
                                    var list = (Array.isArray(medias) ? medias : [medias]).map(function(m) {
                                        return m && m.url ? m.url : '';
                                    }).filter(Boolean);
                                    setAttributes({ galleryJson: JSON.stringify(list) });
                                },
                                allowedTypes: ['image'],
                                multiple: true,
                                gallery: true,
                                render: function(obj) {
                                    return el(Button, {
                                        isSecondary: true,
                                        style: { marginTop: '8px' },
                                        onClick: obj.open
                                    }, gallery.length ? ('Change gallery (' + gallery.length + ')') : 'Select gallery images');
                                }
                            })
                        ),
                        el(TextControl, {
                            label: 'Case-study URL (link-out)',
                            value: a.link || '',
                            onChange: function(v) { setAttributes({ link: v }); }
                        }),
                        el(TextControl, {
                            label: 'Size / scope',
                            value: a.size || '',
                            onChange: function(v) { setAttributes({ size: v }); }
                        }),
                        el(TextControl, {
                            label: 'Budget',
                            value: a.budget || '',
                            onChange: function(v) { setAttributes({ budget: v }); }
                        }),
                        el(TextControl, {
                            label: 'Year',
                            value: a.year || '',
                            onChange: function(v) { setAttributes({ year: v }); }
                        }),
                        el(TextControl, {
                            label: 'Client',
                            value: a.client || '',
                            onChange: function(v) { setAttributes({ client: v }); }
                        }),
                        el(TextControl, {
                            label: 'Services (comma-separated)',
                            value: a.services || '',
                            onChange: function(v) { setAttributes({ services: v }); }
                        }),
                        el(TextareaControl, {
                            label: 'Excerpt',
                            value: a.excerpt || '',
                            onChange: function(v) { setAttributes({ excerpt: v }); }
                        }),
                        el(TextareaControl, {
                            label: 'Detail (modal)',
                            value: a.detail || '',
                            onChange: function(v) { setAttributes({ detail: v }); }
                        }),
                        el(ToggleControl, {
                            label: 'Featured (bento span)',
                            checked: !!a.featured,
                            onChange: function(v) { setAttributes({ featured: !!v, col: v ? 2 : 1, row: v ? 2 : 1 }); }
                        })
                    )
                ),
                el('div', {
                    className: 'rawnaq-cs-editor-card',
                    style: {
                        border: '1px solid #d7e2dc',
                        borderRadius: '12px',
                        padding: '12px 14px',
                        marginBottom: '10px',
                        background: a.featured ? '#f0fdf4' : '#fff'
                    }
                },
                    el('strong', {}, a.title || 'Project'),
                    el('p', { style: { margin: '6px 0 0', fontSize: '12px', color: '#5c6f66' } },
                        [a.sector, a.year, gallery.length ? (gallery.length + ' images') : ''].filter(Boolean).join(' · ') || 'Empty project card'
                    )
                )
            );
        },
        save: function() { return null; }
    });

    registerBlockType('rawnaq/case-study-grid', {
        title: 'Case-Study Grid (Rawnaq)',
        icon: 'portfolio',
        category: 'design',
        attributes: {
            source: { type: 'string', default: 'manual' },
            projectsJson: { type: 'string', default: JSON.stringify(defaultCaseStudyProjects) },
            queryNumber: { type: 'number', default: 12 },
            queryOrderby: { type: 'string', default: 'date' },
            queryOrder: { type: 'string', default: 'DESC' },
            querySector: { type: 'string', default: '' },
            layout: { type: 'string', default: 'bento' },
            columns: { type: 'number', default: 3 },
            showFilter: { type: 'boolean', default: true },
            filterYear: { type: 'boolean', default: true },
            filterService: { type: 'boolean', default: true },
            sort: { type: 'string', default: 'custom' },
            hideBudget: { type: 'boolean', default: false },
            hideClient: { type: 'boolean', default: false },
            clickAction: { type: 'string', default: 'modal' },
            discussTarget: { type: 'string', default: 'auto' },
            initialVisible: { type: 'number', default: 0 },
            loadChunk: { type: 'number', default: 6 },
            accent: { type: 'string', default: '#fbbf24' },
            cardBg: { type: 'string', default: '#ffffff' },
            cardBorder: { type: 'string', default: '#d7e2dc' },
            radius: { type: 'number', default: 18 }
        },
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var clientId = props.clientId;
            var replaceInnerBlocks = (useDispatch('core/block-editor') || {}).replaceInnerBlocks;
            var innerInfo = useSelect(function(select) {
                var b = select('core/block-editor').getBlock(clientId);
                var inners = (b && b.innerBlocks) || [];
                return {
                    count: inners.length,
                    titles: inners.map(function(ib) {
                        return (ib.attributes && ib.attributes.title) || 'Project';
                    })
                };
            }, [clientId]);

            useEffect(function() {
                if ((attributes.source || 'manual') === 'query') {
                    return;
                }
                if (innerInfo.count > 0 || !replaceInnerBlocks || !clientId) {
                    return;
                }
                var legacy = safeParseJson(attributes.projectsJson, []);
                var seed = legacy.length ? legacy : defaultCaseStudyProjects;
                replaceInnerBlocks(clientId, caseStudyProjectsToBlocks(seed), false);
            }, [clientId, attributes.source, innerInfo.count]);

            var isQuery = (attributes.source || 'manual') === 'query';

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Source', initialOpen: true },
                        el(SelectControl, {
                            label: 'Projects source',
                            value: attributes.source || 'manual',
                            options: [
                                { label: 'Manual (InnerBlocks)', value: 'manual' },
                                { label: 'Case Study CPT (query)', value: 'query' }
                            ],
                            onChange: function(v) { setAttributes({ source: v }); }
                        }),
                        isQuery && el(RangeControl, {
                            label: 'Posts to show',
                            value: attributes.queryNumber || 12,
                            min: 1,
                            max: 50,
                            onChange: function(v) { setAttributes({ queryNumber: v || 12 }); }
                        }),
                        isQuery && el(SelectControl, {
                            label: 'Order by',
                            value: attributes.queryOrderby || 'date',
                            options: [
                                { label: 'Date', value: 'date' },
                                { label: 'Title', value: 'title' },
                                { label: 'Menu order', value: 'menu_order' }
                            ],
                            onChange: function(v) { setAttributes({ queryOrderby: v }); }
                        }),
                        isQuery && el(SelectControl, {
                            label: 'Order',
                            value: attributes.queryOrder || 'DESC',
                            options: [
                                { label: 'Descending', value: 'DESC' },
                                { label: 'Ascending', value: 'ASC' }
                            ],
                            onChange: function(v) { setAttributes({ queryOrder: v }); }
                        }),
                        isQuery && el(TextControl, {
                            label: 'Sector slug filter (optional)',
                            value: attributes.querySector || '',
                            onChange: function(v) { setAttributes({ querySector: v }); }
                        }),
                        !isQuery && el('p', { style: { fontSize: '12px', color: '#5c6f66' } },
                            'Add Case-Study Card blocks inside the canvas. Each card is one project.'
                        )
                    ),
                    el(PanelBody, { title: 'Layout & filters', initialOpen: true },
                        el(SelectControl, {
                            label: 'Layout',
                            value: attributes.layout || 'bento',
                            options: [
                                { label: 'Bento (asymmetric)', value: 'bento' },
                                { label: 'Uniform grid', value: 'uniform' },
                                { label: 'Masonry', value: 'masonry' }
                            ],
                            onChange: function(v) { setAttributes({ layout: v }); }
                        }),
                        attributes.layout !== 'bento' && el(RangeControl, {
                            label: 'Columns',
                            value: attributes.columns || 3,
                            min: 2,
                            max: 4,
                            onChange: function(v) { setAttributes({ columns: v || 3 }); }
                        }),
                        el(ToggleControl, {
                            label: 'Sector filter',
                            checked: attributes.showFilter !== false,
                            onChange: function(v) { setAttributes({ showFilter: !!v }); }
                        }),
                        el(ToggleControl, {
                            label: 'Year filter',
                            checked: attributes.filterYear !== false,
                            onChange: function(v) { setAttributes({ filterYear: !!v }); }
                        }),
                        el(ToggleControl, {
                            label: 'Service filter',
                            checked: attributes.filterService !== false,
                            onChange: function(v) { setAttributes({ filterService: !!v }); }
                        }),
                        el(SelectControl, {
                            label: 'Sort',
                            value: attributes.sort || 'custom',
                            options: [
                                { label: 'Custom order', value: 'custom' },
                                { label: 'Year (newest first)', value: 'year_desc' },
                                { label: 'Sector A–Z', value: 'sector' }
                            ],
                            onChange: function(v) { setAttributes({ sort: v }); }
                        }),
                        el(SelectControl, {
                            label: 'Card click',
                            value: attributes.clickAction || 'modal',
                            options: [
                                { label: 'Detail modal', value: 'modal' },
                                { label: 'Open case-study URL', value: 'link' },
                                { label: 'Modal + page link', value: 'both' }
                            ],
                            onChange: function(v) { setAttributes({ clickAction: v }); }
                        }),
                        el(SelectControl, {
                            label: 'Discuss this project',
                            value: attributes.discussTarget || 'auto',
                            options: [
                                { label: 'Auto (Form → Dock WA)', value: 'auto' },
                                { label: 'Smart Form only', value: 'form' },
                                { label: 'Floating Dock WhatsApp', value: 'dock' },
                                { label: 'Hide CTA', value: 'off' }
                            ],
                            onChange: function(v) { setAttributes({ discussTarget: v }); }
                        }),
                        el(ToggleControl, {
                            label: 'NDA: hide budget',
                            checked: !!attributes.hideBudget,
                            onChange: function(v) { setAttributes({ hideBudget: !!v }); }
                        }),
                        el(ToggleControl, {
                            label: 'NDA: hide client',
                            checked: !!attributes.hideClient,
                            onChange: function(v) { setAttributes({ hideClient: !!v }); }
                        })
                    ),
                    el(PanelBody, { title: 'Load more', initialOpen: false },
                        el(RangeControl, {
                            label: 'Initial visible (0 = all)',
                            value: attributes.initialVisible || 0,
                            min: 0,
                            max: 50,
                            onChange: function(v) { setAttributes({ initialVisible: v || 0 }); }
                        }),
                        el(RangeControl, {
                            label: 'Load chunk size',
                            value: attributes.loadChunk || 6,
                            min: 1,
                            max: 24,
                            onChange: function(v) { setAttributes({ loadChunk: v || 6 }); }
                        })
                    ),
                    el(PanelBody, { title: 'Style', initialOpen: false },
                        el('div', { style: { marginBottom: '10px' } },
                            el('label', { style: { display: 'block', marginBottom: '6px', fontWeight: 600 } }, 'Accent'),
                            el(ColorPalette, {
                                value: attributes.accent || '#fbbf24',
                                onChange: function(v) { setAttributes({ accent: v || '#fbbf24' }); }
                            })
                        ),
                        el(RangeControl, {
                            label: 'Card radius',
                            value: attributes.radius || 18,
                            min: 0,
                            max: 32,
                            onChange: function(v) { setAttributes({ radius: v || 0 }); }
                        })
                    )
                ),
                el('div', {
                    style: {
                        border: '1px dashed #c5d0cb',
                        borderRadius: '12px',
                        padding: '16px',
                        background: '#fafcfb'
                    }
                },
                    el('strong', {}, 'Case-Study Grid'),
                    el('p', { style: { margin: '8px 0 12px', fontSize: '13px', color: '#5c6f66' } },
                        isQuery
                            ? ('CPT query · up to ' + (attributes.queryNumber || 12) + ' · ' + (attributes.layout || 'bento'))
                            : (innerInfo.count + ' card(s) · ' + (attributes.layout || 'bento') +
                                (attributes.showFilter !== false ? ' · multi-filter' : ''))
                    ),
                    isQuery
                        ? el('p', { style: { fontSize: '13px', color: '#5c6f66', margin: 0 } },
                            'Projects load from Case Studies under Rawnaq. Publish posts there — no cards needed in the editor.'
                        )
                        : el(InnerBlocks, {
                            allowedBlocks: [ 'rawnaq/case-study-card' ],
                            template: defaultCaseStudyProjects.map(function(p) {
                                return [ 'rawnaq/case-study-card', caseStudyCardAttrsFromProject(p) ];
                            }),
                            templateLock: false,
                            renderAppender: InnerBlocks.ButtonBlockAppender
                        })
                )
            );
        },
        save: function() {
            return el(InnerBlocks.Content);
        }
    });

})(
    window.wp.blocks,
    window.wp.blockEditor || window.wp.editor,
    window.wp.element,
    window.wp.components,
    window.wp.data
);
