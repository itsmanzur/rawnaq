/**
 * Rawnaq Bento Grid: reveal, stat count-up, video play/pause, spotlight, editor resize.
 *
 * Every listener of a grid is bound through one AbortController kept per root,
 * so destroy(root) removes all of them and a second mountGrid() on the same
 * root is a no-op (the old Elementor hook re-mounted and doubled listeners).
 */
(function () {
    'use strict';

    var observer = null;
    var videoObserver = null;
    var mounted = typeof WeakMap === 'function' ? new WeakMap() : null;
    var videoState = typeof WeakMap === 'function' ? new WeakMap() : null;
    var hookRegistered = false;

    function i18n(key, fallback) {
        var all = window.rawnaqBento && window.rawnaqBento.i18n;
        return (all && all[key]) || fallback;
    }

    function mq(query) {
        return window.matchMedia ? window.matchMedia(query) : null;
    }

    function prefersReducedMotion() {
        var m = mq('(prefers-reduced-motion: reduce)');
        return !!(m && m.matches);
    }

    function hasFinePointer() {
        var m = mq('(hover: hover) and (pointer: fine)');
        return !!(m && m.matches);
    }

    /** addEventListener on a media query list across old/new Safari. */
    function onMediaChange(list, fn, signal) {
        if (!list) {
            return;
        }
        if (list.addEventListener) {
            list.addEventListener('change', fn, signal ? { signal: signal } : undefined);
        } else if (list.addListener) {
            list.addListener(fn);
        }
    }

    // ── Stat count-up ───────────────────────────────────────────────────────

    function makeFormatter(decimals, grouping) {
        var opts = {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
            useGrouping: grouping
        };
        var lang = document.documentElement.lang || undefined;
        try {
            return new Intl.NumberFormat(lang, opts);
        } catch (e) {
            // A malformed lang attribute (e.g. "bn_BD") throws RangeError: use the browser default.
            return new Intl.NumberFormat(undefined, opts);
        }
    }

    /** Only cells that carry data-count are ever touched; everything else keeps the typed text. */
    function animateCount(el) {
        if (!el || !el.hasAttribute('data-count') || el.dataset.counted === '1') {
            return;
        }
        var target = parseFloat(el.getAttribute('data-count'));
        if (isNaN(target)) {
            return;
        }
        var decimals = parseInt(el.getAttribute('data-decimals') || '0', 10) || 0;
        var grouping = el.getAttribute('data-grouping') === '1';
        var suffix = el.getAttribute('data-suffix') || '';
        var prefix = el.getAttribute('data-prefix') || '';
        var fmt = makeFormatter(decimals, grouping);

        el.dataset.counted = '1';
        if (prefersReducedMotion()) {
            el.textContent = prefix + fmt.format(target) + suffix;
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
            el.textContent = prefix + fmt.format(p < 1 ? target * eased : target) + suffix;
            if (p < 1) {
                requestAnimationFrame(frame);
            }
        }
        requestAnimationFrame(frame);
    }

    // ── Reveal ──────────────────────────────────────────────────────────────

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

    // ── Video: viewport play/pause + a pause button (WCAG 2.2.2) ───────────

    function applyVideoState(video) {
        var s = videoState && videoState.get(video);
        if (!s) {
            return;
        }
        var play;
        if (s.manual === 'paused') {
            play = false;
        } else if (s.manual === 'playing') {
            play = s.inView;
        } else {
            play = s.inView && !prefersReducedMotion();
        }
        if (play) {
            var promise = video.play();
            if (promise && promise.catch) {
                promise.catch(function () {});
            }
        } else if (!video.paused) {
            video.pause();
        }
    }

    function syncToggle(video) {
        var s = videoState && videoState.get(video);
        if (!s || !s.button) {
            return;
        }
        var paused = video.paused;
        // aria-pressed="true" = paused (the play icon is shown); the label names the action.
        s.button.setAttribute('aria-pressed', paused ? 'true' : 'false');
        s.button.setAttribute('aria-label', paused ? i18n('playVideo', 'Play video') : i18n('pauseVideo', 'Pause video'));
    }

    function ensureVideoObserver() {
        if (videoObserver) {
            return;
        }
        videoObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                var video = entry.target;
                var s = videoState && videoState.get(video);
                if (!(video instanceof HTMLVideoElement) || !s) {
                    return;
                }
                s.inView = entry.isIntersecting && entry.intersectionRatio >= 0.55;
                applyVideoState(video);
            });
        }, { threshold: [0, 0.55, 1] });
    }

    function setupVideo(video, state, signal) {
        if (videoState.has(video)) {
            return; // already wired by another mount of an enclosing root
        }
        video.muted = true;
        video.playsInline = true;
        video.loop = true;

        var s = { manual: null, inView: false, button: null };
        videoState.set(video, s);

        var host = video.closest('.rawnaq-bento-cell') || video.parentNode;
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'rawnaq-bento-video-toggle';
        s.button = button;
        host.appendChild(button);
        syncToggle(video);

        button.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (video.paused) {
                s.manual = 'playing';
                var promise = video.play();
                if (promise && promise.catch) {
                    promise.catch(function () {});
                }
            } else {
                s.manual = 'paused';
                video.pause();
            }
            syncToggle(video);
        }, { signal: signal });

        video.addEventListener('play', function () { syncToggle(video); }, { signal: signal });
        video.addEventListener('pause', function () { syncToggle(video); }, { signal: signal });

        state.videos.push(video);
        ensureVideoObserver();
        videoObserver.observe(video);
    }

    // ── Spotlight (pointer devices only, rAF-throttled, cached rect) ───────

    function setupSpotlight(cell, signal, shared) {
        var rect = null;
        var rectGen = -1;
        var frame = 0;
        var px = 0;
        var py = 0;

        function refresh() {
            rect = cell.getBoundingClientRect();
            rectGen = shared.gen;
        }

        cell.addEventListener('mouseenter', function () {
            if (!hasFinePointer()) {
                return;
            }
            refresh();
        }, { signal: signal });

        cell.addEventListener('mousemove', function (e) {
            if (!hasFinePointer()) {
                return;
            }
            px = e.clientX;
            py = e.clientY;
            if (frame) {
                return;
            }
            frame = requestAnimationFrame(function () {
                frame = 0;
                if (!rect || rectGen !== shared.gen) {
                    refresh();
                }
                cell.style.setProperty('--mouse-x', (px - rect.left) + 'px');
                cell.style.setProperty('--mouse-y', (py - rect.top) + 'px');
            });
        }, { signal: signal });
    }

    // ── Mount / destroy ─────────────────────────────────────────────────────

    function mountGrid(root) {
        if (!root || (mounted && mounted.has(root))) {
            return;
        }
        // First thing: the CSS only hides cells for the reveal once this class exists.
        root.classList.add('bento-bound');

        var controller = typeof AbortController === 'function' ? new AbortController() : null;
        var signal = controller ? controller.signal : undefined;
        var cells = root.querySelectorAll('.rawnaq-bento-cell');
        var state = { controller: controller, cells: cells, videos: [], revealObserved: false };
        if (mounted) {
            mounted.set(root, state);
        }

        var reveal = root.getAttribute('data-reveal') !== '0';

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
            if (observer) {
                state.revealObserved = true;
                cells.forEach(function (cell, i) {
                    cell.setAttribute('data-delay', String(Math.min(i * 70, 420)));
                    observer.observe(cell);
                });
            }
        }

        // Scrolling/resizing moves cells under a still pointer: bump a generation so the
        // cached rects are re-measured lazily (one pair of listeners per grid, not per cell).
        var shared = { gen: 0 };
        function markDirty() { shared.gen++; }
        window.addEventListener('scroll', markDirty, { passive: true, signal: signal });
        window.addEventListener('resize', markDirty, { passive: true, signal: signal });
        cells.forEach(function (cell) {
            setupSpotlight(cell, signal, shared);
        });

        var videos = root.querySelectorAll('video.rawnaq-bento-video');
        if (videos.length && videoState) {
            videos.forEach(function (v) {
                setupVideo(v, state, signal);
            });
            // Re-evaluate when the user flips their reduced-motion preference.
            onMediaChange(mq('(prefers-reduced-motion: reduce)'), function () {
                state.videos.forEach(applyVideoState);
            }, signal);
        }

        if (isEditMode()) {
            enableEditorResize(root, state, signal);
        }
    }

    /** Remove every listener/observer/injected node of a grid so it can be mounted again. */
    function destroy(root) {
        var state = mounted && mounted.get(root);
        if (!state) {
            return;
        }
        if (state.controller) {
            state.controller.abort();
        }
        if (observer && state.revealObserved) {
            state.cells.forEach(function (cell) { observer.unobserve(cell); });
        }
        state.videos.forEach(function (video) {
            if (videoObserver) {
                videoObserver.unobserve(video);
            }
            var s = videoState.get(video);
            if (s && s.button && s.button.parentNode) {
                s.button.parentNode.removeChild(s.button);
            }
            videoState.delete(video);
        });
        root.querySelectorAll('.rawnaq-bento-resize-handle').forEach(function (h) {
            if (h.parentNode) {
                h.parentNode.removeChild(h);
            }
        });
        root.classList.remove('bento-bound', 'bento-resize-bound', 'is-editor-resize');
        mounted.delete(root);
    }

    // ── Elementor editor: drag-to-resize ───────────────────────────────────

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
            // Backbone collection: clone JSON then replace.
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

    /** Columns the grid really has right now (it shrinks inside narrow containers). */
    function effectiveColumns(root) {
        var tracks = getComputedStyle(root).gridTemplateColumns;
        var count = tracks && tracks !== 'none' ? tracks.split(' ').length : 0;
        return count || parseInt(root.getAttribute('data-cols') || '4', 10) || 4;
    }

    function setSpanClasses(cell, col, row) {
        cell.className = cell.className
            .replace(/(^|\s)is-(col|row)-\d+(?=\s|$)/g, ' ')
            .replace(/\s+/g, ' ')
            .trim() + ' is-col-' + col + ' is-row-' + row;
    }

    function enableEditorResize(root, state, signal) {
        if (root.classList.contains('bento-resize-bound')) {
            return;
        }
        root.classList.add('bento-resize-bound', 'is-editor-resize');

        root.querySelectorAll('.rawnaq-bento-cell').forEach(function (cell) {
            if (cell.querySelector('.rawnaq-bento-resize-handle')) {
                return;
            }
            var handle = document.createElement('button');
            handle.type = 'button';
            handle.className = 'rawnaq-bento-resize-handle';
            handle.setAttribute('aria-label', i18n('resizeCell', 'Resize cell'));
            handle.title = i18n('dragToResize', 'Drag to resize');
            cell.appendChild(handle);

            var startX = 0;
            var startY = 0;
            var startCol = 1;
            var startRow = 1;
            var dragging = false;
            var cols = 4;

            handle.addEventListener('pointerdown', function (e) {
                e.preventDefault();
                e.stopPropagation();
                dragging = true;
                startX = e.clientX;
                startY = e.clientY;
                cols = effectiveColumns(root);
                var cs = window.getComputedStyle(cell);
                startCol = parseInt(cs.getPropertyValue('--bento-span-col'), 10) || 1;
                startRow = parseInt(cs.getPropertyValue('--bento-span-row'), 10) || 1;
                delete cell.dataset.pendingCol;
                delete cell.dataset.pendingRow;
                cell.classList.add('is-resizing');
                handle.setPointerCapture(e.pointerId);
            }, { signal: signal });

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
                // Live feedback only: the inline spans are removed again when the drag ends.
                cell.style.gridColumn = 'span ' + nextCol;
                cell.style.gridRow = 'span ' + nextRow;
                cell.style.setProperty('--bento-span-col', String(nextCol));
                cell.style.setProperty('--bento-span-row', String(nextRow));
                cell.dataset.pendingCol = String(nextCol);
                cell.dataset.pendingRow = String(nextRow);
            }, { signal: signal });

            function endDrag(e) {
                if (!dragging) {
                    return;
                }
                dragging = false;
                cell.classList.remove('is-resizing');
                var idx = parseInt(cell.getAttribute('data-bento-index'), 10);
                var nextCol = parseInt(cell.dataset.pendingCol || startCol, 10);
                var nextRow = parseInt(cell.dataset.pendingRow || startRow, 10);
                // The layout now comes from classes + custom properties, like on the frontend.
                cell.style.removeProperty('grid-column');
                cell.style.removeProperty('grid-row');
                setSpanClasses(cell, nextCol, nextRow);
                if (!isNaN(idx)) {
                    syncCellSpan(root, idx, nextCol, nextRow);
                }
                try {
                    handle.releasePointerCapture(e.pointerId);
                } catch (err) { /* ignore */ }
            }

            handle.addEventListener('pointerup', endDrag, { signal: signal });
            handle.addEventListener('pointercancel', endDrag, { signal: signal });
        });
    }

    // ── Boot ────────────────────────────────────────────────────────────────

    function initAll() {
        document.querySelectorAll('.rawnaq-bento-grid').forEach(mountGrid);
    }

    function hookElementor() {
        if (hookRegistered || !window.elementorFrontend || !elementorFrontend.hooks) {
            return;
        }
        hookRegistered = true;
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/rawnaq_bento_grid.default',
            function ($scope) {
                var grid = $scope && $scope[0] ? $scope[0].querySelector('.rawnaq-bento-grid') : null;
                if (grid) {
                    // No-op when this exact element is already mounted; a re-rendered widget is a new element.
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

    window.addEventListener('elementor/frontend/init', hookElementor);
    if (window.jQuery) {
        window.jQuery(window).on('elementor/frontend/init', hookElementor);
    }

    window.rawnaqBentoGridMount = mountGrid;
    window.rawnaqBentoGridDestroy = destroy;
    window.rawnaqBentoGridBoot = initAll;
})();
