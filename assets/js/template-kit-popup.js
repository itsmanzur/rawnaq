/**
 * Rawnaq Template Kit — Elementor editor popup (Phase 2)
 *
 * Built for 100% reliability and maximum visual polish across all Elementor versions.
 */
( function () {
	'use strict';

	if ( typeof elementor === 'undefined' || typeof rawnaqTemplateKit === 'undefined' ) {
		return;
	}

	const cfg      = rawnaqTemplateKit;
	const sections = cfg.templates || [];
	const pageKits = cfg.page_kits || [];

	let sitePalette = null;

	// =========================================================================
	// Inline Stylesheet Injector (Guarantees zero-delay CSS rendering)
	// =========================================================================
	function injectInlineStyles() {
		if ( document.getElementById( 'rawnaq-tk-injected-css' ) ) return;

		const style = document.createElement( 'style' );
		style.id    = 'rawnaq-tk-injected-css';
		style.textContent = "@import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap');.rawnaq-admin-wrap{--rq-ink:#13231c;--rq-muted:#5c6f66;--rq-line:#d7e2dc;--rq-surface:#ffffff;--rq-soft:#f3f7f4;--rq-accent:#0f766e;--rq-accent-deep:#0b5c56;--rq-ok:#16a34a;--rq-warn:#b45309;--rq-shadow:0 18px 40px rgba(19,35,28,0.07);margin:20px 20px 0 0;font-family:'DM Sans',system-ui,sans-serif;color:var(--rq-ink);max-width:1180px;}.rawnaq-admin-wrap *,.rawnaq-admin-wrap *::before,.rawnaq-admin-wrap *::after{box-sizing:border-box;}.rawnaq-header{background:radial-gradient(120% 140% at 0% 0%,rgba(15,118,110,0.35) 0%,transparent 55%),radial-gradient(90% 120% at 100% 10%,rgba(180,83,9,0.18) 0%,transparent 45%),linear-gradient(145deg,#10241d 0%,#1a3a2f 55%,#0f1f1a 100%);color:#fff;padding:26px 32px;border-radius:20px;display:flex;justify-content:space-between;align-items:center;box-shadow:var(--rq-shadow);margin-bottom:24px;border:1px solid rgba(255,255,255,0.08);position:relative;overflow:hidden;}.rawnaq-header::after{content:\"\";position:absolute;inset:auto -10% -40% 40%;height:160px;background:radial-gradient(circle,rgba(255,255,255,0.08),transparent 70%);pointer-events:none;}.rawnaq-logo{display:flex;align-items:center;gap:16px;position:relative;z-index:1;}.logo-mark{width:52px;height:52px;border-radius:14px;display:grid;place-items:center;font-family:'Fraunces',Georgia,serif;font-weight:700;font-size:26px;color:#10241d;background:linear-gradient(160deg,#e8f5f0,#9fd4c5);box-shadow:inset 0 1px 0 rgba(255,255,255,0.7);}.rawnaq-header h1{font-family:'Fraunces',Georgia,serif;font-size:28px;font-weight:700;margin:0;color:#fff;line-height:1.15;letter-spacing:-0.02em;}.rawnaq-header p{margin:4px 0 0 0;font-size:13.5px;opacity:0.78;}.plugin-badge{position:relative;z-index:1;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.16);padding:7px 14px;border-radius:999px;font-weight:700;font-size:12px;letter-spacing:0.04em;backdrop-filter:blur(8px);}.rawnaq-layout{display:grid;grid-template-columns:230px 1fr;gap:22px;align-items:start;}.rawnaq-sidebar{background:var(--rq-surface);border-radius:18px;padding:16px;box-shadow:var(--rq-shadow);border:1px solid var(--rq-line);display:flex;flex-direction:column;gap:28px;}.rawnaq-nav{display:flex;flex-direction:column;gap:6px;}.nav-item{display:flex;align-items:center;gap:10px;padding:11px 14px;color:var(--rq-muted);text-decoration:none;border-radius:12px;font-weight:600;font-size:13.5px;transition:background 0.2s,color 0.2s,transform 0.2s;}.nav-item:hover{background:var(--rq-soft);color:var(--rq-ink);}.nav-item.active{background:#e6f3ef;color:var(--rq-accent-deep);}.nav-icon{font-size:14px;width:1.2em;text-align:center;}.sidebar-footer{background:var(--rq-soft);border-radius:14px;padding:14px;text-align:center;border:1px solid var(--rq-line);}.sidebar-footer p{margin:0 0 8px 0;font-size:12px;color:var(--rq-muted);font-weight:600;}.doc-btn{display:inline-block;background:var(--rq-ink);color:#fff;text-decoration:none;padding:9px 12px;border-radius:10px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;width:100%;transition:background 0.2s;}.doc-btn:hover{background:#09150f;color:#fff;}.rawnaq-content{background:var(--rq-surface);border-radius:18px;padding:28px 30px 32px;box-shadow:var(--rq-shadow);border:1px solid var(--rq-line);min-height:440px;}.tab-panel{display:none;animation:rqFade 0.28s ease;}.tab-panel.active{display:block;}@keyframes rqFade{from{opacity:0;transform:translateY(6px);}to{opacity:1;transform:none;}}.section-desc{color:var(--rq-muted);font-size:14px;margin:0;line-height:1.55;max-width:52ch;}.tab-panel > h2{font-family:'Fraunces',Georgia,serif;font-size:26px;font-weight:700;color:var(--rq-ink);margin:0 0 10px 0;letter-spacing:-0.02em;}.welcome-banner{background:linear-gradient(120deg,rgba(15,118,110,0.08),transparent 40%),var(--rq-soft);border:1px solid var(--rq-line);padding:22px 24px;border-radius:16px;margin-bottom:22px;}.welcome-banner h2{font-family:'Fraunces',Georgia,serif;color:var(--rq-accent-deep);margin:0 0 6px 0;font-size:22px;}.welcome-banner p{margin:0;color:var(--rq-muted);font-size:14px;line-height:1.55;}.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;}.rawnaq-card{border:1px solid var(--rq-line);border-radius:14px;padding:22px;background:#fff;transition:transform 0.2s,box-shadow 0.2s;}.rawnaq-card:hover{transform:translateY(-2px);box-shadow:0 12px 28px rgba(19,35,28,0.06);}.rawnaq-card h3{margin:0 0 10px 0;font-size:15px;font-weight:700;}.rawnaq-card p{color:var(--rq-muted);font-size:13.5px;line-height:1.6;margin:0 0 16px 0;}.btn{display:inline-flex;align-items:center;justify-content:center;padding:11px 18px;border-radius:11px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;transition:background 0.2s,transform 0.15s,box-shadow 0.2s;border:none;font-family:inherit;}.btn:active{transform:translateY(1px);}.btn-primary{background:var(--rq-accent);color:#fff;}.btn-primary:hover{background:var(--rq-accent-deep);color:#fff;}.btn-save{background:var(--rq-ink);color:#fff;min-width:140px;box-shadow:0 8px 20px rgba(19,35,28,0.18);}.btn-save:hover{background:#09150f;color:#fff;}.btn-save:disabled{opacity:0.65;cursor:wait;}.dock-stat-big{font-family:Fraunces,Georgia,serif;font-size:48px;font-weight:700;line-height:1;margin:8px 0 12px;color:var(--rq-accent);}.modules-hero{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:26px;padding-bottom:22px;border-bottom:1px solid var(--rq-line);}.modules-kicker{margin:0 0 6px 0;font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:var(--rq-accent);}.modules-hero h2{font-family:'Fraunces',Georgia,serif;font-size:28px;font-weight:700;margin:0 0 8px 0;letter-spacing:-0.02em;color:var(--rq-ink);}.modules-stat{flex-shrink:0;min-width:92px;text-align:center;padding:14px 16px;border-radius:16px;background:radial-gradient(circle at 30% 20%,rgba(15,118,110,0.14),transparent 60%),var(--rq-soft);border:1px solid var(--rq-line);}.modules-stat-num{display:block;font-family:'Fraunces',Georgia,serif;font-size:32px;font-weight:700;line-height:1;color:var(--rq-accent-deep);}.modules-stat-label{display:block;margin-top:4px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--rq-muted);}.modules-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-bottom:8px;}.module-card{position:relative;display:flex;flex-direction:column;gap:16px;padding:20px;border-radius:18px;background:#fff;border:1px solid var(--rq-line);transition:border-color 0.2s,box-shadow 0.25s,transform 0.2s,background 0.2s;overflow:hidden;}.module-card::before{content:\"\";position:absolute;inset:0 auto 0 0;width:3px;background:var(--tone,var(--rq-accent));opacity:0;transition:opacity 0.2s;}.module-card:hover{transform:translateY(-2px);box-shadow:0 14px 32px rgba(19,35,28,0.07);border-color:#c5d5cc;}.module-card.is-on{background:linear-gradient(165deg,rgba(255,255,255,0.95),rgba(243,247,244,0.9));border-color:#b9d0c4;box-shadow:0 10px 28px rgba(15,118,110,0.08);}.module-card.is-on::before{opacity:1;}.module-card.tone-diagram{--tone:#0f766e;--tone-soft:#d9f2ec;--tone-ink:#0b5c56;}.module-card.tone-visuals{--tone:#b45309;--tone-soft:#f8e8d4;--tone-ink:#92400e;}.module-card.tone-layouts{--tone:#1d4f91;--tone-soft:#dce8f7;--tone-ink:#1e3a8a;}.module-card.tone-nav{--tone:#7c3d2b;--tone-soft:#f3ddd4;--tone-ink:#7c2d12;}.module-card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;}.module-icon{width:48px;height:48px;border-radius:14px;display:grid;place-items:center;background:var(--tone-soft,#e6f3ef);color:var(--tone-ink,var(--rq-accent-deep));transition:transform 0.25s ease;}.module-card:hover .module-icon{transform:scale(1.04);}.module-icon svg{width:24px;height:24px;}.module-info{display:flex;flex-direction:column;gap:6px;}.module-badge{display:inline-flex;align-self:flex-start;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;background:var(--tone-soft,#e6f3ef);color:var(--tone-ink,var(--rq-accent-deep));padding:3px 8px;border-radius:6px;}.module-info h4{margin:0;font-size:16px;font-weight:700;color:var(--rq-ink);letter-spacing:-0.01em;}.module-info p{margin:0;font-size:13px;color:var(--rq-muted);line-height:1.5;}.module-meta{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;}.module-meta span{font-size:10.5px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:var(--rq-muted);background:#eef3f0;border:1px solid var(--rq-line);padding:3px 8px;border-radius:999px;}.switch{position:relative;display:inline-block;width:48px;height:28px;flex-shrink:0;}.switch input{opacity:0;width:0;height:0;}.slider{position:absolute;cursor:pointer;inset:0;background-color:#c5d0ca;transition:0.25s;}.slider:before{position:absolute;content:\"\";height:22px;width:22px;left:3px;bottom:3px;background-color:white;transition:0.25s;box-shadow:0 2px 6px rgba(0,0,0,0.16);}input:checked + .slider{background-color:var(--rq-accent);}input:focus-visible + .slider{outline:2px solid var(--rq-accent);outline-offset:2px;}input:checked + .slider:before{transform:translateX(20px);}.slider.round{border-radius:34px;}.slider.round:before{border-radius:50%;}.modules-footer{margin-top:22px;padding:16px 18px;border:1px solid var(--rq-line);border-radius:14px;background:var(--rq-soft);position:sticky;bottom:12px;}.form-footer{display:flex;align-items:center;gap:16px;}.save-status{font-size:13px;font-weight:600;transition:opacity 0.3s;}.save-status.success{color:var(--rq-ok);}.save-status.error{color:#dc2626;}.system-table{width:100%;border-collapse:collapse;}.system-table td{padding:12px 0;border-bottom:1px solid #eef3f0;font-size:14px;}.system-table tr:last-child td{border-bottom:none;}.status-badge{display:inline-block;font-size:11px;font-weight:700;padding:4px 8px;border-radius:6px;}.status-badge.active{background:#dcfce7;color:#15803d;}.status-badge.inactive{background:#fee2e2;color:#b91c1c;}.rawnaq-doc-card{border:1px solid var(--rq-line);border-radius:14px;padding:22px;background:#fff;line-height:1.6;}.rawnaq-doc-card h3{margin:0 0 8px 0;font-size:17px;color:var(--rq-ink);}.rawnaq-doc-card h4{margin:20px 0 10px 0;font-size:14px;color:var(--rq-muted);border-bottom:1px solid #eef3f0;padding-bottom:6px;}.rawnaq-doc-card ul{list-style-type:disc;margin-left:20px;margin-bottom:12px;}.rawnaq-doc-card li{margin-bottom:8px;font-size:13.5px;color:var(--rq-muted);}@media (max-width:960px){.rawnaq-layout{grid-template-columns:1fr;}.modules-grid,.grid-2{grid-template-columns:1fr;}.modules-hero{flex-direction:column;align-items:flex-start;}}.rawnaq-tk-popup{position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.72);display:flex;align-items:center;justify-content:center;padding:24px;}.rawnaq-tk-popup__inner{background:#1c1c28;border-radius:12px;width:min(960px,100%);max-height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 24px 80px rgba(0,0,0,.6);}.rawnaq-tk-popup__header{display:flex;align-items:center;gap:12px;padding:18px 24px;border-bottom:1px solid rgba(255,255,255,.08);flex-shrink:0;}.rawnaq-tk-popup__title{font-weight:700;font-size:16px;color:#fff;margin-right:auto;}.rawnaq-tk-popup__filters{display:flex;gap:8px;flex-wrap:wrap;}.rawnaq-tk-filter-btn{padding:4px 14px;border-radius:20px;border:1px solid rgba(255,255,255,.2);background:transparent;color:rgba(255,255,255,.6);font-size:12px;cursor:pointer;transition:all .2s;}.rawnaq-tk-filter-btn:hover,.rawnaq-tk-filter-btn.active{background:#6366f1;border-color:#6366f1;color:#fff;}.rawnaq-tk-popup__close{background:none;border:none;color:rgba(255,255,255,.5);font-size:18px;cursor:pointer;line-height:1;padding:4px 8px;border-radius:4px;transition:color .2s;}.rawnaq-tk-popup__close:hover{color:#fff;}.rawnaq-tk-popup__grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;padding:24px;overflow-y:auto;}.rawnaq-tk-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:10px;overflow:hidden;transition:border-color .2s,transform .2s;}.rawnaq-tk-card:not(.rawnaq-tk-card--disabled):hover{border-color:#6366f1;transform:translateY(-2px);}.rawnaq-tk-card--disabled{opacity:.45;}.rawnaq-tk-card__thumb{aspect-ratio:16/9;background:#111;overflow:hidden;}.rawnaq-tk-card__thumb img{width:100%;height:100%;object-fit:cover;display:block;}.rawnaq-tk-card__thumb-placeholder{width:100%;height:100%;background:linear-gradient(135deg,#1e1e2e,#2d2d44);}.rawnaq-tk-card__footer{padding:12px 14px;display:flex;align-items:center;justify-content:space-between;gap:8px;}.rawnaq-tk-card__title{font-size:13px;color:#fff;font-weight:600;}.rawnaq-tk-card__insert{font-size:11px;padding:4px 12px;border-radius:6px;background:#6366f1;color:#fff;border:none;cursor:pointer;white-space:nowrap;transition:background .2s;}.rawnaq-tk-card__insert:hover{background:#4f46e5;}.rawnaq-tk-card__insert:disabled{opacity:.6;cursor:wait;}.rawnaq-tk-card__badge--disabled{font-size:10px;color:#f59e0b;white-space:nowrap;}#rawnaq-tk-trigger{margin-top:8px;display:flex;align-items:center;gap:6px;width:100%;justify-content:center;background:rgba(99,102,241,.12);border:1px dashed #6366f1;color:#818cf8;border-radius:6px;padding:8px 12px;font-size:12px;cursor:pointer;transition:background .2s;}#rawnaq-tk-trigger:hover{background:rgba(99,102,241,.25);}.rawnaq-tk-card__thumb{position:relative;overflow:hidden;}.rawnaq-tk-card__overlay{position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.55);backdrop-filter:blur(3px);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity 0.25s ease;}.rawnaq-tk-card:hover .rawnaq-tk-card__overlay{opacity:1;}.rawnaq-tk-card__preview-btn{background:#ffffff;color:#1e293b;border:none;padding:8px 14px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:transform 0.2s ease,background 0.2s ease;}.rawnaq-tk-card__preview-btn:hover{transform:scale(1.05);background:#f8fafc;}.rawnaq-tk-card__insert--auto-enable{background:linear-gradient(135deg,#6366f1 0%,#a855f7 100%) !important;color:#ffffff !important;border:none !important;box-shadow:0 4px 12px rgba(168,85,247,0.3) !important;}.rawnaq-tk-card__insert--auto-enable:hover{opacity:0.92;transform:translateY(-1px);}.rawnaq-tk-lightbox{position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(15,23,42,0.75);backdrop-filter:blur(8px);z-index:999999;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity 0.3s ease;}.rawnaq-tk-lightbox.is-open{opacity:1;pointer-events:auto;}.rawnaq-tk-lightbox__inner{width:92%;height:90vh;background:#0f172a;border-radius:16px;border:1px solid rgba(255,255,255,0.15);box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);display:flex;flex-direction:column;overflow:hidden;}.rawnaq-tk-lightbox__header{height:60px;background:#1e293b;border-bottom:1px solid rgba(255,255,255,0.1);padding:0 20px;display:flex;align-items:center;justify-content:space-between;}.rawnaq-tk-lightbox__meta{display:flex;align-items:center;gap:12px;}.rawnaq-tk-lightbox__title{color:#ffffff;font-size:16px;font-weight:700;}.rawnaq-tk-lightbox__tag{background:rgba(99,102,241,0.2);color:#818cf8;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;}.rawnaq-tk-lightbox__viewports{display:flex;background:#0f172a;padding:4px;border-radius:8px;gap:4px;}.rawnaq-tk-vp-btn{background:transparent;border:none;color:#94a3b8;padding:6px 12px;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s ease;}.rawnaq-tk-vp-btn.active,.rawnaq-tk-vp-btn:hover{background:#334155;color:#ffffff;}.rawnaq-tk-lightbox__actions{display:flex;align-items:center;gap:12px;}.rawnaq-tk-lightbox__insert-btn{background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 100%);color:#ffffff;border:none;padding:8px 18px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;transition:transform 0.2s ease,opacity 0.2s ease;}.rawnaq-tk-lightbox__insert-btn:hover{transform:scale(1.03);opacity:0.95;}.rawnaq-tk-lightbox__close{background:transparent;border:none;color:#94a3b8;font-size:20px;cursor:pointer;padding:4px 8px;transition:color 0.2s ease;}.rawnaq-tk-lightbox__close:hover{color:#ef4444;}.rawnaq-tk-lightbox__stage{flex-grow:1;background:#090d16;display:flex;align-items:center;justify-content:center;padding:24px;overflow:auto;}.rawnaq-tk-lightbox__frame-wrap{background:#ffffff;border-radius:12px;box-shadow:0 20px 40px rgba(0,0,0,0.4);overflow:hidden;transition:width 0.35s cubic-bezier(0.175,0.885,0.32,1.275);}.rawnaq-tk-lightbox__frame-wrap.vp-desktop{width:100%;max-width:1200px;}.rawnaq-tk-lightbox__frame-wrap.vp-tablet{width:768px;}.rawnaq-tk-lightbox__frame-wrap.vp-mobile{width:375px;}.rawnaq-tk-lightbox__preview-img{width:100%;height:auto;display:block;}.rawnaq-tk-tabs{display:flex;gap:4px;padding:4px;background:rgba(255,255,255,0.05);border-radius:10px;margin-left:auto;}.rawnaq-tk-tab{background:transparent;border:none;color:#94a3b8;padding:7px 16px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;transition:all 0.2s ease;white-space:nowrap;}.rawnaq-tk-tab.active,.rawnaq-tk-tab:hover{background:#1e293b;color:#f8fafc;}.rawnaq-tk-tab.active{box-shadow:0 1px 4px rgba(0,0,0,0.2);}.rawnaq-tk-card__actions{display:flex;gap:6px;align-items:center;margin-top:8px;}.rawnaq-tk-card__palette-btn{background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1);color:#94a3b8;border-radius:6px;padding:6px 10px;font-size:14px;cursor:pointer;transition:all 0.2s ease;flex-shrink:0;}.rawnaq-tk-card__palette-btn:hover{background:rgba(99,102,241,0.15);color:#818cf8;border-color:#6366f1;}.rawnaq-tk-kits-grid{grid-template-columns:repeat(auto-fill,minmax(320px,1fr));}.rawnaq-tk-kit-card{border-radius:12px;}.rawnaq-tk-kit-thumb{height:180px;overflow:hidden;position:relative;}.rawnaq-tk-kit-placeholder{width:100%;height:100%;background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);display:flex;align-items:center;justify-content:center;}.rawnaq-tk-kit-placeholder span{color:#475569;font-size:15px;font-weight:700;letter-spacing:0.05em;text-align:center;padding:0 16px;}.rawnaq-tk-kit-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(10,10,20,0.9) 40%,transparent 100%);padding:16px;display:flex;align-items:flex-end;opacity:0;transition:opacity 0.25s ease;}.rawnaq-tk-kit-card:hover .rawnaq-tk-kit-overlay{opacity:1;}.rawnaq-tk-kit-sections-preview{display:flex;flex-wrap:wrap;gap:5px;}.rawnaq-tk-kit-badge{background:rgba(99,102,241,0.25);border:1px solid rgba(99,102,241,0.4);color:#a5b4fc;padding:3px 8px;border-radius:20px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;}.rawnaq-tk-kit-footer{display:block;padding:12px 14px;}.rawnaq-tk-kit-meta{margin-bottom:10px;}.rawnaq-tk-kit-desc{color:#64748b;font-size:12px;margin:4px 0 8px;line-height:1.5;}.rawnaq-tk-kit-tags{display:flex;flex-wrap:wrap;gap:4px;}.rawnaq-tk-tag{background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);color:#94a3b8;padding:2px 7px;border-radius:20px;font-size:10px;font-weight:600;}.rawnaq-tk-kit-import{flex:1;text-align:center;padding:9px 14px !important;font-size:13px !important;}.rawnaq-tk-color-panel{position:fixed;top:0;right:-440px;width:420px;height:100vh;background:#0f172a;border-left:1px solid rgba(255,255,255,0.08);box-shadow:-8px 0 32px rgba(0,0,0,0.4);z-index:1000000;display:flex;flex-direction:column;transition:right 0.35s cubic-bezier(0.165,0.84,0.44,1);overflow:hidden;}.rawnaq-tk-color-panel.is-open{right:0;}.rawnaq-tk-color-panel__inner{display:flex;flex-direction:column;height:100%;overflow:hidden;}.rawnaq-tk-color-panel__header{display:flex;align-items:flex-start;justify-content:space-between;padding:20px 20px 16px;border-bottom:1px solid rgba(255,255,255,0.08);background:#1e293b;flex-shrink:0;}.rawnaq-tk-color-panel__title{color:#f8fafc;font-size:16px;font-weight:700;margin:0 0 4px;}.rawnaq-tk-color-panel__subtitle{color:#64748b;font-size:12px;margin:0;}.rawnaq-tk-color-panel__close{background:rgba(255,255,255,0.07);border:none;color:#94a3b8;width:30px;height:30px;border-radius:6px;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;transition:all 0.2s;flex-shrink:0;}.rawnaq-tk-color-panel__close:hover{background:rgba(239,68,68,0.15);color:#ef4444;}.rawnaq-tk-color-panel__notice{background:rgba(99,102,241,0.1);border:1px solid rgba(99,102,241,0.2);border-radius:8px;color:#818cf8;font-size:12px;padding:10px 14px;margin:16px 16px 0;flex-shrink:0;}.rawnaq-tk-color-swatches{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px;}.rawnaq-tk-color-row{background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:12px 14px;display:flex;align-items:center;justify-content:space-between;gap:12px;}.rawnaq-tk-color-label{display:flex;flex-direction:column;gap:3px;font-size:13px;color:#e2e8f0;font-weight:600;}.rawnaq-tk-token-code{font-size:9px;color:#475569;font-family:monospace;background:rgba(0,0,0,0.3);padding:1px 5px;border-radius:3px;}.rawnaq-tk-color-input-wrap{display:flex;align-items:center;gap:8px;}.rawnaq-tk-color-swatch{width:36px;height:36px;border:none;border-radius:8px;cursor:pointer;padding:2px;background:transparent;}.rawnaq-tk-color-swatch::-webkit-color-swatch-wrapper{padding:0;}.rawnaq-tk-color-swatch::-webkit-color-swatch{border-radius:6px;border:none;}.rawnaq-tk-color-hex{width:80px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:6px;color:#e2e8f0;font-size:12px;font-family:monospace;padding:6px 8px;text-align:center;}.rawnaq-tk-color-hex:focus{outline:none;border-color:#6366f1;}.rawnaq-tk-color-panel__footer{display:flex;gap:8px;padding:16px;border-top:1px solid rgba(255,255,255,0.08);background:#1e293b;flex-shrink:0;}.rawnaq-tk-color-panel__reset{background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:#94a3b8;padding:10px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s;}.rawnaq-tk-color-panel__reset:hover{background:rgba(255,255,255,0.1);color:#e2e8f0;}.rawnaq-tk-color-panel__apply{flex:1;background:linear-gradient(135deg,#6366f1,#8b5cf6);border:none;color:#fff;padding:10px 14px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;transition:opacity 0.2s,transform 0.1s;}.rawnaq-tk-color-panel__apply:hover{opacity:0.9;transform:translateY(-1px);}.rawnaq-tk-lightbox__palette-btn{background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);color:#94a3b8;padding:7px 12px;border-radius:7px;font-size:13px;cursor:pointer;transition:all 0.2s;}.rawnaq-tk-lightbox__palette-btn:hover{background:rgba(99,102,241,0.15);border-color:#6366f1;color:#818cf8;}.rawnaq-tk-trigger--panel{display:flex;align-items:center;gap:8px;width:calc(100% - 20px);margin:8px 10px;padding:10px 14px;background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 100%);border:none;border-radius:8px;color:#ffffff;font-size:13px;font-weight:700;cursor:pointer;transition:opacity 0.2s,transform 0.15s;box-shadow:0 2px 10px rgba(99,102,241,0.35);letter-spacing:0.02em;}.rawnaq-tk-trigger--panel:hover{opacity:0.92;transform:translateY(-1px);}.rawnaq-tk-trigger--panel i{font-size:16px;}.rawnaq-tk-trigger--fab{position:fixed;bottom:80px;left:185px;z-index:99999;display:flex;align-items:center;gap:8px;padding:12px 18px;background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 100%);border:none;border-radius:50px;color:#ffffff;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 4px 20px rgba(99,102,241,0.5);transition:transform 0.2s,box-shadow 0.2s;white-space:nowrap;}.rawnaq-tk-trigger--fab:hover{transform:translateY(-2px) scale(1.03);box-shadow:0 6px 24px rgba(99,102,241,0.65);}.rawnaq-tk-trigger--fab i{font-size:16px;}#rawnaq-tk-fab{position:fixed;top:50%;left:170px;transform:translateY(-50%);z-index:9999999;display:flex;align-items:center;gap:8px;padding:12px 18px;background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 100%);border:none;border-radius:0 50px 50px 0;color:#ffffff;font-size:13px;font-weight:700;cursor:pointer;box-shadow:4px 0 20px rgba(99,102,241,0.5);transition:transform 0.2s,box-shadow 0.2s,left 0.2s;white-space:nowrap;writing-mode:horizontal-tb;animation:rawnaq-pulse 2.5s ease-in-out infinite;}@keyframes rawnaq-pulse{0%,100%{box-shadow:4px 0 20px rgba(99,102,241,0.5);}50%{box-shadow:4px 0 30px rgba(139,92,246,0.8);}}#rawnaq-tk-fab:hover{left:175px;box-shadow:4px 0 28px rgba(99,102,241,0.75);animation:none;}#rawnaq-tk-fab i{font-size:16px;}.rawnaq-tk-panel-banner{display:flex !important;align-items:center !important;justify-content:space-between !important;background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 100%) !important;border-radius:10px !important;padding:10px 14px !important;margin:12px 10px 14px 10px !important;color:#ffffff !important;cursor:pointer !important;box-shadow:0 4px 14px rgba(99,102,241,0.35) !important;transition:all 0.2s ease !important;box-sizing:border-box !important;width:calc(100% - 20px) !important;clear:both !important;float:none !important;z-index:10 !important;position:relative !important;}.rawnaq-tk-panel-banner:hover{transform:translateY(-2px) !important;box-shadow:0 6px 20px rgba(99,102,241,0.5) !important;}.rawnaq-tk-panel-banner__content{display:flex !important;align-items:center !important;gap:10px !important;}.rawnaq-tk-panel-banner__icon{font-size:20px !important;line-height:1 !important;}.rawnaq-tk-panel-banner__text{display:flex !important;flex-direction:column !important;text-align:left !important;}.rawnaq-tk-panel-banner__title{font-weight:700 !important;font-size:12px !important;line-height:1.2 !important;color:#ffffff !important;letter-spacing:0.02em !important;}.rawnaq-tk-panel-banner__sub{font-size:10px !important;color:rgba(255,255,255,0.8) !important;margin-top:2px !important;font-weight:400 !important;}.rawnaq-tk-panel-banner__btn{background:rgba(255,255,255,0.22) !important;border:1px solid rgba(255,255,255,0.35) !important;color:#ffffff !important;border-radius:6px !important;padding:5px 11px !important;font-size:11px !important;font-weight:700 !important;pointer-events:none !important;white-space:nowrap !important;}.rawnaq-tk-popup{position:fixed !important;inset:0 !important;z-index:9999999 !important;background:rgba(10,10,20,0.82) !important;backdrop-filter:blur(8px) !important;-webkit-backdrop-filter:blur(8px) !important;display:none;align-items:center !important;justify-content:center !important;padding:24px !important;box-sizing:border-box !important;}.rawnaq-tk-popup.is-open{display:flex !important;}";
		document.head.appendChild( style );
	}

	// =========================================================================
	// Popup DOM Builder (Self-Healing)
	// =========================================================================
	function buildPopup() {
		injectInlineStyles();

		let popup = document.getElementById( 'rawnaq-tk-popup' );
		if ( ! popup ) {
			popup = document.createElement( 'div' );
			popup.id        = 'rawnaq-tk-popup';
			popup.className = 'rawnaq-tk-popup';
			popup.style.display = 'none';
			popup.setAttribute( 'aria-modal', 'true' );
			popup.setAttribute( 'role', 'dialog' );
			popup.setAttribute( 'aria-label', cfg.i18n.title || 'Rawnaq Starter Sections' );
			popup.innerHTML = `
				<div class="rawnaq-tk-popup__inner">
					<div class="rawnaq-tk-popup__header">
						<span class="rawnaq-tk-popup__title">✨ Rawnaq Starter Kits</span>
						<div class="rawnaq-tk-popup__filters" id="rawnaq-tk-filters"></div>
						<button class="rawnaq-tk-popup__close" type="button" aria-label="Close">&#x2715;</button>
					</div>
					<div class="rawnaq-tk-popup__grid" id="rawnaq-tk-grid"></div>
				</div>`;
			document.body.appendChild( popup );
		}

		const gridEl    = document.getElementById( 'rawnaq-tk-grid' );
		const filtersEl = document.getElementById( 'rawnaq-tk-filters' );

		if ( ! gridEl || ! filtersEl ) return;

		gridEl.innerHTML    = '';
		filtersEl.innerHTML = '';

		// ── Tab Bar ────────────────────────────────────────────────────────────
		const headerEl = popup.querySelector( '.rawnaq-tk-popup__header' );
		let tabBar     = popup.querySelector( '.rawnaq-tk-tabs' );
		if ( ! tabBar && headerEl ) {
			tabBar = document.createElement( 'div' );
			tabBar.className = 'rawnaq-tk-tabs';
			tabBar.innerHTML = `
				<button type="button" class="rawnaq-tk-tab active" data-tab="sections">📦 Sections</button>
				<button type="button" class="rawnaq-tk-tab" data-tab="page-kits">🗂️ Page Kits</button>`;
			headerEl.appendChild( tabBar );

			tabBar.querySelectorAll( '.rawnaq-tk-tab' ).forEach( btn => {
				btn.addEventListener( 'click', () => switchTab( btn.dataset.tab, filtersEl, gridEl, tabBar ) );
			} );
		}

		// ── Sections Tab ───────────────────────────────────────────────────────
		const cats   = [ ...new Set( sections.map( t => t.category ) ) ];
		const allBtn = createFilterBtn( cfg.i18n.all || 'All', 'all', true );
		filtersEl.appendChild( allBtn );
		cats.forEach( cat => filtersEl.appendChild( createFilterBtn( cap( cat ), cat, false ) ) );
		sections.forEach( tpl => gridEl.appendChild( createSectionCard( tpl ) ) );

		// ── Page Kits Tab ──────────────────────────────────────────────────────
		let kitsGrid = document.getElementById( 'rawnaq-tk-kits-grid' );
		if ( ! kitsGrid ) {
			kitsGrid = document.createElement( 'div' );
			kitsGrid.id        = 'rawnaq-tk-kits-grid';
			kitsGrid.className = 'rawnaq-tk-grid rawnaq-tk-kits-grid';
			kitsGrid.style.display = 'none';
			gridEl.parentElement.insertBefore( kitsGrid, gridEl.nextSibling );
		}
		kitsGrid.innerHTML = '';
		pageKits.forEach( kit => kitsGrid.appendChild( createKitCard( kit ) ) );

		// ── Close Handlers ─────────────────────────────────────────────────────
		popup.querySelector( '.rawnaq-tk-popup__close' )?.addEventListener( 'click', hidePopup );
		popup.addEventListener( 'click', e => {
			if ( e.target === popup ) hidePopup();
		} );
	}

	function switchTab( tab, filtersEl, gridEl, tabBar ) {
		tabBar.querySelectorAll( '.rawnaq-tk-tab' ).forEach( b => b.classList.toggle( 'active', b.dataset.tab === tab ) );
		const kitsGrid = document.getElementById( 'rawnaq-tk-kits-grid' );
		if ( tab === 'sections' ) {
			filtersEl.style.display = '';
			gridEl.style.display    = '';
			if ( kitsGrid ) kitsGrid.style.display = 'none';
		} else {
			filtersEl.style.display = 'none';
			gridEl.style.display    = 'none';
			if ( kitsGrid ) kitsGrid.style.display = '';
		}
	}

	// =========================================================================
	// Section Cards
	// =========================================================================
	function createFilterBtn( label, value, active ) {
		const btn = document.createElement( 'button' );
		btn.type        = 'button';
		btn.textContent = label;
		btn.className   = 'rawnaq-tk-filter-btn' + ( active ? ' active' : '' );
		btn.dataset.cat = value;
		btn.addEventListener( 'click', () => {
			document.querySelectorAll( '.rawnaq-tk-filter-btn' ).forEach( b => b.classList.remove( 'active' ) );
			btn.classList.add( 'active' );
			filterCards( value );
		} );
		return btn;
	}

	function createSectionCard( tpl ) {
		const disabled = tpl.missing_modules && tpl.missing_modules.length > 0;
		const card     = document.createElement( 'div' );
		card.className   = 'rawnaq-tk-card';
		card.dataset.cat = tpl.category;
		card.dataset.id  = tpl.id;

		card.innerHTML = `
			<div class="rawnaq-tk-card__thumb">
				${ tpl.thumbnail_url
					? `<img src="${ escAttr( tpl.thumbnail_url ) }" alt="" loading="lazy" />`
					: `<div class="rawnaq-tk-card__thumb-placeholder"></div>` }
				<div class="rawnaq-tk-card__overlay">
					<button type="button" class="rawnaq-tk-card__preview-btn" data-id="${ escAttr( tpl.id ) }">👁️ Preview</button>
				</div>
			</div>
			<div class="rawnaq-tk-card__footer">
				<span class="rawnaq-tk-card__title">${ escHtml( tpl.title ) }</span>
				<div class="rawnaq-tk-card__actions">
					<button type="button" class="rawnaq-tk-card__palette-btn" title="Customize Colors" data-id="${ escAttr( tpl.id ) }">🎨</button>
					${ disabled
						? `<button type="button" class="rawnaq-tk-card__insert rawnaq-tk-card__insert--auto-enable" data-id="${ escAttr( tpl.id ) }">⚡ Enable & Insert</button>`
						: `<button type="button" class="rawnaq-tk-card__insert" data-id="${ escAttr( tpl.id ) }">${ escHtml( cfg.i18n.insert || 'Insert Section' ) }</button>` }
				</div>
			</div>`;

		card.querySelector( '.rawnaq-tk-card__preview-btn' )?.addEventListener( 'click', e => {
			e.stopPropagation();
			openLightbox( tpl, false );
		} );

		card.querySelector( '.rawnaq-tk-card__palette-btn' )?.addEventListener( 'click', e => {
			e.stopPropagation();
			openColorCustomizer( tpl, false );
		} );

		card.querySelector( '.rawnaq-tk-card__insert' )?.addEventListener( 'click', () => {
			importSection( tpl.id, disabled, {} );
		} );

		return card;
	}

	function filterCards( cat ) {
		document.querySelectorAll( '.rawnaq-tk-card' ).forEach( c => {
			c.style.display = ( cat === 'all' || c.dataset.cat === cat ) ? '' : 'none';
		} );
	}

	// =========================================================================
	// Page Kit Cards
	// =========================================================================
	function createKitCard( kit ) {
		const disabled = kit.missing_modules && kit.missing_modules.length > 0;
		const card     = document.createElement( 'div' );
		card.className     = 'rawnaq-tk-card rawnaq-tk-kit-card';
		card.dataset.kitId = kit.id;

		const sectionBadges = ( kit.section_details || [] ).map(
			s => `<span class="rawnaq-tk-kit-badge">${ escHtml( s.title ) }</span>`
		).join( '' );

		const tagBadges = ( kit.tags || [] ).map(
			t => `<span class="rawnaq-tk-tag">${ escHtml( t ) }</span>`
		).join( '' );

		card.innerHTML = `
			<div class="rawnaq-tk-card__thumb rawnaq-tk-kit-thumb">
				${ kit.thumbnail_url
					? `<img src="${ escAttr( kit.thumbnail_url ) }" alt="" loading="lazy" />`
					: `<div class="rawnaq-tk-card__thumb-placeholder rawnaq-tk-kit-placeholder"><span>${ escHtml( kit.title ) }</span></div>` }
				<div class="rawnaq-tk-kit-overlay">
					<div class="rawnaq-tk-kit-sections-preview">
						${ sectionBadges }
					</div>
				</div>
			</div>
			<div class="rawnaq-tk-card__footer rawnaq-tk-kit-footer">
				<div class="rawnaq-tk-kit-meta">
					<span class="rawnaq-tk-card__title">${ escHtml( kit.title ) }</span>
					<p class="rawnaq-tk-kit-desc">${ escHtml( kit.description || '' ) }</p>
					<div class="rawnaq-tk-kit-tags">${ tagBadges }</div>
				</div>
				<div class="rawnaq-tk-card__actions">
					<button type="button" class="rawnaq-tk-card__palette-btn rawnaq-tk-kit-palette" title="Customize Colors" data-kit-id="${ escAttr( kit.id ) }">🎨</button>
					<button type="button" class="rawnaq-tk-card__insert rawnaq-tk-kit-import${ disabled ? ' rawnaq-tk-card__insert--auto-enable' : '' }" data-kit-id="${ escAttr( kit.id ) }">
						${ disabled ? '⚡ Enable & Import Page' : '🗂️ Import Full Page' }
					</button>
				</div>
			</div>`;

		card.querySelector( '.rawnaq-tk-kit-palette' )?.addEventListener( 'click', e => {
			e.stopPropagation();
			openColorCustomizer( null, true, kit );
		} );

		card.querySelector( '.rawnaq-tk-kit-import' )?.addEventListener( 'click', () => {
			importPageKit( kit.id, disabled, {} );
		} );

		return card;
	}

	// =========================================================================
	// Color Customizer Panel
	// =========================================================================
	const TOKEN_LABELS = {
		COLOR_PRIMARY:   { label: 'Primary',    emoji: '🔵' },
		COLOR_SECONDARY: { label: 'Secondary',  emoji: '🟣' },
		COLOR_ACCENT:    { label: 'Accent',     emoji: '🩵' },
		COLOR_DARK:      { label: 'Dark BG',    emoji: '⬛' },
		COLOR_LIGHT:     { label: 'Light Text', emoji: '⬜' },
		COLOR_MUTED:     { label: 'Muted Text', emoji: '🔘' },
		COLOR_SUCCESS:   { label: 'Success',    emoji: '🟢' },
		COLOR_WARNING:   { label: 'Warning',    emoji: '🟡' },
	};

	function openColorCustomizer( tpl, isKit, kitData ) {
		let panel = document.getElementById( 'rawnaq-tk-color-panel' );
		if ( ! panel ) {
			panel = document.createElement( 'div' );
			panel.id        = 'rawnaq-tk-color-panel';
			panel.className = 'rawnaq-tk-color-panel';
			document.body.appendChild( panel );
		}

		const tokens = isKit
			? Object.keys( TOKEN_LABELS )
			: ( tpl.color_tokens || Object.keys( TOKEN_LABELS ) );

		const title = isKit ? kitData.title : tpl.title;
		const isDisabled = isKit
			? ( kitData.missing_modules && kitData.missing_modules.length > 0 )
			: ( tpl.missing_modules && tpl.missing_modules.length > 0 );

		const renderSwatches = ( palette ) => {
			return tokens.map( token => {
				const info  = TOKEN_LABELS[ token ] || { label: token, emoji: '🎨' };
				const value = palette ? ( palette[ token ] || '#6366f1' ) : '#6366f1';
				return `
					<div class="rawnaq-tk-color-row">
						<label class="rawnaq-tk-color-label">
							<span>${ info.emoji } ${ info.label }</span>
							<code class="rawnaq-tk-token-code">{{${ token }}}</code>
						</label>
						<div class="rawnaq-tk-color-input-wrap">
							<input type="color" class="rawnaq-tk-color-swatch" data-token="${ token }" value="${ escAttr( value ) }" />
							<input type="text"  class="rawnaq-tk-color-hex" data-token="${ token }" value="${ escAttr( value ) }" maxlength="7" placeholder="#000000" />
						</div>
					</div>`;
			} ).join( '' );
		};

		const loadAndRender = ( palette ) => {
			panel.innerHTML = `
				<div class="rawnaq-tk-color-panel__inner">
					<div class="rawnaq-tk-color-panel__header">
						<div>
							<h3 class="rawnaq-tk-color-panel__title">🎨 Customize Colors</h3>
							<p class="rawnaq-tk-color-panel__subtitle">${ escHtml( title ) }</p>
						</div>
						<button type="button" class="rawnaq-tk-color-panel__close">✕</button>
					</div>
					<div class="rawnaq-tk-color-panel__notice">
						Colors are auto-filled from your site's palette. Tweak as needed.
					</div>
					<div class="rawnaq-tk-color-swatches">
						${ renderSwatches( palette ) }
					</div>
					<div class="rawnaq-tk-color-panel__footer">
						<button type="button" class="rawnaq-tk-color-panel__reset">↺ Reset to Site Palette</button>
						<button type="button" class="rawnaq-tk-color-panel__apply ${ isDisabled ? 'rawnaq-tk-card__insert--auto-enable' : '' }">
							${ isDisabled ? '⚡ Enable & Apply' : '✅ Apply & Insert' }
						</button>
					</div>
				</div>`;

			bindColorInputSync( panel );

			panel.querySelector( '.rawnaq-tk-color-panel__close' ).addEventListener( 'click', closeColorCustomizer );
			panel.querySelector( '.rawnaq-tk-color-panel__reset' ).addEventListener( 'click', () => {
				fetchSitePalette().then( p => loadAndRender( p ) );
			} );
			panel.querySelector( '.rawnaq-tk-color-panel__apply' ).addEventListener( 'click', () => {
				const overrides = collectOverrides( panel );
				closeColorCustomizer();
				if ( isKit ) {
					importPageKit( kitData.id, isDisabled, overrides );
				} else {
					importSection( tpl.id, isDisabled, overrides );
				}
			} );

			panel.classList.add( 'is-open' );
		};

		fetchSitePalette().then( loadAndRender );
	}

	function bindColorInputSync( panel ) {
		panel.querySelectorAll( '.rawnaq-tk-color-swatch' ).forEach( swatch => {
			const token    = swatch.dataset.token;
			const hexInput = panel.querySelector( `.rawnaq-tk-color-hex[data-token="${ token }"]` );
			swatch.addEventListener( 'input', () => { if ( hexInput ) hexInput.value = swatch.value; } );
		} );
		panel.querySelectorAll( '.rawnaq-tk-color-hex' ).forEach( hexInput => {
			const token  = hexInput.dataset.token;
			const swatch = panel.querySelector( `.rawnaq-tk-color-swatch[data-token="${ token }"]` );
			hexInput.addEventListener( 'input', () => {
				const v = hexInput.value;
				if ( swatch && /^#[0-9a-f]{6}$/i.test( v ) ) swatch.value = v;
			} );
		} );
	}

	function collectOverrides( panel ) {
		const overrides = {};
		panel.querySelectorAll( '.rawnaq-tk-color-hex' ).forEach( input => {
			const token = input.dataset.token;
			const val   = input.value;
			if ( token && /^#[0-9a-f]{6}$/i.test( val ) ) {
				overrides[ token ] = val;
			}
		} );
		return overrides;
	}

	function closeColorCustomizer() {
		const panel = document.getElementById( 'rawnaq-tk-color-panel' );
		if ( panel ) panel.classList.remove( 'is-open' );
	}

	function fetchSitePalette() {
		if ( sitePalette ) return Promise.resolve( sitePalette );

		const body = new FormData();
		body.append( 'action', 'rawnaq_get_site_palette' );
		body.append( 'nonce',  cfg.nonce );

		return fetch( cfg.ajaxUrl, { method: 'POST', body } )
			.then( r => r.json() )
			.then( res => {
				if ( res.success && res.data ) {
					sitePalette = res.data;
					return sitePalette;
				}
				return null;
			} )
			.catch( () => null );
	}

	// =========================================================================
	// Lightbox Preview
	// =========================================================================
	function openLightbox( tpl, isKit ) {
		let lightbox = document.getElementById( 'rawnaq-tk-lightbox' );
		if ( ! lightbox ) {
			lightbox = document.createElement( 'div' );
			lightbox.id        = 'rawnaq-tk-lightbox';
			lightbox.className = 'rawnaq-tk-lightbox';
			lightbox.innerHTML = `
				<div class="rawnaq-tk-lightbox__inner">
					<div class="rawnaq-tk-lightbox__header">
						<div class="rawnaq-tk-lightbox__meta">
							<span class="rawnaq-tk-lightbox__title"></span>
							<span class="rawnaq-tk-lightbox__tag"></span>
						</div>
						<div class="rawnaq-tk-lightbox__viewports">
							<button type="button" class="rawnaq-tk-vp-btn active" data-vp="desktop">💻 Desktop</button>
							<button type="button" class="rawnaq-tk-vp-btn" data-vp="tablet">📱 Tablet</button>
							<button type="button" class="rawnaq-tk-vp-btn" data-vp="mobile">📱 Mobile</button>
						</div>
						<div class="rawnaq-tk-lightbox__actions">
							<button type="button" class="rawnaq-tk-lightbox__palette-btn">🎨 Colors</button>
							<button type="button" class="rawnaq-tk-lightbox__insert-btn">⚡ Insert Section</button>
							<button type="button" class="rawnaq-tk-lightbox__close">&#x2715;</button>
						</div>
					</div>
					<div class="rawnaq-tk-lightbox__stage">
						<div class="rawnaq-tk-lightbox__frame-wrap vp-desktop">
							<img class="rawnaq-tk-lightbox__preview-img" src="" alt="Template Preview" />
						</div>
					</div>
				</div>`;
			document.body.appendChild( lightbox );

			const frameWrap = lightbox.querySelector( '.rawnaq-tk-lightbox__frame-wrap' );
			lightbox.querySelectorAll( '.rawnaq-tk-vp-btn' ).forEach( b => {
				b.addEventListener( 'click', () => {
					lightbox.querySelectorAll( '.rawnaq-tk-vp-btn' ).forEach( x => x.classList.remove( 'active' ) );
					b.classList.add( 'active' );
					frameWrap.className = 'rawnaq-tk-lightbox__frame-wrap vp-' + b.dataset.vp;
				} );
			} );

			lightbox.querySelector( '.rawnaq-tk-lightbox__close' ).addEventListener( 'click', closeLightbox );
			lightbox.addEventListener( 'click', e => { if ( e.target === lightbox ) closeLightbox(); } );
		}

		const disabled = tpl.missing_modules && tpl.missing_modules.length > 0;

		lightbox.querySelector( '.rawnaq-tk-lightbox__title' ).textContent = tpl.title;
		lightbox.querySelector( '.rawnaq-tk-lightbox__tag' ).textContent   = cap( tpl.category );
		lightbox.querySelector( '.rawnaq-tk-lightbox__preview-img' ).src   = tpl.thumbnail_url || '';

		const insertBtn = lightbox.querySelector( '.rawnaq-tk-lightbox__insert-btn' );
		insertBtn.textContent = disabled ? '⚡ Enable & Insert' : 'Insert Section';
		insertBtn.onclick = () => { importSection( tpl.id, disabled, {} ); closeLightbox(); };

		const paletteBtn = lightbox.querySelector( '.rawnaq-tk-lightbox__palette-btn' );
		paletteBtn.onclick = () => { closeLightbox(); openColorCustomizer( tpl, false, null ); };

		lightbox.classList.add( 'is-open' );
	}

	function closeLightbox() {
		document.getElementById( 'rawnaq-tk-lightbox' )?.classList.remove( 'is-open' );
	}

	// =========================================================================
	// Import Handlers
	// =========================================================================
	function importSection( id, autoEnable, colorOverrides ) {
		const btn = document.querySelector( `.rawnaq-tk-card__insert[data-id="${ id }"]` );
		if ( btn ) { btn.textContent = cfg.i18n.inserting || 'Inserting…'; btn.disabled = true; }

		const body = new FormData();
		body.append( 'action',          'rawnaq_template_kit_import' );
		body.append( 'nonce',           cfg.nonce );
		body.append( 'template_id',     id );
		body.append( 'color_overrides', JSON.stringify( colorOverrides || {} ) );
		if ( autoEnable ) body.append( 'auto_enable', '1' );

		fetch( cfg.ajaxUrl, { method: 'POST', body } )
			.then( r => r.json() )
			.then( res => {
				if ( ! res.success ) {
					alert( res.data?.message || cfg.i18n.insertError || 'Could not insert section.' );
					if ( btn ) { btn.textContent = cfg.i18n.insert || 'Insert Section'; btn.disabled = false; }
					return;
				}
				insertElementorElements( res.data.elements );
				closeLightbox();
				hidePopup();
			} )
			.catch( () => {
				alert( cfg.i18n.insertError || 'Could not insert section.' );
				if ( btn ) { btn.textContent = cfg.i18n.insert || 'Insert Section'; btn.disabled = false; }
			} );
	}

	function importPageKit( kitId, autoEnable, colorOverrides ) {
		const btn = document.querySelector( `.rawnaq-tk-kit-import[data-kit-id="${ kitId }"]` );
		if ( btn ) { btn.textContent = '⏳ Importing...'; btn.disabled = true; }

		const body = new FormData();
		body.append( 'action',          'rawnaq_template_kit_import_page' );
		body.append( 'nonce',           cfg.nonce );
		body.append( 'kit_id',          kitId );
		body.append( 'color_overrides', JSON.stringify( colorOverrides || {} ) );
		if ( autoEnable ) body.append( 'auto_enable', '1' );

		fetch( cfg.ajaxUrl, { method: 'POST', body } )
			.then( r => r.json() )
			.then( res => {
				if ( ! res.success ) {
					alert( res.data?.message || cfg.i18n.insertError || 'Could not import page kit.' );
					if ( btn ) { btn.textContent = '🗂️ Import Full Page'; btn.disabled = false; }
					return;
				}
				insertElementorElements( res.data.elements );
				hidePopup();
			} )
			.catch( () => {
				alert( cfg.i18n.insertError || 'Could not import page kit.' );
				if ( btn ) { btn.textContent = '🗂️ Import Full Page'; btn.disabled = false; }
			} );
	}

	function insertElementorElements( elements ) {
		if ( ! Array.isArray( elements ) || ! elements.length ) return;
		const model   = elementor.getPreviewView().getOption( 'model' );
		const eImport = elementor.channels.data;
		elements.forEach( el => {
			eImport.trigger( 'element:before:add' );
			const newModel = model.get( 'elements' ).add( el );
			eImport.trigger( 'element:after:add', { model: newModel } );
		} );
		elementor.channels.editor.trigger( 'document:change' );
	}

	// =========================================================================
	// Show / Hide Popup
	// =========================================================================
	function showPopup() {
		let popup = document.getElementById( 'rawnaq-tk-popup' );
		if ( ! popup || ! popup.querySelector( '#rawnaq-tk-grid' ) ) {
			buildPopup();
			popup = document.getElementById( 'rawnaq-tk-popup' );
		}
		if ( popup ) {
			popup.style.display = 'flex';
			popup.removeAttribute( 'hidden' );
			popup.classList.add( 'is-open' );
		}
	}

	function hidePopup() {
		const popup = document.getElementById( 'rawnaq-tk-popup' );
		if ( popup ) {
			popup.style.display = 'none';
			popup.classList.remove( 'is-open' );
		}
	}

	window.rawnaqShowPopup = showPopup;

	// =========================================================================
	// Panel Banner & Canvas Injection
	// =========================================================================
	function injectPanelBanner() {
		if ( document.getElementById( 'rawnaq-tk-panel-banner' ) ) return true;

		// Prepend into the categories wrapper (full-width block container)
		const container =
			document.getElementById( 'elementor-panel-elements-categories' ) ||
			document.querySelector( '.elementor-panel-category-items' ) ||
			document.getElementById( 'elementor-panel-elements-wrapper' );

		if ( container ) {
			const banner = document.createElement( 'div' );
			banner.id        = 'rawnaq-tk-panel-banner';
			banner.className = 'rawnaq-tk-panel-banner';
			banner.innerHTML = `
				<div class="rawnaq-tk-panel-banner__content">
					<div class="rawnaq-tk-panel-banner__icon">✨</div>
					<div class="rawnaq-tk-panel-banner__text">
						<span class="rawnaq-tk-panel-banner__title">Rawnaq Starter Kits</span>
						<span class="rawnaq-tk-panel-banner__sub">12 Sections & Full Page Kits</span>
					</div>
				</div>
				<button type="button" class="rawnaq-tk-panel-banner__btn">Browse</button>
			`;

			banner.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				e.stopPropagation();
				showPopup();
			} );

			container.insertAdjacentElement( 'afterbegin', banner );
			console.log( '[Rawnaq] Panel banner prepended inside categories container.' );
			return true;
		}

		return false;
	}

	function setupCanvasInjection() {
		if ( ! elementor.$previewContents ) return;

		const injectCanvasBtn = () => {
			const addSectionAreas = elementor.$previewContents.find( '.elementor-add-section-area, .elementor-add-section-inline' );
			addSectionAreas.each( function () {
				const $area = jQuery( this );
				if ( $area.find( '.rawnaq-canvas-trigger' ).length ) return;

				const $target    = $area.find( '.elementor-add-section-area-button.elementor-add-template-button, .elementor-add-section-drag-title' ).first();
				const $container = $target.length ? $target.parent() : $area.find( '.elementor-add-section-area-button' ).parent();

				if ( $container.length ) {
					const $btn = jQuery( `
						<div class="elementor-add-section-area-button rawnaq-canvas-trigger" title="${ escAttr( cfg.i18n.title || 'Rawnaq Starter Sections' ) }" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; border-radius: 50%; width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; margin-left: 6px; cursor: pointer; font-size: 18px; box-shadow: 0 3px 10px rgba(99, 102, 241, 0.4); vertical-align: middle; transition: transform 0.2s;">
							<i class="eicon-sparkles" style="color: #ffffff;"></i>
						</div>
					` );

					$btn.on( 'click', function ( e ) {
						e.stopPropagation();
						showPopup();
					} );

					if ( $target.length ) {
						$target.after( $btn );
					} else {
						$container.append( $btn );
					}
				}
			} );
		};

		injectCanvasBtn();
		const observer = new MutationObserver( injectCanvasBtn );
		if ( elementor.$previewContents.find( 'body' )[0] ) {
			observer.observe( elementor.$previewContents.find( 'body' )[0], { childList: true, subtree: true } );
		}
	}

	// =========================================================================
	// Helpers
	// =========================================================================
	function escHtml( str ) { const d = document.createElement( 'div' ); d.textContent = str; return d.innerHTML; }
	function escAttr( str ) { return String( str ).replace( /"/g, '&quot;' ); }
	function cap( str )     { return str.charAt( 0 ).toUpperCase() + str.slice( 1 ).replace( /-/g, ' ' ); }

	// =========================================================================
	// Init
	// =========================================================================
	elementor.on( 'preview:loaded', () => {
		injectInlineStyles();
		buildPopup();

		const tryPanel = () => {
			if ( ! injectPanelBanner() ) {
				setTimeout( injectPanelBanner, 300 );
				setTimeout( injectPanelBanner, 800 );
				setTimeout( injectPanelBanner, 2000 );
			}
		};
		tryPanel();

		setupCanvasInjection();
		fetchSitePalette();
	} );

	// Inject styles immediately when script runs
	injectInlineStyles();
} )();
