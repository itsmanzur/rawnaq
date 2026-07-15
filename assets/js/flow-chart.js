/**
 * Rawnaq Flow Chart — org/process layout, SVG connectors, detail popover, mobile list
 */
(function () {
    'use strict';

    var instances = [];
    var elementorHooked = false;
    var NODE_W = 150;
    var NODE_H = 72;
    var GAP_X = 40;
    var GAP_Y = 56;

    function prefersReducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
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

    function destroyInstance(inst) {
        if (inst.onDocClick) {
            document.removeEventListener('click', inst.onDocClick);
        }
        if (inst.observer) {
            inst.observer.disconnect();
        }
        if (inst.root) {
            inst.root.classList.remove('fc-bound');
            var stage = inst.root.querySelector('.rawnaq-flow-stage');
            if (stage) {
                stage.innerHTML = '';
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

    function layoutOrg(nodes) {
        var tree = buildTree(nodes);
        var positions = {};
        var cursorX = 0;

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
            var y = depth * (NODE_H + GAP_Y);
            var x = left + (node._w - NODE_W) / 2;
            positions[node.id] = { x: x, y: y, depth: depth };
            var childLeft = left;
            node.children.forEach(function (c) {
                place(c, depth + 1, childLeft);
                childLeft += c._w + GAP_X;
            });
        }

        tree.roots.forEach(function (r) {
            measure(r);
            place(r, 0, cursorX);
            cursorX += r._w + GAP_X * 2;
        });

        return positions;
    }

    function layoutProcess(nodes) {
        var tree = buildTree(nodes);
        var positions = {};
        var col = 0;

        function place(node, column, row) {
            positions[node.id] = {
                x: column * (NODE_W + GAP_X),
                y: row * (NODE_H + GAP_Y),
                depth: column
            };
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
            col += 1;
        });

        // Normalize y so min is 0
        var minY = Infinity;
        Object.keys(positions).forEach(function (id) {
            minY = Math.min(minY, positions[id].y);
        });
        Object.keys(positions).forEach(function (id) {
            positions[id].y -= minY;
        });

        return positions;
    }

    function edgePath(mode, connector, a, b, posA, posB) {
        var ax = posA.x + NODE_W / 2;
        var ay = posA.y + NODE_H;
        var bx = posB.x + NODE_W / 2;
        var by = posB.y;
        var ayMid = posA.y + NODE_H / 2;
        var byMid = posB.y + NODE_H / 2;

        if (mode === 'process') {
            ax = posA.x + NODE_W;
            ay = ayMid;
            bx = posB.x;
            by = byMid;
            var midX = (ax + bx) / 2;
            if (connector === 'straight') {
                return 'M ' + ax + ' ' + ay + ' L ' + bx + ' ' + by;
            }
            if (connector === 'elbow') {
                return 'M ' + ax + ' ' + ay + ' L ' + midX + ' ' + ay + ' L ' + midX + ' ' + by + ' L ' + bx + ' ' + by;
            }
            return 'M ' + ax + ' ' + ay + ' C ' + midX + ' ' + ay + ', ' + midX + ' ' + by + ', ' + bx + ' ' + by;
        }

        // org vertical
        if (connector === 'straight') {
            return 'M ' + ax + ' ' + ay + ' L ' + bx + ' ' + by;
        }
        if (connector === 'elbow') {
            var midY = (ay + by) / 2;
            return 'M ' + ax + ' ' + ay + ' L ' + ax + ' ' + midY + ' L ' + bx + ' ' + midY + ' L ' + bx + ' ' + by;
        }
        return 'M ' + ax + ' ' + ay + ' C ' + ax + ' ' + (ay + 30) + ', ' + bx + ' ' + (by - 30) + ', ' + bx + ' ' + by;
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderIcon(node) {
        var icon = node.icon || '';
        if (icon.indexOf('dashicons-') === 0) {
            return '<span class="rawnaq-flow-icon"><span class="dashicons ' + escapeHtml(icon) + '" aria-hidden="true"></span></span>';
        }
        if (icon) {
            return '<span class="rawnaq-flow-icon" aria-hidden="true">' + escapeHtml(icon) + '</span>';
        }
        return '<span class="rawnaq-flow-icon" aria-hidden="true">●</span>';
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
        var mode = cfg.mode === 'process' ? 'process' : 'org';
        var connector = cfg.connector || 'curved';
        var nodes = cfg.nodes.map(function (n, i) {
            return {
                id: String(n.id || ('n' + i)),
                parent: String(n.parent || ''),
                title: n.title || '',
                role: n.role || '',
                icon: n.icon || '',
                detail: n.detail || '',
                link: n.link || '',
                decision: !!n.decision,
                root: !!n.root || (!n.parent)
            };
        });

        // Flag actual roots (no parent or parent missing)
        var ids = {};
        nodes.forEach(function (n) { ids[n.id] = true; });
        nodes.forEach(function (n) {
            n.root = !n.parent || !ids[n.parent];
        });

        var positions = mode === 'process' ? layoutProcess(nodes) : layoutOrg(nodes);
        var maxX = 0;
        var maxY = 0;
        Object.keys(positions).forEach(function (id) {
            maxX = Math.max(maxX, positions[id].x + NODE_W);
            maxY = Math.max(maxY, positions[id].y + NODE_H);
        });
        maxX += 24;
        maxY += 24;

        var stage = root.querySelector('.rawnaq-flow-stage');
        if (!stage) {
            stage = document.createElement('div');
            stage.className = 'rawnaq-flow-stage is-responsive';
            root.appendChild(stage);
        } else {
            stage.classList.add('is-responsive');
            stage.innerHTML = '';
        }

        var wrap = document.createElement('div');
        wrap.className = 'rawnaq-flow-canvas-wrap';
        wrap.style.width = maxX + 'px';
        wrap.style.height = maxY + 'px';

        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('class', 'rawnaq-flow-connectors path-' + (connector === 'dashed' ? 'dashed' : 'solid'));
        svg.setAttribute('viewBox', '0 0 ' + maxX + ' ' + maxY);

        var canvas = document.createElement('div');
        canvas.className = 'rawnaq-flow-canvas';
        canvas.style.width = maxX + 'px';
        canvas.style.height = maxY + 'px';

        var byId = {};
        nodes.forEach(function (n) { byId[n.id] = n; });

        nodes.forEach(function (n) {
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
            el.className = 'rawnaq-flow-node'
                + (n.root ? ' is-root' : '')
                + (n.decision ? ' is-decision' : '');
            el.style.left = pos.x + 'px';
            el.style.top = pos.y + 'px';
            el.style.width = NODE_W + 'px';
            el.setAttribute('data-id', n.id);
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
            path.setAttribute('d', edgePath(mode, connector, byId[n.parent], n, positions[n.parent], positions[n.id]));
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
            item.innerHTML = '<div class="dot"></div><div><b>' + escapeHtml(n.title) + '</b>'
                + (n.role ? '<span>' + escapeHtml(n.role) + '</span>' : '') + '</div>';
            mobile.appendChild(item);
        });

        wrap.appendChild(svg);
        wrap.appendChild(canvas);
        stage.appendChild(wrap);
        stage.appendChild(mobile);
        stage.appendChild(detail);

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

        if (reduced) {
            nodeEls.forEach(function (el) { el.classList.add('show'); });
            paths.forEach(function (p) {
                p.style.setProperty('--len', '0');
                p.classList.add('lit');
            });
        } else {
            requestAnimationFrame(function () {
                nodeEls.forEach(function (el, i) {
                    setTimeout(function () { el.classList.add('show'); }, 70 * i);
                });
                setTimeout(function () {
                    paths.forEach(function (p, i) {
                        var len = 1;
                        try { len = p.getTotalLength() || 1; } catch (err) { len = 1; }
                        p.style.setProperty('--len', String(len));
                        setTimeout(function () { p.classList.add('lit'); }, 100 * i);
                    });
                }, 180);
            });
        }

        instances.push({
            root: root,
            onDocClick: onDocClick,
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

    // Public API for Gutenberg / Elementor live preview
    window.rawnaqFlowChartBoot = function () { initAll(true); };
    window.rawnaqFlowChartMount = remount;
})();
