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
				'supports'        => [ 'title', 'editor', 'excerpt', 'thumbnail' ],
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
			'title'    => __( 'Riverfront Civic Center', 'rawnaq' ),
			'image'    => '',
			'gallery'  => [],
			'link'     => '',
			'sector'   => __( 'Civic', 'rawnaq' ),
			'size'     => '120,000 sq ft',
			'budget'   => '$45–60M',
			'year'     => '2024',
			'client'   => __( 'City Planning Board', 'rawnaq' ),
			'services' => __( 'Architecture, Structural, MEP', 'rawnaq' ),
			'excerpt'  => __( 'A mixed-use civic hub along the waterfront with public plazas and performance halls.', 'rawnaq' ),
			'detail'   => __( 'Full scope included schematic design through CA. Passive focus on flood resilience, public access, and phased construction while the existing marina remained operational.', 'rawnaq' ),
			'featured' => true,
			'col'      => 2,
			'row'      => 2,
		],
		[
			'title'    => __( 'Northline Transit Hub', 'rawnaq' ),
			'image'    => '',
			'gallery'  => [],
			'link'     => '',
			'sector'   => __( 'Infrastructure', 'rawnaq' ),
			'size'     => '18 platforms',
			'budget'   => '$28M',
			'year'     => '2023',
			'client'   => __( 'Regional Transit Authority', 'rawnaq' ),
			'services' => __( 'Civil, Structural', 'rawnaq' ),
			'excerpt'  => __( 'Intermodal station upgrade with canopy systems and accessible passenger flow.', 'rawnaq' ),
			'detail'   => __( 'Coordinated with active rail operations. Delivered canopy steel packages, platform widening, and wayfinding integration under a compressed weekend outage schedule.', 'rawnaq' ),
			'featured' => false,
			'col'      => 1,
			'row'      => 1,
		],
		[
			'title'    => __( 'Oakridge Adaptive Reuse', 'rawnaq' ),
			'image'    => '',
			'gallery'  => [],
			'link'     => '',
			'sector'   => __( 'Adaptive Reuse', 'rawnaq' ),
			'size'     => '64 units',
			'budget'   => '$12M',
			'year'     => '2022',
			'client'   => __( 'Private Developer', 'rawnaq' ),
			'services' => __( 'Architecture, Interior', 'rawnaq' ),
			'excerpt'  => __( 'Mill building converted to housing with retained brick shell and new mezzanines.', 'rawnaq' ),
			'detail'   => __( 'Historic fabric retained where feasible. New cores, acoustic upgrades, and courtyard daylighting strategies unlocked density without altering the street elevation.', 'rawnaq' ),
			'featured' => false,
			'col'      => 1,
			'row'      => 1,
		],
		[
			'title'    => __( 'Summit Laboratory Annex', 'rawnaq' ),
			'image'    => '',
			'gallery'  => [],
			'link'     => '',
			'sector'   => __( 'Science & Tech', 'rawnaq' ),
			'size'     => '42,000 sq ft',
			'budget'   => '$22M',
			'year'     => '2025',
			'client'   => __( 'University Facilities', 'rawnaq' ),
			'services' => __( 'Architecture, Lab Planning, MEP', 'rawnaq' ),
			'excerpt'  => __( 'Flexible wet-lab annex with vibration-sensitive floors and modular casework.', 'rawnaq' ),
			'detail'   => __( 'Designed for future reconfiguration. Includes dedicated service corridors, chemical storage, and a rooftop mechanical strategy that keeps the research floors clear.', 'rawnaq' ),
			'featured' => false,
			'col'      => 1,
			'row'      => 1,
		],
	];
}

/**
 * Parse a gallery value (array, JSON string, or newline/comma separated URLs) into a clean URL list.
 *
 * @param mixed $raw Raw gallery value.
 * @return string[]
 */
