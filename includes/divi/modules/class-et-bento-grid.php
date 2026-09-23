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
		$this->name       = esc_html__( 'Rawnaq Bento Grid', 'rawnaq' );
		$this->icon_path  = 'grid';
		$this->main_css_element = '%%order_class%%.rawnaq-bento-grid-wrapper';
	}

	public function get_fields() {
		return [
			'grid_preset' => [
				'label'           => esc_html__( 'Bento Layout Preset', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'featured' => esc_html__( 'Featured Hero Cell (4-Cell Bento)', 'rawnaq' ),
					'equal'    => esc_html__( 'Equal Multi-Cell Grid', 'rawnaq' ),
					'wide'     => esc_html__( 'Wide Asymmetrical Showcase', 'rawnaq' ),
				],
				'default'         => 'featured',
				'toggle_slug'     => 'layout',
			],
			'cells_json' => [
				'label'           => esc_html__( 'Bento Cells JSON', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'default'         => '[{"title":"Luxury Villa Architecture","badge":"Bespoke","desc":"Turnkey spatial planning and luxury finishes.","span":"span-2","bg":"#0f172a","color":"#ffffff"},{"title":"Interior Styling","badge":"Design","desc":"Bespoke furniture & lighting.","span":"span-1","bg":"#1e293b","color":"#ffffff"},{"title":"3D Walkthrough","badge":"VR Tour","desc":"Immersive visualization experience.","span":"span-1","bg":"#0f766e","color":"#ffffff"},{"title":"Project Management","badge":"Turnkey","desc":"On-site supervision & milestone guarantees.","span":"span-2","bg":"#115e59","color":"#ffffff"}]',
				'toggle_slug'     => 'cells',
			],
		];
	}

	public function render( $attrs, $content = null, $render_slug = '' ) {
		wp_enqueue_style( 'rawnaq-bento-grid' );
		wp_enqueue_script( 'rawnaq-bento-grid' );

		$preset    = sanitize_text_field( $this->props['grid_preset'] ?? 'featured' );
		$raw_cells = $this->props['cells_json'] ?? '';

		$cells = json_decode( $raw_cells, true );
		if ( ! is_array( $cells ) || empty( $cells ) ) {
			$cells = [
				[ 'title' => 'Luxury Villa Architecture', 'badge' => 'Bespoke', 'desc' => 'Turnkey spatial planning.', 'span' => 'span-2', 'bg' => '#0f172a', 'color' => '#ffffff' ],
				[ 'title' => 'Interior Styling', 'badge' => 'Design', 'desc' => 'Bespoke furniture.', 'span' => 'span-1', 'bg' => '#1e293b', 'color' => '#ffffff' ],
				[ 'title' => '3D Walkthrough', 'badge' => 'VR Tour', 'desc' => 'Immersive visualization.', 'span' => 'span-1', 'bg' => '#0f766e', 'color' => '#ffffff' ],
				[ 'title' => 'Project Management', 'badge' => 'Turnkey', 'desc' => 'On-site supervision.', 'span' => 'span-2', 'bg' => '#115e59', 'color' => '#ffffff' ],
			];
		}

		ob_start();
		?>
		<div class="rawnaq-bento-grid-wrapper preset-<?php echo esc_attr( $preset ); ?>">
			<div class="rawnaq-bento-grid">
				<?php foreach ( $cells as $cell ) :
					$type = sanitize_key( $cell['type'] ?? 'text' );
					if ( 'post' === $type && function_exists( 'rawnaq_bento_get_post_data' ) ) :
						$pdata = rawnaq_bento_get_post_data( $cell );
						$title = ( ! empty( $cell['title'] ) && 'Title' !== $cell['title'] ) ? $cell['title'] : $pdata['title'];
						$desc  = ! empty( $cell['desc'] ) ? $cell['desc'] : $pdata['excerpt'];
						$badge = ! empty( $cell['badge'] ) ? $cell['badge'] : $pdata['tag'];
						$link  = ! empty( $cell['link'] ) ? $cell['link'] : $pdata['link'];
						$bg    = ! empty( $cell['bg'] ) ? $cell['bg'] : '#0f172a';
						$color = ! empty( $cell['color'] ) ? $cell['color'] : '#ffffff';
						$span  = $cell['span'] ?? 'span-1';
						$img   = $pdata['image'] ?? '';
						?>
						<div class="rawnaq-bento-cell is-post is-cover <?php echo esc_attr( $span ); ?>" style="background-color: <?php echo esc_attr( $bg ); ?>; color: <?php echo esc_attr( $color ); ?>;">
							<?php if ( $img ) : ?>
								<img class="rawnaq-bento-media" src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" decoding="async" />
								<div class="rawnaq-bento-overlay" aria-hidden="true"></div>
							<?php endif; ?>
							<div class="rawnaq-bento-body" style="position:relative;z-index:2;">
								<?php if ( $badge ) : ?>
									<span class="rawnaq-bento-badge rawnaq-bento-tag"><?php echo esc_html( $badge ); ?></span>
								<?php endif; ?>
								<?php if ( ! empty( $pdata['date'] ) || ! empty( $pdata['author'] ) ) : ?>
									<div class="rawnaq-bento-post-meta">
										<?php if ( ! empty( $pdata['author'] ) ) : ?>
											<span class="rawnaq-bento-post-author"><?php echo esc_html( $pdata['author'] ); ?></span>
										<?php endif; ?>
										<?php if ( ! empty( $pdata['date'] ) ) : ?>
											<span class="rawnaq-bento-post-date"><?php echo esc_html( $pdata['date'] ); ?></span>
										<?php endif; ?>
									</div>
								<?php endif; ?>
								<h3 class="rawnaq-bento-title" style="color:inherit;"><a href="<?php echo esc_url( $link ); ?>" style="color:inherit;text-decoration:none;"><?php echo esc_html( $title ); ?></a></h3>
								<?php if ( $desc ) : ?>
									<p class="rawnaq-bento-desc rawnaq-bento-sub" style="color:inherit;opacity:0.85;"><?php echo esc_html( $desc ); ?></p>
								<?php endif; ?>
							</div>
						</div>
					<?php else : ?>
						<div class="rawnaq-bento-cell <?php echo esc_attr( $cell['span'] ?? 'span-1' ); ?>" style="background-color: <?php echo esc_attr( $cell['bg'] ?? '#0f172a' ); ?>; color: <?php echo esc_attr( $cell['color'] ?? '#ffffff' ); ?>;">
							<?php if ( ! empty( $cell['badge'] ) ) : ?>
								<span class="rawnaq-bento-badge"><?php echo esc_html( $cell['badge'] ); ?></span>
							<?php endif; ?>
							<div class="rawnaq-bento-inner">
								<h3 class="rawnaq-bento-title" style="color:inherit;"><?php echo esc_html( $cell['title'] ?? 'Title' ); ?></h3>
								<?php if ( ! empty( $cell['desc'] ) ) : ?>
									<p class="rawnaq-bento-desc" style="color:inherit;opacity:0.85;"><?php echo esc_html( $cell['desc'] ); ?></p>
								<?php endif; ?>
							</div>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
