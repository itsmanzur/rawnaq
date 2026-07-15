/**
 * Scroll-Sync Process Timeline — multi-instance, rAF scroll, Elementor-safe cleanup
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

    function destroyAll() {
        instances.forEach(function (inst) {
            if (inst.observer) {
                inst.observer.disconnect();
            }
            if (inst.wrap) {
                inst.wrap.classList.remove('tl-bound', 'tl-reduced-motion');
            }
        });
        instances = [];
    }

    function updateFills() {
        instances.forEach(function (inst) {
            var wrap = inst.wrap;
            var activeLine = inst.activeLine;
            if (!wrap || !activeLine) {
                return;
            }

            var rect = wrap.getBoundingClientRect();
            var viewportCenter = window.innerHeight / 2;
            var passed = viewportCenter - rect.top;
            var progress = rect.height > 0 ? passed / rect.height : 0;
            progress = Math.max(0, Math.min(1, progress));
            activeLine.style.height = (progress * 100) + '%';
        });
    }

    function onScrollOrResize() {
        if (ticking) {
            return;
        }
        ticking = true;
        window.requestAnimationFrame(function () {
            updateFills();
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

    function initWrapper(wrap) {
        if (!wrap || wrap.classList.contains('tl-bound')) {
            return;
        }
        wrap.classList.add('tl-bound');

        var reduced = prefersReducedMotion();
        if (reduced) {
            wrap.classList.add('tl-reduced-motion');
        }

        var activeLine = wrap.querySelector('.rawnaq-timeline-line-active');
        var items = wrap.querySelectorAll('.rawnaq-timeline-item');
        var observer = null;

        if (reduced) {
            items.forEach(function (item) {
                item.classList.add('item-active');
            });
        } else {
            observer = new IntersectionObserver(
                function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('item-active');
                        }
                    });
                },
                {
                    root: null,
                    rootMargin: '0px 0px -25% 0px',
                    threshold: 0.1
                }
            );
            items.forEach(function (item) {
                observer.observe(item);
            });
        }

        instances.push({
            wrap: wrap,
            activeLine: activeLine,
            observer: observer
        });
    }

    function initTimeline(scope) {
        var root = scope && scope.querySelectorAll ? scope : document;
        // Elementor re-inits: wipe previous observers then rebuild all wrappers.
        // Global scroll listener stays single; we rebuild the instances list.
        destroyAll();

        var wrappers = root.querySelectorAll
            ? root.querySelectorAll('.rawnaq-timeline-wrapper')
            : [];

        // If scoped (Elementor widget), still need other page instances re-bound
        // after destroyAll — so always scan document.
        if (scope && scope !== document) {
            wrappers = document.querySelectorAll('.rawnaq-timeline-wrapper');
        }

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

    // Elementor editor may load frontend after DOMContentLoaded
    if (window.jQuery) {
        jQuery(window).on('elementor/frontend/init', hookElementor);
    }
})();
