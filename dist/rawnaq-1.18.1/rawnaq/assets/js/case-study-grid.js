/**
 * Rawnaq Project Case-Study Grid — multi-filter, load-more, gallery-slider modal
 */
(function () {
    'use strict';

    var bound = false;

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

    function setMasonrySpans(root) {
        var grid = root.querySelector('.rawnaq-cs-grid.is-masonry');
        if (!grid) {
            return;
        }
        var rowH = 12;
        var gap = 16;
        grid.querySelectorAll('.rawnaq-cs-card:not(.is-hidden):not(.is-load-hidden)').forEach(function (card) {
            card.style.removeProperty('--cs-masonry-span');
            var h = card.getBoundingClientRect().height;
            var span = Math.max(8, Math.ceil((h + gap) / (rowH + gap)));
            card.style.setProperty('--cs-masonry-span', String(span));
        });
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

    function applyFilters(root) {
        var filters = currentFilters(root);
        root.querySelectorAll('.rawnaq-cs-card').forEach(function (card) {
            var match = cardMatches(card, filters);
            if (match) {
                card.classList.remove('is-fading');
                card.classList.remove('is-hidden');
            } else if (!card.classList.contains('is-hidden')) {
                card.classList.add('is-fading');
                window.setTimeout(function () {
                    if (card.classList.contains('is-fading')) {
                        card.classList.add('is-hidden');
                        card.classList.remove('is-fading');
                    }
                }, 180);
            }
        });
        updateLoadMoreState(root);
        window.setTimeout(function () {
            setMasonrySpans(root);
        }, 200);
    }

    function updateSliderPosition(modal) {
        var track = modal.querySelector('.rawnaq-cs-slider-track');
        if (!track) {
            return;
        }
        var idx = modal._csIndex || 0;
        track.style.transform = 'translateX(-' + (idx * 100) + '%)';
        modal.querySelectorAll('.rawnaq-cs-slider-dot').forEach(function (dot, i) {
            dot.classList.toggle('is-active', i === idx);
        });
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

        var images = Array.isArray(project.gallery) ? project.gallery.filter(Boolean) : [];
        if (!images.length && project.image) {
            images = [project.image];
        }

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
            return;
        }

        if (slider) {
            slider.classList.remove('is-empty');
        }

        images.forEach(function (src, i) {
            var slide = document.createElement('div');
            slide.className = 'rawnaq-cs-slide';
            var img = document.createElement('img');
            img.src = src;
            img.alt = '';
            slide.appendChild(img);
            track.appendChild(slide);

            if (dotsWrap) {
                var dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'rawnaq-cs-slider-dot' + (i === 0 ? ' is-active' : '');
                dot.setAttribute('aria-label', 'Image ' + (i + 1));
                dot.addEventListener('click', function () {
                    goToSlide(modal, i);
                });
                dotsWrap.appendChild(dot);
            }
        });

        var multi = images.length > 1;
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

    function openModal(root, project) {
        var modal = root.querySelector('.rawnaq-cs-modal');
        if (!modal || !project) {
            return;
        }

        var sectorEl = modal.querySelector('.rawnaq-cs-modal-sector');
        var titleEl = modal.querySelector('.rawnaq-cs-modal-title');
        var metaEl = modal.querySelector('.rawnaq-cs-modal-meta');
        var servicesEl = modal.querySelector('.rawnaq-cs-modal-services');
        var detailEl = modal.querySelector('.rawnaq-cs-modal-detail');
        var linkWrap = modal.querySelector('.rawnaq-cs-modal-link-wrap');
        var linkEl = modal.querySelector('.rawnaq-cs-modal-link');

        buildSlider(modal, project);

        if (sectorEl) {
            sectorEl.textContent = project.sector || '';
        }
        if (titleEl) {
            titleEl.textContent = project.title || '';
        }
        if (metaEl) {
            metaEl.innerHTML = '';
            var rows = [
                ['Year', project.year],
                ['Scope', project.size],
                ['Budget', project.budget],
                ['Client', project.client]
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

        modal.hidden = false;
        document.body.classList.add('rawnaq-cs-modal-open');
        root._csActiveProject = project;
        var closeBtn = modal.querySelector('.rawnaq-cs-modal-close');
        if (closeBtn) {
            closeBtn.focus();
        }
    }

    function closeModal(root) {
        var modal = root.querySelector('.rawnaq-cs-modal');
        if (!modal) {
            return;
        }
        modal.hidden = true;
        document.body.classList.remove('rawnaq-cs-modal-open');
    }

    function bindFilters(root) {
        root.querySelectorAll('.rawnaq-cs-filters[data-filter]').forEach(function (row) {
            row.querySelectorAll('.rawnaq-cs-chip').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    row.querySelectorAll('.rawnaq-cs-chip').forEach(function (c) {
                        var active = c === chip;
                        c.classList.toggle('is-active', active);
                        c.setAttribute('aria-selected', active ? 'true' : 'false');
                    });
                    applyFilters(root);
                });
            });
        });
    }

    function bindLoadMore(root) {
        var btn = root.querySelector('.rawnaq-cs-load-more');
        if (!btn) {
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
            window.setTimeout(function () {
                setMasonrySpans(root);
            }, 50);
        });
    }

    function bindCards(root, cfg) {
        var action = cfg.clickAction || 'modal';
        root.querySelectorAll('.rawnaq-cs-card').forEach(function (card) {
            if (card.tagName.toLowerCase() === 'a') {
                return;
            }

            function handle(e) {
                if (e && e.target && e.target.closest && e.target.closest('[data-cs-discuss]')) {
                    return;
                }
                if (action === 'modal' || action === 'both') {
                    if (e) {
                        e.preventDefault();
                    }
                    openModal(root, parseProject(card));
                } else if (action === 'link') {
                    var link = card.getAttribute('data-link');
                    if (link) {
                        window.location.href = link;
                    }
                }
            }

            card.addEventListener('click', handle);
            card.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    if (e.target && e.target.closest && e.target.closest('[data-cs-discuss]')) {
                        return;
                    }
                    e.preventDefault();
                    handle(e);
                }
            });
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

    function bindDiscuss(root) {
        root.querySelectorAll('[data-cs-discuss]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var card = btn.closest('.rawnaq-cs-card');
                var project = card ? parseProject(card) : (root._csActiveProject || {});
                var source = btn.classList.contains('rawnaq-cs-discuss-card') ? 'card' : 'modal';
                emitDiscuss(root, project, source);
            });
        });
    }

    function bindModal(root) {
        var modal = root.querySelector('.rawnaq-cs-modal');
        if (!modal) {
            return;
        }

        modal.querySelectorAll('[data-cs-close]').forEach(function (el) {
            el.addEventListener('click', function () {
                closeModal(root);
            });
        });

        var prevBtn = modal.querySelector('[data-cs-prev]');
        var nextBtn = modal.querySelector('[data-cs-next]');
        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                goToSlide(modal, (modal._csIndex || 0) - 1);
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                goToSlide(modal, (modal._csIndex || 0) + 1);
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hidden) {
                closeModal(root);
            }
        });
    }

    function bindOne(root) {
        if (!root || root.classList.contains('cs-bound')) {
            return;
        }
        root.classList.add('cs-bound');

        var cfg = cfgOf(root);

        bindFilters(root);
        bindLoadMore(root);
        bindCards(root, cfg);
        bindModal(root);
        bindDiscuss(root);

        applyFilters(root);
        setMasonrySpans(root);
        if (window.ResizeObserver) {
            var ro = new ResizeObserver(function () {
                setMasonrySpans(root);
            });
            ro.observe(root);
        }
    }

    function initAll() {
        document.querySelectorAll('.rawnaq-case-study').forEach(bindOne);
    }

    function hookElementor() {
        if (bound || !window.elementorFrontend || !elementorFrontend.hooks) {
            return;
        }
        bound = true;
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/rawnaq_case_study_grid.default',
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
