/**
 * Rawnaq Admin — v2 redesign
 * Drop-in replacement for assets/js/admin.js (works with the existing PHP markup).
 * Keeps every original behaviour: tab routing, module toggles, AJAX save
 * (rawnaq_save_modules), dock counter reset (rawnaq_dock_reset_clicks),
 * dock status override, CRM / webhook tests, and documentation cheat sheets.
 */
(function ($) {
	'use strict';

	/* ------------------------------------------------------------------ icons */
	var I = {
		home: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z"/></svg>',
		grid: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg>',
		book: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5V5a2 2 0 0 1 2-2h14v16H6.5A2.5 2.5 0 0 0 4 21.5v-2z"/><path d="M8 7h8M8 11h6"/></svg>',
		chart: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 6-6"/></svg>',
		cpu: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="5" width="14" height="14" rx="2"/><rect x="9" y="9" width="6" height="6" rx="1"/><path d="M9 1v4M15 1v4M9 19v4M15 19v4M1 9h4M1 15h4M19 9h4M19 15h4"/></svg>',
		search: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
		sun: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>',
		moon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>',
		check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
		alert: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>',
		bolt: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 4 14h7l-1 8 9-12h-7z"/></svg>',
		click: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 9 5 12 1.8-5.2L21 14z"/><path d="M7.2 2.2 8 5.1M5.1 8 2.2 7.2M14 4.1 12 6.2M6.2 12l-2.1 2"/></svg>',
		layers: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 2 10 5-10 5L2 7z"/><path d="m2 17 10 5 10-5M2 12l10 5 10-5"/></svg>',
		code: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 18 6-6-6-6M8 6l-6 6 6 6"/></svg>',
		wp: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M5 8.5 9 20l3-8 3 8 4-11.5M3.5 8.5h4M15 8.5h5"/></svg>',
		eye: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>',
		eyeOff: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.9 4.2A10 10 0 0 1 12 4c6.4 0 10 8 10 8a17 17 0 0 1-2.2 3.2M6.6 6.6A17 17 0 0 0 2 12s3.6 8 10 8a9.7 9.7 0 0 0 5.4-1.6M2 2l20 20M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>',
		chevron: '<svg class="rq-doc-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>',
		arrow: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
		copy: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"/></svg>',
		save: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>',
		toggle: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="10" rx="5"/><circle cx="16" cy="12" r="2.5"/></svg>',
		plug: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 2v6M15 2v6M6 8h12v4a6 6 0 0 1-12 0zM12 18v4"/></svg>',
		help: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3M12 17h.01"/></svg>'
	};

	var TABS = [
		{ id: 'welcome', label: 'Dashboard', icon: 'home' },
		{ id: 'modules', label: 'Elements', icon: 'grid' },
		{ id: 'docs', label: 'Documentation', icon: 'book' },
		{ id: 'dock-stats', label: 'Dock Stats', icon: 'chart' },
		{ id: 'system', label: 'System Info', icon: 'cpu' }
	];

	var vars = window.rawnaq_admin_vars || {};
	var $wrap, wrap;

	function el(tag, cls, html) {
		var n = document.createElement(tag);
		if (cls) { n.className = cls; }
		if (html != null) { n.innerHTML = html; }
		return n;
	}
	function store(key, val) {
		try {
			if (val === undefined) { return window.localStorage.getItem('rawnaq_' + key); }
			window.localStorage.setItem('rawnaq_' + key, val);
		} catch (e) { return null; }
	}
	function fireChange(input) {
		input.dispatchEvent(new Event('change', { bubbles: true }));
	}
	function isTyping(e) {
		var t = e.target;
		return t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT' || t.isContentEditable);
	}
	function countUp(node, to) {
		if (!node) return;
		to = parseInt(to, 10) || 0;
		var from = 0, start = null, dur = 700;
		if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) { node.textContent = String(to); return; }
		function step(ts) {
			if (!start) { start = ts; }
			var p = Math.min((ts - start) / dur, 1);
			node.textContent = String(Math.round(from + (to - from) * (1 - Math.pow(1 - p, 3))));
			if (p < 1) { window.requestAnimationFrame(step); }
		}
		window.requestAnimationFrame(step);
	}

	/* ----------------------------------------------------------------- toasts */
	var toastBox;
	function toast(msg, type) {
		if (!wrap) { return; }
		if (!toastBox) { toastBox = el('div', 'rq-toasts'); toastBox.setAttribute('aria-live', 'polite'); wrap.appendChild(toastBox); }
		var t = el('div', 'rq-toast is-' + (type || 'success'), type === 'error' ? I.alert : I.check);
		var s = el('span'); s.textContent = msg; t.appendChild(s);
		toastBox.appendChild(t);
		setTimeout(function () {
			t.classList.add('is-leaving');
			setTimeout(function () { t.remove(); }, 300);
		}, 3200);
	}

	/* ------------------------------------------------------------------ theme */
	function applyTheme(theme) {
		if (!wrap) return;
		wrap.setAttribute('data-rq-theme', theme);
		document.body.classList.toggle('rq-dark', theme === 'dark');
		var b = document.getElementById('rq-theme-btn');
		if (b) {
			b.innerHTML = theme === 'dark' ? I.sun : I.moon;
			b.setAttribute('aria-label', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
			b.title = b.getAttribute('aria-label');
		}
	}
	function toggleTheme() {
		var next = wrap.getAttribute('data-rq-theme') === 'dark' ? 'light' : 'dark';
		applyTheme(next);
		store('theme', next);
	}

	/* ----------------------------------------------------------------- header */
	function buildHeader() {
		var header = wrap.querySelector('.rawnaq-header');
		if (!header) { return; }
		var badge = header.querySelector('.plugin-badge');
		var tools = el('div', 'rq-header-tools');
		var isMac = /Mac|iPhone|iPad/.test(navigator.platform);
		var search = el('button', 'rq-search-trigger', I.search + '<span>Search or jump to…</span><kbd>' + (isMac ? '⌘' : 'Ctrl') + ' K</kbd>');
		search.type = 'button';
		search.addEventListener('click', openPalette);
		var themeBtn = el('button', 'rq-icon-btn');
		themeBtn.type = 'button';
		themeBtn.id = 'rq-theme-btn';
		themeBtn.addEventListener('click', toggleTheme);
		tools.appendChild(search);
		tools.appendChild(themeBtn);
		if (badge) { tools.appendChild(badge); }
		header.appendChild(tools);
	}

	/* -------------------------------------------------------------- nav/tabs */
	var indicator;
	function buildNav() {
		var nav = wrap.querySelector('.rawnaq-nav');
		if (!nav) { return; }
		nav.insertBefore(el('div', 'rq-nav-label', 'Menu'), nav.firstChild);
		indicator = el('span', 'rq-nav-indicator');
		nav.appendChild(indicator);
		nav.querySelectorAll('.nav-item').forEach(function (a) {
			var tab = a.getAttribute('data-tab');
			var def = TABS.filter(function (t) { return t.id === tab; })[0];
			var idx = TABS.indexOf(def);
			var ico = a.querySelector('.nav-icon');
			if (def) {
				if (ico) { ico.innerHTML = I[def.icon]; }
				// rebuild label to drop stray emoji / mis-encoded characters
				Array.prototype.slice.call(a.childNodes).forEach(function (n) { if (n.nodeType === 3) { n.remove(); } });
				var lab = el('span', 'rq-nav-text'); lab.textContent = def.label;
				a.appendChild(lab);
			}
			if (tab === 'modules') {
				var c = el('span', 'rq-nav-count'); c.id = 'rq-nav-modules-count';
				a.appendChild(c);
			}
			if (idx > -1) { a.appendChild(el('span', 'rq-nav-key', String(idx + 1))); }
		});
		var foot = wrap.querySelector('.sidebar-footer p');
		if (foot) {
			foot.innerHTML = '';
			foot.appendChild(document.createTextNode('Need a hand?'));
			var sub = el('span', 'rq-foot-sub'); sub.textContent = 'Report a bug or request a feature.';
			foot.appendChild(sub);
		}
		window.addEventListener('resize', moveIndicator);
	}
	function moveIndicator() {
		if (!indicator) { return; }
		var a = wrap.querySelector('.rawnaq-nav .nav-item.active');
		if (!a) { return; }
		indicator.style.height = a.offsetHeight + 'px';
		indicator.style.transform = 'translateY(' + a.offsetTop + 'px)';
	}
	function showTab(target, focusPanel) {
		var link = wrap.querySelector('.rawnaq-nav .nav-item[data-tab="' + target + '"]');
		var panel = document.getElementById('tab-' + target);
		if (!link || !panel) { return; }
		wrap.querySelectorAll('.rawnaq-nav .nav-item').forEach(function (n) { n.classList.remove('active'); n.removeAttribute('aria-current'); });
		link.classList.add('active');
		link.setAttribute('aria-current', 'page');
		wrap.querySelectorAll('.tab-panel').forEach(function (p) { p.classList.remove('active'); });
		panel.classList.add('active');
		moveIndicator();
		if (window.history && window.history.replaceState) {
			window.history.replaceState(null, '', '#' + target);
		} else {
			window.location.hash = target;
		}
		if (target === 'dock-stats') { refreshDockBars(true); }
		if (panel._rqSpy) { setTimeout(panel._rqSpy, 50); }
		if (focusPanel) {
			var top = wrap.getBoundingClientRect().top + window.pageYOffset - 40;
			if (window.pageYOffset > top) { window.scrollTo({ top: top, behavior: 'smooth' }); }
		}
	}
	function bindNav() {
		$wrap.on('click', '.rawnaq-nav .nav-item', function (e) {
			e.preventDefault();
			showTab($(this).data('tab'), true);
		});
		$wrap.on('click', '.trigger-tab-change, [data-rq-go]', function (e) {
			e.preventDefault();
			showTab($(this).data('target') || $(this).data('rq-go'), true);
		});
		var hash = window.location.hash.substring(1);
		showTab(hash && document.getElementById('tab-' + hash) ? hash : 'welcome');
		setTimeout(moveIndicator, 60);
	}

	/* -------------------------------------------------------------- modules */
	var cards = [];
	function moduleData() {
		return cards.map(function (c) {
			return {
				card: c,
				input: c.querySelector('.module-toggle-input'),
				name: (c.querySelector('h4') || {}).textContent || '',
				cat: (c.querySelector('.module-badge') || {}).textContent || '',
				desc: (c.querySelector('.module-info p') || {}).textContent || ''
			};
		});
	}
	function refreshActiveCount() {
		var total = cards.length;
		var on = wrap.querySelectorAll('.module-toggle-input:checked').length;
		var num = document.getElementById('modules-active-count');
		if (num) { num.textContent = String(on); }
		var nc = document.getElementById('rq-nav-modules-count');
		if (nc) { nc.textContent = on + '/' + total; }
		var ring = wrap.querySelector('.rq-ring-fg');
		if (ring) {
			var C = 2 * Math.PI * 20;
			ring.style.strokeDasharray = C;
			ring.style.strokeDashoffset = total ? C - (C * on / total) : C;
		}
		var dv = document.getElementById('rq-dash-active');
		if (dv) { dv.innerHTML = on + '<small> / ' + total + '</small>'; }
		var dm = document.getElementById('rq-dash-meter');
		if (dm) { dm.style.width = (total ? (on / total) * 100 : 0) + '%'; }
		wrap.querySelectorAll('.rq-el-chip').forEach(function (chip) {
			var inp = document.querySelector('.module-toggle-input[name="' + chip.getAttribute('data-name') + '"]');
			chip.classList.toggle('is-on', !!(inp && inp.checked));
		});
	}
	function updateCardState(input) {
		var card = input.closest('.module-card');
		if (card) { card.classList.toggle('is-on', input.checked); }
	}
	function flashCard(card) {
		if (!card) { return; }
		card.classList.remove('rq-hidden');
		card.scrollIntoView({ behavior: 'smooth', block: 'center' });
		card.classList.remove('rq-flash');
		void card.offsetWidth;
		card.classList.add('rq-flash');
	}

	function buildModules() {
		var panel = document.getElementById('tab-modules');
		if (!panel) { return; }
		cards = Array.prototype.slice.call(panel.querySelectorAll('.module-card'));

		// Remove duplicate legacy toolbar and filter pills
		var oldToolbar = panel.querySelector('.rawnaq-modules-toolbar');
		if (oldToolbar) { oldToolbar.remove(); }
		var oldPills = panel.querySelector('.rawnaq-modules-filter-pills');
		if (oldPills) { oldPills.remove(); }
		var oldNoResults = document.getElementById('rawnaq-modules-no-results');
		if (oldNoResults) { oldNoResults.remove(); }

		// Rebuild modules-hero
		var headerBar = panel.querySelector('.rawnaq-modules-header-bar');
		var intro = panel.querySelector('.rawnaq-modules-intro');
		var stat = document.getElementById('modules-active-stat');
		var h2 = intro ? intro.querySelector('h2') : panel.querySelector('h2');
		var desc = intro ? intro.querySelector('.section-desc') : panel.querySelector('.section-desc');
		var kicker = intro ? intro.querySelector('.modules-kicker') : null;

		var hero = el('div', 'modules-hero');
		var heroLeft = el('div');
		if (kicker) { heroLeft.appendChild(kicker); }
		if (h2) { heroLeft.appendChild(h2); }
		if (desc) { heroLeft.appendChild(desc); }
		hero.appendChild(heroLeft);

		if (stat) {
			stat.className = 'modules-stat';
			var num = document.getElementById('modules-active-count');
			if (num) { num.className = 'modules-stat-num'; }
			var text = el('div', 'modules-stat-text');
			var row = el('div');
			if (num) { row.appendChild(num); }
			var of = el('span', 'rq-of'); of.textContent = ' / ' + cards.length;
			row.appendChild(of);
			text.appendChild(row);
			var label = el('span', 'modules-stat-label'); label.textContent = 'Elements active';
			text.appendChild(label);
			stat.innerHTML = '<svg class="rq-ring" viewBox="0 0 46 46" aria-hidden="true"><circle class="rq-ring-bg" cx="23" cy="23" r="20"/><circle class="rq-ring-fg" cx="23" cy="23" r="20"/></svg>';
			stat.appendChild(text);
			hero.appendChild(stat);
		}

		if (headerBar) {
			headerBar.replaceWith(hero);
		} else {
			panel.insertBefore(hero, panel.firstChild);
		}

		// toolbar: search + category chips + bulk actions
		var cats = {};
		moduleData().forEach(function (m) {
			if (m.cat) { cats[m.cat] = (cats[m.cat] || 0) + 1; }
		});
		var bar = el('div', 'rq-toolbar');
		var s = el('label', 'rq-search', I.search);
		var input = el('input'); input.type = 'search'; input.placeholder = 'Search elements…'; input.id = 'rq-mod-search';
		input.setAttribute('aria-label', 'Search elements');
		s.appendChild(input);
		bar.appendChild(s);
		var chips = el('div', 'rq-chips');
		chips.setAttribute('role', 'tablist');
		function chip(label, val, count) {
			var b = el('button', 'rq-chip' + (val === '' ? ' is-active' : ''));
			b.type = 'button';
			b.setAttribute('data-cat', val);
			b.textContent = label + ' ';
			var n = el('b'); n.textContent = String(count); b.appendChild(n);
			chips.appendChild(b);
		}
		chip('All', '', cards.length);
		chip('Active', '__on', 0);
		Object.keys(cats).forEach(function (k) { chip(k, k, cats[k]); });
		bar.appendChild(chips);
		var end = el('div', 'rq-toolbar-end');
		var seg = el('div', 'rq-seg');
		seg.innerHTML = '<button type="button" data-bulk="on">Enable all</button><button type="button" data-bulk="off">Disable all</button>';
		end.appendChild(seg);
		bar.appendChild(end);
		var form = document.getElementById('rawnaq-modules-form');
		if (form && form.parentNode) {
			form.parentNode.insertBefore(bar, form);
		} else {
			panel.appendChild(bar);
		}

		var grid = panel.querySelector('.modules-grid');
		var empty = el('div', 'rq-empty', 'No elements match your search.');
		if (grid) { grid.appendChild(empty); }

		var activeCat = '';
		function applyFilter() {
			var q = input.value.trim().toLowerCase();
			var shown = 0;
			moduleData().forEach(function (m) {
				var okCat = !activeCat || (activeCat === '__on' ? (m.input && m.input.checked) : m.cat === activeCat);
				var okQ = !q || (m.name + ' ' + m.cat + ' ' + m.desc).toLowerCase().indexOf(q) > -1;
				var vis = okCat && okQ;
				m.card.classList.toggle('rq-hidden', !vis);
				if (vis) { shown++; }
			});
			empty.classList.toggle('is-visible', shown === 0);
			var onChip = chips.querySelector('[data-cat="__on"] b');
			if (onChip) { onChip.textContent = String(wrap.querySelectorAll('.module-toggle-input:checked').length); }
		}
		input.addEventListener('input', applyFilter);
		chips.addEventListener('click', function (e) {
			var b = e.target.closest('.rq-chip');
			if (!b) { return; }
			chips.querySelectorAll('.rq-chip').forEach(function (c) { c.classList.remove('is-active'); });
			b.classList.add('is-active');
			activeCat = b.getAttribute('data-cat');
			applyFilter();
		});
		seg.addEventListener('click', function (e) {
			var b = e.target.closest('[data-bulk]');
			if (b) { bulk(b.getAttribute('data-bulk') === 'on'); }
		});
		panel._rqFilter = applyFilter;

		// per-card extras: docs link + cursor spotlight
		cards.forEach(function (card) {
			var name = ((card.querySelector('h4') || {}).textContent || '').trim();
			var info = card.querySelector('.module-info');
			if (info && findDoc(name)) {
				var act = el('div', 'rq-card-actions');
				var d = el('button', 'rq-linkish', 'How to use ' + I.arrow);
				d.type = 'button';
				d.addEventListener('click', function () { openDoc(findDoc(name)); });
				act.appendChild(d);
				card.appendChild(act);
			}
			card.addEventListener('pointermove', function (e) {
				var r = card.getBoundingClientRect();
				card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
				card.style.setProperty('--my', (e.clientY - r.top) + 'px');
			});
		});

		wrap.querySelectorAll('.module-toggle-input').forEach(function (inp) {
			updateCardState(inp);
			inp.addEventListener('change', function () {
				updateCardState(inp);
				refreshActiveCount();
				applyFilter();
			});
		});
		refreshActiveCount();
		applyFilter();
	}
	function bulk(on) {
		wrap.querySelectorAll('.module-toggle-input').forEach(function (inp) {
			if (inp.checked !== on) {
				inp.checked = on;
				fireChange(inp);
			}
		});
		toast(on ? 'All elements enabled — save to apply.' : 'All elements disabled — save to apply.');
	}

	/* ---------------------------------------------------- settings / save bar */
	var initialState = [];
	var footer, saveBtn;
	function snapshot() {
		var form = document.getElementById('rawnaq-modules-form');
		if (!form) { return; }
		initialState = Array.prototype.map.call(form.elements, function (f) {
			return { f: f, v: f.value, c: f.checked };
		});
	}
	function isDirty() {
		return initialState.some(function (s) {
			return (s.f.type === 'checkbox' || s.f.type === 'radio') ? s.f.checked !== s.c : s.f.value !== s.v;
		});
	}
	function updateDirty() {
		if (footer) { footer.classList.toggle('is-dirty', isDirty()); }
		var t = footer && footer.querySelector('.rq-dirty-text');
		if (t) { t.textContent = isDirty() ? 'You have unsaved changes' : 'All changes saved'; }
	}
	function discard() {
		initialState.forEach(function (s) {
			if (s.f.type === 'checkbox' || s.f.type === 'radio') {
				if (s.f.checked !== s.c) { s.f.checked = s.c; fireChange(s.f); }
			} else { s.f.value = s.v; }
		});
		updateDirty();
		toast('Changes discarded.');
	}

	function buildSettings() {
		var form = document.getElementById('rawnaq-modules-form');
		if (!form) { return; }

		// Collapsible toggle for Global Integrations card
		$('#btn-toggle-integrations').off('click').on('click', function (e) {
			e.preventDefault();
			var $card = $(this).closest('.rawnaq-integrations-card');
			var $body = $('#rawnaq-integrations-body');
			var isExpanded = $(this).attr('aria-expanded') === 'true';
			if (isExpanded) {
				$body.slideUp(200);
				$(this).attr('aria-expanded', 'false');
				$card.addClass('is-collapsed');
			} else {
				$body.slideDown(200);
				$(this).attr('aria-expanded', 'true');
				$card.removeClass('is-collapsed');
			}
		});

		// Secret field reveal / cancel toggles
		$(document).off('click', '.btn-toggle-secret-edit').on('click', '.btn-toggle-secret-edit', function (e) {
			e.preventDefault();
			var $wrapper = $(this).closest('.secret-field-wrapper');
			$wrapper.find('.secret-masked-view').hide();
			$wrapper.find('.secret-edit-view').css('display', 'flex');
			$wrapper.find('.secret-input').focus();
		});

		$(document).off('click', '.btn-cancel-secret-edit').on('click', '.btn-cancel-secret-edit', function (e) {
			e.preventDefault();
			var $wrapper = $(this).closest('.secret-field-wrapper');
			$wrapper.find('.secret-input').val('');
			$wrapper.find('.secret-edit-view').hide();
			$wrapper.find('.secret-masked-view').show();
			updateDirty();
		});

		// 1. Test Mailchimp Connection
		$('#btn-test-mailchimp').off('click').on('click', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var $status = $('#mailchimp-test-status');
			var keyVal = $('#rawnaq-mc-key').val();

			$btn.prop('disabled', true);
			$status.css('color', 'var(--rq-muted)').text('Testing connection…');

			$.ajax({
				url: vars.ajax_url,
				type: 'POST',
				data: {
					action: 'rawnaq_sf_test_mailchimp',
					security: vars.nonce,
					key: keyVal
				},
				success: function (res) {
					$btn.prop('disabled', false);
					if (res && res.success) {
						var msg = (res.data && res.data.message) ? res.data.message : 'Connected!';
						$status.css('color', 'var(--rq-success)').text(msg);
						toast(msg);
					} else {
						var errMsg = (res && res.data && res.data.message) ? res.data.message : 'Connection failed.';
						$status.css('color', 'var(--rq-danger)').text(errMsg);
						toast(errMsg, 'error');
					}
				},
				error: function () {
					$btn.prop('disabled', false);
					$status.css('color', 'var(--rq-danger)').text('Request failed.');
					toast('Request failed.', 'error');
				}
			});
		});

		// 2. Send Test Email
		$('#btn-test-email').off('click').on('click', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var $status = $('#email-test-status');

			$btn.prop('disabled', true);
			$status.css('color', 'var(--rq-muted)').text('Sending test email…');

			$.ajax({
				url: vars.ajax_url,
				type: 'POST',
				data: {
					action: 'rawnaq_sf_test_email',
					security: vars.nonce
				},
				success: function (res) {
					$btn.prop('disabled', false);
					if (res && res.success) {
						var msg = (res.data && res.data.message) ? res.data.message : 'Email sent!';
						$status.css('color', 'var(--rq-success)').text(msg);
						toast(msg);
					} else {
						var errMsg = (res && res.data && res.data.message) ? res.data.message : 'Email delivery failed.';
						$status.css('color', 'var(--rq-danger)').text(errMsg);
						toast(errMsg, 'error');
					}
				},
				error: function () {
					$btn.prop('disabled', false);
					$status.css('color', 'var(--rq-danger)').text('Request failed.');
					toast('Request failed.', 'error');
				}
			});
		});

		// 3. Test Webhook
		$('#btn-test-webhook').off('click').on('click', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var $status = $('#webhook-test-status');
			var url = $('#rawnaq-test-webhook-url').val();

			if (!url) {
				$status.css('color', 'var(--rq-danger)').text('Please enter a webhook URL first.');
				toast('Please enter a webhook URL first.', 'error');
				return;
			}

			$btn.prop('disabled', true);
			$status.css('color', 'var(--rq-muted)').text('Sending sample webhook…');

			$.ajax({
				url: vars.ajax_url,
				type: 'POST',
				data: {
					action: 'rawnaq_sf_test_webhook',
					security: vars.nonce,
					url: url
				},
				success: function (res) {
					$btn.prop('disabled', false);
					if (res && res.success) {
						var msg = (res.data && res.data.message) ? res.data.message : 'Webhook delivered!';
						$status.css('color', 'var(--rq-success)').text(msg);
						toast(msg);
					} else {
						var errMsg = (res && res.data && res.data.message) ? res.data.message : 'Webhook failed.';
						$status.css('color', 'var(--rq-danger)').text(errMsg);
						toast(errMsg, 'error');
					}
				},
				error: function () {
					$btn.prop('disabled', false);
					$status.css('color', 'var(--rq-danger)').text('Request failed.');
					toast('Request failed.', 'error');
				}
			});
		});

		// Save Footer
		footer = form.querySelector('.form-footer');
		saveBtn = document.getElementById('btn-save-settings');
		if (footer && saveBtn) {
			var summary = footer.querySelector('.footer-summary');
			if (summary) { summary.remove(); }
			var dt = el('span', 'rq-dirty-text'); dt.textContent = 'All changes saved';
			footer.insertBefore(dt, footer.firstChild);
			var dis = el('button', 'rq-btn rq-btn-ghost rq-discard'); dis.type = 'button'; dis.textContent = 'Discard';
			dis.addEventListener('click', discard);
			if (saveBtn.parentNode) {
				saveBtn.parentNode.insertBefore(dis, saveBtn);
			} else {
				footer.appendChild(dis);
			}
			setSaveLabel();
		}
		snapshot();
		form.addEventListener('input', updateDirty);
		form.addEventListener('change', updateDirty);
		window.addEventListener('beforeunload', function (e) {
			if (isDirty()) { e.preventDefault(); e.returnValue = ''; }
		});

		$(form).off('submit').on('submit', function (e) {
			e.preventDefault();
			var $status = $('#save-status-msg');
			saveBtn.disabled = true;
			saveBtn.innerHTML = '<span class="rq-spinner"></span> Saving…';
			$status.removeClass('success error').text('');
			$.ajax({
				url: vars.ajax_url,
				type: 'POST',
				data: {
					action: 'rawnaq_save_modules',
					security: vars.nonce,
					form_data: $(this).serialize()
				},
				success: function (response) {
					saveBtn.disabled = false;
					setSaveLabel();
					if (response && response.success) {
						$status.addClass('success').text(response.data);
						snapshot();
						updateDirty();
						toast(typeof response.data === 'string' && response.data ? response.data : 'Settings saved successfully!');
					} else {
						var msg = (response && response.data) || 'Failed to save settings.';
						$status.addClass('error').text(msg);
						toast(msg, 'error');
					}
				},
				error: function () {
					saveBtn.disabled = false;
					setSaveLabel();
					$status.addClass('error').text('An error occurred. Please try again.');
					toast('An error occurred. Please try again.', 'error');
				}
			});
		});
	}
	function setSaveLabel() {
		if (!saveBtn) { return; }
		var isMac = /Mac|iPhone|iPad/.test(navigator.platform);
		saveBtn.innerHTML = I.save + ' Save changes <span class="rq-kbd">' + (isMac ? '⌘S' : 'Ctrl S') + '</span>';
	}
	function save() {
		var form = document.getElementById('rawnaq-modules-form');
		if (form && saveBtn && !saveBtn.disabled) { $(form).trigger('submit'); }
	}

	/* ------------------------------------------------------------------ docs */
	var docCards = [];
	function findDoc(name) {
		name = (name || '').toLowerCase().trim();
		if (!name) { return null; }
		return docCards.filter(function (d) {
			var t = (d.querySelector('h3 span:nth-child(2)') || d.querySelector('h3') || {}).textContent || '';
			return t.toLowerCase().indexOf(name) > -1;
		})[0] || null;
	}
	function setDocOpen(card, open) {
		card.classList.toggle('is-open', open);
		var h = card.querySelector('h3');
		if (h) { h.setAttribute('aria-expanded', open ? 'true' : 'false'); }
	}
	function openDoc(card) {
		if (!card) { return; }
		showTab('docs');
		var panel = document.getElementById('tab-docs');
		if (panel && panel._rqReset) { panel._rqReset(); }
		setDocOpen(card, true);
		setTimeout(function () { card.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 80);
	}
	function buildDocs() {
		var panel = document.getElementById('tab-docs');
		if (!panel) { return; }
		docCards = Array.prototype.slice.call(panel.querySelectorAll('.rawnaq-doc-card'));
		if (!docCards.length) { return; }

		var head = el('div', 'rq-page-head');
		var hc = el('div', '', '<p class="rq-eyebrow">Guides</p>');
		var h2 = el('h2'); h2.textContent = 'Documentation & Usage Guide';
		var desc = el('p', 'section-desc'); desc.textContent = 'Interactive reference guides, ready-to-copy shortcodes, and dynamic triggers for all modules.';
		hc.appendChild(h2);
		hc.appendChild(desc);
		head.appendChild(hc);

		var layout = el('div', 'rq-docs-layout');
		var toc = el('nav', 'rq-toc');
		toc.setAttribute('aria-label', 'Documentation contents');
		var main = el('div', 'rq-docs-main');
		var bar = el('div', 'rq-toolbar');
		var s = el('label', 'rq-search', I.search);
		var input = el('input'); input.type = 'search'; input.placeholder = 'Search the docs…'; input.id = 'rq-doc-search';
		input.setAttribute('aria-label', 'Search documentation');
		s.appendChild(input);
		bar.appendChild(s);
		var end = el('div', 'rq-toolbar-end');
		var seg = el('div', 'rq-seg', '<button type="button" data-all="1">Expand all</button><button type="button" data-all="0">Collapse all</button>');
		end.appendChild(seg);
		bar.appendChild(end);
		main.appendChild(bar);
		var empty = el('div', 'rq-empty', 'Nothing found. Try a different word.');
		main.appendChild(empty);

		docCards.forEach(function (card, i) {
			var origH3 = card.querySelector('.header-titles h3') || card.querySelector('h3');
			var title = 'Section ' + (i + 1);
			if (origH3) {
				var clone = origH3.cloneNode(true);
				clone.querySelectorAll('.rawnaq-doc-badge, .badge-cheat, .badge-pro, .dashicons').forEach(function(b) { b.remove(); });
				title = clone.textContent.replace(/^\s*\d+\.\s*/, '').trim();
			}
			var id = 'rq-doc-' + (i + 1);
			card.id = card.id || id;

			var origBody = card.querySelector('.rawnaq-doc-card-body');
			var pad = el('div', 'rq-doc-inner');
			if (origBody) {
				while (origBody.firstChild) {
					pad.appendChild(origBody.firstChild);
				}
			} else {
				Array.prototype.slice.call(card.childNodes).forEach(function (n) {
					if (n !== origH3 && (!n.contains || !n.contains(origH3))) {
						pad.appendChild(n);
					}
				});
			}

			// Accordion header
			var h3 = el('h3');
			var num = el('span', 'rq-doc-num'); num.textContent = String(i + 1);
			var tSpan = el('span'); tSpan.textContent = title;
			h3.appendChild(num);
			h3.appendChild(tSpan);
			h3.insertAdjacentHTML('beforeend', I.chevron);
			h3.setAttribute('role', 'button');
			h3.setAttribute('tabindex', '0');
			h3.setAttribute('aria-controls', card.id);
			h3.addEventListener('click', function () { setDocOpen(card, !card.classList.contains('is-open')); });
			h3.addEventListener('keydown', function (e) {
				if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); h3.click(); }
			});

			var body = el('div', 'rq-doc-body');
			var inner = el('div');
			inner.appendChild(pad);
			body.appendChild(inner);

			card.innerHTML = '';
			card.appendChild(h3);
			card.appendChild(body);

			setDocOpen(card, i === 0);

			var a = el('a');
			a.href = '#' + card.id;
			a.setAttribute('data-doc', card.id);
			var n2 = el('span'); n2.textContent = String(i + 1);
			var t2 = el('b'); t2.style.fontWeight = 'inherit'; t2.textContent = title;
			a.appendChild(n2);
			a.appendChild(t2);
			a.addEventListener('click', function (e) { e.preventDefault(); openDoc(card); });
			toc.appendChild(a);
			main.appendChild(card);
		});

		layout.appendChild(toc);
		layout.appendChild(main);
		panel.innerHTML = '';
		panel.appendChild(head);
		panel.appendChild(layout);

		function filter() {
			var q = input.value.trim().toLowerCase();
			var shown = 0;
			docCards.forEach(function (card) {
				var hit = !q || card.textContent.toLowerCase().indexOf(q) > -1;
				card.classList.toggle('rq-hidden', !hit);
				var link = toc.querySelector('[data-doc="' + card.id + '"]');
				if (link) { link.classList.toggle('rq-hidden', !hit); }
				if (q && hit) { setDocOpen(card, true); }
				if (hit) { shown++; }
			});
			empty.classList.toggle('is-visible', shown === 0);
		}
		input.addEventListener('input', filter);
		panel._rqReset = function () { if (input.value) { input.value = ''; filter(); } };
		seg.addEventListener('click', function (e) {
			var b = e.target.closest('[data-all]');
			if (!b) { return; }
			docCards.forEach(function (c) { setDocOpen(c, b.getAttribute('data-all') === '1'); });
		});

		// scroll-spy
		var ticking = false;
		function spy() {
			ticking = false;
			if (!panel.classList.contains('active')) { return; }
			var current = null;
			docCards.forEach(function (c) {
				if (!c.classList.contains('rq-hidden') && c.getBoundingClientRect().top < 160) { current = c; }
			});
			if (!current) { current = docCards.filter(function (c) { return !c.classList.contains('rq-hidden'); })[0]; }
			toc.querySelectorAll('a').forEach(function (a) { a.classList.toggle('is-active', !!current && a.getAttribute('data-doc') === current.id); });
		}
		window.addEventListener('scroll', function () { if (!ticking) { ticking = true; window.requestAnimationFrame(spy); } }, { passive: true });
		panel._rqSpy = spy;
	}

	/* ------------------------------------------------------------ dock stats */
	var DOCK_KEYS = ['fab', 'agent', 'web', 'chooser', 'secondary', 'classic', 'offline'];
	function refreshDockBars(animate) {
		var vals = DOCK_KEYS.map(function (k) {
			var n = document.getElementById('dock-stat-' + k);
			return n ? (parseInt(n.textContent, 10) || 0) : 0;
		});
		var max = Math.max.apply(null, vals.concat([1]));
		DOCK_KEYS.forEach(function (k, i) {
			var bar = document.getElementById('rq-bar-' + k);
			if (!bar) { return; }
			bar.style.width = '0%';
			var w = (vals[i] / max) * 100;
			if (animate) { window.requestAnimationFrame(function () { setTimeout(function () { bar.style.width = w + '%'; }, 40); }); } else { bar.style.width = w + '%'; }
		});
	}
	function buildDock() {
		var panel = document.getElementById('tab-dock-stats');
		if (!panel) { return; }
		var h2 = panel.querySelector(':scope > h2');
		var desc = panel.querySelector(':scope > .section-desc');
		var head = el('div', 'rq-page-head');
		var hc = el('div', '', '<p class="rq-eyebrow">Analytics</p>');
		if (h2) { hc.appendChild(h2); }
		if (desc) { hc.appendChild(desc); }
		head.appendChild(hc);
		panel.insertBefore(head, panel.firstChild);

		DOCK_KEYS.forEach(function (k) {
			var td = document.getElementById('dock-stat-' + k);
			if (!td) { return; }
			var cell = el('td', 'rq-bar-cell', '<div class="rq-bar"><i id="rq-bar-' + k + '"></i></div>');
			td.parentNode.insertBefore(cell, td);
		});
		var tableCard = document.getElementById('dock-stats-table');
		if (tableCard && tableCard.closest('.rawnaq-card')) {
			var tc = tableCard.closest('.rawnaq-card');
			var th = el('div', 'rq-card-head', '<h3>Clicks by action</h3>');
			tc.insertBefore(th, tc.firstChild);
		}
		var total = document.getElementById('dock-stat-total');
		if (total) { total.setAttribute('aria-live', 'polite'); }

		$('#btn-reset-dock-clicks').off('click').on('click', function () {
			if (!window.confirm('Reset all Floating Dock click counters? This cannot be undone.')) { return; }
			var btn = this;
			var $status = $('#dock-stats-status');
			btn.disabled = true;
			$status.removeClass('success error').text('Resetting…');
			$.ajax({
				url: vars.ajax_url,
				type: 'POST',
				data: { action: 'rawnaq_dock_reset_clicks', nonce: vars.nonce },
				success: function (response) {
					btn.disabled = false;
					if (response && response.success && response.data && response.data.clicks) {
						var c = response.data.clicks;
						countUp(document.getElementById('dock-stat-total'), c.total || 0);
						DOCK_KEYS.forEach(function (k) { $('#dock-stat-' + k).text(String(c[k] || 0)); });
						var dt = document.getElementById('rq-dash-clicks');
						if (dt) { dt.textContent = String(c.total || 0); }
						refreshDockBars(true);
						$status.addClass('success').text('Counters reset.');
						toast('Dock counters reset.');
					} else {
						$status.addClass('error').text('Could not reset counters.');
						toast('Could not reset counters.', 'error');
					}
				},
				error: function () {
					btn.disabled = false;
					$status.addClass('error').text('Request failed.');
					toast('Request failed.', 'error');
				}
			});
		});

		// WhatsApp Status Override selector AJAX
		$('#rawnaq-dock-status-override').off('change').on('change', function () {
			var $select = $(this);
			var $status = $('#dock-status-override-status');
			var value = $select.val();
			$select.prop('disabled', true);
			$status.removeClass('success error').text('Saving…');

			$.ajax({
				url: vars.ajax_url,
				type: 'POST',
				data: {
					action: 'rawnaq_dock_set_status_override',
					nonce: vars.nonce,
					status: value
				},
				success: function (response) {
					$select.prop('disabled', false);
					if (response && response.success) {
						$status.addClass('success').text('Saved.');
						toast('WhatsApp status override saved.');
					} else {
						$status.addClass('error').text('Could not save.');
						toast('Could not save override.', 'error');
					}
					setTimeout(function () {
						$status.fadeOut(300, function () {
							$(this).text('').show();
						});
					}, 2500);
				},
				error: function () {
					$select.prop('disabled', false);
					$status.addClass('error').text('Request failed.');
					toast('Request failed.', 'error');
				}
			});
		});
	}

	/* ---------------------------------------------------------------- system */
	function sysRows() {
		return Array.prototype.map.call(document.querySelectorAll('#tab-system .system-table tr'), function (tr) {
			var td = tr.querySelectorAll('td');
			return {
				k: (td[0] ? td[0].textContent : '').replace(/:\s*$/, '').trim(),
				v: (td[1] ? td[1].textContent : '').trim(),
				html: td[1] ? td[1].innerHTML : ''
			};
		});
	}
	function buildSystem() {
		var panel = document.getElementById('tab-system');
		if (!panel) { return; }
		var h2 = panel.querySelector(':scope > h2');
		var desc = panel.querySelector(':scope > .section-desc');
		var head = el('div', 'rq-page-head');
		var hc = el('div', '', '<p class="rq-eyebrow">Environment</p>');
		if (h2) { hc.appendChild(h2); }
		if (desc) { hc.appendChild(desc); }
		head.appendChild(hc);
		var copy = el('button', 'rq-btn rq-btn-ghost', I.copy + ' Copy report');
		copy.type = 'button';
		copy.addEventListener('click', copySystem);
		head.appendChild(copy);
		panel.insertBefore(head, panel.firstChild);

		var icons = [['wp', ''], ['code', 'is-violet'], ['layers', 'is-gold'], ['grid', 'is-blue']];
		var grid = el('div', 'rq-sys-grid');
		sysRows().forEach(function (r, i) {
			var ic = icons[i % icons.length];
			var t = el('div', 'rq-sys-tile', '<span class="rq-stat-ico ' + ic[1] + '">' + I[ic[0]] + '</span>');
			var k = el('div', 'rq-sys-k'); k.textContent = r.k;
			var v = el('div', 'rq-sys-v'); v.innerHTML = r.html;
			t.appendChild(k); t.appendChild(v);
			grid.appendChild(t);
		});
		if (head && head.parentNode) {
			head.parentNode.insertBefore(grid, head.nextSibling);
		} else {
			panel.appendChild(grid);
		}
		var card = panel.querySelector('.rawnaq-card');
		if (card) { card.insertBefore(el('div', 'rq-card-head', '<h3>Full report</h3>'), card.firstChild); }
	}
	function copySystem() {
		var lines = ['### Rawnaq System Report', 'Plugin: ' + ((wrap.querySelector('.plugin-badge') || {}).textContent || '').trim()];
		sysRows().forEach(function (r) { lines.push(r.k + ': ' + r.v); });
		lines.push('Active elements: ' + moduleData().filter(function (m) { return m.input && m.input.checked; }).map(function (m) { return m.name.trim(); }).join(', '));
		var txt = lines.join('\n');
		function done() { toast('System report copied to clipboard.'); }
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(txt).then(done, fallback);
		} else { fallback(); }
		function fallback() {
			var ta = el('textarea'); ta.value = txt; ta.style.position = 'fixed'; ta.style.opacity = '0';
			document.body.appendChild(ta); ta.select();
			try { document.execCommand('copy'); done(); } catch (e) { toast('Copy failed.', 'error'); }
			ta.remove();
		}
	}

	/* ------------------------------------------------------------- dashboard */
	function buildDashboard() {
		var panel = document.getElementById('tab-welcome');
		if (!panel) { return; }
		var banner = panel.querySelector('.welcome-banner');
		if (banner) {
			var h = new Date().getHours();
			var greet = el('span', 'rq-greet');
			greet.textContent = (h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening') + ' 👋';
			banner.insertBefore(greet, banner.firstChild);
			var actions = el('div', 'rq-hero-actions');
			var go = el('button', 'btn btn-primary', I.grid + ' Manage elements'); go.type = 'button'; go.setAttribute('data-rq-go', 'modules');
			var docs = el('button', 'rq-btn rq-btn-ghost', I.book + ' Read the docs'); docs.type = 'button'; docs.setAttribute('data-rq-go', 'docs');
			actions.appendChild(go); actions.appendChild(docs);
			banner.appendChild(actions);
			banner.insertAdjacentHTML('beforeend',
				'<svg class="rq-hero-art" viewBox="0 0 150 150" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.4">' +
				'<g class="rq-spin" opacity=".55"><rect x="35" y="35" width="80" height="80" rx="6"/><rect x="35" y="35" width="80" height="80" rx="6" transform="rotate(45 75 75)"/></g>' +
				'<circle cx="75" cy="75" r="58" stroke-dasharray="2 6" opacity=".5"/>' +
				'<path d="M75 40l8 27 27 8-27 8-8 27-8-27-27-8 27-8z" fill="currentColor" fill-opacity=".12"/></svg>');
		}

		// stat tiles
		var rows = sysRows();
		var findRow = function (re) { return rows.filter(function (r) { return re.test(r.k); })[0] || { v: '—', html: '—' }; };
		var totalClicks = (document.getElementById('dock-stat-total') || {}).textContent || '0';
		var stats = el('div', 'rq-stats');
		stats.innerHTML =
			'<button type="button" class="rq-stat" data-rq-go="modules"><div class="rq-stat-top"><span class="rq-stat-label">Active elements</span><span class="rq-stat-ico">' + I.bolt + '</span></div><div class="rq-stat-value" id="rq-dash-active">0</div><div class="rq-meter"><i id="rq-dash-meter"></i></div></button>' +
			'<button type="button" class="rq-stat" data-rq-go="dock-stats"><div class="rq-stat-top"><span class="rq-stat-label">Dock clicks</span><span class="rq-stat-ico is-gold">' + I.click + '</span></div><div class="rq-stat-value" id="rq-dash-clicks">0</div><div class="rq-stat-foot">All-time, site-wide</div></button>' +
			'<button type="button" class="rq-stat" data-rq-go="system"><div class="rq-stat-top"><span class="rq-stat-label">Elementor</span><span class="rq-stat-ico is-violet">' + I.layers + '</span></div><div class="rq-stat-value" style="font-size:18px">' + findRow(/elementor/i).html + '</div><div class="rq-stat-foot">Gutenberg: ' + $('<div>').text(findRow(/gutenberg/i).v).html() + '</div></button>' +
			'<button type="button" class="rq-stat" data-rq-go="system"><div class="rq-stat-top"><span class="rq-stat-label">PHP version</span><span class="rq-stat-ico is-blue">' + I.code + '</span></div><div class="rq-stat-value">' + $('<div>').text(findRow(/php/i).v).html() + '</div><div class="rq-stat-foot">WordPress ' + $('<div>').text(findRow(/wordpress/i).v).html() + '</div></button>';
		if (banner) { banner.parentNode.insertBefore(stats, banner.nextSibling); }
		countUp(document.getElementById('rq-dash-clicks'), totalClicks);

		// two-column body
		var grid = panel.querySelector('.grid-2');
		if (grid) {
			grid.className = 'rq-dash-grid';
			var cardsIn = grid.querySelectorAll(':scope > .rawnaq-card');
			var qs = cardsIn[0], sp = cardsIn[1];
			if (qs) {
				var oldBtn = qs.querySelector('.trigger-tab-change');
				if (oldBtn) { oldBtn.remove(); }
				var qh = qs.querySelector('h3');
				var head = el('div', 'rq-card-head');
				var t = el('h3'); t.textContent = 'Your elements';
				var m = el('button', 'rq-linkish', 'Manage →'); m.type = 'button'; m.setAttribute('data-rq-go', 'modules');
				head.appendChild(t); head.appendChild(m);
				if (qh) { qh.replaceWith(head); } else { qs.insertBefore(head, qs.firstChild); }
				var qp = qs.querySelector('p');
				if (qp) { qp.className = 'section-desc'; qp.style.margin = '0 0 14px'; qp.textContent = 'Search any active element by name in Elementor or Gutenberg. Click one to jump to its settings.'; }
				var list = el('div', 'rq-el-list');
				moduleData().forEach(function (md) {
					var c = el('button', 'rq-el-chip');
					c.type = 'button';
					c.setAttribute('data-name', md.input ? md.input.name : '');
					c.appendChild(el('span', 'rq-dot'));
					var n = el('span'); n.textContent = md.name.trim(); c.appendChild(n);
					var k = el('span', 'rq-el-cat'); k.textContent = md.cat.trim(); c.appendChild(k);
					c.addEventListener('click', function () { showTab('modules', true); setTimeout(function () { flashCard(md.card); }, 120); });
					list.appendChild(c);
				});
				qs.appendChild(list);
			}
			if (sp) {
				sp.classList.add('rq-principle');
				sp.insertAdjacentHTML('afterbegin', '<span class="rq-stat-ico" style="margin-bottom:14px">' + I.bolt + '</span>');
				var ul = el('ul');
				['Zero jQuery or frameworks on the frontend', 'Assets load only on pages that use an element', 'Disabled elements ship 0 bytes'].forEach(function (txt) {
					var li = el('li', '', I.check); var s = el('span'); s.textContent = txt; li.appendChild(s); ul.appendChild(li);
				});
				sp.appendChild(ul);
			}
		}
		refreshActiveCount();
	}

	/* ------------------------------------------------------- command palette */
	var pal, palInput, palList, palItems = [], palIndex = 0, lastFocus;
	function paletteCommands() {
		var cmds = [];
		TABS.forEach(function (t, i) {
			cmds.push({ group: 'Pages', label: t.label, icon: t.icon, hint: String(i + 1), run: function () { showTab(t.id, true); } });
		});
		moduleData().forEach(function (m) {
			cmds.push({
				group: 'Elements', label: m.name.trim(), icon: 'toggle', hint: m.input && m.input.checked ? 'On' : 'Off', keywords: m.cat + ' ' + m.desc,
				run: function () { showTab('modules', true); setTimeout(function () { flashCard(m.card); }, 120); }
			});
		});
		docCards.forEach(function (d) {
			var t = d.querySelector('h3 span:nth-child(2)') || d.querySelector('h3');
			cmds.push({ group: 'Docs', label: 'Guide: ' + (t ? t.textContent : ''), icon: 'book', keywords: d.textContent.slice(0, 400), run: function () { openDoc(d); } });
		});
		cmds.push({ group: 'Actions', label: 'Save changes', icon: 'save', hint: 'Ctrl S', run: function () { showTab('modules'); save(); } });
		cmds.push({ group: 'Actions', label: 'Enable all elements', icon: 'check', run: function () { showTab('modules'); bulk(true); } });
		cmds.push({ group: 'Actions', label: 'Disable all elements', icon: 'toggle', run: function () { showTab('modules'); bulk(false); } });
		cmds.push({ group: 'Actions', label: 'Toggle dark mode', icon: 'moon', run: toggleTheme });
		cmds.push({ group: 'Actions', label: 'Copy system report', icon: 'copy', run: copySystem });
		var support = wrap.querySelector('.sidebar-footer .doc-btn');
		if (support) { cmds.push({ group: 'Actions', label: 'Open a support ticket', icon: 'help', run: function () { window.open(support.href, '_blank', 'noopener'); } }); }
		return cmds;
	}
	function buildPalette() {
		pal = el('div', 'rq-palette-backdrop');
		pal.innerHTML = '<div class="rq-palette" role="dialog" aria-modal="true" aria-label="Command palette"><div class="rq-palette-input">' + I.search + '<input type="text" placeholder="Type a command, element or guide…" aria-label="Search commands" role="combobox" aria-expanded="true" aria-controls="rq-pal-list"></div><ul class="rq-palette-list" id="rq-pal-list" role="listbox"></ul><div class="rq-palette-foot"><span><span class="rq-kbd">↑↓</span>navigate</span><span><span class="rq-kbd">↵</span>select</span><span><span class="rq-kbd">esc</span>close</span></div></div>';
		wrap.appendChild(pal);
		palInput = pal.querySelector('input');
		palList = pal.querySelector('.rq-palette-list');
		pal.addEventListener('mousedown', function (e) { if (e.target === pal) { closePalette(); } });
		palInput.addEventListener('input', renderPalette);
		palInput.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowDown') { e.preventDefault(); setPal(palIndex + 1); }
			else if (e.key === 'ArrowUp') { e.preventDefault(); setPal(palIndex - 1); }
			else if (e.key === 'Enter') { e.preventDefault(); runPal(palIndex); }
			else if (e.key === 'Escape') { e.preventDefault(); closePalette(); }
		});
	}
	function renderPalette() {
		var q = palInput.value.trim().toLowerCase();
		var cmds = paletteCommands().filter(function (c) {
			if (!q) { return c.group !== 'Docs'; }
			return (c.label + ' ' + c.group + ' ' + (c.keywords || '')).toLowerCase().indexOf(q) > -1;
		});
		cmds.sort(function (a, b) {
			if (!q) { return 0; }
			return (a.label.toLowerCase().indexOf(q) === -1) - (b.label.toLowerCase().indexOf(q) === -1);
		});
		palItems = cmds;
		palList.innerHTML = '';
		if (!cmds.length) { palList.innerHTML = '<li class="rq-palette-empty">No results</li>'; return; }
		var lastGroup = '';
		cmds.forEach(function (c, i) {
			if (c.group !== lastGroup && !q) {
				var g = el('li', 'rq-palette-group'); g.textContent = c.group; g.setAttribute('role', 'presentation');
				palList.appendChild(g);
				lastGroup = c.group;
			}
			var li = el('li', 'rq-palette-item', '<span class="rq-pi-ico">' + (I[c.icon] || '') + '</span>');
			li.setAttribute('role', 'option');
			li.id = 'rq-pal-' + i;
			var l = el('span'); l.textContent = c.label; li.appendChild(l);
			if (c.hint || q) { var h = el('span', 'rq-pi-hint'); h.textContent = c.hint || c.group; li.appendChild(h); }
			li.addEventListener('mousemove', function () { if (palIndex !== i) { setPal(i); } });
			li.addEventListener('click', function () { runPal(i); });
			palList.appendChild(li);
		});
		setPal(0);
	}
	function setPal(i) {
		if (!palItems.length) { return; }
		palIndex = (i + palItems.length) % palItems.length;
		palList.querySelectorAll('.rq-palette-item').forEach(function (n) { n.classList.remove('is-active'); n.setAttribute('aria-selected', 'false'); });
		var cur = document.getElementById('rq-pal-' + palIndex);
		if (cur) { cur.classList.add('is-active'); cur.setAttribute('aria-selected', 'true'); cur.scrollIntoView({ block: 'nearest' }); palInput.setAttribute('aria-activedescendant', cur.id); }
	}
	function runPal(i) {
		var c = palItems[i];
		closePalette();
		if (c) { c.run(); }
	}
	function openPalette() {
		lastFocus = document.activeElement;
		pal.classList.add('is-open');
		palInput.value = '';
		renderPalette();
		setTimeout(function () { palInput.focus(); }, 10);
	}
	function closePalette() {
		pal.classList.remove('is-open');
		if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
	}

	/* ------------------------------------------------------------- shortcuts */
	function bindKeys() {
		window.addEventListener('keydown', function (e) {
			var mod = e.metaKey || e.ctrlKey;
			if (mod && !e.shiftKey && (e.key === 'k' || e.key === 'K')) {
				e.preventDefault();
				e.stopImmediatePropagation();
				if (pal.classList.contains('is-open')) { closePalette(); } else { openPalette(); }
				return;
			}
			if (mod && (e.key === 's' || e.key === 'S')) {
				if (document.getElementById('rawnaq-modules-form')) { e.preventDefault(); save(); }
				return;
			}
			if (pal.classList.contains('is-open') || mod || e.altKey || isTyping(e)) { return; }
			if (/^[1-5]$/.test(e.key)) {
				var t = TABS[parseInt(e.key, 10) - 1];
				if (t) { showTab(t.id, true); }
			} else if (e.key === '/') {
				var active = wrap.querySelector('.tab-panel.active .rq-search input');
				if (active) { e.preventDefault(); active.focus(); }
			}
		}, true);
	}

	/* --------------------------------------------------------- copy badges */
	function bindCopyBadges() {
		$(document).on('click', '.rawnaq-copy-badge, .copyable', function (e) {
			e.preventDefault();
			var textToCopy = $(this).attr('data-copy') || $(this).text().trim();
			textToCopy = textToCopy.replace(/^📋\s*/, '').replace(/\s*📋$/, '');
			if (!textToCopy) return;

			function done() {
				toast('Copied: ' + textToCopy);
			}
			if (navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(textToCopy).then(done, fallback);
			} else {
				fallback();
			}
			function fallback() {
				var ta = el('textarea');
				ta.value = textToCopy;
				ta.style.position = 'fixed';
				ta.style.opacity = '0';
				document.body.appendChild(ta);
				ta.select();
				try {
					document.execCommand('copy');
					done();
				} catch (err) {
					toast('Copy failed.', 'error');
				}
				ta.remove();
			}
		});
	}

	/* ------------------------------------------------------------------ boot */
	$(function () {
		wrap = document.querySelector('.rawnaq-admin-wrap');
		if (!wrap) { return; }
		$wrap = $(wrap);
		var saved = store('theme');
		applyTheme(saved || ((window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light'));

		buildHeader();
		applyTheme(wrap.getAttribute('data-rq-theme')); // paint the toggle icon
		buildNav();
		buildDocs();       // before modules so cards can link to guides
		buildModules();
		buildSettings();
		buildDock();
		buildSystem();
		buildDashboard();
		buildPalette();
		bindNav();
		bindKeys();
		bindCopyBadges();
		refreshDockBars(false);
	});

})(jQuery);
