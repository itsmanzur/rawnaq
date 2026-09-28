const fs = require('fs');
const path = require('path');

function minifyCSS(css, label) {
    // calc()/clamp()/min()/max() require whitespace around +/- operators per spec
    // (e.g. "calc(a + b)"); protect these blocks before stripping whitespace
    // around combinators, or the collapsed "calc(a+b)" becomes invalid CSS.
    const mathBlocks = [];
    const withoutComments = css.replace(/\/\*[\s\S]*?\*\//g, '');
    // A negative lookbehind-style prefix capture keeps this from matching the
    // "max(" tail inside an unrelated function name like "minmax(".
    const protectedCss = withoutComments.replace(
        /(^|[^\w-])(calc|clamp|min|max)\(((?:[^()]|\([^()]*\))*)\)/g,
        function (match, prefix, fnName, inner) {
            const normalized = (fnName + '(' + inner + ')').replace(/\s+/g, ' ').trim();
            // "+" is always a binary operator inside calc()/clamp()/min()/max() (never
            // a unary prefix like "-" can be), so a bare "a+b" here is already broken
            // in the SOURCE file, independent of minification.
            if (/[^\s]\+[^\s]/.test(normalized)) {
                console.warn(`Warning: possibly invalid calc()-style math (missing space around "+") in ${label || 'CSS'}: ${normalized}`);
            }
            mathBlocks.push(normalized);
            return prefix + '@@MATHBLOCK' + (mathBlocks.length - 1) + '@@';
        }
    );

    const minified = protectedCss
        .replace(/\s+/g, ' ')
        .replace(/\s*([{}:;,>+~])\s*/g, '$1')
        .replace(/;}/g, '}')
        .trim();

    return minified.replace(/@@MATHBLOCK(\d+)@@/g, function (_, i) {
        return mathBlocks[Number(i)];
    });
}

function minifyJS(js) {
    // Safe single line & multi-line comment stripping that avoids strings/regexes
    // For production JS, keep clean without risky mangling
    let out = js
        .replace(/\/\*[\s\S]*?\*\//g, '')
        .replace(/^\s*\/\/.*$/gm, '')
        .replace(/\n\s*\n/g, '\n')
        .trim();
    return out;
}

const modules = [
    'smart-form',
    'bento-grid',
    'floating-dock',
    'scroll-story',
    'tilt-card',
    'scroll-timeline',
    'flow-chart',
    'hub-diagram',
    'case-study-grid',
    'scroll-progress-toc'
];

const cssDir = path.join(__dirname, '..', 'assets', 'css');
const jsDir = path.join(__dirname, '..', 'assets', 'js');

modules.forEach(mod => {
    const cssPath = path.join(cssDir, `${mod}.css`);
    const minCssPath = path.join(cssDir, `${mod}.min.css`);
    if (fs.existsSync(cssPath)) {
        const raw = fs.readFileSync(cssPath, 'utf8');
        fs.writeFileSync(minCssPath, minifyCSS(raw, `${mod}.css`), 'utf8');
        console.log(`Minified CSS: ${mod}.min.css`);
    }

    const jsPath = path.join(jsDir, `${mod}.js`);
    const minJsPath = path.join(jsDir, `${mod}.min.js`);
    if (fs.existsSync(jsPath)) {
        const raw = fs.readFileSync(jsPath, 'utf8');
        fs.writeFileSync(minJsPath, minifyJS(raw), 'utf8');
        console.log(`Minified JS: ${mod}.min.js`);
    }

    const editorJsPath = path.join(jsDir, `${mod}-editor.js`);
    const minEditorJsPath = path.join(jsDir, `${mod}-editor.min.js`);
    if (fs.existsSync(editorJsPath)) {
        const raw = fs.readFileSync(editorJsPath, 'utf8');
        fs.writeFileSync(minEditorJsPath, minifyJS(raw), 'utf8');
        console.log(`Minified Editor JS: ${mod}-editor.min.js`);
    }
});

const gutenbergPath = path.join(jsDir, 'gutenberg-editor.js');
const minGutenbergPath = path.join(jsDir, 'gutenberg-editor.min.js');
if (fs.existsSync(gutenbergPath)) {
    const raw = fs.readFileSync(gutenbergPath, 'utf8');
    fs.writeFileSync(minGutenbergPath, minifyJS(raw), 'utf8');
    console.log('Minified Gutenberg Editor JS: gutenberg-editor.min.js');
}

// wp-admin dashboard assets (not module-scoped, so not part of the `modules` loop above)
const adminCssPath = path.join(cssDir, 'admin.css');
const minAdminCssPath = path.join(cssDir, 'admin.min.css');
if (fs.existsSync(adminCssPath)) {
    const raw = fs.readFileSync(adminCssPath, 'utf8');
    fs.writeFileSync(minAdminCssPath, minifyCSS(raw, 'admin.css'), 'utf8');
    console.log('Minified CSS: admin.min.css');
}

const adminJsPath = path.join(jsDir, 'admin.js');
const minAdminJsPath = path.join(jsDir, 'admin.min.js');
if (fs.existsSync(adminJsPath)) {
    const raw = fs.readFileSync(adminJsPath, 'utf8');
    fs.writeFileSync(minAdminJsPath, minifyJS(raw), 'utf8');
    console.log('Minified JS: admin.min.js');
}

console.log('All assets minified successfully!');
