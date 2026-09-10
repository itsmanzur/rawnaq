<?php
/**
 * Rawnaq - Divi Builder Integration Loader
 *
 * Registers and loads all active Rawnaq Free modules inside Divi Visual Builder.
 *
 * @package Rawnaq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_Divi_Loader {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'et_builder_ready', [ $this, 'register_modules' ] );
	}

	/**
	 * Register active Divi modules.
	 */
	public function register_modules() {
		if ( ! class_exists( 'ET_Builder_Module' ) ) {
			return;
		}

		$module_dir = plugin_dir_path( __FILE__ ) . 'modules/';

		// 1. Smart Form
		if ( rawnaq_is_module_enabled( 'smart-form' ) && file_exists( $module_dir . 'class-et-smart-form.php' ) ) {
			require_once $module_dir . 'class-et-smart-form.php';
			new Rawnaq_ET_Smart_Form();
		}

		// 2. Flow Chart
		if ( rawnaq_is_module_enabled( 'flow-chart' ) && file_exists( $module_dir . 'class-et-flow-chart.php' ) ) {
			require_once $module_dir . 'class-et-flow-chart.php';
			new Rawnaq_ET_Flow_Chart();
		}

		// 3. Scroll Story
		if ( rawnaq_is_module_enabled( 'scroll-story' ) && file_exists( $module_dir . 'class-et-scroll-story.php' ) ) {
			require_once $module_dir . 'class-et-scroll-story.php';
			new Rawnaq_ET_Scroll_Story();
		}

		// 4. Bento Grid
		if ( rawnaq_is_module_enabled( 'bento-grid' ) && file_exists( $module_dir . 'class-et-bento-grid.php' ) ) {
			require_once $module_dir . 'class-et-bento-grid.php';
			new Rawnaq_ET_Bento_Grid();
		}

		// 5. Hub Diagram
		if ( rawnaq_is_module_enabled( 'hub-diagram' ) && file_exists( $module_dir . 'class-et-hub-diagram.php' ) ) {
			require_once $module_dir . 'class-et-hub-diagram.php';
			new Rawnaq_ET_Hub_Diagram();
		}

		// 6. 3D Tilt Card
		if ( rawnaq_is_module_enabled( 'tilt-card' ) && file_exists( $module_dir . 'class-et-tilt-card.php' ) ) {
			require_once $module_dir . 'class-et-tilt-card.php';
			new Rawnaq_ET_Tilt_Card();
		}

		// 7. Scroll Timeline
		if ( rawnaq_is_module_enabled( 'scroll-timeline' ) && file_exists( $module_dir . 'class-et-scroll-timeline.php' ) ) {
			require_once $module_dir . 'class-et-scroll-timeline.php';
			new Rawnaq_ET_Scroll_Timeline();
		}

		// 8. Scroll Progress + TOC
		if ( rawnaq_is_module_enabled( 'scroll-progress-toc' ) && file_exists( $module_dir . 'class-et-scroll-progress-toc.php' ) ) {
			require_once $module_dir . 'class-et-scroll-progress-toc.php';
			new Rawnaq_ET_Scroll_Progress_TOC();
		}

		// 9. Case Study Grid
		if ( rawnaq_is_module_enabled( 'case-study-grid' ) && file_exists( $module_dir . 'class-et-case-study-grid.php' ) ) {
			require_once $module_dir . 'class-et-case-study-grid.php';
			new Rawnaq_ET_Case_Study_Grid();
		}

		// 10. Floating Dock
		if ( rawnaq_is_module_enabled( 'floating-dock' ) && file_exists( $module_dir . 'class-et-floating-dock.php' ) ) {
			require_once $module_dir . 'class-et-floating-dock.php';
			new Rawnaq_ET_Floating_Dock();
		}
	}
}

new Rawnaq_Divi_Loader();
