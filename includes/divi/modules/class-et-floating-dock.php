<?php
/**
 * Rawnaq Divi Module: Floating Dock & Fast Action Bar
 *
 * @package Rawnaq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_ET_Floating_Dock extends ET_Builder_Module {

	public $slug       = 'rawnaq_et_floating_dock';
	public $vb_support = 'on';

	public function init() {
		$this->name       = esc_html__( 'Rawnaq Floating Dock', 'rawnaq' );
		$this->icon_path  = 'admin-links';
		$this->main_css_element = '%%order_class%%.rawnaq-floating-dock';
	}

	public function get_fields() {
		return [
			'dock_position' => [
				'label'           => esc_html__( 'Screen Dock Position', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'bottom_center' => esc_html__( 'Bottom Center (Mac Dock Style)', 'rawnaq' ),
					'bottom_right'  => esc_html__( 'Bottom Right Corner', 'rawnaq' ),
					'bottom_left'   => esc_html__( 'Bottom Left Corner', 'rawnaq' ),
				],
				'default'         => 'bottom_center',
				'toggle_slug'     => 'layout',
			],
			'items_json' => [
				'label'           => esc_html__( 'Dock Action Items JSON', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'default'         => '[{"title":"WhatsApp","icon":"dashicons-format-chat","link":"https://wa.me/15551234567","badge":""},{"title":"Call Studio","icon":"dashicons-phone","link":"tel:+15551234567","badge":""},{"title":"Get Quote","icon":"dashicons-clipboard","link":"#rawnaq-get-quote","badge":"Fast"},{"title":"Scroll to Top","icon":"dashicons-arrow-up-alt2","link":"#top","badge":""}]',
				'toggle_slug'     => 'items',
			],
		];
	}

	public function render( $attrs, $content = null, $render_slug = '' ) {
		wp_enqueue_style( 'rawnaq-floating-dock' );
		wp_enqueue_script( 'rawnaq-floating-dock' );

		$position  = sanitize_text_field( $this->props['dock_position'] ?? 'bottom_center' );
		$raw_items = $this->props['items_json'] ?? '';

		$items = json_decode( $raw_items, true );
		if ( ! is_array( $items ) || empty( $items ) ) {
			$items = [
				[ 'title' => 'WhatsApp', 'icon' => 'dashicons-format-chat', 'link' => '#', 'badge' => '' ],
				[ 'title' => 'Get Quote', 'icon' => 'dashicons-clipboard', 'link' => '#rawnaq-get-quote', 'badge' => 'Fast' ],
				[ 'title' => 'Top', 'icon' => 'dashicons-arrow-up-alt2', 'link' => '#top', 'badge' => '' ],
			];
		}

		ob_start();
		?>
		<div class="rawnaq-floating-dock pos-<?php echo esc_attr( $position ); ?>">
			<div class="rawnaq-dock-bar">
				<?php foreach ( $items as $item ) : ?>
					<a href="<?php echo esc_url( $item['link'] ?? '#' ); ?>" class="rawnaq-dock-item" title="<?php echo esc_attr( $item['title'] ?? '' ); ?>">
						<?php if ( ! empty( $item['badge'] ) ) : ?>
							<span class="rawnaq-dock-badge"><?php echo esc_html( $item['badge'] ); ?></span>
						<?php endif; ?>
						<span class="dashicons <?php echo esc_attr( $item['icon'] ?? 'dashicons-admin-links' ); ?> rawnaq-dock-icon"></span>
						<span class="rawnaq-dock-label"><?php echo esc_html( $item['title'] ?? '' ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
