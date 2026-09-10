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
		$this->name       = esc_html__( 'Rawnaq Scroll Timeline', 'rawnaq' );
		$this->icon_path  = 'clock';
		$this->main_css_element = '%%order_class%%.rawnaq-scroll-timeline-wrap';
	}

	public function get_fields() {
		return [
			'timeline_title' => [
				'label'           => esc_html__( 'Timeline Title', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => 'Our Journey & Milestones',
				'toggle_slug'     => 'main_content',
			],
			'items_json' => [
				'label'           => esc_html__( 'Timeline Milestones JSON', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'default'         => '[{"year":"2021","title":"Studio Founded","desc":"Established in Dubai with a vision for minimal luxury spatial architecture."},{"year":"2023","title":"International Design Award","desc":"Recognized for pioneering eco-sustainable luxury hospitality spaces."},{"year":"2025","title":"50+ Turnkey Landmarks","desc":"Over 50 bespoke residential and commercial landmarks delivered worldwide."}]',
				'toggle_slug'     => 'items',
			],
			'accent_color' => [
				'label'        => esc_html__( 'Line & Badge Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#0f766e',
				'toggle_slug'  => 'style',
			],
		];
	}

	public function render( $attrs, $content = null, $render_slug = '' ) {
		wp_enqueue_style( 'rawnaq-scroll-timeline' );
		wp_enqueue_script( 'rawnaq-scroll-timeline' );

		$title        = sanitize_text_field( $this->props['timeline_title'] ?? 'Our Journey & Milestones' );
		$accent_color = sanitize_hex_color( $this->props['accent_color'] ?? '#0f766e' ) ?: '#0f766e';
		$raw_items    = $this->props['items_json'] ?? '';

		$items = json_decode( $raw_items, true );
		if ( ! is_array( $items ) || empty( $items ) ) {
			$items = [
				[ 'year' => '2021', 'title' => 'Studio Founded', 'desc' => 'Established in Dubai.' ],
				[ 'year' => '2023', 'title' => 'Design Award', 'desc' => 'Recognized globally.' ],
				[ 'year' => '2025', 'title' => '50+ Landmarks', 'desc' => 'Delivered worldwide.' ],
			];
		}

		ob_start();
		?>
		<div class="rawnaq-scroll-timeline-wrap" style="--rq-accent: <?php echo esc_attr( $accent_color ); ?>;">
			<?php if ( $title ) : ?>
				<h3 class="rawnaq-timeline-heading"><?php echo esc_html( $title ); ?></h3>
			<?php endif; ?>
			<div class="rawnaq-timeline-container">
				<div class="rawnaq-timeline-spine">
					<div class="rawnaq-timeline-spine-fill"></div>
				</div>
				<?php foreach ( $items as $idx => $item ) : ?>
					<div class="rawnaq-timeline-item <?php echo ( $idx % 2 === 0 ) ? 'left' : 'right'; ?>">
						<div class="rawnaq-timeline-dot"></div>
						<div class="rawnaq-timeline-card">
							<span class="rawnaq-timeline-year"><?php echo esc_html( $item['year'] ?? '' ); ?></span>
							<h4 class="rawnaq-timeline-card-title"><?php echo esc_html( $item['title'] ?? 'Milestone' ); ?></h4>
							<?php if ( ! empty( $item['desc'] ) ) : ?>
								<p class="rawnaq-timeline-card-desc"><?php echo esc_html( $item['desc'] ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
