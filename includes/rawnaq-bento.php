<?php
/**
 * Bento Grid: shared cell normalizer + renderer.
 *
 * Elementor, Gutenberg and Divi all map their own settings onto ONE cell schema
 * (rawnaq_bento_normalize_cell) and print ONE markup (rawnaq_bento_markup), the
 * same single-source-of-truth pattern as includes/rawnaq-case-study.php.
 *
 * @package Rawnaq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whitelist a heading tag for cell titles.
 *
 * @param mixed $tag Requested tag.
 * @return string div|h2..h6 (default div).
 */
function rawnaq_bento_title_tag( $tag ) {
	$tag = strtolower( sanitize_key( (string) $tag ) );
	return in_array( $tag, [ 'div', 'h2', 'h3', 'h4', 'h5', 'h6' ], true ) ? $tag : 'div';
}

/**
 * Whether the grid is being rendered inside a builder/editor preview
 * (Elementor editor/preview, Divi visual builder, block-editor REST render).
 *
 * @return bool
 */
function rawnaq_bento_is_editor_context() {
	if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance ) {
		$plugin = \Elementor\Plugin::$instance;
		if ( isset( $plugin->editor ) && $plugin->editor->is_edit_mode() ) {
			return true;
		}
		if ( isset( $plugin->preview ) && $plugin->preview->is_preview_mode() ) {
			return true;
		}
	}
	if ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() ) {
		return true;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only context flag, no state change.
		$context = isset( $_GET['context'] ) ? sanitize_key( wp_unslash( $_GET['context'] ) ) : '';
		return 'edit' === $context;
	}
	return false;
}

/**
 * Normalize a link given as a plain URL string or an Elementor URL control array.
 *
 * @param mixed $value URL string or [url, is_external, nofollow].
 * @return array{url:string,external:bool,nofollow:bool}
 */
function rawnaq_bento_link( $value ) {
	$out = [
		'url'      => '',
		'external' => false,
		'nofollow' => false,
	];
	$url = '';
	if ( is_string( $value ) ) {
		$url = $value;
	} elseif ( is_array( $value ) ) {
		$url             = (string) ( $value['url'] ?? '' );
		$out['external'] = ! empty( $value['is_external'] );
		$out['nofollow'] = ! empty( $value['nofollow'] );
	}
	$out['url'] = esc_url_raw( trim( $url ) );
	return $out;
}

/**
 * href/target/rel attribute string for a normalized link (escaped).
 *
 * @param array $link Normalized link.
 * @return string Leading-space attribute string, or ''.
 */
function rawnaq_bento_link_attrs( array $link ) {
	if ( empty( $link['url'] ) ) {
		return '';
	}
	$attrs = ' href="' . esc_url( $link['url'] ) . '"';
	$rel   = [];
	if ( ! empty( $link['external'] ) ) {
		$attrs .= ' target="_blank"';
		$rel[]  = 'noopener';
	}
	if ( ! empty( $link['nofollow'] ) ) {
		$rel[] = 'nofollow';
	}
	if ( $rel ) {
		$attrs .= ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"';
	}
	return $attrs;
}

/**
 * Parse a stat value for the count-up animation.
 *
 * Only a plain ASCII number is animated: digits, optional comma thousands
 * separators, optional decimal part. Anything else ("24/7", Bengali digits,
 * "N/A") returns null and stays exactly as typed.
 *
 * @param string $value Stat text.
 * @return array{count:string,decimals:int,grouping:bool}|null
 */
function rawnaq_bento_parse_stat( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return null;
	}
	$plain   = (bool) preg_match( '/^[0-9]+(?:\.[0-9]+)?$/', $value );
	$grouped = (bool) preg_match( '/^[0-9]{1,3}(?:,[0-9]{3})+(?:\.[0-9]+)?$/', $value );
	if ( ! $plain && ! $grouped ) {
		return null;
	}
	$dot = strpos( $value, '.' );
	return [
		'count'    => str_replace( ',', '', $value ),
		'decimals' => false === $dot ? 0 : strlen( $value ) - $dot - 1,
		'grouping' => $grouped,
	];
}

/**
 * Map one raw cell (Elementor repeater row, Gutenberg cellsJson item or Divi
 * cells_json item) onto the shared schema. Missing keys are tolerated.
 *
 * @param array  $raw    Raw cell.
 * @param string $source elementor|gutenberg|divi.
 * @return array Normalized cell.
 */
