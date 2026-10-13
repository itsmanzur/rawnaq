<?php
/**
 * Rawnaq Scroll Progress + Smart TOC
 * Single source of truth for config sanitization, SSR markup, and heading anchors.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Server-Side Heading Registry for SEO and CLS
 */
class Rawnaq_SPT_SSR {
	/**
	 * Extracted headings for current post.
	 *
	 * @var array<int, array{id: string, text: string, level: int}>
	 */
	public static $headings = [];

	/**
	 * Injected content cache per post ID.
	 *
	 * @var array<int, string>
	 */
	public static $injected = [];

	/**
	 * Reset SSR state.
	 */
	public static function reset() {
		self::$headings = [];
		self::$injected = [];
	}
}

/**
 * Slugify heading text consistently with frontend JS.
 * Does NOT use remove_accents so Unicode alphabets (Bengali, Arabic, etc.) remain intact.
 *
 * @param string $text Raw heading text.
 * @param array  $used Map of already used IDs.
 * @return string Unique slug ID.
 */
function rawnaq_spt_slugify( string $text, array &$used ): string {
	$clean = trim( wp_strip_all_tags( $text ) );
	$base  = function_exists( 'mb_strtolower' ) ? mb_strtolower( $clean, 'UTF-8' ) : strtolower( $clean );
	// Unicode letter, number & mark (diacritics/vowels) property escape.
	$base = preg_replace( '/[^\p{L}\p{N}\p{M}\s-]/u', '', (string) $base );
	$base = trim( (string) $base );
	$base = preg_replace( '/\s+/u', '-', (string) $base );

	if ( '' === $base ) {
		$base = 'section';
	}

	$id = $base;
	$n  = 2;
	while ( isset( $used[ $id ] ) ) {
		$id = $base . '-' . $n;
		$n++;
	}
	$used[ $id ] = true;

	return $id;
}

/**
 * Sanitize and validate SPT module configuration.
 *
 * @param array $raw Unsanitized settings array.
 * @return array Validated configuration.
 */
