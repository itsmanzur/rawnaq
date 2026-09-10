<?php
/**
 * Rawnaq Divi Module: Scroll Progress Table of Contents
 *
 * @package Rawnaq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_ET_Scroll_Progress_TOC extends ET_Builder_Module {

	public $slug       = 'rawnaq_et_scroll_progress_toc';
	public $vb_support = 'on';

	public function init() {
		$this->name       = esc_html__( 'Rawnaq Scroll Progress & TOC', 'rawnaq' );
		$this->icon_path  = 'list-view';
		$this->main_css_element = '%%order_class%%.rawnaq-toc-wrapper';
	}

	public function get_fields() {
		return [
			'toc_title' => [
				'label'           => esc_html__( 'TOC Heading', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => 'Quick Navigation',
				'toggle_slug'     => 'main_content',
			],
			'target_selector' => [
				'label'           => esc_html__( 'Target Content Container Selector', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'default'         => '.entry-content, main, article, .et_pb_section',
				'toggle_slug'     => 'settings',
			],
			'accent_color' => [
				'label'        => esc_html__( 'Active Indicator Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#0f766e',
				'toggle_slug'  => 'style',
			],
		];
	}

	public function render( $attrs, $content = null, $render_slug = '' ) {
		wp_enqueue_style( 'rawnaq-scroll-progress-toc' );
		wp_enqueue_script( 'rawnaq-scroll-progress-toc' );

		$title           = sanitize_text_field( $this->props['toc_title'] ?? 'Quick Navigation' );
		$target_selector = sanitize_text_field( $this->props['target_selector'] ?? '.entry-content, main, article, .et_pb_section' );
		$accent_color    = sanitize_hex_color( $this->props['accent_color'] ?? '#0f766e' ) ?: '#0f766e';

		ob_start();
		?>
		<div class="rawnaq-toc-wrapper" data-target="<?php echo esc_attr( $target_selector ); ?>" style="--rq-accent: <?php echo esc_attr( $accent_color ); ?>;">
			<div class="rawnaq-toc-header">
				<h4 class="rawnaq-toc-title"><?php echo esc_html( $title ); ?></h4>
				<div class="rawnaq-toc-bar"><div class="rawnaq-toc-progress-fill"></div></div>
			</div>
			<nav class="rawnaq-toc-list" aria-label="<?php esc_attr_e( 'Table of Contents', 'rawnaq' ); ?>">
				<!-- Auto-populated via JS -->
			</nav>
		</div>
		<?php
		return ob_get_clean();
	}
}
