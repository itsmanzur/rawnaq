/**
 * Rawnaq Smart Form — multi-step, conditionals, files, rating, reCAPTCHA, a11y, RTL
 */
(function () {
	'use strict';

	var bound = false;
	var recaptchaLoading = null;

	function cfgOf(form) {
		try {
			return JSON.parse(form.getAttribute('data-sf') || '{}');
		} catch (e) {
			return {};
		}
	}

	function isReducedMotion() {
		return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
	}

	function t(key, fallback) {
		var str = (window.rawnaqSmartForm && window.rawnaqSmartForm.i18n && window.rawnaqSmartForm.i18n[key])
			? window.rawnaqSmartForm.i18n[key]
			: (fallback || '');
		var args = Array.prototype.slice.call(arguments, 2);
		if (!args.length) {
			return str;
		}
		var idx = 0;
		str = str.replace(/%(\d+)\$([sdf])/g, function (match, num) {
			var n = parseInt(num, 10) - 1;
			return args[n] !== undefined ? args[n] : match;
		});
		str = str.replace(/%[sdf]/g, function () {
			var val = args[idx++];
			return val !== undefined ? val : '';
		});
		return str;
	}

	var getI18n = t;

	function setValidationState(field, isValid, isInvalid, customError) {
		if (!field) return;
		field.classList.toggle('is-valid', !!isValid);
		field.classList.toggle('is-invalid', !!isInvalid);

		var controls = field.querySelectorAll('input:not([type="hidden"]), textarea, select, button[role="radio"], .rawnaq-sf-dropzone');
		controls.forEach(function (ctrl) {
			if (isInvalid) {
				ctrl.setAttribute('aria-invalid', 'true');
			} else if (isValid) {
				ctrl.setAttribute('aria-invalid', 'false');
			} else {
				ctrl.removeAttribute('aria-invalid');
			}
		});

		var errP = field.querySelector('.rawnaq-sf-error');
		if (errP) {
			if (isInvalid && customError) {
				errP.textContent = customError;
			} else if (!isInvalid) {
				errP.textContent = '';
			}
		}
	}

	function fieldVisible(field) {
		return field && !field.hasAttribute('hidden');
	}

	function fieldInActiveStep(field, form) {
		var panel = field && field.closest('.rawnaq-sf-step-panel');
		if (!panel) {
			return true;
		}
		return panel.classList.contains('is-active');
	}

	function extractUtmParams() {
		var utms = {};
		try {
			var params = new URLSearchParams(window.location.search);
			var keys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid'];
			keys.forEach(function (k) {
				if (params.has(k)) {
					utms[k] = params.get(k);
				}
			});
			if (document.referrer) {
				utms['referrer_url'] = document.referrer;
			}
			utms['landing_page'] = window.location.href.split('#')[0];
		} catch (e) { /* ignore */ }
		return utms;
	}

	function focusFirstInvalid(form, firstBad) {
		if (!firstBad) return;
		var panel = firstBad.closest('.rawnaq-sf-step-panel');
		if (panel && !panel.classList.contains('is-active')) {
			var panels = getStepPanels(form);
			var sIdx = panels.indexOf(panel);
			if (sIdx !== -1) {
				syncStepUi(form, sIdx);
			}
		}
		var reduce = isReducedMotion();
		try {
			firstBad.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
		} catch (e) {
			firstBad.scrollIntoView();
		}
		var target = firstBad.querySelector('input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), select:not([disabled]), button[role="radio"], .rawnaq-sf-dropzone, .rawnaq-sf-signature-type-input, .rawnaq-sf-signature-canvas');
		if (target) {
			setTimeout(function () {
				try {
					target.focus();
				} catch (err) { /* ignore */ }
			}, reduce ? 20 : 150);
		}
	}

	function validateField(field, opts) {
		opts = opts || {};
		if (!field || !fieldVisible(field)) {
			return true;
		}
		if (opts.onlyActiveStep && !fieldInActiveStep(field, field.closest('form'))) {
			return true;
		}
		var type = (field.getAttribute('data-type') || '').toLowerCase();
		if (type === 'hidden') {
			return true;
		}

		var ok = true;
		var errorMsg = '';

		// Choice cards / Radio group validation
		if (type === 'cards' || type === 'radio') {
			var checkedRadio = field.querySelector('input[type="radio"]:checked');
			var firstRadio = field.querySelector('input[type="radio"]');
			var isCardReq = field.classList.contains('is-required') || (firstRadio ? (firstRadio.hasAttribute('required') || firstRadio.getAttribute('aria-required') === 'true') : false);
			if (isCardReq && !checkedRadio) {
				ok = false;
				errorMsg = t('errorRequiredField', 'This field is required.');
			} else {
				ok = true;
			}
			if (!opts.silent) {
				setValidationState(field, ok && !!checkedRadio, !ok, errorMsg);
			}
			return ok;
		}

		// Rating validation
		if (type === 'rating') {
			var hiddenRating = field.querySelector('input[data-sf-type="rating"]');
			var rVal = hiddenRating ? String(hiddenRating.value || '').trim() : '';
			var isRatingReq = field.classList.contains('is-required') || (hiddenRating ? (hiddenRating.hasAttribute('required') || hiddenRating.getAttribute('aria-required') === 'true') : false);
			if (isRatingReq && (!rVal || rVal === '0')) {
				ok = false;
				errorMsg = t('errorRatingRequired', 'Please select a rating.');
			} else {
				ok = true;
			}
			if (!opts.silent) {
				setValidationState(field, ok && !!rVal, !ok, errorMsg);
			}
			return ok;
		}

		// Signature validation
		if (type === 'signature') {
			var hiddenSig = field.querySelector('input[data-sf-type="signature"]');
			var sVal = hiddenSig ? String(hiddenSig.value || '').trim() : '';
			var isSigReq = field.classList.contains('is-required') || (hiddenSig ? (hiddenSig.hasAttribute('required') || hiddenSig.getAttribute('aria-required') === 'true') : false);
			if (isSigReq && !sVal) {
				ok = false;
				errorMsg = t('errorRequiredField', 'This field is required.');
			} else {
				ok = true;
			}
			if (!opts.silent) {
				setValidationState(field, ok && !!sVal, !ok, errorMsg);
			}
			return ok;
		}

		// File upload validation
		var fileInput = field.querySelector('input[type="file"]');
		if (fileInput) {
			var isFileReq = field.classList.contains('is-required') || fileInput.hasAttribute('required') || fileInput.getAttribute('aria-required') === 'true';
			var hasFiles = fileInput.files && fileInput.files.length > 0;
			if (isFileReq && !hasFiles) {
				ok = false;
				errorMsg = t('errorRequiredField', 'This field is required.');
			} else if (hasFiles && fileInput.files[0]) {
				var file = fileInput.files[0];
				var maxMb = parseInt(fileInput.getAttribute('data-max-mb') || field.getAttribute('data-max-mb') || '5', 10);
				if (file.size > maxMb * 1024 * 1024) {
					ok = false;
					errorMsg = t('errorFileTooLarge', 'File exceeds maximum size of %s MB.', maxMb);
				} else {
					var accept = (fileInput.getAttribute('accept') || '').trim();
					if (accept) {
						var fileName = (file.name || '').toLowerCase();
						var fileMime = (file.type || '').toLowerCase();
						var patterns = accept.split(',').map(function (p) { return p.trim().toLowerCase(); }).filter(Boolean);
						var matched = patterns.some(function (p) {
							if (p.indexOf('.') === 0) {
								return fileName.lastIndexOf(p) === (fileName.length - p.length);
							}
							if (p.indexOf('/*') !== -1) {
								var prefix = p.split('/')[0] + '/';
								return fileMime.indexOf(prefix) === 0;
							}
							return fileMime === p;
						});
						if (!matched) {
							ok = false;
							errorMsg = t('errorFileType', 'File type not allowed. Accepted: %s', accept);
						}
					}
				}
			}
			if (!opts.silent) {
				setValidationState(field, ok && hasFiles, !ok, errorMsg);
			}
			return ok;
		}

		// Standard inputs (text, email, tel/phone, url, number, textarea, select, checkbox)
		var input = field.querySelector('input:not([type="hidden"]), textarea, select');
		if (!input) {
			return true;
		}

		var required = field.classList.contains('is-required') || input.hasAttribute('required') || input.getAttribute('aria-required') === 'true';
		var sfType = (input.getAttribute('data-sf-type') || input.type || 'text').toLowerCase();
		var val = '';

		if (input.type === 'checkbox') {
			val = input.checked ? '1' : '';
			if (required && !val) {
				ok = false;
				errorMsg = t('errorRequiredField', 'This field is required.');
			}
		} else {
			val = String(input.value || '').trim();
			if (required && !val) {
				ok = false;
				errorMsg = t('errorRequiredField', 'This field is required.');
			} else if (val) {
				if (sfType === 'email') {
					var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
					if (!emailRegex.test(val)) {
						ok = false;
						errorMsg = t('errorInvalidEmail', 'Please enter a valid email address.');
					}
				} else if (sfType === 'phone') {
					var digits = val.replace(/[^\d]/g, '');
					var pMin = parseInt(input.getAttribute('data-phone-min') || field.getAttribute('data-phone-min') || '6', 10);
					var pMax = parseInt(input.getAttribute('data-phone-max') || field.getAttribute('data-phone-max') || '16', 10);
					if (digits.length < pMin || digits.length > pMax) {
						ok = false;
						errorMsg = t('errorInvalidPhone', 'Please enter a valid phone number.');
					}
				} else if (sfType === 'url') {
					var urlRegex = /^https?:\/\/[^\s]+$/i;
					if (!urlRegex.test(val)) {
						ok = false;
						errorMsg = t('errorInvalidUrl', 'Please enter a valid website URL.');
					}
				} else if (sfType === 'number' || sfType === 'range') {
					var nVal = parseFloat(val);
					var hasMin = input.hasAttribute('min');
					var hasMax = input.hasAttribute('max');
					var minAttr = hasMin ? parseFloat(input.getAttribute('min')) : NaN;
					var maxAttr = hasMax ? parseFloat(input.getAttribute('max')) : NaN;
					if (!isNaN(minAttr) && !isNaN(maxAttr) && (nVal < minAttr || nVal > maxAttr)) {
						ok = false;
						errorMsg = t('errorNumberRange', 'Value must be between %1$s and %2$s.', minAttr, maxAttr);
					} else if (!isNaN(minAttr) && nVal < minAttr) {
						ok = false;
						errorMsg = t('errorNumberMin', 'Value must be at least %s.', minAttr);
					} else if (!isNaN(maxAttr) && nVal > maxAttr) {
						ok = false;
						errorMsg = t('errorNumberMax', 'Value must not exceed %s.', maxAttr);
					}
				}
			}
		}

		if (!opts.silent) {
			var showValid = ok && (val.length > 0 || (input.type === 'checkbox' && input.checked));
			setValidationState(field, showValid, !ok, errorMsg);
		}
		return ok;
	}

	function collectPayload(form) {
		var data = {};
		form.querySelectorAll('.rawnaq-sf-field').forEach(function (field) {
			if (!fieldVisible(field) && field.getAttribute('data-show-if')) {
				return;
			}
			var type = (field.getAttribute('data-type') || '').toLowerCase();
			if (type === 'cards' || type === 'radio') {
				var checkedRadio = field.querySelector('input[type="radio"]:checked');
				var radioSample = field.querySelector('input[type="radio"]');
				if (radioSample) {
					var rKey = radioSample.name.replace(/^sf_/, '');
					data[rKey] = checkedRadio ? checkedRadio.value : '';
				}
				return;
			}
			var el = field.querySelector('[name^="sf_"]:not([type="file"])');
			if (!el) return;
			var key = el.name.replace(/^sf_/, '');
			if (el.type === 'checkbox') {
				data[key] = el.checked ? '1' : '';
			} else if (type === 'phone') {
				var phoneWrap = field.querySelector('.rawnaq-sf-phone-wrap');
				var cc = phoneWrap ? phoneWrap.querySelector('.rawnaq-sf-country-code') : null;
				var phoneVal = String(el.value || '').trim();
				if (cc && phoneVal && phoneVal.indexOf('+') !== 0) {
					data[key] = cc.value + ' ' + phoneVal;
				} else {
					data[key] = phoneVal;
				}
			} else {
				data[key] = el.value;
			}
		});

		// Automatically inject UTM attribution
		var utmParams = extractUtmParams();
		for (var u in utmParams) {
			if (utmParams.hasOwnProperty(u) && !data[u]) {
				data[u] = utmParams[u];
			}
		}

		return data;
	}

	function showStatus(form, type, message) {
		var box = form.querySelector('.rawnaq-sf-status');
		if (!box) return;
		box.classList.remove('is-success', 'is-error');
		box.classList.add(type === 'success' ? 'is-success' : 'is-error');
		var text = box.querySelector('.rawnaq-sf-status-text');
		if (text) {
			text.textContent = message || '';
		}
	}

	function hideStatus(form) {
		var box = form.querySelector('.rawnaq-sf-status');
		if (!box) return;
		box.classList.remove('is-success', 'is-error');
	}

	function setLoading(btn, on) {
		if (!btn) return;
		btn.disabled = !!on;
		btn.classList.toggle('is-loading', !!on);
	}

	function applyConditionals(form) {
		var values = {};
		form.querySelectorAll('.rawnaq-sf-field [name^="sf_"]').forEach(function (el) {
			var key = el.name.replace(/^sf_/, '');
			if (el.type === 'checkbox') {
				values[key] = el.checked ? '1' : (el.value || '');
			} else if (el.type === 'radio') {
				if (el.checked) {
					values[key] = el.value;
				}
			} else if (el.type !== 'file') {
				values[key] = el.value;
			}
		});

		form.querySelectorAll('.rawnaq-sf-field[data-show-if]').forEach(function (field) {
			var dep = field.getAttribute('data-show-if');
			var want = field.getAttribute('data-show-if-value') || '';
			var got = values[dep] || '';
			var show = want ? (got === want) : !!got;
			if (show) {
				field.removeAttribute('hidden');
			} else {
				field.setAttribute('hidden', '');
				setValidationState(field, false, false);
			}
		});
	}

	function escapeHtml(str) {
		return String(str || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function renderStepSummary(form) {
		var summaryWrap = form.querySelector('.rawnaq-sf-summary-card');
		if (!summaryWrap) return;
		var payload = collectPayload(form);
		var count = 0;
		var summaryTitle = form.getAttribute('data-summary-title') || t('reviewSubmission', 'Review Your Submission');
		var html = '<div class="rawnaq-sf-summary-header"><span class="rawnaq-sf-summary-icon">📋</span> <strong>' + escapeHtml(summaryTitle) + '</strong></div>';
		html += '<div class="rawnaq-sf-summary-list">';
		form.querySelectorAll('.rawnaq-sf-field:not([data-type="hidden"])').forEach(function (f) {
			if (!fieldVisible(f)) return;
			var fType = (f.getAttribute('data-type') || '').toLowerCase();
			if (fType === 'hidden' || f.getAttribute('data-field') === 'consent') return;
			var labelEl = f.querySelector('.rawnaq-sf-label') || f.querySelector('.rawnaq-sf-card-title');
			var label = labelEl ? labelEl.textContent.replace('*', '').trim() : f.getAttribute('data-field');
			var key = f.getAttribute('data-field');
			var val = payload[key] || '';
			if (val && typeof val === 'string' && val.trim().length > 0) {
				if (val.indexOf('data:image/') === 0) {
					val = '[' + t('signaturePad', 'Signature pad') + ']';
				} else if (val.indexOf('/') !== -1 || val.indexOf('\\') !== -1) {
					var parts = val.split(/[/\\]/);
					val = parts[parts.length - 1];
				}
				count++;
				html += '<div class="rawnaq-sf-summary-item"><span class="rawnaq-sf-summary-k">' + escapeHtml(label) + '</span><span class="rawnaq-sf-summary-v">' + escapeHtml(val) + '</span></div>';
			}
		});
		html += '</div>';
		if (count > 0) {
			summaryWrap.innerHTML = html;
			summaryWrap.removeAttribute('hidden');
		} else {
			summaryWrap.setAttribute('hidden', '');
		}
	}

	function getStepPanels(form) {
		return Array.prototype.slice.call(form.querySelectorAll('.rawnaq-sf-step-panel'));
	}

	function syncStepUi(form, index, direction) {
		direction = direction || 'forward';
		var panels = getStepPanels(form);
		var dots = form.querySelectorAll('.rawnaq-sf-step-dot');
		var progressBar = form.querySelector('.rawnaq-sf-progress-fill');
		var reduceMotion = isReducedMotion();

		panels.forEach(function (p, i) {
			var on = i === index;
			p.classList.remove('is-slide-forward', 'is-slide-backward');
			if (on) {
				p.classList.add('is-active');
				if (!reduceMotion) {
					p.classList.add(direction === 'backward' ? 'is-slide-backward' : 'is-slide-forward');
				}
				p.removeAttribute('hidden');
			} else {
				p.classList.remove('is-active');
				p.setAttribute('hidden', '');
			}
		});

		dots.forEach(function (d, i) {
			var isActive = (i === index);
			d.classList.toggle('is-active', isActive);
			d.classList.toggle('is-passed', i < index);
			if (isActive) {
				d.setAttribute('aria-current', 'step');
			} else {
				d.removeAttribute('aria-current');
			}
		});

		var announcement = form.querySelector('.rawnaq-sf-step-announcement');
		if (announcement && panels.length > 1) {
			announcement.textContent = t('stepAnnounce', 'Step %1$d of %2$d', index + 1, panels.length);
		}

		if (progressBar && panels.length > 1) {
			var pct = Math.round((index / (panels.length - 1)) * 100);
			progressBar.style.width = pct + '%';
		}

		var prev = form.querySelector('.rawnaq-sf-prev');
		var next = form.querySelector('.rawnaq-sf-next');
		var submit = form.querySelector('.rawnaq-sf-submit');
		var consentWrap = form.querySelector('[data-consent-last]');
		var last = index >= panels.length - 1;

		if (prev) {
			if (index > 0) prev.removeAttribute('hidden');
			else prev.setAttribute('hidden', '');
		}
		if (next) {
			if (last) next.setAttribute('hidden', '');
			else next.removeAttribute('hidden');
		}
		if (submit) {
			if (last || panels.length < 2) submit.removeAttribute('hidden');
			else submit.setAttribute('hidden', '');
		}
		if (consentWrap) {
			if (last) consentWrap.removeAttribute('hidden');
			else consentWrap.setAttribute('hidden', '');
		}

		if (last && panels.length > 1) {
			renderStepSummary(form);
		}

		form.setAttribute('data-step-index', String(index));
	}

	function validateCurrentStep(form) {
		var panels = getStepPanels(form);
		var idx = parseInt(form.getAttribute('data-step-index') || '0', 10) || 0;
		var panel = panels[idx] || form;
		var ok = true;
		var firstBad = null;
		panel.querySelectorAll('.rawnaq-sf-field').forEach(function (field) {
			if (!validateField(field, { onlyActiveStep: true })) {
				ok = false;
				if (!firstBad) firstBad = field;
				if (!isReducedMotion()) {
					field.classList.add('is-shake');
					setTimeout(function () { field.classList.remove('is-shake'); }, 450);
				}
			}
		});
		if (!ok && firstBad) {
			focusFirstInvalid(form, firstBad);
		}
		return ok;
	}

	function loadRecaptcha(siteKey) {
		if (window.grecaptcha && grecaptcha.execute) {
			return Promise.resolve();
		}
		if (recaptchaLoading) {
			return recaptchaLoading;
		}
		recaptchaLoading = new Promise(function (resolve, reject) {
			var s = document.createElement('script');
			s.src = 'https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent(siteKey);
			s.async = true;
			s.onload = function () { resolve(); };
			s.onerror = function () { reject(); };
			document.head.appendChild(s);
		});
		return recaptchaLoading;
	}

	function getRecaptchaToken(cfg) {
		var key = (window.rawnaqSmartForm && rawnaqSmartForm.recaptchaSiteKey) || '';
		if (!cfg.recaptchaEnabled || !key) {
			return Promise.resolve('');
		}
		return loadRecaptcha(key).then(function () {
			return new Promise(function (resolve) {
				grecaptcha.ready(function () {
					grecaptcha.execute(key, { action: 'smart_form' }).then(resolve).catch(function () {
						resolve('');
					});
				});
			});
		});
	}

	function launchConfetti(form) {
		if (isReducedMotion()) {
			return;
		}
		var wrap = document.createElement('div');
		wrap.className = 'rawnaq-sf-confetti-wrap';
		var colors = ['#0f766e', '#10b981', '#fbbf24', '#6366f1', '#ec4899', '#3b82f6'];
		for (var i = 0; i < 36; i++) {
			var p = document.createElement('div');
			p.className = 'rawnaq-sf-confetti-particle';
			p.style.left = (Math.random() * 100) + '%';
			p.style.top = (Math.random() * 20) + 'px';
			p.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
			p.style.animationDelay = (Math.random() * 0.4) + 's';
			p.style.width = (Math.random() * 6 + 6) + 'px';
			p.style.height = (Math.random() * 6 + 6) + 'px';
			wrap.appendChild(p);
		}
		form.style.position = 'relative';
		form.appendChild(wrap);
		setTimeout(function () {
			if (wrap.parentNode) wrap.parentNode.removeChild(wrap);
		}, 2500);
	}

	function fetchFreshToken(form) {
		var formId = form.getAttribute('data-form-id') || '';
		if (!formId) {
			return Promise.reject(new Error('NO_FORM_ID'));
		}
		var endpoint = (window.rawnaqSmartForm && rawnaqSmartForm.ajaxUrl)
			? rawnaqSmartForm.ajaxUrl
			: (window.ajaxurl || '/wp-admin/admin-ajax.php');
		var sep = endpoint.indexOf('?') === -1 ? '?' : '&';
		var url = endpoint + sep + 'action=rawnaq_sf_token&form_id=' + encodeURIComponent(formId);

		return fetch(url, {
			method: 'GET',
			credentials: 'same-origin',
			headers: { 'Cache-Control': 'no-cache' }
		}).then(function (r) {
			return r.json();
		}).then(function (res) {
			var data = (res && res.data) ? res.data : res;
			if (data && data.nonce && data.ts) {
				form._sfNonce = data.nonce;
				var tsInput = form.querySelector('[name="rawnaq_ts"]');
				if (tsInput) {
					tsInput.value = data.ts;
					tsInput.defaultValue = data.ts;
				}
				return data;
			}
			throw new Error('INVALID_TOKEN_RESPONSE');
		});
	}

	function doSubmit(form, isRetry) {
		isRetry = !!isRetry;
		var btn = form.querySelector('.rawnaq-sf-submit');
		if (!isRetry && btn && (btn.classList.contains('is-loading') || btn.disabled)) {
			return;
		}

		var cfg = cfgOf(form);
		hideStatus(form);

		var fields = form.querySelectorAll('.rawnaq-sf-field');
		var valid = true;
		var firstBad = null;
		fields.forEach(function (field) {
			if (!validateField(field)) {
				valid = false;
				if (!firstBad) firstBad = field;
				if (!isReducedMotion()) {
					field.classList.add('is-shake');
					setTimeout(function () { field.classList.remove('is-shake'); }, 450);
				}
			}
		});
		if (!valid) {
			showStatus(form, 'error', cfg.errorMessage || t('errorRequired', 'Please fill in the required fields correctly.'));
			if (firstBad) {
				focusFirstInvalid(form, firstBad);
			}
			return;
		}

		setLoading(btn, true);

		return getRecaptchaToken(cfg).then(function (token) {
			var fd = new FormData();
			fd.append('action', 'rawnaq_smart_form_submit');
			var nonceVal = form._sfNonce || (window.rawnaqSmartForm && rawnaqSmartForm.nonce) || '';
			fd.append('nonce', nonceVal);
			fd.append('form_id', form.getAttribute('data-form-id') || '');
			fd.append('rawnaq_hp', (form.querySelector('[name="rawnaq_hp"]') || {}).value || '');
			fd.append('rawnaq_ts', (form.querySelector('[name="rawnaq_ts"]') || {}).value || '');
			if (token) {
				fd.append('rawnaq_recaptcha', token);
				var hid = form.querySelector('.rawnaq-sf-recaptcha');
				if (hid) hid.value = token;
			}

			var values = collectPayload(form);
			Object.keys(values).forEach(function (k) {
				fd.append('fields[' + k + ']', values[k]);
			});

			form.querySelectorAll('input[type="file"]').forEach(function (input) {
				var field = input.closest('.rawnaq-sf-field');
				if (input.files && input.files[0] && fieldVisible(field)) {
					fd.append(input.name, input.files[0]);
				}
			});

			var endpoint = (window.rawnaqSmartForm && rawnaqSmartForm.ajaxUrl)
				? rawnaqSmartForm.ajaxUrl
				: (window.ajaxurl || '/wp-admin/admin-ajax.php');

			return fetch(endpoint, {
				method: 'POST',
				body: fd,
				credentials: 'same-origin'
			}).then(function (r) {
				var is403 = (r.status === 403);
				return r.text().then(function (text) {
					var parsed = null;
					var trimmed = (text || '').trim();
					if (trimmed !== '-1' && trimmed !== '0') {
						try {
							parsed = JSON.parse(text);
						} catch (e) {
							parsed = null;
						}
					}
					if (is403 || !parsed || typeof parsed !== 'object') {
						if (!isRetry) {
							return fetchFreshToken(form).then(function () {
								return doSubmit(form, true);
							}).then(function () {
								return null;
							});
						}
						var sessionErr = new Error('SESSION_EXPIRED');
						sessionErr.isSessionExpired = true;
						throw sessionErr;
					}
					return parsed;
				});
			});
		}).then(function (json) {
			if (!json) {
				return;
			}
			setLoading(btn, false);
			if (!json.success) {
				var firstBad = null;
				if (json.data && json.data.fields && typeof json.data.fields === 'object') {
					var fieldErrors = json.data.fields;
					Object.keys(fieldErrors).forEach(function (fId) {
						var fWrap = form.querySelector('.rawnaq-sf-field[data-field="' + fId + '"]');
						if (fWrap) {
							setValidationState(fWrap, false, true, fieldErrors[fId]);
							if (!isReducedMotion()) {
								fWrap.classList.add('is-shake');
								setTimeout(function () { fWrap.classList.remove('is-shake'); }, 450);
							}
							if (!firstBad) firstBad = fWrap;
						}
					});
				}
				var msg = (json && json.data && json.data.message)
					? json.data.message
					: (cfg.errorMessage || t('errorGeneric', 'Something went wrong. Please try again.'));
				showStatus(form, 'error', msg);
				if (firstBad) {
					focusFirstInvalid(form, firstBad);
				}
				return;
			}

			var data = json.data || {};
			var statusBox = form.querySelector('.rawnaq-sf-status');
			var successMsg = data.message || cfg.successMessage || t('successDefault', 'Message sent successfully.');
			showStatus(form, 'success', successMsg);
			if (statusBox) {
				try {
					statusBox.focus();
				} catch (focusErr) { /* ignore */ }
			}

			if (!data.duplicate) {
				try {
					if (cfg.celebrationEffect !== false && !isReducedMotion()) {
						launchConfetti(form);
					}
					form.reset();
					fields.forEach(function (f) {
						setValidationState(f, false, false);
					});
					form.querySelectorAll('.rawnaq-sf-preview-wrap').forEach(function (p) {
						p.innerHTML = '';
					});
					form.querySelectorAll('.rawnaq-sf-star').forEach(function (s) {
						s.classList.remove('is-on');
						s.setAttribute('aria-checked', 'false');
					});
					var firstStar = form.querySelector('.rawnaq-sf-star');
					if (firstStar) firstStar.tabIndex = 0;
					applyConditionals(form);
					syncStepUi(form, 0);
				} catch (uiErr) {
					if (window.console && console.error) {
						console.error('Smart Form post-success UI reset error:', uiErr);
					}
				}

				var delay = 700;
				if (data.whatsappUrl && (data.openWhatsapp || cfg.afterSubmit === 'whatsapp')) {
					setTimeout(function () {
						window.location.href = data.whatsappUrl;
					}, delay);
				} else if (data.redirectUrl) {
					setTimeout(function () {
						window.location.href = data.redirectUrl;
					}, delay);
				}
			}
		}).catch(function (err) {
			setLoading(btn, false);
			var isExpired = err && (err.isSessionExpired || err.message === 'SESSION_EXPIRED');
			var msg = isExpired
				? t('sessionExpired', 'Your session expired. Please reload the page and try again.')
				: (cfg.errorMessage || t('errorGeneric', 'Something went wrong. Please try again.'));
			showStatus(form, 'error', msg);
		});
	}

	function onSubmit(e) {
		e.preventDefault();
		doSubmit(e.currentTarget);
	}

	function setupDropzone(form) {
		form.querySelectorAll('.rawnaq-sf-dropzone').forEach(function (zone) {
			var fileInput = zone.querySelector('input[type="file"]');
			if (!fileInput) return;
			var previewWrap = zone.parentNode.querySelector('.rawnaq-sf-preview-wrap');
			if (!previewWrap) {
				previewWrap = document.createElement('div');
				previewWrap.className = 'rawnaq-sf-preview-wrap';
				zone.parentNode.appendChild(previewWrap);
			}

			var announcement = zone.parentNode.querySelector('.rawnaq-sf-dropzone-announcement');
			if (!announcement) {
				announcement = document.createElement('div');
				announcement.className = 'rawnaq-sf-dropzone-announcement rawnaq-sf-sr-only';
				announcement.setAttribute('aria-live', 'polite');
				announcement.setAttribute('aria-atomic', 'true');
				zone.parentNode.appendChild(announcement);
			}

			function updatePreviews(files) {
				previewWrap.innerHTML = '';
				if (!files || !files.length) {
					announcement.textContent = '';
					return;
				}
				Array.prototype.forEach.call(files, function (file) {
					var chip = document.createElement('div');
					chip.className = 'rawnaq-sf-preview-chip';
					var sizeKb = Math.round(file.size / 1024);
					var sizeMbUnit = t('fileMb', 'MB');
					var sizeKbUnit = t('fileKb', 'KB');
					var sizeStr = sizeKb > 1024 ? (sizeKb / 1024).toFixed(1) + ' ' + sizeMbUnit : sizeKb + ' ' + sizeKbUnit;

					announcement.textContent = t('fileAttached', 'File selected: %1$s (%2$s)', file.name, sizeStr);

					if (file.type && file.type.indexOf('image/') === 0) {
						var img = document.createElement('img');
						img.className = 'rawnaq-sf-preview-thumb';
						var reader = new FileReader();
						reader.onload = function (e) { img.src = e.target.result; };
						reader.readAsDataURL(file);
						chip.appendChild(img);
					}

					var nameSpan = document.createElement('span');
					nameSpan.textContent = file.name + ' (' + sizeStr + ')';
					chip.appendChild(nameSpan);

					var removeBtn = document.createElement('button');
					var removeLabel = t('removeFileWithName', 'Remove file: %s', file.name);
					removeBtn.type = 'button';
					removeBtn.className = 'rawnaq-sf-preview-remove';
					removeBtn.innerHTML = '&times;';
					removeBtn.title = removeLabel;
					removeBtn.setAttribute('aria-label', removeLabel);
					removeBtn.addEventListener('click', function (e) {
						e.stopPropagation();
						fileInput.value = '';
						previewWrap.innerHTML = '';
						announcement.textContent = '';
						validateField(zone.closest('.rawnaq-sf-field'));
					});
					chip.appendChild(removeBtn);
					previewWrap.appendChild(chip);
				});
			}

			zone.addEventListener('keydown', function (e) {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					fileInput.click();
				}
			});

			['dragenter', 'dragover'].forEach(function (evt) {
				zone.addEventListener(evt, function (e) {
					e.preventDefault();
					e.stopPropagation();
					zone.classList.add('is-dragover');
				});
			});

			['dragleave', 'drop'].forEach(function (evt) {
				zone.addEventListener(evt, function (e) {
					e.preventDefault();
					e.stopPropagation();
					zone.classList.remove('is-dragover');
				});
			});

			zone.addEventListener('drop', function (e) {
				if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
					fileInput.files = e.dataTransfer.files;
					updatePreviews(fileInput.files);
					validateField(zone.closest('.rawnaq-sf-field'));
				}
			});

			fileInput.addEventListener('change', function () {
				updatePreviews(fileInput.files);
				validateField(zone.closest('.rawnaq-sf-field'));
			});
		});
	}

	function setupSignaturePad(form) {
		form.querySelectorAll('.rawnaq-sf-signature-wrap').forEach(function (wrap) {
			var canvas = wrap.querySelector('.rawnaq-sf-signature-canvas');
			var input = wrap.querySelector('input[data-sf-type="signature"]');
			var clearBtn = wrap.querySelector('.rawnaq-sf-signature-clear');
			var toggleBtn = wrap.querySelector('.rawnaq-sf-signature-toggle-mode');
			var typeWrap = wrap.querySelector('.rawnaq-sf-signature-type-wrap');
			var typeInput = wrap.querySelector('.rawnaq-sf-signature-type-input');
			if (!canvas || !input) return;

			var ctx = canvas.getContext('2d');
			var drawing = false;
			var hasDrawn = false;

			var ratio = Math.max(window.devicePixelRatio || 1, 1);
			var rect = canvas.getBoundingClientRect();
			var w = rect.width || 480;
			var h = 140;
			canvas.width = w * ratio;
			canvas.height = h * ratio;
			canvas.style.width = '100%';
			canvas.style.height = h + 'px';
			ctx.scale(ratio, ratio);

			ctx.lineCap = 'round';
			ctx.lineJoin = 'round';
			ctx.lineWidth = 2.5;
			ctx.strokeStyle = '#1e1b2e';

			function getPos(e) {
				var r = canvas.getBoundingClientRect();
				var clientX = e.clientX;
				var clientY = e.clientY;
				if (e.touches && e.touches[0]) {
					clientX = e.touches[0].clientX;
					clientY = e.touches[0].clientY;
				}
				return {
					x: clientX - r.left,
					y: clientY - r.top
				};
			}

			function startDraw(e) {
				e.preventDefault();
				drawing = true;
				hasDrawn = true;
				var pos = getPos(e);
				ctx.beginPath();
				ctx.moveTo(pos.x, pos.y);
			}

			function moveDraw(e) {
				if (!drawing) return;
				e.preventDefault();
				var pos = getPos(e);
				ctx.lineTo(pos.x, pos.y);
				ctx.stroke();
			}

			function endDraw(e) {
				if (!drawing) return;
				drawing = false;
				if (hasDrawn) {
					input.value = canvas.toDataURL('image/png');
					var field = wrap.closest('.rawnaq-sf-field');
					if (field) {
						validateField(field);
					}
				}
			}

			canvas.addEventListener('mousedown', startDraw);
			canvas.addEventListener('mousemove', moveDraw);
			window.addEventListener('mouseup', endDraw);

			canvas.addEventListener('touchstart', startDraw, { passive: false });
			canvas.addEventListener('touchmove', moveDraw, { passive: false });
			canvas.addEventListener('touchend', endDraw);

			function renderTypedSignature(name) {
				ctx.clearRect(0, 0, canvas.width, canvas.height);
				name = String(name || '').trim();
				if (!name) {
					input.value = '';
					hasDrawn = false;
				} else {
					hasDrawn = true;
					ctx.save();
					ctx.font = 'italic 34px "Brush Script MT", "Caveat", "Dancing Script", cursive, sans-serif';
					ctx.fillStyle = '#1e1b2e';
					ctx.textBaseline = 'middle';
					var isRtl = (document.dir === 'rtl') || !!wrap.closest('[dir="rtl"], .rtl');
					ctx.textAlign = isRtl ? 'right' : 'left';
					var xPos = isRtl ? ((w || 480) - 24) : 24;
					var yPos = (h || 140) / 2;
					ctx.fillText(name, xPos, yPos);
					ctx.restore();
					input.value = canvas.toDataURL('image/png');
				}
				var field = wrap.closest('.rawnaq-sf-field');
				if (field) {
					validateField(field, { silent: true });
				}
			}

			if (typeInput) {
				typeInput.addEventListener('input', function () {
					renderTypedSignature(typeInput.value);
				});
			}

			if (toggleBtn && typeWrap) {
				toggleBtn.addEventListener('click', function (e) {
					e.preventDefault();
					var currentMode = toggleBtn.getAttribute('data-mode') || 'draw';
					if (currentMode === 'draw') {
						toggleBtn.setAttribute('data-mode', 'type');
						toggleBtn.textContent = t('drawSignature', 'Draw signature instead');
						typeWrap.removeAttribute('hidden');
						canvas.setAttribute('hidden', '');
						if (typeInput) {
							typeInput.focus();
							if (typeInput.value) {
								renderTypedSignature(typeInput.value);
							}
						}
					} else {
						toggleBtn.setAttribute('data-mode', 'draw');
						toggleBtn.textContent = t('typeSignature', 'Type your name instead');
						typeWrap.setAttribute('hidden', '');
						canvas.removeAttribute('hidden');
					}
				});
			}

			if (clearBtn) {
				clearBtn.addEventListener('click', function (e) {
					e.preventDefault();
					ctx.clearRect(0, 0, canvas.width, canvas.height);
					input.value = '';
					hasDrawn = false;
					if (typeInput) {
						typeInput.value = '';
					}
					var field = wrap.closest('.rawnaq-sf-field');
					if (field) {
						validateField(field);
					}
				});
			}
		});
	}

	function setupRating(form) {
		form.querySelectorAll('.rawnaq-sf-rating').forEach(function (wrap) {
			var input = wrap.querySelector('input[data-sf-type="rating"]');
			var stars = Array.prototype.slice.call(wrap.querySelectorAll('.rawnaq-sf-star'));
			var max = parseInt(wrap.getAttribute('data-max') || String(stars.length) || '5', 10);

			function setRating(val, moveFocus) {
				val = parseInt(val, 10) || 0;
				val = Math.max(0, Math.min(val, max));
				if (input) {
					input.value = val > 0 ? String(val) : '';
				}
				stars.forEach(function (s, idx) {
					var sVal = idx + 1;
					var isSelected = (sVal === val);
					var isOn = (sVal <= val);
					s.classList.toggle('is-on', isOn);
					s.setAttribute('aria-checked', isSelected ? 'true' : 'false');
					var isFocusable = (val > 0) ? isSelected : (idx === 0);
					s.tabIndex = isFocusable ? 0 : -1;
					s.setAttribute('aria-label', t('ratingStar', '%1$d out of %2$d stars', sVal, max));
				});
				if (moveFocus && val > 0 && stars[val - 1]) {
					try {
						stars[val - 1].focus();
					} catch (e) { /* ignore */ }
				}
				var field = wrap.closest('.rawnaq-sf-field');
				if (field) {
					validateField(field);
				}
			}

			stars.forEach(function (star, idx) {
				var sVal = idx + 1;
				star.setAttribute('aria-label', t('ratingStar', '%1$d out of %2$d stars', sVal, max));

				star.addEventListener('click', function () {
					setRating(sVal, false);
				});

				star.addEventListener('keydown', function (e) {
					var isRtl = (document.dir === 'rtl') || !!wrap.closest('[dir="rtl"], .rtl');
					var currentVal = parseInt((input && input.value) || '0', 10);
					var handled = false;

					switch (e.key) {
						case 'ArrowRight':
							var deltaR = isRtl ? -1 : 1;
							var nextR = currentVal + deltaR;
							if (nextR < 1) nextR = 1;
							if (nextR > max) nextR = max;
							setRating(nextR, true);
							handled = true;
							break;
						case 'ArrowLeft':
							var deltaL = isRtl ? 1 : -1;
							var nextL = currentVal + deltaL;
							if (nextL < 1) nextL = 1;
							if (nextL > max) nextL = max;
							setRating(nextL, true);
							handled = true;
							break;
						case 'ArrowUp':
							var nextUp = Math.min(max, (currentVal || 0) + 1);
							setRating(nextUp, true);
							handled = true;
							break;
						case 'ArrowDown':
							var nextDown = Math.max(1, (currentVal || 1) - 1);
							setRating(nextDown, true);
							handled = true;
							break;
						case 'Home':
							setRating(1, true);
							handled = true;
							break;
						case 'End':
							setRating(max, true);
							handled = true;
							break;
						case ' ':
						case 'Enter':
							setRating(sVal, true);
							handled = true;
							break;
					}
					if (handled) {
						e.preventDefault();
					}
				});
			});
		});
	}

	function setupRangeSliders(form) {
		form.querySelectorAll('.rawnaq-sf-range-wrap').forEach(function (wrap) {
			var input = wrap.querySelector('.rawnaq-sf-range-input');
			var valDisplay = wrap.querySelector('.rawnaq-sf-range-val');
			var prefix = wrap.getAttribute('data-prefix') || '';
			var suffix = wrap.getAttribute('data-suffix') || '';
			if (!input || !valDisplay) return;

			function syncVal() {
				valDisplay.textContent = prefix + input.value + suffix;
				var cfg = cfgOf(form);
				if (cfg && cfg.estimator) {
					updatePriceEstimator(form);
				}
			}

			input.addEventListener('input', syncVal);
			input.addEventListener('change', syncVal);
			syncVal();
		});
	}

	function parsePrice(text) {
		if (!text) return 0;
		var m = String(text).match(/\$\s*([\d,]+(?:\.\d+)?)/) || String(text).match(/([\d,]+(?:\.\d+)?)\s*\$/);
		if (m && m[1]) {
			return parseFloat(m[1].replace(/,/g, '')) || 0;
		}
		return 0;
	}

	function updatePriceEstimator(form) {
		var cfg = cfgOf(form);
		if (!cfg || !cfg.estimator) return;
		var card = form.querySelector('.rawnaq-sf-estimator-card');
		if (!card) return;
		var total = 0;
		var foundAny = false;

		form.querySelectorAll('.rawnaq-sf-field input[type="radio"]:checked').forEach(function (r) {
			var p = parsePrice(r.value);
			var optLabel = r.closest('.rawnaq-sf-card-option');
			if (optLabel && !p) {
				p = parsePrice(optLabel.textContent);
			}
			if (p > 0) {
				total += p;
				foundAny = true;
			}
		});

		form.querySelectorAll('.rawnaq-sf-select').forEach(function (sel) {
			var opt = sel.options[sel.selectedIndex];
			if (opt) {
				var p = parsePrice(opt.text || opt.value);
				if (p > 0) {
					total += p;
					foundAny = true;
				}
			}
		});

		form.querySelectorAll('.rawnaq-sf-check input[type="checkbox"]:checked').forEach(function (cb) {
			var p = parsePrice(cb.parentNode ? cb.parentNode.textContent : '');
			if (p > 0) {
				total += p;
				foundAny = true;
			}
		});

		form.querySelectorAll('.rawnaq-sf-range-wrap').forEach(function (wrap) {
			var prefix = wrap.getAttribute('data-prefix') || '';
			var suffix = wrap.getAttribute('data-suffix') || '';
			if (prefix.indexOf('$') !== -1 || suffix.indexOf('$') !== -1) {
				var input = wrap.querySelector('.rawnaq-sf-range-input');
				if (input) {
					var val = parseFloat(input.value) || 0;
					total += val;
					foundAny = true;
				}
			}
		});

		var totalEl = card.querySelector('.rawnaq-sf-estimator-total');
		if (totalEl) {
			var curr = cfg.estimatorCurrency || '$';
			var formatted = total.toLocaleString();
			if (window.Intl && Intl.NumberFormat) {
				try {
					formatted = new Intl.NumberFormat().format(total);
				} catch (e) {
					formatted = total.toLocaleString();
				}
			}
			totalEl.textContent = curr + formatted;
		}

		var labelEl = card.querySelector('.rawnaq-sf-estimator-label');
		if (labelEl) {
			var labelText = cfg.estimatorLabel || t('estimatedTotal', 'Estimated total');
			labelEl.textContent = labelText + ':';
		}

		if (foundAny && total > 0) {
			card.removeAttribute('hidden');
		} else {
			card.setAttribute('hidden', '');
		}
	}

	function bindOne(form) {
		if (!form || form.classList.contains('sf-bound')) {
			return;
		}
		form.classList.add('sf-bound');
		form.addEventListener('focusin', function () {
			if (!form._sfTokenFetched) {
				form._sfTokenFetched = true;
				fetchFreshToken(form).catch(function () {
					// Silently retain initial localized nonce/ts
				});
			}
			if (!form._sfRecaptchaPreloaded) {
				form._sfRecaptchaPreloaded = true;
				var formCfg = cfgOf(form);
				var rcKey = (window.rawnaqSmartForm && rawnaqSmartForm.recaptchaSiteKey) || '';
				if (formCfg && formCfg.recaptchaEnabled && rcKey) {
					loadRecaptcha(rcKey).catch(function () {});
				}
			}
		});

		applyConditionals(form);
		syncStepUi(form, 0);
		setupDropzone(form);
		setupSignaturePad(form);
		setupRating(form);
		setupRangeSliders(form);

		var cfg = cfgOf(form);
		if (cfg && cfg.estimator) {
			updatePriceEstimator(form);
			form.addEventListener('change', function () {
				updatePriceEstimator(form);
			});
		}

		form.addEventListener('submit', onSubmit);

		form.querySelectorAll('.rawnaq-sf-card-option').forEach(function (optLabel) {
			var radio = optLabel.querySelector('input[type="radio"]');
			if (!radio) return;
			optLabel.addEventListener('click', function () {
				var grid = optLabel.closest('.rawnaq-sf-cards-grid');
				if (grid) {
					grid.querySelectorAll('.rawnaq-sf-card-option').forEach(function (c) {
						c.classList.remove('is-selected');
					});
				}
				optLabel.classList.add('is-selected');
				radio.checked = true;
				applyConditionals(form);
				var field = optLabel.closest('.rawnaq-sf-field');
				if (field) {
					validateField(field);
				}
			});
			if (radio.checked) {
				optLabel.classList.add('is-selected');
			}
		});

		form.querySelectorAll('.rawnaq-sf-country-code').forEach(function (cc) {
			cc.addEventListener('change', function () {
				var field = cc.closest('.rawnaq-sf-field');
				if (field) {
					validateField(field);
				}
			});
		});

		form.querySelectorAll('.rawnaq-sf-field input, .rawnaq-sf-field textarea, .rawnaq-sf-field select').forEach(function (el) {
			if (el.type === 'hidden' || el.type === 'radio') {
				return;
			}
			el.addEventListener('blur', function () {
				var field = el.closest('.rawnaq-sf-field');
				if (!field) return;
				var val = String(el.value || '').trim();
				var isOk = validateField(field);
				if (val.length > 0) {
					field.classList.toggle('is-valid', isOk);
					field.classList.toggle('is-invalid', !isOk);
				} else if (!el.hasAttribute('required') && el.getAttribute('aria-required') !== 'true') {
					field.classList.remove('is-valid', 'is-invalid');
				}
			});
			el.addEventListener('input', function () {
				applyConditionals(form);
				var field = el.closest('.rawnaq-sf-field');
				if (!field) return;
				var val = String(el.value || '').trim();
				var sfType = (el.getAttribute('data-sf-type') || el.type || 'text').toLowerCase();
				if (field.classList.contains('is-invalid')) {
					validateField(field);
				} else if (val.length > 0 && (sfType === 'email' || sfType === 'phone' || sfType === 'url')) {
					var isOk = validateField(field, { silent: true });
					field.classList.toggle('is-valid', isOk);
				}
			});
			el.addEventListener('change', function () {
				applyConditionals(form);
				var field = el.closest('.rawnaq-sf-field');
				if (field) {
					validateField(field);
				}
			});
			// Conversational Enter key navigation in multi-step form & double-submit protection
			el.addEventListener('keydown', function (e) {
				if (e.key === 'Enter' && el.tagName !== 'TEXTAREA') {
					var nextBtn = form.querySelector('.rawnaq-sf-next');
					if (nextBtn && fieldVisible(nextBtn)) {
						e.preventDefault();
						nextBtn.click();
					} else {
						var submitBtn = form.querySelector('.rawnaq-sf-submit');
						if (submitBtn && submitBtn.classList.contains('is-loading')) {
							e.preventDefault();
						}
					}
				}
			});
		});

		var next = form.querySelector('.rawnaq-sf-next');
		var prev = form.querySelector('.rawnaq-sf-prev');
		if (next) {
			next.addEventListener('click', function () {
				var formCfg = cfgOf(form);
				if (!validateCurrentStep(form)) {
					showStatus(form, 'error', formCfg.errorMessage || t('errorRequired', 'Please fill in the required fields correctly.'));
					return;
				}
				hideStatus(form);
				var idx = parseInt(form.getAttribute('data-step-index') || '0', 10) || 0;
				var nextIdx = idx + 1;
				syncStepUi(form, nextIdx, 'forward');
				var panels = getStepPanels(form);
				if (panels[nextIdx]) {
					var firstInput = panels[nextIdx].querySelector('input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), select:not([disabled]), button[role="radio"]');
					if (firstInput) {
						var reduce = isReducedMotion();
						setTimeout(function () {
							try { firstInput.focus(); } catch (e) {}
						}, reduce ? 20 : 100);
					}
				}
			});
		}
		if (prev) {
			prev.addEventListener('click', function () {
				hideStatus(form);
				var idx = parseInt(form.getAttribute('data-step-index') || '0', 10) || 0;
				var prevIdx = Math.max(0, idx - 1);
				syncStepUi(form, prevIdx, 'backward');
				var panels = getStepPanels(form);
				if (panels[prevIdx]) {
					var firstInput = panels[prevIdx].querySelector('input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), select:not([disabled]), button[role="radio"]');
					if (firstInput) {
						var reduce = isReducedMotion();
						setTimeout(function () {
							try { firstInput.focus(); } catch (e) {}
						}, reduce ? 20 : 100);
					}
				}
			});
		}

		applyUrlPrefill(form);
	}

	function prefillFormWithData(form, data, options) {
		options = options || {};
		if (!form || !data) return;
		var fieldsFound = false;

		for (var key in data) {
			if (!data.hasOwnProperty(key)) continue;
			var val = data[key];
			if (val === undefined || val === null || val === '') continue;

			var field = form.querySelector('.rawnaq-sf-field[data-field="' + key + '"]') ||
						form.querySelector('.rawnaq-sf-field[data-field="' + key.toLowerCase() + '"]');

			var input = null;
			if (field) {
				var fType = (field.getAttribute('data-type') || '').toLowerCase();
				if (options.fromUrl && fType === 'hidden') {
					continue;
				}
				input = field.querySelector('input:not([type="hidden"]), textarea, select');
			} else if (!options.fromUrl) {
				input = form.querySelector('[name="sf_' + key + '"]:not([type="hidden"])') || form.querySelector('[name="' + key + '"]:not([type="hidden"])');
			}

			if (!input && !field && !options.fromUrl) {
				if (key === 'projectTitle' || key === 'title' || key === 'subject') {
					input = form.querySelector('[name="sf_project_overview"], [name="sf_project_details"], [name="sf_project_description"], [name="sf_message"], [name="sf_subject"]');
				} else if (key === 'service' || key === 'services') {
					input = form.querySelector('[name="sf_service"], [name="sf_project_type"]');
				} else if (key === 'budget') {
					input = form.querySelector('[name="sf_budget"], [name="sf_budget_tier"]');
				}
			}

			if (input) {
				var fWrap = input.closest('.rawnaq-sf-field');
				if (input.type === 'checkbox') {
					input.checked = !!val && val !== '0' && val !== 'false';
				} else if (input.type === 'radio') {
					var targetRadio = form.querySelector('input[name="' + input.name + '"][value="' + val + '"]');
					if (targetRadio) {
						targetRadio.checked = true;
						var cardOpt = targetRadio.closest('.rawnaq-sf-card-option');
						if (cardOpt) cardOpt.classList.add('is-selected');
					}
				} else if (input.tagName === 'SELECT') {
					var matched = false;
					for (var i = 0; i < input.options.length; i++) {
						if (input.options[i].value === val || input.options[i].text.toLowerCase() === String(val).toLowerCase()) {
							input.selectedIndex = i;
							matched = true;
							break;
						}
					}
					if (!matched && typeof val === 'string') {
						for (var j = 0; j < input.options.length; j++) {
							if (input.options[j].text.toLowerCase().indexOf(val.toLowerCase()) !== -1) {
								input.selectedIndex = j;
								matched = true;
								break;
							}
						}
					}
				} else {
					if (input.tagName === 'TEXTAREA' && input.value && options.append) {
						input.value += '\n' + String(val);
					} else {
						input.value = String(val);
					}
				}
				if (fWrap) {
					validateField(fWrap, { silent: true });
				}
				fieldsFound = true;
			}
		}

		applyConditionals(form);

		if (fieldsFound && options.scroll) {
			var reduce = isReducedMotion();
			form.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
			var targetToFocus = form.querySelector('input:not([type="hidden"]):not([disabled]), textarea:not([disabled])');
			if (targetToFocus) {
				setTimeout(function () {
					try { targetToFocus.focus(); } catch (e) {}
				}, reduce ? 20 : 400);
			}
		}
	}

	function applyUrlPrefill(form) {
		var cfg = cfgOf(form);
		if (!cfg || !cfg.urlPrefill) {
			return;
		}
		try {
			var params = new URLSearchParams(window.location.search);
			var data = {};
			var blockedKeys = ['rawnaq_hp', 'rawnaq_ts', 'action', 'nonce', 'form_id', 'rawnaq_recaptcha'];
			params.forEach(function (val, key) {
				var cleanKey = key.replace(/^sf_/, '');
				if (blockedKeys.indexOf(cleanKey.toLowerCase()) === -1) {
					data[cleanKey] = val;
				}
			});
			if (Object.keys(data).length > 0) {
				prefillFormWithData(form, data, { scroll: false, fromUrl: true });
			}
		} catch (e) { /* ignore */ }
	}

	function setupBridgeListeners() {
		document.addEventListener('rawnaq:case-study:discuss', function (e) {
			var detail = (e && e.detail) || {};
			var project = detail.project || {};
			var forms = document.querySelectorAll('.rawnaq-smart-form form');
			if (!forms.length) return;
			var targetForm = forms[0];

			var title = project.title || t('caseStudy', 'Case Study');
			var inquiryTpl = t('inquiryRegarding', 'Inquiry regarding %s');
			var projectDiscussTpl = t('projectDiscuss', 'I am interested in discussing a project similar to "%s"%s.');
			var clientLabelTpl = t('clientLabel', ' (Client: %s)');
			var forClientTpl = t('forClient', ' for %s');

			var clientSuffix = project.client ? clientLabelTpl.replace('%s', project.client) : '';
			var forClientSuffix = project.client ? forClientTpl.replace('%s', project.client) : '';

			var prefillData = {
				subject: inquiryTpl.replace('%s', title),
				project_details: projectDiscussTpl.replace('%s', project.title || '').replace('%s', clientSuffix),
				message: inquiryTpl.replace('%s', title) + forClientSuffix + '.',
				service: Array.isArray(project.services) ? project.services[0] : (project.services || ''),
				budget: project.budget || ''
			};

			prefillFormWithData(targetForm, prefillData, { scroll: true });
		});

		document.addEventListener('rawnaq:smart_form:prefill', function (e) {
			var detail = (e && e.detail) || {};
			var targetId = detail.formId || detail.targetId;
			var form = targetId ? document.querySelector('#' + targetId + ' form, form[data-form-id="' + targetId + '"]') : document.querySelector('.rawnaq-smart-form form');
			if (!form) return;
			prefillFormWithData(form, detail.data || detail, { scroll: detail.scroll !== false });
		});
	}

	function initAll() {
		document.querySelectorAll('.rawnaq-smart-form form').forEach(bindOne);
	}

	function hookElementor() {
		if (bound || !window.elementorFrontend || !elementorFrontend.hooks) {
			return;
		}
		bound = true;
		elementorFrontend.hooks.addAction(
			'frontend/element_ready/rawnaq_smart_form.default',
			function () { initAll(); }
		);
	}

	function boot() {
		setupBridgeListeners();
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
