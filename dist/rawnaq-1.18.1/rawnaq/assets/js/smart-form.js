/**
 * Rawnaq Smart Form — multi-step, conditionals, files, rating, reCAPTCHA
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

    function setInvalid(field, on) {
        if (!field) return;
        field.classList.toggle('is-invalid', !!on);
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
        var input = field.querySelector('input:not([type="hidden"]), textarea, select');
        var hiddenRating = field.querySelector('input[data-sf-type="rating"]');
        if (hiddenRating) {
            input = hiddenRating;
        }
        var fileInput = field.querySelector('input[type="file"]');
        if (fileInput) {
            input = fileInput;
        }
        if (!input) {
            return true;
        }
        var required = input.hasAttribute('required') || input.getAttribute('aria-required') === 'true';
        var ok = true;
        var sfType = (input.getAttribute('data-sf-type') || input.type || 'text').toLowerCase();
        var val = '';
        if (input.type === 'checkbox') {
            val = input.checked ? '1' : '';
        } else if (input.type === 'file') {
            val = (input.files && input.files.length) ? '1' : '';
            if (val && input.files[0] && input.getAttribute('data-max-mb')) {
                var max = parseInt(input.getAttribute('data-max-mb'), 10) || 5;
                if (input.files[0].size > max * 1024 * 1024) {
                    ok = false;
                }
            }
        } else {
            val = String(input.value || '').trim();
        }

        if (required && !val) {
            ok = false;
        } else if (val && sfType === 'email') {
            ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
        } else if (val && sfType === 'phone') {
            ok = val.replace(/[^\d+]/g, '').length >= 7;
        } else if (val && sfType === 'url') {
            ok = /^https?:\/\//i.test(val) || /^[^\s]+\.[^\s]+/.test(val);
        }

        setInvalid(field, !ok);
        return ok;
    }

    function collectPayload(form) {
        var data = {};
        form.querySelectorAll('.rawnaq-sf-field').forEach(function (field) {
            if (!fieldVisible(field) && field.getAttribute('data-show-if')) {
                return;
            }
            var el = field.querySelector('[name^="sf_"]:not([type="file"])');
            if (!el) return;
            var key = el.name.replace(/^sf_/, '');
            if (el.type === 'checkbox') {
                data[key] = el.checked ? '1' : '';
            } else {
                data[key] = el.value;
            }
        });
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
            } else if (el.type !== 'file') {
                values[key] = el.value;
            }
        });
        // For select, use selected option text/value as stored
        form.querySelectorAll('.rawnaq-sf-field[data-show-if]').forEach(function (field) {
            var dep = field.getAttribute('data-show-if');
            var want = field.getAttribute('data-show-if-value') || '';
            var got = values[dep] || '';
            var show = want ? (got === want) : !!got;
            if (show) {
                field.removeAttribute('hidden');
            } else {
                field.setAttribute('hidden', '');
                setInvalid(field, false);
                var input = field.querySelector('input, textarea, select');
                if (input && input.type !== 'file' && input.type !== 'checkbox') {
                    // keep value but not required when hidden — strip required for validation
                }
            }
        });
    }

    function getStepPanels(form) {
        return Array.prototype.slice.call(form.querySelectorAll('.rawnaq-sf-step-panel'));
    }

    function syncStepUi(form, index) {
        var panels = getStepPanels(form);
        var dots = form.querySelectorAll('.rawnaq-sf-step-dot');
        panels.forEach(function (p, i) {
            var on = i === index;
            p.classList.toggle('is-active', on);
            if (on) {
                p.removeAttribute('hidden');
            } else {
                p.setAttribute('hidden', '');
            }
        });
        dots.forEach(function (d, i) {
            d.classList.toggle('is-active', i === index);
        });
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
        form.setAttribute('data-step-index', String(index));
    }

    function validateCurrentStep(form) {
        var panels = getStepPanels(form);
        var idx = parseInt(form.getAttribute('data-step-index') || '0', 10) || 0;
        var panel = panels[idx] || form;
        var ok = true;
        panel.querySelectorAll('.rawnaq-sf-field').forEach(function (field) {
            if (!validateField(field, { onlyActiveStep: true })) {
                ok = false;
            }
        });
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

    function doSubmit(form) {
        var cfg = cfgOf(form);
        hideStatus(form);

        var fields = form.querySelectorAll('.rawnaq-sf-field');
        var valid = true;
        fields.forEach(function (field) {
            if (!validateField(field)) {
                valid = false;
            }
        });
        if (!valid) {
            showStatus(form, 'error', cfg.errorMessage || 'Please fill in the required fields correctly.');
            return;
        }

        var btn = form.querySelector('.rawnaq-sf-submit');
        setLoading(btn, true);

        getRecaptchaToken(cfg).then(function (token) {
            var fd = new FormData();
            fd.append('action', 'rawnaq_smart_form_submit');
            fd.append('nonce', (window.rawnaqSmartForm && rawnaqSmartForm.nonce) || '');
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
            }).then(function (r) { return r.json(); });
        }).then(function (json) {
            setLoading(btn, false);
            if (!json || !json.success) {
                var msg = (json && json.data && json.data.message)
                    ? json.data.message
                    : (cfg.errorMessage || 'Something went wrong. Please try again.');
                showStatus(form, 'error', msg);
                return;
            }

            var data = json.data || {};
            showStatus(form, 'success', data.message || cfg.successMessage || 'Message sent.');
            form.reset();
            fields.forEach(function (f) { setInvalid(f, false); });
            form.querySelectorAll('.rawnaq-sf-star').forEach(function (s) {
                s.classList.remove('is-on');
            });
            applyConditionals(form);
            syncStepUi(form, 0);
            var ts = form.querySelector('[name="rawnaq_ts"]');
            if (ts) {
                ts.value = String(Math.floor(Date.now() / 1000));
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
        }).catch(function () {
            setLoading(btn, false);
            showStatus(form, 'error', cfg.errorMessage || 'Something went wrong. Please try again.');
        });
    }

    function onSubmit(e) {
        e.preventDefault();
        doSubmit(e.currentTarget);
    }

    function bindOne(form) {
        if (!form || form.classList.contains('sf-bound')) {
            return;
        }
        form.classList.add('sf-bound');
        var ts = form.querySelector('[name="rawnaq_ts"]');
        if (ts) {
            ts.value = String(Math.floor(Date.now() / 1000));
        }

        applyConditionals(form);
        syncStepUi(form, 0);

        form.addEventListener('submit', onSubmit);

        form.querySelectorAll('.rawnaq-sf-field input, .rawnaq-sf-field textarea, .rawnaq-sf-field select').forEach(function (el) {
            el.addEventListener('blur', function () {
                validateField(el.closest('.rawnaq-sf-field'));
            });
            el.addEventListener('input', function () {
                applyConditionals(form);
                var field = el.closest('.rawnaq-sf-field');
                if (field && field.classList.contains('is-invalid')) {
                    validateField(field);
                }
            });
            el.addEventListener('change', function () {
                applyConditionals(form);
            });
        });

        form.querySelectorAll('.rawnaq-sf-rating').forEach(function (wrap) {
            var input = wrap.querySelector('input[data-sf-type="rating"]');
            wrap.querySelectorAll('.rawnaq-sf-star').forEach(function (star) {
                star.addEventListener('click', function () {
                    var v = star.getAttribute('data-value') || '';
                    if (input) input.value = v;
                    wrap.querySelectorAll('.rawnaq-sf-star').forEach(function (s) {
                        s.classList.toggle('is-on', parseInt(s.getAttribute('data-value'), 10) <= parseInt(v, 10));
                    });
                    validateField(wrap.closest('.rawnaq-sf-field'));
                });
            });
        });

        var next = form.querySelector('.rawnaq-sf-next');
        var prev = form.querySelector('.rawnaq-sf-prev');
        if (next) {
            next.addEventListener('click', function () {
                var cfg = cfgOf(form);
                if (!validateCurrentStep(form)) {
                    showStatus(form, 'error', cfg.errorMessage || 'Please fill in the required fields correctly.');
                    return;
                }
                hideStatus(form);
                var idx = parseInt(form.getAttribute('data-step-index') || '0', 10) || 0;
                syncStepUi(form, idx + 1);
            });
        }
        if (prev) {
            prev.addEventListener('click', function () {
                hideStatus(form);
                var idx = parseInt(form.getAttribute('data-step-index') || '0', 10) || 0;
                syncStepUi(form, Math.max(0, idx - 1));
            });
        }
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
