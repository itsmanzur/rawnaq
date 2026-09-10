<?php
/**
 * Rawnaq Divi Module: 3D Tilt Card Showcase
 *
 * @package Rawnaq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_ET_Tilt_Card extends ET_Builder_Module {

	public $slug       = 'rawnaq_et_tilt_card';
	public $vb_support = 'on';

	public function init() {
		$this->name       = esc_html__( 'Rawnaq 3D Tilt Card', 'rawnaq' );
		$this->icon_path  = 'format-image';
		$this->main_css_element = '%%order_class%%.rawnaq-tilt-card-wrap';
	}

	public function get_fields() {
		return [
			'card_title' => [
				'label'           => esc_html__( 'Card Title', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => 'Luxury Penthouse Residence',
				'toggle_slug'     => 'main_content',
			],
			'card_badge' => [
				'label'           => esc_html__( 'Badge Label', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => 'Featured Project',
				'toggle_slug'     => 'main_content',
			],
			'card_desc' => [
				'label'           => esc_html__( 'Description', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'default'         => 'Bespoke contemporary living suite overlooking panoramic city views with custom Italian marble and acoustic fluting.',
				'toggle_slug'     => 'main_content',
			],
			'card_image' => [
				'label'              => esc_html__( 'Background Image', 'rawnaq' ),
				'type'               => 'upload',
				'option_category'    => 'basic_option',
				'upload_button_text' => esc_attr__( 'Upload Image', 'rawnaq' ),
				'choose_text'        => esc_attr__( 'Choose Image', 'rawnaq' ),
				'update_text'        => esc_attr__( 'Set Image', 'rawnaq' ),
				'default'            => '',
				'toggle_slug'        => 'image',
			],
			'glare_effect' => [
				'label'           => esc_html__( 'Enable 3D Glass Glare Effect', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'on',
				'toggle_slug'     => 'effects',
			],
		];
	}

	public function render( $attrs, $content = null, $render_slug = '' ) {
		wp_enqueue_style( 'rawnaq-tilt-card' );
		wp_enqueue_script( 'rawnaq-tilt-card' );

		$title = sanitize_text_field( $this->props['card_title'] ?? 'Luxury Penthouse Residence' );
		$badge = sanitize_text_field( $this->props['card_badge'] ?? 'Featured Project' );
		$desc  = sanitize_text_field( $this->props['card_desc'] ?? '' );
		$image = esc_url( $this->props['card_image'] ?? '' );
		$glare = ( $this->props['glare_effect'] ?? 'on' ) === 'on' ? 'yes' : 'no';

		ob_start();
		?>
		<div class="rawnaq-tilt-card-wrap" data-tilt data-tilt-glare="<?php echo esc_attr( $glare ); ?>" data-tilt-max="15">
			<div class="rawnaq-tilt-card-inner" <?php echo $image ? 'style="background-image:linear-gradient(180deg, rgba(15,23,42,0.2) 0%, rgba(15,23,42,0.85) 100%), url(' . esc_url( $image ) . ');"' : ''; ?>>
				<?php if ( $badge ) : ?>
					<span class="rawnaq-tilt-badge"><?php echo esc_html( $badge ); ?></span>
				<?php endif; ?>
				<div class="rawnaq-tilt-body">
					<h3 class="rawnaq-tilt-title"><?php echo esc_html( $title ); ?></h3>
					<?php if ( $desc ) : ?>
						<p class="rawnaq-tilt-desc"><?php echo esc_html( $desc ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
