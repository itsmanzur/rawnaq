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
