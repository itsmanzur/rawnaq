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
                default: '[{"meta":"Phase 1","title":"Ideation & Sketching","desc":"Gather initial ideas and draft blueprints.","icon":"","imageUrl":"","imageId":0,"ctaText":"","ctaLink":""},{"meta":"Phase 2","title":"Prototype Review","desc":"Interactive mockups and client reviews.","icon":"","imageUrl":"","imageId":0,"ctaText":"","ctaLink":""},{"meta":"Phase 3","title":"Development & Coding","desc":"Build, test, and deploy clean code.","icon":"","imageUrl":"","imageId":0,"ctaText":"","ctaLink":""}]'
            },
            layout: { type: 'string', default: 'alternating' },
            showNumbers: { type: 'boolean', default: true },
            lineBg: { type: 'string', default: '#e2e8f0' },
            lineActive: { type: 'string', default: '#6366f1' },
            bulletBorder: { type: 'string', default: '#cbd5e1' },
            bulletActive: { type: 'string', default: '#6366f1' },
            cardBg: { type: 'string', default: '#ffffff' },
            metaColor: { type: 'string', default: '#6366f1' },
            titleColor: { type: 'string', default: '#1a1a1a' },
            descColor: { type: 'string', default: '#666666' },
            ctaColor: { type: 'string', default: '#6366f1' },
            cardRadius: { type: 'number', default: 16 },
            bulletSize: { type: 'number', default: 28 },
            itemGap: { type: 'number', default: 20 }
        },
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var steps = safeParseJson(attributes.stepsJson, []);
            var layout = attributes.layout || 'alternating';
            var showNumbers = attributes.showNumbers !== false;
            var wrapStyle = {
                '--tl-line-bg': attributes.lineBg || '#e2e8f0',
                '--tl-line-active': attributes.lineActive || '#6366f1',
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
                if (step.imageUrl) {
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

            var wrapClass = 'rawnaq-timeline-wrapper layout-' + layout + (showNumbers ? ' show-numbers' : '');

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Layout', initialOpen: true },
                        el(SelectControl, {
                            label: 'Layout Mode',
                            value: layout,
                            options: [
                                { label: 'Alternating (Left / Right)', value: 'alternating' },
                                { label: 'All Left', value: 'left' },
                                { label: 'All Right', value: 'right' }
                            ],
                            onChange: function(val) { setAttributes({ layout: val }); }
                        }),
                        el(ToggleControl, {
                            label: 'Show Step Numbers',
                            checked: showNumbers,
                            onChange: function(val) { setAttributes({ showNumbers: !!val }); }
                        })
                    ),
                    el(PanelBody, { title: 'Style & Colors', initialOpen: true },
                        el(TextControl, { label: 'Line Background (Hex)', value: attributes.lineBg || '#e2e8f0', onChange: function(val) { setAttributes({ lineBg: val }); } }),
                        el(TextControl, { label: 'Active Line (Hex)', value: attributes.lineActive || '#6366f1', onChange: function(val) { setAttributes({ lineActive: val }); } }),
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
                    el(PanelBody, { title: 'Timeline Steps', initialOpen: false },
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
                                    ctaText: '',
                                    ctaLink: ''
                                }));
                            }
                        }, '+ Add Milestone')
                    )
                ),
                el('div', { className: wrapClass, 'data-show-numbers': showNumbers ? '1' : '0', style: wrapStyle },
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
            maxScale: { type: 'number', default: 1.6 }
        },
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var items = safeParseJson(attributes.itemsJson, []);
            var magnify = attributes.magnify !== false;

            function updateItems(newItems) {
                setAttributes({ itemsJson: JSON.stringify(newItems) });
            }

            function patchItem(idx, patch) {
                var updated = items.slice();
                updated[idx] = Object.assign({}, updated[idx], patch);
                updateItems(updated);
            }

            var wrapStyle = {
                '--dock-offset': (attributes.offset || 20) + 'px',
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

            var className = 'rawnaq-dock-container pos-' + (attributes.position || 'bottom') +
                (attributes.hideMobile ? ' hide-mobile' : '') +
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

            var previewItems = items.map(function(item, idx) {
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

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Layout', initialOpen: true },
                        el(SelectControl, {
                            label: 'Position', value: attributes.position || 'bottom',
                            options: [
                                { label: 'Bottom Center', value: 'bottom' },
                                { label: 'Sidebar Left', value: 'left' },
                                { label: 'Sidebar Right', value: 'right' }
                            ],
                            onChange: function(val) { setAttributes({ position: val }); }
                        }),
                        el(RangeControl, {
                            label: 'Edge Offset (px)',
                            value: attributes.offset || 20,
                            onChange: function(val) { setAttributes({ offset: val }); },
                            min: 0, max: 80
                        }),
                        el(ToggleControl, {
                            label: 'Hide on Mobile',
                            checked: !!attributes.hideMobile,
                            onChange: function(val) { setAttributes({ hideMobile: !!val }); }
                        }),
                        !attributes.hideMobile ? el(ToggleControl, {
                            label: 'Show Labels on Mobile',
                            checked: !!attributes.mobileLabels,
                            onChange: function(val) { setAttributes({ mobileLabels: !!val }); }
                        }) : null
                    ),
                    el(PanelBody, { title: 'Style & Colors', initialOpen: true },
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
                    ),
                    el(PanelBody, { title: 'Magnify Effect', initialOpen: false },
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
                    ),
                    el(PanelBody, { title: 'Dock Items', initialOpen: false },
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
                    )
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
            nodesJson: {
                type: 'string',
                default: '[{"id":"ceo","parent":"","title":"Founder / CEO","role":"Leadership","icon":"★","detail":"Leads the company.","link":"","decision":false},{"id":"eng","parent":"ceo","title":"Engineering","role":"Product","icon":"⚙","detail":"Engineering roadmap.","link":"","decision":false},{"id":"ops","parent":"ceo","title":"Operations","role":"Delivery","icon":"◆","detail":"Project delivery.","link":"","decision":false},{"id":"e1","parent":"eng","title":"Frontend","role":"Team","icon":"▪","detail":"UI work.","link":"","decision":false}]'
            }
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

            var cfg = {
                mode: attributes.mode || 'org',
                connector: attributes.connector || 'curved',
                nodes: nodes
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
            }, [attributes.nodesJson, attributes.mode, attributes.connector, flowAttr]);

            var fields = nodes.map(function(node, idx) {
                return el('div', { key: idx, style: { background: '#f3f4f6', padding: '10px', marginBottom: '10px', borderRadius: '8px' } },
                    el('p', { style: { margin: '0 0 8px', fontWeight: 700 } }, 'Node ' + (idx + 1)),
                    el(TextControl, { label: 'ID', value: node.id || '', onChange: function(v) { patchNode(idx, { id: v }); } }),
                    el(TextControl, { label: 'Parent ID', value: node.parent || '', onChange: function(v) { patchNode(idx, { parent: v }); } }),
                    el(TextControl, { label: 'Title', value: node.title || '', onChange: function(v) { patchNode(idx, { title: v }); } }),
                    el(TextControl, { label: 'Role / Subtitle', value: node.role || '', onChange: function(v) { patchNode(idx, { role: v }); } }),
                    el(TextControl, { label: 'Icon (emoji or dashicons-*)', value: node.icon || '', onChange: function(v) { patchNode(idx, { icon: v }); } }),
                    el(TextareaControl, { label: 'Detail', value: node.detail || '', onChange: function(v) { patchNode(idx, { detail: v }); } }),
                    el(TextControl, { label: 'Link', value: node.link || '', onChange: function(v) { patchNode(idx, { link: v }); } }),
                    el(ToggleControl, {
                        label: 'Decision node',
                        checked: !!node.decision,
                        onChange: function(v) { patchNode(idx, { decision: !!v }); }
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
                            label: 'Mode',
                            value: attributes.mode || 'org',
                            options: [
                                { label: 'Org (tree)', value: 'org' },
                                { label: 'Process (flow)', value: 'process' }
                            ],
                            onChange: function(v) { setAttributes({ mode: v }); }
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
                                    decision: false
                                }));
                            }
                        }, '+ Add Node')
                    )
                ),
                el('div', {
                    className: 'rawnaq-flow-chart',
                    'data-flow': flowAttr,
                    ref: chartRef,
                    style: { minHeight: '200px' }
                },
                    el('div', { className: 'rawnaq-flow-stage is-responsive' })
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
            manualJson: { type: 'string', default: '[]' }
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

})(
    window.wp.blocks,
    window.wp.blockEditor || window.wp.editor,
    window.wp.element,
    window.wp.components
);
