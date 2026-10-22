<?php
/**
 * Project Case-Study Grid — CPT, admin meta, query helpers + markup.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the `rawnaq_case_study` CPT, its sector taxonomy, and post meta.
 */
function rawnaq_case_study_register_cpt() {
	if ( ! post_type_exists( 'rawnaq_case_study' ) ) {
		register_post_type(
			'rawnaq_case_study',
			[
				'labels'          => [
					'name'               => __( 'Case Studies', 'rawnaq' ),
					'singular_name'      => __( 'Case Study', 'rawnaq' ),
					'add_new'            => __( 'Add New', 'rawnaq' ),
					'add_new_item'       => __( 'Add New Case Study', 'rawnaq' ),
					'edit_item'          => __( 'Edit Case Study', 'rawnaq' ),
					'new_item'           => __( 'New Case Study', 'rawnaq' ),
					'view_item'          => __( 'View Case Study', 'rawnaq' ),
					'view_items'         => __( 'View Case Studies', 'rawnaq' ),
					'search_items'       => __( 'Search Case Studies', 'rawnaq' ),
					'not_found'          => __( 'No case studies found.', 'rawnaq' ),
					'not_found_in_trash' => __( 'No case studies found in Trash.', 'rawnaq' ),
					'all_items'          => __( 'All Case Studies', 'rawnaq' ),
					'archives'           => __( 'Case Study Archives', 'rawnaq' ),
					'menu_name'          => __( 'Case Studies', 'rawnaq' ),
				],
				'public'          => true,
				'has_archive'     => true,
				'show_in_rest'    => true,
				'show_ui'         => true,
				'show_in_menu'    => 'rawnaq',
				'menu_icon'       => 'dashicons-portfolio',
				'supports'        => [ 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ],
				'rewrite'         => [ 'slug' => 'case-study' ],
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			]
		);
	}

	if ( ! taxonomy_exists( 'rawnaq_cs_sector' ) ) {
		register_taxonomy(
			'rawnaq_cs_sector',
			'rawnaq_case_study',
			[
				'labels'            => [
					'name'          => __( 'Sectors', 'rawnaq' ),
					'singular_name' => __( 'Sector', 'rawnaq' ),
					'search_items'  => __( 'Search Sectors', 'rawnaq' ),
					'all_items'     => __( 'All Sectors', 'rawnaq' ),
					'edit_item'     => __( 'Edit Sector', 'rawnaq' ),
					'update_item'   => __( 'Update Sector', 'rawnaq' ),
					'add_new_item'  => __( 'Add New Sector', 'rawnaq' ),
					'new_item_name' => __( 'New Sector Name', 'rawnaq' ),
					'menu_name'     => __( 'Sectors', 'rawnaq' ),
				],
				'public'            => true,
				'show_in_rest'      => true,
				'hierarchical'      => false,
				'show_admin_column' => true,
				'rewrite'           => [ 'slug' => 'case-study-sector' ],
			]
		);
	}

	$string_meta = [
		'_rawnaq_cs_size',
		'_rawnaq_cs_budget',
		'_rawnaq_cs_year',
		'_rawnaq_cs_client',
		'_rawnaq_cs_services',
		'_rawnaq_cs_gallery',
		'_rawnaq_cs_featured',
	];
	foreach ( $string_meta as $meta_key ) {
		register_post_meta(
			'rawnaq_case_study',
			$meta_key,
			[
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_textarea_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			]
		);
	}

	$int_meta = [ '_rawnaq_cs_col', '_rawnaq_cs_row' ];
	foreach ( $int_meta as $meta_key ) {
		register_post_meta(
			'rawnaq_case_study',
			$meta_key,
			[
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			]
		);
	}
}

/**
 * Register the details meta box on the Case Study edit screen.
 */
function rawnaq_case_study_add_meta_boxes() {
	add_meta_box(
		'rawnaq_case_study_details',
		__( 'Case Study Details', 'rawnaq' ),
		'rawnaq_case_study_render_meta_box',
		'rawnaq_case_study',
		'normal',
		'high'
	);
}

/**
 * Render the case-study details meta box.
 *
 * @param WP_Post $post Current post.
 */
