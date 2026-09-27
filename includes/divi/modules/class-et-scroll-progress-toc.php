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
		$this->main_css_element = '%%order_class%%.rawnaq-spt';
	}

	public function get_fields() {
		return [
			'progress' => [
				'label'           => esc_html__( 'Progress Style', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => [
					'both' => esc_html__( 'Bar + Ring', 'rawnaq' ),
					'bar'  => esc_html__( 'Top / Bottom Bar', 'rawnaq' ),
					'ring' => esc_html__( 'Circular Ring', 'rawnaq' ),
					'none' => esc_html__( 'None', 'rawnaq' ),
				],
				'default'         => 'both',
				'toggle_slug'     => 'main_content',
			],
			'toc_position' => [
				'label'           => esc_html__( 'TOC Position', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => [
					'sticky'   => esc_html__( 'Sidebar Sticky', 'rawnaq' ),
					'floating' => esc_html__( 'Floating Panel', 'rawnaq' ),
					'inline'   => esc_html__( 'Inline Box', 'rawnaq' ),
					'none'     => esc_html__( 'Hidden (Progress only)', 'rawnaq' ),
				],
				'default'         => 'sticky',
				'toggle_slug'     => 'main_content',
			],
			'toc_title' => [
				'label'           => esc_html__( 'TOC Heading', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => 'Contents',
				'toggle_slug'     => 'main_content',
			],
			'target_selector' => [
				'label'           => esc_html__( 'Content Scope (CSS Selector)', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'default'         => '.entry-content, main, article, .et_pb_section',
				'toggle_slug'     => 'settings',
			],
			'reading_time' => [
				'label'           => esc_html__( 'Show Reading Time', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'on',
				'toggle_slug'     => 'settings',
			],
			'show_search' => [
				'label'           => esc_html__( 'Show Search Filter', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'settings',
			],
			'toc_collapsible' => [
				'label'           => esc_html__( 'Collapsible Accordion Header', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'on',
				'toggle_slug'     => 'settings',
			],
			'click_to_top' => [
				'label'           => esc_html__( 'Click Ring for Back to Top', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'on',
				'toggle_slug'     => 'settings',
			],
			'accent_color' => [
				'label'        => esc_html__( 'Accent Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#FBBF24',
				'toggle_slug'  => 'style',
			],
			'accent_deep' => [
				'label'        => esc_html__( 'Active / Track Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#4338CA',
				'toggle_slug'  => 'style',
			],
		];
	}

	public function render( $attrs, $content = null, $render_slug = '' ) {
		wp_enqueue_style( 'rawnaq-scroll-progress-toc' );
		wp_enqueue_script( 'rawnaq-scroll-progress-toc' );

		$progress        = sanitize_key( $this->props['progress'] ?? 'both' );
		$toc_position    = sanitize_key( $this->props['toc_position'] ?? 'sticky' );
		$title           = sanitize_text_field( $this->props['toc_title'] ?? 'Contents' );
		$target_selector = sanitize_text_field( $this->props['target_selector'] ?? '.entry-content, main, article, .et_pb_section' );
		$reading_time    = ( $this->props['reading_time'] ?? 'on' ) === 'on';
		$show_search     = ( $this->props['show_search'] ?? 'off' ) === 'on';
		$toc_collapsible = ( $this->props['toc_collapsible'] ?? 'on' ) === 'on';
		$click_to_top    = ( $this->props['click_to_top'] ?? 'on' ) === 'on';
		$accent_color    = sanitize_hex_color( $this->props['accent_color'] ?? '#FBBF24' ) ?: '#FBBF24';
		$accent_deep     = sanitize_hex_color( $this->props['accent_deep'] ?? '#4338CA' ) ?: '#4338CA';

		$cfg = [
			'progress'           => $progress,
			'barPosition'        => 'top',
			'showPercent'        => true,
			'clickToTop'         => $click_to_top,
			'tocPosition'        => $toc_position,
			'tocTitle'           => $title,
			'tocCollapsible'     => $toc_collapsible,
			'source'             => 'auto',
			'levels'             => [ 'h2', 'h3' ],
			'manual'             => [],
			'collapseSubs'       => false,
			'showSearch'         => $show_search,
			'urlHashSync'        => true,
			'sectionReadingTime' => false,
			'smooth'             => true,
			'scrollOffset'       => 80,
			'readingTime'        => $reading_time,
			'mobileCollapse'     => true,
			'dockAttach'         => false,
			'syncTimeline'       => '',
			'scope'              => $target_selector,
			'hideIfShort'        => true,
		];

		ob_start();
		?>
		<div class="rawnaq-spt"
		     style="--spt-accent: <?php echo esc_attr( $accent_color ); ?>; --spt-accent-deep: <?php echo esc_attr( $accent_deep ); ?>; --spt-offset: 80px; --spt-ring-size: 56px;"
		     data-spt="<?php echo esc_attr( wp_json_encode( $cfg ) ); ?>">
			<?php if ( 'none' !== $toc_position ) : ?>
				<nav class="rawnaq-spt-toc is-<?php echo esc_attr( $toc_position ); ?>" role="navigation" aria-label="<?php echo esc_attr( $title ); ?>">
					<div class="rawnaq-spt-header-wrap">
						<p class="rawnaq-spt-reading" hidden></p>
						<p class="rawnaq-spt-chapter" hidden></p>
						<div class="rawnaq-spt-title-row">
							<h3 class="rawnaq-spt-title"><?php echo esc_html( $title ); ?></h3>
							<?php if ( $toc_collapsible ) : ?>
								<button type="button" class="rawnaq-spt-toggle-btn" aria-expanded="true" aria-label="<?php esc_attr_e( 'Toggle Table of Contents', 'rawnaq' ); ?>">
									<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="6 9 12 15 18 9"></polyline></svg>
								</button>
							<?php endif; ?>
						</div>
					</div>
					<ul class="rawnaq-spt-list"></ul>
				</nav>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
