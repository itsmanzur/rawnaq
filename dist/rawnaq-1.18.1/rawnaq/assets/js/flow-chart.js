/**
 * Rawnaq Flow Chart — org/process/freeform, direction, shapes, zoom/pan, lazy 20+
 */
(function () {
    'use strict';

    var instances = [];
    var elementorHooked = false;
    var NODE_W = 150;
    var NODE_H = 72;
    var GAP_X = 40;
    var GAP_Y = 56;
    var LAZY_THRESHOLD = 20;
    var FREEFORM_W = 900;
    var FREEFORM_H = 560;

    function prefersReducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function isMobile() {
        return window.matchMedia && window.matchMedia('(max-width: 767px)').matches;
    }

    function parseConfig(el) {
        var raw = el.getAttribute('data-flow');
        if (!raw) {
            return null;
        }
        try {
            return JSON.parse(raw);
        } catch (e1) {
            try {
                return JSON.parse(decodeURIComponent(raw));
            } catch (e2) {
                return null;
            }
        }
    }

    function resolveDirection(dir) {
        var d = dir || 'tb';
        if (d !== 'tb' && d !== 'lr' && d !== 'rl') {
            d = 'tb';
        }
        var rtl = false;
        try {
            rtl = document.documentElement.getAttribute('dir') === 'rtl'
                || !!(document.body && document.body.classList.contains('rtl'));
        } catch (e) { /* ignore */ }
        if (rtl) {
            if (d === 'lr') {
                return 'rl';
            }
            if (d === 'rl') {
                return 'lr';
            }
        }
        return d;
    }

    function destroyInstance(inst) {
        if (inst.onDocClick) {
            document.removeEventListener('click', inst.onDocClick);
        }
        if (inst.onWheel) {
            inst.viewport && inst.viewport.removeEventListener('wheel', inst.onWheel);
        }
        if (inst.onPointerDown) {
            inst.viewport && inst.viewport.removeEventListener('pointerdown', inst.onPointerDown);
        }
        if (inst.onPointerMove) {
            document.removeEventListener('pointermove', inst.onPointerMove);
        }
        if (inst.onPointerUp) {
            document.removeEventListener('pointerup', inst.onPointerUp);
        }
        if (inst.lazyObs) {
            inst.lazyObs.disconnect();
        }
        if (inst.root) {
            inst.root.classList.remove('fc-bound');
            var stage = inst.root.querySelector('.rawnaq-flow-stage');
            if (stage) {
                stage.innerHTML = '';
            }
            var chrome = inst.root.querySelector('.rawnaq-flow-zoom');
            if (chrome) {
                chrome.remove();
            }
        }
    }

    function destroyAll() {
        instances.forEach(destroyInstance);
        instances = [];
    }

    function destroyOne(root) {
        instances = instances.filter(function (inst) {
            if (inst.root === root) {
                destroyInstance(inst);
                return false;
            }
            return true;
        });
        if (root) {
            root.classList.remove('fc-bound');
        }
    }

    function breakCycles(nodes) {
        var byId = {};
        nodes.forEach(function (n) {
            byId[n.id] = n;
        });
        nodes.forEach(function (n) {
            var parent = n.parent || '';
            if (!parent || !byId[parent] || parent === n.id) {
                n.parent = '';
                return;
            }
            var seen = {};
            seen[n.id] = true;
            var cur = parent;
            while (cur && byId[cur]) {
                if (seen[cur]) {
                    n.parent = '';
                    return;
                }
                seen[cur] = true;
                cur = byId[cur].parent || '';
            }
        });
        return nodes;
    }

    function buildTree(nodes) {
        var byId = {};
        var roots = [];
        nodes.forEach(function (n) {
            byId[n.id] = Object.assign({ children: [] }, n);
        });
        nodes.forEach(function (n) {
            var parent = n.parent || '';
            if (parent && byId[parent] && parent !== n.id) {
                byId[parent].children.push(byId[n.id]);
            } else {
                roots.push(byId[n.id]);
            }
        });
        return { byId: byId, roots: roots };
    }

    function layoutOrg(nodes, direction) {
        var tree = buildTree(nodes);
        var positions = {};
        var cursor = 0;
        var horizontal = direction === 'lr' || direction === 'rl';

        function measure(node) {
            if (!node.children.length) {
                node._w = NODE_W;
                return NODE_W;
            }
            var sum = 0;
            node.children.forEach(function (c, i) {
                sum += measure(c);
                if (i < node.children.length - 1) {
                    sum += GAP_X;
                }
            });
            node._w = Math.max(NODE_W, sum);
            return node._w;
        }

        function place(node, depth, left) {
            if (horizontal) {
                var x = depth * (NODE_W + GAP_X);
                var y = left + (node._w - NODE_W) / 2;
                positions[node.id] = { x: x, y: y, depth: depth };
            } else {
                positions[node.id] = {
                    x: left + (node._w - NODE_W) / 2,
                    y: depth * (NODE_H + GAP_Y),
                    depth: depth
                };
            }
            var childLeft = left;
            node.children.forEach(function (c) {
                place(c, depth + 1, childLeft);
                childLeft += c._w + GAP_X;
            });
        }

        tree.roots.forEach(function (r) {
            measure(r);
            place(r, 0, cursor);
            cursor += r._w + GAP_X * 2;
        });

        if (direction === 'rl') {
            var maxX = 0;
            Object.keys(positions).forEach(function (id) {
                maxX = Math.max(maxX, positions[id].x + NODE_W);
            });
            Object.keys(positions).forEach(function (id) {
                positions[id].x = maxX - positions[id].x - NODE_W;
            });
        }

        return positions;
    }

    function layoutProcess(nodes, direction) {
        var tree = buildTree(nodes);
        var positions = {};
        var vertical = direction === 'tb';

        function place(node, column, row) {
            if (vertical) {
                positions[node.id] = {
                    x: row * (NODE_W + GAP_X),
                    y: column * (NODE_H + GAP_Y),
                    depth: column
                };
            } else {
                positions[node.id] = {
                    x: column * (NODE_W + GAP_X),
                    y: row * (NODE_H + GAP_Y),
                    depth: column
                };
            }
            if (!node.children.length) {
                return;
            }
            if (node.children.length === 1) {
                place(node.children[0], column + 1, row);
                return;
            }
            var start = row - (node.children.length - 1) / 2;
            node.children.forEach(function (c, i) {
                place(c, column + 1, start + i);
            });
        }

        tree.roots.forEach(function (r, i) {
            place(r, 0, i * 2);
        });

        var minY = Infinity;
        var minX = Infinity;
        Object.keys(positions).forEach(function (id) {
            minY = Math.min(minY, positions[id].y);
            minX = Math.min(minX, positions[id].x);
        });
        Object.keys(positions).forEach(function (id) {
            positions[id].y -= minY;
            positions[id].x -= minX;
        });

        if (direction === 'rl') {
            var maxX = 0;
            Object.keys(positions).forEach(function (id) {
                maxX = Math.max(maxX, positions[id].x + NODE_W);
            });
            Object.keys(positions).forEach(function (id) {
                positions[id].x = maxX - positions[id].x - NODE_W;
            });
        }

        return positions;
    }

    function layoutFreeform(nodes) {
        var positions = {};
        nodes.forEach(function (n) {
            var px = typeof n.x === 'number' ? n.x : 10;
            var py = typeof n.y === 'number' ? n.y : 10;
            positions[n.id] = {
                x: (Math.max(0, Math.min(100, px)) / 100) * (FREEFORM_W - NODE_W),
                y: (Math.max(0, Math.min(100, py)) / 100) * (FREEFORM_H - NODE_H),
                depth: 0
            };
        });
        return positions;
    }

    function edgeAxis(direction, mode) {
        if (mode === 'freeform') {
            return 'auto';
        }
        if (mode === 'process') {
            return direction === 'tb' ? 'v' : 'h';
        }
        // org
        return (direction === 'lr' || direction === 'rl') ? 'h' : 'v';
    }

    function edgePath(axis, connector, posA, posB, direction) {
        var ax, ay, bx, by, mid;

        if (axis === 'h' || (axis === 'auto' && Math.abs(posB.x - posA.x) >= Math.abs(posB.y - posA.y))) {
            var goRight = direction === 'rl'
                ? posB.x < posA.x
                : posB.x >= posA.x;
            if (goRight) {
                ax = posA.x + NODE_W;
                bx = posB.x;
            } else {
                ax = posA.x;
                bx = posB.x + NODE_W;
            }
            ay = posA.y + NODE_H / 2;
            by = posB.y + NODE_H / 2;
            mid = (ax + bx) / 2;
            if (connector === 'straight') {
                return 'M ' + ax + ' ' + ay + ' L ' + bx + ' ' + by;
            }
            if (connector === 'elbow') {
                return 'M ' + ax + ' ' + ay + ' L ' + mid + ' ' + ay + ' L ' + mid + ' ' + by + ' L ' + bx + ' ' + by;
            }
            return 'M ' + ax + ' ' + ay + ' C ' + mid + ' ' + ay + ', ' + mid + ' ' + by + ', ' + bx + ' ' + by;
        }

        // vertical
        ax = posA.x + NODE_W / 2;
        bx = posB.x + NODE_W / 2;
        if (posB.y >= posA.y) {
            ay = posA.y + NODE_H;
            by = posB.y;
        } else {
            ay = posA.y;
            by = posB.y + NODE_H;
        }
        if (connector === 'straight') {
            return 'M ' + ax + ' ' + ay + ' L ' + bx + ' ' + by;
        }
        if (connector === 'elbow') {
            mid = (ay + by) / 2;
            return 'M ' + ax + ' ' + ay + ' L ' + ax + ' ' + mid + ' L ' + bx + ' ' + mid + ' L ' + bx + ' ' + by;
        }
        return 'M ' + ax + ' ' + ay + ' C ' + ax + ' ' + (ay + (by - ay) * 0.4) + ', ' + bx + ' ' + (by - (by - ay) * 0.4) + ', ' + bx + ' ' + by;
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function escapeXml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&apos;');
    }

    function cssVar(el, name, fallback) {
        if (!el || !window.getComputedStyle) {
            return fallback;
        }
        var v = window.getComputedStyle(el).getPropertyValue(name);
        v = (v || '').trim();
        return v || fallback;
    }

    function loadImageDataUrl(src) {
        return new Promise(function (resolve) {
            if (!src) {
                resolve('');
                return;
            }
            if (src.indexOf('data:') === 0) {
                resolve(src);
                return;
            }
            var img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = function () {
                try {
                    var c = document.createElement('canvas');
                    c.width = img.naturalWidth || img.width || 1;
                    c.height = img.naturalHeight || img.height || 1;
                    c.getContext('2d').drawImage(img, 0, 0);
                    resolve(c.toDataURL('image/png'));
                } catch (e) {
                    resolve(src);
                }
            };
            img.onerror = function () {
                resolve('');
            };
            img.src = src;
        });
    }

    /**
     * Pure SVG snapshot (no foreignObject) so PNG/SVG include nodes + connectors.
     */
    function buildFlowExportSvg(root, wrap, width, height, bg) {
        var pad = 16;
        var w = Math.ceil(width + pad * 2);
        var h = Math.ceil(height + pad * 2);
        var indigo = cssVar(root, '--fc-indigo', '#4338ca');
        var violet = cssVar(root, '--fc-violet', '#7c3aed');
        var line = cssVar(root, '--fc-line', '#e6e2f0');
        var amber = cssVar(root, '--fc-amber', '#fbbf24');
        var amberSoft = cssVar(root, '--fc-amber-soft', '#fef3c7');
        var panel = cssVar(root, '--fc-panel', '#ffffff');
        var ink = cssVar(root, '--fc-ink', '#1e1b2e');
        var muted = cssVar(root, '--fc-muted', '#6b6478');
        var radius = parseFloat(cssVar(root, '--fc-radius', '14')) || 14;
        var avatar = parseFloat(cssVar(root, '--fc-avatar', '30')) || 30;
        var avatarRadius = cssVar(root, '--fc-avatar-radius', '9px');
        var avatarBg = cssVar(root, '--fc-avatar-bg', amberSoft);
        var avatarIcon = cssVar(root, '--fc-avatar-icon', '#92400e');
        var avatarFit = cssVar(root, '--fc-avatar-fit', 'cover');
        var fillBg = bg || '#ffffff';

        var nodeEls = Array.prototype.slice.call(wrap.querySelectorAll('.rawnaq-flow-node'));
        var pathEls = Array.prototype.slice.call(wrap.querySelectorAll('.rawnaq-flow-connectors path'));

        var imageJobs = nodeEls.map(function (el) {
            var img = el.querySelector('.rawnaq-flow-icon-img');
            return loadImageDataUrl(img ? (img.currentSrc || img.src) : '');
        });

        return Promise.all(imageJobs).then(function (imageUrls) {
            var out = [];
            out.push('<?xml version="1.0" encoding="UTF-8"?>');
            out.push(
                '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="'
                + w + '" height="' + h + '" viewBox="0 0 ' + w + ' ' + h + '">'
            );
            out.push('<rect width="100%" height="100%" fill="' + escapeXml(fillBg) + '"/>');
            out.push('<defs>');
            out.push(
                '<linearGradient id="rqFcRoot" x1="0%" y1="0%" x2="100%" y2="100%">'
                + '<stop offset="0%" stop-color="' + escapeXml(indigo) + '"/>'
                + '<stop offset="100%" stop-color="' + escapeXml(violet) + '"/>'
                + '</linearGradient>'
            );
            out.push('</defs>');
            out.push('<g transform="translate(' + pad + ',' + pad + ')">');

            pathEls.forEach(function (path) {
                var d = path.getAttribute('d') || '';
                if (!d) {
                    return;
                }
                var accent = path.classList.contains('accent');
                out.push(
                    '<path d="' + escapeXml(d) + '" fill="none" stroke="'
                    + escapeXml(accent ? amber : line) + '" stroke-width="'
                    + (accent ? '3' : '2.5') + '" stroke-linecap="round" stroke-linejoin="round"/>'
                );
            });

            nodeEls.forEach(function (el, idx) {
                var x = parseFloat(el.style.left);
                var y = parseFloat(el.style.top);
                if (isNaN(x)) {
                    x = el.offsetLeft || 0;
                }
                if (isNaN(y)) {
                    y = el.offsetTop || 0;
                }
                var nw = el.offsetWidth || NODE_W;
                var nh = el.offsetHeight || NODE_H;
                var isRoot = el.classList.contains('is-root');
                var isDecision = el.classList.contains('is-decision');
                var isCircle = el.classList.contains('shape-circle');
                var titleEl = el.querySelector('.rawnaq-flow-title');
                var roleEl = el.querySelector('.rawnaq-flow-role');
                var iconEl = el.querySelector('.rawnaq-flow-icon');
                var title = titleEl ? titleEl.textContent : '';
                var role = roleEl ? roleEl.textContent : '';
                var rx = isCircle ? nw / 2 : radius;
                var fill = isRoot ? 'url(#rqFcRoot)' : (isDecision ? amberSoft : panel);
                var stroke = isRoot ? 'none' : (isDecision ? amber : line);
                var strokeW = isRoot ? 0 : 1.5;
                var textColor = isRoot ? '#ffffff' : ink;
                var roleColor = isRoot ? 'rgba(255,255,255,0.75)' : muted;

                out.push(
                    '<rect x="' + x + '" y="' + y + '" width="' + nw + '" height="' + nh
                    + '" rx="' + rx + '" ry="' + rx + '" fill="' + escapeXml(fill)
                    + '" stroke="' + escapeXml(stroke) + '" stroke-width="' + strokeW + '"/>'
                );

                var ax = x + 14;
                var ay = y + 12;
                var hasImage = !!(imageUrls[idx]);
                var iconText = '';
                if (iconEl && !hasImage) {
                    var fa = iconEl.querySelector('i, .dashicons');
                    if (!fa) {
                        iconText = (iconEl.textContent || '').trim();
                    }
                }

                var avRx = avatarRadius.indexOf('%') !== -1 ? avatar / 2 : (parseFloat(avatarRadius) || 9);
                out.push(
                    '<rect x="' + ax + '" y="' + ay + '" width="' + avatar + '" height="' + avatar
                    + '" rx="' + avRx + '" ry="' + avRx + '" fill="'
                    + escapeXml(isRoot && !hasImage ? 'rgba(255,255,255,0.18)' : avatarBg) + '"/>'
                );

                if (hasImage) {
                    out.push(
                        '<clipPath id="rqAv' + idx + '">'
                        + '<rect x="' + ax + '" y="' + ay + '" width="' + avatar + '" height="' + avatar
                        + '" rx="' + avRx + '" ry="' + avRx + '"/>'
                        + '</clipPath>'
                    );
                    var href = escapeXml(imageUrls[idx]);
                    var preserve = avatarFit === 'contain' ? 'xMidYMid meet' : (avatarFit === 'fill' ? 'none' : 'xMidYMid slice');
                    out.push(
                        '<image x="' + ax + '" y="' + ay + '" width="' + avatar + '" height="' + avatar
                        + '" preserveAspectRatio="' + preserve + '" clip-path="url(#rqAv' + idx + ')" href="'
                        + href + '" xlink:href="' + href + '"/>'
                    );
                } else if (iconText) {
                    out.push(
                        '<text x="' + (ax + avatar / 2) + '" y="' + (ay + avatar / 2 + 4)
                        + '" text-anchor="middle" font-size="12" fill="'
                        + escapeXml(isRoot ? '#ffffff' : avatarIcon) + '">'
                        + escapeXml(iconText) + '</text>'
                    );
                } else {
                    out.push(
                        '<circle cx="' + (ax + avatar / 2) + '" cy="' + (ay + avatar / 2)
                        + '" r="4" fill="' + escapeXml(isRoot ? '#ffffff' : avatarIcon) + '"/>'
                    );
                }

                var textX = ax;
                var textY = ay + avatar + 18;
                out.push(
                    '<text x="' + textX + '" y="' + textY
                    + '" font-family="Segoe UI, Helvetica, Arial, sans-serif" font-size="13.5" font-weight="700" fill="'
                    + escapeXml(textColor) + '">' + escapeXml(title) + '</text>'
                );
                if (role) {
                    out.push(
                        '<text x="' + textX + '" y="' + (textY + 16)
                        + '" font-family="Segoe UI, Helvetica, Arial, sans-serif" font-size="11" fill="'
                        + escapeXml(roleColor) + '">' + escapeXml(role) + '</text>'
                    );
                }
            });

            out.push('</g></svg>');
            return out.join('');
        });
    }

    function renderIcon(node) {
        var image = node.image || node.imageUrl || '';
        if (image) {
            return '<span class="rawnaq-flow-icon has-image"><img class="rawnaq-flow-icon-img" src="'
                + escapeHtml(image) + '" alt="" loading="lazy" decoding="async" /></span>';
        }
        var icon = node.icon || '';
        if (icon.indexOf('dashicons-') === 0) {
            return '<span class="rawnaq-flow-icon"><span class="dashicons ' + escapeHtml(icon) + '" aria-hidden="true"></span></span>';
        }
        if (/\bfa[srb]?\b|\beicon-/.test(icon) || (icon.indexOf(' ') !== -1 && icon.indexOf('<') === -1)) {
            return '<span class="rawnaq-flow-icon"><i class="' + escapeHtml(icon) + '" aria-hidden="true"></i></span>';
        }
        if (icon) {
            return '<span class="rawnaq-flow-icon" aria-hidden="true">' + escapeHtml(icon) + '</span>';
        }
        return '<span class="rawnaq-flow-icon" aria-hidden="true">●</span>';
    }

    function ensureViewport(root) {
        var viewport = root.querySelector('.rawnaq-flow-viewport');
        if (!viewport) {
            viewport = document.createElement('div');
            viewport.className = 'rawnaq-flow-viewport';
            var stage = root.querySelector('.rawnaq-flow-stage');
            if (stage) {
                root.insertBefore(viewport, stage);
                viewport.appendChild(stage);
            } else {
                root.appendChild(viewport);
            }
        }
        return viewport;
    }

    function applyTransform(canvasWrap, scale, panX, panY) {
        canvasWrap.style.transform = 'translate(' + panX + 'px, ' + panY + 'px) scale(' + scale + ')';
        canvasWrap.style.transformOrigin = '0 0';
    }

    function initChart(root, force) {
        if (!root) {
            return;
        }
        if (force || root.classList.contains('fc-bound')) {
            destroyOne(root);
        }
        var cfg = parseConfig(root);
        if (!cfg || !cfg.nodes || !cfg.nodes.length) {
            return;
        }

        root.classList.add('fc-bound');
        var mode = cfg.mode === 'process' ? 'process' : (cfg.mode === 'freeform' ? 'freeform' : 'org');
        var direction = resolveDirection(cfg.direction || (mode === 'process' ? 'lr' : 'tb'));
        var shape = cfg.shape || 'rect';
        if (shape !== 'rect' && shape !== 'circle' && shape !== 'hex') {
            shape = 'rect';
        }
        var connector = cfg.connector || 'curved';
        var zoomEnabled = cfg.zoom !== false && !isMobile();
        var exportEnabled = cfg.export !== false;
        var avatarShape = cfg.avatarShape || 'rounded';
        if (avatarShape !== 'circle' && avatarShape !== 'square' && avatarShape !== 'rounded') {
            avatarShape = 'rounded';
        }
        root.classList.remove('avatar-rounded', 'avatar-circle', 'avatar-square');
        root.classList.add('avatar-' + avatarShape);

        var nodes = cfg.nodes.map(function (n, i) {
            return {
                id: String(n.id || ('n' + i)),
                parent: String(n.parent || ''),
                title: n.title || '',
                role: n.role || '',
                icon: n.icon || '',
                image: n.image || n.imageUrl || '',
                detail: n.detail || '',
                link: n.link || '',
                decision: !!n.decision,
                x: typeof n.x === 'number' ? n.x : parseFloat(n.x) || 10,
                y: typeof n.y === 'number' ? n.y : parseFloat(n.y) || 10,
                shape: n.shape || shape
            };
        });
        nodes = breakCycles(nodes);

        var ids = {};
        nodes.forEach(function (n) { ids[n.id] = true; });
        nodes.forEach(function (n) {
            n.root = !n.parent || !ids[n.parent];
        });

        var positions;
        if (mode === 'freeform') {
            positions = layoutFreeform(nodes);
        } else if (mode === 'process') {
            positions = layoutProcess(nodes, direction);
        } else {
            positions = layoutOrg(nodes, direction);
        }

        var maxX = mode === 'freeform' ? FREEFORM_W : 0;
        var maxY = mode === 'freeform' ? FREEFORM_H : 0;
        Object.keys(positions).forEach(function (id) {
            maxX = Math.max(maxX, positions[id].x + NODE_W);
            maxY = Math.max(maxY, positions[id].y + NODE_H);
        });
        maxX += 24;
        maxY += 24;

        var viewport = ensureViewport(root);
        var stage = root.querySelector('.rawnaq-flow-stage');
        if (!stage) {
            stage = document.createElement('div');
            stage.className = 'rawnaq-flow-stage is-responsive';
            viewport.appendChild(stage);
        } else {
            stage.classList.add('is-responsive');
            stage.innerHTML = '';
            if (stage.parentNode !== viewport) {
                viewport.appendChild(stage);
            }
        }

        var wrap = document.createElement('div');
        wrap.className = 'rawnaq-flow-canvas-wrap';
        wrap.style.width = maxX + 'px';
        wrap.style.height = maxY + 'px';

        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('class', 'rawnaq-flow-connectors path-' + (connector === 'dashed' ? 'dashed' : 'solid'));
        svg.setAttribute('viewBox', '0 0 ' + maxX + ' ' + maxY);
        svg.setAttribute('width', String(maxX));
        svg.setAttribute('height', String(maxY));

        var canvas = document.createElement('div');
        canvas.className = 'rawnaq-flow-canvas';
        canvas.style.width = maxX + 'px';
        canvas.style.height = maxY + 'px';

        var byId = {};
        nodes.forEach(function (n) { byId[n.id] = n; });

        var useLazy = nodes.length >= LAZY_THRESHOLD;
        var axis = edgeAxis(direction, mode);

        nodes.forEach(function (n, idx) {
            var pos = positions[n.id];
            if (!pos) {
                return;
            }
            var el;
            if (n.link) {
                el = document.createElement('a');
                el.href = n.link;
            } else {
                el = document.createElement('button');
                el.type = 'button';
            }
            var nodeShape = n.shape || shape;
            el.className = 'rawnaq-flow-node shape-' + nodeShape
                + (n.root ? ' is-root' : '')
                + (n.decision ? ' is-decision' : '')
                + (n.image ? ' has-image' : '')
                + (useLazy && idx >= 8 ? ' is-lazy' : '');
            el.style.left = pos.x + 'px';
            el.style.top = pos.y + 'px';
            el.style.width = NODE_W + 'px';
            el.setAttribute('data-id', n.id);
            el.setAttribute('data-shape', nodeShape);
            el.innerHTML = renderIcon(n)
                + '<div class="rawnaq-flow-title">' + escapeHtml(n.title) + '</div>'
                + (n.role ? '<div class="rawnaq-flow-role">' + escapeHtml(n.role) + '</div>' : '');
            canvas.appendChild(el);
        });

        nodes.forEach(function (n) {
            if (!n.parent || !byId[n.parent] || !positions[n.id] || !positions[n.parent]) {
                return;
            }
            var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', edgePath(axis, connector, positions[n.parent], positions[n.id], direction));
            if (byId[n.parent].decision || n.decision) {
                path.classList.add('accent');
            }
            svg.appendChild(path);
        });

        var detail = document.createElement('div');
        detail.className = 'rawnaq-flow-detail';
        detail.setAttribute('role', 'dialog');

        var mobile = document.createElement('div');
        mobile.className = 'rawnaq-flow-mobile';
        nodes.forEach(function (n) {
            var depth = positions[n.id] ? positions[n.id].depth || 0 : 0;
            var item = document.createElement(n.link ? 'a' : 'div');
            item.className = 'rawnaq-flow-mobile-item'
                + (n.root ? ' is-root' : '')
                + (depth > 0 ? ' indent-' + Math.min(depth, 3) : '');
            if (n.link) {
                item.href = n.link;
            }
            item.innerHTML = renderIcon(n)
                + '<div><b>' + escapeHtml(n.title) + '</b>'
                + (n.role ? '<span>' + escapeHtml(n.role) + '</span>' : '') + '</div>';
            mobile.appendChild(item);
        });

        wrap.appendChild(svg);
        wrap.appendChild(canvas);
        stage.appendChild(wrap);
        stage.appendChild(mobile);
        stage.appendChild(detail);

        // Zoom / pan
        var scale = 1;
        var panX = 0;
        var panY = 0;
        var dragging = false;
        var lastX = 0;
        var lastY = 0;
        var onWheel = null;
        var onPointerDown = null;
        var onPointerMove = null;
        var onPointerUp = null;

        if (zoomEnabled) {
            var chrome = document.createElement('div');
            chrome.className = 'rawnaq-flow-zoom';
            chrome.innerHTML = '<button type="button" class="fc-zoom-in" aria-label="Zoom in">+</button>'
                + '<button type="button" class="fc-zoom-out" aria-label="Zoom out">−</button>'
                + '<button type="button" class="fc-zoom-reset" aria-label="Reset zoom">⟲</button>';
            root.insertBefore(chrome, viewport);

            function clampScale(s) {
                return Math.max(0.4, Math.min(2.5, s));
            }
            function refreshTransform() {
                applyTransform(wrap, scale, panX, panY);
            }

            chrome.querySelector('.fc-zoom-in').addEventListener('click', function () {
                scale = clampScale(scale + 0.15);
                refreshTransform();
            });
            chrome.querySelector('.fc-zoom-out').addEventListener('click', function () {
                scale = clampScale(scale - 0.15);
                refreshTransform();
            });
            chrome.querySelector('.fc-zoom-reset').addEventListener('click', function () {
                scale = 1;
                panX = 0;
                panY = 0;
                refreshTransform();
            });

            onWheel = function (e) {
                e.preventDefault();
                var delta = e.deltaY > 0 ? -0.08 : 0.08;
                scale = clampScale(scale + delta);
                refreshTransform();
            };
            viewport.addEventListener('wheel', onWheel, { passive: false });

            onPointerDown = function (e) {
                if (e.target.closest && e.target.closest('.rawnaq-flow-node')) {
                    return;
                }
                dragging = true;
                lastX = e.clientX;
                lastY = e.clientY;
                viewport.classList.add('is-panning');
            };
            onPointerMove = function (e) {
                if (!dragging) {
                    return;
                }
                panX += e.clientX - lastX;
                panY += e.clientY - lastY;
                lastX = e.clientX;
                lastY = e.clientY;
                refreshTransform();
            };
            onPointerUp = function () {
                dragging = false;
                viewport.classList.remove('is-panning');
            };
            viewport.addEventListener('pointerdown', onPointerDown);
            document.addEventListener('pointermove', onPointerMove);
            document.addEventListener('pointerup', onPointerUp);
            viewport.classList.add('has-zoom');
        }

        if (exportEnabled && window.rawnaqDiagramExport && rawnaqDiagramExport.attachToolbar) {
            var savedTransform = null;
            rawnaqDiagramExport.attachToolbar(root, {
                filenameBase: 'rawnaq-flow-chart',
                background: '#ffffff',
                varSource: root,
                getTarget: function () {
                    return wrap;
                },
                getSize: function () {
                    return { width: maxX + 32, height: maxY + 32 };
                },
                getSvgMarkup: function (width, height, meta) {
                    return buildFlowExportSvg(root, wrap, maxX, maxY, (meta && meta.background) || '#ffffff');
                },
                prepare: function () {
                    savedTransform = { scale: scale, panX: panX, panY: panY };
                    applyTransform(wrap, 1, 0, 0);
                    wrap.style.width = maxX + 'px';
                    wrap.style.height = maxY + 'px';
                    wrap.querySelectorAll('.rawnaq-flow-node').forEach(function (el) {
                        el.classList.add('show');
                        el.classList.remove('is-lazy');
                        el.style.opacity = '1';
                        el.style.transform = 'none';
                        el.style.visibility = 'visible';
                    });
                    return function () {
                        if (!savedTransform) {
                            return;
                        }
                        applyTransform(wrap, savedTransform.scale, savedTransform.panX, savedTransform.panY);
                        savedTransform = null;
                    };
                },
                getHide: function () {
                    return ['.rawnaq-flow-zoom', '.rawnaq-diagram-export', '.rawnaq-flow-detail', '.rawnaq-flow-mobile'];
                }
            });
        }

        function showDetail(e, node, el) {
            if (!node.detail) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            var rect = el.getBoundingClientRect();
            var stageRect = stage.getBoundingClientRect();
            detail.innerHTML = '<strong>' + escapeHtml(node.title) + '</strong>' + escapeHtml(node.detail);
            detail.style.left = Math.min(rect.left - stageRect.left, stageRect.width - 240) + 'px';
            detail.style.top = (rect.bottom - stageRect.top + 12 + stage.scrollTop) + 'px';
            detail.classList.add('open');
        }

        canvas.querySelectorAll('.rawnaq-flow-node').forEach(function (el) {
            var id = el.getAttribute('data-id');
            var node = byId[id];
            el.addEventListener('click', function (e) {
                if (node.detail) {
                    showDetail(e, node, el);
                }
            });
        });

        var onDocClick = function () {
            detail.classList.remove('open');
        };
        document.addEventListener('click', onDocClick);

        var reduced = prefersReducedMotion();
        var nodeEls = canvas.querySelectorAll('.rawnaq-flow-node');
        var paths = svg.querySelectorAll('path');
        var lazyObs = null;

        function activateNode(el) {
            el.classList.remove('is-lazy');
            el.classList.add('show');
        }

        if (useLazy && 'IntersectionObserver' in window) {
            lazyObs = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        activateNode(entry.target);
                        lazyObs.unobserve(entry.target);
                    }
                });
            }, { root: stage, rootMargin: '80px', threshold: 0.01 });

            nodeEls.forEach(function (el, i) {
                if (i < 8 || reduced) {
                    activateNode(el);
                } else {
                    lazyObs.observe(el);
                }
            });
        } else if (reduced) {
            nodeEls.forEach(function (el) { el.classList.add('show'); });
        } else {
            requestAnimationFrame(function () {
                nodeEls.forEach(function (el, i) {
                    setTimeout(function () { el.classList.add('show'); }, 70 * i);
                });
            });
        }

        if (reduced) {
            paths.forEach(function (p) {
                p.style.setProperty('--len', '0');
                p.classList.add('lit');
            });
        } else {
            setTimeout(function () {
                paths.forEach(function (p, i) {
                    var len = 1;
                    try { len = p.getTotalLength() || 1; } catch (err) { len = 1; }
                    p.style.setProperty('--len', String(len));
                    setTimeout(function () { p.classList.add('lit'); }, 100 * i);
                });
            }, 180);
        }

        instances.push({
            root: root,
            viewport: viewport,
            onDocClick: onDocClick,
            onWheel: onWheel,
            onPointerDown: onPointerDown,
            onPointerMove: onPointerMove,
            onPointerUp: onPointerUp,
            lazyObs: lazyObs,
            observer: null
        });
    }

    function initAll(force) {
        if (force) {
            destroyAll();
            document.querySelectorAll('.rawnaq-flow-chart').forEach(function (el) {
                initChart(el, true);
            });
            return;
        }
        document.querySelectorAll('.rawnaq-flow-chart').forEach(function (el) {
            if (!el.classList.contains('fc-bound')) {
                initChart(el, false);
            }
        });
    }

    function remount(el) {
        if (!el) {
            initAll(true);
            return;
        }
        initChart(el, true);
    }

    function hookElementor() {
        if (elementorHooked || !window.elementorFrontend || !elementorFrontend.hooks) {
            return;
        }
        elementorHooked = true;
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/rawnaq_flow_chart.default',
            function ($scope) {
                var root = $scope && $scope[0]
                    ? $scope[0].querySelector('.rawnaq-flow-chart')
                    : null;
                if (root) {
                    remount(root);
                } else {
                    initAll(true);
                }
            }
        );
    }

    function boot() {
        initAll(true);
        hookElementor();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    if (window.jQuery) {
        jQuery(window).on('elementor/frontend/init', hookElementor);
    }

    window.rawnaqFlowChartBoot = function () { initAll(true); };
    window.rawnaqFlowChartMount = remount;
})();
