<?php
/**
 * Fired when the plugin is uninstalled.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

delete_option( 'rawnaq_settings' );
delete_option( 'rawnaq_dock_clicks' );
delete_option( 'rawnaq_sf_configs' );
delete_option( 'manzur_elements_settings' ); // legacy

wp_clear_scheduled_hook( 'rawnaq_smart_form_prune_configs' );
wp_clear_scheduled_hook( 'rawnaq_sf_retry_delivery' );
wp_clear_scheduled_hook( 'rawnaq_smart_form_daily_cleanup' );

// Remove protected uploads directory (rawnaq-forms).
$rawnaq_upload_info = wp_upload_dir();
$rawnaq_forms_dir   = untrailingslashit( $rawnaq_upload_info['basedir'] ) . '/rawnaq-forms';
if ( is_dir( $rawnaq_forms_dir ) ) {
	rawnaq_uninstall_delete_directory( $rawnaq_forms_dir );
}

/**
 * Recursively remove a directory and its contents.
 *
 * @param string $dir Directory path.
 */
function rawnaq_uninstall_delete_directory( $dir ) {
	if ( ! is_dir( $dir ) ) {
		return;
	}
	$items = scandir( $dir );
	if ( ! is_array( $items ) ) {
		return;
	}
	foreach ( $items as $item ) {
		if ( '.' === $item || '..' === $item ) {
			continue;
		}
		$path = $dir . '/' . $item;
		if ( is_dir( $path ) ) {
			rawnaq_uninstall_delete_directory( $path );
		} else {
			@unlink( $path );
		}
	}
	@rmdir( $dir );
}

// Smart Form trusted-config transients.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_rawnaq_sf_cfg_%' OR option_name LIKE '_transient_timeout_rawnaq_sf_cfg_%'"
);

/**
 * Delete all posts of a type (and their meta via wp_delete_post).
 *
 * @param string $post_type Post type slug.
 */
function rawnaq_uninstall_delete_posts( $post_type ) {
	$ids = get_posts(
		[
			'post_type'              => $post_type,
			'post_status'            => 'any',
			'numberposts'            => -1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		]
	);
	foreach ( $ids as $id ) {
		wp_delete_post( (int) $id, true );
	}
}

rawnaq_uninstall_delete_posts( 'rawnaq_sf_entry' );
rawnaq_uninstall_delete_posts( 'rawnaq_case_study' );

$rawnaq_sector_terms = get_terms(
	[
		'taxonomy'   => 'rawnaq_cs_sector',
		'hide_empty' => false,
		'fields'     => 'ids',
	]
);
if ( ! is_wp_error( $rawnaq_sector_terms ) && $rawnaq_sector_terms ) {
	foreach ( $rawnaq_sector_terms as $rawnaq_term_id ) {
		wp_delete_term( (int) $rawnaq_term_id, 'rawnaq_cs_sector' );
	}
}
