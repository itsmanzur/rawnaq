<?php
/**
 * Rawnaq JSON-LD schema pack.
 *
 * Builds structured data (schema.org) for content-bearing modules so search
 * engines can surface rich results. Every emitter is filterable and can be
 * globally disabled via the `rawnaq_enable_schema` filter.
 *
 * @package Rawnaq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether JSON-LD output is enabled.
 *
 * @param string $context Module context (case-study, timeline, review).
 * @return bool
 */
function rawnaq_schema_enabled( $context = '' ) {
	/**
	 * Toggle Rawnaq JSON-LD output.
	 *
	 * @param bool   $enabled Default true.
	 * @param string $context Module context.
	 */
	return (bool) apply_filters( 'rawnaq_enable_schema', true, $context );
}

/**
 * Safely print a JSON-LD graph.
 *
 * @param array  $data    Schema data (associative).
 * @param string $context Context for the enable filter.
 * @return void
 */
function rawnaq_schema_print( $data, $context = '' ) {
	if ( empty( $data ) || ! is_array( $data ) || ! rawnaq_schema_enabled( $context ) ) {
		return;
	}
	$json = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( ! $json ) {
		return;
	}
	// esc: JSON-LD must not have </script> break out; wp_json_encode escapes '/'? we kept slashes, so guard.
	$json = str_replace( '</', '<\/', $json );
	echo '<script type="application/ld+json">' . $json . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-LD, encoded + closing-tag guarded.
}

/**
 * Build an ItemList of CreativeWork entries for case-study projects.
 *
 * @param array $projects Normalized projects.
 * @return array JSON-LD graph or empty array.
 */
function rawnaq_schema_case_studies( $projects ) {
	if ( ! is_array( $projects ) || ! $projects ) {
		return [];
	}
	$elements = [];
	$position = 0;
	foreach ( $projects as $p ) {
		$title = trim( (string) ( $p['title'] ?? '' ) );
		if ( '' === $title ) {
			continue;
		}
		$position++;
		$work = [
			'@type'    => 'CreativeWork',
			'name'     => $title,
			'position' => $position,
		];
		$desc = trim( (string) ( $p['excerpt'] ?? $p['detail'] ?? '' ) );
		if ( $desc ) {
			$work['description'] = wp_strip_all_tags( $desc );
		}
		if ( ! empty( $p['image'] ) ) {
			$work['image'] = esc_url_raw( $p['image'] );
		}
		if ( ! empty( $p['link'] ) ) {
			$work['url'] = esc_url_raw( $p['link'] );
		}
		if ( ! empty( $p['sector'] ) ) {
			$work['genre'] = wp_strip_all_tags( (string) $p['sector'] );
		}
		if ( ! empty( $p['client'] ) ) {
			$work['sourceOrganization'] = [
				'@type' => 'Organization',
				'name'  => wp_strip_all_tags( (string) $p['client'] ),
			];
		}
		if ( ! empty( $p['year'] ) && preg_match( '/\d{4}/', (string) $p['year'], $m ) ) {
			$work['dateCreated'] = $m[0];
		}
		$elements[] = [
			'@type'    => 'ListItem',
			'position' => $position,
			'item'     => $work,
		];
	}
	if ( ! $elements ) {
		return [];
	}
	$graph = [
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'itemListElement' => $elements,
	];
	/**
	 * Filter case-study JSON-LD.
	 *
	 * @param array $graph    Schema graph.
	 * @param array $projects Source projects.
	 */
	return apply_filters( 'rawnaq_schema_case_studies', $graph, $projects );
}

/**
 * Build an ItemList for scroll-timeline steps (milestones / changelog / events).
 *
 * @param array $steps Steps with meta/title/desc.
 * @return array
 */
function rawnaq_schema_timeline( $steps ) {
	if ( ! is_array( $steps ) || ! $steps ) {
		return [];
	}
	$elements = [];
	$position = 0;
	foreach ( $steps as $step ) {
		$title = trim( (string) ( $step['title'] ?? '' ) );
		if ( '' === $title ) {
			continue;
		}
		$position++;
		$item = [
			'@type'    => 'ListItem',
			'position' => $position,
			'name'     => $title,
		];
		$desc = trim( (string) ( $step['desc'] ?? '' ) );
		if ( $desc ) {
			$item['description'] = wp_strip_all_tags( $desc );
		}
		$elements[] = $item;
	}
	if ( ! $elements ) {
		return [];
	}
	$graph = [
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'itemListElement' => $elements,
	];
	/**
	 * Filter timeline JSON-LD.
	 *
	 * @param array $graph Schema graph.
	 * @param array $steps Source steps.
	 */
	return apply_filters( 'rawnaq_schema_timeline', $graph, $steps );
}

/**
 * Build Review / AggregateRating JSON-LD from testimonial-like items.
 *
 * @param array  $reviews Each: [ body, author, rating (0-5) ].
 * @param string $item_name Reviewed item name (defaults to site name).
 * @return array
 */
function rawnaq_schema_reviews( $reviews, $item_name = '' ) {
	if ( ! is_array( $reviews ) || ! $reviews ) {
		return [];
	}
	$item_name = $item_name ?: wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$list      = [];
	$sum       = 0;
	$rated     = 0;
	foreach ( $reviews as $r ) {
		$body = trim( (string) ( $r['body'] ?? '' ) );
		if ( '' === $body ) {
			continue;
		}
		$entry = [
			'@type'        => 'Review',
			'reviewBody'   => wp_strip_all_tags( $body ),
			'itemReviewed' => [ '@type' => 'Thing', 'name' => $item_name ],
		];
		if ( ! empty( $r['author'] ) ) {
			$entry['author'] = [ '@type' => 'Person', 'name' => wp_strip_all_tags( (string) $r['author'] ) ];
		}
		$rating = isset( $r['rating'] ) ? (float) $r['rating'] : 0;
		if ( $rating > 0 ) {
			$entry['reviewRating'] = [
				'@type'       => 'Rating',
				'ratingValue' => $rating,
				'bestRating'  => 5,
			];
			$sum += $rating;
			$rated++;
		}
		$list[] = $entry;
	}
	if ( ! $list ) {
		return [];
	}
	$graph = [
		'@context' => 'https://schema.org',
		'@type'    => 'Product',
		'name'     => $item_name,
		'review'   => $list,
	];
	if ( $rated > 0 ) {
		$graph['aggregateRating'] = [
			'@type'       => 'AggregateRating',
			'ratingValue' => round( $sum / $rated, 2 ),
			'reviewCount' => $rated,
			'bestRating'  => 5,
		];
	}
	/**
	 * Filter review JSON-LD.
	 *
	 * @param array $graph   Schema graph.
	 * @param array $reviews Source reviews.
	 */
	return apply_filters( 'rawnaq_schema_reviews', $graph, $reviews );
}