function rawnaq_bento_normalize_cell( array $raw, $source = 'elementor' ) {
	$g = static function ( array $keys, $default = '' ) use ( $raw ) {
		foreach ( $keys as $key ) {
			if ( isset( $raw[ $key ] ) && '' !== $raw[ $key ] && null !== $raw[ $key ] ) {
				return $raw[ $key ];
			}
		}
		return $default;
	};
	$text = static function ( $value ) {
		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	};

	$type = sanitize_key( (string) $g( [ 'cell_type', 'type' ], 'text' ) );
	if ( 'icon' === $type ) {
		$type = 'text';
	} elseif ( 'media' === $type ) {
		$type = 'image';
	}
	if ( ! in_array( $type, [ 'text', 'featured', 'image', 'stat', 'video', 'testimonial', 'post' ], true ) ) {
		$type = 'text';
	}

	$col = $g( [ 'col_span', 'col', 'colSpan' ], null );
	if ( null === $col && 'span-2' === ( $raw['span'] ?? '' ) ) {
		$col = 2;
	}
	$row = $g( [ 'row_span', 'row', 'rowSpan' ], null );

	$order = static function ( $value ) {
		return max( -20, min( 20, intval( $value ) ) );
	};

	$title    = $text( $g( [ 'title' ] ) );
	$subtitle = $g( [ 'subtitle', 'desc' ] );
	$subtitle = is_scalar( $subtitle ) ? sanitize_textarea_field( (string) $subtitle ) : '';
	$tag      = $text( $g( [ 'tag', 'badge' ] ) );

	// Image.
	$image_raw = $g( [ 'image' ], '' );
	$image_id  = 0;
	if ( is_array( $image_raw ) ) {
		$image_id  = absint( $image_raw['id'] ?? 0 );
		$image_url = (string) ( $image_raw['url'] ?? '' );
	} else {
		$image_url = (string) $image_raw;
		$image_id  = absint( $g( [ 'imageId' ], 0 ) );
	}
	$image_url = esc_url_raw( trim( $image_url ) );
	$image_alt = $text( $g( [ 'image_alt', 'imageAlt', 'alt' ] ) );
	if ( '' === $image_alt && $image_id > 0 ) {
		$image_alt = $text( get_post_meta( $image_id, '_wp_attachment_image_alt', true ) );
	}

	// Video.
	$video_raw = $g( [ 'video_url', 'video' ], '' );
	$video     = esc_url_raw( trim( is_array( $video_raw ) ? (string) ( $video_raw['url'] ?? '' ) : (string) $video_raw ) );

	// Icon: Elementor icon control (rendered markup) or a dashicons class.
	$icon_html  = '';
	$icon_class = '';
	$icon_raw   = $raw['selected_icon'] ?? null;
	if ( is_array( $icon_raw ) && ! empty( $icon_raw['value'] ) && class_exists( '\Elementor\Icons_Manager' ) ) {
		ob_start();
		\Elementor\Icons_Manager::render_icon( $icon_raw, [ 'aria-hidden' => 'true' ] );
		$icon_html = (string) ob_get_clean();
	} elseif ( isset( $raw['icon'] ) && is_string( $raw['icon'] ) ) {
		$icon_class = sanitize_html_class( $raw['icon'] );
	}

	// Testimonial avatar.
	$avatar_raw = $g( [ 'testimonial_avatar', 'avatar' ], '' );
	$avatar     = esc_url_raw( trim( is_array( $avatar_raw ) ? (string) ( $avatar_raw['url'] ?? '' ) : (string) $avatar_raw ) );

	$link     = rawnaq_bento_link( $g( [ 'link' ], '' ) );
	$cta_text = $text( $g( [ 'cta_text', 'ctaText' ] ) );
	$cta_link = rawnaq_bento_link( $g( [ 'cta_link', 'ctaLink' ], '' ) );
	if ( '' === $cta_link['url'] ) {
		$cta_link = $link;
	}

	$bg = rawnaq_sanitize_css_color( $g( [ 'bg_color', 'bg' ] ) );
	if ( 'elementor' === $source && ! in_array( $type, [ 'text', 'stat', 'testimonial', 'post' ], true ) ) {
		$bg = '';
	}

	$sync_raw  = trim( (string) $g( [ 'sync_timeline', 'timelineSync' ] ) );
	$sync_name = '';
	if ( '' !== $sync_raw ) {
		$sync_name = function_exists( 'rawnaq_timeline_sanitize_tl_name' )
			? rawnaq_timeline_sanitize_tl_name( $sync_raw, $sync_raw )
			: preg_replace( '/[^a-zA-Z0-9_-]/', '', $sync_raw );
	}

	$cell = [
		'type'       => $type,
		'col'        => max( 1, min( 6, intval( null === $col ? 1 : $col ) ) ),
		'row'        => max( 1, min( 6, intval( null === $row ? 1 : $row ) ) ),
		'order'      => $order( $g( [ 'order_desktop', 'order', 'orderDesk' ], 0 ) ),
		'col_md'     => max( 0, min( 6, intval( $g( [ 'col_span_tablet', 'colMd' ], 0 ) ) ) ),
		'row_md'     => max( 0, min( 6, intval( $g( [ 'row_span_tablet', 'rowMd' ], 0 ) ) ) ),
		'order_md'   => $order( $g( [ 'order_tablet', 'orderMd' ], 0 ) ),
		'col_sm'     => max( 0, min( 6, intval( $g( [ 'col_span_mobile', 'colSm' ], 0 ) ) ) ),
		'row_sm'     => max( 0, min( 6, intval( $g( [ 'row_span_mobile', 'rowSm' ], 0 ) ) ) ),
		'order_sm'   => $order( $g( [ 'order_mobile', 'orderSm' ], 0 ) ),
		'align'      => sanitize_key( (string) $g( [ 'content_align', 'align' ] ) ),
		'tag'        => $tag,
		'tag_bg'     => rawnaq_sanitize_css_color( $g( [ 'cell_tag_bg', 'tagBg' ] ) ),
		'tag_color'  => rawnaq_sanitize_css_color( $g( [ 'cell_tag_color', 'tagColor' ] ) ),
		'title'      => $title,
		'subtitle'   => $subtitle,
		'role'       => $text( $g( [ 'testimonial_role', 'role' ] ) ),
		'rating'     => max( 0, min( 5, absint( $g( [ 'testimonial_rating', 'rating' ], 0 ) ) ) ),
		'avatar'     => $avatar,
		'icon_html'  => $icon_html,
		'icon_class' => $icon_class,
		'image'      => $image_url,
		'image_alt'  => $image_alt,
		'video'      => $video,
		'stat'       => $text( $g( [ 'stat_value', 'stat' ] ) ),
		'prefix'     => $text( $g( [ 'stat_prefix', 'prefix' ] ) ),
		'suffix'     => $text( $g( [ 'stat_suffix', 'suffix' ] ) ),
		'link'       => $link,
		'cta_text'   => $cta_text,
		'cta_link'   => $cta_link,
		'bg'         => $bg,
		'color'      => rawnaq_sanitize_css_color( $g( [ 'color' ] ) ),
		'sync'       => $sync_name,
		'post_args'  => [],
	];

	if ( 'post' === $type ) {
		$cell['post_args'] = array_merge(
			$raw,
			[
				'title'    => $title,
				'subtitle' => $subtitle,
				'tag'      => $tag,
				'cta_text' => $cta_text,
			]
		);
	}

	return $cell;
}