function rawnaq_case_study_render_meta_box( $post ) {
	wp_nonce_field( 'rawnaq_case_study_save_meta', 'rawnaq_case_study_nonce' );

	$size     = get_post_meta( $post->ID, '_rawnaq_cs_size', true );
	$budget   = get_post_meta( $post->ID, '_rawnaq_cs_budget', true );
	$year     = get_post_meta( $post->ID, '_rawnaq_cs_year', true );
	$client   = get_post_meta( $post->ID, '_rawnaq_cs_client', true );
	$services = get_post_meta( $post->ID, '_rawnaq_cs_services', true );
	$gallery  = get_post_meta( $post->ID, '_rawnaq_cs_gallery', true );
	$featured = get_post_meta( $post->ID, '_rawnaq_cs_featured', true );
	$col      = absint( get_post_meta( $post->ID, '_rawnaq_cs_col', true ) );
	$row      = absint( get_post_meta( $post->ID, '_rawnaq_cs_row', true ) );
	?>
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row"><label for="rawnaq_cs_size"><?php esc_html_e( 'Size / Scope', 'rawnaq' ); ?></label></th>
				<td><input type="text" class="regular-text" id="rawnaq_cs_size" name="rawnaq_cs_size" value="<?php echo esc_attr( $size ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="rawnaq_cs_budget"><?php esc_html_e( 'Budget Range', 'rawnaq' ); ?></label></th>
				<td>
					<input type="text" class="regular-text" id="rawnaq_cs_budget" name="rawnaq_cs_budget" value="<?php echo esc_attr( $budget ); ?>" />
					<p class="description"><?php esc_html_e( 'Leave blank for NDA projects — use the widget "Hide budget" toggle to suppress this field on the frontend.', 'rawnaq' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="rawnaq_cs_year"><?php esc_html_e( 'Year Completed', 'rawnaq' ); ?></label></th>
				<td><input type="text" class="regular-text" id="rawnaq_cs_year" name="rawnaq_cs_year" value="<?php echo esc_attr( $year ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="rawnaq_cs_client"><?php esc_html_e( 'Client', 'rawnaq' ); ?></label></th>
				<td><input type="text" class="regular-text" id="rawnaq_cs_client" name="rawnaq_cs_client" value="<?php echo esc_attr( $client ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="rawnaq_cs_services"><?php esc_html_e( 'Services Provided', 'rawnaq' ); ?></label></th>
				<td>
					<input type="text" class="large-text" id="rawnaq_cs_services" name="rawnaq_cs_services" value="<?php echo esc_attr( $services ); ?>" />
					<p class="description"><?php esc_html_e( 'Comma-separated, e.g. Architecture, Structural, MEP.', 'rawnaq' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="rawnaq_cs_gallery"><?php esc_html_e( 'Gallery URLs', 'rawnaq' ); ?></label></th>
				<td>
					<textarea class="large-text code" rows="6" id="rawnaq_cs_gallery" name="rawnaq_cs_gallery"><?php echo esc_textarea( $gallery ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One image URL per line. These appear in the detail-modal slider alongside the featured image.', 'rawnaq' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Featured', 'rawnaq' ); ?></th>
				<td>
					<label for="rawnaq_cs_featured">
						<input type="checkbox" id="rawnaq_cs_featured" name="rawnaq_cs_featured" value="1" <?php checked( '1', $featured ); ?> />
						<?php esc_html_e( 'Show as a larger, featured card in the bento layout', 'rawnaq' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="rawnaq_cs_col"><?php esc_html_e( 'Bento Column Span', 'rawnaq' ); ?></label></th>
				<td><input type="number" min="1" max="4" class="small-text" id="rawnaq_cs_col" name="rawnaq_cs_col" value="<?php echo esc_attr( $col ? (string) $col : '1' ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="rawnaq_cs_row"><?php esc_html_e( 'Bento Row Span', 'rawnaq' ); ?></label></th>
				<td><input type="number" min="1" max="3" class="small-text" id="rawnaq_cs_row" name="rawnaq_cs_row" value="<?php echo esc_attr( $row ? (string) $row : '1' ); ?>" /></td>
			</tr>
		</tbody>
	</table>
	<?php
}

/**
 * Persist case-study meta fields.
 *
 * @param int $post_id Post ID.
 */
function rawnaq_case_study_save_meta( $post_id ) {
	if ( ! isset( $_POST['rawnaq_case_study_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rawnaq_case_study_nonce'] ) ), 'rawnaq_case_study_save_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( 'rawnaq_case_study' !== get_post_type( $post_id ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$text_fields = [
		'rawnaq_cs_size'     => '_rawnaq_cs_size',
		'rawnaq_cs_budget'   => '_rawnaq_cs_budget',
		'rawnaq_cs_year'     => '_rawnaq_cs_year',
		'rawnaq_cs_client'   => '_rawnaq_cs_client',
		'rawnaq_cs_services' => '_rawnaq_cs_services',
	];
	foreach ( $text_fields as $field => $meta_key ) {
		if ( isset( $_POST[ $field ] ) ) {
			update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
		}
	}

	if ( isset( $_POST['rawnaq_cs_gallery'] ) ) {
		update_post_meta( $post_id, '_rawnaq_cs_gallery', sanitize_textarea_field( wp_unslash( $_POST['rawnaq_cs_gallery'] ) ) );
	}

	update_post_meta( $post_id, '_rawnaq_cs_featured', ! empty( $_POST['rawnaq_cs_featured'] ) ? '1' : '' );

	$col = isset( $_POST['rawnaq_cs_col'] ) ? absint( $_POST['rawnaq_cs_col'] ) : 1;
	$row = isset( $_POST['rawnaq_cs_row'] ) ? absint( $_POST['rawnaq_cs_row'] ) : 1;
	update_post_meta( $post_id, '_rawnaq_cs_col', max( 1, min( 4, $col ?: 1 ) ) );
	update_post_meta( $post_id, '_rawnaq_cs_row', max( 1, min( 3, $row ?: 1 ) ) );
}

/**
 * Default sample projects for editors.
 *
 * @return array<int, array<string, mixed>>
 */
function rawnaq_case_study_sample_projects() {
	return [
		[
			'title'    => __( 'Fintech Global App & Dashboard Redesign', 'rawnaq' ),
			'image'    => '',
			'gallery'  => [],
			'link'     => '',
			'sector'   => __( 'Fintech & SaaS', 'rawnaq' ),
			'size'     => '+140% Conversion · $85M Vol',
			'budget'   => '$85M Volume',
			'year'     => '2025',
			'client'   => __( 'Stripe & Apex Financial', 'rawnaq' ),
			'services' => __( 'Product Strategy, UI/UX, Design System', 'rawnaq' ),
			'excerpt'  => __( 'A high-performance financial operational canvas unifying multi-currency settlement and real-time risk intelligence.', 'rawnaq' ),
			'detail'   => __( 'End-to-end product overhaul including design system architecture, sub-50ms streaming transaction tables, and tactile mobile workflows across iOS and Android.', 'rawnaq' ),
			'featured' => true,
			'col'      => 2,
			'row'      => 2,
		],
		[
			'title'    => __( 'AI-Powered Medical Intelligence Platform', 'rawnaq' ),
			'image'    => '',
			'gallery'  => [],
			'link'     => '',
			'sector'   => __( 'HealthTech & AI', 'rawnaq' ),
			'size'     => '1.2M Records · 99.9% Uptime',
			'budget'   => 'Enterprise Scope',
			'year'     => '2025',
			'client'   => __( 'BioHealth Labs', 'rawnaq' ),
			'services' => __( 'Cloud Architecture, Web App, AI Models', 'rawnaq' ),
			'excerpt'  => __( 'HIPAA-compliant diagnostic dashboard for rapid multi-modal patient record synthesis and risk prediction.', 'rawnaq' ),
			'detail'   => __( 'Built with zero-drift modular web components and real-time HL7/FHIR event ingestion, reducing diagnosis lookup from minutes to seconds.', 'rawnaq' ),
			'featured' => false,
			'col'      => 1,
			'row'      => 1,
		],
		[
			'title'    => __( 'Autonomous Mobility & Fleet Telemetry', 'rawnaq' ),
			'image'    => '',
			'gallery'  => [],
			'link'     => '',
			'sector'   => __( 'IoT & Automotive', 'rawnaq' ),
			'size'     => '450k Connected Vehicles',
			'budget'   => '$32M Scope',
			'year'     => '2024',
			'client'   => __( 'Nova Transit Network', 'rawnaq' ),
			'services' => __( 'Real-time Telemetry, Mobile App', 'rawnaq' ),
			'excerpt'  => __( 'Unified command center coordinating connected EV fleets, route optimization, and predictive charging.', 'rawnaq' ),
			'detail'   => __( 'Engineered high-concurrency WebSocket pipelines with live geospatial map clusters and automated charge-scheduling algorithms.', 'rawnaq' ),
			'featured' => false,
			'col'      => 1,
			'row'      => 1,
		],
		[
			'title'    => __( 'Luxury Sustainable Fashion Flagship', 'rawnaq' ),
			'image'    => '',
			'gallery'  => [],
			'link'     => '',
			'sector'   => __( 'E-Commerce & Brand', 'rawnaq' ),
			'size'     => '+320% Growth · 18 Markets',
			'budget'   => 'Global Flagship',
			'year'     => '2024',
			'client'   => __( 'Maison Aurelia', 'rawnaq' ),
			'services' => __( 'Headless Shopify, Brand Identity', 'rawnaq' ),
			'excerpt'  => __( 'Immersive headless commerce platform delivering tactile 3D product previews and instant checkout.', 'rawnaq' ),
			'detail'   => __( 'Architected with global edge caching and WebGL product interactions, driving an 84% reduction in page load time and +320% global sales growth.', 'rawnaq' ),
			'featured' => false,
			'col'      => 1,
			'row'      => 1,
		],
	];
}

/**
 * Parse a gallery value (array of URLs or attachment objects/IDs, JSON string, or newline/comma separated URLs) into a clean URL list.
 *
 * @param mixed $raw Raw gallery value.
 * @return string[]
 */
function rawnaq_case_study_parse_gallery( $raw ) {
	if ( is_array( $raw ) ) {
		$items = $raw;
	} elseif ( is_string( $raw ) && '' !== trim( $raw ) ) {
		$trimmed = trim( $raw );
		$decoded = json_decode( $trimmed, true );
		if ( is_array( $decoded ) ) {
			$items = $decoded;
		} else {
			$items = preg_split( '/[\r\n,]+/', $trimmed, -1, PREG_SPLIT_NO_EMPTY );
			$items = is_array( $items ) ? $items : [];
		}
	} else {
		$items = [];
	}

	$urls = [];
	foreach ( $items as $item ) {
		$url = '';
		if ( is_array( $item ) ) {
			if ( ! empty( $item['url'] ) ) {
				$url = (string) $item['url'];
			} elseif ( ! empty( $item['id'] ) && function_exists( 'wp_get_attachment_url' ) ) {
				$url = wp_get_attachment_url( (int) $item['id'] );
			} elseif ( ! empty( $item['src'] ) ) {
				$url = (string) $item['src'];
			}
		} elseif ( is_numeric( $item ) && (int) $item > 0 && function_exists( 'wp_get_attachment_url' ) ) {
			$url = wp_get_attachment_url( (int) $item );
		} elseif ( is_string( $item ) ) {
			$url = $item;
		}
		if ( $url && is_string( $url ) ) {
			$clean = esc_url_raw( trim( $url ) );
			if ( $clean && ! in_array( $clean, [ 'Array', '[object Object]' ], true ) ) {
				$urls[] = $clean;
			}
		}
	}

	return array_values( array_unique( array_filter( $urls ) ) );
}

/**
 * Normalize project list from builder config.
 *
 * @param array $projects Raw projects.
 * @return array<int, array<string, mixed>>
 */
function rawnaq_case_study_normalize_projects( $projects ) {
	$out = [];
	if ( ! is_array( $projects ) ) {
		return $out;
	}
	foreach ( $projects as $i => $p ) {
		if ( ! is_array( $p ) ) {
			continue;
		}
		$services_raw = $p['services'] ?? '';
		if ( is_array( $services_raw ) ) {
			$services = array_values( array_filter( array_map( 'sanitize_text_field', $services_raw ) ) );
		} else {
			$parts    = array_map( 'trim', explode( ',', (string) $services_raw ) );
			$services = array_values( array_filter( array_map( 'sanitize_text_field', $parts ) ) );
		}
		$featured = ! empty( $p['featured'] ) && 'no' !== $p['featured'] && 'false' !== $p['featured'];
		$col      = absint( $p['col'] ?? 0 );
		$row      = absint( $p['row'] ?? 0 );
		if ( $col < 1 ) {
			$col = $featured ? 2 : 1;
		}
		if ( $row < 1 ) {
			$row = $featured ? 2 : 1;
		}
		$col = max( 1, min( 4, $col ) );
		$row = max( 1, min( 3, $row ) );

		$title = sanitize_text_field( $p['title'] ?? '' );
		if ( ! $title ) {
			$title = sprintf( /* translators: %d: project index */ __( 'Project %d', 'rawnaq' ), $i + 1 );
		}

		$image   = esc_url_raw( $p['image'] ?? ( $p['imageUrl'] ?? '' ) );
		$gallery = rawnaq_case_study_parse_gallery( $p['gallery'] ?? [] );
		if ( $image && ! in_array( $image, $gallery, true ) ) {
			array_unshift( $gallery, $image );
		}

		$custom_id = sanitize_text_field( (string) ( $p['id'] ?? '' ) );
		$post_id   = absint( $p['postId'] ?? ( $p['post_id'] ?? 0 ) );
		$slug      = sanitize_title( (string) ( $p['slug'] ?? '' ) );

		$out[] = [
			'id'       => $custom_id ? $custom_id : ( 'p' . ( $i + 1 ) ),
			'postId'   => $post_id,
			'slug'     => $slug,
			'title'    => $title,
			'image'    => $image,
			'gallery'  => $gallery,
			'link'     => esc_url_raw( $p['link'] ?? ( $p['url'] ?? '' ) ),
			'sector'     => sanitize_text_field( $p['sector'] ?? '' ),
			'sectorSlug' => sanitize_title( (string) ( $p['sectorSlug'] ?? '' ) ),
			'size'     => sanitize_text_field( $p['size'] ?? '' ),
			'budget'   => sanitize_text_field( $p['budget'] ?? '' ),
			'year'     => sanitize_text_field( $p['year'] ?? '' ),
			'client'   => sanitize_text_field( $p['client'] ?? '' ),
			'services' => $services,
			'excerpt'  => sanitize_textarea_field( $p['excerpt'] ?? '' ),
			'detail'   => sanitize_textarea_field( $p['detail'] ?? '' ),
			'featured' => $featured,
			'col'      => $col,
			'row'      => $row,
		];
	}
	return $out;
}

/**
 * Build a normalized project array from a `rawnaq_case_study` post.
 *
 * @param int|WP_Post $post Post or post ID.
 * @return array<string, mixed>|null
 */
function rawnaq_case_study_project_from_post( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}
	$post_id = $post->ID;

	$is_pw_required = post_password_required( $post );

	$sector      = '';
	$sector_slug = '';
	$terms       = get_the_terms( $post_id, 'rawnaq_cs_sector' );
	if ( is_array( $terms ) && ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		$sector      = $terms[0]->name;
		$sector_slug = $terms[0]->slug;
	}

	$services_raw = get_post_meta( $post_id, '_rawnaq_cs_services', true );
	$services     = array_values( array_filter( array_map( 'trim', explode( ',', (string) $services_raw ) ) ) );

	$image = get_the_post_thumbnail_url( $post_id, 'large' );
	$image = $image ? $image : '';

	$gallery = rawnaq_case_study_parse_gallery( get_post_meta( $post_id, '_rawnaq_cs_gallery', true ) );
	if ( $image && ! in_array( $image, $gallery, true ) ) {
		array_unshift( $gallery, $image );
	}

	if ( $is_pw_required ) {
		$excerpt = '';
		$detail  = '';
	} else {
		$excerpt = has_excerpt( $post_id )
			? get_the_excerpt( $post_id )
			: wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 24 );
		$detail  = wp_trim_words( wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) ), 120, '…' );
	}

	$featured = '1' === (string) get_post_meta( $post_id, '_rawnaq_cs_featured', true );
	$col      = absint( get_post_meta( $post_id, '_rawnaq_cs_col', true ) );
	$row      = absint( get_post_meta( $post_id, '_rawnaq_cs_row', true ) );
	if ( $col < 1 ) {
		$col = $featured ? 2 : 1;
	}
	if ( $row < 1 ) {
		$row = $featured ? 2 : 1;
	}

	return [
		'id'         => 'post-' . $post_id,
		'postId'     => $post_id,
		'slug'       => sanitize_title( (string) $post->post_name ),
		'title'      => get_the_title( $post_id ),
		'image'      => $image,
		'gallery'    => $gallery,
		'link'       => get_permalink( $post_id ),
		'sector'     => sanitize_text_field( $sector ),
		'sectorSlug' => sanitize_title( $sector_slug ),
		'size'       => sanitize_text_field( (string) get_post_meta( $post_id, '_rawnaq_cs_size', true ) ),
		'budget'     => sanitize_text_field( (string) get_post_meta( $post_id, '_rawnaq_cs_budget', true ) ),
		'year'       => sanitize_text_field( (string) get_post_meta( $post_id, '_rawnaq_cs_year', true ) ),
		'client'     => sanitize_text_field( (string) get_post_meta( $post_id, '_rawnaq_cs_client', true ) ),
		'services'   => $services,
		'excerpt'    => wp_strip_all_tags( (string) $excerpt ),
		'detail'     => $detail,
		'featured'   => $featured,
		'col'        => max( 1, min( 4, $col ) ),
		'row'        => max( 1, min( 3, $row ) ),
	];
}

