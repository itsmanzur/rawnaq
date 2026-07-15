/**
 * Floating Dock — proximity magnify with destroy/rebind + reduced-motion
 */
(function () {
    'use strict';

    var instances = [];
    var pointerBound = false;
    var ticking = false;
    var lastEvent = null;
    var elementorHooked = false;

    function prefersReducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function isMobile() {
        return window.innerWidth < 768;
    }

    function resetItem(item, baseSize) {
        item.style.transition = 'width 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), height 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
        item.style.width = baseSize + 'px';
        item.style.height = baseSize + 'px';
        var icon = item.querySelector('.rawnaq-dock-icon i, .rawnaq-dock-icon svg, .rawnaq-dock-icon .dashicons');
        if (icon) {
            icon.style.transition = 'font-size 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
            icon.style.fontSize = (baseSize * 0.5) + 'px';
        }
    }

    function destroyAll() {
        instances.forEach(function (inst) {
            if (inst.dock) {
                inst.dock.classList.remove('dock-bound');
                if (inst.onLeave) {
                    inst.dock.removeEventListener('mouseleave', inst.onLeave);
                }
                var items = inst.dock.querySelectorAll('.rawnaq-dock-item');
                items.forEach(function (item) {
                    item.style.width = '';
                    item.style.height = '';
                    item.style.transition = '';
                    var icon = item.querySelector('.rawnaq-dock-icon i, .rawnaq-dock-icon svg, .rawnaq-dock-icon .dashicons');
                    if (icon) {
                        icon.style.fontSize = '';
                        icon.style.transition = '';
                    }
                });
            }
        });
        instances = [];
    }

    function applyMagnify(e) {
        instances.forEach(function (inst) {
            if (!inst.enabled || !inst.dock) {
                return;
            }
            var items = inst.dock.querySelectorAll('.rawnaq-dock-item');
            var baseSize = inst.baseSize;
            var maxScale = inst.maxScale;
            var maxDistance = Math.max(70, baseSize * 1.8);

            items.forEach(function (item) {
                var rect = item.getBoundingClientRect();
                var cx = rect.left + rect.width / 2;
                var cy = rect.top + rect.height / 2;
                var distance = Math.hypot(e.clientX - cx, e.clientY - cy);

                if (distance < maxDistance) {
                    var ratio = (maxDistance - distance) / maxDistance;
                    var size = baseSize + (baseSize * (maxScale - 1) * ratio);
                    item.style.transition = 'none';
                    item.style.width = size + 'px';
                    item.style.height = size + 'px';
                    var icon = item.querySelector('.rawnaq-dock-icon i, .rawnaq-dock-icon svg, .rawnaq-dock-icon .dashicons');
                    if (icon) {
                        icon.style.transition = 'none';
                        icon.style.fontSize = (size * 0.5) + 'px';
                    }
                } else {
                    resetItem(item, baseSize);
                }
            });
        });
    }

    function onPointerMove(e) {
        lastEvent = e;
        if (ticking) {
            return;
        }
        ticking = true;
        window.requestAnimationFrame(function () {
            if (lastEvent) {
                applyMagnify(lastEvent);
            }
            ticking = false;
        });
    }

    function bindGlobalPointer() {
        if (pointerBound) {
            return;
        }
        window.addEventListener('pointermove', onPointerMove, { passive: true });
        pointerBound = true;
    }

    // Timezone helper to calculate target date time
    function getOffsetTime(timezoneStr) {
        var now = new Date();
        var offset = 6;
        var match = (timezoneStr || '').match(/UTC([+-]\d+(\.\d+)?)/);
        if (match && match[1]) {
            offset = parseFloat(match[1]);
        }
        var utc = now.getTime() + (now.getTimezoneOffset() * 60000);
        return new Date(utc + (3600000 * offset));
    }

    function checkIsOnline(sched, timezone) {
        var localTime = getOffsetTime(timezone);
        var dayNames = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        var currentDay = dayNames[localTime.getDay()];
        var todaySched = sched[currentDay];
        if (!todaySched || !todaySched.enabled) {
            return false;
        }

        var hrs = localTime.getHours();
        var mins = localTime.getMinutes();
        var currentMinutes = hrs * 60 + mins;

        var openParts = (todaySched.open || '09:00').split(':');
        var closeParts = (todaySched.close || '18:00').split(':');
        var openMinutes = (parseInt(openParts[0]) || 9) * 60 + (parseInt(openParts[1]) || 0);
        var closeMinutes = (parseInt(closeParts[0]) || 18) * 60 + (parseInt(closeParts[1]) || 0);

        return currentMinutes >= openMinutes && currentMinutes <= closeMinutes;
    }

    function buildWhatsAppUrl(num, text) {
        var base = "https://wa.me/" + num.replace(/[^0-9]/g, '');
        if (text) {
            base += "?text=" + encodeURIComponent(text);
        }
        return base;
    }

    function isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

    function escapeHtml(text) {
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text || '').replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function showQrModal(link) {
        var modal = document.querySelector('.rawnaq-wa-qr-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.className = 'rawnaq-wa-qr-modal';
            modal.innerHTML = '<div class="rawnaq-wa-qr-content">' +
                '<span class="rawnaq-wa-qr-close">×</span>' +
                '<h5>Scan to start chat</h5>' +
                '<p>Scan this QR code using your phone camera to begin WhatsApp messaging</p>' +
                '<div class="rawnaq-wa-qr-code"></div>' +
                '</div>';
            document.body.appendChild(modal);

            modal.querySelector('.rawnaq-wa-qr-close').addEventListener('click', function() {
                modal.classList.remove('is-show');
            });
            modal.addEventListener('click', function(e) {
                if (e.target === modal) { modal.classList.remove('is-show'); }
            });
        }

        var codeContainer = modal.querySelector('.rawnaq-wa-qr-code');
        codeContainer.innerHTML = '';

        if (typeof window.QRCode !== 'undefined') {
            new QRCode(codeContainer, {
                text: link,
                width: 180,
                height: 180
            });
        } else {
            codeContainer.innerHTML = '<p class="qr-error">QR code engine unavailable. Please copy link instead.</p>';
        }

        modal.classList.add('is-show');
    }

    function setupWhatsAppDock(root, cfg) {
        var isOnline = checkIsOnline(cfg.schedule, cfg.timezone);

        // Off-hours actions
        if (!isOnline) {
            if (cfg.offHoursBehavior === 'hide') {
                root.style.display = 'none';
                return;
            }
        }

        // Render WhatsApp-first dynamic components
        root.innerHTML = '';
        root.className += ' rawnaq-whatsapp-dock';

        // 1. Tooltip Greeting bubble
        if (cfg.greetingText && !sessionStorage.getItem('rawnaq_spt_greet_shown')) {
            var triggerDelay = (cfg.triggerDelay || 0) * 1000;
            var triggerScroll = cfg.triggerScroll || 0;

            var showGreet = function() {
                var greet = document.createElement('div');
                greet.className = 'rawnaq-wa-greet';
                greet.innerHTML = '<span class="rawnaq-wa-greet-close">×</span><p>' + escapeHtml(cfg.greetingText) + '</p>';
                root.appendChild(greet);
                sessionStorage.setItem('rawnaq_spt_greet_shown', '1');

                greet.querySelector('.rawnaq-wa-greet-close').addEventListener('click', function(e) {
                    e.stopPropagation();
                    greet.classList.add('is-fadeout');
                    setTimeout(function() { greet.remove(); }, 300);
                });
            };

            if (triggerScroll > 0) {
                var onScrollTrigger = function() {
                    var doc = document.documentElement;
                    var pct = (doc.scrollTop / (doc.scrollHeight - window.innerHeight)) * 100;
                    if (pct >= triggerScroll) {
                        showGreet();
                        window.removeEventListener('scroll', onScrollTrigger);
                    }
                };
                window.addEventListener('scroll', onScrollTrigger, { passive: true });
            } else {
                setTimeout(showGreet, triggerDelay);
            }
        }

        // 2. Secondary channels tray
        var tray = document.createElement('div');
        tray.className = 'rawnaq-wa-secondary-tray';
        var hasSec = false;

        if (cfg.secCall) {
            hasSec = true;
            tray.innerHTML += '<a href="tel:' + cfg.secCall + '" class="rawnaq-sec-btn call" title="Call Us">📞</a>';
        }
        if (cfg.secMessenger) {
            hasSec = true;
            tray.innerHTML += '<a href="https://m.me/' + cfg.secMessenger + '" target="_blank" rel="noopener" class="rawnaq-sec-btn msg" title="Messenger">💬</a>';
        }
        if (cfg.secEmail) {
            hasSec = true;
            tray.innerHTML += '<a href="mailto:' + cfg.secEmail + '" class="rawnaq-sec-btn email" title="Email Us">✉</a>';
        }
        if (cfg.secTelegram) {
            hasSec = true;
            tray.innerHTML += '<a href="https://t.me/' + cfg.secTelegram + '" target="_blank" rel="noopener" class="rawnaq-sec-btn tg" title="Telegram">✈</a>';
        }

        if (hasSec) {
            root.appendChild(tray);
        }

        // 3. Main Floating Button
        var mainBtn = document.createElement('button');
        mainBtn.type = 'button';
        var brandColor = '#25d366';
        var mainIcon = '💬';

        if (cfg.primaryChannel === 'call') {
            brandColor = '#3b82f6';
            mainIcon = '📞';
        } else if (cfg.primaryChannel === 'messenger') {
            brandColor = '#a855f7';
            mainIcon = '⚡';
        }

        mainBtn.className = 'rawnaq-wa-main-trigger' + (isOnline ? ' is-online' : ' is-offline');
        mainBtn.style.backgroundColor = brandColor;
        mainBtn.innerHTML = '<span class="main-icon">' + mainIcon + '</span>' + 
            '<span class="pulse-ring"></span>' +
            '<span class="online-dot"></span>' +
            (!isOnline && cfg.offHoursBehavior === 'offline_badge' ? '<span class="offline-badge">Off</span>' : '');
        
        root.appendChild(mainBtn);

        // 4. Modal Drawers
        var drawer = document.createElement('div');
        drawer.className = 'rawnaq-wa-drawer';
        drawer.innerHTML = '<div class="rawnaq-wa-drawer-header">' +
            '<h4>Connect with us</h4>' +
            '<span class="rawnaq-wa-drawer-close">×</span>' +
            '</div>' +
            '<div class="rawnaq-wa-agents-list"></div>';
        root.appendChild(drawer);

        var listContainer = drawer.querySelector('.rawnaq-wa-agents-list');
        var closeDrawer = drawer.querySelector('.rawnaq-wa-drawer-close');
        closeDrawer.addEventListener('click', function(e) {
            e.stopPropagation();
            drawer.classList.remove('is-open');
        });

        // Populate agents
        cfg.agents.forEach(function(a) {
            var agentEl = document.createElement('div');
            agentEl.className = 'rawnaq-wa-agent-row';
            var avatar = a.avatar || 'https://secure.gravatar.com/avatar/?s=80&d=mp';
            
            agentEl.innerHTML = '<img class="agent-avatar" src="' + avatar + '" alt="' + escapeHtml(a.name) + '" />' +
                '<div class="agent-info">' +
                '<span class="agent-name">' + escapeHtml(a.name) + '</span>' +
                '<span class="agent-role">' + escapeHtml(a.role) + '</span>' +
                '</div>' +
                '<span class="agent-connect-icon">➔</span>';
            
            agentEl.addEventListener('click', function() {
                var waLink = buildWhatsAppUrl(a.number, a.msg);
                if (cfg.qrFallback && !isMobileDevice()) {
                    showQrModal(waLink);
                } else {
                    window.open(waLink, '_blank', 'noopener');
                }
                drawer.classList.remove('is-open');
            });
            listContainer.appendChild(agentEl);
        });

        // Click actions trigger
        mainBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (!isOnline && cfg.offHoursBehavior === 'redirect' && cfg.offHoursRedirect) {
                window.location.href = cfg.offHoursRedirect;
                return;
            }

            if (hasSec) {
                tray.classList.toggle('is-open');
            }

            if (cfg.agents.length > 1) {
                drawer.classList.toggle('is-open');
            } else if (cfg.agents.length === 1) {
                var a = cfg.agents[0];
                var waLink = buildWhatsAppUrl(a.number, a.msg);
                if (cfg.qrFallback && !isMobileDevice()) {
                    showQrModal(waLink);
                } else {
                    window.open(waLink, '_blank', 'noopener');
                }
            }
        });
    }

    function initDock(dock) {
        if (!dock || dock.classList.contains('dock-bound')) {
            return;
        }
        dock.classList.add('dock-bound');

        var waDataStr = dock.getAttribute('data-wa-dock');
        var isWaMode = !!waDataStr;

        if (isWaMode) {
            setupWhatsAppDock(dock, JSON.parse(waDataStr));
            return;
        }

        var magnifyAttr = dock.getAttribute('data-magnify');
        var enabled = magnifyAttr !== '0' && !prefersReducedMotion() && !isMobile();
        var maxScale = parseFloat(dock.getAttribute('data-max-scale') || '1.6');
        var baseSize = parseInt(dock.getAttribute('data-base-size') || '48', 10);
        if (isNaN(maxScale) || maxScale < 1.1) {
            maxScale = 1.6;
        }
        if (isNaN(baseSize) || baseSize < 24) {
            baseSize = 48;
        }

        var onLeave = function () {
            if (!enabled) {
                return;
            }
            dock.querySelectorAll('.rawnaq-dock-item').forEach(function (item) {
                resetItem(item, baseSize);
            });
        };
        dock.addEventListener('mouseleave', onLeave);

        instances.push({
            dock: dock,
            enabled: enabled,
            maxScale: maxScale,
            baseSize: baseSize,
            onLeave: onLeave
        });
    }

    function initAll() {
        destroyAll();
        document.querySelectorAll('.rawnaq-dock-container').forEach(initDock);
        if (instances.some(function (i) { return i.enabled; })) {
            bindGlobalPointer();
        }
    }

    function hookElementor() {
        if (elementorHooked || !window.elementorFrontend || !elementorFrontend.hooks) {
            return;
        }
        elementorHooked = true;
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/rawnaq_floating_dock.default',
            function () {
                initAll();
            }
        );
    }

    function boot() {
        initAll();
        hookElementor();
        window.addEventListener('resize', function () {
            // Re-evaluate mobile / reduced-motion enable flags
            initAll();
        }, { passive: true });
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
