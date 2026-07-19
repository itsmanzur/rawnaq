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

        if (isEditMode()) {
            enableEditorResize(root);
        }
    }

    function isEditMode() {
        try {
            return !!(window.elementorFrontend && elementorFrontend.isEditMode && elementorFrontend.isEditMode());
        } catch (e) {
            return !!(document.body && document.body.classList.contains('elementor-editor-active'));
        }
    }

    function getEditorApi() {
        try {
            if (window.parent && window.parent.elementor) {
                return window.parent.elementor;
            }
        } catch (e) { /* cross-origin */ }
        return window.elementor || null;
    }

    function syncCellSpan(grid, index, col, row) {
        var scope = grid.closest('.elementor-element');
        var widgetId = scope && scope.getAttribute('data-id');
        var api = getEditorApi();
        if (!widgetId || !api) {
            return;
        }
        var container = null;
        try {
            if (typeof api.getContainer === 'function') {
                container = api.getContainer(widgetId);
            } else if (api.getContainerById) {
                container = api.getContainerById(widgetId);
            }
        } catch (err) {
            return;
        }
        if (!container || !container.settings) {
            return;
        }
        var cells = container.settings.get('cells');
        if (!cells) {
            return;
        }
        var models = cells.models || cells;
        var model = models[index];
        if (!model) {
            return;
        }
        var nextCells;
        if (cells.models) {
            // Backbone collection — clone JSON then replace
            nextCells = cells.toJSON();
            if (!nextCells[index]) {
                return;
            }
            nextCells[index].col_span = String(col);
            nextCells[index].row_span = String(row);
        } else if (Array.isArray(cells)) {
            nextCells = cells.map(function (c, i) {
                var copy = Object.assign({}, c);
                if (i === index) {
                    copy.col_span = String(col);
                    copy.row_span = String(row);
                }
                return copy;
            });
        } else {
            return;
        }

        var $e = (window.parent && window.parent.$e) || window.$e;
        if ($e && $e.run) {
            $e.run('document/elements/settings', {
                container: container,
                settings: { cells: nextCells },
                options: { external: true }
            });
        }
    }

    function enableEditorResize(root) {
        if (root.classList.contains('bento-resize-bound')) {
            return;
        }
        root.classList.add('bento-resize-bound', 'is-editor-resize');
        var cols = parseInt(root.getAttribute('data-cols') || '4', 10) || 4;

        root.querySelectorAll('.rawnaq-bento-cell').forEach(function (cell) {
            if (cell.querySelector('.rawnaq-bento-resize-handle')) {
                return;
            }
            var handle = document.createElement('button');
            handle.type = 'button';
            handle.className = 'rawnaq-bento-resize-handle';
            handle.setAttribute('aria-label', 'Resize cell');
            handle.title = 'Drag to resize';
            cell.appendChild(handle);

            var startX = 0;
            var startY = 0;
            var startCol = 1;
            var startRow = 1;
            var dragging = false;

            handle.addEventListener('pointerdown', function (e) {
                e.preventDefault();
                e.stopPropagation();
                dragging = true;
                startX = e.clientX;
                startY = e.clientY;
                var cs = window.getComputedStyle(cell);
                startCol = parseInt(cs.getPropertyValue('--bento-span-col'), 10) || 1;
                startRow = parseInt(cs.getPropertyValue('--bento-span-row'), 10) || 1;
                cell.classList.add('is-resizing');
                handle.setPointerCapture(e.pointerId);
            });

            handle.addEventListener('pointermove', function (e) {
                if (!dragging) {
                    return;
                }
                var gridRect = root.getBoundingClientRect();
                var gap = parseFloat(getComputedStyle(root).columnGap) || 16;
                var unitW = (gridRect.width - gap * (cols - 1)) / cols;
                var rowH = parseFloat(getComputedStyle(root).getPropertyValue('--bento-row')) || 140;
                var dCol = Math.round((e.clientX - startX) / Math.max(unitW, 24));
                var dRow = Math.round((e.clientY - startY) / Math.max(rowH, 40));
                var nextCol = Math.max(1, Math.min(cols, startCol + dCol));
                var nextRow = Math.max(1, Math.min(6, startRow + dRow));
                cell.style.gridColumn = 'span ' + nextCol;
                cell.style.gridRow = 'span ' + nextRow;
                cell.style.setProperty('--bento-span-col', String(nextCol));
                cell.style.setProperty('--bento-span-row', String(nextRow));
                cell.dataset.pendingCol = String(nextCol);
                cell.dataset.pendingRow = String(nextRow);
            });

            function endDrag(e) {
                if (!dragging) {
                    return;
                }
                dragging = false;
                cell.classList.remove('is-resizing');
                var idx = parseInt(cell.getAttribute('data-bento-index'), 10);
                var nextCol = parseInt(cell.dataset.pendingCol || startCol, 10);
                var nextRow = parseInt(cell.dataset.pendingRow || startRow, 10);
                if (!isNaN(idx)) {
                    syncCellSpan(root, idx, nextCol, nextRow);
                }
                try {
                    handle.releasePointerCapture(e.pointerId);
                } catch (err) { /* ignore */ }
            }

            handle.addEventListener('pointerup', endDrag);
            handle.addEventListener('pointercancel', endDrag);
        });
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