/**
 * Query published `rawnaq_case_study` posts and return normalized projects.
 *
 * @param array $args {
 *     @type int    $posts_per_page Number to fetch, -1 for all. Default 12.
 *     @type string $orderby        WP_Query orderby key. Default 'date'.
 *     @type string $order          ASC|DESC. Default 'DESC'.
 *     @type string $sector         Sector term slug to filter by. Optional.
 * }
 * @return array{projects: array<int, array<string, mixed>>, found: int}
 */
function rawnaq_case_study_query_projects( $args = [] ) {
	$args = is_array( $args ) ? $args : [];

	$per_page = isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : 12;
	$per_page = $per_page > 0 ? $per_page : ( $per_page < 0 ? -1 : 12 );

	$orderby = sanitize_key( $args['orderby'] ?? 'date' );
	if ( ! in_array( $orderby, [ 'date', 'title', 'menu_order', 'rand', 'modified' ], true ) ) {
		$orderby = 'date';
	}
	if ( 'rand' === $orderby && isset( $args['paged'] ) && (int) $args['paged'] > 1 ) {
		$orderby = 'date';
	}
	$order = ( 'ASC' === strtoupper( (string) ( $args['order'] ?? 'DESC' ) ) ) ? 'ASC' : 'DESC';
	$paged = isset( $args['paged'] ) ? max( 1, (int) $args['paged'] ) : 1;

	$query_args = [
		'post_type'           => 'rawnaq_case_study',
		'post_status'         => 'publish',
		'posts_per_page'      => $per_page,
		'orderby'             => $orderby,
		'order'               => $order,
		'has_password'        => false,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => false,
	];
	if ( $per_page > 0 ) {
		$query_args['paged'] = $paged;
	}

	$sector = sanitize_title( (string) ( $args['sector'] ?? '' ) );
	if ( $sector && taxonomy_exists( 'rawnaq_cs_sector' ) ) {
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- optional sector filter for a bounded CPT query.
		$query_args['tax_query'] = [
			[
				'taxonomy' => 'rawnaq_cs_sector',
				'field'    => 'slug',
				'terms'    => $sector,
			],
		];
	}

	// Optional server-side year (exact meta) + service (LIKE meta) filters.
	$meta_query = [];
	$year       = sanitize_text_field( (string) ( $args['year'] ?? '' ) );
	if ( '' !== $year ) {
		$meta_query[] = [
			'key'     => '_rawnaq_cs_year',
			'value'   => $year,
			'compare' => '=',
		];
	}
	$service = sanitize_text_field( (string) ( $args['service'] ?? '' ) );
	if ( '' !== $service ) {
		$meta_query[] = [
			'key'     => '_rawnaq_cs_services',
			'value'   => $service,
			'compare' => 'LIKE',
		];
	}
	if ( $meta_query ) {
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- optional facet filters for a bounded CPT query.
		$query_args['meta_query'] = $meta_query;
	}

	$q        = new WP_Query( $query_args );
	$projects = [];
	if ( $q->have_posts() ) {
		foreach ( $q->posts as $post ) {
			$project = rawnaq_case_study_project_from_post( $post );
			if ( $project ) {
				$projects[] = $project;
			}
		}
	}
	wp_reset_postdata();

	return [
		'projects'    => $projects,
		'found'       => (int) $q->found_posts,
		'maxPages'    => (int) $q->max_num_pages,
		'page'        => $paged,
	];
}