/**
 * Tag/eyebrow pill markup.
 *
 * @param array  $cell Normalized cell.
 * @param string $text Pill text (defaults to the cell tag).
 * @return string
 */
function rawnaq_bento_tag_html( array $cell, $text = null ) {
	$text = null === $text ? (string) $cell['tag'] : (string) $text;
	if ( '' === $text ) {
		return '';
	}
	$attrs = rawnaq_bento_tag_attrs( $cell['tag_bg'], $cell['tag_color'] );
	$html  = '<div class="' . esc_attr( $attrs['class'] ) . '"';
	if ( '' !== $attrs['style'] ) {
		$html .= ' style="' . esc_attr( $attrs['style'] ) . '"';
	}
	return $html . '>' . esc_html( $text ) . '</div>';
}

/**
 * CTA markup: a link when a URL exists, otherwise a static label.
 *
 * @param string $text CTA text.
 * @param array  $link Normalized link.
 * @return string
 */
function rawnaq_bento_cta_html( $text, array $link ) {
	$text = trim( (string) $text );
	if ( '' === $text ) {
		return '';
	}
	if ( ! empty( $link['url'] ) ) {
		return '<a class="rawnaq-bento-cta"' . rawnaq_bento_link_attrs( $link ) . '>' . esc_html( $text ) . '</a>';
	}
	return '<span class="rawnaq-bento-cta is-static">' . esc_html( $text ) . '</span>';
}

