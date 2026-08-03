<?php
/**
 * Rawnaq Template Kit — Loader and shared helpers.
 *
 * Boots Elementor and Gutenberg sub-loaders, provides the central registry
 * of bundled starter-section templates, and exposes the AJAX endpoints that
 * both builders call.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_Template_Kit {

	/** Absolute path to the bundled templates directory. */
	const TEMPLATES_DIR = RAWNAQ_PATH . 'assets/templates/';

	public function __construct() {
		// Gutenberg: always (no Elementor dependency).
		require_once RAWNAQ_PATH . 'includes/gutenberg/class-template-kit-gutenberg.php';
		new Rawnaq_Template_Kit_Gutenberg();

		// Elementor: only when loaded.
		if ( did_action( 'elementor/loaded' ) ) {
			$this->load_elementor_kit();
		} else {
			add_action( 'elementor/loaded', [ $this, 'load_elementor_kit' ] );
		}

		// Shared AJAX endpoints (used by Elementor popup).
		add_action( 'wp_ajax_rawnaq_template_kit_list',   [ $this, 'ajax_list' ] );
		add_action( 'wp_ajax_rawnaq_template_kit_import', [ $this, 'ajax_import' ] );
	}

	public function load_elementor_kit() {
		require_once RAWNAQ_PATH . 'includes/elementor/class-template-kit-elementor.php';
		new Rawnaq_Template_Kit_Elementor();
	}

	// -------------------------------------------------------------------------
	// Template Registry
	// -------------------------------------------------------------------------

	/**
	 * All bundled starter-section templates.
	 *
	 * Each entry:
	 *   id               string   Unique slug (file name without extension).
	 *   title            string   Human-readable name.
	 *   category         string   UI filter group.
	 *   required_modules string[] Module slugs that must be enabled.
	 *   thumbnail        string   Filename inside assets/templates/thumbnails/.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_registry(): array {
		return [
			[
				'id'               => 'agency-hero',
				'title'            => __( 'Agency Hero', 'rawnaq' ),
				'category'         => 'hero',
				'required_modules' => [ 'floating-dock' ],
				'thumbnail'        => 'agency-hero.jpg',
			],
			[
				'id'               => 'services-hub',
				'title'            => __( 'Services Hub', 'rawnaq' ),
				'category'         => 'services',
				'required_modules' => [ 'hub-diagram' ],
				'thumbnail'        => 'services-hub.jpg',
			],
			[
				'id'               => 'portfolio-bento',
				'title'            => __( 'Portfolio Bento', 'rawnaq' ),
				'category'         => 'portfolio',
				'required_modules' => [ 'bento-grid' ],
				'thumbnail'        => 'portfolio-bento.jpg',
			],
			[
				'id'               => 'timeline-about',
				'title'            => __( 'About / Story Timeline', 'rawnaq' ),
				'category'         => 'about',
				'required_modules' => [ 'scroll-timeline' ],
				'thumbnail'        => 'timeline-about.jpg',
			],
			[
				'id'               => 'contact-form',
				'title'            => __( 'Contact Section', 'rawnaq' ),
				'category'         => 'contact',
				'required_modules' => [ 'smart-form' ],
				'thumbnail'        => 'contact-form.jpg',
			],
			[
				'id'               => 'flow-process',
				'title'            => __( 'Process Flow', 'rawnaq' ),
				'category'         => 'process',
				'required_modules' => [ 'flow-chart' ],
				'thumbnail'        => 'flow-process.jpg',
			],
		];
	}

	/**
	 * Return registry enriched with missing-module warnings.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_registry_with_status(): array {
		$list = self::get_registry();
		foreach ( $list as &$tpl ) {
			$missing = [];
			foreach ( $tpl['required_modules'] as $slug ) {
				if ( ! rawnaq_is_module_enabled( $slug ) ) {
					$missing[] = $slug;
				}
			}
			$tpl['missing_modules'] = $missing;
			$tpl['thumbnail_url']   = self::thumbnail_url( $tpl['thumbnail'] );
		}
		unset( $tpl );
		return $list;
	}

	/**
	 * Absolute path to an Elementor JSON template file.
	 *
	 * @param string $id Template id.
	 * @return string
	 */
	public static function elementor_json_path( string $id ): string {
		return self::TEMPLATES_DIR . 'elementor/' . sanitize_file_name( $id ) . '.json';
	}

	/**
	 * URL for a thumbnail image.
	 *
	 * @param string $filename Thumbnail filename.
	 * @return string
	 */
	public static function thumbnail_url( string $filename ): string {
		return RAWNAQ_URL . 'assets/templates/thumbnails/' . rawurlencode( $filename );
	}

	// -------------------------------------------------------------------------
	// AJAX Handlers
	// -------------------------------------------------------------------------

	/** Return the template list as JSON (used by Elementor popup). */
	public function ajax_list(): void {
		check_ajax_referer( 'rawnaq_template_kit', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rawnaq' ) ], 403 );
		}
		wp_send_json_success( self::get_registry_with_status() );
	}

	/**
	 * Import an Elementor template: read the bundled JSON, create a page-builder
	 * document, and return the document id so the editor JS can insert it.
	 */
	public function ajax_import(): void {
		check_ajax_referer( 'rawnaq_template_kit', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rawnaq' ) ], 403 );
		}

		$template_id = isset( $_POST['template_id'] )
			? sanitize_key( wp_unslash( $_POST['template_id'] ) )
			: '';

		if ( ! $template_id ) {
			wp_send_json_error( [ 'message' => __( 'Missing template ID.', 'rawnaq' ) ], 400 );
		}

		$json_path = self::elementor_json_path( $template_id );
		if ( ! file_exists( $json_path ) ) {
			wp_send_json_error(
				/* translators: %s: template id */
				[ 'message' => sprintf( __( 'Template "%s" not found.', 'rawnaq' ), $template_id ) ],
				404
			);
		}

		$raw  = file_get_contents( $json_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid template data.', 'rawnaq' ) ], 500 );
		}

		// Missing-module guard.
		$registry = self::get_registry();
		$tpl_meta = null;
		foreach ( $registry as $t ) {
			if ( $t['id'] === $template_id ) {
				$tpl_meta = $t;
				break;
			}
		}
		if ( $tpl_meta ) {
			$missing = array_filter(
				$tpl_meta['required_modules'],
				static fn( $slug ) => ! rawnaq_is_module_enabled( $slug )
			);
			if ( $missing ) {
				$auto_enable = ! empty( $_POST['auto_enable'] );
				if ( $auto_enable ) {
					$settings = get_option( 'rawnaq_settings', [] );
					if ( ! is_array( $settings ) ) {
						$settings = [];
					}
					if ( empty( $settings['modules'] ) || ! is_array( $settings['modules'] ) ) {
						$settings['modules'] = rawnaq_default_modules();
					}
					foreach ( $missing as $m_slug ) {
						$settings['modules'][ $m_slug ] = '1';
					}
					update_option( 'rawnaq_settings', $settings );
				} else {
					wp_send_json_error(
						[
							'message'         => __( 'Some required modules are disabled.', 'rawnaq' ),
							'missing_modules' => array_values( $missing ),
						],
						409
					);
				}
			}
		}

		wp_send_json_success(
			[
				'elements' => $data['elements'] ?? $data,
				'title'    => $tpl_meta ? $tpl_meta['title'] : $template_id,
			]
		);
	}
}
