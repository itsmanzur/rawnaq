/**
 * Rawnaq Starter Sections — Elementor editor popup.
 *
 * Adds a "Starter Sections" button to the Elementor add-section panel.
 * When clicked it opens an overlay that lists all bundled templates.
 * Clicking a template card calls the rawnaq_template_kit_import AJAX
 * endpoint and inserts the returned elements into the current page.
 */
( function () {
	'use strict';

	if ( typeof elementor === 'undefined' || typeof rawnaqTemplateKit === 'undefined' ) {
		return;
	}

	const cfg       = rawnaqTemplateKit;
	const templates = cfg.templates || [];

	// -------------------------------------------------------------------------
	// Build the popup DOM (once)
	// -------------------------------------------------------------------------
	function buildPopup() {
		const popup = document.getElementById( 'rawnaq-tk-popup' );
		if ( ! popup ) return;

		const grid    = document.getElementById( 'rawnaq-tk-grid' );
		const filters = document.getElementById( 'rawnaq-tk-filters' );

		// Gather unique categories.
		const cats = [ ...new Set( templates.map( t => t.category ) ) ];

		// Filter buttons.
		const allBtn = createFilterBtn( cfg.i18n.all, 'all', true );
		filters.appendChild( allBtn );
		cats.forEach( cat => {
			filters.appendChild( createFilterBtn( cap( cat ), cat, false ) );
		} );

		// Template cards.
		templates.forEach( tpl => {
			grid.appendChild( createCard( tpl ) );
		} );

		// Close button.
		const closeBtn = popup.querySelector( '.rawnaq-tk-popup__close' );
		closeBtn.addEventListener( 'click', hidePopup );

		// Click outside to close.
		popup.addEventListener( 'click', e => {
			if ( e.target === popup ) hidePopup();
		} );
	}

	function createFilterBtn( label, value, active ) {
		const btn = document.createElement( 'button' );
		btn.type = 'button';
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

	function createCard( tpl ) {
		const card = document.createElement( 'div' );
		card.className        = 'rawnaq-tk-card';
		card.dataset.cat      = tpl.category;
		card.dataset.id       = tpl.id;
		const disabled        = tpl.missing_modules && tpl.missing_modules.length > 0;

		card.innerHTML = `
			<div class="rawnaq-tk-card__thumb">
				${ tpl.thumbnail_url
					? `<img src="${ escAttr( tpl.thumbnail_url ) }" alt="" loading="lazy" />`
					: `<div class="rawnaq-tk-card__thumb-placeholder"></div>` }
				<div class="rawnaq-tk-card__overlay">
					<button type="button" class="rawnaq-tk-card__preview-btn" data-id="${ escAttr( tpl.id ) }">👁️ Live Preview</button>
				</div>
			</div>
			<div class="rawnaq-tk-card__footer">
				<span class="rawnaq-tk-card__title">${ escHtml( tpl.title ) }</span>
				${ disabled
					? `<button type="button" class="rawnaq-tk-card__insert rawnaq-tk-card__insert--auto-enable" data-id="${ escAttr( tpl.id ) }">⚡ Enable & Insert</button>`
					: `<button type="button" class="rawnaq-tk-card__insert" data-id="${ escAttr( tpl.id ) }">${ escHtml( cfg.i18n.insert ) }</button>` }
			</div>`;

		const previewBtn = card.querySelector( '.rawnaq-tk-card__preview-btn' );
		if ( previewBtn ) {
			previewBtn.addEventListener( 'click', (e) => {
				e.stopPropagation();
				openLightbox( tpl );
			} );
		}

		const insertBtn = card.querySelector( '.rawnaq-tk-card__insert' );
		if ( insertBtn ) {
			insertBtn.addEventListener( 'click', () => importTemplate( tpl.id, disabled ) );
		}
		return card;
	}

	function filterCards( cat ) {
		document.querySelectorAll( '.rawnaq-tk-card' ).forEach( card => {
			card.style.display = ( cat === 'all' || card.dataset.cat === cat ) ? '' : 'none';
		} );
	}

	// -------------------------------------------------------------------------
	// Import
	// -------------------------------------------------------------------------
	function openLightbox( tpl ) {
		let lightbox = document.getElementById( 'rawnaq-tk-lightbox' );
		if ( ! lightbox ) {
			lightbox = document.createElement( 'div' );
			lightbox.id = 'rawnaq-tk-lightbox';
			lightbox.className = 'rawnaq-tk-lightbox';
			lightbox.innerHTML = `
				<div class="rawnaq-tk-lightbox__inner">
					<div class="rawnaq-tk-lightbox__header">
						<div class="rawnaq-tk-lightbox__meta">
							<span class="rawnaq-tk-lightbox__title"></span>
							<span class="rawnaq-tk-lightbox__tag"></span>
						</div>
						<div class="rawnaq-tk-lightbox__viewports">
							<button type="button" class="rawnaq-tk-vp-btn active" data-vp="desktop" title="Desktop View">💻 Desktop</button>
							<button type="button" class="rawnaq-tk-vp-btn" data-vp="tablet" title="Tablet View (768px)">📱 Tablet</button>
							<button type="button" class="rawnaq-tk-vp-btn" data-vp="mobile" title="Mobile View (375px)">📱 Mobile</button>
						</div>
						<div class="rawnaq-tk-lightbox__actions">
							<button type="button" class="rawnaq-tk-lightbox__insert-btn">⚡ Insert Section</button>
							<button type="button" class="rawnaq-tk-lightbox__close" title="Close Preview">&#x2715;</button>
						</div>
					</div>
					<div class="rawnaq-tk-lightbox__stage">
						<div class="rawnaq-tk-lightbox__frame-wrap vp-desktop">
							<img class="rawnaq-tk-lightbox__preview-img" src="" alt="Template Preview" />
						</div>
					</div>
				</div>`;
			document.body.appendChild( lightbox );

			const vpBtns = lightbox.querySelectorAll( '.rawnaq-tk-vp-btn' );
			const frameWrap = lightbox.querySelector( '.rawnaq-tk-lightbox__frame-wrap' );
			vpBtns.forEach( b => {
				b.addEventListener( 'click', () => {
					vpBtns.forEach( btn => btn.classList.remove( 'active' ) );
					b.classList.add( 'active' );
					frameWrap.className = 'rawnaq-tk-lightbox__frame-wrap vp-' + b.dataset.vp;
				} );
			} );

			lightbox.querySelector( '.rawnaq-tk-lightbox__close' ).addEventListener( 'click', closeLightbox );
			lightbox.addEventListener( 'click', (e) => {
				if ( e.target === lightbox ) closeLightbox();
			} );
		}

		lightbox.querySelector( '.rawnaq-tk-lightbox__title' ).textContent = tpl.title;
		lightbox.querySelector( '.rawnaq-tk-lightbox__tag' ).textContent = cap( tpl.category );
		lightbox.querySelector( '.rawnaq-tk-lightbox__preview-img' ).src = tpl.thumbnail_url || '';
		
		const insertBtn = lightbox.querySelector( '.rawnaq-tk-lightbox__insert-btn' );
		const disabled = tpl.missing_modules && tpl.missing_modules.length > 0;
		insertBtn.textContent = disabled ? '⚡ Enable & Insert' : 'Insert Section';
		insertBtn.onclick = () => importTemplate( tpl.id, disabled );

		lightbox.classList.add( 'is-open' );
	}

	function closeLightbox() {
		const lightbox = document.getElementById( 'rawnaq-tk-lightbox' );
		if ( lightbox ) lightbox.classList.remove( 'is-open' );
	}

	function importTemplate( id, autoEnable = false ) {
		const btn = document.querySelector( `.rawnaq-tk-card__insert[data-id="${ id }"]` );
		if ( btn ) {
			btn.textContent = cfg.i18n.inserting;
			btn.disabled    = true;
		}

		const body = new FormData();
		body.append( 'action',      'rawnaq_template_kit_import' );
		body.append( 'nonce',       cfg.nonce );
		body.append( 'template_id', id );
		if ( autoEnable ) {
			body.append( 'auto_enable', '1' );
		}

		fetch( cfg.ajaxUrl, { method: 'POST', body } )
			.then( r => r.json() )
			.then( res => {
				if ( ! res.success ) {
					alert( res.data?.message || cfg.i18n.insertError );
					if ( btn ) { btn.textContent = cfg.i18n.insert; btn.disabled = false; }
					return;
				}
				insertElements( res.data.elements );
				closeLightbox();
				hidePopup();
			} )
			.catch( () => {
				alert( cfg.i18n.insertError );
				if ( btn ) { btn.textContent = cfg.i18n.insert; btn.disabled = false; }
			} );
	}

	function insertElements( elements ) {
		if ( ! Array.isArray( elements ) || ! elements.length ) return;
		const model  = elementor.getPreviewView().getOption( 'model' );
		const eImport = elementor.channels.data;
		elements.forEach( el => {
			eImport.trigger( 'element:before:add' );
			const newModel = model.get( 'elements' ).add( el );
			eImport.trigger( 'element:after:add', { model: newModel } );
		} );
		elementor.channels.editor.trigger( 'document:change' );
	}

	// -------------------------------------------------------------------------
	// Show / Hide
	// -------------------------------------------------------------------------
	function showPopup() {
		const popup = document.getElementById( 'rawnaq-tk-popup' );
		if ( popup ) { popup.style.display = ''; popup.removeAttribute( 'hidden' ); }
	}

	function hidePopup() {
		const popup = document.getElementById( 'rawnaq-tk-popup' );
		if ( popup ) popup.style.display = 'none';
	}

	// -------------------------------------------------------------------------
	// Inject the trigger button into the Elementor "Add Section" panel
	// -------------------------------------------------------------------------
	function injectTriggerButton() {
		const panel = document.getElementById( 'elementor-add-section' );
		if ( ! panel || document.getElementById( 'rawnaq-tk-trigger' ) ) return;

		const btn = document.createElement( 'button' );
		btn.id          = 'rawnaq-tk-trigger';
		btn.type        = 'button';
		btn.className   = 'rawnaq-tk-trigger elementor-add-section-button';
		btn.innerHTML   = `<i class="eicon-columns"></i> ${ escHtml( cfg.i18n.title ) }`;
		btn.addEventListener( 'click', showPopup );
		panel.appendChild( btn );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------
	function escHtml( str ) {
		const d = document.createElement( 'div' );
		d.textContent = str;
		return d.innerHTML;
	}
	function escAttr( str ) {
		return String( str ).replace( /"/g, '&quot;' );
	}
	function cap( str ) {
		return str.charAt( 0 ).toUpperCase() + str.slice( 1 ).replace( '-', ' ' );
	}

	// -------------------------------------------------------------------------
	// Init
	// -------------------------------------------------------------------------
	elementor.on( 'preview:loaded', () => {
		buildPopup();
		injectTriggerButton();
	} );
} )();
