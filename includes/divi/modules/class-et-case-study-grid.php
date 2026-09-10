<?php
/**
 * Rawnaq Divi Module: Case Study Grid & Portfolio Showcase
 *
 * @package Rawnaq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_ET_Case_Study_Grid extends ET_Builder_Module {

	public $slug       = 'rawnaq_et_case_study_grid';
	public $vb_support = 'on';

	public function init() {
		$this->name       = esc_html__( 'Rawnaq Case Study Grid', 'rawnaq' );
		$this->icon_path  = 'portfolio';
		$this->main_css_element = '%%order_class%%.rawnaq-cs-grid-wrapper';
	}

	public function get_fields() {
		return [
			'posts_per_page' => [
				'label'           => esc_html__( 'Projects Per Page', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'configuration',
				'range_settings'  => [
					'min'  => 3,
					'max'  => 24,
					'step' => 3,
				],
				'default'         => '6',
				'toggle_slug'     => 'query',
			],
			'show_filter' => [
				'label'           => esc_html__( 'Show Category Filter Tabs', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'on',
				'toggle_slug'     => 'query',
			],
			'accent_color' => [
				'label'        => esc_html__( 'Filter & Badge Accent Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#0f766e',
				'toggle_slug'  => 'style',
			],
		];
	}

	public function render( $attrs, $content = null, $render_slug = '' ) {
		wp_enqueue_style( 'rawnaq-case-study' );
		wp_enqueue_script( 'rawnaq-case-study' );

		$posts_per_page = absint( $this->props['posts_per_page'] ?? 6 );
		$show_filter    = ( $this->props['show_filter'] ?? 'on' ) === 'on';
		$accent_color   = sanitize_hex_color( $this->props['accent_color'] ?? '#0f766e' ) ?: '#0f766e';

		$query_args = [
			'post_type'      => 'post',
			'posts_per_page' => $posts_per_page,
			'post_status'    => 'publish',
		];
		$query = new WP_Query( $query_args );

		ob_start();
		?>
		<div class="rawnaq-cs-grid-wrapper" style="--rq-accent: <?php echo esc_attr( $accent_color ); ?>;">
			<?php if ( $show_filter ) : ?>
				<div class="rawnaq-cs-filters">
					<button type="button" class="rawnaq-cs-filter-btn is-active" data-cat="all"><?php esc_html_e( 'All Works', 'rawnaq' ); ?></button>
				</div>
			<?php endif; ?>
			<div class="rawnaq-cs-grid">
				<?php
				if ( $query->have_posts() ) :
					while ( $query->have_posts() ) :
						$query->the_post();
						?>
						<article class="rawnaq-cs-card">
							<?php if ( has_post_thumbnail() ) : ?>
								<div class="rawnaq-cs-thumb">
									<?php the_post_thumbnail( 'large' ); ?>
								</div>
							<?php endif; ?>
							<div class="rawnaq-cs-content">
								<h3 class="rawnaq-cs-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
								<p class="rawnaq-cs-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
							</div>
						</article>
						<?php
					endwhile;
					wp_reset_postdata();
				else :
					?>
					<div class="rawnaq-cs-placeholder">
						<p><?php esc_html_e( 'Portfolio showcase ready. Add posts or case studies to display.', 'rawnaq' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
