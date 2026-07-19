/**
 * Rawnaq diagram export — PNG / SVG downloads for Flow Chart & Hub Diagram.
 * Uses SVG foreignObject + canvas (no third-party deps).
 */
(function (window) {
    'use strict';

    var STYLE_PROPS = [
        'background', 'background-color', 'background-image', 'border', 'border-radius',
        'box-shadow', 'color', 'display', 'flex', 'flex-direction', 'align-items',
        'justify-content', 'font', 'font-family', 'font-size', 'font-weight', 'line-height',
        'letter-spacing', 'text-align', 'text-transform', 'padding', 'margin', 'width',
        'height', 'min-width', 'min-height', 'max-width', 'max-height', 'opacity',
        'overflow', 'position', 'left', 'top', 'right', 'bottom', 'transform',
        'clip-path', 'object-fit', 'object-position', 'gap', 'grid-template-columns', 'z-index',
        'white-space', 'word-break', 'box-sizing', 'inset', 'aspect-ratio',
        'stroke', 'stroke-width', 'stroke-linecap', 'stroke-dasharray', 'stroke-dashoffset',
        'fill', 'border-top', 'border-right', 'border-bottom', 'border-left'
    ];

    function downloadBlob(blob, filename) {
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        setTimeout(function () {
            URL.revokeObjectURL(url);
            a.remove();
        }, 500);
    }

    function downloadDataUrl(dataUrl, filename) {
        var a = document.createElement('a');
        a.href = dataUrl;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
    }

    function copyCssVars(fromEl, toEl) {
        if (!fromEl || !toEl || !window.getComputedStyle) {
            return;
        }
        var cs = window.getComputedStyle(fromEl);
        var i;
        for (i = 0; i < cs.length; i++) {
            var name = cs[i];
            if (name && name.indexOf('--') === 0) {
                toEl.style.setProperty(name, cs.getPropertyValue(name));
            }
        }
    }

    function inlineStyles(source, clone) {
        if (!source || !clone || source.nodeType !== 1) {
            return;
        }
        var cs = window.getComputedStyle(source);
        var css = '';
        for (var i = 0; i < STYLE_PROPS.length; i++) {
            var prop = STYLE_PROPS[i];
            var val = cs.getPropertyValue(prop);
            if (val && val !== 'none' && val !== 'normal' && val !== 'auto' && val !== 'rgba(0, 0, 0, 0)') {
                css += prop + ':' + val + ';';
            }
        }
        if (cs.getPropertyValue('display') === 'none') {
            clone.style.cssText = 'display:none !important';
            return;
        }
        clone.setAttribute('style', (clone.getAttribute('style') || '') + ';' + css);

        var sChildren = source.children;
        var cChildren = clone.children;
        var len = Math.min(sChildren.length, cChildren.length);
        for (var j = 0; j < len; j++) {
            inlineStyles(sChildren[j], cChildren[j]);
        }
    }

    function hideSelectors(root, selectors) {
        (selectors || []).forEach(function (sel) {
            root.querySelectorAll(sel).forEach(function (el) {
                el.setAttribute('data-rawnaq-export-hide', el.style.display || '');
                el.style.display = 'none';
            });
        });
    }

    function restoreHidden(root) {
        root.querySelectorAll('[data-rawnaq-export-hide]').forEach(function (el) {
            el.style.display = el.getAttribute('data-rawnaq-export-hide') || '';
            el.removeAttribute('data-rawnaq-export-hide');
        });
    }

    function parsePx(value) {
        var n = parseFloat(value);
        return isNaN(n) ? 0 : n;
    }

    function measure(node, opts) {
        opts = opts || {};
        if (opts.width && opts.height) {
            return {
                width: Math.ceil(opts.width),
                height: Math.ceil(opts.height)
            };
        }
        var styleW = parsePx(node.style && node.style.width);
        var styleH = parsePx(node.style && node.style.height);
        var width = Math.ceil(Math.max(
            styleW,
            node.offsetWidth || 0,
            node.scrollWidth || 0,
            320
        ));
        var height = Math.ceil(Math.max(
            styleH,
            node.offsetHeight || 0,
            node.scrollHeight || 0,
            240
        ));
        return { width: width, height: height };
    }

    function imageToDataUrl(img) {
        return new Promise(function (resolve) {
            if (!img || !img.src || img.src.indexOf('data:') === 0) {
                resolve();
                return;
            }
            var src = img.currentSrc || img.src;
            var loader = new Image();
            loader.crossOrigin = 'anonymous';
            loader.onload = function () {
                try {
                    var c = document.createElement('canvas');
                    c.width = loader.naturalWidth || loader.width || 1;
                    c.height = loader.naturalHeight || loader.height || 1;
                    c.getContext('2d').drawImage(loader, 0, 0);
                    img.setAttribute('src', c.toDataURL('image/png'));
                } catch (e) {
                    /* keep original src */
                }
                resolve();
            };
            loader.onerror = function () {
                resolve();
            };
            loader.src = src;
        });
    }

    function inlineImages(root) {
        var imgs = root.querySelectorAll('img');
        var jobs = [];
        for (var i = 0; i < imgs.length; i++) {
            jobs.push(imageToDataUrl(imgs[i]));
        }
        return Promise.all(jobs);
    }

    function prepareClone(node, width, height, varSource) {
        var clone = node.cloneNode(true);
        inlineStyles(node, clone);
        if (varSource) {
            copyCssVars(varSource, clone);
        }
        copyCssVars(node, clone);

        clone.style.margin = '0';
        clone.style.transform = 'none';
        clone.style.left = '0';
        clone.style.top = '0';
        clone.style.position = 'relative';
        clone.style.width = width + 'px';
        clone.style.height = height + 'px';
        clone.style.maxWidth = 'none';
        clone.style.maxHeight = 'none';
        clone.style.overflow = 'visible';
        clone.style.opacity = '1';
        clone.setAttribute('xmlns', 'http://www.w3.org/1999/xhtml');

        // Force animated / lazy nodes visible in the snapshot.
        clone.querySelectorAll('.rawnaq-flow-node').forEach(function (el) {
            el.classList.add('show');
            el.classList.remove('is-lazy');
            el.style.opacity = '1';
            el.style.transform = 'none';
            el.style.visibility = 'visible';
        });
        clone.querySelectorAll('.rawnaq-flow-connectors path, path').forEach(function (path) {
            path.classList.add('lit');
            path.style.strokeDashoffset = '0';
            path.style.animation = 'none';
        });

        return clone;
    }

    function buildSvgMarkup(node, width, height, bg, varSource) {
        var clone = prepareClone(node, width, height, varSource);
        var serializer = new XMLSerializer();
        var xhtml = serializer.serializeToString(clone);
        if (xhtml.indexOf('xmlns=') === -1) {
            xhtml = xhtml.replace(/^\s*<([a-zA-Z0-9-]+)/, '<$1 xmlns="http://www.w3.org/1999/xhtml"');
        }

        var fill = bg || '#ffffff';
        return (
            '<?xml version="1.0" encoding="UTF-8"?>'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="' + width + '" height="' + height + '" viewBox="0 0 ' + width + ' ' + height + '">'
            + '<rect width="100%" height="100%" fill="' + fill + '"/>'
            + '<foreignObject x="0" y="0" width="' + width + '" height="' + height + '">'
            + xhtml
            + '</foreignObject></svg>'
        );
    }

    function exportNode(node, opts) {
        opts = opts || {};
        var format = (opts.format || 'png').toLowerCase();
        var filename = opts.filename || ('rawnaq-diagram.' + (format === 'svg' ? 'svg' : 'png'));
        var hide = opts.hide || [];
        var bg = opts.background || '#ffffff';
        var pixelRatio = opts.pixelRatio || Math.min(2, window.devicePixelRatio || 1);
        var varSource = opts.varSource || null;
        var host = opts.host || null;

        if (!node) {
            return Promise.reject(new Error('No export target'));
        }

        var cleanup = null;
        if (typeof opts.prepare === 'function') {
            cleanup = opts.prepare(node) || null;
        }
        if (host) {
            host.classList.add('is-exporting');
        }

        hideSelectors(host || node, hide);

        var prepImages = typeof opts.getSvgMarkup === 'function'
            ? Promise.resolve()
            : inlineImages(node);

        return prepImages.then(function () {
            var sizeOpts = {};
            if (typeof opts.getSize === 'function') {
                sizeOpts = opts.getSize(node) || {};
            } else if (opts.width && opts.height) {
                sizeOpts = { width: opts.width, height: opts.height };
            }
            var size = measure(node, sizeOpts);
            var svgReady;
            if (typeof opts.getSvgMarkup === 'function') {
                svgReady = Promise.resolve(opts.getSvgMarkup(size.width, size.height, {
                    background: bg,
                    host: host,
                    target: node
                }));
            } else {
                svgReady = Promise.resolve(buildSvgMarkup(node, size.width, size.height, bg, varSource || host));
            }
            return svgReady.then(function (svgText) {
                restoreHidden(host || node);
                if (host) {
                    host.classList.remove('is-exporting');
                }
                if (typeof cleanup === 'function') {
                    cleanup();
                } else if (typeof opts.restore === 'function') {
                    opts.restore();
                }

                if (format === 'svg') {
                    var blob = new Blob([svgText], { type: 'image/svg+xml;charset=utf-8' });
                    downloadBlob(blob, filename);
                    return { format: 'svg', filename: filename, width: size.width, height: size.height };
                }

                return new Promise(function (resolve, reject) {
                    var img = new Image();
                    img.onload = function () {
                        try {
                            var canvas = document.createElement('canvas');
                            canvas.width = Math.round(size.width * pixelRatio);
                            canvas.height = Math.round(size.height * pixelRatio);
                            var ctx = canvas.getContext('2d');
                            ctx.scale(pixelRatio, pixelRatio);
                            ctx.fillStyle = bg;
                            ctx.fillRect(0, 0, size.width, size.height);
                            ctx.drawImage(img, 0, 0, size.width, size.height);
                            if (canvas.toBlob) {
                                canvas.toBlob(function (b) {
                                    if (b) {
                                        downloadBlob(b, filename);
                                        resolve({ format: 'png', filename: filename, width: size.width, height: size.height });
                                    } else {
                                        downloadDataUrl(canvas.toDataURL('image/png'), filename);
                                        resolve({ format: 'png', filename: filename, width: size.width, height: size.height });
                                    }
                                }, 'image/png');
                            } else {
                                downloadDataUrl(canvas.toDataURL('image/png'), filename);
                                resolve({ format: 'png', filename: filename, width: size.width, height: size.height });
                            }
                        } catch (err) {
                            reject(err);
                        }
                    };
                    img.onerror = function () {
                        reject(new Error('Export image failed'));
                    };
                    img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svgText);
                });
            });
        }).catch(function (err) {
            restoreHidden(host || node);
            if (host) {
                host.classList.remove('is-exporting');
            }
            if (typeof cleanup === 'function') {
                cleanup();
            } else if (typeof opts.restore === 'function') {
                opts.restore();
            }
            throw err;
        });
    }

    function attachToolbar(host, config) {
        if (!host) {
            return null;
        }
        var existing = host.querySelector('.rawnaq-diagram-export');
        if (existing) {
            existing.remove();
        }
        config = config || {};
        var bar = document.createElement('div');
        bar.className = 'rawnaq-diagram-export';
        bar.setAttribute('role', 'group');
        bar.setAttribute('aria-label', 'Export diagram');
        bar.innerHTML =
            '<button type="button" class="rq-export-png" title="Download PNG">PNG</button>'
            + '<button type="button" class="rq-export-svg" title="Download SVG">SVG</button>';

        function run(format) {
            var target = typeof config.getTarget === 'function' ? config.getTarget() : host;
            var hide = typeof config.getHide === 'function' ? config.getHide() : (config.hide || []);
            bar.classList.add('is-busy');
            exportNode(target, {
                format: format,
                filename: (config.filenameBase || 'rawnaq-diagram') + '.' + format,
                hide: hide,
                background: config.background || '#ffffff',
                pixelRatio: config.pixelRatio || 2,
                host: host,
                varSource: config.varSource || host,
                getSize: config.getSize,
                getSvgMarkup: config.getSvgMarkup,
                prepare: config.prepare,
                restore: config.restore,
                width: config.width,
                height: config.height
            }).catch(function () {
                /* silent */
            }).then(function () {
                bar.classList.remove('is-busy');
            });
        }

        bar.querySelector('.rq-export-png').addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            run('png');
        });
        bar.querySelector('.rq-export-svg').addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            run('svg');
        });

        if (config.prepend && host.firstChild) {
            host.insertBefore(bar, host.firstChild);
        } else {
            host.appendChild(bar);
        }
        return bar;
    }

    window.rawnaqDiagramExport = {
        exportNode: exportNode,
        attachToolbar: attachToolbar
    };
})(window);
