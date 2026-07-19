/**
 * Rawnaq cross-module bridge — Case-Study discuss + scroll highlight
 */
(function () {
    'use strict';

    function buildDiscussMessage(project) {
        var p = project || {};
        var bits = [];
        if (p.sector) {
            bits.push(p.sector);
        }
        if (p.year) {
            bits.push(p.year);
        }
        var meta = bits.length ? ' (' + bits.join(', ') + ')' : '';
        var title = p.title || 'this project';
        return 'Re: ' + title + meta + ' — I’d like to discuss this project.';
    }

    function findSmartForm() {
        return document.querySelector('.rawnaq-smart-form form.rawnaq-smart-form-el') ||
            document.querySelector('.rawnaq-smart-form form') ||
            document.querySelector('form.rawnaq-smart-form-el');
    }

    function setField(form, name, value) {
        if (!form || !value) {
            return;
        }
        var el = form.querySelector('[name="' + name + '"]');
        if (el) {
            el.value = value;
            try {
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            } catch (e) { /* ignore */ }
        }
    }

    function prefillSmartForm(project) {
        var form = findSmartForm();
        if (!form) {
            return false;
        }
        var msg = buildDiscussMessage(project);
        setField(form, 'sf_message', msg);
        setField(form, 'sf_project', project.title || '');
        setField(form, 'sf_project_id', project.id || (project.postId ? String(project.postId) : ''));
        try {
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } catch (e) {
            form.scrollIntoView(true);
        }
        var messageEl = form.querySelector('[name="sf_message"]');
        if (messageEl && typeof messageEl.focus === 'function') {
            messageEl.focus();
        }
        return true;
    }

    function openDock(project) {
        if (typeof window.rawnaqDockOpen !== 'function') {
            return false;
        }
        return !!window.rawnaqDockOpen({ message: buildDiscussMessage(project) });
    }

    function resolveDiscussTarget(detail) {
        var target = (detail && detail.discussTarget) || 'auto';
        if (detail && detail.root) {
            try {
                var cfg = JSON.parse(detail.root.getAttribute('data-cs') || '{}');
                if (cfg.discussTarget) {
                    target = cfg.discussTarget;
                }
            } catch (e) { /* ignore */ }
        }
        return target;
    }

    function onDiscuss(e) {
        var detail = (e && e.detail) || {};
        var project = detail.project || {};
        var target = resolveDiscussTarget(detail);
        if (target === 'off') {
            return;
        }
        if (target === 'form') {
            prefillSmartForm(project);
            return;
        }
        if (target === 'dock') {
            openDock(project);
            return;
        }
        // auto
        if (!prefillSmartForm(project)) {
            openDock(project);
        }
    }

    function parseCardProject(card) {
        try {
            return JSON.parse(card.getAttribute('data-project') || '{}');
        } catch (e) {
            return {};
        }
    }

    function cardMatchesScroll(card, detail) {
        var p = parseCardProject(card);
        var id = (detail.projectId || '').toString();
        var slug = (detail.projectSlug || '').toString();
        var title = (detail.title || '').toString().trim().toLowerCase();

        if (id) {
            if (String(p.id || '') === id) {
                return true;
            }
            if (String(p.postId || '') === id || ('post-' + String(p.postId || '')) === id) {
                return true;
            }
            if ((card.getAttribute('data-project-id') || '') === id) {
                return true;
            }
        }
        if (slug) {
            if (String(p.slug || '') === slug) {
                return true;
            }
            if ((card.getAttribute('data-project-slug') || '') === slug) {
                return true;
            }
        }
        if (!id && !slug && title) {
            return String(p.title || '').trim().toLowerCase() === title;
        }
        return false;
    }

    var lastHighlightKey = '';
    var lastScrolledKey = '';

    function sourceStillInView(detail) {
        // Prefer not yanking the page while the user is still reading Story/Timeline.
        var sel = detail.module === 'story'
            ? '.rawnaq-story-chapter.is-active'
            : '.rawnaq-timeline-item';
        var nodes = document.querySelectorAll(sel);
        var vh = window.innerHeight;
        for (var i = 0; i < nodes.length; i++) {
            var el = nodes[i];
            if (detail.module === 'timeline') {
                var id = detail.projectId || '';
                var slug = detail.projectSlug || '';
                if (id && el.getAttribute('data-project-id') !== id) {
                    continue;
                }
                if (slug && el.getAttribute('data-project-slug') !== slug) {
                    continue;
                }
                if (!id && !slug && typeof detail.index === 'number') {
                    var wrap = el.closest('.rawnaq-timeline-wrapper');
                    var items = wrap ? wrap.querySelectorAll('.rawnaq-timeline-item:not(.tl-hidden)') : [];
                    if (items[detail.index] !== el) {
                        continue;
                    }
                }
            }
            var r = el.getBoundingClientRect();
            var visible = r.bottom > 80 && r.top < vh - 80;
            if (visible) {
                return true;
            }
        }
        return false;
    }

    function shouldNudgeCard(card) {
        var rect = card.getBoundingClientRect();
        var vh = window.innerHeight;
        // Card is far from the viewport — highlight only, never jump the page.
        if (rect.top > vh * 1.25 || rect.bottom < -vh * 0.35) {
            return false;
        }
        return rect.bottom < 72 || rect.top > vh - 72;
    }

    function onScrollActive(e) {
        var detail = (e && e.detail) || {};
        if (!detail.projectId && !detail.projectSlug && !detail.title) {
            document.querySelectorAll('.rawnaq-cs-card.is-related').forEach(function (c) {
                c.classList.remove('is-related');
            });
            lastHighlightKey = '';
            return;
        }

        // Title-only matches are too noisy (timeline steps without a project id).
        if (!detail.projectId && !detail.projectSlug) {
            return;
        }

        var key = (detail.projectId || '') + '|' + (detail.projectSlug || '');
        var matched = null;
        document.querySelectorAll('.rawnaq-case-study .rawnaq-cs-card').forEach(function (card) {
            var hit = cardMatchesScroll(card, detail);
            card.classList.toggle('is-related', hit);
            if (hit && !matched) {
                matched = card;
            }
        });

        if (matched && matched.classList.contains('is-load-hidden')) {
            matched.classList.remove('is-load-hidden');
        }

        if (!matched || key === lastScrolledKey) {
            lastHighlightKey = key;
            return;
        }

        // Don't steal scroll while the story/timeline source is still on screen.
        if (sourceStillInView(detail)) {
            lastHighlightKey = key;
            return;
        }

        if (shouldNudgeCard(matched)) {
            lastScrolledKey = key;
            try {
                matched.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } catch (err) {
                matched.scrollIntoView(true);
            }
        }
        lastHighlightKey = key;
    }

    document.addEventListener('rawnaq:case-study:discuss', onDiscuss);
    document.addEventListener('rawnaq:scroll:active', onScrollActive);
})();
