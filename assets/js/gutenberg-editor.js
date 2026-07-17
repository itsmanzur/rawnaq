(function(blocks, editor, element, components) {
    var el = element.createElement;
    var registerBlockType = blocks.registerBlockType;
    var InspectorControls = editor.InspectorControls;
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
            btnColor:      { type: 'string', default: '#ffffff' }
        },
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var hasImage = !!attributes.imageUrl;
            var ctaUrl = attributes.ctaLink || attributes.link;
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
                    )
                ),
                el('div', { className: 'rawnaq-tilt-container' },
                    el('div', {
                        className: 'rawnaq-tilt-card align-' + attributes.contentAlign + (hasImage ? ' has-image' : ''),
                        style: cardStyle,
                        'data-tilt-max': attributes.maxTilt,
                        'data-hover-scale': attributes.hoverScale,
                        'data-glare': attributes.glare
                    },
                        hasImage ? el('img', {
                            className: 'rawnaq-tilt-image',
                            src: attributes.imageUrl,
                            alt: attributes.imageAlt || attributes.title || ''
                        }) : null,
                        hasImage ? el('span', { className: 'rawnaq-tilt-overlay' }) : null,
                        el('span', { className: 'rawnaq-tilt-glare' }),
                        attributes.badge ? el('span', { className: 'rawnaq-tilt-badge' }, attributes.badge) : null,
                        attributes.icon ? el('span', { className: 'rawnaq-tilt-icon dashicons ' + attributes.icon }) : null,
                        el('div', { className: 'rawnaq-tilt-content' },
                            attributes.title ? el('h3', { className: 'rawnaq-tilt-title' }, attributes.title) : null,
                            attributes.desc ? el('p', { className: 'rawnaq-tilt-desc' }, attributes.desc) : null,
                            attributes.ctaText && ctaUrl
                                ? el('a', { className: 'rawnaq-tilt-btn', href: ctaUrl }, attributes.ctaText)
                                : (attributes.ctaText ? el('span', { className: 'rawnaq-tilt-btn is-static' }, attributes.ctaText) : null)
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
            loadMoreText: { type: 'string', default: 'Load more' }
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
            timezone: { type: 'string', default: 'UTC+6' },
            scheduleJson: { type: 'string', default: defaultWaSchedule },
            offHoursBehavior: { type: 'string', default: 'offline_badge' },
            offHoursRedirect: { type: 'string', default: '' },
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
                        el(TextControl, {
                            label: 'Timezone offset',
                            value: attributes.timezone || 'UTC+6',
                            help: 'e.g. UTC+6 for Bangladesh',
                            onChange: function(val) { setAttributes({ timezone: val }); }
                        }),
                        scheduleFields,
                        el(SelectControl, {
                            label: 'Off-hours Behavior',
                            value: attributes.offHoursBehavior || 'offline_badge',
                            options: [
                                { label: 'Offline badge', value: 'offline_badge' },
                                { label: 'Hide dock', value: 'hide' },
                                { label: 'Redirect URL', value: 'redirect' }
                            ],
                            onChange: function(val) { setAttributes({ offHoursBehavior: val }); }
                        }),
                        attributes.offHoursBehavior === 'redirect' ? el(TextControl, {
                            label: 'Redirect URL',
                            value: attributes.offHoursRedirect || '',
                            onChange: function(val) { setAttributes({ offHoursRedirect: val }); }
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
            nodesJson: {
                type: 'string',
                default: '[{"id":"ceo","parent":"","title":"Founder / CEO","role":"Leadership","icon":"★","detail":"Leads the company.","link":"","decision":false,"x_pos":0,"y_pos":0,"badge":"","status":"default"}]'
            },
            accentColor:    { type: 'string', default: '#FBBF24' },
            rootColorFrom:  { type: 'string', default: '#4338CA' },
            rootColorTo:    { type: 'string', default: '#7C3AED' },
            lineColor:      { type: 'string', default: '#E6E2F0' },
            nodeBg:         { type: 'string', default: '#ffffff' },
            nodeRadius:     { type: 'number', default: 14 },
            dataSource:     { type: 'string', default: 'manual' },
            sheetUrl:       { type: 'string', default: '' },
            visualPreset:   { type: 'string', default: 'default' },
            showSearch:     { type: 'boolean', default: false },
            enableCollapse: { type: 'boolean', default: true }
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
            var cfg = {
                mode: attributes.mode || 'org',
                connector: attributes.connector || 'curved',
                nodes: mappedNodes,
                direction: attributes.direction || 'tb',
                shape: shapeVal,
                zoom: true
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
            }, [attributes.nodesJson, attributes.mode, attributes.connector, attributes.direction, attributes.shape, attributes.accentColor, attributes.rootColorFrom, attributes.rootColorTo, attributes.lineColor, attributes.nodeBg, attributes.nodeRadius, flowAttr]);

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
                            label: 'Data Source',
                            value: attributes.dataSource || 'manual',
                            options: [
                                { label: 'Manual Nodes Repeater', value: 'manual' },
                                { label: 'WordPress Site Users', value: 'wp_users' },
                                { label: 'Google Sheet CSV Sync', value: 'google_sheet' }
                            ],
                            onChange: function(v) { setAttributes({ dataSource: v }); }
                        }),
                        (attributes.dataSource === 'google_sheet') && el(TextControl, {
                            label: 'Google Sheet CSV URL',
                            value: attributes.sheetUrl || '',
                            onChange: function(v) { setAttributes({ sheetUrl: v }); }
                        }),
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
                            label: 'Connector',
                            value: attributes.connector || 'curved',
                            options: [
                                { label: 'Curved', value: 'curved' },
                                { label: 'Elbow', value: 'elbow' },
                                { label: 'Straight', value: 'straight' },
                                { label: 'Dashed', value: 'dashed' }
                            ],
                            onChange: function(v) { setAttributes({ connector: v }); }
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
                    el(PanelBody, { title: 'Nodes', initialOpen: true },
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
                    className: 'rawnaq-flow-chart',
                    'data-flow': flowAttr,
                    ref: chartRef,
                    style: {
                        minHeight: '200px',
                        '--fc-amber': attributes.accentColor || '#FBBF24',
                        '--fc-indigo': attributes.rootColorFrom || '#4338CA',
                        '--fc-violet': attributes.rootColorTo || '#7C3AED',
                        '--fc-line': attributes.lineColor || '#E6E2F0',
                        '--fc-panel': attributes.nodeBg || '#ffffff',
                        '--fc-radius': (attributes.nodeRadius || 14) + 'px'
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
            scrollOffset: { type: 'number', default: 80 },
            smooth: { type: 'boolean', default: true },
            readingTime: { type: 'boolean', default: true },
            showPercent: { type: 'boolean', default: true },
            mobileCollapse: { type: 'boolean', default: true },
            manualJson: { type: 'string', default: '[]' },
            collapseSubs: { type: 'boolean', default: false },
            showSearch: { type: 'boolean', default: false },
            dockAttach: { type: 'boolean', default: false }
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
                        el(RangeControl, {
                            label: 'Scroll offset',
                            value: attributes.scrollOffset || 80,
                            onChange: function(v) { setAttributes({ scrollOffset: v }); },
                            min: 0, max: 200
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
            var cells = safeParseJson(attributes.cellsJson, []);
            var gridRef = useRef ? useRef(null) : { current: null };

            function updateCells(next) {
                setAttributes({ cellsJson: JSON.stringify(next) });
            }

            function patchCell(idx, patch) {
                var updated = cells.slice();
                updated[idx] = Object.assign({}, updated[idx], patch);
                updateCells(updated);
            }

            function applyBentoPreset() {
                var key = attributes.preset || 'featured';
                if (key === 'custom') {
                    return;
                }
                var pack = window.rawnaqBentoPresets && rawnaqBentoPresets[key];
                if (!pack || !pack.cells) {
                    return;
                }
                var next = {
                    cellsJson: JSON.stringify(pack.cells)
                };
                if (pack.columns) {
                    next.columns = pack.columns;
                }
                setAttributes(next);
            }

            var cols = 4;
            if (attributes.preset === 'wide') {
                cols = 3;
            } else if (attributes.preset === 'custom') {
                cols = Math.max(2, Math.min(6, attributes.columns || 4));
            } else if (attributes.preset === 'equal' || attributes.preset === 'featured') {
                cols = 4;
            }

            useEffect(function() {
                var t = setTimeout(function() {
                    var node = gridRef.current;
                    if (node && typeof window.rawnaqBentoGridMount === 'function') {
                        node.classList.remove('bento-bound');
                        window.rawnaqBentoGridMount(node);
                    }
                }, 50);
                return function() { clearTimeout(t); };
            }, [attributes.cellsJson, attributes.preset, attributes.columns, attributes.reveal]);

            var cellFields = cells.map(function(cell, idx) {
                return el('div', {
                    key: idx,
                    style: { background: '#f3f7f4', padding: '10px', marginBottom: '10px', borderRadius: '8px' }
                },
                    el('p', { style: { margin: '0 0 8px', fontWeight: 700 } }, 'Cell ' + (idx + 1)),
                    el(SelectControl, {
                        label: 'Type',
                        value: cell.type || 'text',
                        options: [
                            { label: 'Icon + Text', value: 'text' },
                            { label: 'Featured', value: 'featured' },
                            { label: 'Image', value: 'image' },
                            { label: 'Stat', value: 'stat' },
                            { label: 'Video', value: 'video' },
                            { label: 'Testimonial', value: 'testimonial' }
                        ],
                        onChange: function(val) { patchCell(idx, { type: val }); }
                    }),
                    el(RangeControl, {
                        label: 'Column Span', value: cell.col || 1,
                        onChange: function(val) { patchCell(idx, { col: val || 1 }); },
                        min: 1, max: 6
                    }),
                    el(RangeControl, {
                        label: 'Row Span', value: cell.row || 1,
                        onChange: function(val) { patchCell(idx, { row: val || 1 }); },
                        min: 1, max: 4
                    }),
                    el(SelectControl, {
                        label: 'Content Align',
                        value: cell.align || '',
                        options: [
                            { label: 'Default (by type)', value: '' },
                            { label: 'Top', value: 'top' },
                            { label: 'Center', value: 'center' },
                            { label: 'Bottom', value: 'bottom' }
                        ],
                        help: 'Vertical alignment of cell content',
                        onChange: function(val) { patchCell(idx, { align: val || '' }); }
                    }),
                    el(RangeControl, {
                        label: 'Order (Desktop)', value: cell.order || 0,
                        onChange: function(val) { patchCell(idx, { order: val || 0 }); },
                        min: -20, max: 20,
                        help: '0 = natural order'
                    }),
                    el('p', { style: { margin: '12px 0 6px', fontWeight: 600, fontSize: '12px' } }, 'Tablet / Mobile'),
                    el(RangeControl, {
                        label: 'Col Span (Tablet ≤900px)', value: cell.colMd || 0,
                        onChange: function(val) { patchCell(idx, { colMd: val || 0 }); },
                        min: 0, max: 6,
                        help: '0 = inherit desktop; tablet grid is 2 cols'
                    }),
                    el(RangeControl, {
                        label: 'Row Span (Tablet)', value: cell.rowMd || 0,
                        onChange: function(val) { patchCell(idx, { rowMd: val || 0 }); },
                        min: 0, max: 4
                    }),
                    el(RangeControl, {
                        label: 'Order (Tablet)', value: cell.orderMd || 0,
                        onChange: function(val) { patchCell(idx, { orderMd: val || 0 }); },
                        min: -20, max: 20
                    }),
                    el(RangeControl, {
                        label: 'Col Span (Mobile ≤640px)', value: cell.colSm || 0,
                        onChange: function(val) { patchCell(idx, { colSm: val || 0 }); },
                        min: 0, max: 2,
                        help: '0 = full width; 1 = half of 2-col mobile grid'
                    }),
                    el(RangeControl, {
                        label: 'Row Span (Mobile)', value: cell.rowSm || 0,
                        onChange: function(val) { patchCell(idx, { rowSm: val || 0 }); },
                        min: 0, max: 4
                    }),
                    el(RangeControl, {
                        label: 'Order (Mobile)', value: cell.orderSm || 0,
                        onChange: function(val) { patchCell(idx, { orderSm: val || 0 }); },
                        min: -20, max: 20
                    }),
                    el(TextControl, {
                        label: 'Tag', value: cell.tag || '',
                        onChange: function(val) { patchCell(idx, { tag: val }); }
                    }),
                    cell.tag ? el(TextControl, {
                        label: 'Tag Background', value: cell.tagBg || '',
                        onChange: function(val) { patchCell(idx, { tagBg: val }); },
                        help: 'Optional override — empty uses global Tag / Badge colors'
                    }) : null,
                    cell.tag ? el(TextControl, {
                        label: 'Tag Text Color', value: cell.tagColor || '',
                        onChange: function(val) { patchCell(idx, { tagColor: val }); }
                    }) : null,
                    (cell.type || 'text') !== 'stat' ? el(TextControl, {
                        label: cell.type === 'testimonial' ? 'Author Name' : 'Title',
                        value: cell.title || '',
                        onChange: function(val) { patchCell(idx, { title: val }); }
                    }) : null,
                    el(TextareaControl, {
                        label: cell.type === 'testimonial' ? 'Quote' : 'Subtitle',
                        value: cell.subtitle || '',
                        onChange: function(val) { patchCell(idx, { subtitle: val }); }
                    }),
                    cell.type === 'testimonial' ? el(Fragment, {},
                        el(TextControl, {
                            label: 'Author Role / Company', value: cell.role || '',
                            onChange: function(val) { patchCell(idx, { role: val }); }
                        }),
                        el(TextControl, {
                            label: 'Avatar URL', value: cell.avatar || '',
                            onChange: function(val) { patchCell(idx, { avatar: val }); }
                        }),
                        el(RangeControl, {
                            label: 'Star Rating', value: typeof cell.rating === 'number' ? cell.rating : 5,
                            onChange: function(val) { patchCell(idx, { rating: typeof val === 'number' ? val : 0 }); },
                            min: 0, max: 5,
                            help: '0 hides stars'
                        })
                    ) : null,
                    (cell.type === 'text' || cell.type === 'featured') ? el(TextControl, {
                        label: 'Dashicon', value: cell.icon || '',
                        help: 'e.g. dashicons-star-filled',
                        onChange: function(val) { patchCell(idx, { icon: val }); }
                    }) : null,
                    cell.type === 'image' ? el(TextControl, {
                        label: 'Image URL', value: cell.image || '',
                        onChange: function(val) { patchCell(idx, { image: val }); }
                    }) : null,
                    cell.type === 'video' ? el(TextControl, {
                        label: 'Video URL', value: cell.video || '',
                        help: 'YouTube, Vimeo, or direct mp4/webm URL',
                        onChange: function(val) { patchCell(idx, { video: val }); }
                    }) : null,
                    cell.type === 'stat' ? el(Fragment, {},
                        el(TextControl, {
                            label: 'Stat Value', value: cell.stat || '',
                            onChange: function(val) { patchCell(idx, { stat: val }); }
                        }),
                        el(TextControl, {
                            label: 'Suffix', value: cell.suffix || '',
                            onChange: function(val) { patchCell(idx, { suffix: val }); }
                        }),
                        el(TextControl, {
                            label: 'Prefix', value: cell.prefix || '',
                            onChange: function(val) { patchCell(idx, { prefix: val }); }
                        })
                    ) : null,
                    el(TextControl, {
                        label: 'Link', value: cell.link || '',
                        onChange: function(val) { patchCell(idx, { link: val }); },
                        help: 'Whole-cell link when CTA is empty'
                    }),
                    el(TextControl, {
                        label: 'Sync Timeline ID',
                        value: cell.timelineSync || '',
                        onChange: function(val) { patchCell(idx, { timelineSync: val }); },
                        help: 'Paste Named Timeline ID from Scroll Sync Timeline'
                    }),
                    el(TextControl, {
                        label: 'CTA Button Text', value: cell.ctaText || '',
                        onChange: function(val) { patchCell(idx, { ctaText: val }); },
                        help: 'Optional button under cell content'
                    }),
                    cell.ctaText ? el(TextControl, {
                        label: 'CTA Button Link', value: cell.ctaLink || '',
                        onChange: function(val) { patchCell(idx, { ctaLink: val }); },
                        help: 'Falls back to Cell Link if empty'
                    }) : null,
                    el(Button, {
                        isDestructive: true, isSmall: true,
                        onClick: function() { updateCells(cells.filter(function(_, i) { return i !== idx; })); }
                    }, 'Remove')
                );
            });

            var previewCells = cells.map(function(cell, idx) {
                var type = cell.type || 'text';
                var layout = bentoCellLayout(cell);
                var cls = 'rawnaq-bento-cell is-in';
                if (type === 'featured') cls += ' is-featured';
                if (type === 'image') cls += ' is-image';
                if (type === 'stat') cls += ' is-stat';
                if (type === 'video') cls += ' is-video';
                if (type === 'testimonial') cls += ' is-testimonial';
                if (cell.align === 'top' || cell.align === 'center' || cell.align === 'bottom') {
                    cls += ' is-align-' + cell.align;
                }
                if (layout.hasSmSpan) cls += ' has-sm-span';
                var syncTl = (cell.timelineSync || '').toString().replace(/[^a-zA-Z0-9_-]/g, '');
                if (syncTl && /^[0-9]/.test(syncTl)) { syncTl = 'tl-' + syncTl; }
                if (syncTl) { cls += ' tl-sync'; }
                var style = layout.style;
                if (syncTl) { style += 'animation-timeline:--' + syncTl + ';'; }
                var attrs = { className: cls, style: style, key: idx };
                if (syncTl) { attrs['data-tl-sync'] = syncTl; }
                var ctaText = (cell.ctaText || '').toString().trim();
                var ctaUrl = cell.ctaLink || cell.link || '';
                var ctaEl = ctaText
                    ? (ctaUrl
                        ? el('a', { className: 'rawnaq-bento-cta', href: ctaUrl }, ctaText)
                        : el('span', { className: 'rawnaq-bento-cta is-static' }, ctaText))
                    : null;
                var rating = Math.max(0, Math.min(5, parseInt(cell.rating, 10) || 0));
                var stars = '';
                for (var si = 0; si < rating; si++) { stars += '★'; }

                if (type === 'testimonial') {
                    return el('div', attrs,
                        bentoTagEl(el, cell),
                        cell.subtitle ? el('blockquote', { className: 'rawnaq-bento-quote' }, cell.subtitle) : null,
                        rating > 0 ? el('div', { className: 'rawnaq-bento-stars' }, stars) : null,
                        (cell.title || cell.role || cell.avatar) ? el('div', { className: 'rawnaq-bento-author' },
                            cell.avatar
                                ? el('img', { className: 'rawnaq-bento-avatar', src: cell.avatar, alt: '' })
                                : (cell.title
                                    ? el('div', { className: 'rawnaq-bento-avatar is-placeholder' }, (cell.title || '').charAt(0).toUpperCase())
                                    : null),
                            (cell.title || cell.role) ? el('div', { className: 'rawnaq-bento-author-meta' },
                                cell.title ? el('div', { className: 'rawnaq-bento-author-name' }, cell.title) : null,
                                cell.role ? el('div', { className: 'rawnaq-bento-author-role' }, cell.role) : null
                            ) : null
                        ) : null,
                        ctaEl
                    );
                }
                if (type === 'image') {
                    return el('div', attrs,
                        cell.image
                            ? el('img', { className: 'rawnaq-bento-media', src: cell.image, alt: '' })
                            : el('div', { className: 'rawnaq-bento-media', style: { background: 'linear-gradient(135deg,#0f766e,#134e4a)' } }),
                        el('div', { className: 'rawnaq-bento-overlay' }),
                        el('div', { className: 'rawnaq-bento-body' },
                            bentoTagEl(el, cell),
                            cell.title ? el('div', { className: 'rawnaq-bento-title' }, cell.title) : null,
                            cell.subtitle ? el('div', { className: 'rawnaq-bento-sub' }, cell.subtitle) : null,
                            ctaEl
                        )
                    );
                }
                if (type === 'stat') {
                    return el('div', attrs,
                        bentoTagEl(el, cell),
                        el('div', { className: 'rawnaq-bento-num' }, (cell.prefix || '') + (cell.stat || '0') + (cell.suffix || '')),
                        cell.subtitle ? el('div', { className: 'rawnaq-bento-sub' }, cell.subtitle) : null,
                        ctaEl
                    );
                }
                if (type === 'video') {
                    var parsedVid = bentoParseVideo(cell.video || '');
                    if (parsedVid && (parsedVid.kind === 'youtube' || parsedVid.kind === 'vimeo')) {
                        cls += ' is-embed';
                    }
                    return el('div', attrs,
                        parsedVid && parsedVid.embed
                            ? el('iframe', {
                                className: 'rawnaq-bento-embed',
                                src: parsedVid.embed,
                                title: 'Video',
                                allow: 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share'
                            })
                            : (parsedVid && parsedVid.src
                                ? el('video', { className: 'rawnaq-bento-video', muted: true, playsInline: true, src: parsedVid.src })
                                : el('div', { className: 'rawnaq-bento-media', style: { background: '#1e1b2e' } })),
                        el('div', { className: 'rawnaq-bento-overlay' }),
                        el('div', { className: 'rawnaq-bento-body' },
                            bentoTagEl(el, cell),
                            cell.title ? el('div', { className: 'rawnaq-bento-title' }, cell.title) : null,
                            cell.subtitle ? el('div', { className: 'rawnaq-bento-sub' }, cell.subtitle) : null,
                            ctaEl
                        )
                    );
                }
                return el('div', attrs,
                    bentoTagEl(el, cell),
                    cell.icon ? el('div', { className: 'rawnaq-bento-icon' },
                        el('span', { className: 'dashicons ' + cell.icon })
                    ) : null,
                    cell.title ? el('div', { className: 'rawnaq-bento-title' }, cell.title) : null,
                    cell.subtitle ? el('div', { className: 'rawnaq-bento-sub' }, cell.subtitle) : null,
                    ctaEl
                );
            });

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Layout', initialOpen: true },
                        el(SelectControl, {
                            label: 'Preset',
                            value: attributes.preset || 'featured',
                            options: [
                                { label: '1 large + 4 small', value: 'featured' },
                                { label: '2×2 equal', value: 'equal' },
                                { label: '1 wide + stacked', value: 'wide' },
                                { label: 'Custom columns', value: 'custom' }
                            ],
                            help: 'Choose a layout, then Apply Preset to replace cells.',
                            onChange: function(val) { setAttributes({ preset: val }); }
                        }),
                        attributes.preset !== 'custom' ? el(Button, {
                            isPrimary: true,
                            style: { marginBottom: '12px' },
                            onClick: applyBentoPreset
                        }, 'Apply Preset to Cells') : null,
                        attributes.preset === 'custom' ? el(RangeControl, {
                            label: 'Columns', value: attributes.columns || 4,
                            onChange: function(val) { setAttributes({ columns: val }); },
                            min: 2, max: 6
                        }) : null,
                        el(RangeControl, {
                            label: 'Row Height', value: attributes.rowHeight || 140,
                            onChange: function(val) { setAttributes({ rowHeight: val }); },
                            min: 80, max: 240
                        }),
                        el(RangeControl, {
                            label: 'Column Gap',
                            value: typeof attributes.columnGap === 'number' ? attributes.columnGap : (attributes.gap || 16),
                            onChange: function(val) { setAttributes({ columnGap: typeof val === 'number' ? val : 16 }); },
                            min: 0, max: 40
                        }),
                        el(RangeControl, {
                            label: 'Row Gap',
                            value: typeof attributes.rowGap === 'number' ? attributes.rowGap : (attributes.gap || 16),
                            onChange: function(val) { setAttributes({ rowGap: typeof val === 'number' ? val : 16 }); },
                            min: 0, max: 40
                        }),
                        el(RangeControl, {
                            label: 'Radius', value: attributes.radius || 18,
                            onChange: function(val) { setAttributes({ radius: val }); },
                            min: 0, max: 40
                        }),
                        el(SelectControl, {
                            label: 'Hover',
                            value: attributes.hoverEffect || 'lift',
                            options: [
                                { label: 'Lift', value: 'lift' },
                                { label: 'Zoom media', value: 'zoom' },
                                { label: 'Tint', value: 'tint' },
                                { label: 'None', value: 'none' }
                            ],
                            onChange: function(val) { setAttributes({ hoverEffect: val }); }
                        }),
                        el(ToggleControl, {
                            label: 'Scroll Reveal',
                            checked: attributes.reveal !== false,
                            onChange: function(val) { setAttributes({ reveal: !!val }); }
                        }),
                        el(ToggleControl, {
                            label: 'Hairline Borders',
                            checked: !!attributes.hairline,
                            onChange: function(val) { setAttributes({ hairline: !!val }); }
                        }),
                        el(RangeControl, {
                            label: 'Image Overlay Opacity',
                            value: typeof attributes.overlayOpacity === 'number' ? attributes.overlayOpacity : 100,
                            onChange: function(val) { setAttributes({ overlayOpacity: typeof val === 'number' ? val : 100 }); },
                            min: 0,
                            max: 100,
                            help: 'Dark gradient on image/video cells. 0 = none, 100 = default.'
                        })
                    ),
                    el(PanelBody, { title: 'Tag / Badge', initialOpen: true },
                        el('p', { style: { margin: '0 0 8px', fontSize: '12px', color: '#5c6f66' } },
                            'Small pill labels like HIGHLIGHT / SHOWCASE'),
                        el(TextControl, {
                            label: 'Tag Background', value: attributes.tagBg || '#fef3c7',
                            onChange: function(val) { setAttributes({ tagBg: val }); }
                        }),
                        el(TextControl, {
                            label: 'Tag Text', value: attributes.tagColor || '#92400e',
                            onChange: function(val) { setAttributes({ tagColor: val }); }
                        })
                    ),
                    el(PanelBody, { title: 'Colors', initialOpen: false },
                        el(TextControl, {
                            label: 'Title', value: attributes.titleColor || '#13231c',
                            onChange: function(val) { setAttributes({ titleColor: val }); }
                        }),
                        el(TextControl, {
                            label: 'Subtitle', value: attributes.subColor || '#5c6f66',
                            onChange: function(val) { setAttributes({ subColor: val }); }
                        }),
                        el(TextControl, {
                            label: 'Icon', value: attributes.iconColor || '#0f766e',
                            onChange: function(val) { setAttributes({ iconColor: val }); }
                        }),
                        el(TextControl, {
                            label: 'Stat Number', value: attributes.statColor || '#0f766e',
                            onChange: function(val) { setAttributes({ statColor: val }); }
                        }),
                        el(TextControl, {
                            label: 'Cell Background', value: attributes.cellBg || '#ffffff',
                            onChange: function(val) { setAttributes({ cellBg: val }); }
                        }),
                        el(TextControl, {
                            label: 'Cell Border', value: attributes.cellBorder || '#d7e2dc',
                            onChange: function(val) { setAttributes({ cellBorder: val }); }
                        }),
                        el(TextControl, {
                            label: 'Featured From', value: attributes.featuredFrom || '#0f766e',
                            onChange: function(val) { setAttributes({ featuredFrom: val }); }
                        }),
                        el(TextControl, {
                            label: 'Featured To', value: attributes.featuredTo || '#134e4a',
                            onChange: function(val) { setAttributes({ featuredTo: val }); }
                        }),
                        el(TextControl, {
                            label: 'CTA Background', value: attributes.ctaBg || '#fbbf24',
                            onChange: function(val) { setAttributes({ ctaBg: val }); }
                        }),
                        el(TextControl, {
                            label: 'CTA Text', value: attributes.ctaColor || '#92400e',
                            onChange: function(val) { setAttributes({ ctaColor: val }); }
                        })
                    ),
                    el(PanelBody, { title: 'Cells', initialOpen: true },
                        cellFields,
                        el(Button, {
                            isSecondary: true,
                            onClick: function() {
                                updateCells(cells.concat({
                                    type: 'text', col: 1, row: 1, tag: '', title: 'New cell',
                                    subtitle: '', icon: 'dashicons-admin-generic', image: '', video: '',
                                    stat: '', suffix: '', prefix: '', link: '', ctaText: '', ctaLink: '',
                                    role: '', avatar: '', rating: 0, align: '',
                                    order: 0, colMd: 0, rowMd: 0, orderMd: 0, colSm: 0, rowSm: 0, orderSm: 0
                                }));
                            }
                        }, '+ Add Cell')
                    )
                ),
                el('div', { className: 'rawnaq-bento-editor-frame' },
                    el('div', {
                        ref: gridRef,
                        className: 'rawnaq-bento-grid' + (attributes.hairline ? ' rawnaq-bento-hairline' : ''),
                        style: {
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
                        },
                        'data-cols': String(cols),
                        'data-reveal': '0',
                        'data-hover': attributes.hoverEffect || 'lift',
                        role: 'list'
                    }, previewCells)
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