/**
 * Unique facet values from normalized projects (preserve first-seen order; years sort newest-first).
 *
 * @param array  $projects Normalized projects.
 * @param string $key      sector|year|services.
 * @return string[]
 */
function rawnaq_case_study_facets( $projects, $key ) {
	$key = sanitize_key( $key );
	if ( ! is_array( $projects ) || ! in_array( $key, [ 'sector', 'year', 'services' ], true ) ) {
		return [];
	}

	$seen = [];
	foreach ( $projects as $p ) {
		if ( 'services' === $key ) {
			$values = is_array( $p['services'] ?? null ) ? $p['services'] : [];
		} else {
			$values = [ (string) ( $p[ $key ] ?? '' ) ];
		}
		foreach ( $values as $v ) {
			$v = trim( (string) $v );
			if ( '' !== $v && ! isset( $seen[ $v ] ) ) {
				$seen[ $v ] = $v;
			}
		}
	}

	$out = array_values( $seen );
	if ( 'year' === $key ) {
		usort(
			$out,
			static function ( $a, $b ) {
				return strcmp( $b, $a );
			}
		);
	}
	return $out;
}

/**
 * Sort projects for initial DOM order.
 *
 * @param array  $projects Projects.
 * @param string $sort     custom|year_desc|sector.
 * @return array
 */
function rawnaq_case_study_sort_projects( $projects, $sort ) {
	$sort = sanitize_key( $sort );
	if ( 'year_desc' === $sort ) {
		usort(
			$projects,
			static function ( $a, $b ) {
				return strcmp( (string) ( $b['year'] ?? '' ), (string) ( $a['year'] ?? '' ) );
			}
		);
	} elseif ( 'sector' === $sort ) {
		usort(
			$projects,
			static function ( $a, $b ) {
				$c = strcasecmp( (string) ( $a['sector'] ?? '' ), (string) ( $b['sector'] ?? '' ) );
				if ( 0 !== $c ) {
					return $c;
				}
				return strcasecmp( (string) ( $a['title'] ?? '' ), (string) ( $b['title'] ?? '' ) );
			}
		);
	}
	return $projects;
}

/**
 * Resolve the final project list for a builder config: query-mode (CPT) vs manual (repeater),
 * always falling back to sample projects when a manual list resolves empty.
 *
 * @param array $cfg Builder/widget config.
 * @return array<int, array<string, mixed>>
 */
function rawnaq_case_study_resolve_projects( $cfg ) {
	$cfg    = is_array( $cfg ) ? $cfg : [];
	$source = sanitize_key( $cfg['source'] ?? 'manual' );

	if ( 'query' === $source ) {
		$result   = rawnaq_case_study_query_projects(
			[
				'posts_per_page' => $cfg['queryNumber'] ?? 12,
				'orderby'        => $cfg['queryOrderby'] ?? 'date',
				'order'          => $cfg['queryOrder'] ?? 'DESC',
				'sector'         => $cfg['querySector'] ?? '',
			]
		);
		$projects = $result['projects'];
		if ( ! $projects ) {
			$projects = rawnaq_case_study_normalize_projects( rawnaq_case_study_sample_projects() );
		}
		return $projects;
	}

	$projects = rawnaq_case_study_normalize_projects( $cfg['projects'] ?? [] );
	if ( ! $projects ) {
		$projects = rawnaq_case_study_normalize_projects( rawnaq_case_study_sample_projects() );
	}
	return $projects;
}

/**
 * Bump the facets cache version on case study CPT/term updates.
 *
 * @return void
 */
function rawnaq_case_study_bump_facets_version() {
	$ver = (int) get_option( 'rawnaq_cs_facets_ver', 1 );
	update_option( 'rawnaq_cs_facets_ver', $ver + 1, false );
}
add_action( 'save_post_rawnaq_case_study', 'rawnaq_case_study_bump_facets_version' );
add_action( 'deleted_post', static function ( $post_id ) {
	if ( 'rawnaq_case_study' === get_post_type( $post_id ) ) {
		rawnaq_case_study_bump_facets_version();
	}
} );
add_action( 'set_object_terms', static function ( $object_id, $terms, $tt_ids, $taxonomy ) {
	if ( 'rawnaq_cs_sector' === $taxonomy ) {
		rawnaq_case_study_bump_facets_version();
	}
}, 10, 4 );
add_action( 'edited_rawnaq_cs_sector', 'rawnaq_case_study_bump_facets_version' );
add_action( 'created_rawnaq_cs_sector', 'rawnaq_case_study_bump_facets_version' );
add_action( 'delete_rawnaq_cs_sector', 'rawnaq_case_study_bump_facets_version' );

/**
 * Query all matching published CPT posts to compute global facet filter lists.
 *
 * @param string $querySector Optional sector term slug constraint.
 * @return array{sectors: array<int, array{key: string, label: string}>, years: string[], services: string[]}
 */
