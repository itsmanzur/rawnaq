/**
 * Floating Dock — classic magnify + WhatsApp Contact Mode
 */
(function () {
    'use strict';

    var instances = [];
    var waInstances = [];
    var pointerBound = false;
    var ticking = false;
    var lastEvent = null;
    var elementorHooked = false;
    var docClickBound = false;

    var ICONS = {
        whatsapp: '<svg viewBox="0 0 24 24" width="26" height="26" aria-hidden="true" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>',
        call: '<svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" fill="currentColor"><path d="M6.62 10.79a15.15 15.15 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24c1.12.37 2.33.57 3.57.57a1 1 0 011 1V21a1 1 0 01-1 1C10.4 22 2 13.6 2 3a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.45.57 3.57a1 1 0 01-.25 1.02l-2.2 2.2z"/></svg>',
        messenger: '<svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" fill="currentColor"><path d="M12 2C6.36 2 2 6.13 2 11.7c0 2.91 1.19 5.44 3.14 7.17V22l3.45-1.89c.95.26 1.97.4 3.01.4 5.64 0 10.2-4.13 10.2-9.81C21.8 6.13 17.64 2 12 2zm1.01 13.19l-2.61-2.78-5.09 2.78L10.5 9l2.68 2.78L18.2 9l-5.19 6.19z"/></svg>',
        email: '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="currentColor"><path d="M20 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2zm0 4l-8 5L4 8V6l8 5 8-5v2z"/></svg>',
        telegram: '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="currentColor"><path d="M9.78 15.45l-.4 4.04c.57 0 .82-.24 1.12-.53l2.7-2.58 5.6 4.1c1.03.57 1.76.27 2.03-.95L22.9 4.87c.3-1.28-.46-1.78-1.45-1.47L2.5 9.67c-1.24.48-1.22 1.17-.21 1.48l5.3 1.66 12.3-7.75c.58-.38 1.11-.17.67.21L9.78 15.45z"/></svg>'
    };

    function prefersReducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function isMobileViewport() {
        return window.innerWidth < 768;
    }

    function isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

    function sanitizePhone(num) {
        return String(num || '').replace(/[^\d]/g, '');
    }

    function isValidWaNumber(num) {
        var n = sanitizePhone(num);
        return n.length >= 8 && n.length <= 15;
    }

    function escapeHtml(text) {
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text || '').replace(/[&<>"']/g, function (m) { return map[m]; });
    }

    function parseWaCfg(dock) {
        var raw = dock.getAttribute('data-wa-dock');
        if (!raw) {
            return null;
        }
        try {
            return JSON.parse(raw);
        } catch (e1) {
            try {
                return JSON.parse(decodeURIComponent(raw));
            } catch (e2) {
                return null;
            }
        }
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

    function destroyClassic() {
        instances.forEach(function (inst) {
            if (inst.dock && inst.onLeave) {
                inst.dock.removeEventListener('mouseleave', inst.onLeave);
            }
            if (inst.dock) {
                inst.dock.classList.remove('dock-bound');
                inst.dock.querySelectorAll('.rawnaq-dock-item').forEach(function (item) {
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

    function destroyWa() {
        waInstances.forEach(function (inst) {
            if (inst.scrollHandler) {
                window.removeEventListener('scroll', inst.scrollHandler);
            }
            if (inst.dock) {
                inst.dock.classList.remove('dock-bound', 'is-expanded');
                inst.dock.style.display = '';
            }
        });
        waInstances = [];
        var modal = document.querySelector('.rawnaq-wa-qr-modal');
        if (modal) {
            modal.classList.remove('is-show');
        }
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

    function getOffsetTime(timezoneStr) {
        var now = new Date();
        var offset = 6;
        var match = String(timezoneStr || '').match(/UTC([+-]\d+(\.\d+)?)/);
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
        var todaySched = sched && sched[currentDay];
        if (!todaySched || !todaySched.enabled) {
            return false;
        }

        var currentMinutes = localTime.getHours() * 60 + localTime.getMinutes();
        var openParts = String(todaySched.open || '09:00').split(':');
        var closeParts = String(todaySched.close || '18:00').split(':');
        var openMinutes = (parseInt(openParts[0], 10) || 9) * 60 + (parseInt(openParts[1], 10) || 0);
        var closeMinutes = (parseInt(closeParts[0], 10) || 18) * 60 + (parseInt(closeParts[1], 10) || 0);

        return currentMinutes >= openMinutes && currentMinutes <= closeMinutes;
    }

    function nextOpenHint(sched, timezone) {
        var localTime = getOffsetTime(timezone);
        var dayNames = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        for (var i = 0; i < 7; i++) {
            var d = new Date(localTime.getTime());
            d.setDate(d.getDate() + i);
            var key = dayNames[d.getDay()];
            var day = sched && sched[key];
            if (!day || !day.enabled) {
                continue;
            }
            var open = day.open || '09:00';
            if (i === 0) {
                var parts = open.split(':');
                var openMin = (parseInt(parts[0], 10) || 9) * 60 + (parseInt(parts[1], 10) || 0);
                var nowMin = localTime.getHours() * 60 + localTime.getMinutes();
                if (nowMin < openMin) {
                    return 'Opens today at ' + open;
                }
                continue;
            }
            var labels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            return 'Opens ' + labels[d.getDay()] + ' at ' + open;
        }
        return 'Currently offline';
    }

    function buildWhatsAppUrl(num, text) {
        var cleaned = sanitizePhone(num);
        if (!isValidWaNumber(cleaned)) {
            return '';
        }
        var base = 'https://wa.me/' + cleaned;
        if (text) {
            base += '?text=' + encodeURIComponent(text);
        }
        return base;
    }

    function getMessageVars(cfg) {
        var ctx = (cfg && cfg.pageContext) ? cfg.pageContext : {};
        var now = new Date();
        var pageTitle = (typeof document !== 'undefined' && document.title) ? document.title : '';
        if (!pageTitle && ctx.pageTitle) {
            pageTitle = ctx.pageTitle;
        }
        // Prefer product name on Woo product pages when available
        if (ctx.productName) {
            pageTitle = ctx.productName;
        }
        var url = (typeof window !== 'undefined' && window.location && window.location.href)
            ? window.location.href
            : (ctx.url || '');
        if (ctx.productUrl) {
            url = ctx.productUrl;
        }
        var siteTitle = ctx.siteTitle || '';
        var vars = {
            pageTitle: pageTitle,
            title: pageTitle,
            url: url,
            currentURL: url,
            currentUrl: url,
            siteTitle: siteTitle,
            siteName: siteTitle,
            date: now.toLocaleDateString(),
            time: now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
            productName: ctx.productName || '',
            price: ctx.price || '',
            sku: ctx.sku || '',
            productUrl: ctx.productUrl || '',
            productId: ctx.productId || ''
        };
        return vars;
    }

    function resolveMessageTemplate(template, cfg) {
        if (!template) {
            return '';
        }
        var vars = getMessageVars(cfg);
        return String(template).replace(/\{([a-zA-Z0-9_]+)\}/g, function (match, key) {
            return Object.prototype.hasOwnProperty.call(vars, key) ? String(vars[key]) : match;
        });
    }

    function trackClick(type, cfgOrEnabled) {
        var enabled = true;
        if (typeof cfgOrEnabled === 'boolean') {
            enabled = cfgOrEnabled;
        } else if (cfgOrEnabled && cfgOrEnabled.trackClicks === false) {
            enabled = false;
        }
        if (!enabled || !type) {
            return;
        }
        if (typeof window.rawnaqDock === 'undefined' || !rawnaqDock.ajaxUrl || !rawnaqDock.nonce) {
            return;
        }
        try {
            var body = new FormData();
            body.append('action', 'rawnaq_dock_click');
            body.append('nonce', rawnaqDock.nonce);
            body.append('type', type);
            if (navigator.sendBeacon) {
                navigator.sendBeacon(rawnaqDock.ajaxUrl, body);
            } else {
                fetch(rawnaqDock.ajaxUrl, {
                    method: 'POST',
                    body: body,
                    credentials: 'same-origin',
                    keepalive: true
                });
            }
        } catch (err) {
            // ignore tracking failures
        }
    }

    function showChatChooser(link, mode, cfg) {
        mode = mode || 'choice';
        var modal = document.querySelector('.rawnaq-wa-qr-modal');
        // Recreate if an older modal markup is still on the page
        if (modal && !modal.querySelector('.rawnaq-wa-chooser-lead')) {
            modal.remove();
            modal = null;
        }
        if (!modal) {
            modal = document.createElement('div');
            modal.className = 'rawnaq-wa-qr-modal';
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-modal', 'true');
            modal.setAttribute('aria-labelledby', 'rawnaq-wa-chooser-title');
            modal.innerHTML =
                '<div class="rawnaq-wa-qr-content">' +
                '<button type="button" class="rawnaq-wa-qr-close" aria-label="Close">×</button>' +
                '<h5 id="rawnaq-wa-chooser-title">Start WhatsApp chat</h5>' +
                '<p class="rawnaq-wa-chooser-lead">Pick the easiest way for you.</p>' +
                '<a class="rawnaq-wa-qr-open" href="#" target="_blank" rel="noopener">' +
                '<span class="rawnaq-wa-qr-open-icon" aria-hidden="true"></span>' +
                'Continue on this device' +
                '</a>' +
                '<div class="rawnaq-wa-chooser-divider"><span>or</span></div>' +
                '<div class="rawnaq-wa-qr-section">' +
                '<p class="rawnaq-wa-qr-hint">Scan with your phone camera</p>' +
                '<div class="rawnaq-wa-qr-code"></div>' +
                '</div>' +
                '</div>';
            document.body.appendChild(modal);

            modal.querySelector('.rawnaq-wa-qr-close').addEventListener('click', function () {
                modal.classList.remove('is-show');
            });
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    modal.classList.remove('is-show');
                }
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && modal.classList.contains('is-show')) {
                    modal.classList.remove('is-show');
                }
            });
        }

        var codeContainer = modal.querySelector('.rawnaq-wa-qr-code');
        var openLink = modal.querySelector('.rawnaq-wa-qr-open');
        var qrSection = modal.querySelector('.rawnaq-wa-qr-section');
        var divider = modal.querySelector('.rawnaq-wa-chooser-divider');
        var title = modal.querySelector('#rawnaq-wa-chooser-title');
        var lead = modal.querySelector('.rawnaq-wa-chooser-lead');

        openLink.href = link;
        openLink.onclick = function () {
            trackClick('web', cfg);
            modal.classList.remove('is-show');
        };

        // Track that the chooser was shown (desktop QR/Web options)
        trackClick('chooser', cfg);

        if (mode === 'qr') {
            title.textContent = 'Scan to chat on WhatsApp';
            lead.textContent = 'Use your phone camera, or open WhatsApp on this device below.';
            openLink.classList.remove('is-primary');
            openLink.classList.add('is-secondary');
            openLink.innerHTML = 'Open WhatsApp on this device';
            if (divider) {
                divider.style.display = '';
            }
            if (qrSection) {
                qrSection.style.display = '';
            }
            // Move QR above the link for qr-first layout
            modal.classList.add('is-qr-first');
            modal.classList.remove('is-web-first');
        } else {
            title.textContent = 'Start WhatsApp chat';
            lead.textContent = 'Continue here, or scan the code with your phone.';
            openLink.classList.add('is-primary');
            openLink.classList.remove('is-secondary');
            openLink.innerHTML =
                '<span class="rawnaq-wa-qr-open-icon" aria-hidden="true"></span>Continue on this device';
            if (divider) {
                divider.style.display = '';
            }
            if (qrSection) {
                qrSection.style.display = '';
            }
            modal.classList.add('is-web-first');
            modal.classList.remove('is-qr-first');
        }

        codeContainer.innerHTML = '';
        if (typeof window.QRCode !== 'undefined') {
            new QRCode(codeContainer, {
                text: link,
                width: 160,
                height: 160
            });
        } else {
            codeContainer.innerHTML = '<p class="qr-error">QR unavailable — use the button above.</p>';
        }

        modal.classList.add('is-show');
        openLink.focus();
    }

    function openChannel(cfg, agent, opts) {
        opts = opts || {};
        var channel = cfg.primaryChannel || 'whatsapp';

        if (channel === 'call') {
            var phone = sanitizePhone((agent && agent.number) || cfg.secCall || '');
            if (phone) {
                window.location.href = 'tel:+' + phone;
            }
            return;
        }

        if (channel === 'messenger') {
            var user = (cfg.secMessenger || '').replace(/^@/, '');
            if (user) {
                window.open('https://m.me/' + encodeURIComponent(user), '_blank', 'noopener');
            }
            return;
        }

        var num = agent && agent.number ? agent.number : '';
        var rawMsg = agent && agent.msg ? agent.msg : '';
        if (!num && cfg.agents && cfg.agents[0]) {
            num = cfg.agents[0].number;
            rawMsg = cfg.agents[0].msg || rawMsg;
        }
        if (!rawMsg) {
            rawMsg = cfg.defaultMsg || '';
        }
        if (opts.message) {
            rawMsg = opts.message;
        }
        var msg = resolveMessageTemplate(rawMsg, cfg);
        var waLink = buildWhatsAppUrl(num, msg);
        if (!waLink) {
            return;
        }

        // Mobile: always open app/link directly
        if (isMobileDevice()) {
            trackClick('web', cfg);
            window.open(waLink, '_blank', 'noopener');
            return;
        }

        // Desktop: choice (default) | web | qr
        var desktop = cfg.desktopAction || (cfg.qrFallback === false ? 'web' : 'choice');
        if (desktop === 'web') {
            trackClick('web', cfg);
            window.open(waLink, '_blank', 'noopener');
            return;
        }
        showChatChooser(waLink, desktop === 'qr' ? 'qr' : 'choice', cfg);
    }

    function setupWhatsAppDock(root, cfg) {
        var isOnline = checkIsOnline(cfg.schedule || {}, cfg.timezone);
        var agents = Array.isArray(cfg.agents) ? cfg.agents.filter(function (a) {
            return a && (a.number || cfg.primaryChannel !== 'whatsapp');
        }) : [];

        if (!isOnline && cfg.offHoursBehavior === 'hide') {
            root.style.display = 'none';
            root.classList.add('dock-bound');
            waInstances.push({ dock: root });
            return;
        }

        root.innerHTML = '';
        root.classList.add('rawnaq-whatsapp-dock');
        root.style.display = '';

        var inst = { dock: root, scrollHandler: null, drawer: null, tray: null };

        // Greeting bubble (once per session)
        if (cfg.greetingText && !sessionStorage.getItem('rawnaq_wa_greet_shown')) {
            var triggerDelay = (cfg.triggerDelay || 0) * 1000;
            var triggerScroll = cfg.triggerScroll || 0;

            var showGreet = function () {
                if (sessionStorage.getItem('rawnaq_wa_greet_shown')) {
                    return;
                }
                var greet = document.createElement('div');
                greet.className = 'rawnaq-wa-greet';
                greet.innerHTML =
                    '<button type="button" class="rawnaq-wa-greet-close" aria-label="Dismiss">×</button>' +
                    '<p>' + escapeHtml(cfg.greetingText) + '</p>';
                root.appendChild(greet);
                sessionStorage.setItem('rawnaq_wa_greet_shown', '1');
                greet.querySelector('.rawnaq-wa-greet-close').addEventListener('click', function (e) {
                    e.stopPropagation();
                    greet.classList.add('is-fadeout');
                    setTimeout(function () { greet.remove(); }, 300);
                });
            };

            if (triggerScroll > 0) {
                inst.scrollHandler = function () {
                    var doc = document.documentElement;
                    var max = Math.max(doc.scrollHeight - window.innerHeight, 1);
                    var pct = (window.pageYOffset / max) * 100;
                    if (pct >= triggerScroll) {
                        showGreet();
                        window.removeEventListener('scroll', inst.scrollHandler);
                        inst.scrollHandler = null;
                    }
                };
                window.addEventListener('scroll', inst.scrollHandler, { passive: true });
            } else {
                setTimeout(showGreet, triggerDelay);
            }
        }

        // Secondary tray
        var tray = document.createElement('div');
        tray.className = 'rawnaq-wa-secondary-tray';
        tray.setAttribute('role', 'group');
        tray.setAttribute('aria-label', 'Other contact channels');
        var hasSec = false;

        if (cfg.secCall) {
            hasSec = true;
            tray.innerHTML +=
                '<a href="tel:+' + sanitizePhone(cfg.secCall) + '" class="rawnaq-sec-btn call" aria-label="Call us">' +
                ICONS.call + '</a>';
        }
        if (cfg.secMessenger) {
            hasSec = true;
            tray.innerHTML +=
                '<a href="https://m.me/' + encodeURIComponent(String(cfg.secMessenger).replace(/^@/, '')) +
                '" target="_blank" rel="noopener" class="rawnaq-sec-btn msg" aria-label="Facebook Messenger">' +
                ICONS.messenger + '</a>';
        }
        if (cfg.secEmail) {
            hasSec = true;
            tray.innerHTML +=
                '<a href="mailto:' + escapeHtml(cfg.secEmail) +
                '" class="rawnaq-sec-btn email" aria-label="Email us">' + ICONS.email + '</a>';
        }
        if (cfg.secTelegram) {
            hasSec = true;
            tray.innerHTML +=
                '<a href="https://t.me/' + encodeURIComponent(String(cfg.secTelegram).replace(/^@/, '')) +
                '" target="_blank" rel="noopener" class="rawnaq-sec-btn tg" aria-label="Telegram">' +
                ICONS.telegram + '</a>';
        }
        if (hasSec) {
            root.appendChild(tray);
            inst.tray = tray;
            tray.addEventListener('click', function (e) {
                if (e.target.closest('.rawnaq-sec-btn')) {
                    trackClick('secondary', cfg);
                }
            });
        }

        // Main trigger
        var mainBtn = document.createElement('button');
        mainBtn.type = 'button';
        var brandColor = '#25d366';
        var mainIcon = ICONS.whatsapp;
        var ariaLabel = 'Chat on WhatsApp';

        if (cfg.primaryChannel === 'call') {
            brandColor = '#3b82f6';
            mainIcon = ICONS.call;
            ariaLabel = 'Call us';
        } else if (cfg.primaryChannel === 'messenger') {
            brandColor = '#0084ff';
            mainIcon = ICONS.messenger;
            ariaLabel = 'Message on Messenger';
        }

        mainBtn.className = 'rawnaq-wa-main-trigger' + (isOnline ? ' is-online' : ' is-offline');
        mainBtn.style.backgroundColor = brandColor;
        mainBtn.setAttribute('aria-label', ariaLabel);
        mainBtn.setAttribute('aria-expanded', 'false');
        mainBtn.innerHTML =
            '<span class="main-icon">' + mainIcon + '</span>' +
            (prefersReducedMotion() || !isOnline ? '' : '<span class="pulse-ring" aria-hidden="true"></span>') +
            '<span class="online-dot" aria-hidden="true"></span>' +
            (!isOnline && cfg.offHoursBehavior === 'offline_badge'
                ? '<span class="offline-badge" title="' + escapeHtml(nextOpenHint(cfg.schedule, cfg.timezone)) + '">Off</span>'
                : '');

        root.appendChild(mainBtn);

        // Agent drawer
        var drawer = document.createElement('div');
        drawer.className = 'rawnaq-wa-drawer';
        drawer.setAttribute('role', 'dialog');
        drawer.setAttribute('aria-label', 'Choose an agent');
        drawer.innerHTML =
            '<div class="rawnaq-wa-drawer-header">' +
            '<h4>Connect with us</h4>' +
            '<button type="button" class="rawnaq-wa-drawer-close" aria-label="Close">×</button>' +
            '</div>' +
            (!isOnline
                ? '<p class="rawnaq-wa-offline-note">' + escapeHtml(nextOpenHint(cfg.schedule, cfg.timezone)) + '</p>'
                : '') +
            '<div class="rawnaq-wa-agents-list"></div>' +
            '<div class="rawnaq-wa-lead-form" hidden></div>';
        root.appendChild(drawer);
        inst.drawer = drawer;

        drawer.querySelector('.rawnaq-wa-drawer-close').addEventListener('click', function (e) {
            e.stopPropagation();
            closePanels();
        });

        var listContainer = drawer.querySelector('.rawnaq-wa-agents-list');
        var leadFormWrap = drawer.querySelector('.rawnaq-wa-lead-form');

        function buildLeadForm() {
            if (!leadFormWrap) {
                return;
            }
            var note = cfg.offHoursFormNote || 'We are offline right now. Leave a message and we will reply by email.';
            leadFormWrap.hidden = false;
            leadFormWrap.innerHTML =
                '<p class="rawnaq-wa-lead-note">' + escapeHtml(note) + '</p>' +
                '<label><span>Name</span><input type="text" class="rawnaq-wa-lead-name" autocomplete="name" required /></label>' +
                '<label><span>Email</span><input type="email" class="rawnaq-wa-lead-email" autocomplete="email" required /></label>' +
                '<label><span>Message</span><textarea class="rawnaq-wa-lead-msg" rows="3" required></textarea></label>' +
                '<button type="button" class="rawnaq-wa-lead-submit">Send message</button>' +
                '<p class="rawnaq-wa-lead-status" hidden></p>';
            leadFormWrap.querySelector('.rawnaq-wa-lead-submit').addEventListener('click', function () {
                var name = (leadFormWrap.querySelector('.rawnaq-wa-lead-name').value || '').trim();
                var email = (leadFormWrap.querySelector('.rawnaq-wa-lead-email').value || '').trim();
                var msg = (leadFormWrap.querySelector('.rawnaq-wa-lead-msg').value || '').trim();
                var status = leadFormWrap.querySelector('.rawnaq-wa-lead-status');
                if (!name || !email || !msg) {
                    status.hidden = false;
                    status.textContent = 'Please fill in all fields.';
                    return;
                }
                var to = cfg.offHoursEmail || cfg.secEmail || '';
                trackClick('offline', cfg);
                if (to) {
                    var subject = encodeURIComponent('Website offline lead — ' + name);
                    var body = encodeURIComponent('Name: ' + name + '\nEmail: ' + email + '\n\n' + msg);
                    window.location.href = 'mailto:' + encodeURIComponent(to) + '?subject=' + subject + '&body=' + body;
                }
                status.hidden = false;
                status.textContent = to ? 'Opening your email app…' : 'Thanks — we received your details.';
                leadFormWrap.querySelector('.rawnaq-wa-lead-name').value = '';
                leadFormWrap.querySelector('.rawnaq-wa-lead-email').value = '';
                leadFormWrap.querySelector('.rawnaq-wa-lead-msg').value = '';
            });
        }

        if (!isOnline && cfg.offHoursBehavior === 'lead_form') {
            listContainer.hidden = true;
            buildLeadForm();
        } else {
            agents.forEach(function (a) {
            if (!isValidWaNumber(a.number) && (cfg.primaryChannel || 'whatsapp') === 'whatsapp') {
                return;
            }
            var agentEl = document.createElement('button');
            agentEl.type = 'button';
            agentEl.className = 'rawnaq-wa-agent-row';
            var avatar = a.avatar || 'https://secure.gravatar.com/avatar/?s=80&d=mp';
            agentEl.innerHTML =
                '<img class="agent-avatar" src="' + escapeHtml(avatar) + '" alt="" width="40" height="40" loading="lazy" />' +
                '<div class="agent-info">' +
                '<span class="agent-name">' + escapeHtml(a.name || 'Agent') + '</span>' +
                '<span class="agent-role">' + escapeHtml(a.role || '') + '</span>' +
                '</div>' +
                '<span class="agent-connect-icon" aria-hidden="true">→</span>';
            agentEl.addEventListener('click', function () {
                trackClick('agent', cfg);
                openChannel(cfg, a);
                closePanels();
            });
            listContainer.appendChild(agentEl);
        });
        }

        function closePanels() {
            drawer.classList.remove('is-open');
            if (tray) {
                tray.classList.remove('is-open');
            }
            root.classList.remove('is-expanded');
            mainBtn.setAttribute('aria-expanded', 'false');
        }

        function openPanels() {
            if (hasSec) {
                tray.classList.add('is-open');
            }
            if ((!isOnline && cfg.offHoursBehavior === 'lead_form') ||
                (agents.length > 1 && (cfg.primaryChannel || 'whatsapp') === 'whatsapp')) {
                drawer.classList.add('is-open');
            }
            root.classList.add('is-expanded');
            mainBtn.setAttribute('aria-expanded', 'true');
        }

        mainBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            trackClick('fab', cfg);
            if (!isOnline && cfg.offHoursBehavior === 'redirect' && cfg.offHoursRedirect) {
                trackClick('offline', cfg);
                window.location.href = cfg.offHoursRedirect;
                return;
            }

            if (!isOnline && cfg.offHoursBehavior === 'lead_form') {
                var panelsOpenLead = drawer.classList.contains('is-open');
                if (panelsOpenLead) {
                    closePanels();
                } else {
                    openPanels();
                }
                return;
            }

            var panelsOpen = drawer.classList.contains('is-open') || (tray && tray.classList.contains('is-open'));
            if (panelsOpen) {
                // Second click on FAB while open → primary action
                if ((cfg.primaryChannel || 'whatsapp') === 'whatsapp' && agents.length > 1) {
                    closePanels();
                    return;
                }
                closePanels();
                openChannel(cfg, agents[0] || null);
                return;
            }

            if ((cfg.primaryChannel || 'whatsapp') === 'whatsapp' && agents.length > 1) {
                openPanels();
                return;
            }

            if (hasSec) {
                openPanels();
                return;
            }

            openChannel(cfg, agents[0] || null);
        });

        waInstances.push(inst);
        root.classList.add('dock-bound');
    }

    function initClassicDock(dock) {
        var magnifyAttr = dock.getAttribute('data-magnify');
        var enabled = magnifyAttr !== '0' && !prefersReducedMotion() && !isMobileViewport();
        var maxScale = parseFloat(dock.getAttribute('data-max-scale') || '1.6');
        var baseSize = parseInt(dock.getAttribute('data-base-size') || '48', 10);
        var trackEnabled = dock.getAttribute('data-track-clicks') !== '0';
        if (isNaN(maxScale) || maxScale < 1.1) {
            maxScale = 1.6;
        }
        if (isNaN(baseSize) || baseSize < 24) {
            baseSize = 48;
        }

        dock.addEventListener('click', function (e) {
            if (e.target.closest('.rawnaq-dock-item')) {
                trackClick('classic', trackEnabled);
            }
        });

        var onLeave = function () {
            if (!enabled) {
                return;
            }
            dock.querySelectorAll('.rawnaq-dock-item').forEach(function (item) {
                resetItem(item, baseSize);
            });
        };
        dock.addEventListener('mouseleave', onLeave);
        dock.classList.add('dock-bound');

        instances.push({
            dock: dock,
            enabled: enabled,
            maxScale: maxScale,
            baseSize: baseSize,
            onLeave: onLeave
        });
    }

    function initDock(dock) {
        if (!dock || dock.classList.contains('dock-bound')) {
            return;
        }

        var cfg = parseWaCfg(dock);
        if (cfg && cfg.whatsappMode) {
            setupWhatsAppDock(dock, cfg);
            return;
        }

        initClassicDock(dock);
    }

    function initAll(force) {
        if (force) {
            destroyClassic();
            destroyWa();
            document.querySelectorAll('.rawnaq-dock-container').forEach(function (d) {
                d.classList.remove('dock-bound');
            });
        }
        document.querySelectorAll('.rawnaq-dock-container').forEach(initDock);
        if (instances.some(function (i) { return i.enabled; })) {
            bindGlobalPointer();
        }
    }

    function onDocClick(e) {
        waInstances.forEach(function (inst) {
            if (!inst.dock || inst.dock.contains(e.target)) {
                return;
            }
            if (inst.drawer) {
                inst.drawer.classList.remove('is-open');
            }
            if (inst.tray) {
                inst.tray.classList.remove('is-open');
            }
            inst.dock.classList.remove('is-expanded');
            var btn = inst.dock.querySelector('.rawnaq-wa-main-trigger');
            if (btn) {
                btn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function hookElementor() {
        if (elementorHooked || !window.elementorFrontend || !elementorFrontend.hooks) {
            return;
        }
        elementorHooked = true;
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/rawnaq_floating_dock.default',
            function ($scope) {
                var dock = $scope && $scope[0] ? $scope[0].querySelector('.rawnaq-dock-container') : null;
                if (dock) {
                    dock.classList.remove('dock-bound');
                    // Remove previous WA instance for this dock
                    waInstances = waInstances.filter(function (inst) {
                        return inst.dock !== dock;
                    });
                    initDock(dock);
                    if (instances.some(function (i) { return i.enabled; })) {
                        bindGlobalPointer();
                    }
                } else {
                    initAll(true);
                }
            }
        );
    }

    function boot() {
        initAll(true);
        hookElementor();
        if (!docClickBound) {
            document.addEventListener('click', onDocClick);
            docClickBound = true;
        }
        var resizeTimer = null;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                // Only re-eval classic magnify; avoid wiping WA UI
                destroyClassic();
                document.querySelectorAll('.rawnaq-dock-container:not(.rawnaq-whatsapp-dock-mode)').forEach(function (d) {
                    d.classList.remove('dock-bound');
                    initDock(d);
                });
                if (instances.some(function (i) { return i.enabled; })) {
                    bindGlobalPointer();
                }
            }, 200);
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

    window.rawnaqFloatingDockBoot = function () { initAll(true); };

    /**
     * Open WhatsApp dock with an optional one-shot message override.
     * @param {{ message?: string }} opts
     * @returns {boolean}
     */
    window.rawnaqDockOpen = function (opts) {
        opts = opts || {};
        var root = document.querySelector('.rawnaq-dock-container.rawnaq-whatsapp-dock-mode[data-wa-dock]');
        if (!root) {
            root = document.querySelector('[data-wa-dock]');
        }
        if (!root) {
            return false;
        }
        var cfg = parseWaCfg(root);
        if (!cfg || cfg.whatsappMode === false) {
            return false;
        }
        var agents = Array.isArray(cfg.agents) ? cfg.agents.filter(function (a) {
            return a && a.number;
        }) : [];
        openChannel(cfg, agents[0] || null, { message: opts.message || '' });
        return true;
    };
})();
