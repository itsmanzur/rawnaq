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
		$this->name            = esc_html__( 'Rawnaq Scroll Storytelling', 'rawnaq' );
		$this->icon_path       = 'book';
		$this->main_css_element = '%%order_class%% .rawnaq-story';
	}

	public function get_fields() {
		return [
			'media_side' => [
				'label'           => esc_html__( 'Sticky Visual Media Position', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'left'  => esc_html__( 'Media Left, Story Text Right', 'rawnaq' ),
					'right' => esc_html__( 'Story Text Left, Media Right', 'rawnaq' ),
				],
				'default'         => 'left',
				'toggle_slug'     => 'layout',
			],
			'pin_top' => [
				'label'           => esc_html__( 'Sticky Pin Top Offset (px)', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'layout',
				'default'         => '96',
				'range_settings'  => [
					'min'  => 40,
					'max'  => 180,
					'step' => 2,
				],
				'toggle_slug'     => 'layout',
			],
			'chapters_json' => [
				'label'           => esc_html__( 'Story Chapters (JSON)', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'JSON array of story chapters [{title, body, image, video, caption, ctaText, ctaUrl, anchor}]', 'rawnaq' ),
				'default'         => '[{"title":"01. The Architectural Vision","body":"Rooted in timeless aesthetics, the architectural journey begins with pure spatial harmony and natural light orientation.","image":"https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=900","caption":"Conceptual Spatial Model — Initial Ideation","ctaText":"Explore Design Process","ctaUrl":"#design"},{"title":"02. Material Mastery & Texture","body":"Custom Italian terrazzo, acoustically treated fluted timber, and brushed bronze create a tactile sanctuary.","image":"https://images.unsplash.com/photo-1513694203232-719a280e022f?w=900","caption":"Textural Palette Selection — Milan Sourcing","ctaText":"View Material Specs","ctaUrl":"#materials"},{"title":"03. Precision Turnkey Delivery","body":"Engineering tolerances within fractions of a millimeter ensure seamless acoustic performance and lifetime durability.","image":"https://images.unsplash.com/photo-1497366216548-37526070297c?w=900","caption":"Completed Executive Suite — Final Handover","ctaText":"Request Private Tour","ctaUrl":"#contact"}]',
				'toggle_slug'     => 'chapters',
			],
			'accent_color' => [
				'label'        => esc_html__( 'Accent & Dot Indicator Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#0f766e',
				'toggle_slug'  => 'style',
			],
		];
	}

	public function render( $attrs, $content = null, $render_slug = '' ) {
		wp_enqueue_style( 'rawnaq-scroll-story' );
		wp_enqueue_script( 'rawnaq-scroll-story' );
		wp_enqueue_script( 'rawnaq-bridge' );

		$side         = sanitize_key( $this->props['media_side'] ?? 'left' );
		$side         = ( 'right' === $side ) ? 'right' : 'left';
		$pin_top      = max( 40, min( 180, absint( $this->props['pin_top'] ?? 96 ) ) );
		$accent_color = sanitize_hex_color( $this->props['accent_color'] ?? '#0f766e' ) ?: '#0f766e';
		$raw_chaps    = $this->props['chapters_json'] ?? '';

		$raw = json_decode( $raw_chaps, true );
		if ( ! is_array( $raw ) || empty( $raw ) ) {
			$raw = [
				[
					'title'   => '01. The Architectural Vision',
					'body'    => 'Rooted in timeless aesthetics, the architectural journey begins with pure spatial harmony.',
					'image'   => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=900',
					'caption' => 'Conceptual Spatial Model',
					'ctaText' => 'Explore Design',
					'ctaUrl'  => '#',
				],
				[
					'title'   => '02. Material Mastery',
					'body'    => 'Custom Italian terrazzo and acoustically treated fluted timber create a tactile sanctuary.',
					'image'   => 'https://images.unsplash.com/photo-1513694203232-719a280e022f?w=900',
					'caption' => 'Textural Palette',
					'ctaText' => 'View Specs',
					'ctaUrl'  => '#',
				],
			];
		}

		$chapters = [];
		foreach ( $raw as $row ) {
			$chapters[] = [
				'title'       => sanitize_text_field( $row['title'] ?? '' ),
				'body'        => wp_kses_post( $row['body'] ?? ( $row['desc'] ?? '' ) ),
				'image'       => esc_url_raw( $row['image'] ?? '' ),
				'imageAlt'    => sanitize_text_field( $row['title'] ?? '' ),
				'video'       => esc_url_raw( $row['video'] ?? '' ),
				'anchor'      => sanitize_title( $row['anchor'] ?? ( $row['title'] ?? '' ) ),
				'caption'     => sanitize_text_field( $row['caption'] ?? '' ),
				'ctaText'     => sanitize_text_field( $row['ctaText'] ?? ( $row['cta_text'] ?? '' ) ),
				'ctaUrl'      => esc_url_raw( $row['ctaUrl'] ?? ( $row['cta_url'] ?? '' ) ),
				'ctaExt'      => false,
				'ctaNof'      => false,
				'projectId'   => sanitize_text_field( $row['projectId'] ?? '' ),
				'projectSlug' => sanitize_title( $row['projectSlug'] ?? '' ),
			];
		}

		ob_start();
		printf(
			'<div style="--story-accent: %1$s; --story-pin-top: %2$dpx;">',
			esc_attr( $accent_color ),
			(int) $pin_top
		);
		if ( function_exists( 'rawnaq_scroll_story_markup' ) ) {
			rawnaq_scroll_story_markup( $chapters, $side );
		}
		echo '</div>';
		return ob_get_clean();
	}
}
