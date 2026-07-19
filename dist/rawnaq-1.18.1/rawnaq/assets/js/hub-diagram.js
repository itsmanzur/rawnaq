/**
 * Shared Hub Diagram Logic - ULTRA SPEED EDITION
 * Version: 1.0.0
 * Includes: 360° Radial Layout, Glow Flow Particles, Responsive Mobile Timeline.
 */
(function() {
    'use strict';

    window.HubDiagram = {
        _map: new Map(),
        init: function(host) {
            if (!host) return;
            if (this._map.has(host)) {
                this._map.get(host).destroy();
            }
            var inst = new HubDgmInstance(host);
            this._map.set(host, inst);
            return inst;
        }
    };

    function HubDgmInstance(host) {
        this.host     = host;
        this.activeId = null;
        this.ro       = null;
        this._readCfg();
        this._build();
        this._bindResize();
    }

    HubDgmInstance.prototype = {
        destroy: function() {
            if (this.ro) this.ro.disconnect();
            while (this.host.firstChild) {
                this.host.removeChild(this.host.firstChild);
            }
        },
        _readCfg: function() {
            this.cfg = this.cfg || {};
            try {
                var raw = this.host.getAttribute('data-hub') || '{}';
                // Decode HTML entities Elementor may leave in the attribute
                if (raw.indexOf('&') !== -1) {
                    var ta = document.createElement('textarea');
                    ta.innerHTML = raw;
                    raw = ta.value;
                }
                this.cfg = JSON.parse(raw);

                // One-Click JSON Override integration
                if (this.cfg.importJson) {
                    try {
                        var parsed = JSON.parse(this.cfg.importJson);
                        if (Array.isArray(parsed)) {
                            var mid = Math.ceil(parsed.length / 2);
                            this.cfg.top = parsed.slice(0, mid);
                            this.cfg.bottom = parsed.slice(mid);
                        }
                    } catch (ie) {
                        // Keep repeater nodes if import JSON is invalid
                    }
                }
            } catch (e) {
                this.cfg = this.cfg || {};
            }
        },
        _build: function() {
            var h = this.host;
            h.innerHTML = '';
            h.style.position = 'relative';

            this.svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            Object.assign(this.svg.style, {
                position: 'absolute',
                inset: '0',
                width: '100%',
                height: '100%',
                pointerEvents: 'none',
                zIndex: '1'
            });
            h.appendChild(this.svg);

            this.centerEl = document.createElement('div');
            this.centerEl.className = 'hd-center';
            this.centerEl.innerHTML =
                '<div class="hd-center-ring"></div>' +
                '<div class="hd-center-inner">' +
                    '<div class="hd-center-title"></div>' +
                    '<div class="hd-center-sub"></div>' +
                '</div>';
            h.appendChild(this.centerEl);

            this.nodesEl = document.createElement('div');
            this.nodesEl.className = 'hd-nodes';
            this.nodesEl.style.cssText = 'position:absolute;inset:0;';
            h.appendChild(this.nodesEl);

            this.render(true);
            this._attachExport();
        },
        _attachExport: function() {
            if (this.cfg && this.cfg.export === false) {
                return;
            }
            if (!window.rawnaqDiagramExport || !rawnaqDiagramExport.attachToolbar) {
                return;
            }
            var host = this.host;
            rawnaqDiagramExport.attachToolbar(host, {
                filenameBase: 'rawnaq-hub-diagram',
                background: '#ffffff',
                getTarget: function () { return host; },
                getHide: function () { return ['.rawnaq-diagram-export']; }
            });
        },
        _isMobile: function(width) {
            return width < 768;
        },
        _isTablet: function(width) {
            return width >= 768 && width < 1024;
        },
        _paintCenterRing: function(cfg, D) {
            var s1 = cfg.seg1Color || '#E8793A';
            var s2 = cfg.seg2Color || '#D4A92A';
            var s3 = cfg.seg3Color || '#26B8B8';
            var s4 = 'rgba(210,210,210,.35)';
            var cr = this.centerEl.querySelector('.hd-center-ring');
            if (cfg.centerStyle === 'solid') {
                cr.style.background = s1;
            } else {
                cr.style.background = 'conic-gradient(' + s1 + ' 0deg 95deg, ' + s2 + ' 95deg 185deg, ' + s3 + ' 185deg 255deg, ' + s4 + ' 255deg 360deg)';
            }
            var ci = this.centerEl.querySelector('.hd-center-inner');
            ci.style.inset = (D * 0.047) + 'px';
            var ctEl = this.centerEl.querySelector('.hd-center-title');
            ctEl.textContent = cfg.centerTitle || '';
            ctEl.style.fontSize = Math.max(11, D * 0.09) + 'px';
            var csEl = this.centerEl.querySelector('.hd-center-sub');
            csEl.textContent = '';
            var subParts = String(cfg.centerSubtitle || '').split('\n');
            subParts.forEach(function(part, i) {
                if (i > 0) {
                    csEl.appendChild(document.createElement('br'));
                }
                csEl.appendChild(document.createTextNode(part));
            });
            csEl.style.fontSize = Math.max(9, D * 0.075) + 'px';
        },
        _makeCardEl: function(node, cardShape, barOnTop, animate, delay, animCls) {
            var div = document.createElement(node.link ? 'a' : 'div');
            div.className = 'hd-card shape-' + cardShape;
            if (node.link) {
                div.setAttribute('href', node.link);
                if (node.target) div.setAttribute('target', node.target);
            }
            if (node.cardBg) div.style.backgroundColor = node.cardBg;

            if (animate) {
                div.style.animation = 'none';
                void div.offsetWidth;
                div.style.animation = (animCls || 'hd-fadeUp') + ' .5s ease ' + (delay || 0) + 's both';
            }
            if (this.activeId) {
                if (node.id === this.activeId) div.classList.add('hd-highlighted');
                else div.classList.add('hd-dimmed');
            }

            var inner = document.createElement('div');
            inner.className = 'hd-card-inner';

            var bar = document.createElement('div');
            bar.className = 'hd-card-bar';
            bar.style.background = node.color || '#E8793A';
            if (barOnTop) inner.appendChild(bar);

            if (node.icon) {
                var iconSpan = document.createElement('span');
                var icon = String(node.icon);
                if (icon.indexOf('dashicons-') === 0) {
                    iconSpan.className = 'hd-card-icon dashicons ' + icon;
                } else if (/\bfa[srb]?\b|\beicon-/.test(icon) || icon.indexOf(' ') !== -1) {
                    var iEl = document.createElement('i');
                    iEl.className = icon;
                    iEl.setAttribute('aria-hidden', 'true');
                    iconSpan.className = 'hd-card-icon';
                    iconSpan.appendChild(iEl);
                } else {
                    iconSpan.className = 'hd-card-icon';
                    iconSpan.textContent = icon;
                }
                if (node.cardColor) iconSpan.style.color = node.cardColor;
                inner.appendChild(iconSpan);
            }

            var lbl = document.createElement('div');
            lbl.className = 'hd-card-label';
            lbl.textContent = node.label || '';
            if (node.cardColor) lbl.style.color = node.cardColor;
            inner.appendChild(lbl);
            if (!barOnTop) inner.appendChild(bar);

            div.appendChild(inner);

            if (!node.link) {
                var self = this;
                div.addEventListener('click', function(e) {
                    e.stopPropagation();
                    self.activeId = self.activeId === node.id ? null : node.id;
                    self.render(false);
                });
            }
            return div;
        },
        _renderMobile: function(animate) {
            var cfg = this.cfg || {};
            var cardShape = cfg.cardShape || 'rect';
            var top = cfg.top || [];
            var bottom = cfg.bottom || [];
            var all = top.concat(bottom);
            var D = 140;

            this.host.classList.add('hd-mobile');
            this.host.classList.remove('hd-tablet');
            this.host.style.height = 'auto';
            this.host.style.minHeight = '';

            this.svg.innerHTML = '';
            this.svg.style.display = 'none';

            this.nodesEl.style.cssText = '';
            this.nodesEl.className = 'hd-nodes';
            this.nodesEl.innerHTML = '';

            Object.assign(this.centerEl.style, {
                position: '',
                left: '',
                top: '',
                width: D + 'px',
                height: D + 'px',
                transform: '',
                borderRadius: '50%',
                zIndex: '10',
                margin: '0 auto 8px'
            });
            this._paintCenterRing(cfg, D);

            if (animate) {
                this.centerEl.style.animation = 'none';
                void this.centerEl.offsetWidth;
                this.centerEl.style.animation = 'hd-mobileIn .5s ease both';
            }

            // Vertical timeline rail
            var rail = document.createElement('div');
            rail.className = 'hd-mobile-rail';
            this.nodesEl.appendChild(rail);

            var self = this;
            all.forEach(function(nd, i) {
                var node = Object.assign({}, nd, { id: nd.id || ('m' + i) });
                var wrap = document.createElement('div');
                wrap.className = 'hd-mobile-item';

                var bullet = document.createElement('span');
                bullet.className = 'hd-mobile-bullet';
                bullet.style.background = node.color || '#E8793A';
                wrap.appendChild(bullet);

                var card = self._makeCardEl(node, cardShape, false, animate, i * 0.06, 'hd-fadeUp');
                card.style.cssText = '';
                wrap.appendChild(card);
                self.nodesEl.appendChild(wrap);
            });
        },
        render: function(animate) {
            this._readCfg();
            var W = this.host.clientWidth;
            // Width-only gate so mobile (height:auto) can still init
            if (W < 10) {
                var selfEarly = this;
                if (!this._retryPending) {
                    this._retryPending = true;
                    requestAnimationFrame(function() {
                        selfEarly._retryPending = false;
                        selfEarly.render(animate);
                    });
                }
                return;
            }

            if (this._isMobile(W)) {
                this._renderMobile(animate);
                return;
            }

            this.host.classList.remove('hd-mobile');
            var isTablet = this._isTablet(W);
            this.host.classList.toggle('hd-tablet', isTablet);

            // Restore desktop chrome if coming back from mobile
            this.svg.style.display = '';
            this.nodesEl.style.cssText = 'position:absolute;inset:0;';
            this.nodesEl.className = 'hd-nodes';
            if (this.host.style.height === 'auto') {
                this.host.style.height = '';
            }

            var H = this.host.clientHeight;
            if (H < 10) {
                var selfH = this;
                if (!this._retryPending) {
                    this._retryPending = true;
                    requestAnimationFrame(function() {
                        selfH._retryPending = false;
                        selfH.render(animate);
                    });
                }
                return;
            }

            var cfg = this.cfg;
            var CX  = W / 2, CY = H / 2;
            var R   = Math.min(W, H) * (isTablet ? 0.12 : 0.145);
            var D   = R * 2;
            var lineColor = cfg.lineColor || '#c2c2c2';

            /* ── center ring style ── */
            var ce = this.centerEl;
            Object.assign(ce.style, {
                position: 'absolute',
                borderRadius: '50%',
                left: CX + 'px',
                top: CY + 'px',
                width: D + 'px',
                height: D + 'px',
                transform: 'translate(-50%,-50%)',
                zIndex: '10',
                margin: ''
            });
            this._paintCenterRing(cfg, D);

            if (animate) {
                ce.style.animation = 'none';
                void ce.offsetWidth;
                ce.style.animation = 'hd-scaleIn .65s cubic-bezier(.34,1.56,.64,1) .3s both';
            }

            var cardShape = cfg.cardShape || 'rect';
            var lineStyle = cfg.lineStyle || 'solid';
            var layoutFlow = cfg.layoutFlow || 'horizontal';
            var glowLines = cfg.glowLines || 'no';

            var maxN  = Math.max((cfg.top || []).length, (cfg.bottom || []).length, 1);
            var cardW = Math.max(isTablet ? 78 : 90, Math.min(isTablet ? 120 : 152, (W - 32) / maxN - 10));
            var cardH = isTablet ? 70 : 80;
            var PAD_Y = isTablet ? 12 : 18;
            var GAP = isTablet ? 8 : 10;

            var self = this;
            function distHorizontal(nodes, side) {
                var n = nodes.length; if (!n) return [];
                var total = n * cardW + (n - 1) * GAP;
                var sx = (W - total) / 2;
                var y = side === 'top' ? PAD_Y : H - cardH - PAD_Y;
                return nodes.map(function(nd, i) {
                    var x = sx + i * (cardW + GAP);
                    return Object.assign({}, nd, {
                        x: x, y: y, w: cardW, h: cardH,
                        cx: x + cardW / 2, cy: y + cardH / 2,
                        side: side, rowIndex: i, rowCount: n
                    });
                });
            }

            function distVertical(nodes, side) {
                var n = nodes.length; if (!n) return [];
                var total = n * cardH + (n - 1) * GAP;
                var sy = (H - total) / 2;
                var x = side === 'left' ? PAD_Y + 10 : W - cardW - PAD_Y - 10;
                return nodes.map(function(nd, i) {
                    var y = sy + i * (cardH + GAP);
                    return Object.assign({}, nd, {
                        x: x, y: y, w: cardW, h: cardH,
                        cx: x + cardW / 2, cy: y + cardH / 2,
                        side: side, rowIndex: i, rowCount: n
                    });
                });
            }

            var topNodesList = cfg.top || [];
            var botNodesList = cfg.bottom || [];
            var allNodesRaw  = topNodesList.concat(botNodesList);

            var topN = [], botN = [], all = [];

            if (layoutFlow === 'radial') {
                var total = allNodesRaw.length;
                all = allNodesRaw.map(function(nd, i) {
                    var angle = (i * 2 * Math.PI) / total;
                    var rx = R * 1.95;
                    var ry = R * 1.95;
                    if (W > H) rx = rx * (W / H) * 0.72;
                    else ry = ry * (H / W) * 0.72;
                    var x = CX + Math.cos(angle) * rx - cardW / 2;
                    var y = CY + Math.sin(angle) * ry - cardH / 2;
                    return Object.assign({}, nd, { x: x, y: y, w: cardW, h: cardH, cx: x + cardW / 2, cy: y + cardH / 2 });
                });
            } else if (layoutFlow === 'vertical') {
                topN = distVertical(topNodesList, 'left');
                botN = distVertical(botNodesList, 'right');
                all = topN.concat(botN);
            } else {
                topN = distHorizontal(topNodesList, 'top');
                botN = distHorizontal(botNodesList, 'bottom');
                all = topN.concat(botN);
            }

            this.nodesEl.innerHTML = '';
            var activeId = this.activeId;

            all.forEach(function(node, i) {
                var barOnTop = (layoutFlow === 'horizontal' && node.side === 'bottom')
                    || (layoutFlow === 'vertical' && node.side === 'right');
                var animCls = 'hd-fadeUp';
                if (layoutFlow === 'radial') {
                    animCls = node.cy < CY ? 'hd-fadeDown' : 'hd-fadeUp';
                } else {
                    animCls = (i < topN.length) ? 'hd-fadeDown' : 'hd-fadeUp';
                }
                var div = self._makeCardEl(node, cardShape, barOnTop, animate, i * 0.07 + 0.08, animCls);
                Object.assign(div.style, {
                    left: node.x + 'px',
                    top: node.y + 'px',
                    width: node.w + 'px',
                    height: node.h + 'px'
                });
                var lbl = div.querySelector('.hd-card-label');
                if (lbl) lbl.style.fontSize = Math.max(10, node.w * 0.086) + 'px';
                self.nodesEl.appendChild(div);
            });

            this._drawLines(all, CX, CY, R, W, H, lineColor, lineStyle, glowLines, layoutFlow);
        },
        _cardEdge: function(cx, cy, cw, ch, tx, ty) {
            var dx = tx - cx, dy = ty - cy, hw = cw / 2, hh = ch / 2, t = Infinity;
            if (Math.abs(dx) > 0.001) {
                var a = hw / dx, b = -hw / dx;
                if (a > 0.001) t = Math.min(t, a);
                if (b > 0.001) t = Math.min(t, b);
            }
            if (Math.abs(dy) > 0.001) {
                var a = hh / dy, b = -hh / dy;
                if (a > 0.001) t = Math.min(t, a);
                if (b > 0.001) t = Math.min(t, b);
            }
            return { x: cx + t * dx, y: cy + t * dy };
        },
        _circleEdge: function(CX, CY, R, fx, fy) {
            var dx = fx - CX, dy = fy - CY, d = Math.hypot(dx, dy) || 1;
            return { x: CX + (dx / d) * R, y: CY + (dy / d) * R };
        },
        _circleAtAngle: function(CX, CY, R, angle) {
            return { x: CX + Math.cos(angle) * R, y: CY + Math.sin(angle) * R };
        },
        _mkSvg: function(tag, attrs) {
            var el = document.createElementNS('http://www.w3.org/2000/svg', tag);
            Object.keys(attrs).forEach(function(k) { el.setAttribute(k, attrs[k]); });
            return el;
        },
        /**
         * Orthogonal elbow path for outer cards (matches Canva reference).
         * Inner cards keep a straight hub-facing link.
         */
        _pathForNode: function(node, CX, CY, R, layoutFlow) {
            var isOuter = node.rowCount >= 3
                && (node.rowIndex === 0 || node.rowIndex === node.rowCount - 1);
            var isLeft = node.rowIndex === 0;
            var pts = [];
            var cardPt, hubPt;

            if (layoutFlow === 'horizontal' && isOuter) {
                // Dock near 9 o'clock / 3 o'clock, nudged by row (Canva style)
                var nudge = node.side === 'top' ? -0.28 : 0.28;
                var angle = isLeft ? (Math.PI + nudge) : (0 - nudge);
                hubPt = this._circleAtAngle(CX, CY, R, angle);

                // Outer cards: enter from the outer side edge (left / right middle)
                cardPt = isLeft
                    ? { x: node.x, y: node.cy }
                    : { x: node.x + node.w, y: node.cy };

                var channelX = isLeft
                    ? Math.min(cardPt.x - 20, hubPt.x - 32)
                    : Math.max(cardPt.x + 20, hubPt.x + 32);

                // Orthogonal elbow: side → out → down/up to hub → into hub
                pts = [
                    cardPt,
                    { x: channelX, y: cardPt.y },
                    { x: channelX, y: hubPt.y },
                    hubPt
                ];
            } else if (layoutFlow === 'vertical' && isOuter) {
                var isTopOuter = node.rowIndex === 0;
                var vAngle = isTopOuter
                    ? (node.side === 'left' ? -Math.PI / 2 - 0.25 : -Math.PI / 2 + 0.25)
                    : (node.side === 'left' ? Math.PI / 2 + 0.25 : Math.PI / 2 - 0.25);
                hubPt = this._circleAtAngle(CX, CY, R, vAngle);

                cardPt = isTopOuter
                    ? { x: node.cx, y: node.y }
                    : { x: node.cx, y: node.y + node.h };

                var channelY = isTopOuter
                    ? Math.min(cardPt.y - 20, hubPt.y - 32)
                    : Math.max(cardPt.y + 20, hubPt.y + 32);

                pts = [
                    cardPt,
                    { x: cardPt.x, y: channelY },
                    { x: hubPt.x, y: channelY },
                    hubPt
                ];
            } else if (layoutFlow === 'horizontal') {
                // Inner cards: straight vertical to facing edge
                cardPt = node.side === 'top'
                    ? { x: node.cx, y: node.y + node.h }
                    : { x: node.cx, y: node.y };
                hubPt = this._circleEdge(CX, CY, R, cardPt.x, cardPt.y);
                pts = [cardPt, hubPt];
            } else if (layoutFlow === 'vertical') {
                cardPt = node.side === 'left'
                    ? { x: node.x + node.w, y: node.cy }
                    : { x: node.x, y: node.cy };
                hubPt = this._circleEdge(CX, CY, R, cardPt.x, cardPt.y);
                pts = [cardPt, hubPt];
            } else {
                // Radial: straight spoke
                cardPt = this._cardEdge(node.cx, node.cy, node.w, node.h, CX, CY);
                hubPt = this._circleEdge(CX, CY, R, node.cx, node.cy);
                pts = [cardPt, hubPt];
            }

            return { pts: pts, cardPt: pts[0], hubPt: pts[pts.length - 1] };
        },
        _drawLines: function(all, CX, CY, R, W, H, lineColor, lineStyle, glowLines, layoutFlow) {
            var svg = this.svg;
            svg.innerHTML = '';
            svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
            var self = this;
            var dash = '';
            if (lineStyle === 'dashed') dash = '8,6';
            else if (lineStyle === 'dotted') dash = '3,4';

            all.forEach(function(node) {
                var path = self._pathForNode(node, CX, CY, R, layoutFlow || 'horizontal');
                var pts = path.pts;
                var d = pts.map(function(p, i) {
                    return (i === 0 ? 'M' : 'L') + p.x.toFixed(1) + ' ' + p.y.toFixed(1);
                }).join(' ');

                var pathAttrs = {
                    d: d,
                    fill: 'none',
                    stroke: lineColor,
                    'stroke-width': 1.8,
                    'stroke-linecap': 'round',
                    'stroke-linejoin': 'round'
                };
                if (dash) pathAttrs['stroke-dasharray'] = dash;
                svg.appendChild(self._mkSvg('path', pathAttrs));

                if (glowLines === 'yes') {
                    var glowAttrs = {
                        d: d,
                        fill: 'none',
                        stroke: node.color || '#6366f1',
                        'stroke-width': 2.2,
                        'stroke-linecap': 'round',
                        'stroke-linejoin': 'round',
                        class: 'hd-glow-line'
                    };
                    svg.appendChild(self._mkSvg('path', glowAttrs));
                }

                svg.appendChild(self._mkSvg('circle', {
                    cx: path.cardPt.x, cy: path.cardPt.y, r: 4, fill: '#555'
                }));
                svg.appendChild(self._mkSvg('circle', {
                    cx: path.hubPt.x, cy: path.hubPt.y, r: 4, fill: '#555'
                }));
            });
        },
        _bindResize: function() {
            var self = this;
            if (window.ResizeObserver) {
                this.ro = new ResizeObserver(function() { self.render(false); });
                this.ro.observe(this.host);
            }
        }
    };

    function initAllHubs() {
        document.querySelectorAll('.hub-diagram-host').forEach(function(host) {
            window.HubDiagram.init(host);
        });
    }

    function onElementorReady($scope) {
        var host = $scope[0].querySelector('.hub-diagram-host');
        if (host) window.HubDiagram.init(host);
    }

    function bindElementorHook() {
        if (bindElementorHook._done) return true;
        if (!window.elementorFrontend || !elementorFrontend.hooks) return false;
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/rawnaq_hub_diagram.default',
            onElementorReady
        );
        bindElementorHook._done = true;
        return true;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllHubs);
    } else {
        initAllHubs();
    }

    // Elementor editor/frontend: hook must register on elementor/frontend/init
    // (elementorFrontend is usually not ready at DOMContentLoaded).
    if (!bindElementorHook()) {
        window.addEventListener('elementor/frontend/init', bindElementorHook);
        if (window.jQuery) {
            jQuery(window).on('elementor/frontend/init', bindElementorHook);
        }
    }

})();
