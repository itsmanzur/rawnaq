<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared helpers for Rawnaq modules / settings.
 */
function rawnaq_default_modules() {
	return [
		'hub-diagram'         => '1',
		'tilt-card'           => '1',
		'scroll-timeline'     => '1',
		'floating-dock'       => '1',
		'flow-chart'          => '1',
		'scroll-progress-toc' => '1',
		'bento-grid'          => '1',
	];
}

/**
 * Return enabled modules map (slug => '1'|'0').
 * Defaults only when the option has never been saved.
 */
function rawnaq_get_modules() {
	$settings = get_option( 'rawnaq_settings', null );

	// Fresh install / never saved.
	if ( null === $settings || false === $settings || ! is_array( $settings ) || ! isset( $settings['modules'] ) || ! is_array( $settings['modules'] ) ) {
		return rawnaq_default_modules();
	}

	$modules = $settings['modules'];

	// Repair legacy empty save caused by sanitize_text_field() stripping %XX in AJAX form data.
	if ( empty( $modules ) && empty( $settings['modules_repaired'] ) ) {
		$defaults                   = rawnaq_default_modules();
		$settings['modules']        = $defaults;
		$settings['modules_repaired'] = 1;
		update_option( 'rawnaq_settings', $settings );
		return $defaults;
	}

	$out = [];
	foreach ( rawnaq_default_modules() as $slug => $default ) {
		if ( array_key_exists( $slug, $modules ) ) {
			$out[ $slug ] = ! empty( $modules[ $slug ] ) ? '1' : '0';
		} else {
			// Newly introduced modules inherit their default (usually on).
			$out[ $slug ] = $default;
		}
	}
	return $out;
}

/**
 * Whether a module is enabled.
 */
function rawnaq_is_module_enabled( $slug ) {
	$modules = rawnaq_get_modules();
	return isset( $modules[ $slug ] ) && $modules[ $slug ] === '1';
}

/**
 * Page/product context for WhatsApp message placeholders.
 *
 * Supported tokens (resolved in JS at click time, with this as server fallback):
 * {pageTitle}, {title}, {url}, {currentURL}, {siteTitle}, {siteName},
 * {date}, {time}, {productName}, {price}, {sku}, {productUrl}, {productId}
 *
 * @return array<string, string>
 */
function rawnaq_get_wa_page_context() {
	$url = '';
	if ( is_singular() ) {
		$url = (string) get_permalink();
	}
	if ( ! $url ) {
		global $wp;
		$path = isset( $wp->request ) ? (string) $wp->request : '';
		$url  = home_url( '/' . ltrim( $path, '/' ) );
	}

	$ctx = [
		'pageTitle'   => wp_get_document_title(),
		'url'         => $url ? $url : home_url( '/' ),
		'siteTitle'   => get_bloginfo( 'name' ),
		'productName' => '',
		'price'       => '',
		'sku'         => '',
		'productUrl'  => '',
		'productId'   => '',
	];

	if ( function_exists( 'is_product' ) && is_product() && function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( get_the_ID() );
		if ( $product ) {
			$product_url          = (string) get_permalink( $product->get_id() );
			$ctx['productName']   = $product->get_name();
			$ctx['sku']           = (string) $product->get_sku();
			$ctx['productId']     = (string) $product->get_id();
			$ctx['productUrl']    = $product_url;
			$ctx['pageTitle']     = $product->get_name();
			$ctx['url']           = $product_url;

			if ( function_exists( 'wc_get_price_to_display' ) && function_exists( 'wc_price' ) ) {
				$ctx['price'] = html_entity_decode(
					wp_strip_all_tags( wc_price( wc_get_price_to_display( $product ) ) ),
					ENT_QUOTES,
					'UTF-8'
				);
			} else {
				$ctx['price'] = (string) $product->get_price();
			}
		}
	}

	/**
	 * Filter WhatsApp page context used for dynamic prefilled messages.
	 *
	 * @param array<string, string> $ctx Context map.
	 */
	return apply_filters( 'rawnaq_wa_page_context', $ctx );
}

/**
 * Parse comma/space separated IDs into a unique int list.
 *
 * @param mixed $raw IDs as array or string.
 * @return int[]
 */
