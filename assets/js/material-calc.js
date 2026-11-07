/**
 * Rawnaq Material Quantity Calculator.
 * Vanilla JS, no dependencies. All values are held in metric and converted for display.
 */
(function () {
	'use strict';

	var I18N = (window.rawnaqMC && window.rawnaqMC.i18n) || {};
	function t(key, fallback) {
		return I18N[key] || fallback;
	}

	// Conversion from imperial display value to metric base value.
	var TO_METRIC = {
		len: 0.3048,        // ft -> m
		cm: 2.54,           // in -> cm
		mm: 25.4,           // in -> mm
		area: 0.09290304,   // sq ft -> m2
		cov: 0.09290304,    // sq ft/L -> m2/L
		num: 1,
		pct: 1
	};
	var UNIT_LABEL = {
		metric: { len: 'm', cm: 'cm', mm: 'mm', area: 'm²', cov: 'm²/L', pct: '%' },
		imperial: { len: 'ft', cm: 'in', mm: 'in', area: 'sq ft', cov: 'sq ft/L', pct: '%' }
	};

	var CEMENT_BAG_M3 = 0.0347; // 50 kg bag at 1440 kg/m3
	var CONCRETE_DRY_FACTOR = 1.54;
	var MORTAR_DRY_FACTOR = 1.27;

	function round(n, d) {
		var p = Math.pow(10, d);
		return Math.round(n * p) / p;
	}

	function fmt(n, digits) {
		try {
			return new Intl.NumberFormat(undefined, { maximumFractionDigits: digits === undefined ? 2 : digits }).format(n);
		} catch (e) {
			return String(round(n, digits === undefined ? 2 : digits));
		}
	}

	// Output formatters: value is metric, system decides the display unit.
	var OUT = {
		vol: function (v, sys) {
			return sys === 'imperial' ? fmt(v * 35.3147) + ' cu ft' : fmt(v) + ' m³';
		},
		area: function (v, sys) {
			return sys === 'imperial' ? fmt(v * 10.7639) + ' sq ft' : fmt(v) + ' m²';
		},
		len: function (v, sys) {
			return sys === 'imperial' ? fmt(v * 3.28084) + ' ft' : fmt(v) + ' m';
		},
		kg: function (v) {
			return fmt(v) + ' kg';
		},
		lb: function (v) {
			return fmt(v * 2.20462) + ' lb';
		},
		litre: function (v) {
			return fmt(v, 1) + ' L';
		},
		count: function (v) {
			return fmt(v, 0);
		},
		kgm: function (v) {
			return fmt(v, 3) + ' kg/m';
		}
	};

	var CALC = {
		concrete: function (v) {
			var parts = String(v.mix).split(':').map(Number);
			var sum = parts[0] + parts[1] + parts[2];
			var vol = v.length * v.width * (v.depth / 100) * (1 + v.waste / 100);
			var dry = vol * CONCRETE_DRY_FACTOR;
			var cement = dry * parts[0] / sum;
			return {
				ok: vol > 0,
				rows: [
					[t('concreteVolume', 'Concrete volume'), OUT.vol(vol, v.sys), true],
					[t('cementBags', 'Cement (50 kg bags)'), OUT.count(Math.ceil(cement / CEMENT_BAG_M3)), false],
					[t('sand', 'Sand'), OUT.vol(dry * parts[1] / sum, v.sys), false],
					[t('aggregate', 'Aggregate'), OUT.vol(dry * parts[2] / sum, v.sys), false]
				]
			};
		},
		bricks: function (v) {
			var gross = v.length * v.height;
			var net = Math.max(0, gross - v.open);
			var wallVol = net * (v.thick / 1000);
			var unit = ((v.bl + v.joint) * (v.bw + v.joint) * (v.bh + v.joint)) / 1e9;
			var raw = unit > 0 ? wallVol / unit : 0;
			var brickVol = raw * (v.bl * v.bw * v.bh) / 1e9;
			var mortarDry = Math.max(0, wallVol - brickVol) * MORTAR_DRY_FACTOR;
			var n = Number(v.mortar);
			return {
				ok: net > 0 && unit > 0,
				rows: [
					[t('bricks', 'Bricks'), OUT.count(Math.ceil(raw * (1 + v.waste / 100))), true],
					[t('wallArea', 'Wall area (net)'), OUT.area(net, v.sys), false],
					[t('cementBags', 'Cement (50 kg bags)'), OUT.count(Math.ceil((mortarDry / (1 + n)) / CEMENT_BAG_M3)), false],
					[t('sand', 'Sand'), OUT.vol(mortarDry * n / (1 + n), v.sys), false]
				]
			};
		},
		tiles: function (v) {
			var area = v.length * v.width;
			var tileArea = ((v.tw * 10 + v.gap) * (v.th * 10 + v.gap)) / 1e6;
			var count = tileArea > 0 ? Math.ceil(area / tileArea * (1 + v.waste / 100)) : 0;
			var rows = [
				[t('tiles', 'Tiles'), OUT.count(count), true],
				[t('floorArea', 'Floor area'), OUT.area(area, v.sys), false]
			];
			if (v.box > 0) {
				rows.push([t('boxes', 'Boxes'), OUT.count(Math.ceil(count / v.box)), false]);
			}
			return { ok: area > 0 && tileArea > 0, rows: rows };
		},
		paint: function (v) {
			var walls = Math.max(0, 2 * (v.length + v.width) * v.height - v.open);
			var area = walls + (v.surfaces === 'walls_ceiling' ? v.length * v.width : 0);
			var litres = v.cover > 0 ? area * v.coats / v.cover * (1 + v.waste / 100) : 0;
			return {
				ok: area > 0 && v.cover > 0,
				rows: [
					[t('paintNeeded', 'Paint needed'), OUT.litre(litres), true],
					[t('paintedArea', 'Area to paint'), OUT.area(area, v.sys), false]
				]
			};
		},
		rebar: function (v) {
			var d = Number(v.dia);
			var kgm = d * d / 162.2;
			var total = v.count * v.len * (1 + v.waste / 100);
			var weight = total * kgm;
			var rows = [
				[t('steelWeight', 'Steel weight'), OUT.kg(weight), true]
			];
			if (v.sys === 'imperial') {
				rows.push([t('steelWeightLb', 'Steel weight (lb)'), OUT.lb(weight), false]);
			}
			if (weight >= 1000) {
				rows.push([t('steelTonnes', 'Steel weight (tonnes)'), fmt(weight / 1000) + ' t', false]);
			}
			rows.push([t('totalLength', 'Total length'), OUT.len(total, v.sys), false]);
			rows.push([t('weightPerMetre', 'Weight per metre'), OUT.kgm(kgm), false]);
			return { ok: weight > 0, rows: rows };
		}
	};

	function toBase(type, value, sys) {
		if (sys === 'imperial' && TO_METRIC[type]) {
			return value * TO_METRIC[type];
		}
		return value;
	}

	function fromBase(type, value, sys) {
		if (sys === 'imperial' && TO_METRIC[type]) {
			return value / TO_METRIC[type];
		}
		return value;
	}

	function init(root) {
		if (root.getAttribute('data-mc-ready')) {
			return;
		}
		root.setAttribute('data-mc-ready', '1');

		var sys = root.getAttribute('data-unit') === 'imperial' ? 'imperial' : 'metric';
		var tabs = [].slice.call(root.querySelectorAll('.rawnaq-mc-tab'));
		var panels = [].slice.call(root.querySelectorAll('.rawnaq-mc-panel'));
		var unitBtns = [].slice.call(root.querySelectorAll('.rawnaq-mc-unit-btn'));

		function read(panel) {
			var vals = { sys: sys };
			[].forEach.call(panel.querySelectorAll('[data-k]'), function (el) {
				var type = el.getAttribute('data-t');
				if (type === 'select') {
					vals[el.getAttribute('data-k')] = el.value;
					return;
				}
				var n = parseFloat(el.value);
				if (!isFinite(n) || n < 0) {
					n = 0;
				}
				vals[el.getAttribute('data-k')] = toBase(type, n, sys);
			});
			return vals;
		}

		function render(panel) {
			var key = panel.getAttribute('data-calc');
			var out = panel.querySelector('.rawnaq-mc-result');
			var res = CALC[key] ? CALC[key](read(panel)) : { ok: false, rows: [] };
			out.textContent = '';
			if (!res.ok) {
				var p = document.createElement('p');
				p.className = 'rawnaq-mc-empty';
				p.textContent = t('enterValues', 'Enter the sizes to see the quantities.');
				out.appendChild(p);
				return;
			}
			res.rows.forEach(function (row) {
				var d = document.createElement('div');
				d.className = 'rawnaq-mc-row' + (row[2] ? ' is-primary' : '');
				var l = document.createElement('span');
				l.className = 'rawnaq-mc-row-label';
				l.textContent = row[0];
				var v = document.createElement('strong');
				v.className = 'rawnaq-mc-row-value';
				v.textContent = row[1];
				d.appendChild(l);
				d.appendChild(v);
				out.appendChild(d);
			});
		}

		function renderAll() {
			panels.forEach(render);
		}

		function showTab(tab, focus) {
			tabs.forEach(function (tb) {
				var on = tb === tab;
				tb.setAttribute('aria-selected', on ? 'true' : 'false');
				tb.setAttribute('tabindex', on ? '0' : '-1');
			});
			panels.forEach(function (p) {
				p.hidden = p.getAttribute('data-calc') !== tab.getAttribute('data-calc');
			});
			if (focus) {
				tab.focus();
			}
		}

		function setUnit(next, convert) {
			if (next === sys) {
				return;
			}
			var prev = sys;
			if (convert) {
				[].forEach.call(root.querySelectorAll('input[data-k]'), function (el) {
					var type = el.getAttribute('data-t');
					var n = parseFloat(el.value);
					if (!isFinite(n) || !TO_METRIC[type] || TO_METRIC[type] === 1) {
						return;
					}
					el.value = round(fromBase(type, toBase(type, n, prev), next), 2);
				});
			}
			sys = next;
			root.setAttribute('data-unit', sys);
			[].forEach.call(root.querySelectorAll('.rawnaq-mc-unit'), function (u) {
				var lbl = UNIT_LABEL[sys][u.getAttribute('data-unit')];
				if (lbl) {
					u.textContent = '(' + lbl + ')';
				}
			});
			unitBtns.forEach(function (b) {
				b.setAttribute('aria-pressed', b.getAttribute('data-set-unit') === sys ? 'true' : 'false');
			});
			renderAll();
		}

		tabs.forEach(function (tab, i) {
			tab.addEventListener('click', function () {
				showTab(tab, false);
			});
			tab.addEventListener('keydown', function (e) {
				var next = null;
				if (e.key === 'ArrowRight') {
					next = tabs[(i + 1) % tabs.length];
				} else if (e.key === 'ArrowLeft') {
					next = tabs[(i - 1 + tabs.length) % tabs.length];
				} else if (e.key === 'Home') {
					next = tabs[0];
				} else if (e.key === 'End') {
					next = tabs[tabs.length - 1];
				}
				if (next) {
					e.preventDefault();
					showTab(next, true);
				}
			});
		});

		unitBtns.forEach(function (b) {
			b.addEventListener('click', function () {
				setUnit(b.getAttribute('data-set-unit'), true);
			});
		});

		root.addEventListener('input', function (e) {
			var panel = e.target.closest ? e.target.closest('.rawnaq-mc-panel') : null;
			if (panel) {
				render(panel);
			}
		});
		root.addEventListener('change', function (e) {
			var panel = e.target.closest ? e.target.closest('.rawnaq-mc-panel') : null;
			if (panel) {
				render(panel);
			}
		});

		// Markup is rendered in metric; switch to the configured unit once.
		if (sys === 'imperial') {
			sys = 'metric';
			root.setAttribute('data-unit', 'metric');
			setUnit('imperial', true);
		} else {
			renderAll();
		}
	}

	function initAll(scope) {
		[].forEach.call((scope || document).querySelectorAll('[data-rawnaq-mc]'), init);
	}

	window.rawnaqMaterialCalc = { init: initAll };

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			initAll();
		});
	} else {
		initAll();
	}

	// Elementor editor and live preview.
	window.addEventListener('elementor/frontend/init', function () {
		if (window.elementorFrontend && window.elementorFrontend.hooks) {
			window.elementorFrontend.hooks.addAction('frontend/element_ready/rawnaq_material_calc.default', function ($el) {
				initAll($el && $el[0] ? $el[0] : document);
			});
		}
	});
})();
