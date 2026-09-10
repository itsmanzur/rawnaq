<?php
/**
 * Rawnaq Divi Module: Scroll Storytelling Chapter Walkthrough
 *
 * @package Rawnaq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_ET_Scroll_Story extends ET_Builder_Module {

	public $slug       = 'rawnaq_et_scroll_story';
	public $vb_support = 'on';

	public function init() {
		$this->name       = esc_html__( 'Rawnaq Scroll Storytelling', 'rawnaq' );
		$this->icon_path  = 'book';
		$this->main_css_element = '%%order_class%%.rawnaq-scroll-story-wrapper';
	}

	public function get_fields() {
		return [
			'story_title' => [
				'label'           => esc_html__( 'Story Section Title', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => 'The Architectural Narrative',
				'toggle_slug'     => 'main_content',
			],
			'direction' => [
				'label'           => esc_html__( 'Scroll Direction', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'horizontal' => esc_html__( 'Horizontal Pinned Walkthrough', 'rawnaq' ),
					'vertical'   => esc_html__( 'Vertical Cascading Story', 'rawnaq' ),
				],
				'default'         => 'horizontal',
				'toggle_slug'     => 'layout',
			],
			'chapters_json' => [
				'label'           => esc_html__( 'Chapters JSON', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'default'         => '[{"chapter":"01","title":"The Vision","desc":"Rooted in timeless aesthetics, the architectural concept began with a minimalist spatial layout.","image":"https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200"},{"chapter":"02","title":"Material Craft","desc":"Custom Italian terrazzo and natural fluted oak form the textural backbone of the living suites.","image":"https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1200"},{"chapter":"03","title":"The Execution","desc":"Precision engineering and acoustic optimization deliver a tranquil sanctuary.","image":"https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=1200"}]',
				'toggle_slug'     => 'chapters',
			],
		];
	}

	public function render( $attrs, $content = null, $render_slug = '' ) {
		wp_enqueue_style( 'rawnaq-scroll-story' );
		wp_enqueue_script( 'rawnaq-scroll-story' );

		$title     = sanitize_text_field( $this->props['story_title'] ?? 'The Architectural Narrative' );
		$direction = sanitize_text_field( $this->props['direction'] ?? 'horizontal' );
		$raw_chaps = $this->props['chapters_json'] ?? '';

		$chapters = json_decode( $raw_chaps, true );
		if ( ! is_array( $chapters ) || empty( $chapters ) ) {
			$chapters = [
				[ 'chapter' => '01', 'title' => 'The Vision', 'desc' => 'Rooted in timeless aesthetics.', 'image' => '' ],
				[ 'chapter' => '02', 'title' => 'Material Craft', 'desc' => 'Custom Italian terrazzo.', 'image' => '' ],
				[ 'chapter' => '03', 'title' => 'The Execution', 'desc' => 'Precision engineering.', 'image' => '' ],
			];
		}

		ob_start();
		?>
		<div class="rawnaq-scroll-story-wrapper direction-<?php echo esc_attr( $direction ); ?>">
			<?php if ( $title ) : ?>
				<div class="rawnaq-story-header">
					<h3 class="rawnaq-story-headline"><?php echo esc_html( $title ); ?></h3>
				</div>
			<?php endif; ?>
			<div class="rawnaq-story-track">
				<?php foreach ( $chapters as $idx => $chap ) : ?>
					<div class="rawnaq-story-slide" data-chapter="<?php echo esc_attr( (string) ( $idx + 1 ) ); ?>">
						<?php if ( ! empty( $chap['image'] ) ) : ?>
							<div class="rawnaq-story-media" style="background-image: url('<?php echo esc_url( $chap['image'] ); ?>');"></div>
						<?php endif; ?>
						<div class="rawnaq-story-content">
							<span class="rawnaq-story-number"><?php echo esc_html( $chap['chapter'] ?? ( $idx + 1 ) ); ?></span>
							<h4 class="rawnaq-story-title"><?php echo esc_html( $chap['title'] ?? 'Chapter' ); ?></h4>
							<p class="rawnaq-story-desc"><?php echo esc_html( $chap['desc'] ?? '' ); ?></p>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