function rawnaq_case_study_query_facets( $querySector = '' ) {
	$query_sector = sanitize_title( (string) $querySector );
	$ver          = (int) get_option( 'rawnaq_cs_facets_ver', 1 );
	$cache_key    = 'rq_cs_fct_' . md5( $query_sector . '|' . $ver );
	$cached       = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$query_args = [
		'post_type'           => 'rawnaq_case_study',
		'post_status'         => 'publish',
		'posts_per_page'      => 300,
		'has_password'        => false,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'fields'              => 'ids',
	];
	if ( $query_sector && taxonomy_exists( 'rawnaq_cs_sector' ) ) {
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		$query_args['tax_query'] = [
			[
				'taxonomy' => 'rawnaq_cs_sector',
				'field'    => 'slug',
				'terms'    => $query_sector,
			],
		];
	}

	$q        = new WP_Query( $query_args );
	$post_ids = is_array( $q->posts ) ? $q->posts : [];
	wp_reset_postdata();

	if ( ! empty( $post_ids ) && function_exists( 'update_postmeta_cache' ) ) {
		update_postmeta_cache( $post_ids );
	}

	$sectors      = [];
	$seen_sectors = [];
	if ( ! empty( $post_ids ) && taxonomy_exists( 'rawnaq_cs_sector' ) ) {
		$terms = wp_get_object_terms( $post_ids, 'rawnaq_cs_sector' );
		if ( is_array( $terms ) && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( ! isset( $seen_sectors[ $term->slug ] ) ) {
					$seen_sectors[ $term->slug ] = true;
					$sectors[]                   = [
						'key'   => $term->slug,
						'label' => $term->name,
					];
				}
			}
		}
	}

	$seen_years    = [];
	$seen_services = [];
	$years         = [];
	$services      = [];

	foreach ( $post_ids as $pid ) {
		$y = trim( (string) get_post_meta( $pid, '_rawnaq_cs_year', true ) );
		if ( '' !== $y && ! isset( $seen_years[ $y ] ) ) {
			$seen_years[ $y ] = true;
			$years[]          = $y;
		}
		$s_raw   = get_post_meta( $pid, '_rawnaq_cs_services', true );
		$s_parts = array_filter( array_map( 'trim', explode( ',', (string) $s_raw ) ) );
		foreach ( $s_parts as $sp ) {
			if ( '' !== $sp && ! isset( $seen_services[ $sp ] ) ) {
				$seen_services[ $sp ] = true;
				$services[]           = $sp;
			}
		}
	}

	usort(
		$years,
		static function ( $a, $b ) {
			return strcmp( $b, $a );
		}
	);

	$facets = [
		'sectors'  => $sectors,
		'years'    => $years,
		'services' => $services,
	];

	set_transient( $cache_key, $facets, 12 * HOUR_IN_SECONDS );
	return $facets;
}

/**
 * Base64url encode without padding.
 *
 * @param string $data Data to encode.
 * @return string
 */
function rawnaq_case_study_base64url_encode( $data ) {
	return rtrim( strtr( base64_encode( (string) $data ), '+/', '-_' ), '=' );
}

/**
 * Base64url decode with padding restoration.
 *
 * @param string $data Data to decode.
 * @return string|false
 */
function rawnaq_case_study_base64url_decode( $data ) {
	$data = (string) $data;
	$rem  = strlen( $data ) % 4;
	if ( $rem ) {
		$data .= str_repeat( '=', 4 - $rem );
	}
	return base64_decode( strtr( $data, '-_', '+/' ) );
}

/**
 * Sanitize CSS color values strictly: #hex (3/4/6/8), rgb(), rgba(), hsl(), hsla(),
 * var(--name), transparent, currentColor.
 * Reject ; { } < > " ' url( expression( @import or backslashes.
 *
 * @param mixed  $value   Input CSS color string.
 * @param string $default Fallback value if invalid.
 * @return string Valid CSS color string or $default.
 */
function rawnaq_cs_sanitize_css_color( $value, $default = '' ) {
	if ( ! is_string( $value ) ) {
		return $default;
	}
	$value = trim( $value );
	if ( '' === $value ) {
		return $default;
	}

	// Reject dangerous characters or directives.
	if ( preg_match( '/[;{}<>"\'\\\\]/i', $value ) ) {
		return $default;
	}
	if ( preg_match( '/(?:url|expression|@import)\s*\(/i', $value ) ) {
		return $default;
	}

	// 1. Keywords: transparent, currentColor
	if ( preg_match( '/^(?:transparent|currentcolor)$/i', $value ) ) {
		return $value;
	}

	// 2. Hex colors (3, 4, 6, 8 hex digits)
	if ( preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value ) ) {
		return $value;
	}

	// 3. rgb() and rgba()
	if ( preg_match( '/^rgba?\(\s*[\d.]+%?(?:[\s,]+[\d.]+%?){2}(?:\s*(?:,|\/)\s*[\d.]+%?)?\s*\)$/i', $value ) ) {
		return $value;
	}

	// 4. hsl() and hsla()
	if ( preg_match( '/^hsla?\(\s*[\d.]+(?:deg|turn|rad)?(?:[\s,]+[\d.]+%?){2}(?:\s*(?:,|\/)\s*[\d.]+%?)?\s*\)$/i', $value ) ) {
		return $value;
	}

	// 5. CSS custom properties var(--custom-name)
	if ( preg_match( '/^var\(\s*--[a-zA-Z0-9_-]+\s*\)$/', $value ) ) {
		return $value;
	}

	return $default;
}

/**
 * AJAX: filtered + paginated case-study cards (CPT source).
 * Verified via stateless signed HMAC token without database transients or nonces.
 *
 * @return void
 */
function rawnaq_case_study_ajax_query() {
	$raw_token = isset( $_POST['ctx'] ) ? trim( (string) wp_unslash( $_POST['ctx'] ) ) : '';
	if ( '' === $raw_token ) {
		wp_send_json_error( [ 'message' => __( 'Missing query context token.', 'rawnaq' ) ], 400 );
	}

	$parts = explode( '.', $raw_token, 2 );
	if ( 2 !== count( $parts ) ) {
		wp_send_json_error( [ 'message' => __( 'Invalid query context token format.', 'rawnaq' ) ], 400 );
	}

	list( $payload, $sig ) = $parts;
	$expected_sig          = hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
	if ( ! hash_equals( $expected_sig, $sig ) ) {
		wp_send_json_error( [ 'message' => __( 'Invalid query context signature.', 'rawnaq' ) ], 400 );
	}

	$decoded_json = rawnaq_case_study_base64url_decode( $payload );
	$ctx          = json_decode( (string) $decoded_json, true );
	if ( ! is_array( $ctx ) || empty( $ctx['v'] ) ) {
		wp_send_json_error( [ 'message' => __( 'Invalid query context payload.', 'rawnaq' ) ], 400 );
	}

	$paged         = isset( $_POST['paged'] ) ? max( 1, min( 500, absint( $_POST['paged'] ) ) ) : 1;
	$client_sector = isset( $_POST['sector'] ) ? substr( sanitize_text_field( wp_unslash( $_POST['sector'] ) ), 0, 100 ) : '';
	$year          = isset( $_POST['year'] ) ? substr( sanitize_text_field( wp_unslash( $_POST['year'] ) ), 0, 100 ) : '';
	$service       = isset( $_POST['service'] ) ? substr( sanitize_text_field( wp_unslash( $_POST['service'] ) ), 0, 100 ) : '';

	// If querySector is set in ctx it ALWAYS wins; never allow client sector to widen the restriction.
	$forced_sector = ! empty( $ctx['querySector'] ) ? sanitize_title( (string) $ctx['querySector'] ) : '';
	$sector        = '' !== $forced_sector ? $forced_sector : sanitize_title( $client_sector );

	$per_page = max( 1, absint( $ctx['perPage'] ?? 12 ) );
	$orderby  = sanitize_key( $ctx['orderby'] ?? 'date' );
	if ( 'rand' === $orderby || ! in_array( $orderby, [ 'date', 'title', 'menu_order', 'modified' ], true ) ) {
		$orderby = 'date';
	}
	$order = ( 'ASC' === strtoupper( (string) ( $ctx['order'] ?? 'DESC' ) ) ) ? 'ASC' : 'DESC';

	$result = rawnaq_case_study_query_projects( [
		'posts_per_page' => $per_page,
		'orderby'        => $orderby,
		'order'          => $order,
		'sector'         => $sector,
		'year'           => $year,
		'service'        => $service,
		'paged'          => $paged,
	] );

	$projects     = $result['projects'];
	$layout       = sanitize_key( $ctx['layout'] ?? 'bento' );
	$hide_budget  = ! empty( $ctx['hideBudget'] );
	$hide_client  = ! empty( $ctx['hideClient'] );
	$click_action = sanitize_key( $ctx['clickAction'] ?? 'modal' );
	$show_discuss = ! empty( $ctx['showDiscuss'] );

	ob_start();
	foreach ( $projects as $project ) {
		rawnaq_case_study_render_card(
			$project,
			$layout,
			$hide_budget,
			$hide_client,
			$click_action,
			false,
			$show_discuss
		);
	}
	$html = ob_get_clean();

	wp_send_json_success( [
		'html'     => $html,
		'page'     => $paged,
		'maxPages' => (int) $result['maxPages'],
		'hasMore'  => $paged < (int) $result['maxPages'],
		'found'    => (int) $result['found'],
	] );
}

