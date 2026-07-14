/**
 * 3D Mouse-Tilt Physics Engine - ULTRA SPEED & SMOOTH EDITION
 */
(function() {
    'use strict';

    function initTiltCards() {
        var cards = document.querySelectorAll('.rawnaq-tilt-card');
        
        cards.forEach(function(card) {
            var maxTilt = parseFloat(card.getAttribute('data-tilt-max')) || 15;
            var glow = card.querySelector('.rawnaq-tilt-glow');

            card.addEventListener('mouseenter', function() {
                // Remove transition during active track for lag-free physics
                card.style.transition = 'box-shadow 0.25s ease';
                if (glow) {
                    glow.style.transition = 'opacity 0.25s ease';
                }
            });

            card.addEventListener('mousemove', function(e) {
                var rect = card.getBoundingClientRect();
                var x = e.clientX - rect.left;
                var y = e.clientY - rect.top;

                var tiltX = (rect.height / 2 - y) / (rect.height / 2);
                var tiltY = (x - rect.width / 2) / (rect.width / 2);

                card.style.transform = 'rotateX(' + (tiltX * maxTilt) + 'deg) rotateY(' + (tiltY * maxTilt) + 'deg)';
                card.style.boxShadow = '0 20px 40px rgba(0, 0, 0, 0.18)';

                if (glow) {
                    glow.style.left = x + 'px';
                    glow.style.top = y + 'px';
                    glow.style.opacity = '1';
                }
            });

            card.addEventListener('mouseleave', function() {
                // Apply smooth spring-back transition on mouse leave
                card.style.transition = 'transform 0.5s cubic-bezier(0.25, 1, 0.5, 1), box-shadow 0.3s ease';
                card.style.transform = 'rotateX(0deg) rotateY(0deg)';
                card.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.08)';

                if (glow) {
                    glow.style.transition = 'opacity 0.5s ease';
                    glow.style.opacity = '0';
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTiltCards);
    } else {
        initTiltCards();
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (window.elementorFrontend) {
            elementorFrontend.hooks.addAction(
                'frontend/element_ready/rawnaq_tilt_card.default',
                function($scope) {
                    initTiltCards();
                }
            );
        }
    });

})();
