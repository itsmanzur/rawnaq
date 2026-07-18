/**
 * Scroll Sync Timeline — CSS scroll-driven when supported; JS fallback otherwise.
 * Also: load-more (DOM or AJAX query), reduced-motion, Elementor re-init.
 */
(function () {
    'use strict';

    var instances = [];
    var scrollBound = false;
    var resizeBound = false;
    var ticking = false;
    var elementorHooked = false;

    function prefersReducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function supportsCssScrollTimeline() {
        try {
            return window.CSS && CSS.supports && (
                CSS.supports('animation-timeline: view()') ||
                CSS.supports('animation-timeline: scroll()')
            );
        } catch (e) {
            return false;
        }
    }

    function destroyAll() {
        instances.forEach(function (inst) {
            if (inst.observer) {
                inst.observer.disconnect();
            }
            if (inst.wrap) {
                inst.wrap.classList.remove('tl-bound', 'tl-reduced-motion', 'tl-css-driven', 'tl-js-driven');
            }
            if (inst.loadBtn && inst.loadHandler) {
                inst.loadBtn.removeEventListener('click', inst.loadHandler);
            }
        });
        instances = [];
    }

    function updateFills() {
        instances.forEach(function (inst) {
            if (!inst.useJsMotion || !inst.wrap || !inst.activeLine) {
                return;
            }

            var wrap = inst.wrap;
            var activeLine = inst.activeLine;
            var horizontal = wrap.classList.contains('layout-horizontal') && window.innerWidth > 767;
            var rect = wrap.getBoundingClientRect();
            var viewportCenter = window.innerHeight / 2;
            var passed = viewportCenter - rect.top;
            var progress = rect.height > 0 ? passed / rect.height : 0;
            progress = Math.max(0, Math.min(1, progress));

            if (horizontal) {
                activeLine.style.height = '';
                activeLine.style.width = (progress * 100) + '%';
            } else {
                activeLine.style.width = '';
                activeLine.style.height = (progress * 100) + '%';
            }
        });

        emitPrimaryTimelineActive();
    }

    var lastTimelineKeys = {};

    function emitPrimaryTimelineActive() {
        var wraps = document.querySelectorAll('.rawnaq-timeline-wrapper');
        wraps.forEach(function (wrap) {
            var items = wrap.querySelectorAll('.rawnaq-timeline-item:not(.tl-hidden)');
            if (!items.length) {
                return;
            }
            var viewportCenter = window.innerHeight / 2;
            var best = null;
            var bestDist = Infinity;
            var bestIndex = -1;
            items.forEach(function (item, idx) {
                var r = item.getBoundingClientRect();
                var mid = (r.top + r.bottom) / 2;
                var dist = Math.abs(mid - viewportCenter);
                if (dist < bestDist) {
                    bestDist = dist;
                    best = item;
                    bestIndex = idx;
                }
            });
            if (!best) {
                return;
            }
            // Ignore when the "closest" item is still far outside the viewport —
            // otherwise scrolling the Case-Study section keeps re-targeting old steps.
            var bestRect = best.getBoundingClientRect();
            var inBand = bestRect.bottom > -40 && bestRect.top < viewportCenter * 2 + 40;
            if (!inBand) {
                return;
            }
            var titleEl = best.querySelector('h4');
            var wrapKey = wrap.getAttribute('data-tl-name') || 'tl';
            var key = bestIndex + ':' +
                (best.getAttribute('data-project-id') || '') + ':' +
                (best.getAttribute('data-project-slug') || '');
            if (lastTimelineKeys[wrapKey] === key) {
                return;
            }
            lastTimelineKeys[wrapKey] = key;
            wrap.dispatchEvent(new CustomEvent('rawnaq:scroll:active', {
                bubbles: true,
                detail: {
                    module: 'timeline',
                    index: bestIndex,
                    projectId: best.getAttribute('data-project-id') || '',
                    projectSlug: best.getAttribute('data-project-slug') || '',
                    title: titleEl ? (titleEl.textContent || '').trim() : ''
                }
            }));
        });
    }

    function onScrollOrResize() {
        if (ticking) {
            return;
        }
        ticking = true;
        window.requestAnimationFrame(function () {
            updateFills();
            // CSS-driven timelines still need highlight sync on scroll.
            if (!instances.some(function (i) { return i.useJsMotion; })) {
                emitPrimaryTimelineActive();
            }
            ticking = false;
        });
    }

    function bindGlobalListeners() {
        if (!scrollBound) {
            window.addEventListener('scroll', onScrollOrResize, { passive: true });
            scrollBound = true;
        }
        if (!resizeBound) {
            window.addEventListener('resize', onScrollOrResize, { passive: true });
            resizeBound = true;
        }
    }

    function observeNewItems(observer, activateOnReveal, nodes) {
        Array.prototype.forEach.call(nodes, function (item) {
            if (activateOnReveal) {
                item.classList.add('item-active');
            }
            if (observer) {
                observer.observe(item);
            }
        });
    }

    function setupAjaxLoadMore(wrap, observer, activateOnReveal) {
        var btnWrap = wrap.querySelector('.rawnaq-timeline-load-more');
        var btn = btnWrap ? btnWrap.querySelector('button') : null;
        if (!btn || !btnWrap) {
            return { btn: null, handler: null };
        }

        var cfg = window.rawnaqTimeline || {};
        var offset = parseInt(wrap.getAttribute('data-tl-offset'), 10) || 0;
        var chunk = parseInt(wrap.getAttribute('data-load-chunk'), 10) || 3;
        var query = wrap.getAttribute('data-tl-query') || '';
        var layout = wrap.getAttribute('data-tl-layout') || 'alternating';
        var showNumbers = wrap.getAttribute('data-show-numbers') === '1';
        var loading = false;

        function revealMore(e) {
            if (e) {
                e.preventDefault();
            }
            if (loading || !cfg.ajaxUrl || !cfg.nonce) {
                return;
            }
            loading = true;
            btn.disabled = true;

            var body = new window.FormData();
            body.append('action', 'rawnaq_timeline_load_more');
            body.append('nonce', cfg.nonce);
            body.append('offset', String(offset));
            body.append('chunk', String(chunk));
            body.append('query', query);
            body.append('layout', layout);
            body.append('show_numbers', showNumbers ? '1' : '0');

            window.fetch(cfg.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: body
            }).then(function (res) {
                return res.json();
            }).then(function (json) {
                loading = false;
                btn.disabled = false;
                if (!json || !json.success || !json.data) {
                    return;
                }
                var data = json.data;
                if (data.html) {
                    var tmp = document.createElement('div');
                    tmp.innerHTML = data.html;
                    var nodes = Array.prototype.slice.call(tmp.querySelectorAll('.rawnaq-timeline-item'));
                    nodes.forEach(function (node) {
                        btnWrap.parentNode.insertBefore(node, btnWrap);
                    });
                    observeNewItems(observer, activateOnReveal, nodes);
                }
                offset = parseInt(data.next_offset, 10) || offset;
                wrap.setAttribute('data-tl-offset', String(offset));
                if (!data.has_more) {
                    btnWrap.hidden = true;
                }
                updateFills();
            }).catch(function () {
                loading = false;
                btn.disabled = false;
            });
        }

        btn.addEventListener('click', revealMore);
        return { btn: btn, handler: revealMore };
    }

    function setupDomLoadMore(wrap, observer, activateOnReveal) {
        var initial = parseInt(wrap.getAttribute('data-initial-visible'), 10) || 0;
        var items = Array.prototype.slice.call(wrap.querySelectorAll('.rawnaq-timeline-item'));
        var btnWrap = wrap.querySelector('.rawnaq-timeline-load-more');
        var btn = btnWrap ? btnWrap.querySelector('button') : null;

        if (!initial || initial <= 0 || items.length <= initial) {
            if (btnWrap) {
                btnWrap.hidden = true;
            }
            return { btn: null, handler: null };
        }

        items.forEach(function (item, idx) {
            if (idx >= initial) {
                item.classList.add('tl-hidden');
            } else {
                item.classList.remove('tl-hidden');
            }
        });

        if (!btn || !btnWrap) {
            return { btn: null, handler: null };
        }

        btnWrap.hidden = false;
        var nextIndex = initial;
        var chunk = parseInt(wrap.getAttribute('data-load-chunk'), 10) || initial;

        function revealMore() {
            var end = Math.min(nextIndex + chunk, items.length);
            for (var i = nextIndex; i < end; i++) {
                items[i].classList.remove('tl-hidden');
                if (activateOnReveal) {
                    items[i].classList.add('item-active');
                }
                if (observer) {
                    observer.observe(items[i]);
                }
            }
            nextIndex = end;
            if (nextIndex >= items.length) {
                btnWrap.hidden = true;
            }
            updateFills();
        }

        btn.addEventListener('click', revealMore);
        return { btn: btn, handler: revealMore };
    }

    function setupLoadMore(wrap, observer, activateOnReveal) {
        if (wrap.getAttribute('data-tl-ajax') === '1') {
            return setupAjaxLoadMore(wrap, observer, activateOnReveal);
        }
        return setupDomLoadMore(wrap, observer, activateOnReveal);
    }

    function initWrapper(wrap) {
        if (!wrap || wrap.classList.contains('tl-bound')) {
            return;
        }
        wrap.classList.add('tl-bound');

        var reduced = prefersReducedMotion();
        var cssDriven = !reduced && supportsCssScrollTimeline();
        var activeLine = wrap.querySelector('.rawnaq-timeline-line-active');
        var items = wrap.querySelectorAll('.rawnaq-timeline-item');
        var observer = null;
        var useJsMotion = false;

        if (reduced) {
            wrap.classList.add('tl-reduced-motion');
            items.forEach(function (item) {
                item.classList.add('item-active');
            });
            if (activeLine) {
                if (wrap.classList.contains('layout-horizontal') && window.innerWidth > 767) {
                    activeLine.style.width = '100%';
                } else {
                    activeLine.style.height = '100%';
                }
            }
        } else if (cssDriven) {
            wrap.classList.add('tl-css-driven');
        } else {
            wrap.classList.add('tl-js-driven');
            useJsMotion = true;
            observer = new IntersectionObserver(
                function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('item-active');
                            entry.target.classList.remove('item-leaving');
                        } else {
                            // Match CSS scrub feel: deactivate when leaving viewport.
                            entry.target.classList.remove('item-active');
                            entry.target.classList.add('item-leaving');
                        }
                    });
                },
                {
                    root: null,
                    rootMargin: '0px 0px -18% 0px',
                    threshold: [0, 0.15, 0.35, 0.55]
                }
            );
            items.forEach(function (item) {
                if (!item.classList.contains('tl-hidden')) {
                    observer.observe(item);
                }
            });
        }

        var load = setupLoadMore(wrap, observer, reduced);

        instances.push({
            wrap: wrap,
            activeLine: activeLine,
            observer: observer,
            useJsMotion: useJsMotion,
            loadBtn: load.btn,
            loadHandler: load.handler
        });
    }

    function initTimeline(scope) {
        destroyAll();

        var wrappers = document.querySelectorAll('.rawnaq-timeline-wrapper');
        wrappers.forEach(initWrapper);
        bindGlobalListeners();
        updateFills();
    }

    function hookElementor() {
        if (elementorHooked || !window.elementorFrontend || !elementorFrontend.hooks) {
            return;
        }
        elementorHooked = true;
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/rawnaq_scroll_timeline.default',
            function () {
                initTimeline(document);
            }
        );
    }

    function boot() {
        initTimeline(document);
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

    window.rawnaqTimelineInit = initTimeline;
})();