/**
 * Render Case-Study Grid markup.
 *
 * @param array  $cfg Config.
 * @param string $uid Unique instance id.
 */
function rawnaq_case_study_markup( $cfg, $uid = '' ) {
	$cfg     = is_array( $cfg ) ? $cfg : [];
	$source  = sanitize_key( $cfg['source'] ?? 'manual' );
	// Server-side pagination is available for the CPT (query) source.
	$cs_ajax = ( 'query' === $source );
	if ( $cs_ajax ) {
		// 'rand' cannot be paginated consistently across stateless AJAX requests (each query re-shuffles rows), so downgrade to 'date'.
		$ctx_orderby = sanitize_key( $cfg['queryOrderby'] ?? 'date' );
		if ( 'rand' === $ctx_orderby ) {
			$ctx_orderby = 'date';
		}
		$cfg['queryOrderby'] = $ctx_orderby;
	}
	$projects = rawnaq_case_study_resolve_projects( $cfg );

	$layout = sanitize_key( $cfg['layout'] ?? 'bento' );
	if ( ! in_array( $layout, [ 'bento', 'uniform', 'masonry' ], true ) ) {
		$layout = 'bento';
	}
	$columns = max( 2, min( 4, absint( $cfg['columns'] ?? 3 ) ) );

	$sort = sanitize_key( $cfg['sort'] ?? 'custom' );
	if ( ! in_array( $sort, [ 'custom', 'year_desc', 'sector' ], true ) ) {
		$sort = 'custom';
	}
	if ( 'query' !== $source ) {
		$projects = rawnaq_case_study_sort_projects( $projects, $sort );
	}

	$show_filter    = ! empty( $cfg['showFilter'] );
	$filter_year    = ! empty( $cfg['filterYear'] );
	$filter_service = ! empty( $cfg['filterService'] );
	$hide_budget    = ! empty( $cfg['hideBudget'] );
	$hide_client    = ! empty( $cfg['hideClient'] );

	$click_action = sanitize_key( $cfg['clickAction'] ?? 'modal' );
	if ( ! in_array( $click_action, [ 'modal', 'link', 'both' ], true ) ) {
		$click_action = 'modal';
	}

	$discuss_target = sanitize_key( $cfg['discussTarget'] ?? 'auto' );
	if ( ! in_array( $discuss_target, [ 'auto', 'form', 'dock', 'off' ], true ) ) {
		$discuss_target = 'auto';
	}

	$initial_visible = isset( $cfg['initialVisible'] ) ? absint( $cfg['initialVisible'] ) : 0;
	$load_chunk      = max( 1, absint( $cfg['loadChunk'] ?? 3 ) );

	if ( 'query' === $source ) {
		$facets   = rawnaq_case_study_query_facets( $cfg['querySector'] ?? '' );
		$sectors  = $show_filter ? $facets['sectors'] : [];
		$years    = $filter_year ? $facets['years'] : [];
		$services = $filter_service ? $facets['services'] : [];
	} else {
		$sectors  = $show_filter ? rawnaq_case_study_facets( $projects, 'sector' ) : [];
		$years    = $filter_year ? rawnaq_case_study_facets( $projects, 'year' ) : [];
		$services = $filter_service ? rawnaq_case_study_facets( $projects, 'services' ) : [];
	}

	$uid = $uid ? sanitize_html_class( $uid ) : ( 'rawnaq-cs-' . wp_unique_id() );
	if ( 0 !== strpos( $uid, 'rawnaq-cs-' ) ) {
		$uid = 'rawnaq-cs-' . $uid;
	}

	$show_modal   = ( 'link' !== $click_action ) || ( 'off' !== $discuss_target );
	$show_discuss = ( 'off' !== $discuss_target );

	$cfg_out = [
		'layout'         => $layout,
		'columns'        => $columns,
		'sort'           => $sort,
		'showFilter'     => $show_filter,
		'filterYear'     => $filter_year,
		'filterService'  => $filter_service,
		'hideBudget'     => $hide_budget,
		'hideClient'     => $hide_client,
		'clickAction'    => $click_action,
		'discussTarget'  => $discuss_target,
		'initialVisible' => $initial_visible,
		'loadChunk'      => $load_chunk,
	];

	// JSON-LD (CreativeWork ItemList) for real CPT-backed portfolios.
	if ( 'query' === $source && function_exists( 'rawnaq_schema_print' ) && function_exists( 'rawnaq_schema_case_studies' ) ) {
		rawnaq_schema_print( rawnaq_schema_case_studies( $projects ), 'case-study' );
	}

	$cs_token   = '';
	$cs_per_page = max( 1, absint( $cfg['queryNumber'] ?? 12 ) );
	if ( $cs_ajax ) {
		$ctx = [
			'v'           => 1,
			'layout'      => $layout,
			'clickAction' => $click_action,
			'hideBudget'  => (bool) $hide_budget,
			'hideClient'  => (bool) $hide_client,
			'showDiscuss' => (bool) $show_discuss,
			'perPage'     => $cs_per_page,
			'orderby'     => $ctx_orderby,
			'order'       => ( 'ASC' === strtoupper( (string) ( $cfg['queryOrder'] ?? 'DESC' ) ) ) ? 'ASC' : 'DESC',
			'querySector' => sanitize_title( (string) ( $cfg['querySector'] ?? '' ) ),
		];
		$payload  = rawnaq_case_study_base64url_encode( wp_json_encode( $ctx ) );
		$cs_token = $payload . '.' . hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
	}

	$theme = sanitize_key( $cfg['cardStyle'] ?? ( $cfg['theme'] ?? 'elevated' ) );
	if ( ! in_array( $theme, [ 'elevated', 'glass', 'spotlight' ], true ) ) {
		$theme = 'elevated';
	}

	$style       = '';
	$accent      = rawnaq_cs_sanitize_css_color( $cfg['accent'] ?? '', '' );
	if ( '' !== $accent ) {
		$style .= '--cs-accent:' . esc_attr( $accent ) . ';';
	}
	$card_bg = rawnaq_cs_sanitize_css_color( $cfg['cardBg'] ?? '', '' );
	if ( '' !== $card_bg ) {
		$style .= '--cs-card-bg:' . esc_attr( $card_bg ) . ';';
	}
	$card_border = rawnaq_cs_sanitize_css_color( $cfg['cardBorder'] ?? '', '' );
	if ( '' !== $card_border ) {
		$style .= '--cs-card-border:' . esc_attr( $card_border ) . ';';
	}
	$text_color = rawnaq_cs_sanitize_css_color( $cfg['textColor'] ?? '', '' );
	if ( '' !== $text_color ) {
		$style .= '--cs-ink:' . esc_attr( $text_color ) . ';';
	}
	if ( isset( $cfg['radius'] ) && '' !== $cfg['radius'] && null !== $cfg['radius'] ) {
		$style .= '--cs-radius:' . esc_attr( absint( $cfg['radius'] ) ) . 'px;';
	}

	$wrapper_classes = [
		'rawnaq-case-study',
		'is-theme-' . $theme,
	];

	$grid_class = 'rawnaq-cs-grid is-' . $layout;
	$total      = count( $projects );
	if ( $cs_ajax ) {
		// Server pagination: page 1 shows all fetched cards; more pages likely
		// when this page came back full. The AJAX response corrects hasMore.
		$has_load_more = ( $total >= $cs_per_page );
	} else {
		$has_load_more = $initial_visible > 0 && $initial_visible < $total;
	}
	?>
	<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>" id="<?php echo esc_attr( $uid ); ?>"
		data-cs="<?php echo esc_attr( wp_json_encode( $cfg_out ) ); ?>"
		data-theme="<?php echo esc_attr( $theme ); ?>"
		<?php if ( $cs_ajax ) : ?>
		data-cs-ajax="1"
		data-cs-ctx="<?php echo esc_attr( $cs_token ); ?>"
		<?php endif; ?>
		style="<?php echo esc_attr( $style . '--cs-cols:' . $columns ); ?>">

		<div class="rawnaq-cs-stage">
			<?php if ( $show_filter && $sectors ) : ?>
				<div class="rawnaq-cs-filters" data-filter="sector" role="group" aria-label="<?php esc_attr_e( 'Filter by sector', 'rawnaq' ); ?>">
					<button type="button" class="rawnaq-cs-chip is-active" data-sector="" aria-pressed="true"><?php esc_html_e( 'All sectors', 'rawnaq' ); ?></button>
					<?php
					foreach ( $sectors as $sector_item ) :
						$s_val   = is_array( $sector_item ) ? $sector_item['key'] : $sector_item;
						$s_label = is_array( $sector_item ) ? $sector_item['label'] : $sector_item;
						?>
						<button type="button" class="rawnaq-cs-chip" data-sector="<?php echo esc_attr( $s_val ); ?>" aria-pressed="false"><?php echo esc_html( $s_label ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $filter_year && $years ) : ?>
				<div class="rawnaq-cs-filters rawnaq-cs-filters-year" data-filter="year" role="group" aria-label="<?php esc_attr_e( 'Filter by year', 'rawnaq' ); ?>">
					<button type="button" class="rawnaq-cs-chip is-active" data-year="" aria-pressed="true"><?php esc_html_e( 'All years', 'rawnaq' ); ?></button>
					<?php foreach ( $years as $year ) : ?>
						<button type="button" class="rawnaq-cs-chip" data-year="<?php echo esc_attr( $year ); ?>" aria-pressed="false"><?php echo esc_html( $year ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $filter_service && $services ) : ?>
				<div class="rawnaq-cs-filters rawnaq-cs-filters-service" data-filter="service" role="group" aria-label="<?php esc_attr_e( 'Filter by service', 'rawnaq' ); ?>">
					<button type="button" class="rawnaq-cs-chip is-active" data-service="" aria-pressed="true"><?php esc_html_e( 'All services', 'rawnaq' ); ?></button>
					<?php foreach ( $services as $service ) : ?>
						<button type="button" class="rawnaq-cs-chip" data-service="<?php echo esc_attr( $service ); ?>" aria-pressed="false"><?php echo esc_html( $service ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="rawnaq-cs-status screen-reader-text" role="status" aria-live="polite" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;"></div>

			<div class="<?php echo esc_attr( $grid_class ); ?>">
				<?php
				foreach ( $projects as $i => $project ) :
					// In AJAX mode every fetched card is visible (paging is server-side).
					$load_hidden = ! $cs_ajax && $has_load_more && $i >= $initial_visible;
					rawnaq_case_study_render_card( $project, $layout, $hide_budget, $hide_client, $click_action, $load_hidden, $show_discuss );
				endforeach;
				?>
			</div>

			<?php if ( $has_load_more ) : ?>
				<div class="rawnaq-cs-load-more-wrap">
					<button type="button" class="rawnaq-cs-load-more" data-load-chunk="<?php echo esc_attr( (string) $load_chunk ); ?>">
						<?php esc_html_e( 'Load more projects', 'rawnaq' ); ?>
					</button>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $show_modal ) : ?>
			<div class="rawnaq-cs-modal" hidden>
				<div class="rawnaq-cs-modal-backdrop" data-cs-close></div>
				<div class="rawnaq-cs-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $uid ); ?>-modal-title">
					<button type="button" class="rawnaq-cs-modal-close" data-cs-close aria-label="<?php esc_attr_e( 'Close', 'rawnaq' ); ?>">&times;</button>
					<div class="rawnaq-cs-modal-media">
						<div class="rawnaq-cs-slider">
							<button type="button" class="rawnaq-cs-slider-prev" data-cs-prev aria-label="<?php esc_attr_e( 'Previous image', 'rawnaq' ); ?>">&#8249;</button>
							<div class="rawnaq-cs-slider-track"></div>
							<button type="button" class="rawnaq-cs-slider-next" data-cs-next aria-label="<?php esc_attr_e( 'Next image', 'rawnaq' ); ?>">&#8250;</button>
							<div class="rawnaq-cs-slider-dots"></div>
						</div>
					</div>
					<div class="rawnaq-cs-modal-content">
						<p class="rawnaq-cs-modal-sector"></p>
						<h3 class="rawnaq-cs-modal-title" id="<?php echo esc_attr( $uid ); ?>-modal-title"></h3>
						<ul class="rawnaq-cs-modal-meta"></ul>
						<div class="rawnaq-cs-modal-services"></div>
						<div class="rawnaq-cs-modal-detail"></div>
						<p class="rawnaq-cs-modal-link-wrap" hidden>
							<a class="rawnaq-cs-modal-link" href="#" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View full page', 'rawnaq' ); ?></a>
						</p>
						<?php if ( $show_discuss ) : ?>
							<p class="rawnaq-cs-modal-discuss-wrap">
								<button type="button" class="rawnaq-cs-discuss" data-cs-discuss>
									<?php esc_html_e( 'Discuss this project', 'rawnaq' ); ?>
								</button>
							</p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render one project card.
 *
 * @param array  $project      Normalized project.
 * @param string $layout       bento|uniform|masonry.
 * @param bool   $hide_budget  Suppress budget field.
 * @param bool   $hide_client  Suppress client field.
 * @param string $click_action modal|link|both.
 * @param bool   $load_hidden  Card starts hidden behind "load more".
 * @param bool   $show_discuss Show discuss CTA on link-only cards.
 */
function rawnaq_case_study_render_card( $project, $layout, $hide_budget, $hide_client, $click_action, $load_hidden = false, $show_discuss = false ) {
	$link = $project['link'] ?? '';

	$payload = [
		'id'         => $project['id'] ?? '',
		'postId'     => absint( $project['postId'] ?? 0 ),
		'slug'       => $project['slug'] ?? '',
		'title'      => $project['title'],
		'image'      => $project['image'],
		'gallery'    => $project['gallery'] ?? [],
		'sector'     => $project['sector'],
		'sectorSlug' => $project['sectorSlug'] ?? '',
		'size'       => $project['size'],
		'budget'     => $hide_budget ? '' : $project['budget'],
		'year'       => $project['year'],
		'client'     => $hide_client ? '' : $project['client'],
		'services'   => $project['services'],
		'detail'     => $project['detail'] ?: $project['excerpt'],
		'link'       => $link,
	];

	$col = max( 1, min( 4, (int) ( $project['col'] ?? 1 ) ) );
	$row = max( 1, min( 3, (int) ( $project['row'] ?? 1 ) ) );

	$card_classes = [ 'rawnaq-cs-card' ];
	if ( 'bento' === $layout ) {
		$card_classes[] = 'is-col-' . $col;
		$card_classes[] = 'is-row-' . $row;
	}
	if ( ! empty( $project['featured'] ) ) {
		$card_classes[] = 'is-featured';
	}
	if ( $load_hidden ) {
		$card_classes[] = 'is-load-hidden';
	}
	$card_class = implode( ' ', $card_classes );

	$card_sector   = ! empty( $project['sectorSlug'] ) ? $project['sectorSlug'] : ( $project['sector'] ?? '' );
	$services_attr = implode( ',', array_map( 'sanitize_text_field', (array) ( $project['services'] ?? [] ) ) );
	// Prefer article when discuss CTA is needed so we can nest a button safely.
	$use_link = ( 'link' === $click_action && $link && ! $show_discuss );
	?>
	<?php if ( $use_link ) : ?>
		<a href="<?php echo esc_url( $link ); ?>"
			class="<?php echo esc_attr( $card_class ); ?>"
			data-project-id="<?php echo esc_attr( (string) ( $project['id'] ?? '' ) ); ?>"
			data-project-slug="<?php echo esc_attr( (string) ( $project['slug'] ?? '' ) ); ?>"
			data-sector="<?php echo esc_attr( $card_sector ); ?>"
			data-year="<?php echo esc_attr( $project['year'] ); ?>"
			data-services="<?php echo esc_attr( $services_attr ); ?>"
			data-link="<?php echo esc_url( $link ); ?>"
			data-project="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>">
			<?php rawnaq_case_study_render_card_inner( $project, $hide_budget, $hide_client, false, 'none' ); ?>
		</a>
	<?php else : ?>
		<?php
		$trigger_type = ( 'link' === $click_action && $link ) ? 'link' : 'button';
		?>
		<article
			class="<?php echo esc_attr( $card_class ); ?>"
			data-project-id="<?php echo esc_attr( (string) ( $project['id'] ?? '' ) ); ?>"
			data-project-slug="<?php echo esc_attr( (string) ( $project['slug'] ?? '' ) ); ?>"
			data-sector="<?php echo esc_attr( $card_sector ); ?>"
			data-year="<?php echo esc_attr( $project['year'] ); ?>"
			data-services="<?php echo esc_attr( $services_attr ); ?>"
			data-link="<?php echo esc_url( $link ); ?>"
			data-project="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>">
			<?php rawnaq_case_study_render_card_inner( $project, $hide_budget, $hide_client, $show_discuss && 'link' === $click_action, $trigger_type ); ?>
		</article>
	<?php endif; ?>
	<?php
}

/**
 * Shared card body (media + text) reused by both the <a> and <article> card wrappers.
 *
 * @param array  $project      Normalized project.
 * @param bool   $hide_budget  Suppress budget field.
 * @param bool   $hide_client  Suppress client field.
 * @param bool   $show_discuss Show inline discuss CTA (link-only cards).
 * @param string $trigger      'button'|'link'|'none'.
 */
function rawnaq_case_study_render_card_inner( $project, $hide_budget, $hide_client, $show_discuss = false, $trigger = 'button' ) {
	?>
	<div class="rawnaq-cs-media">
		<?php if ( $project['image'] ) : ?>
			<img class="rawnaq-cs-img" src="<?php echo esc_url( $project['image'] ); ?>" alt="<?php echo esc_attr( $project['title'] ); ?>" loading="lazy" />
		<?php else : ?>
			<div class="rawnaq-cs-media-fallback" aria-hidden="true">
				<div class="rawnaq-cs-fallback-mesh">
					<div class="rawnaq-cs-fallback-glow"></div>
					<div class="rawnaq-cs-fallback-icon">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
					</div>
					<div class="rawnaq-cs-fallback-lines">
						<span></span>
						<span class="short"></span>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<?php if ( $project['sector'] ) : ?>
			<span class="rawnaq-cs-badge"><?php echo esc_html( $project['sector'] ); ?></span>
		<?php endif; ?>
	</div>
	<div class="rawnaq-cs-body">
		<h3 class="rawnaq-cs-title">
			<?php if ( 'button' === $trigger ) : ?>
				<button type="button" class="rawnaq-cs-card-trigger" aria-haspopup="dialog"><?php echo esc_html( $project['title'] ); ?></button>
			<?php elseif ( 'link' === $trigger && ! empty( $project['link'] ) ) : ?>
				<a href="<?php echo esc_url( $project['link'] ); ?>" class="rawnaq-cs-card-trigger"><?php echo esc_html( $project['title'] ); ?></a>
			<?php else : ?>
				<?php echo esc_html( $project['title'] ); ?>
			<?php endif; ?>
		</h3>
		<?php if ( ! empty( $project['excerpt'] ) ) : ?>
			<p class="rawnaq-cs-excerpt"><?php echo esc_html( $project['excerpt'] ); ?></p>
		<?php endif; ?>
		<ul class="rawnaq-cs-meta">
			<?php if ( $project['year'] ) : ?>
				<li class="rawnaq-cs-chip-meta"><span class="rawnaq-cs-meta-icon" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></span><span><?php echo esc_html( $project['year'] ); ?></span></li>
			<?php endif; ?>
			<?php if ( $project['size'] ) : ?>
				<li class="rawnaq-cs-chip-meta"><span class="rawnaq-cs-meta-icon" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="6"></circle><circle cx="12" cy="12" r="2"></circle></svg></span><span><?php echo esc_html( $project['size'] ); ?></span></li>
			<?php endif; ?>
			<?php if ( ! $hide_budget && $project['budget'] ) : ?>
				<li class="rawnaq-cs-chip-meta"><span class="rawnaq-cs-meta-icon" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg></span><span><?php echo esc_html( $project['budget'] ); ?></span></li>
			<?php endif; ?>
			<?php if ( ! $hide_client && $project['client'] ) : ?>
				<li class="rawnaq-cs-chip-meta"><span class="rawnaq-cs-meta-icon" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg></span><span><?php echo esc_html( $project['client'] ); ?></span></li>
			<?php endif; ?>
		</ul>
		<div class="rawnaq-cs-footer">
			<span class="rawnaq-cs-view">
				<span><?php esc_html_e( 'View case study', 'rawnaq' ); ?></span>
				<svg class="rawnaq-cs-arrow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
			</span>
			<?php if ( $show_discuss ) : ?>
				<button type="button" class="rawnaq-cs-discuss rawnaq-cs-discuss-card" data-cs-discuss>
					<?php esc_html_e( 'Discuss project', 'rawnaq' ); ?>
				</button>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

add_action( 'init', static function () {
	if ( function_exists( 'rawnaq_is_module_enabled' ) && rawnaq_is_module_enabled( 'case-study-grid' ) ) {
		rawnaq_case_study_register_cpt();
	}
} );
add_action( 'add_meta_boxes', static function () {
	if ( function_exists( 'rawnaq_is_module_enabled' ) && rawnaq_is_module_enabled( 'case-study-grid' ) ) {
		rawnaq_case_study_add_meta_boxes();
	}
} );
add_action( 'save_post_rawnaq_case_study', 'rawnaq_case_study_save_meta' );