/**
 * Render one cell.
 *
 * @param array $cell  Normalized cell (rawnaq_bento_normalize_cell).
 * @param int   $index Cell index.
 * @param array $ctx   Context: title_tag (string), editor (bool).
 * @return string HTML ('' when a specific-post cell cannot be shown on the frontend).
 */
function rawnaq_bento_render_cell( array $cell, $index, array $ctx ) {
	$type      = $cell['type'];
	$title_tag = rawnaq_bento_title_tag( $ctx['title_tag'] ?? 'div' );
	$editor    = ! empty( $ctx['editor'] );

	$title    = $cell['title'];
	$subtitle = $cell['subtitle'];
	$tag      = $cell['tag'];
	$image    = $cell['image'];
	$link     = $cell['link'];
	$cta_text = $cell['cta_text'];
	$cta_link = $cell['cta_link'];

	$post_data  = [];
	$show_badge = true;
	if ( 'post' === $type ) {
		$post_data = rawnaq_bento_get_post_data( $cell['post_args'] );
		if ( empty( $post_data['found'] ) && ! $editor ) {
			return '';
		}
		$show_badge = ! empty( $post_data['show_badge'] );
		if ( ! empty( $post_data['found'] ) ) {
			if ( '' === $link['url'] && ! empty( $post_data['link'] ) ) {
				$link['url'] = $post_data['link'];
			}
		}
		$title    = ( '' !== $title && 'Cell title' !== $title ) ? $title : (string) ( $post_data['title'] ?? '' );
		$subtitle = '' !== $subtitle ? $subtitle : (string) ( $post_data['excerpt'] ?? '' );
		$tag      = '' !== $tag ? $tag : (string) ( $post_data['tag'] ?? '' );
		if ( ! empty( $post_data['read_more'] ) ) {
			$cta_text = (string) $post_data['read_more'];
		}
		$cta_url = '' !== $cell['cta_link']['url'] ? $cell['cta_link'] : [
			'url'      => ! empty( $post_data['found'] ) ? (string) $post_data['link'] : '',
			'external' => false,
			'nofollow' => false,
		];
		$cta_link = $cta_url;
		if ( '' === $image ) {
			$image = (string) ( $post_data['image'] ?? '' );
		}
	}

	$has_cta = '' !== trim( (string) $cta_text );
	$stretch = '' !== $link['url'] && ! $has_cta;
	$state   = [ 'stretched' => false ];

	// Title element (all types except stat/testimonial which have no title line).
	$title_html = static function ( $text ) use ( $title_tag, $stretch, $link, &$state ) {
		$text = (string) $text;
		if ( '' === $text ) {
			return '';
		}
		$inner = esc_html( $text );
		if ( $stretch && ! $state['stretched'] ) {
			$inner            = '<a class="rawnaq-bento-stretch-link"' . rawnaq_bento_link_attrs( $link ) . '>' . $inner . '</a>';
			$state['stretched'] = true;
		}
		return '<' . $title_tag . ' class="rawnaq-bento-title">' . $inner . '</' . $title_tag . '>';
	};
	$sub_html  = static function ( $text ) {
		return '' !== (string) $text ? '<div class="rawnaq-bento-sub">' . esc_html( $text ) . '</div>' : '';
	};
	$tag_html  = static function () use ( $cell, $tag ) {
		return rawnaq_bento_tag_html( $cell, $tag );
	};
	$cta_html  = static function () use ( $cta_text, $cta_link ) {
		return rawnaq_bento_cta_html( $cta_text, $cta_link );
	};
	$gradient  = '<div class="rawnaq-bento-media" style="background:linear-gradient(135deg,#0f766e,#134e4a);" aria-hidden="true"></div>';

	$layout = rawnaq_bento_cell_layout(
		[
			'col'      => $cell['col'],
			'row'      => $cell['row'],
			'order'    => $cell['order'],
			'col_md'   => $cell['col_md'],
			'row_md'   => $cell['row_md'],
			'order_md' => $cell['order_md'],
			'col_sm'   => $cell['col_sm'],
			'row_sm'   => $cell['row_sm'],
			'order_sm' => $cell['order_sm'],
		]
	);

	$classes     = array_merge( [ 'rawnaq-bento-cell' ], $layout['classes'] );
	$align_class = rawnaq_bento_align_class( $cell['align'] );
	if ( $align_class ) {
		$classes[] = $align_class;
	}
	if ( 'featured' === $type ) {
		$classes[] = 'is-featured';
	} elseif ( 'image' === $type ) {
		$classes[] = 'is-image';
	} elseif ( 'stat' === $type ) {
		$classes[] = 'is-stat';
	} elseif ( 'video' === $type ) {
		$classes[] = 'is-video';
		$parsed    = rawnaq_bento_parse_video( $cell['video'] );
		if ( $parsed && in_array( $parsed['kind'], [ 'youtube', 'vimeo' ], true ) ) {
			$classes[] = 'is-embed';
		}
	} elseif ( 'testimonial' === $type ) {
		$classes[] = 'is-testimonial';
	} elseif ( 'post' === $type ) {
		$classes[] = 'is-post';
		$classes[] = ( ! empty( $post_data['display_mode'] ) && 'card' === $post_data['display_mode'] ) ? 'is-card' : 'is-cover';
	}
	if ( $cell['sync'] ) {
		$classes[] = 'tl-sync';
	}

	$style = $layout['style'];
	if ( $cell['sync'] ) {
		$style .= 'animation-timeline:--' . $cell['sync'] . ';';
	}
	if ( $cell['bg'] ) {
		$style .= 'background-color:' . $cell['bg'] . ';';
	}
	if ( $cell['color'] ) {
		$style .= 'color:' . $cell['color'] . ';';
	}

	$out  = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" style="' . esc_attr( $style ) . '" role="listitem" data-bento-index="' . esc_attr( (string) (int) $index ) . '"';
	$out .= $cell['sync'] ? ' data-tl-sync="' . esc_attr( $cell['sync'] ) . '"' : '';
	$out .= '>';

	if ( 'testimonial' === $type ) {
		$out .= $tag_html();
		if ( '' !== $subtitle ) {
			$out .= '<blockquote class="rawnaq-bento-quote">' . esc_html( $subtitle ) . '</blockquote>';
		}
		if ( $cell['rating'] > 0 ) {
			/* translators: %d: star count */
			$out .= '<div class="rawnaq-bento-stars" aria-label="' . esc_attr( sprintf( __( '%d out of 5 stars', 'rawnaq' ), $cell['rating'] ) ) . '">' . esc_html( str_repeat( '★', $cell['rating'] ) ) . '</div>';
		}
		if ( '' !== $title || '' !== $cell['role'] || '' !== $cell['avatar'] ) {
			$out .= '<div class="rawnaq-bento-author">';
			if ( '' !== $cell['avatar'] ) {
				$out .= '<img class="rawnaq-bento-avatar" src="' . esc_url( $cell['avatar'] ) . '" alt="" loading="lazy" decoding="async" />';
			} elseif ( '' !== $title ) {
				$initial = function_exists( 'mb_substr' ) ? mb_substr( $title, 0, 1 ) : substr( $title, 0, 1 );
				$out    .= '<div class="rawnaq-bento-avatar is-placeholder" aria-hidden="true">' . esc_html( strtoupper( $initial ) ) . '</div>';
			}
			if ( '' !== $title || '' !== $cell['role'] ) {
				$out .= '<div class="rawnaq-bento-author-meta">';
				$out .= '' !== $title ? '<div class="rawnaq-bento-author-name">' . esc_html( $title ) . '</div>' : '';
				$out .= '' !== $cell['role'] ? '<div class="rawnaq-bento-author-role">' . esc_html( $cell['role'] ) . '</div>' : '';
				$out .= '</div>';
			}
			$out .= '</div>';
		}
		$out .= $cta_html();
	} elseif ( 'post' === $type ) {
		$card        = ! empty( $post_data['display_mode'] ) && 'card' === $post_data['display_mode'];
		$badge_html  = ( '' !== $tag && $show_badge ) ? $tag_html() : '';
		$meta_html   = '';
		if ( ! empty( $post_data['date'] ) || ! empty( $post_data['author'] ) ) {
			$meta_html = '<div class="rawnaq-bento-post-meta">';
			if ( ! empty( $post_data['author'] ) ) {
				if ( ! empty( $post_data['author_avatar'] ) ) {
					$meta_html .= '<img class="rawnaq-bento-post-author-img" src="' . esc_url( $post_data['author_avatar'] ) . '" alt="" loading="lazy" />';
				}
				$meta_html .= '<span class="rawnaq-bento-post-author">' . esc_html( $post_data['author'] ) . '</span>';
			}
			if ( ! empty( $post_data['date'] ) ) {
				$meta_html .= '<span class="rawnaq-bento-post-date">' . esc_html( $post_data['date'] ) . '</span>';
			}
			$meta_html .= '</div>';
		}
		if ( $card ) {
			if ( '' !== $image ) {
				$out .= '<div class="rawnaq-bento-post-thumb"><img src="' . esc_url( $image ) . '" alt="' . esc_attr( $title ) . '" loading="lazy" decoding="async" />' . $badge_html . '</div>';
			}
			$out .= '<div class="rawnaq-bento-post-content">';
			$out .= '' === $image ? $badge_html : '';
			$out .= $meta_html . $title_html( $title ) . $sub_html( $subtitle ) . $cta_html();
			$out .= '</div>';
		} else {
			$out .= '' !== $image
				? '<img class="rawnaq-bento-media" src="' . esc_url( $image ) . '" alt="' . esc_attr( $title ) . '" loading="lazy" decoding="async" />'
				: $gradient;
			$out .= '<div class="rawnaq-bento-overlay" aria-hidden="true"></div>';
			$out .= '<div class="rawnaq-bento-body">' . $badge_html . $meta_html . $title_html( $title ) . $sub_html( $subtitle ) . $cta_html() . '</div>';
		}
	} elseif ( 'image' === $type ) {
		if ( '' !== $image ) {
			// Decorative when the cell already has a text label; otherwise use the attachment alt.
			$alt  = ( '' !== $title || '' !== $subtitle ) ? '' : $cell['image_alt'];
			$out .= '<img class="rawnaq-bento-media" src="' . esc_url( $image ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async" />';
		} else {
			$out .= $gradient;
		}
		$out .= '<div class="rawnaq-bento-overlay" aria-hidden="true"></div>';
		$out .= '<div class="rawnaq-bento-body">' . $tag_html() . $title_html( $title ) . $sub_html( $subtitle ) . $cta_html() . '</div>';
	} elseif ( 'video' === $type ) {
		$out .= rawnaq_bento_video_markup( $cell['video'], $title );
		$out .= '<div class="rawnaq-bento-overlay" aria-hidden="true"></div>';
		$out .= '<div class="rawnaq-bento-body">' . $tag_html() . $title_html( $title ) . $sub_html( $subtitle ) . $cta_html() . '</div>';
	} elseif ( 'stat' === $type ) {
		$stat   = $cell['stat'];
		$parsed = rawnaq_bento_parse_stat( $stat );
		$out   .= $tag_html();
		$out   .= '<div class="rawnaq-bento-num"';
		if ( $parsed ) {
			$out .= ' data-count="' . esc_attr( $parsed['count'] ) . '"';
			$out .= ' data-decimals="' . esc_attr( (string) $parsed['decimals'] ) . '"';
			$out .= $parsed['grouping'] ? ' data-grouping="1"' : '';
			$out .= ' data-suffix="' . esc_attr( $cell['suffix'] ) . '" data-prefix="' . esc_attr( $cell['prefix'] ) . '"';
		}
		$out .= '>' . esc_html( $cell['prefix'] . $stat . $cell['suffix'] ) . '</div>';
		$out .= $sub_html( $subtitle ) . $cta_html();
	} else {
		$out .= $tag_html();
		if ( '' !== $cell['icon_html'] ) {
			// Elementor Icons_Manager output (already escaped by Elementor).
			$out .= '<div class="rawnaq-bento-icon" aria-hidden="true">' . $cell['icon_html'] . '</div>';
		} elseif ( '' !== $cell['icon_class'] ) {
			$out .= '<div class="rawnaq-bento-icon" aria-hidden="true"><span class="dashicons ' . esc_attr( $cell['icon_class'] ) . '"></span></div>';
		}
		$out .= $title_html( $title ) . $sub_html( $subtitle ) . $cta_html();
	}

	// Link-only cell without a title line: an invisible stretched link with an accessible name.
	if ( $stretch && ! $state['stretched'] ) {
		$label = '' !== $title ? $title : ( '' !== $tag ? $tag : __( 'Open', 'rawnaq' ) );
		$out  .= '<a class="rawnaq-bento-stretch-link"' . rawnaq_bento_link_attrs( $link ) . ' aria-label="' . esc_attr( $label ) . '"></a>';
	}

	return $out . '</div>';
}

/**
 * Render the whole grid (stage wrapper + grid + cells + Review JSON-LD).
 *
 * @param array $cfg {
 *     @type string   $source      elementor|gutenberg|divi (how raw cells are mapped).
 *     @type int      $cols        Column count (data-cols).
 *     @type string   $theme       elevated|glass|obsidian|dark|spotlight.
 *     @type bool     $reveal      Scroll reveal.
 *     @type string   $hover       lift|zoom|tint|none.
 *     @type string[] $classes     Extra grid classes.
 *     @type string   $style       Inline style for the grid element.
 *     @type string   $title_tag   div|h2..h6.
 *     @type bool     $editor      Force editor behaviour (default: auto-detect).
 * }
 * @param array $cells Raw cells.
 * @return string HTML.
 */
function rawnaq_bento_markup( array $cfg, array $cells ) {
	$cfg = wp_parse_args(
		$cfg,
		[
			'source'    => 'elementor',
			'cols'      => 4,
			'theme'     => 'elevated',
			'reveal'    => true,
			'hover'     => 'lift',
			'classes'   => [],
			'style'     => '',
			'title_tag' => 'div',
			'editor'    => null,
		]
	);

	$theme = sanitize_key( (string) $cfg['theme'] );
	if ( ! in_array( $theme, [ 'elevated', 'glass', 'obsidian', 'dark', 'spotlight' ], true ) ) {
		$theme = 'elevated';
	}
	$hover = sanitize_key( (string) $cfg['hover'] );
	if ( ! in_array( $hover, [ 'lift', 'zoom', 'tint', 'none' ], true ) ) {
		$hover = 'lift';
	}
	$cols   = max( 2, min( 6, absint( $cfg['cols'] ) ?: 4 ) );
	$editor = null === $cfg['editor'] ? rawnaq_bento_is_editor_context() : (bool) $cfg['editor'];
	$ctx    = [
		'title_tag' => rawnaq_bento_title_tag( $cfg['title_tag'] ),
		'editor'    => $editor,
	];

	$classes = array_merge( [ 'rawnaq-bento-grid', 'is-theme-' . $theme ], array_map( 'sanitize_html_class', (array) $cfg['classes'] ) );

	$cells_html   = '';
	$testimonials = [];
	foreach ( array_values( $cells ) as $index => $raw ) {
		if ( ! is_array( $raw ) ) {
			continue;
		}
		$cell = rawnaq_bento_normalize_cell( $raw, (string) $cfg['source'] );
		if ( 'testimonial' === $cell['type'] && '' !== $cell['subtitle'] ) {
			$testimonials[] = [
				'body'   => $cell['subtitle'],
				'author' => $cell['title'],
				'rating' => $cell['rating'],
			];
		}
		$cells_html .= rawnaq_bento_render_cell( $cell, $index, $ctx );
	}

	$html  = '<div class="rawnaq-bento-stage">';
	$html .= '<div class="' . esc_attr( implode( ' ', $classes ) ) . '"';
	$html .= '' !== (string) $cfg['style'] ? ' style="' . esc_attr( (string) $cfg['style'] ) . '"' : '';
	$html .= ' data-cols="' . esc_attr( (string) $cols ) . '" data-theme="' . esc_attr( $theme ) . '"';
	$html .= ' data-reveal="' . ( $cfg['reveal'] ? '1' : '0' ) . '" data-hover="' . esc_attr( $hover ) . '" role="list">';
	$html .= $cells_html . '</div></div>';

	if ( ! $editor && $testimonials && function_exists( 'rawnaq_schema_print' ) && function_exists( 'rawnaq_schema_reviews' ) ) {
		ob_start();
		rawnaq_schema_print( rawnaq_schema_reviews( $testimonials ), 'review' );
		$html .= (string) ob_get_clean();
	}

	return $html;
}
