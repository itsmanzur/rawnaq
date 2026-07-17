/**
 * Rawnaq Bento Grid — reveal, stat count-up, video viewport play
 */
(function () {
    'use strict';

    var observer = null;
    var videoObserver = null;

    function prefersReducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function animateCount(el) {
        if (!el || el.dataset.counted === '1') {
            return;
        }
        var target = parseFloat(el.getAttribute('data-count') || '0');
        var suffix = el.getAttribute('data-suffix') || '';
        var prefix = el.getAttribute('data-prefix') || '';
        if (isNaN(target)) {
            return;
        }
        el.dataset.counted = '1';
        if (prefersReducedMotion()) {
            el.textContent = prefix + target + suffix;
            return;
        }
        var duration = 1100;
        var start = null;
        function frame(ts) {
            if (!start) {
                start = ts;
            }
            var p = Math.min(1, (ts - start) / duration);
            var eased = 1 - Math.pow(1 - p, 3);
            var val = target % 1 === 0
                ? Math.round(target * eased)
                : (target * eased).toFixed(1);
            el.textContent = prefix + val + suffix;
            if (p < 1) {
                requestAnimationFrame(frame);
            }
        }
        requestAnimationFrame(frame);
    }

    function ensureRevealObserver() {
        if (observer || prefersReducedMotion()) {
            return;
        }
        observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                var cell = entry.target;
                var delay = parseInt(cell.getAttribute('data-delay') || '0', 10);
                setTimeout(function () {
                    cell.classList.add('is-in');
                    var num = cell.querySelector('.rawnaq-bento-num[data-count]');
                    if (num) {
                        animateCount(num);
                    }
                }, delay);
                observer.unobserve(cell);
            });
        }, { threshold: 0.18, rootMargin: '0px 0px -8% 0px' });
    }

    function ensureVideoObserver() {
        if (videoObserver) {
            return;
        }
        videoObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                var video = entry.target;
                if (!(video instanceof HTMLVideoElement)) {
                    return;
                }
                if (entry.isIntersecting && entry.intersectionRatio >= 0.55) {
                    var playPromise = video.play();
                    if (playPromise && playPromise.catch) {
                        playPromise.catch(function () {});
                    }
                } else {
                    video.pause();
                }
            });
        }, { threshold: [0, 0.55, 1] });
    }

    function mountGrid(root) {
        if (!root || root.classList.contains('bento-bound')) {
            return;
        }
        root.classList.add('bento-bound');

        var reveal = root.getAttribute('data-reveal') !== '0';
        var cells = root.querySelectorAll('.rawnaq-bento-cell');

        if (!reveal || prefersReducedMotion()) {
            cells.forEach(function (cell) {
                cell.classList.add('is-in');
                var num = cell.querySelector('.rawnaq-bento-num[data-count]');
                if (num) {
                    animateCount(num);
                }
            });
        } else {
            ensureRevealObserver();
            cells.forEach(function (cell, i) {
                cell.setAttribute('data-delay', String(Math.min(i * 70, 420)));
                observer.observe(cell);
            });
        }

        var videos = root.querySelectorAll('video.rawnaq-bento-video');
        if (videos.length) {
            ensureVideoObserver();
            videos.forEach(function (v) {
                v.muted = true;
                v.playsInline = true;
                v.loop = true;
                videoObserver.observe(v);
            });
        }
    }

    function initAll() {
        document.querySelectorAll('.rawnaq-bento-grid').forEach(mountGrid);
    }

    function hookElementor() {
        if (!window.elementorFrontend || !elementorFrontend.hooks) {
            return;
        }
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/rawnaq_bento_grid.default',
            function ($scope) {
                var grid = $scope && $scope[0] ? $scope[0].querySelector('.rawnaq-bento-grid') : null;
                if (grid) {
                    grid.classList.remove('bento-bound');
                    mountGrid(grid);
                }
            }
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

    window.rawnaqBentoGridMount = mountGrid;
    window.rawnaqBentoGridBoot = initAll;
})();
