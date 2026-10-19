/**
 * Rawnaq Project Case-Study Grid — multi-filter, load-more, gallery-slider modal
 */
(function () {
    'use strict';

    var rootStates = new WeakMap();
    var activeModal = null;
    var keydownBound = false;
    var elementorHooked = false;

    function t(key, fallback) {
        var i18n = (window.rawnaqCaseStudy && window.rawnaqCaseStudy.i18n) ? window.rawnaqCaseStudy.i18n : {};
        var str = (i18n && typeof i18n[key] === 'string') ? i18n[key] : (fallback || '');
        var args = Array.prototype.slice.call(arguments, 2);
        if (!args.length) {
            return str;
        }
        var idx = 0;
        str = str.replace(/%(\d+)\$([sdf])/g, function (match, num) {
            var n = parseInt(num, 10) - 1;
            return args[n] !== undefined ? args[n] : match;
        });
        str = str.replace(/%[sdf]/g, function () {
            var val = args[idx++];
            return val !== undefined ? val : '';
        });
        return str;
    }

    function prefersReducedMotion() {
        return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    function cfgOf(root) {
        try {
            return JSON.parse(root.getAttribute('data-cs') || '{}');
        } catch (e) {
            return {};
        }
    }

    function parseProject(card) {
        try {
            return JSON.parse(card.getAttribute('data-project') || '{}');
        } catch (e) {
            return {};
        }
    }

    function cardServices(card) {
        var raw = card.getAttribute('data-services') || '';
        return raw.split(/[,|]/).map(function (s) {
            return s.trim();
        }).filter(Boolean);
    }

    function currentFilters(root) {
        var filters = { sector: '', year: '', service: '' };
        root.querySelectorAll('.rawnaq-cs-filters[data-filter]').forEach(function (row) {
            var key = row.getAttribute('data-filter');
            if (!Object.prototype.hasOwnProperty.call(filters, key)) {
                return;
            }
            var active = row.querySelector('.rawnaq-cs-chip.is-active');
            filters[key] = active ? (active.getAttribute('data-' + key) || '') : '';
        });
        return filters;
    }

    function cardMatches(card, filters) {
        if (filters.sector && card.getAttribute('data-sector') !== filters.sector) {
            return false;
        }
        if (filters.year && card.getAttribute('data-year') !== filters.year) {
            return false;
        }
        if (filters.service && cardServices(card).indexOf(filters.service) === -1) {
            return false;
        }
        return true;
    }

    function updateLoadMoreState(root) {
        var btn = root.querySelector('.rawnaq-cs-load-more');
        var wrap = root.querySelector('.rawnaq-cs-load-more-wrap');
        if (!btn) {
            return;
        }
        var filters = currentFilters(root);
        var remaining = 0;
        root.querySelectorAll('.rawnaq-cs-card.is-load-hidden').forEach(function (card) {
            if (cardMatches(card, filters)) {
                remaining++;
            }
        });
        var hasRemaining = remaining > 0;
        btn.hidden = !hasRemaining;
        if (wrap) {
            wrap.classList.toggle('is-hidden', !hasRemaining);
        }
    }

    function setLiveStatus(root, message) {
        var statusEl = root.querySelector('.rawnaq-cs-status');
        if (statusEl) {
            statusEl.textContent = message;
        }
    }

    function applyFilters(root) {
        var filters = currentFilters(root);
        var state = rootStates.get(root);
        var cfg = cfgOf(root);
        var chunk = parseInt(cfg.loadChunk, 10) || 3;
        var isFiltered = !!(filters.sector || filters.year || filters.service);
        var reducedMotion = prefersReducedMotion();

        // If a filter is active, auto-reveal matching .is-load-hidden cards up to chunk size
        if (isFiltered) {
            var visibleMatches = 0;
            root.querySelectorAll('.rawnaq-cs-card:not(.is-load-hidden)').forEach(function (card) {
                if (cardMatches(card, filters)) {
                    visibleMatches++;
                }
            });
            if (visibleMatches < chunk) {
                var hiddenCards = root.querySelectorAll('.rawnaq-cs-card.is-load-hidden');
                for (var i = 0; i < hiddenCards.length && visibleMatches < chunk; i++) {
                    if (cardMatches(hiddenCards[i], filters)) {
                        hiddenCards[i].classList.remove('is-load-hidden');
                        visibleMatches++;
                    }
                }
            }
        }

        var matchCount = 0;
        root.querySelectorAll('.rawnaq-cs-card').forEach(function (card) {
            var match = cardMatches(card, filters);
            if (match) {
                card.classList.remove('is-fading');
                card.classList.remove('is-hidden');
                if (!card.classList.contains('is-load-hidden')) {
                    matchCount++;
                }
            } else {
                if (reducedMotion) {
                    card.classList.remove('is-fading');
                    card.classList.add('is-hidden');
                } else if (!card.classList.contains('is-hidden')) {
                    card.classList.add('is-fading');
                    var timer = window.setTimeout(function () {
                        if (card.classList.contains('is-fading')) {
                            card.classList.add('is-hidden');
                            card.classList.remove('is-fading');
                        }
                    }, 180);
                    if (state && state.fadeTimers) {
                        state.fadeTimers.push(timer);
                    }
                }
            }
        });

        // Client-side empty message
        var grid = root.querySelector('.rawnaq-cs-grid');
        if (grid) {
            var existingEmpty = grid.querySelector('.rawnaq-cs-empty');
            if (matchCount === 0) {
                if (!existingEmpty) {
                    var emptyP = document.createElement('p');
                    emptyP.className = 'rawnaq-cs-empty';
                    emptyP.textContent = t('noResults', 'No matching projects.');
                    grid.appendChild(emptyP);
                }
                setLiveStatus(root, t('noResults', 'No matching projects.'));
            } else {
                if (existingEmpty) {
                    existingEmpty.remove();
                }
                setLiveStatus(root, t('showing', 'Showing %d projects', matchCount));
            }
        }

        updateLoadMoreState(root);
    }

    function updateSliderPosition(modal) {
        var track = modal.querySelector('.rawnaq-cs-slider-track');
        var dotsWrap = modal.querySelector('.rawnaq-cs-slider-dots');
        var statusEl = modal.querySelector('.rawnaq-cs-slider-status');
        if (!track) {
            return;
        }
        var idx = modal._csIndex || 0;
        var images = modal._csImages || [];
        var total = images.length;

        var reduceMotion = prefersReducedMotion();
        track.style.transition = reduceMotion ? 'none' : '';
        track.style.transform = 'translateX(-' + (idx * 100) + '%)';

        if (dotsWrap) {
            dotsWrap.querySelectorAll('.rawnaq-cs-slider-dot').forEach(function (dot, i) {
                var isActive = (i === idx);
                dot.classList.toggle('is-active', isActive);
                if (isActive) {
                    dot.setAttribute('aria-current', 'true');
                } else {
                    dot.removeAttribute('aria-current');
                }
            });
        }

        if (statusEl && total > 0) {
            statusEl.textContent = t('imageOf', 'Image %1$d of %2$d', idx + 1, total);
        }
    }

    function goToSlide(modal, index) {
        var images = modal._csImages || [];
        if (!images.length) {
            return;
        }
        var len = images.length;
        modal._csIndex = ((index % len) + len) % len;
        updateSliderPosition(modal);
    }

    function buildSlider(modal, project) {
        var slider = modal.querySelector('.rawnaq-cs-slider');
        var track = modal.querySelector('.rawnaq-cs-slider-track');
        var dotsWrap = modal.querySelector('.rawnaq-cs-slider-dots');
        var prevBtn = modal.querySelector('[data-cs-prev]');
        var nextBtn = modal.querySelector('[data-cs-next]');
        if (!track) {
            return;
        }

        if (slider) {
            slider.style.touchAction = 'pan-y';
        }

        // Live region for slider announcements
        var statusEl = modal.querySelector('.rawnaq-cs-slider-status');
        if (!statusEl && slider) {
            statusEl = document.createElement('div');
            statusEl.className = 'rawnaq-cs-slider-status screen-reader-text';
            statusEl.setAttribute('role', 'status');
            statusEl.setAttribute('aria-live', 'polite');
            statusEl.style.cssText = 'position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;';
            slider.appendChild(statusEl);
        }

        var rawImages = Array.isArray(project.gallery) ? project.gallery : [];
        if (!rawImages.length && project.image) {
            rawImages = [project.image];
        }

        var images = [];
        rawImages.forEach(function (item) {
            if (!item) {
                return;
            }
            if (typeof item === 'string') {
                var s = item.trim();
                if (s && s !== 'Array' && s !== '[object Object]') {
                    images.push(s);
                }
            } else if (typeof item === 'object') {
                var url = item.url || item.src || '';
                if (url && typeof url === 'string') {
                    var trimmed = url.trim();
                    if (trimmed && trimmed !== 'Array' && trimmed !== '[object Object]') {
                        images.push(trimmed);
                    }
                }
            }
        });

        images = images.filter(function (val, idx, self) {
            return self.indexOf(val) === idx;
        });

        track.innerHTML = '';
        if (dotsWrap) {
            dotsWrap.innerHTML = '';
        }
        modal._csImages = images;
        modal._csIndex = 0;

        if (!images.length) {
            var fallback = document.createElement('div');
            fallback.className = 'rawnaq-cs-modal-media-fallback';
            fallback.textContent = (project.title || '?').charAt(0);
            track.appendChild(fallback);
            if (slider) {
                slider.classList.add('is-empty');
            }
            if (prevBtn) {
                prevBtn.hidden = true;
            }
            if (nextBtn) {
                nextBtn.hidden = true;
            }
            if (dotsWrap) {
                dotsWrap.hidden = true;
            }
            if (statusEl) {
                statusEl.textContent = '';
            }
            return;
        }

        if (slider) {
            slider.classList.remove('is-empty');
        }

        var total = images.length;
        images.forEach(function (src, i) {
            var slide = document.createElement('div');
            slide.className = 'rawnaq-cs-slide';
            var img = document.createElement('img');
            img.src = src;
            img.alt = (project.title || '') + (total > 1 ? ' (' + (i + 1) + ')' : '');
            img.loading = (i === 0) ? 'eager' : 'lazy';
            slide.appendChild(img);
            track.appendChild(slide);

            if (dotsWrap) {
                var dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'rawnaq-cs-slider-dot' + (i === 0 ? ' is-active' : '');
                dot.setAttribute('aria-label', t('imageOf', 'Image %1$d of %2$d', i + 1, total));
                if (i === 0) {
                    dot.setAttribute('aria-current', 'true');
                }
                dot.addEventListener('click', function () {
                    goToSlide(modal, i);
                });
                dotsWrap.appendChild(dot);
            }
        });

        var multi = total > 1;
        if (prevBtn) {
            prevBtn.hidden = !multi;
        }
        if (nextBtn) {
            nextBtn.hidden = !multi;
        }
        if (dotsWrap) {
            dotsWrap.hidden = !multi;
        }

        updateSliderPosition(modal);
    }

    function openModal(root, project, trigger) {
        var state = rootStates.get(root);
        var modal = (state && state.modal) ? state.modal : root.querySelector('.rawnaq-cs-modal');
        if (!modal || !project) {
            return;
        }

        modal._csRoot = root;
        if (state) {
            state.activeProject = project;
            state.returnFocus = trigger || document.activeElement;
        }

        buildSlider(modal, project);

        var sectorEl = modal.querySelector('.rawnaq-cs-modal-sector');
        var titleEl = modal.querySelector('.rawnaq-cs-modal-title');
        var metaEl = modal.querySelector('.rawnaq-cs-modal-meta');
        var servicesEl = modal.querySelector('.rawnaq-cs-modal-services');
        var detailEl = modal.querySelector('.rawnaq-cs-modal-detail');
        var linkWrap = modal.querySelector('.rawnaq-cs-modal-link-wrap');
        var linkEl = modal.querySelector('.rawnaq-cs-modal-link');

        if (sectorEl) {
            sectorEl.textContent = project.sector || '';
        }
        if (titleEl) {
            titleEl.textContent = project.title || '';
        }
        if (metaEl) {
            metaEl.innerHTML = '';
            var rows = [
                [t('year', 'Year'), project.year],
                [t('scope', 'Scope'), project.size],
                [t('budget', 'Budget'), project.budget],
                [t('client', 'Client'), project.client]
            ];
            rows.forEach(function (row) {
                if (!row[1]) {
                    return;
                }
                var li = document.createElement('li');
                var span = document.createElement('span');
                span.textContent = row[0];
                li.appendChild(span);
                li.appendChild(document.createTextNode(' ' + row[1]));
                metaEl.appendChild(li);
            });
        }
        if (servicesEl) {
            servicesEl.innerHTML = '';
            var services = Array.isArray(project.services) ? project.services : [];
            services.forEach(function (s) {
                if (!s) {
                    return;
                }
                var tag = document.createElement('span');
                tag.className = 'rawnaq-cs-tag';
                tag.textContent = s;
                servicesEl.appendChild(tag);
            });
        }
        if (detailEl) {
            detailEl.textContent = project.detail || '';
        }
        if (linkWrap && linkEl) {
            if (project.link) {
                linkEl.setAttribute('href', project.link);
                linkWrap.hidden = false;
            } else {
                linkEl.removeAttribute('href');
                linkWrap.hidden = true;
            }
        }

        // Move modal to document.body with a comment placeholder
        if (!modal._csPlaceholder && modal.parentNode && modal.parentNode !== document.body) {
            var placeholder = document.createComment('rawnaq-cs-modal-placeholder');
            modal._csPlaceholder = placeholder;
            modal.parentNode.insertBefore(placeholder, modal);
            document.body.appendChild(modal);
        }

        // Scroll lock & scrollbar width
        var sbw = window.innerWidth - document.documentElement.clientWidth;
        document.documentElement.style.setProperty('--rawnaq-cs-sbw', sbw + 'px');
        document.documentElement.classList.add('rawnaq-cs-modal-open');
        document.body.classList.add('rawnaq-cs-modal-open');

        // Set inert on all other direct children of body
        var inertList = [];
        Array.prototype.forEach.call(document.body.children, function (child) {
            if (child !== modal && !child.contains(modal) && child.tagName !== 'SCRIPT' && child.tagName !== 'STYLE') {
                var hadInert = child.hasAttribute('inert');
                var hadAriaHidden = child.getAttribute('aria-hidden');
                inertList.push({ el: child, hadInert: hadInert, hadAriaHidden: hadAriaHidden });
                child.setAttribute('inert', '');
                child.setAttribute('aria-hidden', 'true');
            }
        });
        modal._csInertList = inertList;

        modal.hidden = false;
        activeModal = modal;

        var closeBtn = modal.querySelector('.rawnaq-cs-modal-close');
        if (closeBtn) {
            closeBtn.setAttribute('aria-label', t('close', 'Close'));
            closeBtn.focus();
        }
    }

    function closeModal(modalOrRoot) {
        var modal = null;
        var root = null;
        if (!modalOrRoot) {
            modal = activeModal;
            root = modal ? modal._csRoot : null;
        } else if (modalOrRoot.classList && modalOrRoot.classList.contains('rawnaq-cs-modal')) {
            modal = modalOrRoot;
            root = modal._csRoot;
        } else {
            root = modalOrRoot;
            var st = rootStates.get(root);
            modal = (st && st.modal) ? st.modal : activeModal;
        }

        if (!modal) {
            return;
        }

        modal.hidden = true;
        if (activeModal === modal) {
            activeModal = null;
        }

        // Restore inert direct children of body
        if (modal._csInertList) {
            modal._csInertList.forEach(function (item) {
                if (!item.hadInert) {
                    item.el.removeAttribute('inert');
                }
                if (item.hadAriaHidden === null) {
                    item.el.removeAttribute('aria-hidden');
                } else {
                    item.el.setAttribute('aria-hidden', item.hadAriaHidden);
                }
            });
            modal._csInertList = null;
        }

        // Unlock scroll
        document.documentElement.classList.remove('rawnaq-cs-modal-open');
        document.body.classList.remove('rawnaq-cs-modal-open');
        document.documentElement.style.removeProperty('--rawnaq-cs-sbw');

        // Put modal back before placeholder
        if (modal._csPlaceholder && modal._csPlaceholder.parentNode) {
            modal._csPlaceholder.parentNode.insertBefore(modal, modal._csPlaceholder);
            modal._csPlaceholder.parentNode.removeChild(modal._csPlaceholder);
            modal._csPlaceholder = null;
        }

        // Focus return
        var state = root ? rootStates.get(root) : null;
        var back = state ? state.returnFocus : null;
        if (state) {
            state.returnFocus = null;
        }
        if (back && typeof back.focus === 'function' && document.body.contains(back)) {
            back.focus();
        } else if (root) {
            var grid = root.querySelector('.rawnaq-cs-grid') || root;
            if (!grid.hasAttribute('tabindex')) {
                grid.setAttribute('tabindex', '-1');
            }
            grid.focus();
        }
    }

    function getFocusable(container) {
        var sel = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
        return Array.prototype.filter.call(container.querySelectorAll(sel), function (el) {
            return el.offsetWidth > 0 || el.offsetHeight > 0 || el === document.activeElement;
        });
    }

    function handleDocumentKeydown(e) {
        if (!activeModal || activeModal.hidden) {
            return;
        }

        if (e.key === 'Escape') {
            e.preventDefault();
            closeModal(activeModal);
            return;
        }

        var tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
        var isEditable = tag === 'input' || tag === 'textarea' || tag === 'select' || (e.target && e.target.isContentEditable);

        if (!isEditable) {
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                goToSlide(activeModal, (activeModal._csIndex || 0) - 1);
                return;
            }
            if (e.key === 'ArrowRight') {
                e.preventDefault();
                goToSlide(activeModal, (activeModal._csIndex || 0) + 1);
                return;
            }
        }

        if (e.key === 'Tab') {
            var focusable = getFocusable(activeModal);
            if (!focusable.length) {
                e.preventDefault();
                return;
            }
            var first = focusable[0];
            var last = focusable[focusable.length - 1];
            var active = document.activeElement;

            if (e.shiftKey) {
                if (active === first || !activeModal.contains(active)) {
                    e.preventDefault();
                    last.focus();
                }
            } else {
                if (active === last || !activeModal.contains(active)) {
                    e.preventDefault();
                    first.focus();
                }
            }
        }
    }

    function isAjaxGrid(root) {
        return root.getAttribute('data-cs-ajax') === '1'
            && window.rawnaqCaseStudy && rawnaqCaseStudy.ajaxUrl;
    }

    function removeAjaxError(root) {
        var err = root.querySelector('.rawnaq-cs-error');
        if (err) {
            err.remove();
        }
    }

    function showAjaxError(root, onRetry) {
        removeAjaxError(root);
        var errWrap = document.createElement('div');
        errWrap.className = 'rawnaq-cs-error';
        errWrap.setAttribute('role', 'alert');
        errWrap.style.cssText = 'margin:16px 0;text-align:center;color:#ef4444;';

        var p = document.createElement('p');
        p.className = 'rawnaq-cs-error-msg';
        p.textContent = t('loadError', 'Could not load projects. Please try again.');
        errWrap.appendChild(p);

        var retryBtn = document.createElement('button');
        retryBtn.type = 'button';
        retryBtn.className = 'rawnaq-cs-retry';
        retryBtn.textContent = t('retry', 'Retry');
        retryBtn.style.cssText = 'appearance:none;border:1px solid currentColor;background:transparent;color:inherit;font:inherit;font-size:0.85rem;font-weight:600;padding:6px 14px;border-radius:999px;cursor:pointer;margin-top:6px;';
        retryBtn.addEventListener('click', function () {
            removeAjaxError(root);
            if (typeof onRetry === 'function') {
                onRetry();
            }
        });
        errWrap.appendChild(retryBtn);

        var loadMoreWrap = root.querySelector('.rawnaq-cs-load-more-wrap');
        if (loadMoreWrap) {
            loadMoreWrap.parentNode.insertBefore(errWrap, loadMoreWrap);
        } else {
            var grid = root.querySelector('.rawnaq-cs-grid');
            if (grid) {
                grid.parentNode.insertBefore(errWrap, grid.nextSibling);
            } else {
                root.appendChild(errWrap);
            }
        }

        setLiveStatus(root, t('loadError', 'Could not load projects. Please try again.'));
    }

    function csAjaxFetch(root, opts) {
        opts = opts || {};
        var grid = root.querySelector('.rawnaq-cs-grid');
        if (!grid) {
            return;
        }

        var state = rootStates.get(root);
        if (!state) {
            return;
        }

        // Abort prior in-flight AJAX request
        if (state.ajaxAbortController) {
            state.ajaxAbortController.abort();
        }
        var controller = new AbortController();
        state.ajaxAbortController = controller;

        var filters = currentFilters(root);
        var page = opts.reset ? 1 : ((state.page || 1) + 1);
        if (opts.reset) {
            state.page = 1;
        }

        var body = new URLSearchParams();
        body.set('action', 'rawnaq_cs_query');
        body.set('ctx', root.getAttribute('data-cs-ctx') || '');
        body.set('paged', String(page));
        body.set('sector', filters.sector || '');
        body.set('year', filters.year || '');
        body.set('service', filters.service || '');

        var btn = root.querySelector('.rawnaq-cs-load-more');
        if (btn) {
            btn.classList.add('is-loading');
        }
        root.classList.add('is-cs-loading');
        grid.setAttribute('aria-busy', 'true');
        removeAjaxError(root);

        fetch(rawnaqCaseStudy.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
            signal: controller.signal
        }).then(function (r) {
            return r.json();
        }).then(function (res) {
            if (!res || !res.success) {
                showAjaxError(root, function () {
                    csAjaxFetch(root, opts);
                });
                return;
            }

            var html = res.data.html || '';
            if (opts.reset) {
                if (html.trim()) {
                    grid.innerHTML = html;
                } else {
                    grid.innerHTML = '<p class="rawnaq-cs-empty">' + t('noResults', 'No matching projects.') + '</p>';
                }
            } else {
                if (html.trim()) {
                    grid.insertAdjacentHTML('beforeend', html);
                }
            }

            state.page = res.data.page || page;
            state.hasMore = !!res.data.hasMore;

            var totalCards = grid.querySelectorAll('.rawnaq-cs-card').length;
            if (totalCards === 0) {
                setLiveStatus(root, t('noResults', 'No matching projects.'));
            } else {
                setLiveStatus(root, t('showing', 'Showing %d projects', totalCards));
            }

            var cfg = cfgOf(root);
            bindCards(root, cfg, state);
            bindDiscuss(root, state);

            var wrap = root.querySelector('.rawnaq-cs-load-more-wrap');
            if (btn) {
                btn.hidden = !state.hasMore;
            }
            if (wrap) {
                wrap.classList.toggle('is-hidden', !state.hasMore);
            }
        }).catch(function (err) {
            if (err && err.name === 'AbortError') {
                return;
            }
            showAjaxError(root, function () {
                csAjaxFetch(root, opts);
            });
        }).then(function () {
            if (state.ajaxAbortController === controller) {
                state.ajaxAbortController = null;
            }
            if (btn) {
                btn.classList.remove('is-loading');
            }
            root.classList.remove('is-cs-loading');
            grid.removeAttribute('aria-busy');
        });
    }

    function bindFilters(root, state) {
        var ajax = isAjaxGrid(root);
        var signal = state.abortController.signal;
        root.querySelectorAll('.rawnaq-cs-filters[data-filter]').forEach(function (row) {
            row.querySelectorAll('.rawnaq-cs-chip').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    row.querySelectorAll('.rawnaq-cs-chip').forEach(function (c) {
                        var active = (c === chip);
                        c.classList.toggle('is-active', active);
                        c.setAttribute('aria-pressed', active ? 'true' : 'false');
                    });
                    if (ajax) {
                        csAjaxFetch(root, { reset: true });
                    } else {
                        applyFilters(root);
                    }
                }, { signal: signal });
            });
        });
    }

    function bindLoadMore(root, state) {
        var btn = root.querySelector('.rawnaq-cs-load-more');
        if (!btn) {
            return;
        }
        var signal = state.abortController.signal;
        if (isAjaxGrid(root)) {
            btn.addEventListener('click', function () {
                csAjaxFetch(root, { reset: false });
            }, { signal: signal });
            return;
        }
        btn.addEventListener('click', function () {
            var chunk = parseInt(btn.getAttribute('data-load-chunk'), 10);
            if (!chunk || chunk < 1) {
                chunk = parseInt(cfgOf(root).loadChunk, 10) || 3;
            }
            var filters = currentFilters(root);
            var candidates = root.querySelectorAll('.rawnaq-cs-card.is-load-hidden');
            var revealed = 0;
            for (var i = 0; i < candidates.length && revealed < chunk; i++) {
                if (cardMatches(candidates[i], filters)) {
                    candidates[i].classList.remove('is-load-hidden');
                    revealed++;
                }
            }
            updateLoadMoreState(root);
            var visibleCount = root.querySelectorAll('.rawnaq-cs-card:not(.is-load-hidden):not(.is-hidden)').length;
            setLiveStatus(root, t('showing', 'Showing %d projects', visibleCount));
        }, { signal: signal });
    }

    function bindCards(root, cfg, state) {
        var action = cfg.clickAction || 'modal';
        var signal = state.abortController.signal;
        root.querySelectorAll('.rawnaq-cs-card').forEach(function (card) {
            if (card._csCardBound) {
                return;
            }
            card._csCardBound = true;

            var trigger = card.querySelector('.rawnaq-cs-card-trigger');
            if (trigger) {
                trigger.addEventListener('click', function (e) {
                    if (action === 'modal' || action === 'both') {
                        e.preventDefault();
                        openModal(root, parseProject(card), trigger);
                    } else if (action === 'link') {
                        var link = card.getAttribute('data-link');
                        if (link && trigger.tagName.toLowerCase() === 'button') {
                            e.preventDefault();
                            window.location.href = link;
                        }
                    }
                }, { signal: signal });
            }

            // Card clicks delegate to trigger except on discuss CTA and real links
            card.addEventListener('click', function (e) {
                if (e.target.closest('[data-cs-discuss]')) {
                    return;
                }
                var linkEl = e.target.closest('a');
                if (linkEl && !linkEl.classList.contains('rawnaq-cs-card-trigger')) {
                    return;
                }
                if (trigger && e.target !== trigger && !trigger.contains(e.target)) {
                    e.preventDefault();
                    trigger.click();
                }
            }, { signal: signal });
        });
    }

    function emitDiscuss(root, project, source) {
        root.dispatchEvent(new CustomEvent('rawnaq:case-study:discuss', {
            bubbles: true,
            detail: {
                project: project || {},
                source: source || 'modal',
                root: root,
                discussTarget: (cfgOf(root).discussTarget || 'auto')
            }
        }));
    }

    function bindDiscuss(root, state) {
        var signal = state.abortController.signal;
        root.querySelectorAll('[data-cs-discuss]').forEach(function (btn) {
            if (btn._csDiscussBound) {
                return;
            }
            btn._csDiscussBound = true;
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var card = btn.closest('.rawnaq-cs-card');
                var project = card ? parseProject(card) : (state.activeProject || {});
                var source = btn.classList.contains('rawnaq-cs-discuss-card') ? 'card' : 'modal';
                emitDiscuss(root, project, source);
            }, { signal: signal });
        });
    }

    function bindModal(root, state) {
        var modal = root.querySelector('.rawnaq-cs-modal');
        if (!modal) {
            return;
        }
        state.modal = modal;
        modal._csRoot = root;
        var signal = state.abortController.signal;

        modal.querySelectorAll('[data-cs-close]').forEach(function (el) {
            el.addEventListener('click', function () {
                closeModal(modal);
            }, { signal: signal });
        });

        var prevBtn = modal.querySelector('[data-cs-prev]');
        var nextBtn = modal.querySelector('[data-cs-next]');
        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                goToSlide(modal, (modal._csIndex || 0) - 1);
            }, { signal: signal });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                goToSlide(modal, (modal._csIndex || 0) + 1);
            }, { signal: signal });
        }

        // Pointer event touch swipe
        var slider = modal.querySelector('.rawnaq-cs-slider');
        if (slider) {
            slider.style.touchAction = 'pan-y';
            var pointerStartX = 0;
            var pointerStartY = 0;
            var pointerActive = false;

            slider.addEventListener('pointerdown', function (e) {
                if (e.pointerType === 'mouse' && e.button !== 0) {
                    return;
                }
                pointerActive = true;
                pointerStartX = e.clientX;
                pointerStartY = e.clientY;
            }, { signal: signal });

            slider.addEventListener('pointerup', function (e) {
                if (!pointerActive) {
                    return;
                }
                pointerActive = false;
                var diffX = e.clientX - pointerStartX;
                var diffY = e.clientY - pointerStartY;
                if (Math.abs(diffX) >= 40 && Math.abs(diffX) > Math.abs(diffY)) {
                    if (diffX < 0) {
                        goToSlide(modal, (modal._csIndex || 0) + 1);
                    } else {
                        goToSlide(modal, (modal._csIndex || 0) - 1);
                    }
                }
            }, { signal: signal });

            slider.addEventListener('pointercancel', function () {
                pointerActive = false;
            }, { signal: signal });
        }
    }

    function destroy(root) {
        if (!root) {
            return;
        }
        var state = rootStates.get(root);
        if (state) {
            if (activeModal && activeModal._csRoot === root) {
                closeModal(activeModal);
            }
            if (state.abortController) {
                state.abortController.abort();
            }
            if (state.ajaxAbortController) {
                state.ajaxAbortController.abort();
            }
            if (state.fadeTimers) {
                state.fadeTimers.forEach(clearTimeout);
            }
            rootStates.delete(root);
        }
        root.classList.remove('cs-bound');
    }

    function bindOne(root) {
        if (!root || rootStates.has(root)) {
            return;
        }
        root.classList.add('cs-bound');

        var modal = root.querySelector('.rawnaq-cs-modal');
        var state = {
            root: root,
            abortController: new AbortController(),
            ajaxAbortController: null,
            modal: modal,
            page: 1,
            hasMore: false,
            activeProject: null,
            returnFocus: null,
            fadeTimers: []
        };
        rootStates.set(root, state);

        var cfg = cfgOf(root);

        bindFilters(root, state);
        bindLoadMore(root, state);
        bindCards(root, cfg, state);
        bindModal(root, state);
        bindDiscuss(root, state);

        if (!isAjaxGrid(root)) {
            applyFilters(root);
        }
    }

    function initAll() {
        document.querySelectorAll('.rawnaq-case-study').forEach(bindOne);
    }

    function hookElementor() {
        if (!window.elementorFrontend || !window.elementorFrontend.hooks) {
            return;
        }
        if (elementorHooked) {
            return;
        }
        elementorHooked = true;
        window.elementorFrontend.hooks.addAction(
            'frontend/element_ready/rawnaq_case_study_grid.default',
            function ($scope) {
                var el = $scope && $scope[0] ? $scope[0].querySelector('.rawnaq-case-study') : null;
                if (!el) {
                    return;
                }
                var isEdit = !!(window.elementorFrontend.isEditMode && window.elementorFrontend.isEditMode());
                if (isEdit) {
                    destroy(el);
                    bindOne(el);
                } else {
                    if (!rootStates.has(el)) {
                        bindOne(el);
                    }
                }
            }
        );
    }

    function boot() {
        if (!keydownBound) {
            keydownBound = true;
            document.addEventListener('keydown', handleDocumentKeydown);
        }
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