function rawnaq_parse_id_list( $raw ) {
	if ( is_array( $raw ) ) {
		$parts = $raw;
	} else {
		$parts = preg_split( '/[\s,]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY );
	}
	$ids = [];
	foreach ( (array) $parts as $part ) {
		$id = absint( $part );
		if ( $id > 0 ) {
			$ids[ $id ] = $id;
		}
	}
	return array_values( $ids );
}

/**
 * Whether the floating dock should render on the current request.
 *
 * @param array $args {
 *     @type string $mode            all|include|exclude
 *     @type array  $ids             Post IDs
 *     @type bool   $include_front   Match front page in include mode
 *     @type bool   $include_products Match Woo products in include mode
 * }
 * @return bool
 */
function rawnaq_dock_is_visible( $args ) {
	$mode = isset( $args['mode'] ) ? sanitize_key( $args['mode'] ) : 'all';
	if ( ! in_array( $mode, [ 'all', 'include', 'exclude' ], true ) ) {
		$mode = 'all';
	}

	// Builders / preview: always show so editors can configure.
	if ( is_admin() ) {
		return true;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return true;
	}
	if ( class_exists( '\Elementor\Plugin' ) ) {
		$el = \Elementor\Plugin::$instance;
		if ( ! empty( $el->editor ) && method_exists( $el->editor, 'is_edit_mode' ) && $el->editor->is_edit_mode() ) {
			return true;
		}
		if ( ! empty( $el->preview ) && method_exists( $el->preview, 'is_preview_mode' ) && $el->preview->is_preview_mode() ) {
			return true;
		}
	}

	if ( 'all' === $mode ) {
		return true;
	}

	$ids              = rawnaq_parse_id_list( $args['ids'] ?? [] );
	$include_front    = ! empty( $args['include_front'] );
	$include_products = ! empty( $args['include_products'] );
	$current_id       = (int) get_queried_object_id();
	$matched_id       = $current_id > 0 && in_array( $current_id, $ids, true );
	$matched_front    = $include_front && is_front_page();
	$matched_product  = $include_products && function_exists( 'is_product' ) && is_product();
	$matched          = $matched_id || $matched_front || $matched_product;

	if ( 'include' === $mode ) {
		return $matched;
	}

	// exclude
	return ! $matched;
}

/**
 * Default click counter shape.
 *
 * @return array<string, int|string>
 */
function rawnaq_dock_click_defaults() {
	return [
		'fab'       => 0,
		'agent'     => 0,
		'web'       => 0,
		'chooser'   => 0,
		'secondary' => 0,
		'classic'   => 0,
		'total'     => 0,
		'updated'   => 0,
	];
}

/**
 * @return array<string, int|string>
 */
function rawnaq_dock_get_clicks() {
	$stored = get_option( 'rawnaq_dock_clicks', [] );
	if ( ! is_array( $stored ) ) {
		$stored = [];
	}
	return array_merge( rawnaq_dock_click_defaults(), $stored );
}

/**
 * Increment a dock click counter.
 *
 * @param string $type Counter key.
 * @return bool
 */
function rawnaq_dock_track_click( $type ) {
	$type = sanitize_key( $type );
	$allowed = [ 'fab', 'agent', 'web', 'chooser', 'secondary', 'classic' ];
	if ( ! in_array( $type, $allowed, true ) ) {
		return false;
	}

	$data           = rawnaq_dock_get_clicks();
	$data[ $type ]  = absint( $data[ $type ] ) + 1;
	$data['total']  = absint( $data['total'] ) + 1;
	$data['updated'] = time();
	update_option( 'rawnaq_dock_clicks', $data, false );
	return true;
}

/**
 * Bento Grid layout presets (shared Elementor + Gutenberg).
 *
 * @return array<string, array{columns:int,cells:array<int,array<string,mixed>>}>
 */
function rawnaq_bento_presets() {
	$presets = [
		'featured' => [
			'columns' => 4,
			'cells'   => [
				[
					'type'       => 'featured',
					'col'        => 2,
					'row'        => 2,
					'tag'        => 'Highlight',
					'title'      => 'Zero-jQuery performance',
					'subtitle'   => 'Per-page assets, clean output',
					'icon_fa'    => 'fas fa-star',
					'icon_dash'  => 'dashicons-star-filled',
					'stat'       => '',
					'suffix'     => '',
					'prefix'     => '',
				],
				[
					'type'       => 'image',
					'col'        => 2,
					'row'        => 1,
					'tag'        => 'Showcase',
					'title'      => 'Project gallery',
					'subtitle'   => 'Client work highlights',
					'icon_fa'    => '',
					'icon_dash'  => '',
					'stat'       => '',
					'suffix'     => '',
					'prefix'     => '',
				],
				[
					'type'       => 'stat',
					'col'        => 1,
					'row'        => 1,
					'tag'        => '',
					'title'      => '',
					'subtitle'   => 'Active installs',
					'icon_fa'    => '',
					'icon_dash'  => '',
					'stat'       => '42',
					'suffix'     => '+',
					'prefix'     => '',
				],
				[
					'type'       => 'text',
					'col'        => 1,
					'row'        => 1,
					'tag'        => '',
					'title'      => 'Fast setup',
					'subtitle'   => 'Ready in minutes',
					'icon_fa'    => 'fas fa-bolt',
					'icon_dash'  => 'dashicons-performance',
					'stat'       => '',
					'suffix'     => '',
					'prefix'     => '',
				],
			],
		],
		'equal'    => [
			'columns' => 4,
			'cells'   => [
				[
					'type' => 'text', 'col' => 1, 'row' => 1, 'tag' => '',
					'title' => 'Hub Diagram', 'subtitle' => 'Interactive nodes',
					'icon_fa' => 'fas fa-project-diagram', 'icon_dash' => 'dashicons-networking',
					'stat' => '', 'suffix' => '', 'prefix' => '',
				],
				[
					'type' => 'stat', 'col' => 1, 'row' => 1, 'tag' => '',
					'title' => '', 'subtitle' => 'Modules',
					'icon_fa' => '', 'icon_dash' => '',
					'stat' => '7', 'suffix' => '', 'prefix' => '',
				],
				[
					'type' => 'image', 'col' => 1, 'row' => 1, 'tag' => 'New',
					'title' => 'Flow Chart', 'subtitle' => 'Org + Process',
					'icon_fa' => '', 'icon_dash' => '',
					'stat' => '', 'suffix' => '', 'prefix' => '',
				],
				[
					'type' => 'featured', 'col' => 1, 'row' => 1, 'tag' => 'Core',
					'title' => 'RTL ready', 'subtitle' => 'Bangla / Arabic',
					'icon_fa' => 'fas fa-globe', 'icon_dash' => 'dashicons-translation',
					'stat' => '', 'suffix' => '', 'prefix' => '',
				],
			],
		],
		'wide'     => [
			'columns' => 3,
			'cells'   => [
				[
					'type' => 'image', 'col' => 2, 'row' => 2, 'tag' => 'Featured',
					'title' => 'WhatsApp Dock', 'subtitle' => 'Business-hours aware',
					'icon_fa' => '', 'icon_dash' => '',
					'stat' => '', 'suffix' => '', 'prefix' => '',
				],
				[
					'type' => 'text', 'col' => 1, 'row' => 1, 'tag' => '',
					'title' => 'Scroll Progress', 'subtitle' => 'Auto TOC',
					'icon_fa' => 'fas fa-stream', 'icon_dash' => 'dashicons-editor-ul',
					'stat' => '', 'suffix' => '', 'prefix' => '',
				],
				[
					'type' => 'stat', 'col' => 1, 'row' => 1, 'tag' => '',
					'title' => '', 'subtitle' => 'jQuery deps',
					'icon_fa' => '', 'icon_dash' => '',
					'stat' => '0', 'suffix' => '', 'prefix' => '',
				],
				[
					'type' => 'text', 'col' => 1, 'row' => 1, 'tag' => '',
					'title' => 'Tilt Card', 'subtitle' => 'Motion effect',
					'icon_fa' => 'fas fa-cube', 'icon_dash' => 'dashicons-image-flip-horizontal',
					'stat' => '', 'suffix' => '', 'prefix' => '',
				],
				[
					'type' => 'featured', 'col' => 1, 'row' => 1, 'tag' => 'Soon',
					'title' => 'Bento Grid', 'subtitle' => 'You are looking at it',
					'icon_fa' => 'fas fa-th-large', 'icon_dash' => 'dashicons-grid-view',
					'stat' => '', 'suffix' => '', 'prefix' => '',
				],
			],
		],
	];

	/**
	 * Filter Bento Grid presets.
	 *
	 * @param array $presets Preset map.
	 */
	return apply_filters( 'rawnaq_bento_presets', $presets );
}

/**
 * Map a preset key to Elementor repeater rows.
 *
 * @param string $preset Preset key.
 * @return array{columns:int,cells:array<int,array<string,mixed>>}|null
 */
function rawnaq_bento_preset_for_elementor( $preset ) {
	$all = rawnaq_bento_presets();
	$key = sanitize_key( $preset );
	if ( empty( $all[ $key ] ) ) {
		return null;
	}

	$rows = [];
	foreach ( $all[ $key ]['cells'] as $cell ) {
		$type = sanitize_key( $cell['type'] ?? 'text' );
		$row  = [
			'cell_type'     => $type,
			'col_span'      => max( 1, absint( $cell['col'] ?? 1 ) ),
			'row_span'      => max( 1, absint( $cell['row'] ?? 1 ) ),
			'content_align' => '',
			'order_desktop' => 0,
			'col_span_tablet'  => 0,
			'row_span_tablet'  => 0,
			'order_tablet'     => 0,
			'col_span_mobile'  => 0,
			'row_span_mobile'  => 0,
			'order_mobile'     => 0,
			'tag'           => (string) ( $cell['tag'] ?? '' ),
			'cell_tag_bg'   => '',
			'cell_tag_color'=> '',
			'title'         => (string) ( $cell['title'] ?? '' ),
			'subtitle'      => (string) ( $cell['subtitle'] ?? '' ),
			'stat_value'    => (string) ( $cell['stat'] ?? '' ),
			'stat_suffix'   => (string) ( $cell['suffix'] ?? '' ),
			'stat_prefix'   => (string) ( $cell['prefix'] ?? '' ),
			'bg_color'      => '',
			'image'         => [ 'url' => '', 'id' => '' ],
			'video_url'     => [ 'url' => '', 'is_external' => '' ],
			'link'          => [ 'url' => '', 'is_external' => '', 'nofollow' => '' ],
			'cta_text'      => '',
			'cta_link'      => [ 'url' => '', 'is_external' => '', 'nofollow' => '' ],
			'testimonial_role'   => '',
			'testimonial_avatar' => [ 'url' => '', 'id' => '' ],
			'testimonial_rating' => 0,
			'selected_icon' => [
				'value'   => (string) ( $cell['icon_fa'] ?? 'fas fa-bolt' ),
				'library' => 'fa-solid',
			],
		];
		$rows[] = $row;
	}

	return [
		'columns' => absint( $all[ $key ]['columns'] ),
		'cells'   => $rows,
	];
}

/**
 * Map a preset key to Gutenberg cell objects.
 *
 * @param string $preset Preset key.
 * @return array{columns:int,cells:array<int,array<string,mixed>>}|null
 */
function rawnaq_bento_preset_for_gutenberg( $preset ) {
	$all = rawnaq_bento_presets();
	$key = sanitize_key( $preset );
	if ( empty( $all[ $key ] ) ) {
		return null;
	}

	$cells = [];
	foreach ( $all[ $key ]['cells'] as $cell ) {
		$cells[] = [
			'type'     => sanitize_key( $cell['type'] ?? 'text' ),
			'col'      => max( 1, absint( $cell['col'] ?? 1 ) ),
			'row'      => max( 1, absint( $cell['row'] ?? 1 ) ),
			'align'    => '',
			'order'    => 0,
			'colMd'    => 0,
			'rowMd'    => 0,
			'orderMd'  => 0,
			'colSm'    => 0,
			'rowSm'    => 0,
			'orderSm'  => 0,
			'tag'      => (string) ( $cell['tag'] ?? '' ),
			'tagBg'    => '',
			'tagColor' => '',
			'title'    => (string) ( $cell['title'] ?? '' ),
			'subtitle' => (string) ( $cell['subtitle'] ?? '' ),
			'icon'     => (string) ( $cell['icon_dash'] ?? '' ),
			'image'    => '',
			'video'    => '',
			'stat'     => (string) ( $cell['stat'] ?? '' ),
			'suffix'   => (string) ( $cell['suffix'] ?? '' ),
			'prefix'   => (string) ( $cell['prefix'] ?? '' ),
			'link'     => '',
			'ctaText'  => '',
			'ctaLink'  => '',
			'role'     => '',
			'avatar'   => '',
			'rating'   => 0,
		];
	}

	return [
		'columns' => absint( $all[ $key ]['columns'] ),
		'cells'   => $cells,
	];
}

/**
 * Build inline layout CSS for a bento cell (desktop + optional tablet/mobile overrides).
 *
 * Override values of 0 mean "inherit / auto" (var not emitted).
 * On mobile, unset col span keeps full-bleed stacking; set col span enables custom width.
 *
 * @param array $layout {
 *     @type int $col       Desktop column span.
 *     @type int $row       Desktop row span.
 *     @type int $order     Desktop order (0 = natural).
 *     @type int $col_md    Tablet column span (0 = inherit desktop).
 *     @type int $row_md    Tablet row span (0 = inherit).
 *     @type int $order_md  Tablet order (0 = inherit).
 *     @type int $col_sm    Mobile column span (0 = full width).
 *     @type int $row_sm    Mobile row span (0 = auto/1).
 *     @type int $order_sm  Mobile order (0 = inherit).
 * }
 * @return array{style:string,classes:string[]}
 */
function rawnaq_bento_cell_layout( array $layout ) {
	$col = max( 1, absint( $layout['col'] ?? 1 ) );
	$row = max( 1, absint( $layout['row'] ?? 1 ) );

	$parts = [
		sprintf( 'grid-column:span %d', $col ),
		sprintf( 'grid-row:span %d', $row ),
		sprintf( '--bento-span-col:%d', $col ),
		sprintf( '--bento-span-row:%d', $row ),
	];

	$order = isset( $layout['order'] ) ? intval( $layout['order'] ) : 0;
	if ( $order !== 0 ) {
		$parts[] = sprintf( 'order:%d', $order );
		$parts[] = sprintf( '--bento-order:%d', $order );
	}

	$col_md = absint( $layout['col_md'] ?? 0 );
	$row_md = absint( $layout['row_md'] ?? 0 );
	$order_md = isset( $layout['order_md'] ) ? intval( $layout['order_md'] ) : 0;
	$col_sm = absint( $layout['col_sm'] ?? 0 );
	$row_sm = absint( $layout['row_sm'] ?? 0 );
	$order_sm = isset( $layout['order_sm'] ) ? intval( $layout['order_sm'] ) : 0;

	if ( $col_md > 0 ) {
		$parts[] = sprintf( '--bento-span-col-md:%d', $col_md );
	}
	if ( $row_md > 0 ) {
		$parts[] = sprintf( '--bento-span-row-md:%d', $row_md );
	}
	if ( $order_md !== 0 ) {
		$parts[] = sprintf( '--bento-order-md:%d', $order_md );
	}
	if ( $col_sm > 0 ) {
		$parts[] = sprintf( '--bento-span-col-sm:%d', $col_sm );
	}
	if ( $row_sm > 0 ) {
		$parts[] = sprintf( '--bento-span-row-sm:%d', $row_sm );
	}
	if ( $order_sm !== 0 ) {
		$parts[] = sprintf( '--bento-order-sm:%d', $order_sm );
	}

	$classes = [];
	if ( $col_sm > 0 ) {
		$classes[] = 'has-sm-span';
	}

	return [
		'style'   => implode( ';', $parts ) . ';',
		'classes' => $classes,
	];
}

/**
 * Build class/style for a bento tag with optional per-cell color overrides.
 *
 * @param string $bg    Background hex (empty = inherit global / featured default).
 * @param string $color Text hex (empty = inherit).
 * @return array{class:string,style:string}
 */
function rawnaq_bento_tag_attrs( $bg = '', $color = '' ) {
	$bg    = is_string( $bg ) ? sanitize_hex_color( $bg ) : '';
	$color = is_string( $color ) ? sanitize_hex_color( $color ) : '';

	$class = 'rawnaq-bento-tag';
	$parts = [];

	if ( $bg || $color ) {
		$class .= ' has-custom';
	}
	if ( $bg ) {
		$parts[] = '--bento-tag-cell-bg:' . $bg;
		$parts[] = 'background:' . $bg;
	}
	if ( $color ) {
		$parts[] = '--bento-tag-cell-color:' . $color;
		$parts[] = 'color:' . $color;
	}

	return [
		'class' => $class,
		'style' => $parts ? implode( ';', $parts ) . ';' : '',
	];
}

/**
 * CSS class for per-cell vertical content alignment.
 *
 * @param string $align top|center|bottom|empty (type default).
 * @return string Class name or empty string.
 */
function rawnaq_bento_align_class( $align ) {
	$align = sanitize_key( (string) $align );
	if ( in_array( $align, [ 'top', 'center', 'bottom' ], true ) ) {
		return 'is-align-' . $align;
	}
	return '';
}

/**
 * Parse a bento video URL into file or YouTube/Vimeo embed data.
 *
 * @param string $url Raw video URL.
 * @return array{kind:string,src:string,embed:string}|null
 */
function rawnaq_bento_parse_video( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return null;
	}

	// YouTube: watch, youtu.be, embed, shorts.
	if ( preg_match( '#(?:youtube\.com/(?:watch\?(?:[^#]*&)?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,})#i', $url, $m ) ) {
		$id = sanitize_text_field( $m[1] );
		return [
			'kind'  => 'youtube',
			'src'   => $url,
			'embed' => 'https://www.youtube-nocookie.com/embed/' . rawurlencode( $id ) . '?rel=0&modestbranding=1',
		];
	}

	// Vimeo: vimeo.com/ID or player.vimeo.com/video/ID.
	if ( preg_match( '#(?:player\.)?vimeo\.com/(?:video/)?(\d+)#i', $url, $m ) ) {
		$id = absint( $m[1] );
		if ( $id > 0 ) {
			return [
				'kind'  => 'vimeo',
				'src'   => $url,
				'embed' => 'https://player.vimeo.com/video/' . $id . '?title=0&byline=0&portrait=0',
			];
		}
	}

	return [
		'kind'  => 'file',
		'src'   => esc_url_raw( $url ),
		'embed' => '',
	];
}

