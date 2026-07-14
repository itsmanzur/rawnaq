/**
 * macOS Dock Proximity Magnification Math (Vanilla JS - SPRING ANIMATED)
 */
(function() {
    'use strict';

    function initDock() {
        var docks = document.querySelectorAll('.rawnaq-dock-container');
        
        docks.forEach(function(dock) {
            var items = dock.querySelectorAll('.rawnaq-dock-item');
            var baseSize = 48;
            var maxScale = 1.6;
            var maxDistance = 90;

            if (window.innerWidth < 768) {
                return;
            }

            document.addEventListener('mousemove', function(e) {
                items.forEach(function(item) {
                    var rect = item.getBoundingClientRect();
                    var cx = rect.left + rect.width / 2;
                    var cy = rect.top + rect.height / 2;

                    var dx = e.clientX - cx;
                    var dy = e.clientY - cy;
                    var distance = Math.hypot(dx, dy);

                    if (distance < maxDistance) {
                        var ratio = (maxDistance - distance) / maxDistance;
                        var size = baseSize + (baseSize * (maxScale - 1) * ratio);
                        
                        // Disable heavy transitions during active mouse tracking
                        item.style.transition = 'none';
                        item.style.width  = size + 'px';
                        item.style.height = size + 'px';
                        
                        var icon = item.querySelector('.dashicons');
                        if (icon) {
                            icon.style.transition = 'none';
                            icon.style.fontSize = (size * 0.5) + 'px';
                        }
                    } else {
                        // Spring back to base size
                        item.style.transition = 'width 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), height 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                        item.style.width  = baseSize + 'px';
                        item.style.height = baseSize + 'px';
                        
                        var icon = item.querySelector('.dashicons');
                        if (icon) {
                            icon.style.transition = 'font-size 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                            icon.style.fontSize = (baseSize * 0.5) + 'px';
                        }
                    }
                });
            });

            dock.addEventListener('mouseleave', function() {
                items.forEach(function(item) {
                    item.style.transition = 'width 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), height 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                    item.style.width  = baseSize + 'px';
                    item.style.height = baseSize + 'px';
                    var icon = item.querySelector('.dashicons');
                    if (icon) {
                        icon.style.transition = 'font-size 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                        icon.style.fontSize = (baseSize * 0.5) + 'px';
                    }
                });
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDock);
    } else {
        initDock();
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (window.elementorFrontend) {
            elementorFrontend.hooks.addAction(
                'frontend/element_ready/rawnaq_floating_dock.default',
                function($scope) {
                    initDock();
                }
            );
        }
    });

})();
