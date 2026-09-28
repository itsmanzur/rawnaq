const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const rootDir = path.resolve(__dirname, '..');
const screenshotsDir = path.join(rootDir, 'screenshots');
const bannersDir = path.join(rootDir, 'banners');
const iconsDir = path.join(rootDir, 'icons');
const slidesDir = path.join(__dirname, 'slides');

[screenshotsDir, bannersDir, iconsDir, slidesDir].forEach(dir => {
    if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true });
    }
});

// Detect browser
let browserPath = '';
const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const edgePath = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';

if (fs.existsSync(chromePath)) {
    browserPath = chromePath;
} else if (fs.existsSync(edgePath)) {
    browserPath = edgePath;
} else {
    console.error('Neither Google Chrome nor Microsoft Edge was found.');
    process.exit(1);
}

console.log('Using browser:', browserPath);

// Read main generator HTML
const mainHtml = fs.readFileSync(path.join(__dirname, 'index.html'), 'utf8');
const styleMatch = mainHtml.match(/<style>([\s\S]*?)<\/style>/)[1];

// Extract individual canvases and generate clean single-page slides
for (let i = 1; i <= 10; i++) {
    const canvasRegex = new RegExp(`<div class="wp-screenshot-canvas" id="canvas-${i}">([\\s\\S]*?)<\\/div>\\s*<\\/div>\\s*<\\/div>`, 'm');
    const match = mainHtml.match(canvasRegex);

    if (match) {
        const slideHtml = `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Screenshot ${i}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { width: 1280px; height: 720px; overflow: hidden; background: #f8fafc; font-family: 'Plus Jakarta Sans', sans-serif; }
        ${styleMatch}
        .wp-screenshot-canvas { box-shadow: none !important; margin: 0 !important; border-radius: 0 !important; width: 1280px !important; height: 720px !important; }
    </style>
</head>
<body style="margin:0; padding:0; overflow:hidden;">
    <div class="wp-screenshot-canvas" id="canvas-${i}">
        ${match[1]}
    </div>
</body>
</html>`;

        const slideFile = path.join(slidesDir, `slide-${i}.html`);
        fs.writeFileSync(slideFile, slideHtml, 'utf8');

        const outFile = path.join(screenshotsDir, `screenshot-${i}.png`);
        const fileUrl = `file:///${slideFile.replace(/\\/g, '/')}`;

        console.log(`Generating screenshot-${i}.png...`);
        execSync(`"${browserPath}" --headless --disable-gpu --hide-scrollbars --window-size=1280,720 --screenshot="${outFile}" "${fileUrl}"`, { stdio: 'ignore' });
    }
}

// Generate Banners
const bannerHtmlPath = path.join(__dirname, 'banner.html');
if (fs.existsSync(bannerHtmlPath)) {
    const bannerUrl = `file:///${bannerHtmlPath.replace(/\\/g, '/')}`;

    console.log('Generating banner-1544x500.png...');
    const bannerRetinaOut = path.join(bannersDir, 'banner-1544x500.png');
    execSync(`"${browserPath}" --headless --disable-gpu --hide-scrollbars --window-size=1544,500 --screenshot="${bannerRetinaOut}" "${bannerUrl}"`, { stdio: 'ignore' });

    console.log('Generating banner-772x250.png...');
    const bannerStdOut = path.join(bannersDir, 'banner-772x250.png');
    execSync(`"${browserPath}" --headless --disable-gpu --hide-scrollbars --window-size=772,250 --screenshot="${bannerStdOut}" "${bannerUrl}"`, { stdio: 'ignore' });
}

// Generate SVG Icon and PNG Icons
const svgIcon = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" width="256" height="256">
  <defs>
    <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#0f172a" />
      <stop offset="100%" stop-color="#1e293b" />
    </linearGradient>
    <linearGradient id="starGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#f59e0b" />
      <stop offset="100%" stop-color="#fbbf24" />
    </linearGradient>
    <linearGradient id="accentGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#6366f1" />
      <stop offset="100%" stop-color="#818cf8" />
    </linearGradient>
  </defs>
  <rect width="256" height="256" rx="56" fill="url(#bgGrad)" />
  <circle cx="128" cy="128" r="84" fill="none" stroke="url(#accentGrad)" stroke-width="6" stroke-dasharray="16 8" opacity="0.4" />
  <!-- Sparkle 4-point star -->
  <path d="M 128 48 Q 128 128 208 128 Q 128 128 128 208 Q 128 128 48 128 Q 128 128 128 48 Z" fill="url(#starGrad)" />
  <circle cx="128" cy="128" r="14" fill="#ffffff" />
</svg>`;

fs.writeFileSync(path.join(iconsDir, 'icon.svg'), svgIcon, 'utf8');

// Icon HTML for rendering PNG icons
const iconHtml = `<!DOCTYPE html>
<html>
<head>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { width:256px; height:256px; overflow:hidden; background:transparent; display:flex; align-items:center; justify-content:center; }
</style>
</head>
<body style="margin:0; padding:0; overflow:hidden;">
  ${svgIcon}
</body>
</html>`;

const iconHtmlFile = path.join(slidesDir, 'icon.html');
fs.writeFileSync(iconHtmlFile, iconHtml, 'utf8');

console.log('Generating icon-256x256.png...');
execSync(`"${browserPath}" --headless --disable-gpu --hide-scrollbars --window-size=256,256 --screenshot="${path.join(iconsDir, 'icon-256x256.png')}" "file:///${iconHtmlFile.replace(/\\/g, '/')}"`, { stdio: 'ignore' });

console.log('Generating icon-128x128.png...');
execSync(`"${browserPath}" --headless --disable-gpu --hide-scrollbars --window-size=128,128 --screenshot="${path.join(iconsDir, 'icon-128x128.png')}" "file:///${iconHtmlFile.replace(/\\/g, '/')}"`, { stdio: 'ignore' });

console.log('All WordPress.org assets exported successfully!');