/**
 * HTML markup for a bento video/media layer (file, YouTube, or Vimeo).
 *
 * @param string $url Video URL.
 * @return string Safe HTML.
 */
function rawnaq_bento_video_markup( $url ) {
	$parsed = rawnaq_bento_parse_video( $url );
	if ( ! $parsed ) {
		return '<div class="rawnaq-bento-media" style="background:#1e1b2e;" aria-hidden="true"></div>';
	}

	if ( in_array( $parsed['kind'], [ 'youtube', 'vimeo' ], true ) && ! empty( $parsed['embed'] ) ) {
		$title = 'youtube' === $parsed['kind']
			? __( 'YouTube video', 'rawnaq' )
			: __( 'Vimeo video', 'rawnaq' );

		return sprintf(
			'<iframe class="rawnaq-bento-embed" src="%1$s" title="%2$s" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>',
			esc_url( $parsed['embed'] ),
			esc_attr( $title )
		);
	}

	if ( empty( $parsed['src'] ) ) {
		return '<div class="rawnaq-bento-media" style="background:#1e1b2e;" aria-hidden="true"></div>';
	}

	return sprintf(
		'<video class="rawnaq-bento-video" muted playsinline loop preload="metadata" src="%s"></video>',
		esc_url( $parsed['src'] )
	);
}

