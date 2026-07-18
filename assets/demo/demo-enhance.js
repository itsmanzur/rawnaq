/**
 * Rawnaq demo page — interaction layer (nav, toasts, try-it actions)
 */
(function () {
    'use strict';

    function $(sel, root) {
        return (root || document).querySelector(sel);
    }
    function $$(sel, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(sel));
    }

    /* ── Toast ── */
    var toastEl = null;
    var toastTimer = null;

    function ensureToast() {
        if (toastEl) {
            return toastEl;
        }
        toastEl = document.createElement('div');
        toastEl.className = 'demo-toast';
        toastEl.setAttribute('role', 'status');
        toastEl.hidden = true;
        document.body.appendChild(toastEl);
        return toastEl;
    }

    function toast(msg, ms) {
        var el = ensureToast();
        el.textContent = msg;
        el.hidden = false;
        el.classList.add('is-show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () {
            el.classList.remove('is-show');
            setTimeout(function () {
                el.hidden = true;
            }, 280);
        }, ms || 3200);
    }

    /* ── Active nav ── */
    function bindNavSpy() {
        var links = $$('.demo-nav a[href^="#"]');
        var map = {};
        links.forEach(function (a) {
            var id = a.getAttribute('href').slice(1);
            if (id) {
                map[id] = a;
            }
        });
        var sections = $$('.module[id]');
        if (!sections.length || !window.IntersectionObserver) {
            return;
        }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                var id = entry.target.id;
                links.forEach(function (a) {
                    a.classList.toggle('is-active', a === map[id]);
                });
            });
        }, { rootMargin: '-35% 0px -50% 0px', threshold: 0.01 });
        sections.forEach(function (s) {
            io.observe(s);
        });
    }

    /* ── Section reveal ── */
    function bindReveals() {
        var mods = $$('.module');
        if (!window.IntersectionObserver) {
            mods.forEach(function (m) {
                m.classList.add('is-in');
            });
            return;
        }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-in');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
        mods.forEach(function (m) {
            m.classList.add('demo-reveal');
            io.observe(m);
        });
    }

    /* ── Hero parallax ── */
    function bindHeroParallax() {
        var hero = $('.hero');
        var brand = $('.brand');
        if (!hero || !brand || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }
        hero.addEventListener('pointermove', function (e) {
            var r = hero.getBoundingClientRect();
            var x = ((e.clientX - r.left) / r.width - 0.5) * 12;
            var y = ((e.clientY - r.top) / r.height - 0.5) * 8;
            brand.style.transform = 'translate(' + x.toFixed(1) + 'px,' + y.toFixed(1) + 'px)';
        });
        hero.addEventListener('pointerleave', function () {
            brand.style.transform = '';
        });
    }

    /* ── Sync feedback ── */
    function bindBridgeFeedback() {
        document.addEventListener('rawnaq:scroll:active', function (e) {
            var d = e.detail || {};
            if (!d.projectId && !d.projectSlug) {
                return;
            }
            var card = $('.rawnaq-cs-card.is-related .rawnaq-cs-title');
            var title = card ? card.textContent.trim() : (d.title || d.projectId);
            if (title) {
                toast('Synced → ' + title);
            }
        });
        document.addEventListener('rawnaq:case-study:discuss', function (e) {
            var p = (e.detail && e.detail.project) || {};
            toast('Discuss ready — form prefills for “' + (p.title || 'project') + '”');
        });
    }

    /* ── Try-it actions ── */
    function openFirstCaseModal() {
        var card = $('.rawnaq-cs-card:not(.is-hidden):not(.is-load-hidden)');
        if (card) {
            card.click();
            toast('Modal open — try Discuss this project');
        }
    }

    function filterCases(sector) {
        var root = $('#cs-demo');
        if (!root) {
            return;
        }
        var chip = root.querySelector('.rawnaq-cs-filters[data-filter="sector"] .rawnaq-cs-chip[data-sector="' + sector + '"]');
        if (chip) {
            chip.click();
            toast(sector ? ('Filter: ' + sector) : 'Showing all sectors');
        }
    }

    function pulseCard(id) {
        var card = $('.rawnaq-cs-card[data-project-id="' + id + '"]');
        if (!card) {
            return;
        }
        $$('.rawnaq-cs-card.is-related').forEach(function (c) {
            c.classList.remove('is-related');
        });
        card.classList.add('is-related', 'demo-pulse');
        setTimeout(function () {
            card.classList.remove('demo-pulse');
        }, 900);
        try {
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } catch (err) { /* ignore */ }
        var t = card.querySelector('.rawnaq-cs-title');
        toast('Highlight → ' + (t ? t.textContent.trim() : id));
    }

    function openDockDemo() {
        if (typeof window.rawnaqDockOpen === 'function') {
            window.rawnaqDockOpen({
                message: 'Hi from the Rawnaq interactive demo — I’d like a walkthrough of the modules.'
            });
            toast('Dock WhatsApp opened with a demo message');
        } else {
            toast('Dock script not ready yet');
        }
    }

    function focusForm() {
        var form = $('.rawnaq-smart-form form');
        var msg = form && form.querySelector('[name="sf_message"]');
        if (form) {
            try {
                form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } catch (e) {
                form.scrollIntoView(true);
            }
        }
        if (msg) {
            msg.value = 'Re: Riverfront Civic Center (Civic, 2024) — I’d like to discuss this project.';
            msg.focus();
            toast('Message field filled — edit and submit');
        }
    }

    function wobbleTilt() {
        var card = $('.rawnaq-tilt-card');
        if (!card) {
            return;
        }
        card.classList.add('demo-tilt-wobble');
        setTimeout(function () {
            card.classList.remove('demo-tilt-wobble');
        }, 700);
        toast('Move your pointer over the card for live tilt');
    }

    function bindTryButtons() {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-demo-action]');
            if (!btn) {
                return;
            }
            e.preventDefault();
            var action = btn.getAttribute('data-demo-action');
            var arg = btn.getAttribute('data-demo-arg') || '';
            if (action === 'open-modal') {
                openFirstCaseModal();
            } else if (action === 'filter') {
                filterCases(arg);
            } else if (action === 'pulse') {
                pulseCard(arg || 'post-1');
            } else if (action === 'dock') {
                openDockDemo();
            } else if (action === 'form') {
                focusForm();
            } else if (action === 'tilt') {
                wobbleTilt();
            } else if (action === 'load-more') {
                var more = $('.rawnaq-cs-load-more');
                if (more) {
                    more.click();
                    toast('Loaded more projects');
                }
            }
        });
    }

    /* ── Bento cell click expand ── */
    function bindBentoClicks() {
        $$('.rawnaq-bento-cell').forEach(function (cell) {
            cell.style.cursor = 'pointer';
            cell.setAttribute('tabindex', '0');
            cell.addEventListener('click', function () {
                var title = cell.querySelector('.rawnaq-bento-title');
                cell.classList.toggle('demo-bento-on');
                toast(title ? ('Bento: ' + title.textContent.trim()) : 'Bento cell toggled');
            });
            cell.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    cell.click();
                }
            });
        });
    }

    function boot() {
        bindNavSpy();
        bindReveals();
        bindHeroParallax();
        bindBridgeFeedback();
        bindTryButtons();
        bindBentoClicks();
        setTimeout(function () {
            toast('Tip: use the Try it chips under each module', 4000);
        }, 1200);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