function rawnaq_spt_cfg_sanitize( array $raw ): array {
	// Progress type
	$progress = sanitize_key( $raw['progress'] ?? 'both' );
	if ( ! in_array( $progress, [ 'bar', 'ring', 'both', 'none' ], true ) ) {
		$progress = 'both';
	}

	// Bar position
	$bar_pos = sanitize_key( $raw['barPosition'] ?? $raw['bar_position'] ?? 'top' );
	if ( ! in_array( $bar_pos, [ 'top', 'bottom' ], true ) ) {
		$bar_pos = 'top';
	}

	// Progress target (page | article | custom)
	$progress_target = sanitize_key( $raw['progressTarget'] ?? $raw['progress_target'] ?? 'page' );
	if ( ! in_array( $progress_target, [ 'page', 'article', 'custom' ], true ) ) {
		$progress_target = 'page';
	}

	// Custom progress selector (reject '<' or '{' for safety)
	$progress_selector = sanitize_text_field( $raw['progressSelector'] ?? $raw['progress_selector'] ?? '' );
	if ( strpbrk( $progress_selector, '<{>' ) !== false ) {
		$progress_selector = '';
	}

	// Ring size
	$ring_size = absint( $raw['ringSize'] ?? $raw['ring_size'] ?? 56 );
	if ( $ring_size < 40 ) {
		$ring_size = 40;
	} elseif ( $ring_size > 96 ) {
		$ring_size = 96;
	}

	// TOC position
	$toc_pos = sanitize_key( $raw['tocPosition'] ?? $raw['toc_position'] ?? 'sticky' );
	if ( ! in_array( $toc_pos, [ 'sticky', 'floating', 'inline', 'none' ], true ) ) {
		$toc_pos = 'sticky';
	}

	// TOC title
	$toc_title = sanitize_text_field( $raw['tocTitle'] ?? $raw['toc_title'] ?? __( 'Contents', 'rawnaq' ) );
	if ( '' === $toc_title ) {
		$toc_title = __( 'Contents', 'rawnaq' );
	}

	// Source
	$source = sanitize_key( $raw['source'] ?? 'auto' );
	if ( ! in_array( $source, [ 'auto', 'manual' ], true ) ) {
		$source = 'auto';
	}

	// Heading levels (h2-h6 subset)
	$levels_raw = $raw['levels'] ?? [ 'h2', 'h3' ];
	if ( is_string( $levels_raw ) ) {
		$levels_raw = explode( ',', $levels_raw );
	}
	$allowed_levels = [ 'h2', 'h3', 'h4', 'h5', 'h6' ];
	$levels         = [];
	if ( is_array( $levels_raw ) ) {
		foreach ( $levels_raw as $lvl ) {
			$lvl = strtolower( trim( (string) $lvl ) );
			if ( in_array( $lvl, $allowed_levels, true ) && ! in_array( $lvl, $levels, true ) ) {
				$levels[] = $lvl;
			}
		}
	}
	if ( empty( $levels ) ) {
		$levels = [ 'h2', 'h3' ];
	}

	// Scroll offset (bounded 0 to 400)
	$scroll_offset = isset( $raw['scrollOffset'] ) ? (int) $raw['scrollOffset'] : ( isset( $raw['scroll_offset'] ) ? (int) $raw['scroll_offset'] : 80 );
	$scroll_offset = max( 0, min( 400, $scroll_offset ) );

	// Content scope selector (reject '<' or '{' for safety)
	$scope = sanitize_text_field( $raw['scope'] ?? $raw['content_selector'] ?? '' );
	if ( strpbrk( $scope, '<{>' ) !== false ) {
		$scope = '';
	}

	// Manual items sanitization
	$manual_raw = $raw['manual'] ?? $raw['manual_items'] ?? [];
	if ( is_string( $manual_raw ) ) {
		$decoded = json_decode( $manual_raw, true );
		if ( is_array( $decoded ) ) {
			$manual_raw = $decoded;
		} else {
			// Check if lines of "Title|anchor|level"
			$lines = explode( "\n", $manual_raw );
			$manual_raw = [];
			foreach ( $lines as $line ) {
				$line = trim( $line );
				if ( '' === $line ) {
					continue;
				}
				$parts = array_map( 'trim', explode( '|', $line ) );
				$manual_raw[] = [
					'title'  => $parts[0] ?? '',
					'anchor' => $parts[1] ?? ( $parts[0] ?? '' ),
					'level'  => $parts[2] ?? '2',
				];
			}
		}
	}
	$manual = [];
	if ( is_array( $manual_raw ) ) {
		$manual_used = [];
		foreach ( $manual_raw as $it ) {
			if ( ! is_array( $it ) ) {
				continue;
			}
			$m_title = sanitize_text_field( $it['title'] ?? '' );
			if ( '' === $m_title ) {
				continue;
			}
			$raw_anchor = trim( (string) ( $it['anchor'] ?? $it['id'] ?? '' ) );
			if ( '' !== $raw_anchor ) {
				$m_id = rawnaq_spt_slugify( $raw_anchor, $manual_used );
			} else {
				$m_id = rawnaq_spt_slugify( $m_title, $manual_used );
			}
			$m_level = sanitize_text_field( $it['level'] ?? '2' );
			$manual[] = [
				'title' => $m_title,
				'id'    => $m_id,
				'level' => in_array( $m_level, [ '2', '3', '4', '5', '6' ], true ) ? $m_level : '2',
			];
		}
	}

	// Words per minute reading rate
	$wpm = absint( $raw['wpm'] ?? 200 );
	if ( $wpm < 50 || $wpm > 600 ) {
		$wpm = 200;
	}
	$wpm = (int) apply_filters( 'rawnaq_spt_wpm', $wpm, $raw );

	// Boolean flags
	$toc_collapsible      = ! isset( $raw['tocCollapsible'] ) && ! isset( $raw['toc_collapsible'] ) ? true : ( ( $raw['tocCollapsible'] ?? $raw['toc_collapsible'] ?? 'yes' ) === 'yes' || ! empty( $raw['tocCollapsible'] ) || ! empty( $raw['toc_collapsible'] ) );
	$collapse_subs        = ( $raw['collapseSubs'] ?? $raw['collapse_subs'] ?? '' ) === 'yes' || ! empty( $raw['collapseSubs'] ) || ! empty( $raw['collapse_subs'] );
	$show_search          = ( $raw['showSearch'] ?? $raw['show_search'] ?? '' ) === 'yes' || ! empty( $raw['showSearch'] ) || ! empty( $raw['show_search'] );
	// Default urlHashSync to FALSE in all three builders
	$url_hash_sync        = ( $raw['urlHashSync'] ?? $raw['url_hash_sync'] ?? '' ) === 'yes' || ( ! empty( $raw['urlHashSync'] ) && 'no' !== $raw['urlHashSync'] );
	$section_reading_time = ( $raw['sectionReadingTime'] ?? $raw['section_reading_time'] ?? '' ) === 'yes' || ! empty( $raw['sectionReadingTime'] ) || ! empty( $raw['section_reading_time'] );
	$reading_time         = ( $raw['readingTime'] ?? $raw['reading_time'] ?? '' ) === 'yes' || ! empty( $raw['readingTime'] ) || ! empty( $raw['reading_time'] );
	$smooth               = ! isset( $raw['smooth'] ) ? true : ( ( $raw['smooth'] ?? 'yes' ) === 'yes' || ! empty( $raw['smooth'] ) );
	$mobile_collapse      = ! isset( $raw['mobileCollapse'] ) && ! isset( $raw['mobile_collapse'] ) ? true : ( ( $raw['mobileCollapse'] ?? $raw['mobile_collapse'] ?? 'yes' ) === 'yes' || ! empty( $raw['mobileCollapse'] ) || ! empty( $raw['mobile_collapse'] ) );
	$dock_attach          = ( $raw['dockAttach'] ?? $raw['dock_attach'] ?? '' ) === 'yes' || ! empty( $raw['dockAttach'] ) || ! empty( $raw['dock_attach'] );
	$click_to_top         = ! isset( $raw['clickToTop'] ) && ! isset( $raw['click_to_top'] ) ? true : ( ( $raw['clickToTop'] ?? $raw['click_to_top'] ?? 'yes' ) === 'yes' || ! empty( $raw['clickToTop'] ) || ! empty( $raw['click_to_top'] ) );
	$show_percent         = ! isset( $raw['showPercent'] ) && ! isset( $raw['show_percent'] ) ? true : ( ( $raw['showPercent'] ?? $raw['show_percent'] ?? 'yes' ) === 'yes' || ! empty( $raw['showPercent'] ) || ! empty( $raw['show_percent'] ) );
	$hide_if_short        = ! isset( $raw['hideIfShort'] ) && ! isset( $raw['hide_if_short'] ) ? true : ( ( $raw['hideIfShort'] ?? $raw['hide_if_short'] ?? 'yes' ) === 'yes' || ! empty( $raw['hideIfShort'] ) || ! empty( $raw['hide_if_short'] ) );
	$sync_timeline        = sanitize_text_field( $raw['syncTimeline'] ?? $raw['sync_timeline'] ?? '' );

	return [
		'progress'           => $progress,
		'barPosition'        => $bar_pos,
		'progressTarget'     => $progress_target,
		'progressSelector'   => $progress_selector,
		'showPercent'        => $show_percent,
		'clickToTop'         => $click_to_top,
		'ringSize'           => $ring_size,
		'tocPosition'        => $toc_pos,
		'tocTitle'           => $toc_title,
		'tocCollapsible'     => $toc_collapsible,
		'source'             => $source,
		'levels'             => array_values( $levels ),
		'manual'             => $manual,
		'collapseSubs'       => $collapse_subs,
		'showSearch'         => $show_search,
		'urlHashSync'        => $url_hash_sync,
		'sectionReadingTime' => $section_reading_time,
		'readingTime'        => $reading_time,
		'wpm'                => $wpm,
		'smooth'             => $smooth,
		'scrollOffset'       => $scroll_offset,
		'mobileCollapse'     => $mobile_collapse,
		'dockAttach'         => $dock_attach,
		'syncTimeline'       => $sync_timeline,
		'scope'              => $scope,
		'hideIfShort'        => $hide_if_short,
		'debug'              => defined( 'WP_DEBUG' ) && WP_DEBUG,
	];
}