/**
 * Sanitize a CSS scroll-timeline custom-ident (without leading --).
 *
 * @param string $name     Proposed name.
 * @param string $fallback Fallback when empty/invalid.
 * @return string
 */
function rawnaq_timeline_sanitize_tl_name( $name, $fallback = 'rawnaq-tl' ) {
	$name = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $name );
	if ( '' === $name ) {
		$name = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $fallback );
	}
	if ( '' === $name ) {
		$name = 'rawnaq-tl';
	}
	if ( preg_match( '/^[0-9]/', $name ) ) {
		$name = 'tl-' . $name;
	}
	return $name;
}

/**
 * Default UI strings registered for WPML / Polylang.
 *
 * @return array<string, string> name => default English string
 */
function rawnaq_i18n_strings() {
	return [
		'load_more'     => 'Load more',
		'read_more'     => 'Read more',
		'engine_badge'  => 'Native CSS scroll animations — no motion JS in this browser.',
		'query_mode'    => 'Query mode',
		'posts_frontend'=> 'Posts load on the frontend',
		'posts_preview' => 'Save and preview the page to see live CPT / post results.',
		'named_timeline'=> 'Named timeline:',
	];
}

/**
 * Register plugin strings with WPML String Translation and Polylang.
 */
function rawnaq_register_i18n_strings() {
	$strings = rawnaq_i18n_strings();
	foreach ( $strings as $name => $value ) {
		if ( function_exists( 'icl_register_string' ) ) {
			icl_register_string( 'rawnaq', $name, $value );
		} elseif ( has_action( 'wpml_register_single_string' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API hook.
			do_action( 'wpml_register_single_string', 'rawnaq', $name, $value );
		}
		if ( function_exists( 'pll_register_string' ) ) {
			pll_register_string( $name, $value, 'Rawnaq', false );
		}
	}
}
add_action( 'init', 'rawnaq_register_i18n_strings', 20 );

/**
 * Translate a registered Rawnaq UI string (WPML / Polylang aware).
 *
 * @param string $name    String name from rawnaq_i18n_strings().
 * @param string $default Fallback (usually __() result).
 * @return string
 */
function rawnaq_translate( $name, $default = '' ) {
	$catalog = rawnaq_i18n_strings();
	if ( '' === $default && isset( $catalog[ $name ] ) ) {
		$default = $catalog[ $name ];
	}

	if ( function_exists( 'icl_t' ) ) {
		return (string) icl_t( 'rawnaq', $name, $default );
	}
	if ( has_filter( 'wpml_translate_single_string' ) ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API filter.
		return (string) apply_filters( 'wpml_translate_single_string', $default, 'rawnaq', $name );
	}
	if ( function_exists( 'pll__' ) ) {
		return (string) pll__( $default );
	}

	return (string) $default;
}

/**
 * Resolve a post ID through WPML when available.
 *
 * @param int    $id        Post ID.
 * @param string $post_type Post type.
 * @return int
 */
function rawnaq_timeline_resolve_post_id( $id, $post_type = 'post' ) {
	$id = absint( $id );
	if ( $id <= 0 ) {
		return 0;
	}
	if ( has_filter( 'wpml_object_id' ) ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML API filter.
		$translated = apply_filters( 'wpml_object_id', $id, $post_type, true );
		if ( $translated ) {
			$id = absint( $translated );
		}
	}
	return $id;
}

/**
 * Filter timeline steps (manual or query) for WPML/tools.
 *
 * @param array $steps   Step arrays.
 * @param array $context Context (source, widget_id, …).
 * @return array
 */
function rawnaq_timeline_filter_steps( $steps, $context = [] ) {
	$steps = is_array( $steps ) ? $steps : [];
	/**
	 * Filter Scroll Timeline step arrays before render.
	 *
	 * @param array $steps   Steps.
	 * @param array $context Context.
	 */
	return apply_filters( 'rawnaq_timeline_steps', $steps, $context );
}

/**
 * Sanitize timeline query args from widget / AJAX.
 *
 * @param array $args Raw args.
 * @return array
 */
function rawnaq_timeline_sanitize_query_args( $args ) {
	$args = is_array( $args ) ? $args : [];
	$post_type = sanitize_key( $args['post_type'] ?? 'post' );
	if ( '' === $post_type ) {
		$post_type = 'post';
	}

	$orderby = sanitize_key( $args['orderby'] ?? 'date' );
	$allowed_orderby = [ 'date', 'title', 'menu_order', 'modified', 'rand', 'ID' ];
	if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
		$orderby = 'date';
	}

	$order = strtoupper( (string) ( $args['order'] ?? 'DESC' ) );
	if ( ! in_array( $order, [ 'ASC', 'DESC' ], true ) ) {
		$order = 'DESC';
	}

	$max = isset( $args['max'] ) ? (int) $args['max'] : ( isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : 6 );
	if ( $max < 1 ) {
		$max = 6;
	}
	if ( $max > 50 ) {
		$max = 50;
	}

	$per_page = isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : $max;
	if ( $per_page < 1 ) {
		$per_page = $max;
	}
	if ( $per_page > 50 ) {
		$per_page = 50;
	}

	$offset = isset( $args['offset'] ) ? max( 0, (int) $args['offset'] ) : 0;

	return [
		'post_type'      => $post_type,
		'posts_per_page' => $per_page,
		'offset'         => $offset,
		'max'            => $max,
		'orderby'        => $orderby,
		'order'          => $order,
		'taxonomy'       => sanitize_key( $args['taxonomy'] ?? '' ),
		'terms'          => is_array( $args['terms'] ?? null ) ? $args['terms'] : (string) ( $args['terms'] ?? '' ),
		'include'        => $args['include'] ?? ( $args['include_ids'] ?? '' ),
		'exclude_ids'    => $args['exclude_ids'] ?? ( $args['exclude'] ?? '' ),
	];
}

