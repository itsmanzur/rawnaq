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
        
        // Live reading time updates
        if (inst.cfg.readingTime && inst.totalMins) {
            var readEl = inst.root.querySelector('.rawnaq-spt-reading');
            if (readEl) {
                var minsRemaining = Math.max(1, Math.round(inst.totalMins * (1 - p)));
                if (p >= 0.96) {
                    readEl.textContent = 'Finished reading 🎉';
                } else {
                    readEl.textContent = minsRemaining + ' min left';
                }
            }
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
        var currentParent = null;
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
            var lvl = parseInt(h.tagName.replace('H', ''), 10) || 2;
            if (lvl === 2) {
                currentParent = h.id;
            }
            items.push({
                id: h.id,
                text: text,
                level: lvl,
                el: h,
                parentH2: lvl > 2 ? currentParent : null
            });
        });
        return items;
    }

    function manualItems(cfg) {
        var currentParent = null;
        return (cfg.manual || []).map(function (m, i) {
            var lvl = parseInt(m.level, 10) || 2;
            var id = m.id || ('spt-m-' + i);
            if (lvl === 2) {
                currentParent = id;
            }
            return {
                id: id,
                text: m.title || '',
                level: lvl,
                el: document.getElementById(m.id) || null,
                parentH2: lvl > 2 ? currentParent : null
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
            li.className = 'lvl-' + it.level;
            if (it.level > 2 && cfg.collapseSubs) {
                li.classList.add('is-collapsed-child');
                if (it.parentH2) {
                    li.setAttribute('data-parent-h2', it.parentH2);
                }
            }

            var a = document.createElement('a');
            a.href = '#' + it.id;
            a.textContent = it.text;
            a.className = 'lvl-' + it.level;
            
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
            it.liElement = li;
        });

        // Add search headings input at the top if enabled
        if (cfg.showSearch) {
            var searchBox = document.createElement('div');
            searchBox.className = 'rawnaq-spt-search-wrap';
            searchBox.innerHTML = '<input type="search" class="rawnaq-spt-search-input" placeholder="Search headings..." />';
            tocEl.insertBefore(searchBox, list);

            var searchInput = searchBox.querySelector('.rawnaq-spt-search-input');
            searchInput.addEventListener('input', function(e) {
                var q = e.target.value.toLowerCase().trim();
                var listItems = list.querySelectorAll('li');
                listItems.forEach(function(li) {
                    var text = li.textContent.toLowerCase();
                    if (!q) {
                        // Restore standard collapsible/non-collapsible state
                        if (li.classList.contains('lvl-3') || li.classList.contains('lvl-4')) {
                            // If collapsible is active, it will be handled by the scroll observer
                            if (!cfg.collapseSubs) {
                                li.style.display = '';
                            }
                        } else {
                            li.style.display = '';
                        }
                        return;
                    }
                    if (text.indexOf(q) !== -1) {
                        li.style.display = '';
                        li.classList.remove('is-collapsed-child');
                    } else {
                        li.style.display = 'none';
                    }
                });
            });
        }
    }

    function observeActive(items, tocEl) {
        if (!('IntersectionObserver' in window) || !items.length) {
            return null;
        }
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                var id = entry.target.id;
                var activeItem = items.find(function(it) { return it.id === id; });
                var activeParent = activeItem ? (activeItem.parentH2 || activeItem.id) : id;

                items.forEach(function (it) {
                    if (it.link) {
                        var isActive = it.id === id;
                        it.link.classList.toggle('is-active', isActive);
                    }
                    
                    // Dynamic Collapsing/Expanding of subheadings
                    if (it.liElement && it.level > 2 && cfg.collapseSubs) {
                        if (it.parentH2 === activeParent) {
                            it.liElement.classList.remove('is-collapsed-child');
                            it.liElement.style.display = '';
                        } else {
                            it.liElement.classList.add('is-collapsed-child');
                            // Only hide if search query is not active
                            var searchInput = tocEl ? tocEl.querySelector('.rawnaq-spt-search-input') : null;
                            if (!searchInput || !searchInput.value.trim()) {
                                it.liElement.style.display = 'none';
                            }
                        }
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

    function bindTimelineSync(inst, tlName) {
        var name = String(tlName || '').replace(/[^a-zA-Z0-9_-]/g, '');
        if (!name || !inst.chapterEl) {
            return;
        }
        var wrap = document.querySelector('.rawnaq-timeline-wrapper[data-tl-name="' + name + '"]');
        if (!wrap) {
            return;
        }
        function refresh() {
            var active = wrap.querySelector('.rawnaq-timeline-item.item-active');
            if (!active) {
                inst.chapterEl.hidden = true;
                return;
            }
            var title = '';
            var h = active.querySelector('h4, h3, .rawnaq-timeline-title');
            if (h) {
                title = (h.textContent || '').trim();
            }
            var items = wrap.querySelectorAll('.rawnaq-timeline-item');
            var idx = 0;
            for (var i = 0; i < items.length; i++) {
                if (items[i] === active) {
                    idx = i + 1;
                    break;
                }
            }
            inst.chapterEl.hidden = false;
            inst.chapterEl.textContent = title
                ? ('Chapter ' + idx + ': ' + title)
                : ('Chapter ' + idx);
        }
        refresh();
        if (typeof MutationObserver === 'undefined') {
            return;
        }
        var mo = new MutationObserver(refresh);
        mo.observe(wrap, { attributes: true, attributeFilter: ['class'], subtree: true });
        inst.timelineMo = mo;
    }

    function destroyAll() {
        instances.forEach(function (inst) {
            if (inst.observer) {
                inst.observer.disconnect();
            }
            if (inst.timelineMo) {
                inst.timelineMo.disconnect();
            }
            if (inst.bar && inst.bar.parentNode) {
                inst.bar.parentNode.removeChild(inst.bar);
            }
            if (inst.ring && inst.ring.parentNode) {
                inst.ring.parentNode.removeChild(inst.ring);
            }
            if (inst.dockItem && inst.dockItem.parentNode) {
                inst.dockItem.parentNode.removeChild(inst.dockItem);
            }
            if (inst.root) {
                inst.root.classList.remove('spt-bound', 'is-dock-attached');
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
            var ringSize = (getComputedStyle(root).getPropertyValue('--spt-ring-size') || '').trim();
            if (ringSize) {
                ring.style.setProperty('--spt-ring-size', ringSize);
            }
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
                    inst.totalMins = mins; // Store inside instance
                    readEl.hidden = false;
                    readEl.textContent = mins + ' min read';
                }
            }

            var chapterEl = toc.querySelector('.rawnaq-spt-chapter');
            if (!chapterEl) {
                chapterEl = document.createElement('p');
                chapterEl.className = 'rawnaq-spt-chapter';
                chapterEl.hidden = true;
                var titleNode = toc.querySelector('.rawnaq-spt-title');
                if (titleNode && titleNode.parentNode) {
                    titleNode.parentNode.insertBefore(chapterEl, titleNode);
                } else {
                    toc.insertBefore(chapterEl, toc.firstChild);
                }
            }
            inst.chapterEl = chapterEl;
            if (cfg.syncTimeline) {
                bindTimelineSync(inst, cfg.syncTimeline);
            }

            buildTocList(toc, items, cfg);
            inst.observer = observeActive(items, toc);

            var dockAttached = false;
            function tryDockAttach() {
                if (dockAttached || !cfg.dockAttach || tocPos !== 'floating') {
                    return false;
                }
                var dock = document.querySelector('.rawnaq-dock-container');
                if (!dock) {
                    return false;
                }
                var existing = dock.querySelector('.rawnaq-dock-item.is-toc-trigger');
                if (existing) {
                    inst.dockItem = existing;
                    dockAttached = true;
                    root.classList.add('is-dock-attached');
                    toc.classList.add('dock-attached');
                    return true;
                }
                var dockBtn = document.createElement('button');
                dockBtn.type = 'button';
                dockBtn.className = 'rawnaq-dock-item is-toc-trigger';
                dockBtn.setAttribute('aria-label', cfg.tocTitle || 'Contents');
                dockBtn.setAttribute('aria-expanded', 'false');
                dockBtn.style.setProperty('--hover-color', '#4338ca');
                dockBtn.innerHTML = '<span class="rawnaq-dock-icon" aria-hidden="true">≡</span>'
                    + '<span class="rawnaq-dock-tooltip">' + (cfg.tocTitle || 'Contents') + '</span>'
                    + '<span class="rawnaq-dock-mobile-label">' + (cfg.tocTitle || 'Contents') + '</span>';
                dock.appendChild(dockBtn);
                dockBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var open = toc.classList.toggle('is-panel-open');
                    dockBtn.classList.toggle('is-active', open);
                    dockBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
                });
                inst.dockItem = dockBtn;
                dockAttached = true;
                root.classList.add('is-dock-attached');
                toc.classList.add('dock-attached');
                var dockOffset = getComputedStyle(dock).getPropertyValue('--dock-safe-offset') || '0px';
                var dockBottom = getComputedStyle(dock).getPropertyValue('--dock-offset') || '20px';
                root.style.setProperty('--spt-dock-clear', 'calc(' + dockBottom.trim() + ' + ' + dockOffset.trim() + ' + 72px)');
                var existingFab = root.querySelector('.rawnaq-spt-fab-wrapper');
                if (existingFab) {
                    existingFab.hidden = true;
                    existingFab.style.display = 'none';
                }
                return true;
            }

            if (cfg.dockAttach && tocPos === 'floating') {
                tryDockAttach();
                if (!dockAttached) {
                    setTimeout(function () {
                        if (tryDockAttach()) {
                            var fab = root.querySelector('.rawnaq-spt-fab-wrapper');
                            if (fab) {
                                fab.hidden = true;
                                fab.style.display = 'none';
                            }
                        }
                    }, 400);
                }
            }

            var fabWrapper = root.querySelector('.rawnaq-spt-fab-wrapper');
            if (!dockAttached && !fabWrapper && root.classList.contains('has-mobile-fab')) {
                fabWrapper = document.createElement('div');
                fabWrapper.className = 'rawnaq-spt-fab-wrapper';
                fabWrapper.innerHTML =
                    '<button type="button" class="rawnaq-spt-fab-trigger" aria-label="Toggle Actions">≡</button>' +
                    '<div class="rawnaq-spt-radial-menu">' +
                        '<button type="button" class="rawnaq-spt-action-btn action-toc" title="Toggle Contents">📖</button>' +
                        '<button type="button" class="rawnaq-spt-action-btn action-top" title="Scroll to Top">▲</button>' +
                        '<button type="button" class="rawnaq-spt-action-btn action-copy" title="Copy Article Link">🔗</button>' +
                    '</div>' +
                    '<div class="rawnaq-spt-toast-msg" hidden>Copied!</div>';
                root.appendChild(fabWrapper);
            }

            if (dockAttached && fabWrapper) {
                fabWrapper.hidden = true;
                fabWrapper.style.display = 'none';
            }

            if (fabWrapper && !dockAttached) {
                var trigger = fabWrapper.querySelector('.rawnaq-spt-fab-trigger');
                var radialMenu = fabWrapper.querySelector('.rawnaq-spt-radial-menu');
                var toast = fabWrapper.querySelector('.rawnaq-spt-toast-msg');

                trigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    fabWrapper.classList.toggle('is-active');
                });

                // 1. Toggle TOC Slide Drawer
                var btnToc = radialMenu.querySelector('.action-toc');
                btnToc.addEventListener('click', function(e) {
                    e.stopPropagation();
                    toc.classList.toggle('is-sheet-open');
                    fabWrapper.classList.remove('is-active');
                });

                // 2. Scroll to top
                var btnTop = radialMenu.querySelector('.action-top');
                btnTop.addEventListener('click', function(e) {
                    e.stopPropagation();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    fabWrapper.classList.remove('is-active');
                });

                // 3. Copy Page URL
                var btnCopy = radialMenu.querySelector('.action-copy');
                btnCopy.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var url = window.location.href.split('#')[0];
                    navigator.clipboard.writeText(url).then(function() {
                        toast.hidden = false;
                        toast.classList.add('show');
                        setTimeout(function() {
                            toast.classList.remove('show');
                            toast.hidden = true;
                        }, 2000);
                    });
                    fabWrapper.classList.remove('is-active');
                });

                // Close radial menu on body click
                document.addEventListener('click', function() {
                    fabWrapper.classList.remove('is-active');
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