/**
 * Extract headings from HTML, inject missing IDs, and return heading list + modified HTML.
 *
 * @param string $html HTML content.
 * @param array  $used Reference to used IDs map.
 * @return array Array with keys 'headings' (array) and 'html' (string).
 */
function rawnaq_spt_extract_headings( string $html, array &$used ): array {
	$headings = [];
	if ( '' === trim( $html ) ) {
		return [
			'headings' => [],
			'html'     => $html,
			0          => [],
			1          => $html,
		];
	}

	// Match h2-h6 headings.
	$pattern = '/<(h[2-6])\b([^>]*)>(.*?)<\/\1>/uis';

	$html = preg_replace_callback( $pattern, function( $matches ) use ( &$used, &$headings ) {
		$tag   = strtolower( $matches[1] );
		$attrs = $matches[2];
		$inner = $matches[3];

		// Skip headings inside our own TOC widget / nav / tilt card
		if ( strpos( $attrs, 'rawnaq-spt-title' ) !== false || strpos( $attrs, 'rawnaq-tilt-title' ) !== false ) {
			return $matches[0];
		}

		// Decode entities and strip tags for clean link text and slug generation
		$text = trim( html_entity_decode( wp_strip_all_tags( $inner ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		if ( '' === $text ) {
			return $matches[0];
		}

		$id = '';
		// Strictly match id="..." while ignoring data-id or aria-id
		if ( preg_match( '/(?<![\w-])id\s*=\s*(["\'])(.*?)\1/i', $attrs, $id_match ) ) {
			$id          = $id_match[2];
			$used[ $id ] = true;
		} else {
			$id     = rawnaq_spt_slugify( $text, $used );
			$attrs .= ' id="' . esc_attr( $id ) . '"';
		}

		$level      = (int) substr( $tag, 1 );
		$headings[] = [
			'id'    => $id,
			'text'  => $text,
			'level' => $level,
			'tag'   => $tag,
		];

		return '<' . $tag . $attrs . '>' . $inner . '</' . $tag . '>';
	}, $html );

	return [
		'headings' => $headings,
		'html'     => $html,
		0          => $headings,
		1          => $html,
	];
}

/**
 * Filter the_content (priority 8, before do_blocks) to auto-inject missing IDs into headings and extract SSR list.
 *
 * @param string $content Post HTML content.
 * @return string Processed content.
 */
function rawnaq_spt_the_content_headings( $content ) {
	if ( ! is_string( $content ) || '' === trim( $content ) ) {
		return $content;
	}

	if ( ! apply_filters( 'rawnaq_spt_ssr_enabled', true ) ) {
		return $content;
	}

	// Process only the main post's content in the main query loop.
	if ( ! ( is_singular() && in_the_loop() && is_main_query() && get_the_ID() === get_queried_object_id() ) ) {
		return $content;
	}

	$post_id = get_the_ID();
	static $extracted_posts = [];
	if ( empty( Rawnaq_SPT_SSR::$injected ) && empty( Rawnaq_SPT_SSR::$headings ) ) {
		$extracted_posts = [];
	}
	if ( isset( $extracted_posts[ $post_id ] ) ) {
		return $extracted_posts[ $post_id ];
	}

	// Check if post content or page has TOC widget / block / shortcode
	$has_toc = (
		strpos( $content, 'rawnaq-spt' ) !== false ||
		strpos( $content, 'wp:rawnaq/scroll-progress-toc' ) !== false ||
		( function_exists( 'has_block' ) && has_block( 'rawnaq/scroll-progress-toc' ) )
	);

	if ( ! $has_toc ) {
		return $content;
	}

	$used_ids                             = [];
	$extracted                            = rawnaq_spt_extract_headings( $content, $used_ids );
	$extracted_posts[ $post_id ]          = $extracted['html'];
	Rawnaq_SPT_SSR::$injected[ $post_id ] = $extracted['html'];
	Rawnaq_SPT_SSR::$headings             = $extracted['headings'];

	return $extracted['html'];
}
add_filter( 'the_content', 'rawnaq_spt_the_content_headings', 8 );

/**
 * Generate unified HTML markup for Scroll Progress + TOC module.
 *
 * @param array $cfg Validated config from rawnaq_spt_cfg_sanitize().
 * @param array $style_vars Extra CSS variables (e.g. ['--spt-accent' => '#6366f1']).
 * @param array $extra_attrs Extra HTML attributes (e.g. from Gutenberg block wrapper).
 * @return string Complete HTML markup.
 */
function rawnaq_spt_markup( array $cfg, array $style_vars = [], array $extra_attrs = [] ): string {
	$pos       = $cfg['tocPosition'];
	$ring_size = $cfg['ringSize'];
	$list_id   = 'spt-list-' . wp_unique_id();

	// Style variables
	$styles = [
		'--spt-offset: ' . esc_attr( (string) $cfg['scrollOffset'] ) . 'px',
		'--spt-ring-size: ' . esc_attr( (string) $ring_size ) . 'px',
	];
	foreach ( $style_vars as $k => $v ) {
		if ( $v ) {
			$styles[] = esc_attr( $k ) . ': ' . esc_attr( $v );
		}
	}
	$style_str = implode( '; ', $styles ) . ';';

	// Extra wrapper classes / attributes
	$wrapper_classes = [ 'rawnaq-spt' ];
	if ( ! empty( $extra_attrs['class'] ) ) {
		$wrapper_classes[] = $extra_attrs['class'];
		unset( $extra_attrs['class'] );
	}
	$attr_strs = [];
	foreach ( $extra_attrs as $k => $v ) {
		$attr_strs[] = esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
	}

	// Self-sufficient heading extraction for early renders (block-theme template parts, sidebars)
	if ( 'auto' === $cfg['source'] && empty( Rawnaq_SPT_SSR::$headings ) && is_singular() && apply_filters( 'rawnaq_spt_ssr_enabled', true ) ) {
		static $spt_post_cache = [];
		if ( empty( Rawnaq_SPT_SSR::$injected ) && empty( Rawnaq_SPT_SSR::$headings ) ) {
			$spt_post_cache = [];
		}
		$post_id = function_exists( 'get_queried_object_id' ) ? (int) get_queried_object_id() : 0;
		$post    = $post_id ? get_post( $post_id ) : null;
		if ( $post && ! empty( $post->post_content ) ) {
			if ( ! isset( $spt_post_cache[ $post_id ] ) ) {
				$used                       = [];
				$extracted                  = rawnaq_spt_extract_headings( $post->post_content, $used );
				$spt_post_cache[ $post_id ] = $extracted['headings'];
			}
			Rawnaq_SPT_SSR::$headings = $spt_post_cache[ $post_id ];
		}
	}

	// SSR Headings Rendering (source=auto or manual)
	$ssr_items = [];
	if ( 'auto' === $cfg['source'] && ! empty( Rawnaq_SPT_SSR::$headings ) ) {
		$filter_levels = array_map( function( $l ) {
			return (int) str_replace( 'h', '', strtolower( $l ) );
		}, $cfg['levels'] );

		foreach ( Rawnaq_SPT_SSR::$headings as $h ) {
			if ( in_array( $h['level'], $filter_levels, true ) ) {
				$ssr_items[] = [
					'id'    => $h['id'],
					'text'  => $h['text'],
					'level' => $h['level'],
				];
			}
		}
	} elseif ( 'manual' === $cfg['source'] && ! empty( $cfg['manual'] ) ) {
		foreach ( $cfg['manual'] as $m ) {
			$ssr_items[] = [
				'id'    => $m['id'],
				'text'  => $m['title'],
				'level' => (int) $m['level'],
			];
		}
	}

	$has_ssr = ! empty( $ssr_items );

	ob_start();
	?>
	<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>"
	     style="<?php echo esc_attr( $style_str ); ?>"
	     data-spt="<?php echo esc_attr( wp_json_encode( $cfg ) ); ?>"
	     <?php echo implode( ' ', $attr_strs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( 'none' !== $pos ) : ?>
			<nav class="rawnaq-spt-toc is-<?php echo esc_attr( $pos ); ?>" aria-label="<?php echo esc_attr( $cfg['tocTitle'] ); ?>">
				<div class="rawnaq-spt-header-wrap">
					<p class="rawnaq-spt-reading" hidden></p>
					<p class="rawnaq-spt-chapter" hidden></p>
					<div class="rawnaq-spt-title-row">
						<h3 class="rawnaq-spt-title"><?php echo esc_html( $cfg['tocTitle'] ); ?></h3>
						<?php if ( ! empty( $cfg['tocCollapsible'] ) ) : ?>
							<button type="button" class="rawnaq-spt-toggle-btn" aria-expanded="true" aria-controls="<?php echo esc_attr( $list_id ); ?>" aria-label="<?php esc_attr_e( 'Toggle Table of Contents', 'rawnaq' ); ?>">
								<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
							</button>
						<?php endif; ?>
					</div>
				</div>
				<div class="rawnaq-spt-list-wrap" id="<?php echo esc_attr( $list_id ); ?>">
					<ul class="rawnaq-spt-list"<?php if ( $has_ssr ) : ?> data-ssr="1"<?php endif; ?>>
						<?php if ( $has_ssr ) : ?>
							<?php foreach ( $ssr_items as $item ) : ?>
								<li class="lvl-<?php echo esc_attr( (string) $item['level'] ); ?>">
									<a href="#<?php echo esc_attr( $item['id'] ); ?>" class="lvl-<?php echo esc_attr( (string) $item['level'] ); ?>">
										<span class="rawnaq-spt-link-text"><?php echo esc_html( $item['text'] ); ?></span>
									</a>
								</li>
							<?php endforeach; ?>
						<?php endif; ?>
					</ul>
				</div>
			</nav>
		<?php endif; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}
