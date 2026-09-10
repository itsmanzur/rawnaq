<?php
/**
 * Rawnaq Divi Module: Interactive Hub Diagram & Ecosystem Map
 *
 * @package Rawnaq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_ET_Hub_Diagram extends ET_Builder_Module {

	public $slug       = 'rawnaq_et_hub_diagram';
	public $vb_support = 'on';

	public function init() {
		$this->name       = esc_html__( 'Rawnaq Hub Diagram', 'rawnaq' );
		$this->icon_path  = 'networking';
		$this->main_css_element = '%%order_class%%.rawnaq-hub-diagram-wrap';
	}

	public function get_fields() {
		return [
			'center_title' => [
				'label'           => esc_html__( 'Center Hub Title', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => 'CORE ECOSYSTEM',
				'toggle_slug'     => 'main_content',
			],
			'center_subtitle' => [
				'label'           => esc_html__( 'Center Hub Subtitle', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => 'Integrated Services',
				'toggle_slug'     => 'main_content',
			],
			'spokes_json' => [
				'label'           => esc_html__( 'Connected Spoke Nodes JSON', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'default'         => '[{"title":"Architecture & Planning","icon":"dashicons-building","color":"#0f766e"},{"title":"Interior Design & Fitout","icon":"dashicons-art","color":"#d97706"},{"title":"Smart Automation","icon":"dashicons-lightbulb","color":"#2563eb"},{"title":"Structural Engineering","icon":"dashicons-hammer","color":"#9333ea"}]',
				'toggle_slug'     => 'nodes',
			],
			'accent_color' => [
				'label'        => esc_html__( 'Hub Accent Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#0f766e',
				'toggle_slug'  => 'style',
			],
		];
	}

	public function render( $attrs, $content = null, $render_slug = '' ) {
		wp_enqueue_style( 'rawnaq-hub-diagram' );
		wp_enqueue_script( 'rawnaq-hub-diagram' );

		$center_title = sanitize_text_field( $this->props['center_title'] ?? 'CORE ECOSYSTEM' );
		$center_sub   = sanitize_text_field( $this->props['center_subtitle'] ?? 'Integrated Services' );
		$accent_color = sanitize_hex_color( $this->props['accent_color'] ?? '#0f766e' ) ?: '#0f766e';
		$raw_spokes   = $this->props['spokes_json'] ?? '';

		$spokes = json_decode( $raw_spokes, true );
		if ( ! is_array( $spokes ) || empty( $spokes ) ) {
			$spokes = [
				[ 'title' => 'Architecture & Planning', 'icon' => 'dashicons-building', 'color' => '#0f766e' ],
				[ 'title' => 'Interior Design & Fitout', 'icon' => 'dashicons-art', 'color' => '#d97706' ],
				[ 'title' => 'Smart Automation', 'icon' => 'dashicons-lightbulb', 'color' => '#2563eb' ],
				[ 'title' => 'Structural Engineering', 'icon' => 'dashicons-hammer', 'color' => '#9333ea' ],
			];
		}

		ob_start();
		?>
		<div class="rawnaq-hub-diagram-wrap" style="--rq-accent: <?php echo esc_attr( $accent_color ); ?>;">
			<div class="rawnaq-hub-center-core">
				<div class="rawnaq-hub-core-pulse"></div>
				<h3 class="rawnaq-hub-core-title"><?php echo esc_html( $center_title ); ?></h3>
				<?php if ( $center_sub ) : ?>
					<p class="rawnaq-hub-core-sub"><?php echo esc_html( $center_sub ); ?></p>
				<?php endif; ?>
			</div>
			<div class="rawnaq-hub-spokes-list">
				<?php foreach ( $spokes as $spoke ) : ?>
					<div class="rawnaq-hub-spoke-card" style="border-left: 3px solid <?php echo esc_attr( $spoke['color'] ?? $accent_color ); ?>;">
						<?php if ( ! empty( $spoke['icon'] ) ) : ?>
							<span class="dashicons <?php echo esc_attr( $spoke['icon'] ); ?> rawnaq-hub-spoke-icon"></span>
						<?php endif; ?>
						<span class="rawnaq-hub-spoke-title"><?php echo esc_html( $spoke['title'] ?? 'Service' ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
