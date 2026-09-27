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
		$this->name            = esc_html__( 'Rawnaq 3D Tilt Card', 'rawnaq' );
		$this->icon_path       = 'format-image';
		$this->main_css_element = '%%order_class%% .rawnaq-tilt-card';
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
			'cta_text' => [
				'label'           => esc_html__( 'Button / CTA Text', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => 'Explore Residence',
				'toggle_slug'     => 'main_content',
			],
			'cta_link' => [
				'label'           => esc_html__( 'Button / Card Link URL', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
				'toggle_slug'     => 'link',
			],
			'link_target' => [
				'label'           => esc_html__( 'Open Link In New Tab', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'off' => esc_html__( 'No', 'rawnaq' ),
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'link',
			],
			'max_tilt' => [
				'label'           => esc_html__( 'Max Tilt Angle (deg)', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'layout',
				'default'         => '15',
				'range_settings'  => [
					'min'  => 0,
					'max'  => 45,
					'step' => 1,
				],
				'toggle_slug'     => 'tilt_settings',
			],
			'hover_scale' => [
				'label'           => esc_html__( 'Hover Scale Factor', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'layout',
				'default'         => '1.03',
				'toggle_slug'     => 'tilt_settings',
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
				'toggle_slug'     => 'tilt_settings',
			],
			'enable_spotlight' => [
				'label'           => esc_html__( 'Spotlight Border Lighting', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'on',
				'toggle_slug'     => 'tilt_settings',
			],
			'enable_holo' => [
				'label'           => esc_html__( 'Holographic Iridescent Sheen', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'off' => esc_html__( 'No', 'rawnaq' ),
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'tilt_settings',
			],
			'enable_gyro' => [
				'label'           => esc_html__( 'Mobile Gyroscope 3D Tilt', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'on',
				'toggle_slug'     => 'tilt_settings',
			],
			'enable_flip' => [
				'label'           => esc_html__( 'Enable 3D Flip Card', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'off' => esc_html__( 'No', 'rawnaq' ),
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'flip_card',
			],
			'flip_trigger' => [
				'label'           => esc_html__( 'Flip Trigger', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'configuration',
				'options'         => [
					'hover' => esc_html__( 'On Hover', 'rawnaq' ),
					'click' => esc_html__( 'On Click / Tap', 'rawnaq' ),
				],
				'default'         => 'hover',
				'toggle_slug'     => 'flip_card',
			],
			'back_title' => [
				'label'           => esc_html__( 'Back Title', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => 'Why Choose This Unit',
				'toggle_slug'     => 'flip_card',
			],
			'back_desc' => [
				'label'           => esc_html__( 'Back Description', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'default'         => 'Includes smart home automation, private elevator access, and 24/7 dedicated concierge service.',
				'toggle_slug'     => 'flip_card',
			],
			'back_cta_text' => [
				'label'           => esc_html__( 'Back Button Text', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => 'Book Private Tour',
				'toggle_slug'     => 'flip_card',
			],
			'back_cta_link' => [
				'label'           => esc_html__( 'Back Button URL', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
				'toggle_slug'     => 'flip_card',
			],
			'card_height' => [
				'label'           => esc_html__( 'Card Height (px)', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'layout',
				'default'         => '380',
				'range_settings'  => [
					'min'  => 200,
					'max'  => 800,
					'step' => 10,
				],
				'toggle_slug'     => 'style_settings',
			],
			'card_radius' => [
				'label'           => esc_html__( 'Card Border Radius (px)', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'layout',
				'default'         => '20',
				'range_settings'  => [
					'min'  => 0,
					'max'  => 60,
					'step' => 1,
				],
				'toggle_slug'     => 'style_settings',
			],
		];
	}

	public function render( $attrs, $content = null, $render_slug = '' ) {
		wp_enqueue_style( 'rawnaq-tilt-card' );
		wp_enqueue_script( 'rawnaq-tilt-card' );

		$title            = sanitize_text_field( $this->props['card_title'] ?? 'Luxury Penthouse Residence' );
		$badge            = sanitize_text_field( $this->props['card_badge'] ?? 'Featured Project' );
		$desc             = sanitize_text_field( $this->props['card_desc'] ?? '' );
		$image            = esc_url( $this->props['card_image'] ?? '' );
		$cta_text         = trim( sanitize_text_field( $this->props['cta_text'] ?? '' ) );
		$cta_link         = esc_url( $this->props['cta_link'] ?? '' );
		$is_blank         = ( $this->props['link_target'] ?? 'off' ) === 'on';
		$target           = $is_blank ? '_blank' : '_self';
		$rel_value        = $is_blank ? 'noopener' : '';

		$max_tilt         = floatval( $this->props['max_tilt'] ?? 15 );
		$hover_scale      = floatval( $this->props['hover_scale'] ?? 1.03 );
		$glare_intensity  = ( $this->props['glare_effect'] ?? 'on' ) === 'on' ? 0.45 : 0;
		$enable_spotlight = ( $this->props['enable_spotlight'] ?? 'on' ) === 'on';
		$enable_holo      = ( $this->props['enable_holo'] ?? 'off' ) === 'on';
		$enable_gyro      = ( $this->props['enable_gyro'] ?? 'on' ) === 'on';
		$card_height      = intval( $this->props['card_height'] ?? 380 );
		$card_radius      = intval( $this->props['card_radius'] ?? 20 );

		$enable_flip      = ( $this->props['enable_flip'] ?? 'off' ) === 'on';
		$flip_trigger     = ( $this->props['flip_trigger'] ?? 'hover' ) === 'click' ? 'click' : 'hover';
		$back_title       = sanitize_text_field( $this->props['back_title'] ?? '' );
		$back_desc        = sanitize_text_field( $this->props['back_desc'] ?? '' );
		$back_cta_text    = trim( sanitize_text_field( $this->props['back_cta_text'] ?? '' ) );
		$back_cta_link    = esc_url( $this->props['back_cta_link'] ?? '' );

		$classes = [ 'rawnaq-tilt-card', 'align-bottom' ];
		if ( ! empty( $image ) ) {
			$classes[] = 'has-image';
		}
		if ( $enable_spotlight ) {
			$classes[] = 'has-spotlight';
		}
		if ( $enable_flip ) {
			$classes[] = 'is-flip';
			$classes[] = 'flip-' . $flip_trigger;
		}

		$style_parts = [
			'--overlay: 0.7',
			'--glare: ' . esc_attr( (string) $glare_intensity ),
			'--hover-scale: ' . esc_attr( (string) $hover_scale ),
			'border-radius: ' . esc_attr( (string) $card_radius ) . 'px',
			'height: ' . esc_attr( (string) $card_height ) . 'px',
		];
		$style = implode( ';', $style_parts ) . ';';

		$card_attrs = '';
		if ( $enable_flip && 'click' === $flip_trigger ) {
			$card_attrs = ' tabindex="0" role="button" aria-pressed="false" aria-label="' . esc_attr( $title ?: __( 'Flip card', 'rawnaq' ) ) . '"';
		}

		ob_start();
		?>
		<div class="rawnaq-tilt-container">
			<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
				 style="<?php echo esc_attr( $style ); ?>"
				 data-tilt-max="<?php echo esc_attr( (string) $max_tilt ); ?>"
				 data-hover-scale="<?php echo esc_attr( (string) $hover_scale ); ?>"
				 data-glare="<?php echo esc_attr( (string) $glare_intensity ); ?>"
				 data-gyro="<?php echo $enable_gyro ? 'yes' : 'no'; ?>"<?php
					echo $card_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>>
				<?php if ( $enable_flip ) : ?>
					<div class="rawnaq-tilt-flip">
						<div class="rawnaq-tilt-face rawnaq-tilt-front">
							<?php if ( ! empty( $image ) ) : ?>
								<img class="rawnaq-tilt-image" src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
								<span class="rawnaq-tilt-overlay" aria-hidden="true"></span>
							<?php endif; ?>
							<span class="rawnaq-tilt-glare" aria-hidden="true"></span>
							<?php if ( $enable_holo ) : ?>
								<span class="rawnaq-tilt-holo" aria-hidden="true"></span>
							<?php endif; ?>
							<?php if ( ! empty( $badge ) ) : ?>
								<span class="rawnaq-tilt-badge"><?php echo esc_html( $badge ); ?></span>
							<?php endif; ?>
							<div class="rawnaq-tilt-content">
								<?php if ( ! empty( $title ) ) : ?>
									<h3 class="rawnaq-tilt-title"><?php echo esc_html( $title ); ?></h3>
								<?php endif; ?>
								<?php if ( ! empty( $desc ) ) : ?>
									<p class="rawnaq-tilt-desc"><?php echo esc_html( $desc ); ?></p>
								<?php endif; ?>
								<?php if ( $cta_text && $cta_link ) : ?>
									<a class="rawnaq-tilt-btn" href="<?php echo esc_url( $cta_link ); ?>" target="<?php echo esc_attr( $target ); ?>"<?php if ( $rel_value ) : ?> rel="<?php echo esc_attr( $rel_value ); ?>"<?php endif; ?>><?php echo esc_html( $cta_text ); ?></a>
								<?php elseif ( $cta_text ) : ?>
									<span class="rawnaq-tilt-btn is-static"><?php echo esc_html( $cta_text ); ?></span>
								<?php endif; ?>
							</div>
						</div>
						<div class="rawnaq-tilt-back">
							<div class="rawnaq-tilt-back-inner">
								<?php if ( ! empty( $back_title ) ) : ?>
									<h3 class="rawnaq-tilt-back-title"><?php echo esc_html( $back_title ); ?></h3>
								<?php endif; ?>
								<?php if ( ! empty( $back_desc ) ) : ?>
									<p class="rawnaq-tilt-back-desc"><?php echo esc_html( $back_desc ); ?></p>
								<?php endif; ?>
								<?php if ( $back_cta_text && $back_cta_link ) : ?>
									<a class="rawnaq-tilt-btn rawnaq-tilt-back-btn" href="<?php echo esc_url( $back_cta_link ); ?>" target="<?php echo esc_attr( $target ); ?>"<?php if ( $rel_value ) : ?> rel="<?php echo esc_attr( $rel_value ); ?>"<?php endif; ?>><?php echo esc_html( $back_cta_text ); ?></a>
								<?php elseif ( $back_cta_text ) : ?>
									<span class="rawnaq-tilt-btn is-static"><?php echo esc_html( $back_cta_text ); ?></span>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php else : ?>
					<?php if ( ! empty( $image ) ) : ?>
						<img class="rawnaq-tilt-image" src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
						<span class="rawnaq-tilt-overlay" aria-hidden="true"></span>
					<?php endif; ?>
					<span class="rawnaq-tilt-glare" aria-hidden="true"></span>
					<?php if ( $enable_holo ) : ?>
						<span class="rawnaq-tilt-holo" aria-hidden="true"></span>
					<?php endif; ?>
					<?php if ( ! empty( $badge ) ) : ?>
						<span class="rawnaq-tilt-badge"><?php echo esc_html( $badge ); ?></span>
					<?php endif; ?>
					<div class="rawnaq-tilt-content">
						<?php if ( ! empty( $title ) ) : ?>
							<h3 class="rawnaq-tilt-title"><?php echo esc_html( $title ); ?></h3>
						<?php endif; ?>
						<?php if ( ! empty( $desc ) ) : ?>
							<p class="rawnaq-tilt-desc"><?php echo esc_html( $desc ); ?></p>
						<?php endif; ?>
						<?php if ( $cta_text && $cta_link ) : ?>
							<a class="rawnaq-tilt-btn" href="<?php echo esc_url( $cta_link ); ?>" target="<?php echo esc_attr( $target ); ?>"<?php if ( $rel_value ) : ?> rel="<?php echo esc_attr( $rel_value ); ?>"<?php endif; ?>><?php echo esc_html( $cta_text ); ?></a>
						<?php elseif ( $cta_text ) : ?>
							<span class="rawnaq-tilt-btn is-static"><?php echo esc_html( $cta_text ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( ! empty( $cta_link ) ) : ?>
						<a class="rawnaq-tilt-stretch-link" href="<?php echo esc_url( $cta_link ); ?>" target="<?php echo esc_attr( $target ); ?>"<?php if ( $rel_value ) : ?> rel="<?php echo esc_attr( $rel_value ); ?>"<?php endif; ?> aria-label="<?php echo esc_attr( $title ?: __( 'Open link', 'rawnaq' ) ); ?>"></a>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
