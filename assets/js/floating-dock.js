/**
 * Floating Dock — proximity magnify with destroy/rebind + reduced-motion
 */
(function () {
    'use strict';

    var instances = [];
    var pointerBound = false;
    var ticking = false;
    var lastEvent = null;
    var elementorHooked = false;

    function prefersReducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function isMobile() {
        return window.innerWidth < 768;
    }

    function resetItem(item, baseSize) {
        item.style.transition = 'width 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), height 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
        item.style.width = baseSize + 'px';
        item.style.height = baseSize + 'px';
        var icon = item.querySelector('.rawnaq-dock-icon i, .rawnaq-dock-icon svg, .rawnaq-dock-icon .dashicons');
        if (icon) {
            icon.style.transition = 'font-size 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
            icon.style.fontSize = (baseSize * 0.5) + 'px';
        }
    }

    function destroyAll() {
        instances.forEach(function (inst) {
            if (inst.dock) {
                inst.dock.classList.remove('dock-bound');
                if (inst.onLeave) {
                    inst.dock.removeEventListener('mouseleave', inst.onLeave);
                }
                var items = inst.dock.querySelectorAll('.rawnaq-dock-item');
                items.forEach(function (item) {
                    item.style.width = '';
                    item.style.height = '';
                    item.style.transition = '';
                    var icon = item.querySelector('.rawnaq-dock-icon i, .rawnaq-dock-icon svg, .rawnaq-dock-icon .dashicons');
                    if (icon) {
                        icon.style.fontSize = '';
                        icon.style.transition = '';
                    }
                });
            }
        });
        instances = [];
    }

    function applyMagnify(e) {
        instances.forEach(function (inst) {
            if (!inst.enabled || !inst.dock) {
                return;
            }
            var items = inst.dock.querySelectorAll('.rawnaq-dock-item');
            var baseSize = inst.baseSize;
            var maxScale = inst.maxScale;
            var maxDistance = Math.max(70, baseSize * 1.8);

            items.forEach(function (item) {
                var rect = item.getBoundingClientRect();
                var cx = rect.left + rect.width / 2;
                var cy = rect.top + rect.height / 2;
                var distance = Math.hypot(e.clientX - cx, e.clientY - cy);

                if (distance < maxDistance) {
                    var ratio = (maxDistance - distance) / maxDistance;
                    var size = baseSize + (baseSize * (maxScale - 1) * ratio);
                    item.style.transition = 'none';
                    item.style.width = size + 'px';
                    item.style.height = size + 'px';
                    var icon = item.querySelector('.rawnaq-dock-icon i, .rawnaq-dock-icon svg, .rawnaq-dock-icon .dashicons');
                    if (icon) {
                        icon.style.transition = 'none';
                        icon.style.fontSize = (size * 0.5) + 'px';
                    }
                } else {
                    resetItem(item, baseSize);
                }
            });
        });
    }

    function onPointerMove(e) {
        lastEvent = e;
        if (ticking) {
            return;
        }
        ticking = true;
        window.requestAnimationFrame(function () {
            if (lastEvent) {
                applyMagnify(lastEvent);
            }
            ticking = false;
        });
    }

    function bindGlobalPointer() {
        if (pointerBound) {
            return;
        }
        window.addEventListener('pointermove', onPointerMove, { passive: true });
        pointerBound = true;
    }

    function initDock(dock) {
        if (!dock || dock.classList.contains('dock-bound')) {
            return;
        }
        dock.classList.add('dock-bound');

        var magnifyAttr = dock.getAttribute('data-magnify');
        var enabled = magnifyAttr !== '0' && !prefersReducedMotion() && !isMobile();
        var maxScale = parseFloat(dock.getAttribute('data-max-scale') || '1.6');
        var baseSize = parseInt(dock.getAttribute('data-base-size') || '48', 10);
        if (isNaN(maxScale) || maxScale < 1.1) {
            maxScale = 1.6;
        }
        if (isNaN(baseSize) || baseSize < 24) {
            baseSize = 48;
        }

        var onLeave = function () {
            if (!enabled) {
                return;
            }
            dock.querySelectorAll('.rawnaq-dock-item').forEach(function (item) {
                resetItem(item, baseSize);
            });
        };
        dock.addEventListener('mouseleave', onLeave);

        instances.push({
            dock: dock,
            enabled: enabled,
            maxScale: maxScale,
            baseSize: baseSize,
            onLeave: onLeave
        });
    }

    function initAll() {
        destroyAll();
        document.querySelectorAll('.rawnaq-dock-container').forEach(initDock);
        if (instances.some(function (i) { return i.enabled; })) {
            bindGlobalPointer();
        }
    }

    function hookElementor() {
        if (elementorHooked || !window.elementorFrontend || !elementorFrontend.hooks) {
            return;
        }
        elementorHooked = true;
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/rawnaq_floating_dock.default',
            function () {
                initAll();
            }
        );
    }

    function boot() {
        initAll();
        hookElementor();
        window.addEventListener('resize', function () {
            // Re-evaluate mobile / reduced-motion enable flags
            initAll();
        }, { passive: true });
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
