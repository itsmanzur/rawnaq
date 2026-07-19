(function () {
    'use strict';

    var instances = [];
    var elementorHooked = false;
    var reduceMotion = false;

    try {
        reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    } catch (e) {
        reduceMotion = false;
    }

    function setActive(inst, index) {
        if (!inst || !inst.chapters.length) {
            return;
        }
        var i = Math.max(0, Math.min(index, inst.chapters.length - 1));
        if (inst.active === i) {
            return;
        }
        inst.active = i;

        inst.chapters.forEach(function (ch, idx) {
            ch.classList.toggle('is-active', idx === i);
        });
        inst.medias.forEach(function (m, idx) {
            var on = idx === i;
            m.classList.toggle('is-active', on);
            // Play the active chapter's video, pause the rest.
            var vid = m.querySelector('video.rawnaq-story-video');
            if (vid) {
                if (on && !reduceMotion) {
                    var p = vid.play();
                    if (p && p.catch) { p.catch(function () {}); }
                } else {
                    vid.pause();
                }
            }
        });
        inst.dots.forEach(function (d, idx) {
            d.classList.toggle('is-active', idx === i);
            d.setAttribute('aria-current', idx === i ? 'true' : 'false');
        });

        // Reflect the active chapter in the URL hash for deep-linking (no scroll jump).
        var activeCh = inst.chapters[i];
        if (activeCh && activeCh.id && window.history && history.replaceState) {
            try {
                history.replaceState(null, '', '#' + activeCh.id);
            } catch (e) { /* ignore */ }
        }

        var caption = inst.captionEl;
        if (caption) {
            var text = inst.chapters[i].getAttribute('data-caption') || '';
            caption.textContent = text;
            caption.hidden = !text;
        }

        var ch = inst.chapters[i];
        var titleEl = ch ? ch.querySelector('h3') : null;
        inst.root.dispatchEvent(new CustomEvent('rawnaq:scroll:active', {
            bubbles: true,
            detail: {
                module: 'story',
                index: i,
                projectId: ch ? (ch.getAttribute('data-project-id') || '') : '',
                projectSlug: ch ? (ch.getAttribute('data-project-slug') || '') : '',
                title: titleEl ? (titleEl.textContent || '').trim() : ''
            }
        }));
    }

    function destroyAll() {
        instances.forEach(function (inst) {
            if (inst.observer) {
                inst.observer.disconnect();
            }
            if (inst.root) {
                inst.root.classList.remove('story-bound');
            }
        });
        instances = [];
    }

    function initOne(root) {
        if (!root || root.classList.contains('story-bound')) {
            return;
        }
        root.classList.add('story-bound');

        var chapters = Array.prototype.slice.call(root.querySelectorAll('.rawnaq-story-chapter'));
        var medias = Array.prototype.slice.call(root.querySelectorAll('.rawnaq-story-media'));
        var dots = Array.prototype.slice.call(root.querySelectorAll('.rawnaq-story-dot'));
        var captionEl = root.querySelector('.rawnaq-story-caption');

        if (!chapters.length) {
            return;
        }

        var inst = {
            root: root,
            chapters: chapters,
            medias: medias,
            dots: dots,
            captionEl: captionEl,
            active: -1,
            observer: null
        };

        function goToChapter(idx) {
            var target = chapters[idx];
            if (!target) {
                return;
            }
            var top = target.getBoundingClientRect().top + window.pageYOffset - 80;
            window.scrollTo({
                top: top,
                behavior: reduceMotion ? 'auto' : 'smooth'
            });
            setActive(inst, idx);
        }

        dots.forEach(function (dot, idx) {
            dot.addEventListener('click', function () {
                goToChapter(idx);
            });
            // Keyboard: arrow keys move between chapters, Home/End jump to ends.
            dot.addEventListener('keydown', function (e) {
                var next = null;
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                    next = Math.min(dots.length - 1, idx + 1);
                } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                    next = Math.max(0, idx - 1);
                } else if (e.key === 'Home') {
                    next = 0;
                } else if (e.key === 'End') {
                    next = dots.length - 1;
                } else {
                    return;
                }
                e.preventDefault();
                if (dots[next]) {
                    dots[next].focus();
                }
                goToChapter(next);
            });
        });

        setActive(inst, 0);

        // Deep-link: if the URL hash targets a chapter in this story, jump to it.
        if (window.location.hash && window.location.hash.length > 1) {
            var hashId = window.location.hash.slice(1);
            for (var hi = 0; hi < chapters.length; hi++) {
                if (chapters[hi].id === hashId) {
                    window.setTimeout((function (targetIdx) {
                        return function () { goToChapter(targetIdx); };
                    })(hi), 60);
                    break;
                }
            }
        }

        if (typeof IntersectionObserver === 'undefined') {
            instances.push(inst);
            return;
        }

        var ratios = {};
        inst.observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                var idx = chapters.indexOf(entry.target);
                if (idx < 0) {
                    return;
                }
                ratios[idx] = entry.isIntersecting ? entry.intersectionRatio : 0;
            });
            var best = 0;
            var bestRatio = -1;
            Object.keys(ratios).forEach(function (key) {
                var k = parseInt(key, 10);
                if (ratios[k] > bestRatio) {
                    bestRatio = ratios[k];
                    best = k;
                }
            });
            if (bestRatio > 0) {
                setActive(inst, best);
            }
        }, {
            root: null,
            rootMargin: '-20% 0px -35% 0px',
            threshold: [0.15, 0.35, 0.55, 0.75]
        });

        chapters.forEach(function (ch) {
            inst.observer.observe(ch);
        });

        instances.push(inst);
    }

    function initAll() {
        destroyAll();
        document.querySelectorAll('.rawnaq-story').forEach(initOne);
    }

    function hookElementor() {
        if (elementorHooked || !window.elementorFrontend || !elementorFrontend.hooks) {
            return;
        }
        elementorHooked = true;
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/rawnaq_scroll_story.default',
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