/**
 * Build timeline steps from a WP_Query (posts / CPT) with optional offset.
 *
 * @param array $args    Query args (see rawnaq_timeline_sanitize_query_args).
 * @param array $context Extra context passed to rawnaq_timeline_steps.
 * @return array{steps: array, found_posts: int, offset: int, posts_per_page: int}
 */
function rawnaq_timeline_query_result( $args = [], $context = [] ) {
	$args = rawnaq_timeline_sanitize_query_args( $args );
	$post_type = $args['post_type'];
	$per_page  = $args['posts_per_page'];
	$offset    = $args['offset'];
	$orderby   = $args['orderby'];
	$order     = $args['order'];

	$include = rawnaq_parse_id_list( $args['include'] );
	$exclude = rawnaq_parse_id_list( $args['exclude_ids'] );

	$include = array_values( array_filter( array_map(
		static function ( $id ) use ( $post_type ) {
			return rawnaq_timeline_resolve_post_id( $id, $post_type );
		},
		$include
	) ) );
	$exclude = array_values( array_filter( array_map(
		static function ( $id ) use ( $post_type ) {
			return rawnaq_timeline_resolve_post_id( $id, $post_type );
		},
		$exclude
	) ) );

	$query_args = [
		'post_type'           => $post_type,
		'post_status'         => 'publish',
		'posts_per_page'      => $per_page,
		'offset'              => $offset,
		'orderby'             => $orderby,
		'order'               => $order,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => false,
	];

	if ( $include ) {
		$query_args['post__in'] = $include;
		$query_args['orderby']  = 'post__in';
	}
	if ( $exclude ) {
		// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- intentional optional exclude for timeline query UI.
		$query_args['post__not_in'] = $exclude;
	}

	$taxonomy  = $args['taxonomy'];
	$terms_raw = $args['terms'];
	if ( is_array( $terms_raw ) ) {
		$term_slugs = array_values( array_filter( array_map( 'sanitize_title', $terms_raw ) ) );
	} else {
		$term_slugs = array_values( array_filter( array_map(
			'sanitize_title',
			preg_split( '/[\s,]+/', (string) $terms_raw, -1, PREG_SPLIT_NO_EMPTY ) ?: []
		) ) );
	}

	if ( $taxonomy && $term_slugs && taxonomy_exists( $taxonomy ) ) {
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- optional taxonomy filter for timeline CPT queries.
		$query_args['tax_query'] = [
			[
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $term_slugs,
			],
		];
	}

	$q     = new WP_Query( $query_args );
	$steps = [];
	$cta_label = function_exists( 'rawnaq_translate' )
		? rawnaq_translate( 'read_more', __( 'Read more', 'rawnaq' ) )
		: __( 'Read more', 'rawnaq' );

	if ( $q->have_posts() ) {
		while ( $q->have_posts() ) {
			$q->the_post();
			$post_id = get_the_ID();
			$thumb   = get_the_post_thumbnail_url( $post_id, 'medium_large' );
			$video   = (string) get_post_meta( $post_id, 'rawnaq_timeline_video', true );
			$excerpt = get_the_excerpt( $post_id );
			if ( '' === trim( (string) $excerpt ) ) {
				$excerpt = wp_trim_words( wp_strip_all_tags( get_the_content( null, false, $post_id ) ), 24 );
			}

			$steps[] = [
				'meta'      => get_the_date( '', $post_id ),
				'title'     => get_the_title( $post_id ),
				'desc'      => $excerpt,
				'image'     => [
					'url' => $thumb ? $thumb : '',
					'alt' => get_the_title( $post_id ),
				],
				'imageUrl'  => $thumb ? $thumb : '',
				'imageId'   => (int) get_post_thumbnail_id( $post_id ),
				'video'     => $video,
				'video_url' => [ 'url' => $video ],
				'cta_text'  => $cta_label,
				'ctaText'   => $cta_label,
				'cta_link'  => [
					'url'         => get_permalink( $post_id ),
					'is_external' => false,
					'nofollow'    => false,
				],
				'ctaLink'   => get_permalink( $post_id ),
				'post_id'   => $post_id,
			];
		}
		wp_reset_postdata();
	}

	$context = array_merge(
		[
			'source'    => 'query',
			'post_type' => $post_type,
			'offset'    => $offset,
		],
		is_array( $context ) ? $context : []
	);

	$steps = rawnaq_timeline_filter_steps( $steps, $context );
	$found = (int) $q->found_posts;
	$max   = (int) $args['max'];
	if ( $max > 0 && $found > $max ) {
		$found = $max;
	}

	return [
		'steps'          => $steps,
		'found_posts'    => $found,
		'offset'         => $offset,
		'posts_per_page' => $per_page,
	];
}

