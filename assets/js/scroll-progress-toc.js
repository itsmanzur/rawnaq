/**
 * Rawnaq Scroll Progress + Smart TOC
 */
(function () {
    'use strict';

    var instances = [];
    var scrollBound = false;
    var ticking = false;
    var elementorHooked = false;

    function prefersReducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function slugify(text, used) {
        var base = String(text || 'section')
            .toLowerCase()
            .replace(/[^\w\u0980-\u09FF\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-');
        if (!base) {
            base = 'section';
        }
        var id = base;
        var n = 2;
        while (used[id]) {
            id = base + '-' + n;
            n++;
        }
        used[id] = true;
        return id;
    }

    function parseCfg(el) {
        try {
            return JSON.parse(el.getAttribute('data-spt') || '{}');
        } catch (e) {
            return {};
        }
    }

    function getScrollProgress() {
        var doc = document.documentElement;
        var scrollTop = window.pageYOffset || doc.scrollTop || 0;
        var height = Math.max(doc.scrollHeight - window.innerHeight, 1);
        return Math.max(0, Math.min(1, scrollTop / height));
    }

    function updateProgress(inst) {
        var p = getScrollProgress();
        if (inst.barFill) {
            inst.barFill.style.width = (p * 100) + '%';
        }
        if (inst.ringFg) {
            var circ = 126;
            inst.ringFg.style.strokeDashoffset = String(circ * (1 - p));
        }
        if (inst.ringLabel) {
            inst.ringLabel.textContent = Math.round(p * 100) + '%';
        }
        // Hide widgets if page too short
        if (inst.root) {
            var short = document.documentElement.scrollHeight <= window.innerHeight + 40;
            inst.root.classList.toggle('is-hidden', short && !!inst.cfg.hideIfShort);
        }
    }

    function onScroll() {
        if (ticking) {
            return;
        }
        ticking = true;
        window.requestAnimationFrame(function () {
            instances.forEach(updateProgress);
            ticking = false;
        });
    }

    function bindScroll() {
        if (scrollBound) {
            return;
        }
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        scrollBound = true;
    }

    function collectHeadings(cfg) {
        var levels = cfg.levels || ['h2', 'h3'];
        var selector = levels.map(function (l) { return l.toLowerCase(); }).join(',');
        if (!selector) {
            return [];
        }
        var scope = document.querySelector(cfg.scope || 'main, .entry-content, article, .wp-block-post-content, body');
        if (!scope) {
            scope = document.body;
        }
        var nodes = scope.querySelectorAll(selector);
        var used = {};
        var items = [];
        nodes.forEach(function (h) {
            var text = (h.textContent || '').trim();
            if (!text) {
                return;
            }
            if (!h.id) {
                h.id = slugify(text, used);
            } else {
                used[h.id] = true;
            }
            items.push({
                id: h.id,
                text: text,
                level: parseInt(h.tagName.replace('H', ''), 10) || 2,
                el: h
            });
        });
        return items;
    }

    function manualItems(cfg) {
        return (cfg.manual || []).map(function (m, i) {
            return {
                id: m.id || ('spt-m-' + i),
                text: m.title || '',
                level: parseInt(m.level, 10) || 2,
                el: document.getElementById(m.id) || null
            };
        }).filter(function (m) { return m.text; });
    }

    function estimateReadingTime(items) {
        var words = 0;
        items.forEach(function (it) {
            if (!it.el) {
                return;
            }
            var next = it.el.parentElement;
            words += String((next && next.textContent) || it.text).split(/\s+/).length;
        });
        // fallback: whole article
        if (words < 50) {
            var art = document.querySelector('article, .entry-content, .wp-block-post-content');
            if (art) {
                words = String(art.textContent || '').split(/\s+/).filter(Boolean).length;
            }
        }
        var mins = Math.max(1, Math.round(words / 200));
        return mins;
    }

    function buildTocList(tocEl, items, cfg) {
        var list = tocEl.querySelector('.rawnaq-spt-list');
        if (!list) {
            return;
        }
        list.innerHTML = '';
        var offset = parseInt(cfg.scrollOffset, 10) || 80;

        items.forEach(function (it) {
            var li = document.createElement('li');
            var a = document.createElement('a');
            a.href = '#' + it.id;
            a.textContent = it.text;
            a.className = 'lvl-' + it.level;
            if (it.level > 2 && cfg.collapseSubs) {
                a.classList.add('is-child');
            }
            a.addEventListener('click', function (e) {
                var target = document.getElementById(it.id);
                if (!target) {
                    return;
                }
                e.preventDefault();
                var top = target.getBoundingClientRect().top + window.pageYOffset - offset;
                if (prefersReducedMotion() || cfg.smooth === false) {
                    window.scrollTo(0, top);
                } else {
                    window.scrollTo({ top: top, behavior: 'smooth' });
                }
                // close mobile sheet
                tocEl.classList.remove('is-sheet-open');
            });
            li.appendChild(a);
            list.appendChild(li);
            it.link = a;
        });
    }

    function observeActive(items) {
        if (!('IntersectionObserver' in window) || !items.length) {
            return null;
        }
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                var id = entry.target.id;
                items.forEach(function (it) {
                    if (it.link) {
                        it.link.classList.toggle('is-active', it.id === id);
                    }
                });
            });
        }, {
            root: null,
            rootMargin: '-20% 0px -60% 0px',
            threshold: 0
        });
        items.forEach(function (it) {
            if (it.el) {
                observer.observe(it.el);
            }
        });
        return observer;
    }

    function destroyAll() {
        instances.forEach(function (inst) {
            if (inst.observer) {
                inst.observer.disconnect();
            }
            if (inst.bar && inst.bar.parentNode) {
                inst.bar.parentNode.removeChild(inst.bar);
            }
            if (inst.ring && inst.ring.parentNode) {
                inst.ring.parentNode.removeChild(inst.ring);
            }
            if (inst.root) {
                inst.root.classList.remove('spt-bound');
            }
        });
        instances = [];
    }

    function initOne(root) {
        if (!root || root.classList.contains('spt-bound')) {
            return;
        }
        root.classList.add('spt-bound');
        var cfg = parseCfg(root);
        cfg.hideIfShort = cfg.hideIfShort !== false;

        var progress = cfg.progress || 'bar'; // bar | ring | both | none
        var tocPos = cfg.tocPosition || 'inline'; // sticky | floating | inline | none
        var showToc = tocPos !== 'none';

        var inst = {
            root: root,
            cfg: cfg,
            bar: null,
            barFill: null,
            ring: null,
            ringFg: null,
            ringLabel: null,
            observer: null
        };

        // Progress UI (document-level for fixed)
        if (progress === 'bar' || progress === 'both') {
            var bar = document.createElement('div');
            bar.className = 'rawnaq-spt-bar' + (cfg.barPosition === 'bottom' ? ' is-bottom' : '');
            bar.innerHTML = '<span class="rawnaq-spt-bar-fill"></span>';
            document.body.appendChild(bar);
            inst.bar = bar;
            inst.barFill = bar.querySelector('.rawnaq-spt-bar-fill');
        }
        if (progress === 'ring' || progress === 'both') {
            var ring = document.createElement('div');
            ring.className = 'rawnaq-spt-ring';
            ring.innerHTML = '<svg viewBox="0 0 48 48" aria-hidden="true">'
                + '<circle class="rawnaq-spt-ring-bg" cx="24" cy="24" r="20"></circle>'
                + '<circle class="rawnaq-spt-ring-fg" cx="24" cy="24" r="20"></circle>'
                + '</svg>'
                + (cfg.showPercent !== false ? '<span class="rawnaq-spt-ring-label">0%</span>' : '');
            document.body.appendChild(ring);
            inst.ring = ring;
            inst.ringFg = ring.querySelector('.rawnaq-spt-ring-fg');
            inst.ringLabel = ring.querySelector('.rawnaq-spt-ring-label');
        }

        // TOC
        var items = [];
        if (showToc) {
            if (cfg.source === 'manual') {
                items = manualItems(cfg);
            } else {
                items = collectHeadings(cfg);
            }

            var toc = root.querySelector('.rawnaq-spt-toc');
            if (!toc) {
                toc = document.createElement('nav');
                toc.className = 'rawnaq-spt-toc';
                toc.innerHTML = '<p class="rawnaq-spt-reading" hidden></p><h3 class="rawnaq-spt-title"></h3><ul class="rawnaq-spt-list"></ul>';
                root.appendChild(toc);
            }
            toc.classList.add('is-' + tocPos);
            if (cfg.mobileCollapse !== false && (tocPos === 'sticky' || tocPos === 'floating')) {
                toc.classList.add('collapse-mobile');
                root.classList.add('has-mobile-fab');
            }
            if (cfg.collapseSubs) {
                toc.classList.remove('is-expanded');
            } else {
                toc.classList.add('is-expanded');
            }

            var titleEl = toc.querySelector('.rawnaq-spt-title');
            if (titleEl) {
                titleEl.textContent = cfg.tocTitle || 'Contents';
            }

            if (cfg.readingTime) {
                var readEl = toc.querySelector('.rawnaq-spt-reading');
                if (readEl) {
                    var mins = estimateReadingTime(items);
                    readEl.hidden = false;
                    readEl.textContent = mins + ' min read';
                }
            }

            buildTocList(toc, items, cfg);
            inst.observer = observeActive(items);

            var fab = root.querySelector('.rawnaq-spt-fab');
            if (!fab && root.classList.contains('has-mobile-fab')) {
                fab = document.createElement('button');
                fab.type = 'button';
                fab.className = 'rawnaq-spt-fab';
                fab.setAttribute('aria-label', 'Table of contents');
                fab.textContent = '≡';
                root.appendChild(fab);
            }
            if (fab) {
                fab.addEventListener('click', function () {
                    toc.classList.toggle('is-sheet-open');
                });
            }
        }

        instances.push(inst);
        updateProgress(inst);
        bindScroll();
    }

    function initAll() {
        destroyAll();
        document.querySelectorAll('.rawnaq-spt').forEach(initOne);
    }

    function hookElementor() {
        if (elementorHooked || !window.elementorFrontend || !elementorFrontend.hooks) {
            return;
        }
        elementorHooked = true;
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/rawnaq_scroll_progress_toc.default',
            function () { initAll(); }
        );
    }

    function boot() {
        initAll();
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
})();
