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
			'label' => esc_html__( 'Chapters', 'rawnaq' ),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
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

		$repeater = new \Elementor\Repeater();

		$repeater->add_control( 'title', [
			'label'       => esc_html__( 'Title', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => esc_html__( 'Chapter title', 'rawnaq' ),
			'label_block' => true,
		] );

		$repeater->add_control( 'body', [
			'label'   => esc_html__( 'Body', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::TEXTAREA,
			'default' => esc_html__( 'Tell this chapter of the story as the reader scrolls.', 'rawnaq' ),
			'rows'    => 4,
		] );

		$repeater->add_control( 'image', [
			'label' => esc_html__( 'Pinned Image', 'rawnaq' ),
			'type'  => \Elementor\Controls_Manager::MEDIA,
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
					'title'   => esc_html__( 'The challenge', 'rawnaq' ),
					'body'    => esc_html__( 'Set the scene. What problem or opportunity opens the story?', 'rawnaq' ),
					'caption' => '',
				],
				[
					'title'   => esc_html__( 'The approach', 'rawnaq' ),
					'body'    => esc_html__( 'Explain the turning point — method, insight, or decision.', 'rawnaq' ),
					'caption' => '',
				],
				[
					'title'   => esc_html__( 'The outcome', 'rawnaq' ),
					'body'    => esc_html__( 'Close with the result readers should remember.', 'rawnaq' ),
					'caption' => '',
				],
			],
			'title_field' => '{{{ title }}}',
		] );

		$this->end_controls_section();

		$this->start_controls_section( 's_style', [
			'label' => esc_html__( 'Style', 'rawnaq' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'accent', [
			'label'     => esc_html__( 'Accent Color', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#0f766e',
			'selectors' => [ '{{WRAPPER}} .rawnaq-story' => '--story-accent: {{VALUE}};' ],
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
			$cta_url = '';
			if ( ! empty( $row['cta_link']['url'] ) ) {
				$cta_url = esc_url( $row['cta_link']['url'] );
			}
			$out[] = [
				'title'       => (string) ( $row['title'] ?? '' ),
				'body'        => (string) ( $row['body'] ?? '' ),
				'image'       => $img,
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
	 */
	public static function render_markup( $chapters, $side = 'left' ) {
		if ( function_exists( 'rawnaq_scroll_story_markup' ) ) {
			rawnaq_scroll_story_markup( $chapters, $side );
			return;
		}
	}

	protected function render() {
		$s        = $this->get_settings_for_display();
		$chapters = $this->normalize_chapters( $s['chapters'] ?? [] );
		if ( ! $chapters ) {
			return;
		}
		self::render_markup( $chapters, $s['media_side'] ?? 'left' );
	}

	protected function content_template() {
		?>
		<#
		var side = settings.media_side === 'right' ? 'right' : 'left';
		var layoutClass = 'rawnaq-story-layout' + ( side === 'right' ? ' is-media-right' : '' );
		var chapters = settings.chapters || [];
		#>
		<div class="rawnaq-story">
			<div class="{{ layoutClass }}">
				<aside class="rawnaq-story-pin">
					<div class="rawnaq-story-media-stack">
						<# _.each( chapters, function( ch, i ) {
							var img = ( ch.image && ch.image.url ) ? ch.image.url : '';
						#>
							<div class="rawnaq-story-media<# if ( i === 0 ) { #> is-active<# } #>" data-index="{{ i }}">
								<# if ( img ) { #>
									<img src="{{ img }}" alt="" />
								<# } else { #>
									<div class="rawnaq-story-media-fallback">{{{ ch.title || ( 'Chapter ' + ( i + 1 ) ) }}}</div>
								<# } #>
							</div>
						<# } ); #>
					</div>
					<p class="rawnaq-story-caption"><# if ( chapters[0] && chapters[0].caption ) { #>{{{ chapters[0].caption }}}<# } #></p>
					<ol class="rawnaq-story-dots">
						<# _.each( chapters, function( ch, i ) { #>
							<li><button type="button" class="rawnaq-story-dot<# if ( i === 0 ) { #> is-active<# } #>"></button></li>
						<# } ); #>
					</ol>
				</aside>
				<div class="rawnaq-story-chapters">
					<# _.each( chapters, function( ch, i ) { #>
						<section class="rawnaq-story-chapter<# if ( i === 0 ) { #> is-active<# } #>" data-index="{{ i }}" data-caption="{{ ch.caption || '' }}">
							<span class="rawnaq-story-kicker">Chapter {{ i + 1 }}</span>
							<# if ( ch.title ) { #><h3>{{{ ch.title }}}</h3><# } #>
							<# if ( ch.body ) { #><p>{{{ ch.body }}}</p><# } #>
						</section>
					<# } ); #>
				</div>
			</div>
		</div>
		<?php
	}
}
