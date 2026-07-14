/**
 * Scroll-Sync Process Timeline Tracking - MULTI-INSTANCE ISOLATED
 */
(function() {
    'use strict';

    function initTimeline() {
        var wrappers = document.querySelectorAll('.rawnaq-timeline-wrapper');
        
        wrappers.forEach(function(wrap) {
            var activeLine = wrap.querySelector('.rawnaq-timeline-line-active');
            var items      = wrap.querySelectorAll('.rawnaq-timeline-item');
            
            var observerOptions = {
                root: null,
                rootMargin: '0px 0px -25% 0px',
                threshold: 0.1
            };

            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('item-active');
                    }
                });
            }, observerOptions);

            items.forEach(function(item) {
                observer.observe(item);
            });

            // Scoped multi-instance scroll calculations
            function calculateFill() {
                var rect = wrap.getBoundingClientRect();
                var viewportHeight = window.innerHeight;
                var viewportCenter = viewportHeight / 2;

                var passed = viewportCenter - rect.top;
                var progress = passed / rect.height;
                progress = Math.max(0, Math.min(1, progress));

                if (activeLine) {
                    activeLine.style.height = (progress * 100) + '%';
                }
            }

            window.addEventListener('scroll', calculateFill);
            window.addEventListener('resize', calculateFill);
            calculateFill(); // Initial check
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTimeline);
    } else {
        initTimeline();
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (window.elementorFrontend) {
            elementorFrontend.hooks.addAction(
                'frontend/element_ready/rawnaq_scroll_timeline.default',
                function($scope) {
                    initTimeline();
                }
            );
        }
    });

})();