function rawnaq_case_study_parse_gallery( $raw ) {
	if ( is_array( $raw ) ) {
		$urls = $raw;
	} elseif ( is_string( $raw ) && '' !== trim( $raw ) ) {
		$trimmed = trim( $raw );
		$decoded = json_decode( $trimmed, true );
		if ( is_array( $decoded ) ) {
			$urls = $decoded;
		} else {
			$urls = preg_split( '/[\r\n,]+/', $trimmed, -1, PREG_SPLIT_NO_EMPTY );
			$urls = is_array( $urls ) ? $urls : [];
		}
	} else {
		$urls = [];
	}

	$urls = array_map(
		static function ( $url ) {
			return esc_url_raw( trim( (string) $url ) );
		},
		$urls
	);

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
			'sector'   => sanitize_text_field( $p['sector'] ?? '' ),
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

	$sector = '';
	$terms  = get_the_terms( $post_id, 'rawnaq_cs_sector' );
	if ( is_array( $terms ) && ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		$sector = $terms[0]->name;
	}

	$services_raw = get_post_meta( $post_id, '_rawnaq_cs_services', true );
	$services     = array_values( array_filter( array_map( 'trim', explode( ',', (string) $services_raw ) ) ) );

	$image = get_the_post_thumbnail_url( $post_id, 'large' );
	$image = $image ? $image : '';

	$gallery = rawnaq_case_study_parse_gallery( get_post_meta( $post_id, '_rawnaq_cs_gallery', true ) );
	if ( $image && ! in_array( $image, $gallery, true ) ) {
		array_unshift( $gallery, $image );
	}

	$excerpt = has_excerpt( $post_id )
		? get_the_excerpt( $post_id )
		: wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 24 );

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
		'id'       => 'post-' . $post_id,
		'postId'   => $post_id,
		'slug'     => sanitize_title( (string) $post->post_name ),
		'title'    => get_the_title( $post_id ),
		'image'    => $image,
		'gallery'  => $gallery,
		'link'     => get_permalink( $post_id ),
		'sector'   => sanitize_text_field( $sector ),
		'size'     => sanitize_text_field( (string) get_post_meta( $post_id, '_rawnaq_cs_size', true ) ),
		'budget'   => sanitize_text_field( (string) get_post_meta( $post_id, '_rawnaq_cs_budget', true ) ),
		'year'     => sanitize_text_field( (string) get_post_meta( $post_id, '_rawnaq_cs_year', true ) ),
		'client'   => sanitize_text_field( (string) get_post_meta( $post_id, '_rawnaq_cs_client', true ) ),
		'services' => $services,
		'excerpt'  => wp_strip_all_tags( (string) $excerpt ),
		'detail'   => wp_strip_all_tags( (string) $post->post_content ),
		'featured' => $featured,
		'col'      => max( 1, min( 4, $col ) ),
		'row'      => max( 1, min( 3, $row ) ),
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
	$order = ( 'ASC' === strtoupper( (string) ( $args['order'] ?? 'DESC' ) ) ) ? 'ASC' : 'DESC';
	$paged = isset( $args['paged'] ) ? max( 1, (int) $args['paged'] ) : 1;

	$query_args = [
		'post_type'           => 'rawnaq_case_study',
		'post_status'         => 'publish',
		'posts_per_page'      => $per_page,
		'orderby'             => $orderby,
		'order'               => $order,
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
 * Store the server-render context for a grid so AJAX pagination can reuse
 * NDA/layout settings without trusting the client.
 *
 * @param string $uid Instance id.
 * @param array  $ctx Context (layout, click, nda flags, query args).
 * @return void
 */
function rawnaq_case_study_store_ctx( $uid, $ctx ) {
	set_transient( 'rawnaq_cs_ctx_' . $uid, $ctx, 12 * HOUR_IN_SECONDS );
}

/**
 * AJAX: filtered + paginated case-study cards (CPT source).
 *
 * @return void
 */
function rawnaq_case_study_ajax_query() {
	check_ajax_referer( 'rawnaq_cs_query', 'nonce' );

	$uid = isset( $_POST['uid'] ) ? sanitize_html_class( wp_unslash( $_POST['uid'] ) ) : '';
	$ctx = $uid ? get_transient( 'rawnaq_cs_ctx_' . $uid ) : false;
	if ( ! is_array( $ctx ) ) {
		wp_send_json_error( [ 'message' => __( 'Grid context expired. Please reload.', 'rawnaq' ) ], 400 );
	}

	$paged   = isset( $_POST['paged'] ) ? max( 1, absint( $_POST['paged'] ) ) : 1;
	$sector  = isset( $_POST['sector'] ) ? sanitize_text_field( wp_unslash( $_POST['sector'] ) ) : '';
	$year    = isset( $_POST['year'] ) ? sanitize_text_field( wp_unslash( $_POST['year'] ) ) : '';
	$service = isset( $_POST['service'] ) ? sanitize_text_field( wp_unslash( $_POST['service'] ) ) : '';

	$per_page = max( 1, absint( $ctx['perPage'] ?? 9 ) );
	$result   = rawnaq_case_study_query_projects( [
		'posts_per_page' => $per_page,
		'orderby'        => $ctx['orderby'] ?? 'date',
		'order'          => $ctx['order'] ?? 'DESC',
		'sector'         => $sector,
		'year'           => $year,
		'service'        => $service,
		'paged'          => $paged,
	] );

	$projects = rawnaq_case_study_normalize_projects( $result['projects'] );
	$layout   = $ctx['layout'] ?? 'bento';

	ob_start();
	foreach ( $projects as $project ) {
		rawnaq_case_study_render_card(
			$project,
			$layout,
			! empty( $ctx['hideBudget'] ),
			! empty( $ctx['hideClient'] ),
			$ctx['clickAction'] ?? 'modal',
			false,
			! empty( $ctx['showDiscuss'] )
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
	$cfg      = is_array( $cfg ) ? $cfg : [];
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
	$projects = rawnaq_case_study_sort_projects( $projects, $sort );

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

	$sectors  = ( $show_filter ) ? rawnaq_case_study_facets( $projects, 'sector' ) : [];
	$years    = ( $filter_year ) ? rawnaq_case_study_facets( $projects, 'year' ) : [];
	$services = ( $filter_service ) ? rawnaq_case_study_facets( $projects, 'services' ) : [];

	$uid = $uid ? sanitize_html_class( $uid ) : ( 'cs-' . wp_unique_id() );

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

	// Server-side pagination is available for the CPT (query) source.
	$source   = sanitize_key( $cfg['source'] ?? 'manual' );
	$cs_ajax  = ( 'query' === $source );

	// JSON-LD (CreativeWork ItemList) for real CPT-backed portfolios.
	if ( 'query' === $source && function_exists( 'rawnaq_schema_print' ) && function_exists( 'rawnaq_schema_case_studies' ) ) {
		rawnaq_schema_print( rawnaq_schema_case_studies( $projects ), 'case-study' );
	}
	$cs_nonce = '';
	if ( $cs_ajax ) {
		$cs_nonce = wp_create_nonce( 'rawnaq_cs_query' );
		rawnaq_case_study_store_ctx( $uid, [
			'layout'      => $layout,
			'clickAction' => $click_action,
			'hideBudget'  => $hide_budget,
			'hideClient'  => $hide_client,
			'showDiscuss' => $show_discuss,
			'perPage'     => max( 1, absint( $cfg['queryNumber'] ?? 9 ) ),
			'orderby'     => sanitize_key( $cfg['queryOrderby'] ?? 'date' ),
			'order'       => ( 'ASC' === strtoupper( (string) ( $cfg['queryOrder'] ?? 'DESC' ) ) ) ? 'ASC' : 'DESC',
		] );
	}

	$style = '';
	if ( ! empty( $cfg['accent'] ) ) {
		$style .= '--cs-accent:' . esc_attr( $cfg['accent'] ) . ';';
	}
	if ( ! empty( $cfg['cardBg'] ) ) {
		$style .= '--cs-card-bg:' . esc_attr( $cfg['cardBg'] ) . ';';
	}
	if ( ! empty( $cfg['cardBorder'] ) ) {
		$style .= '--cs-card-border:' . esc_attr( $cfg['cardBorder'] ) . ';';
	}
	if ( isset( $cfg['radius'] ) && '' !== $cfg['radius'] && null !== $cfg['radius'] ) {
		$style .= '--cs-radius:' . esc_attr( absint( $cfg['radius'] ) ) . 'px;';
	}

	$grid_class    = 'rawnaq-cs-grid is-' . $layout;
	$total         = count( $projects );
	$has_load_more = $initial_visible > 0 && $initial_visible < $total;
	?>
	<div class="rawnaq-case-study" id="<?php echo esc_attr( $uid ); ?>"
		data-cs="<?php echo esc_attr( wp_json_encode( $cfg_out ) ); ?>"
		<?php if ( $cs_ajax ) : ?>
		data-cs-ajax="1"
		data-cs-uid="<?php echo esc_attr( $uid ); ?>"
		data-cs-nonce="<?php echo esc_attr( $cs_nonce ); ?>"
		<?php endif; ?>
		style="<?php echo esc_attr( $style . '--cs-cols:' . $columns ); ?>">

		<?php if ( $show_filter && $sectors ) : ?>
			<div class="rawnaq-cs-filters" data-filter="sector" role="tablist" aria-label="<?php esc_attr_e( 'Filter by sector', 'rawnaq' ); ?>">
				<button type="button" class="rawnaq-cs-chip is-active" data-sector="" role="tab" aria-selected="true"><?php esc_html_e( 'All sectors', 'rawnaq' ); ?></button>
				<?php foreach ( $sectors as $sector ) : ?>
					<button type="button" class="rawnaq-cs-chip" data-sector="<?php echo esc_attr( $sector ); ?>" role="tab" aria-selected="false"><?php echo esc_html( $sector ); ?></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( $filter_year && $years ) : ?>
			<div class="rawnaq-cs-filters rawnaq-cs-filters-year" data-filter="year" role="tablist" aria-label="<?php esc_attr_e( 'Filter by year', 'rawnaq' ); ?>">
				<button type="button" class="rawnaq-cs-chip is-active" data-year="" role="tab" aria-selected="true"><?php esc_html_e( 'All years', 'rawnaq' ); ?></button>
				<?php foreach ( $years as $year ) : ?>
					<button type="button" class="rawnaq-cs-chip" data-year="<?php echo esc_attr( $year ); ?>" role="tab" aria-selected="false"><?php echo esc_html( $year ); ?></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( $filter_service && $services ) : ?>
			<div class="rawnaq-cs-filters rawnaq-cs-filters-service" data-filter="service" role="tablist" aria-label="<?php esc_attr_e( 'Filter by service', 'rawnaq' ); ?>">
				<button type="button" class="rawnaq-cs-chip is-active" data-service="" role="tab" aria-selected="true"><?php esc_html_e( 'All services', 'rawnaq' ); ?></button>
				<?php foreach ( $services as $service ) : ?>
					<button type="button" class="rawnaq-cs-chip" data-service="<?php echo esc_attr( $service ); ?>" role="tab" aria-selected="false"><?php echo esc_html( $service ); ?></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="<?php echo esc_attr( $grid_class ); ?>">
			<?php
			foreach ( $projects as $i => $project ) :
				$load_hidden = $has_load_more && $i >= $initial_visible;
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
		'id'       => $project['id'] ?? '',
		'postId'   => absint( $project['postId'] ?? 0 ),
		'slug'     => $project['slug'] ?? '',
		'title'    => $project['title'],
		'image'    => $project['image'],
		'gallery'  => $project['gallery'] ?? [],
		'sector'   => $project['sector'],
		'size'     => $project['size'],
		'budget'   => $hide_budget ? '' : $project['budget'],
		'year'     => $project['year'],
		'client'   => $hide_client ? '' : $project['client'],
		'services' => $project['services'],
		'detail'   => $project['detail'] ?: $project['excerpt'],
		'link'     => $link,
	];

	$span_style = '';
	if ( 'bento' === $layout ) {
		$span_style = 'grid-column: span ' . (int) $project['col'] . '; grid-row: span ' . (int) $project['row'] . ';';
	}

	$services_attr = implode( ',', array_map( 'sanitize_text_field', (array) ( $project['services'] ?? [] ) ) );
	$card_class    = 'rawnaq-cs-card' . ( $project['featured'] ? ' is-featured' : '' ) . ( $load_hidden ? ' is-load-hidden' : '' );
	// Prefer article when discuss CTA is needed so we can nest a button safely.
	$use_link      = ( 'link' === $click_action && $link && ! $show_discuss );
	$aria_label    = sprintf( /* translators: %s: project title */ __( 'View case study: %s', 'rawnaq' ), $project['title'] );
	?>
	<?php if ( $use_link ) : ?>
		<a href="<?php echo esc_url( $link ); ?>"
			class="<?php echo esc_attr( $card_class ); ?>"
			data-project-id="<?php echo esc_attr( (string) ( $project['id'] ?? '' ) ); ?>"
			data-project-slug="<?php echo esc_attr( (string) ( $project['slug'] ?? '' ) ); ?>"
			data-sector="<?php echo esc_attr( $project['sector'] ); ?>"
			data-year="<?php echo esc_attr( $project['year'] ); ?>"
			data-services="<?php echo esc_attr( $services_attr ); ?>"
			data-link="<?php echo esc_attr( $link ); ?>"
			data-project="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>"
			style="<?php echo esc_attr( $span_style ); ?>">
			<?php rawnaq_case_study_render_card_inner( $project, $hide_budget, $hide_client, false ); ?>
		</a>
	<?php else : ?>
		<article
			class="<?php echo esc_attr( $card_class ); ?>"
			data-project-id="<?php echo esc_attr( (string) ( $project['id'] ?? '' ) ); ?>"
			data-project-slug="<?php echo esc_attr( (string) ( $project['slug'] ?? '' ) ); ?>"
			data-sector="<?php echo esc_attr( $project['sector'] ); ?>"
			data-year="<?php echo esc_attr( $project['year'] ); ?>"
			data-services="<?php echo esc_attr( $services_attr ); ?>"
			data-link="<?php echo esc_attr( $link ); ?>"
			data-project="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>"
			style="<?php echo esc_attr( $span_style ); ?>"
			tabindex="0"
			role="button"
			aria-label="<?php echo esc_attr( $aria_label ); ?>">
			<?php rawnaq_case_study_render_card_inner( $project, $hide_budget, $hide_client, $show_discuss && 'link' === $click_action ); ?>
		</article>
	<?php endif; ?>
	<?php
}

/**
 * Shared card body (media + text) reused by both the <a> and <article> card wrappers.
 *
 * @param array $project      Normalized project.
 * @param bool  $hide_budget  Suppress budget field.
 * @param bool  $hide_client  Suppress client field.
 * @param bool  $show_discuss Show inline discuss CTA (link-only cards).
 */
function rawnaq_case_study_render_card_inner( $project, $hide_budget, $hide_client, $show_discuss = false ) {
	?>
	<div class="rawnaq-cs-media">
		<?php if ( $project['image'] ) : ?>
			<img src="<?php echo esc_url( $project['image'] ); ?>" alt="" loading="lazy" />
		<?php else : ?>
			<div class="rawnaq-cs-media-fallback" aria-hidden="true"><?php echo esc_html( mb_substr( (string) $project['title'], 0, 1 ) ); ?></div>
		<?php endif; ?>
		<?php if ( $project['sector'] ) : ?>
			<span class="rawnaq-cs-badge"><?php echo esc_html( $project['sector'] ); ?></span>
		<?php endif; ?>
	</div>
	<div class="rawnaq-cs-body">
		<h3 class="rawnaq-cs-title"><?php echo esc_html( $project['title'] ); ?></h3>
		<?php if ( ! empty( $project['excerpt'] ) ) : ?>
			<p class="rawnaq-cs-excerpt"><?php echo esc_html( $project['excerpt'] ); ?></p>
		<?php endif; ?>
		<ul class="rawnaq-cs-meta">
			<?php if ( $project['year'] ) : ?>
				<li><span><?php esc_html_e( 'Year', 'rawnaq' ); ?></span> <?php echo esc_html( $project['year'] ); ?></li>
			<?php endif; ?>
			<?php if ( $project['size'] ) : ?>
				<li><span><?php esc_html_e( 'Scope', 'rawnaq' ); ?></span> <?php echo esc_html( $project['size'] ); ?></li>
			<?php endif; ?>
			<?php if ( ! $hide_budget && $project['budget'] ) : ?>
				<li><span><?php esc_html_e( 'Budget', 'rawnaq' ); ?></span> <?php echo esc_html( $project['budget'] ); ?></li>
			<?php endif; ?>
			<?php if ( ! $hide_client && $project['client'] ) : ?>
				<li><span><?php esc_html_e( 'Client', 'rawnaq' ); ?></span> <?php echo esc_html( $project['client'] ); ?></li>
			<?php endif; ?>
		</ul>
		<span class="rawnaq-cs-view"><?php esc_html_e( 'View case study', 'rawnaq' ); ?></span>
		<?php if ( $show_discuss ) : ?>
			<button type="button" class="rawnaq-cs-discuss rawnaq-cs-discuss-card" data-cs-discuss>
				<?php esc_html_e( 'Discuss this project', 'rawnaq' ); ?>
			</button>
		<?php endif; ?>
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
