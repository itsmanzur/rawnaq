<?php
/**
 * Rawnaq Divi Module: Interactive Scroll Timeline
 *
 * @package Rawnaq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_ET_Scroll_Timeline extends ET_Builder_Module {

	public $slug       = 'rawnaq_et_scroll_timeline';
	public $vb_support = 'on';

	public function init() {
		$this->name            = esc_html__( 'Rawnaq Scroll Timeline', 'rawnaq' );
		$this->icon_path       = 'clock';
		$this->main_css_element = '%%order_class%% .rawnaq-timeline-wrapper';
	}

	public function get_fields() {
		return [
			'timeline_layout' => [
				'label'           => esc_html__( 'Timeline Layout', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'alternating' => esc_html__( 'Alternating (Zig-Zag)', 'rawnaq' ),
					'left'        => esc_html__( 'Left Spine (Cards on Right)', 'rawnaq' ),
					'right'       => esc_html__( 'Right Spine (Cards on Left)', 'rawnaq' ),
					'horizontal'  => esc_html__( 'Horizontal Process Bar', 'rawnaq' ),
				],
				'default'         => 'alternating',
				'toggle_slug'     => 'main_content',
			],
			'timeline_skin' => [
				'label'           => esc_html__( 'Timeline Visual Theme / Skin', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'classic' => esc_html__( 'Classic Card', 'rawnaq' ),
					'glass'   => esc_html__( 'Glassmorphism Blur', 'rawnaq' ),
					'minimal' => esc_html__( 'Minimal Editorial', 'rawnaq' ),
					'glow'    => esc_html__( 'Gradient Glow', 'rawnaq' ),
					'process' => esc_html__( 'Process Roadmap', 'rawnaq' ),
				],
				'default'         => 'classic',
				'toggle_slug'     => 'main_content',
			],
			'node_style' => [
				'label'           => esc_html__( 'Node Spine Style', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'number' => esc_html__( 'Step Numbers (01, 02...)', 'rawnaq' ),
					'icon'   => esc_html__( 'Custom Icons', 'rawnaq' ),
					'dot'    => esc_html__( 'Minimal Pulsing Dot', 'rawnaq' ),
				],
				'default'         => 'number',
				'toggle_slug'     => 'main_content',
			],
			'line_style' => [
				'label'           => esc_html__( 'Line Connector Style', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'solid'    => esc_html__( 'Solid Line', 'rawnaq' ),
					'dashed'   => esc_html__( 'Dashed Line', 'rawnaq' ),
					'dotted'   => esc_html__( 'Dotted Line', 'rawnaq' ),
					'gradient' => esc_html__( 'Gradient Flow', 'rawnaq' ),
				],
				'default'         => 'solid',
				'toggle_slug'     => 'main_content',
			],
			'active_node_glow' => [
				'label'           => esc_html__( 'Active Node Glow Shadow', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'on',
				'toggle_slug'     => 'effects',
			],
			'card_hover_tilt' => [
				'label'           => esc_html__( 'Card 3D Hover Tilt', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'on',
				'toggle_slug'     => 'effects',
			],
			'steps_json' => [
				'label'           => esc_html__( 'Timeline Milestones (JSON)', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'JSON array of milestones [{title, desc, meta, icon, link, ctaText}]', 'rawnaq' ),
				'default'         => '[{"meta":"2021","title":"Studio Founded","desc":"Established in Dubai with a vision for minimal luxury spatial architecture.","icon":"dashicons-building"},{"meta":"2023","title":"International Design Award","desc":"Recognized for pioneering eco-sustainable luxury hospitality spaces.","icon":"dashicons-awards"},{"meta":"2025","title":"50+ Turnkey Landmarks","desc":"Over 50 bespoke residential and commercial landmarks delivered worldwide.","icon":"dashicons-location-alt"}]',
				'toggle_slug'     => 'items',
			],
			'line_active' => [
				'label'        => esc_html__( 'Active Progress Line Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#6366f1',
				'toggle_slug'  => 'style',
			],
			'line_bg' => [
				'label'        => esc_html__( 'Inactive Spine Background', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#e2e8f0',
				'toggle_slug'  => 'style',
			],
			'card_bg' => [
				'label'        => esc_html__( 'Card Background Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#ffffff',
				'toggle_slug'  => 'style',
			],
			'meta_color' => [
				'label'        => esc_html__( 'Meta / Year Badge Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#6366f1',
				'toggle_slug'  => 'style',
			],
			'card_radius' => [
				'label'           => esc_html__( 'Card Border Radius (px)', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'layout',
				'default'         => '16',
				'range_settings'  => [
					'min'  => 0,
					'max'  => 40,
					'step' => 1,
				],
				'toggle_slug'     => 'style',
			],
			'item_gap' => [
				'label'           => esc_html__( 'Vertical Item Spacing (px)', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'layout',
				'default'         => '24',
				'range_settings'  => [
					'min'  => 8,
					'max'  => 80,
					'step' => 2,
				],
				'toggle_slug'     => 'style',
			],
		];
	}

	public function render( $attrs, $content = null, $render_slug = '' ) {
		wp_enqueue_style( 'rawnaq-scroll-timeline' );
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_script( 'rawnaq-scroll-timeline' );
		wp_enqueue_script( 'rawnaq-bridge' );

		$layout      = sanitize_html_class( $this->props['timeline_layout'] ?? 'alternating' );
		if ( ! in_array( $layout, [ 'alternating', 'left', 'right', 'horizontal' ], true ) ) {
			$layout = 'alternating';
		}
		$skin        = sanitize_key( $this->props['timeline_skin'] ?? 'classic' );
		$node_style  = sanitize_key( $this->props['node_style'] ?? 'number' );
		$line_style  = sanitize_key( $this->props['line_style'] ?? 'solid' );
		$active_glow = ( $this->props['active_node_glow'] ?? 'on' ) === 'on';
		$card_hover  = ( $this->props['card_hover_tilt'] ?? 'on' ) === 'on';
		$raw_steps   = $this->props['steps_json'] ?? '';

		$steps = json_decode( $raw_steps, true );
		if ( ! is_array( $steps ) || empty( $steps ) ) {
			$steps = [
				[ 'meta' => '2021', 'title' => 'Studio Founded', 'desc' => 'Established in Dubai with a vision for minimal luxury spatial architecture.', 'icon' => 'dashicons-building' ],
				[ 'meta' => '2023', 'title' => 'International Design Award', 'desc' => 'Recognized for pioneering eco-sustainable luxury hospitality spaces.', 'icon' => 'dashicons-awards' ],
				[ 'meta' => '2025', 'title' => '50+ Turnkey Landmarks', 'desc' => 'Over 50 bespoke residential and commercial landmarks delivered worldwide.', 'icon' => 'dashicons-location-alt' ],
			];
		}

		$line_active = sanitize_hex_color( $this->props['line_active'] ?? '#6366f1' ) ?: '#6366f1';
		$line_bg     = sanitize_hex_color( $this->props['line_bg'] ?? '#e2e8f0' ) ?: '#e2e8f0';
		$card_bg     = sanitize_hex_color( $this->props['card_bg'] ?? '#ffffff' ) ?: '#ffffff';
		$meta_color  = sanitize_hex_color( $this->props['meta_color'] ?? '#6366f1' ) ?: '#6366f1';
		$card_radius = intval( $this->props['card_radius'] ?? 16 );
		$item_gap    = intval( $this->props['item_gap'] ?? 24 );

		$wrap_class = 'rawnaq-timeline-wrapper layout-' . $layout
			. ' skin-' . sanitize_html_class( $skin )
			. ' node-' . sanitize_html_class( $node_style )
			. ' line-' . sanitize_html_class( $line_style );
		if ( 'number' === $node_style ) {
			$wrap_class .= ' show-numbers';
		}
		if ( $active_glow ) {
			$wrap_class .= ' has-node-glow';
		}
		if ( $card_hover ) {
			$wrap_class .= ' has-card-hover';
		}

		$tl_name    = 'rawnaq-divi-tl-' . wp_unique_id();
		$style_vars = [
			'--tl-line-bg'          => $line_bg,
			'--tl-line-active'      => $line_active,
			'--tl-line-gradient-to' => '#f59e0b',
			'--tl-glow-color'       => 'rgba(99, 102, 241, 0.45)',
			'--tl-glass-blur'       => '16px',
			'--tl-glass-border'     => 'rgba(255, 255, 255, 0.45)',
			'--tl-line-width'       => '4px',
			'--tl-bullet-border'    => '#cbd5e1',
			'--tl-bullet-active'    => $line_active,
			'--tl-card-bg'          => $card_bg,
			'--tl-meta'             => $meta_color,
			'--tl-title'            => '#1a1a1a',
			'--tl-desc'             => '#666666',
			'--tl-cta'              => $line_active,
			'--tl-card-radius'      => $card_radius . 'px',
			'--tl-bullet-size'      => '28px',
			'--tl-item-pad-y'       => $item_gap . 'px',
		];
		$style_attr = 'scroll-timeline-name: --' . $tl_name . ';';
		foreach ( $style_vars as $prop => $val ) {
			$style_attr .= $prop . ':' . $val . ';';
		}

		ob_start();
		?>
		<div
			class="<?php echo esc_attr( $wrap_class ); ?>"
			data-show-numbers="<?php echo ( 'number' === $node_style ) ? '1' : '0'; ?>"
			data-tl-name="<?php echo esc_attr( $tl_name ); ?>"
			data-initial-visible="0"
			data-load-chunk="3"
			style="<?php echo esc_attr( $style_attr ); ?>"
		>
			<div class="rawnaq-timeline-line-bg"></div>
			<div class="rawnaq-timeline-line-active"></div>
			<?php
			if ( function_exists( 'rawnaq_timeline_render_items_html' ) ) {
				echo rawnaq_timeline_render_items_html( $steps, $layout, ( 'number' === $node_style ), 0, $node_style ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>
		<?php
		return ob_get_clean();
	}
}
