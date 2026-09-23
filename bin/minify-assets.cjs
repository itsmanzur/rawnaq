const fs = require('fs');
const path = require('path');

function minifyCSS(css) {
    return css
        // Remove comments
        .replace(/\/\*[\s\S]*?\*\//g, '')
        // Remove newlines and extra spaces
        .replace(/\s+/g, ' ')
        .replace(/\s*([{}:;,>+~])\s*/g, '$1')
        .replace(/;}/g, '}')
        .trim();
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
        fs.writeFileSync(minCssPath, minifyCSS(raw), 'utf8');
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

console.log('All assets minified successfully!');
