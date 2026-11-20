<?php
/**
 * Rawnaq Divi Module: Interactive Bento Grid Showcase
 *
 * @package Rawnaq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_ET_Bento_Grid extends ET_Builder_Module {

	public $slug       = 'rawnaq_et_bento_grid';
	public $vb_support = 'on';

	public function init() {
		$this->name            = esc_html__( 'Rawnaq Bento Grid', 'rawnaq' );
		$this->icon_path       = 'grid';
		$this->main_css_element = '%%order_class%% .rawnaq-bento-grid';
	}

	public function get_fields() {
		return [
			'bento_theme' => [
				'label'           => esc_html__( 'Theme Preset', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'elevated'  => esc_html__( 'Modern Elevated (Default)', 'rawnaq' ),
					'glass'     => esc_html__( 'Minimal Glassmorphism', 'rawnaq' ),
					'obsidian'  => esc_html__( 'Dark Obsidian / Cyber', 'rawnaq' ),
					'dark'      => esc_html__( 'Dark', 'rawnaq' ),
					'spotlight' => esc_html__( 'Editorial Spotlight', 'rawnaq' ),
				],
				'default'         => 'elevated',
				'toggle_slug'     => 'layout',
			],
			'title_tag' => [
				'label'           => esc_html__( 'Title Tag', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'div' => 'div',
					'h2'  => 'H2',
					'h3'  => 'H3',
					'h4'  => 'H4',
					'h5'  => 'H5',
					'h6'  => 'H6',
				],
				'default'         => 'div',
				'description'     => esc_html__( 'HTML tag used for every cell title.', 'rawnaq' ),
				'toggle_slug'     => 'layout',
			],
			'grid_preset' => [
				'label'           => esc_html__( 'Bento Layout Preset', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'featured' => esc_html__( 'Featured Hero Cell (4 Columns)', 'rawnaq' ),
					'wide'     => esc_html__( 'Wide Showcase (3 Columns)', 'rawnaq' ),
					'equal'    => esc_html__( 'Equal Grid (4 Columns)', 'rawnaq' ),
					'custom'   => esc_html__( 'Custom Column Grid', 'rawnaq' ),
				],
				'default'         => 'featured',
				'toggle_slug'     => 'layout',
			],
			'grid_columns' => [
				'label'           => esc_html__( 'Columns (Custom Preset)', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'layout',
				'default'         => '4',
				'range_settings'  => [
					'min'  => 2,
					'max'  => 6,
					'step' => 1,
				],
				'show_if'         => [ 'grid_preset' => 'custom' ],
				'toggle_slug'     => 'layout',
			],
			'hover_effect' => [
				'label'           => esc_html__( 'Cell Hover Animation', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'lift' => esc_html__( '3D Smooth Lift & Shadow', 'rawnaq' ),
					'zoom' => esc_html__( 'Subtle Inner Zoom', 'rawnaq' ),
					'tint' => esc_html__( 'Accent Color Glow', 'rawnaq' ),
					'none' => esc_html__( 'Static / None', 'rawnaq' ),
				],
				'default'         => 'lift',
				'toggle_slug'     => 'layout',
			],
			'enable_reveal' => [
				'label'           => esc_html__( 'Staggered Scroll Entrance Reveal', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'on',
				'toggle_slug'     => 'layout',
			],
			'hairline' => [
				'label'           => esc_html__( 'Seamless Hairline Grid Mode', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'off' => esc_html__( 'No', 'rawnaq' ),
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'layout',
			],
			'cells_json' => [
				'label'           => esc_html__( 'Bento Cells Configuration (JSON)', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'JSON array of cells [{type: text|featured|image|stat|video|testimonial|post, tag, title, subtitle, icon, image, alt, video, stat, prefix, suffix, role, avatar, rating, col, row, colMd, rowMd, colSm, rowSm, order, orderMd, orderSm, link, ctaText, ctaLink, bg, color, postSource, postId}]', 'rawnaq' ),
				'default'         => '[{"type":"featured","tag":"Spatial Intelligence","title":"Autonomous Spatial Design & AI Architecture","subtitle":"End-to-end cognitive spatial modeling and real-time biometric telemetry delivering 99.8% operational precision.","col":2,"row":2,"ctaText":"Explore Architecture","ctaLink":"#portfolio"},{"type":"stat","tag":"Growth Index","stat":"240","suffix":"%","title":"","subtitle":"Average Client ROI","col":1,"row":1},{"type":"icon","icon":"dashicons-networking","tag":"Core Tech","title":"Sub-50ms Telemetry","subtitle":"Streaming WebSocket events","col":1,"row":1},{"type":"media","image":"https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=900","tag":"Global Landmark","title":"Turnkey Penthouse & Flagships","subtitle":"Award-winning sustainable luxury landmarks.","col":2,"row":1}]',
				'toggle_slug'     => 'cells',
			],
			'row_height' => [
				'label'           => esc_html__( 'Base Row Height (px)', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'layout',
				'default'         => '190',
				'range_settings'  => [
					'min'  => 120,
					'max'  => 360,
					'step' => 5,
				],
				'toggle_slug'     => 'style',
			],
			'col_gap' => [
				'label'           => esc_html__( 'Column Gap (px)', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'layout',
				'default'         => '16',
				'range_settings'  => [
					'min'  => 0,
					'max'  => 40,
					'step' => 2,
				],
				'toggle_slug'     => 'style',
			],
			'row_gap' => [
				'label'           => esc_html__( 'Row Gap (px)', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'layout',
				'default'         => '16',
				'range_settings'  => [
					'min'  => 0,
					'max'  => 40,
					'step' => 2,
				],
				'toggle_slug'     => 'style',
			],
			'border_radius' => [
				'label'           => esc_html__( 'Cell Border Radius (px)', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'layout',
				'default'         => '16',
				'range_settings'  => [
					'min'  => 0,
					'max'  => 36,
					'step' => 1,
				],
				'toggle_slug'     => 'style',
			],
			'cell_bg' => [
				'label'        => esc_html__( 'Cell Card Background', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#ffffff',
				'toggle_slug'  => 'style',
			],
			'cell_border' => [
				'label'        => esc_html__( 'Cell Border Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#e2e8f0',
				'toggle_slug'  => 'style',
			],
			'tag_bg' => [
				'label'        => esc_html__( 'Tag Badge Background', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#f1f5f9',
				'toggle_slug'  => 'style',
			],
			'tag_color' => [
				'label'        => esc_html__( 'Tag Badge Text Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#0f766e',
				'toggle_slug'  => 'style',
			],
			'featured_from' => [
				'label'        => esc_html__( 'Featured Card Gradient Start', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#0f766e',
				'toggle_slug'  => 'style',
			],
			'featured_to' => [
				'label'        => esc_html__( 'Featured Card Gradient End', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#115e59',
				'toggle_slug'  => 'style',
			],
		];
	}

	public function render( $attrs, $content = null, $render_slug = '' ) {
		wp_enqueue_style( 'rawnaq-bento-grid' );
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_script( 'rawnaq-bento-grid' );

		$preset = sanitize_key( $this->props['grid_preset'] ?? 'featured' );
		$cols   = ( 'wide' === $preset ) ? 3 : ( ( 'custom' === $preset ) ? max( 2, min( 6, intval( $this->props['grid_columns'] ?? 4 ) ) ) : 4 );

		$color = function ( $key, $fallback ) {
			return rawnaq_sanitize_css_color( $this->props[ $key ] ?? '', $fallback );
		};

		$classes = [];
		if ( ( $this->props['hairline'] ?? 'off' ) === 'on' ) {
			$classes[] = 'rawnaq-bento-hairline';
		}

		$style_parts = [
			'--bento-row:' . intval( $this->props['row_height'] ?? 190 ) . 'px',
			'--bento-gap-col:' . intval( $this->props['col_gap'] ?? 16 ) . 'px',
			'--bento-gap-row:' . intval( $this->props['row_gap'] ?? 16 ) . 'px',
			'--bento-radius:' . intval( $this->props['border_radius'] ?? 16 ) . 'px',
			'--bento-panel:' . $color( 'cell_bg', '#ffffff' ),
			'--bento-line:' . $color( 'cell_border', '#e2e8f0' ),
			'--bento-tag-bg:' . $color( 'tag_bg', '#f1f5f9' ),
			'--bento-tag-color:' . $color( 'tag_color', '#0f766e' ),
			'--bento-featured-from:' . $color( 'featured_from', '#0f766e' ),
			'--bento-featured-to:' . $color( 'featured_to', '#115e59' ),
		];

		$raw_cells = json_decode( (string) ( $this->props['cells_json'] ?? '[]' ), true );
		if ( ! is_array( $raw_cells ) ) {
			$raw_cells = [];
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside rawnaq_bento_markup().
		return rawnaq_bento_markup(
			[
				'source'    => 'divi',
				'cols'      => $cols,
				'theme'     => $this->props['bento_theme'] ?? 'elevated',
				'reveal'    => ( $this->props['enable_reveal'] ?? 'on' ) === 'on',
				'hover'     => $this->props['hover_effect'] ?? 'lift',
				'classes'   => $classes,
				'style'     => implode( ';', $style_parts ) . ';',
				'title_tag' => $this->props['title_tag'] ?? 'div',
			],
			$raw_cells
		);
	}
}
