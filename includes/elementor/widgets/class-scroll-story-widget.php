<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_Scroll_Story_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rawnaq_scroll_story';
	}

	public function get_title() {
		return esc_html__( 'Scroll Story Chapters', 'rawnaq' );
	}

	public function get_icon() {
		return 'eicon-scroll';
	}

	public function get_categories() {
		return [ 'rawnaq' ];
	}

	public function get_style_depends() {
		return [ 'rawnaq-scroll-story' ];
	}

	public function get_script_depends() {
		return [ 'rawnaq-scroll-story', 'rawnaq-bridge' ];
	}

	protected function register_controls() {
		$this->start_controls_section( 's_content', [
			'label' => esc_html__( 'Story & Chapters', 'rawnaq' ),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'card_style', [
			'label'   => esc_html__( 'Theme Preset', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'cards',
			'options' => [
				'cards'     => esc_html__( 'Modern Cards (Elevated)', 'rawnaq' ),
				'minimal'   => esc_html__( 'Minimal Editorial (Clean)', 'rawnaq' ),
				'spotlight' => esc_html__( 'Spotlight Glow', 'rawnaq' ),
			],
		] );

		$this->add_control( 'media_side', [
			'label'   => esc_html__( 'Pinned Media Side', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'left',
			'options' => [
				'left'  => esc_html__( 'Left', 'rawnaq' ),
				'right' => esc_html__( 'Right', 'rawnaq' ),
			],
		] );

		$this->add_control( 'media_ratio', [
			'label'   => esc_html__( 'Media Aspect Ratio', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => '4-5',
			'options' => [
				'4-5'   => esc_html__( 'Portrait (4:5)', 'rawnaq' ),
				'16-10' => esc_html__( 'Landscape (16:10)', 'rawnaq' ),
				'1-1'   => esc_html__( 'Square (1:1)', 'rawnaq' ),
			],
		] );

		$this->add_control( 'show_counter', [
			'label'        => esc_html__( 'Show Progress Counter', 'rawnaq' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'label_on'     => esc_html__( 'Yes', 'rawnaq' ),
			'label_off'    => esc_html__( 'No', 'rawnaq' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->add_control( 'show_progress', [
			'label'        => esc_html__( 'Show Progress Bar', 'rawnaq' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'label_on'     => esc_html__( 'Yes', 'rawnaq' ),
			'label_off'    => esc_html__( 'No', 'rawnaq' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$repeater = new \Elementor\Repeater();

		$repeater->add_control( 'kicker', [
			'label'       => esc_html__( 'Kicker Tag / Phase', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => esc_html__( '01 · Phase Name', 'rawnaq' ),
			'label_block' => true,
		] );

		$repeater->add_control( 'title', [
			'label'       => esc_html__( 'Title', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => esc_html__( 'Chapter title', 'rawnaq' ),
			'label_block' => true,
		] );

		$repeater->add_control( 'body', [
			'label'       => esc_html__( 'Body', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::WYSIWYG,
			'default'     => esc_html__( 'Tell this chapter of the story as the reader scrolls.', 'rawnaq' ),
			'description' => esc_html__( 'Rich text — links, emphasis, and lists are allowed.', 'rawnaq' ),
		] );

		$repeater->add_control( 'image', [
			'label' => esc_html__( 'Pinned Image', 'rawnaq' ),
			'type'  => \Elementor\Controls_Manager::MEDIA,
		] );

		$repeater->add_control( 'video', [
			'label'       => esc_html__( 'Pinned Video (MP4)', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::MEDIA,
			'media_types' => [ 'video' ],
			'description' => esc_html__( 'Optional. Autoplays muted while the chapter is active; image is used as the poster.', 'rawnaq' ),
		] );

		$repeater->add_control( 'anchor', [
			'label'       => esc_html__( 'Anchor / Deep-link ID', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => '',
			'placeholder' => 'the-challenge',
			'description' => esc_html__( 'Optional. Enables #anchor deep-linking. Defaults to a slug of the title.', 'rawnaq' ),
		] );

		$repeater->add_control( 'caption', [
			'label'       => esc_html__( 'Media Caption', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => '',
			'label_block' => true,
		] );

		$repeater->add_control( 'cta_text', [
			'label'   => esc_html__( 'CTA Text', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => '',
		] );

		$repeater->add_control( 'cta_link', [
			'label'       => esc_html__( 'CTA Link', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::URL,
			'placeholder' => 'https://',
			'default'     => [ 'url' => '' ],
		] );

		$repeater->add_control( 'project_id', [
			'label'       => esc_html__( 'Case-Study project ID', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => '',
			'description' => esc_html__( 'Match Case-Study card id (e.g. post-123). Highlights related card on scroll.', 'rawnaq' ),
		] );

		$repeater->add_control( 'project_slug', [
			'label'   => esc_html__( 'Case-Study project slug', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => '',
		] );

		$this->add_control( 'chapters', [
			'label'       => esc_html__( 'Chapters', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'default'     => [
				[
					'kicker'   => esc_html__( '01 · Discovery & Vision', 'rawnaq' ),
					'title'    => esc_html__( 'Reimagining Enterprise Financial Intelligence', 'rawnaq' ),
					'body'     => esc_html__( 'Fragmented workflows and legacy analytics were slowing strategic decisions. We designed a cohesive operational canvas that synthesizes complex multi-currency data into actionable intelligence in real time.', 'rawnaq' ),
					'caption'  => esc_html__( 'Intelligent portfolio overview & predictive risk modelling.', 'rawnaq' ),
					'cta_text' => esc_html__( 'View Discovery Notes', 'rawnaq' ),
					'cta_link' => [ 'url' => '#' ],
				],
				[
					'kicker'   => esc_html__( '02 · System Architecture', 'rawnaq' ),
					'title'    => esc_html__( 'Sub-50ms Micro-Frontend & Event Sync', 'rawnaq' ),
					'body'     => esc_html__( 'Engineered with lightweight web components and streaming event-sinks. The modular dashboard dynamically coordinates multi-window widgets with zero state drift and effortless responsive adaptation.', 'rawnaq' ),
					'caption'  => esc_html__( 'Modular dashboard widget architecture running in real-time.', 'rawnaq' ),
					'cta_text' => esc_html__( 'Explore Tech Stack', 'rawnaq' ),
					'cta_link' => [ 'url' => '#' ],
				],
				[
					'kicker'   => esc_html__( '03 · Measurable Impact', 'rawnaq' ),
					'title'    => esc_html__( '4.8x Efficiency Boost Across 120k Users', 'rawnaq' ),
					'body'     => esc_html__( 'Accelerated decision turnaround from 3 days to under 4 hours. Automated risk scoring and tactile interactive charts drove a 99.4% customer satisfaction score within the first quarter.', 'rawnaq' ),
					'caption'  => esc_html__( 'Performance analytics post-migration across 12 enterprise regions.', 'rawnaq' ),
					'cta_text' => esc_html__( 'Read Full Case Study', 'rawnaq' ),
					'cta_link' => [ 'url' => '#' ],
				],
			],
			'title_field' => '{{{ title }}}',
		] );

		$this->end_controls_section();

		$this->start_controls_section( 's_style', [
			'label' => esc_html__( 'Style & Colors', 'rawnaq' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'accent', [
			'label'     => esc_html__( 'Accent Color', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#0f766e',
			'selectors' => [ '{{WRAPPER}} .rawnaq-story' => '--story-accent: {{VALUE}};' ],
		] );

		$this->add_control( 'card_bg', [
			'label'     => esc_html__( 'Card Background', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '',
			'selectors' => [ '{{WRAPPER}} .rawnaq-story' => '--story-card-bg: {{VALUE}};' ],
		] );

		$this->add_control( 'text_color', [
			'label'     => esc_html__( 'Text Color', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '',
			'selectors' => [ '{{WRAPPER}} .rawnaq-story' => '--story-ink: {{VALUE}};' ],
		] );

		$this->add_control( 'border_radius', [
			'label'      => esc_html__( 'Border Radius', 'rawnaq' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 4, 'max' => 40 ] ],
			'default'    => [ 'size' => 18 ],
			'selectors'  => [ '{{WRAPPER}} .rawnaq-story' => '--story-radius: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_control( 'pin_top', [
			'label'      => esc_html__( 'Pin Offset', 'rawnaq' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 40, 'max' => 180 ] ],
			'default'    => [ 'size' => 96 ],
			'selectors'  => [ '{{WRAPPER}} .rawnaq-story' => '--story-pin-top: {{SIZE}}{{UNIT}};' ],
		] );

		$this->end_controls_section();
	}

	/**
	 * @param array $chapters Raw Elementor repeater rows.
	 * @return array<int, array<string, mixed>>
	 */
	private function normalize_chapters( $chapters ) {
		$out = [];
		if ( ! is_array( $chapters ) ) {
			return $out;
		}
		foreach ( $chapters as $row ) {
			$img = '';
			if ( ! empty( $row['image']['url'] ) ) {
				$img = esc_url( $row['image']['url'] );
			}
			$video = '';
			if ( ! empty( $row['video']['url'] ) ) {
				$video = esc_url( $row['video']['url'] );
			}
			$cta_url = '';
			if ( ! empty( $row['cta_link']['url'] ) ) {
				$cta_url = esc_url( $row['cta_link']['url'] );
			}
			$out[] = [
				'kicker'      => (string) ( $row['kicker'] ?? '' ),
				'title'       => (string) ( $row['title'] ?? '' ),
				'body'        => (string) ( $row['body'] ?? '' ),
				'image'       => $img,
				'imageAlt'    => (string) ( $row['image']['alt'] ?? '' ),
				'video'       => $video,
				'anchor'      => (string) ( $row['anchor'] ?? '' ),
				'caption'     => (string) ( $row['caption'] ?? '' ),
				'ctaText'     => (string) ( $row['cta_text'] ?? '' ),
				'ctaUrl'      => $cta_url,
				'ctaExt'      => ! empty( $row['cta_link']['is_external'] ),
				'ctaNof'      => ! empty( $row['cta_link']['nofollow'] ),
				'projectId'   => (string) ( $row['project_id'] ?? '' ),
				'projectSlug' => (string) ( $row['project_slug'] ?? '' ),
			];
		}
		return $out;
	}

	/**
	 * Shared markup for PHP render + editor template.
	 *
	 * @param array  $chapters Normalized chapters.
	 * @param string $side     left|right.
	 * @param array  $options  Theme options.
	 */
	public static function render_markup( $chapters, $side = 'left', $options = [] ) {
		if ( function_exists( 'rawnaq_scroll_story_markup' ) ) {
			rawnaq_scroll_story_markup( $chapters, $side, $options );
			return;
		}
	}

	protected function render() {
		$s        = $this->get_settings_for_display();
		$chapters = $this->normalize_chapters( $s['chapters'] ?? [] );
		if ( ! $chapters ) {
			return;
		}
		$options = [
			'card_style'    => $s['card_style'] ?? 'cards',
			'media_ratio'   => $s['media_ratio'] ?? '4-5',
			'show_counter'  => 'yes' === ( $s['show_counter'] ?? 'yes' ),
			'show_progress' => 'yes' === ( $s['show_progress'] ?? 'yes' ),
		];
		self::render_markup( $chapters, $s['media_side'] ?? 'left', $options );
	}

	protected function content_template() {
		?>
		<#
		var side = settings.media_side === 'right' ? 'right' : 'left';
		var themeStyle = settings.card_style || 'cards';
		var mediaRatio = settings.media_ratio || '4-5';
		var showCounter = settings.show_counter !== 'no';
		var showProgress = settings.show_progress !== 'no';
		var chapters = settings.chapters || [];
		var totalCount = chapters.length;
		var totalFormatted = totalCount < 10 ? '0' + totalCount : '' + totalCount;
		#>
		<div class="rawnaq-story is-theme-{{ themeStyle }} is-ratio-{{ mediaRatio }}" data-theme="{{ themeStyle }}">
			<div class="rawnaq-story-layout<# if ( side === 'right' ) { #> is-media-right<# } #>">
				<aside class="rawnaq-story-pin">
					<# if ( showCounter || showProgress ) { #>
						<div class="rawnaq-story-pin-header">
							<# if ( showCounter ) { #>
								<div class="rawnaq-story-counter">
									<span class="rawnaq-story-counter-current">01</span>
									<span class="rawnaq-story-counter-divider">/</span>
									<span class="rawnaq-story-counter-total">{{ totalFormatted }}</span>
								</div>
							<# } #>
							<# if ( showProgress ) { #>
								<div class="rawnaq-story-tracker">
									<div class="rawnaq-story-tracker-fill" style="width: {{ ( 1 / Math.max( 1, totalCount ) ) * 100 }}%;"></div>
								</div>
							<# } #>
						</div>
					<# } #>

					<div class="rawnaq-story-media-stack">
						<# _.each( chapters, function( ch, i ) {
							var num = ( i + 1 ) < 10 ? '0' + ( i + 1 ) : '' + ( i + 1 );
							var img = ( ch.image && ch.image.url ) ? ch.image.url : '';
							var kicker = ch.kicker || ( 'Chapter ' + num );
						#>
							<div class="rawnaq-story-media<# if ( i === 0 ) { #> is-active<# } #>" data-index="{{ i }}">
								<# if ( img ) { #>
									<img class="rawnaq-story-img" src="{{ img }}" alt="" />
								<# } else { #>
									<div class="rawnaq-story-media-fallback">
										<div class="rawnaq-story-fallback-canvas">
											<div class="rawnaq-story-fallback-glow"></div>
											<div class="rawnaq-story-fallback-badge">
												<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
												<span>{{{ kicker }}}</span>
											</div>
											<h4 class="rawnaq-story-fallback-title">{{{ ch.title || ( 'Chapter ' + num ) }}}</h4>
											<div class="rawnaq-story-fallback-wireframe">
												<div class="rawnaq-story-wire-chip"></div>
												<div class="rawnaq-story-wire-lines">
													<span></span>
													<span></span>
													<span class="short"></span>
												</div>
											</div>
										</div>
									</div>
								<# } #>
							</div>
						<# } ); #>
					</div>

					<p class="rawnaq-story-caption"><# if ( chapters[0] && chapters[0].caption ) { #>{{{ chapters[0].caption }}}<# } #></p>

					<ol class="rawnaq-story-dots">
						<# _.each( chapters, function( ch, i ) {
							var num = ( i + 1 ) < 10 ? '0' + ( i + 1 ) : '' + ( i + 1 );
						#>
							<li>
								<button type="button" class="rawnaq-story-dot<# if ( i === 0 ) { #> is-active<# } #>">
									<span class="rawnaq-story-dot-num">{{ num }}</span>
								</button>
							</li>
						<# } ); #>
					</ol>
				</aside>

				<div class="rawnaq-story-chapters">
					<# _.each( chapters, function( ch, i ) {
						var num = ( i + 1 ) < 10 ? '0' + ( i + 1 ) : '' + ( i + 1 );
						var kicker = ch.kicker || ( 'Chapter ' + num );
						var ctaText = ch.cta_text || ch.ctaText || '';
						var ctaUrl = ( ch.cta_link && ch.cta_link.url ) ? ch.cta_link.url : ( ch.ctaUrl || '' );
					#>
						<section class="rawnaq-story-chapter<# if ( i === 0 ) { #> is-active<# } #>" data-index="{{ i }}" data-caption="{{ ch.caption || '' }}">
							<div class="rawnaq-story-card-inner">
								<div class="rawnaq-story-kicker-wrap">
									<span class="rawnaq-story-kicker">{{{ kicker }}}</span>
								</div>
								<# if ( ch.title ) { #><h3 class="rawnaq-story-title">{{{ ch.title }}}</h3><# } #>
								<# if ( ch.body ) { #><div class="rawnaq-story-body"><p>{{{ ch.body }}}</p></div><# } #>
								<# if ( ctaText && ctaUrl ) { #>
									<div class="rawnaq-story-cta-wrap">
										<a class="rawnaq-story-cta" href="{{ ctaUrl }}">
											<span>{{{ ctaText }}}</span>
											<svg class="rawnaq-story-cta-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
										</a>
									</div>
								<# } #>
							</div>
						</section>
					<# } ); #>
				</div>
			</div>
		</div>
		<?php
	}
}