/**
 * Build timeline steps from a WP_Query (posts / CPT).
 *
 * @param array $args    Query args.
 * @param array $context Extra context.
 * @return array Step arrays.
 */
function rawnaq_timeline_query_steps( $args = [], $context = [] ) {
	$result = rawnaq_timeline_query_result( $args, $context );
	return $result['steps'];
}

/**
 * Side class for a timeline item index + layout.
 *
 * @param int    $index  Zero-based index.
 * @param string $layout Layout mode.
 * @return string
 */
function rawnaq_timeline_side_class( $index, $layout ) {
	if ( 'horizontal' === $layout ) {
		return 'h-item';
	}
	if ( 'left' === $layout ) {
		return 'left-item';
	}
	if ( 'right' === $layout ) {
		return 'right-item';
	}
	return ( 0 === ( (int) $index % 2 ) ) ? 'left-item' : 'right-item';
}

/**
 * Render timeline item HTML fragments (for SSR + AJAX).
 *
 * @param array  $steps        Steps.
 * @param string $layout       Layout mode.
 * @param bool   $show_numbers Show numbers.
 * @param int    $start_index  Numbering offset (0-based index of first step).
 * @return string
 */
function rawnaq_timeline_render_items_html( $steps, $layout = 'alternating', $show_numbers = true, $start_index = 0 ) {
	$steps  = is_array( $steps ) ? $steps : [];
	$layout = sanitize_html_class( $layout );
	if ( ! in_array( $layout, [ 'alternating', 'left', 'right', 'horizontal' ], true ) ) {
		$layout = 'alternating';
	}
	$start_index = max( 0, (int) $start_index );
	ob_start();
	foreach ( $steps as $i => $step ) {
		$index = $start_index + (int) $i;
		$side  = rawnaq_timeline_side_class( $index, $layout );
		$num   = str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT );
		$meta  = $step['meta'] ?? '';
		$title = $step['title'] ?? '';
		$desc  = $step['desc'] ?? '';
		$icon  = $step['icon'] ?? '';
		$img   = $step['imageUrl'] ?? ( $step['image']['url'] ?? '' );
		$video = trim( (string) ( $step['video'] ?? ( $step['video_url']['url'] ?? '' ) ) );
		$cta_text = trim( (string) ( $step['ctaText'] ?? $step['cta_text'] ?? '' ) );
		$cta_link = $step['ctaLink'] ?? ( $step['cta_link']['url'] ?? '' );
		?>
		<div class="rawnaq-timeline-item <?php echo esc_attr( $side ); ?>">
			<span class="rawnaq-timeline-bullet">
				<?php if ( $show_numbers ) : ?>
					<span class="num"><?php echo esc_html( $num ); ?></span>
				<?php endif; ?>
			</span>
			<div class="rawnaq-timeline-card">
				<?php if ( $video && function_exists( 'rawnaq_bento_video_markup' ) ) : ?>
					<div class="rawnaq-timeline-media">
						<?php echo rawnaq_bento_video_markup( $video ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				<?php elseif ( $img ) : ?>
					<img class="rawnaq-timeline-thumb" src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
				<?php endif; ?>
				<?php if ( $meta ) : ?>
					<span class="rawnaq-timeline-meta"><?php echo esc_html( $meta ); ?></span>
				<?php endif; ?>
				<?php if ( $icon ) : ?>
					<span class="rawnaq-timeline-icon"><span class="dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span></span>
				<?php endif; ?>
				<?php if ( ! empty( $step['selected_icon']['value'] ) && class_exists( '\Elementor\Icons_Manager' ) ) : ?>
					<span class="rawnaq-timeline-icon">
						<?php \Elementor\Icons_Manager::render_icon( $step['selected_icon'], [ 'aria-hidden' => 'true' ] ); ?>
					</span>
				<?php endif; ?>
				<?php if ( $title ) : ?>
					<h4><?php echo esc_html( $title ); ?></h4>
				<?php endif; ?>
				<?php if ( $desc ) : ?>
					<p><?php echo esc_html( $desc ); ?></p>
				<?php endif; ?>
				<?php if ( $cta_text && $cta_link ) : ?>
					<a class="rawnaq-timeline-cta" href="<?php echo esc_url( $cta_link ); ?>"><?php echo esc_html( $cta_text ); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
	return (string) ob_get_clean();
}
