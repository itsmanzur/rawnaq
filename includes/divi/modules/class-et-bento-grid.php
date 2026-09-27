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
				'description'     => esc_html__( 'JSON array of cells [{type, tag, title, subtitle, icon, image, stat, suffix, col, row, link, ctaText}]', 'rawnaq' ),
				'default'         => '[{"type":"featured","tag":"Flagship","title":"Next-Gen Architectural Solutions","subtitle":"Precision spatial engineering for luxury residential complexes and bespoke penthouses.","col":2,"row":2,"ctaText":"Explore Portfolio","ctaLink":"#portfolio"},{"type":"stat","tag":"Proven Results","stat":"99.8","suffix":"%","title":"On-Time Delivery","subtitle":"Milestone adherence across all projects.","col":1,"row":1},{"type":"icon","icon":"dashicons-art","tag":"Design","title":"Bespoke Interiors","subtitle":"Custom acoustic fluting & marble textures.","col":1,"row":1},{"type":"media","image":"https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=600","tag":"VR Space","title":"3D Digital Twins","col":2,"row":1}]',
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

		$preset       = sanitize_key( $this->props['grid_preset'] ?? 'featured' );
		$cols         = ( 'wide' === $preset ) ? 3 : ( ( 'custom' === $preset ) ? max( 2, min( 6, intval( $this->props['grid_columns'] ?? 4 ) ) ) : 4 );
		$hover        = sanitize_key( $this->props['hover_effect'] ?? 'lift' );
		$reveal       = ( $this->props['enable_reveal'] ?? 'on' ) === 'on';
		$hairline     = ( $this->props['hairline'] ?? 'off' ) === 'on';
		$row_height   = intval( $this->props['row_height'] ?? 190 );
		$col_gap      = intval( $this->props['col_gap'] ?? 16 );
		$row_gap      = intval( $this->props['row_gap'] ?? 16 );
		$radius       = intval( $this->props['border_radius'] ?? 16 );
		$cell_bg      = $this->props['cell_bg'] ?? '#ffffff';
		$cell_border  = $this->props['cell_border'] ?? '#e2e8f0';
		$tag_bg       = $this->props['tag_bg'] ?? '#f1f5f9';
		$tag_color    = $this->props['tag_color'] ?? '#0f766e';
		$feat_from    = $this->props['featured_from'] ?? '#0f766e';
		$feat_to      = $this->props['featured_to'] ?? '#115e59';

		$classes = [ 'rawnaq-bento-grid' ];
		if ( $hairline ) {
			$classes[] = 'rawnaq-bento-hairline';
		}

		$style_parts = [
			'--bento-row: ' . esc_attr( (string) $row_height ) . 'px',
			'--bento-gap-col: ' . esc_attr( (string) $col_gap ) . 'px',
			'--bento-gap-row: ' . esc_attr( (string) $row_gap ) . 'px',
			'--bento-radius: ' . esc_attr( (string) $radius ) . 'px',
			'--bento-panel: ' . esc_attr( $cell_bg ),
			'--bento-line: ' . esc_attr( $cell_border ),
			'--bento-tag-bg: ' . esc_attr( $tag_bg ),
			'--bento-tag-color: ' . esc_attr( $tag_color ),
			'--bento-featured-from: ' . esc_attr( $feat_from ),
			'--bento-featured-to: ' . esc_attr( $feat_to ),
		];
		$style = implode( ';', $style_parts ) . ';';

		$raw_cells = json_decode( $this->props['cells_json'] ?? '[]', true ) ?: [];

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			 style="<?php echo esc_attr( $style ); ?>"
			 data-cols="<?php echo esc_attr( (string) $cols ); ?>"
			 data-reveal="<?php echo $reveal ? '1' : '0'; ?>"
			 data-hover="<?php echo esc_attr( $hover ); ?>"
			 role="list">
			<?php foreach ( $raw_cells as $cell ) :
				$type     = sanitize_key( $cell['type'] ?? 'text' );
				$tag      = sanitize_text_field( $cell['tag'] ?? ( $cell['badge'] ?? '' ) );
				$title    = sanitize_text_field( $cell['title'] ?? '' );
				$subtitle = sanitize_text_field( $cell['subtitle'] ?? ( $cell['desc'] ?? '' ) );
				$icon     = sanitize_html_class( $cell['icon'] ?? '' );
				$image    = ! empty( $cell['image'] ) ? esc_url( $cell['image'] ) : '';
				$link     = ! empty( $cell['link'] ) ? esc_url( $cell['link'] ) : '';
				$stat     = sanitize_text_field( $cell['stat'] ?? '' );
				$suffix   = sanitize_text_field( $cell['suffix'] ?? '' );
				$prefix   = sanitize_text_field( $cell['prefix'] ?? '' );
				$num      = floatval( preg_replace( '/[^\d.]/', '', $stat ) );
				$cta_text = sanitize_text_field( $cell['ctaText'] ?? ( $cell['cta_text'] ?? '' ) );
				$cta_link = ! empty( $cell['ctaLink'] ) ? esc_url( $cell['ctaLink'] ) : ( ! empty( $cell['cta_link'] ) ? esc_url( $cell['cta_link'] ) : $link );

				$col_span = max( 1, min( 6, intval( $cell['col'] ?? ( $cell['span'] === 'span-2' ? 2 : 1 ) ) ) );
				$row_span = max( 1, min( 4, intval( $cell['row'] ?? 1 ) ) );

				$cell_classes = [ 'rawnaq-bento-cell', 'type-' . $type, 'span-' . $col_span ];
				if ( $col_span > 1 ) {
					$cell_classes[] = 'col-span-' . $col_span;
				}
				if ( $row_span > 1 ) {
					$cell_classes[] = 'row-span-' . $row_span;
				}

				$cell_style = sprintf( 'grid-column: span %d; grid-row: span %d;', $col_span, $row_span );
				if ( ! empty( $cell['bg'] ) ) {
					$cell_style .= ' background-color: ' . esc_attr( $cell['bg'] ) . ';';
				}
				if ( ! empty( $cell['color'] ) ) {
					$cell_style .= ' color: ' . esc_attr( $cell['color'] ) . ';';
				}
				?>
				<div class="<?php echo esc_attr( implode( ' ', $cell_classes ) ); ?>"
					 style="<?php echo esc_attr( $cell_style ); ?>"
					 role="listitem">
					<?php if ( $image ) : ?>
						<img class="rawnaq-bento-media" src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" decoding="async" />
						<div class="rawnaq-bento-overlay" aria-hidden="true"></div>
					<?php endif; ?>
					<div class="rawnaq-bento-body">
						<?php if ( $tag ) : ?>
							<span class="rawnaq-bento-tag"><?php echo esc_html( $tag ); ?></span>
						<?php endif; ?>
						<?php if ( $icon ) : ?>
							<div class="rawnaq-bento-icon"><span class="dashicons <?php echo esc_attr( $icon ); ?>"></span></div>
						<?php endif; ?>
						<?php if ( 'stat' === $type && $stat !== '' ) : ?>
							<div class="rawnaq-bento-stat">
								<span class="rawnaq-bento-num" data-count="<?php echo esc_attr( (string) $num ); ?>" data-suffix="<?php echo esc_attr( $suffix ); ?>" data-prefix="<?php echo esc_attr( $prefix ); ?>">
									<?php echo esc_html( $prefix . $stat . $suffix ); ?>
								</span>
							</div>
						<?php endif; ?>
						<?php if ( $title ) : ?>
							<h3 class="rawnaq-bento-title">
								<?php if ( $link && empty( $cta_text ) ) : ?>
									<a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $title ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $title ); ?>
								<?php endif; ?>
							</h3>
						<?php endif; ?>
						<?php if ( $subtitle ) : ?>
							<p class="rawnaq-bento-sub"><?php echo esc_html( $subtitle ); ?></p>
						<?php endif; ?>
						<?php if ( $cta_text ) : ?>
							<div class="rawnaq-bento-cta">
								<a class="rawnaq-bento-btn" href="<?php echo esc_url( $cta_link ?: '#' ); ?>"><?php echo esc_html( $cta_text ); ?></a>
							</div>
						<?php endif; ?>
					</div>
					<?php if ( $link && empty( $cta_text ) ) : ?>
						<a class="rawnaq-bento-stretch-link" href="<?php echo esc_url( $link ); ?>" aria-label="<?php echo esc_attr( $title ); ?>"></a>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
