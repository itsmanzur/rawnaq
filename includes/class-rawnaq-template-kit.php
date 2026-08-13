<?php
/**
 * Rawnaq Template Kit — Loader and shared helpers.
 *
 * Boots Elementor and Gutenberg sub-loaders, provides the central registry
 * of bundled starter-section templates and full-page kits, and exposes the
 * AJAX endpoints that both builders call.
 *
 * Phase 2 additions:
 *   • Color Token System  — templates use {{COLOR_*}} placeholders that are
 *     resolved at import time from the site palette or user overrides.
 *   • Site Palette AJAX   — reads WordPress theme.json + Elementor kit colors.
 *   • Page Kit Registry   — multi-section bundles importable in one click.
 *   • Page Kit Import     — new AJAX endpoint combining multiple sections.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_Template_Kit {

	/** Absolute path to the bundled templates directory. */
	const TEMPLATES_DIR = RAWNAQ_PATH . 'assets/templates/';

	/**
	 * Default color palette tokens.
	 * Used when the site provides no matching palette entry.
	 */
	const DEFAULT_PALETTE = [
		'COLOR_PRIMARY'   => '#6366f1',
		'COLOR_SECONDARY' => '#a855f7',
		'COLOR_ACCENT'    => '#22d3ee',
		'COLOR_DARK'      => '#0a0a0a',
		'COLOR_LIGHT'     => '#ffffff',
		'COLOR_MUTED'     => '#a1a1aa',
		'COLOR_SUCCESS'   => '#10b981',
		'COLOR_WARNING'   => '#f59e0b',
	];

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
		add_action( 'wp_ajax_rawnaq_template_kit_list',        [ $this, 'ajax_list' ] );
		add_action( 'wp_ajax_rawnaq_template_kit_import',      [ $this, 'ajax_import' ] );
		add_action( 'wp_ajax_rawnaq_get_site_palette',         [ $this, 'ajax_get_site_palette' ] );
		add_action( 'wp_ajax_rawnaq_template_kit_import_page', [ $this, 'ajax_import_page_kit' ] );
	}

	public function load_elementor_kit() {
		require_once RAWNAQ_PATH . 'includes/elementor/class-template-kit-elementor.php';
		new Rawnaq_Template_Kit_Elementor();
	}

	// =========================================================================
	// Section Template Registry
	// =========================================================================

	/**
	 * All bundled starter-section templates.
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
				'color_tokens'     => [ 'COLOR_PRIMARY', 'COLOR_DARK', 'COLOR_LIGHT', 'COLOR_MUTED' ],
			],
			[
				'id'               => 'services-hub',
				'title'            => __( 'Services Hub', 'rawnaq' ),
				'category'         => 'services',
				'required_modules' => [ 'hub-diagram' ],
				'thumbnail'        => 'services-hub.jpg',
				'color_tokens'     => [ 'COLOR_PRIMARY', 'COLOR_DARK', 'COLOR_LIGHT' ],
			],
			[
				'id'               => 'portfolio-bento',
				'title'            => __( 'Portfolio Bento', 'rawnaq' ),
				'category'         => 'portfolio',
				'required_modules' => [ 'bento-grid' ],
				'thumbnail'        => 'portfolio-bento.jpg',
				'color_tokens'     => [ 'COLOR_SECONDARY', 'COLOR_DARK', 'COLOR_LIGHT' ],
			],
			[
				'id'               => 'timeline-about',
				'title'            => __( 'About / Story Timeline', 'rawnaq' ),
				'category'         => 'about',
				'required_modules' => [ 'scroll-timeline' ],
				'thumbnail'        => 'timeline-about.jpg',
				'color_tokens'     => [ 'COLOR_ACCENT', 'COLOR_DARK', 'COLOR_LIGHT' ],
			],
			[
				'id'               => 'contact-form',
				'title'            => __( 'Contact Section', 'rawnaq' ),
				'category'         => 'contact',
				'required_modules' => [ 'smart-form' ],
				'thumbnail'        => 'contact-form.jpg',
				'color_tokens'     => [ 'COLOR_SUCCESS', 'COLOR_DARK', 'COLOR_LIGHT', 'COLOR_MUTED' ],
			],
			[
				'id'               => 'flow-process',
				'title'            => __( 'Process Flow', 'rawnaq' ),
				'category'         => 'process',
				'required_modules' => [ 'flow-chart' ],
				'thumbnail'        => 'flow-process.jpg',
				'color_tokens'     => [ 'COLOR_WARNING', 'COLOR_DARK', 'COLOR_LIGHT' ],
			],
		];
	}

	/**
	 * Return registry enriched with missing-module warnings + thumbnail URLs.
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

	// =========================================================================
	// Page Kit Registry
	// =========================================================================

	/**
	 * Bundled full-page kits — each is a named collection of section IDs.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_page_kit_registry(): array {
		return [
			[
				'id'          => 'saas-landing',
				'title'       => __( 'SaaS Landing Page', 'rawnaq' ),
				'description' => __( 'Hero, Services Hub, Process Flow & Contact — perfect for software products.', 'rawnaq' ),
				'thumbnail'   => 'kit-saas-landing.jpg',
				'sections'    => [ 'agency-hero', 'services-hub', 'flow-process', 'contact-form' ],
				'tags'        => [ 'SaaS', 'App', 'Startup' ],
			],
			[
				'id'          => 'agency-showcase',
				'title'       => __( 'Agency Showcase', 'rawnaq' ),
				'description' => __( 'Hero, Portfolio Bento, About Timeline & Contact — ideal for creative studios.', 'rawnaq' ),
				'thumbnail'   => 'kit-agency-showcase.jpg',
				'sections'    => [ 'agency-hero', 'portfolio-bento', 'timeline-about', 'contact-form' ],
				'tags'        => [ 'Agency', 'Creative', 'Studio' ],
			],
			[
				'id'          => 'portfolio-pro',
				'title'       => __( 'Portfolio Pro', 'rawnaq' ),
				'description' => __( 'Hero, Portfolio Bento & About Timeline — built for freelancers and designers.', 'rawnaq' ),
				'thumbnail'   => 'kit-portfolio-pro.jpg',
				'sections'    => [ 'agency-hero', 'portfolio-bento', 'timeline-about' ],
				'tags'        => [ 'Freelancer', 'Designer', 'Portfolio' ],
			],
			[
				'id'          => 'business-classic',
				'title'       => __( 'Business Classic', 'rawnaq' ),
				'description' => __( 'Hero, Services Hub & Contact — clean and professional for any business.', 'rawnaq' ),
				'thumbnail'   => 'kit-business-classic.jpg',
				'sections'    => [ 'agency-hero', 'services-hub', 'contact-form' ],
				'tags'        => [ 'Business', 'Corporate', 'Professional' ],
			],
		];
	}

	/**
	 * Page Kit registry enriched with section details and missing-module status.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_page_kit_registry_with_status(): array {
		$section_map = [];
		foreach ( self::get_registry_with_status() as $s ) {
			$section_map[ $s['id'] ] = $s;
		}

		$kits = self::get_page_kit_registry();
		foreach ( $kits as &$kit ) {
			$kit['thumbnail_url']    = self::thumbnail_url( $kit['thumbnail'] );
			$kit['section_details']  = [];
			$kit['missing_modules']  = [];

			foreach ( $kit['sections'] as $sec_id ) {
				if ( isset( $section_map[ $sec_id ] ) ) {
					$sec                    = $section_map[ $sec_id ];
					$kit['section_details'][] = [
						'id'    => $sec_id,
						'title' => $sec['title'],
					];
					foreach ( $sec['missing_modules'] as $m ) {
						if ( ! in_array( $m, $kit['missing_modules'], true ) ) {
							$kit['missing_modules'][] = $m;
						}
					}
				}
			}
		}
		unset( $kit );
		return $kits;
	}

	// =========================================================================
	// Color Palette Helpers
	// =========================================================================

	/**
	 * Read the active site palette from WordPress theme.json and/or Elementor.
	 *
	 * Returns an associative array keyed by Rawnaq token name, e.g.:
	 *   [ 'COLOR_PRIMARY' => '#6366f1', ... ]
	 *
	 * @return array<string, string>
	 */
	public static function get_site_palette(): array {
		$palette = self::DEFAULT_PALETTE;

		// ── WordPress theme.json palette ─────────────────────────────────────
		if ( class_exists( 'WP_Theme_JSON_Resolver' ) ) {
			try {
				$theme_data = WP_Theme_JSON_Resolver::get_merged_data()->get_settings();
				$wp_colors  = $theme_data['color']['palette']['theme'] ?? [];

				// Map first 6 theme palette slots to our tokens (best-effort).
				$token_slots = [
					0 => 'COLOR_PRIMARY',
					1 => 'COLOR_SECONDARY',
					2 => 'COLOR_ACCENT',
					3 => 'COLOR_DARK',
					4 => 'COLOR_LIGHT',
					5 => 'COLOR_MUTED',
				];
				foreach ( $wp_colors as $i => $entry ) {
					if ( isset( $token_slots[ $i ], $entry['color'] ) ) {
						$palette[ $token_slots[ $i ] ] = sanitize_hex_color( $entry['color'] ) ?: $palette[ $token_slots[ $i ] ];
					}
				}
			} catch ( \Throwable $e ) {
				// Silently fall back to defaults.
			}
		}

		// ── Elementor Global Colors ───────────────────────────────────────────
		if ( class_exists( '\Elementor\Plugin' ) ) {
			try {
				$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit_for_frontend();
				if ( $kit ) {
					$sys_colors = $kit->get_settings_for_display( 'system_colors' ) ?? [];
					$custom_colors = $kit->get_settings_for_display( 'custom_colors' ) ?? [];
					$el_colors = array_merge( $sys_colors, $custom_colors );

					foreach ( $el_colors as $color_entry ) {
						$id    = $color_entry['_id'] ?? '';
						$color = sanitize_hex_color( $color_entry['color'] ?? '' );
						if ( ! $color ) continue;

						// Map Elementor system color IDs to our tokens.
						$el_map = [
							'primary'   => 'COLOR_PRIMARY',
							'secondary' => 'COLOR_SECONDARY',
							'text'      => 'COLOR_LIGHT',
							'accent'    => 'COLOR_ACCENT',
						];
						if ( isset( $el_map[ $id ] ) ) {
							$palette[ $el_map[ $id ] ] = $color;
						}
					}
				}
			} catch ( \Throwable $e ) {
				// Silently fall back to defaults.
			}
		}

		return $palette;
	}

	/**
	 * Replace {{COLOR_*}} tokens in a string with resolved palette values.
	 *
	 * @param string               $content Raw template content.
	 * @param array<string,string> $overrides User-supplied color overrides (token => hex).
	 * @return string
	 */
	public static function resolve_color_tokens( string $content, array $overrides = [] ): string {
		$palette = array_merge( self::get_site_palette(), $overrides );
		foreach ( $palette as $token => $hex ) {
			$hex     = sanitize_hex_color( $hex );
			if ( ! $hex ) continue;
			$content = str_replace( '{{' . $token . '}}', $hex, $content );
		}
		return $content;
	}

	// =========================================================================
	// Misc Helpers
	// =========================================================================

	/** Absolute path to an Elementor JSON template file. */
	public static function elementor_json_path( string $id ): string {
		return self::TEMPLATES_DIR . 'elementor/' . sanitize_file_name( $id ) . '.json';
	}

	/** URL for a thumbnail image. */
	public static function thumbnail_url( string $filename ): string {
		return RAWNAQ_URL . 'assets/templates/thumbnails/' . rawurlencode( $filename );
	}

	// =========================================================================
	// AJAX Handlers
	// =========================================================================

	/** Return the section + page-kit list as JSON (used by Elementor popup). */
	public function ajax_list(): void {
		check_ajax_referer( 'rawnaq_template_kit', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rawnaq' ) ], 403 );
		}
		wp_send_json_success( [
			'sections'  => self::get_registry_with_status(),
			'page_kits' => self::get_page_kit_registry_with_status(),
		] );
	}

	/**
	 * Return the resolved site palette so the JS color-picker can pre-fill.
	 */
	public function ajax_get_site_palette(): void {
		check_ajax_referer( 'rawnaq_template_kit', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rawnaq' ) ], 403 );
		}
		wp_send_json_success( self::get_site_palette() );
	}

	/**
	 * Import a single Elementor section template with optional color overrides.
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

		// Collect user color overrides (token => hex).
		$overrides = [];
		$raw_overrides = isset( $_POST['color_overrides'] ) ? wp_unslash( $_POST['color_overrides'] ) : '{}';
		if ( is_string( $raw_overrides ) ) {
			$decoded = json_decode( $raw_overrides, true );
			if ( is_array( $decoded ) ) {
				foreach ( $decoded as $token => $hex ) {
					$token = sanitize_key( $token );
					$hex   = sanitize_hex_color( $hex );
					if ( $token && $hex ) {
						$overrides[ strtoupper( $token ) ] = $hex;
					}
				}
			}
		}

		$data = $this->load_and_resolve_section( $template_id, $overrides );
		if ( is_wp_error( $data ) ) {
			wp_send_json_error( [ 'message' => $data->get_error_message() ], $data->get_error_data() ?: 500 );
		}

		wp_send_json_success( $data );
	}

	/**
	 * Import a full page kit — combines multiple sections into one payload.
	 */
	public function ajax_import_page_kit(): void {
		check_ajax_referer( 'rawnaq_template_kit', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'rawnaq' ) ], 403 );
		}

		$kit_id = isset( $_POST['kit_id'] )
			? sanitize_key( wp_unslash( $_POST['kit_id'] ) )
			: '';

		if ( ! $kit_id ) {
			wp_send_json_error( [ 'message' => __( 'Missing kit ID.', 'rawnaq' ) ], 400 );
		}

		// Find kit in registry.
		$kit_meta = null;
		foreach ( self::get_page_kit_registry() as $k ) {
			if ( $k['id'] === $kit_id ) {
				$kit_meta = $k;
				break;
			}
		}
		if ( ! $kit_meta ) {
			wp_send_json_error(
				[ 'message' => sprintf( __( 'Page kit "%s" not found.', 'rawnaq' ), $kit_id ) ],
				404
			);
		}

		// Collect user color overrides.
		$overrides = [];
		$raw_overrides = isset( $_POST['color_overrides'] ) ? wp_unslash( $_POST['color_overrides'] ) : '{}';
		if ( is_string( $raw_overrides ) ) {
			$decoded = json_decode( $raw_overrides, true );
			if ( is_array( $decoded ) ) {
				foreach ( $decoded as $token => $hex ) {
					$token = sanitize_key( $token );
					$hex   = sanitize_hex_color( $hex );
					if ( $token && $hex ) {
						$overrides[ strtoupper( $token ) ] = $hex;
					}
				}
			}
		}

		// Auto-enable any missing modules.
		$auto_enable = ! empty( $_POST['auto_enable'] );
		if ( $auto_enable ) {
			$settings = get_option( 'rawnaq_settings', [] );
			if ( ! is_array( $settings ) ) {
				$settings = [];
			}
			if ( empty( $settings['modules'] ) || ! is_array( $settings['modules'] ) ) {
				$settings['modules'] = rawnaq_default_modules();
			}
			// Check all sections in kit.
			foreach ( $kit_meta['sections'] as $sec_id ) {
				$sec_meta = null;
				foreach ( self::get_registry() as $t ) {
					if ( $t['id'] === $sec_id ) { $sec_meta = $t; break; }
				}
				if ( $sec_meta ) {
					foreach ( $sec_meta['required_modules'] as $m_slug ) {
						if ( ! rawnaq_is_module_enabled( $m_slug ) ) {
							$settings['modules'][ $m_slug ] = '1';
						}
					}
				}
			}
			update_option( 'rawnaq_settings', $settings );
		}

		// Combine all sections' elements into one array.
		$all_elements = [];
		$errors       = [];
		foreach ( $kit_meta['sections'] as $sec_id ) {
			$result = $this->load_and_resolve_section( $sec_id, $overrides, true );
			if ( is_wp_error( $result ) ) {
				$errors[] = $result->get_error_message();
			} elseif ( is_array( $result['elements'] ) ) {
				foreach ( $result['elements'] as $el ) {
					$all_elements[] = $el;
				}
			}
		}

		if ( empty( $all_elements ) ) {
			$msg = $errors ? implode( ' | ', $errors ) : __( 'No sections could be loaded.', 'rawnaq' );
			wp_send_json_error( [ 'message' => $msg ], 500 );
		}

		wp_send_json_success( [
			'elements' => $all_elements,
			'title'    => $kit_meta['title'],
			'errors'   => $errors,
		] );
	}

	// =========================================================================
	// Internal helpers
	// =========================================================================

	/**
	 * Load a section template JSON, resolve color tokens, check modules.
	 *
	 * @param string               $template_id
	 * @param array<string,string> $overrides
	 * @param bool                 $skip_module_check  True when called from page-kit import (handled upstream).
	 * @return array<string,mixed>|\WP_Error
	 */
	private function load_and_resolve_section( string $template_id, array $overrides = [], bool $skip_module_check = false ) {
		$json_path = self::elementor_json_path( $template_id );
		if ( ! file_exists( $json_path ) ) {
			return new \WP_Error(
				'not_found',
				sprintf( __( 'Template "%s" not found.', 'rawnaq' ), $template_id ),
				404
			);
		}

		$raw = file_get_contents( $json_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( false === $raw ) {
			return new \WP_Error( 'read_error', __( 'Could not read template file.', 'rawnaq' ), 500 );
		}

		// Resolve color tokens before JSON parsing so tokens in string values work.
		$raw  = self::resolve_color_tokens( $raw, $overrides );
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return new \WP_Error( 'invalid_json', __( 'Invalid template data.', 'rawnaq' ), 500 );
		}

		// Module guard (skip when called from page-kit import).
		if ( ! $skip_module_check ) {
			$tpl_meta = null;
			foreach ( self::get_registry() as $t ) {
				if ( $t['id'] === $template_id ) { $tpl_meta = $t; break; }
			}
			if ( $tpl_meta ) {
				$missing = array_values( array_filter(
					$tpl_meta['required_modules'],
					static fn( $slug ) => ! rawnaq_is_module_enabled( $slug )
				) );
				if ( $missing ) {
					return new \WP_Error(
						'modules_missing',
						__( 'Some required modules are disabled.', 'rawnaq' ),
						[ 'status' => 409, 'missing_modules' => $missing ]
					);
				}
			}
		}

		$tpl_meta = $tpl_meta ?? null;
		if ( ! $tpl_meta ) {
			foreach ( self::get_registry() as $t ) {
				if ( $t['id'] === $template_id ) { $tpl_meta = $t; break; }
			}
		}

		return [
			'elements' => $data['elements'] ?? $data,
			'title'    => $tpl_meta ? $tpl_meta['title'] : $template_id,
		];
	}
}
