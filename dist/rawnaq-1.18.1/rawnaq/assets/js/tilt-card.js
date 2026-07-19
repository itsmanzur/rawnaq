/**
 * 3D Tilt Card — Professional motion engine
 * Glare + hover scale + reduced-motion / touch guards
 */
(function() {
    'use strict';

    var bound = typeof WeakSet !== 'undefined' ? new WeakSet() : null;

    function prefersReducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function isCoarsePointer() {
        return window.matchMedia && window.matchMedia('(hover: none), (pointer: coarse)').matches;
    }

    function parseNum(value, fallback) {
        var n = parseFloat(value);
        return isNaN(n) ? fallback : n;
    }

    function bindCard(card) {
        if (!card || (bound && bound.has(card))) return;
        if (bound) bound.add(card);

        if (prefersReducedMotion() || isCoarsePointer()) {
            card.classList.add('no-tilt');
            return;
        }

        var maxTilt = parseNum(card.getAttribute('data-tilt-max'), 15);
        var hoverScale = parseNum(card.getAttribute('data-hover-scale') || card.style.getPropertyValue('--hover-scale'), 1.03);
        var glareEl = card.querySelector('.rawnaq-tilt-glare');

        if (maxTilt <= 0 && hoverScale <= 1) {
            return;
        }

        card.addEventListener('mouseenter', function() {
            card.style.transition = 'box-shadow 0.25s ease';
            card.classList.add('is-tilting');
            if (glareEl) {
                glareEl.style.transition = 'opacity 0.2s ease';
            }
        });

        card.addEventListener('mousemove', function(e) {
            var rect = card.getBoundingClientRect();
            var x = e.clientX - rect.left;
            var y = e.clientY - rect.top;
            var px = x / rect.width;
            var py = y / rect.height;

            var tiltX = (0.5 - py) * (maxTilt * 2);
            var tiltY = (px - 0.5) * (maxTilt * 2);

            card.style.transform =
                'rotateX(' + tiltX.toFixed(2) + 'deg) rotateY(' + tiltY.toFixed(2) + 'deg) scale(' + hoverScale + ')';

            if (glareEl) {
                glareEl.style.left = x + 'px';
                glareEl.style.top = y + 'px';
                glareEl.style.opacity = '1';
            }
        });

        card.addEventListener('mouseleave', function() {
            card.style.transition = 'transform 0.5s cubic-bezier(0.25, 1, 0.5, 1), box-shadow 0.3s ease';
            card.style.transform = 'rotateX(0deg) rotateY(0deg) scale(1)';
            card.classList.remove('is-tilting');
            if (glareEl) {
                glareEl.style.transition = 'opacity 0.4s ease';
                glareEl.style.opacity = '0';
            }
        });
    }

    function initTiltCards(root) {
        var scope = root && root.querySelectorAll ? root : document;
        scope.querySelectorAll('.rawnaq-tilt-card').forEach(bindCard);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initTiltCards(document);
        });
    } else {
        initTiltCards(document);
    }

    function bindElementor() {
        if (!window.elementorFrontend || !elementorFrontend.hooks) return false;
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/rawnaq_tilt_card.default',
            function($scope) {
                initTiltCards($scope[0]);
            }
        );
        return true;
    }

    if (!bindElementor()) {
        window.addEventListener('elementor/frontend/init', bindElementor);
        if (window.jQuery) {
            jQuery(window).on('elementor/frontend/init', bindElementor);
        }
    }

    window.RawnaqTiltCard = { init: initTiltCards };
})();
