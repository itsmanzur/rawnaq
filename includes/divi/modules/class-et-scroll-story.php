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
			'story_theme' => [
				'label'           => esc_html__( 'Theme Preset', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'cards'     => esc_html__( 'Editorial Cards (Default)', 'rawnaq' ),
					'obsidian'  => esc_html__( 'Dark Obsidian Glass', 'rawnaq' ),
					'minimal'   => esc_html__( 'Minimal Light', 'rawnaq' ),
					'spotlight' => esc_html__( 'Spotlight Focus', 'rawnaq' ),
				],
				'default'         => 'cards',
				'toggle_slug'     => 'layout',
			],
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
			'media_ratio' => [
				'label'           => esc_html__( 'Media Aspect Ratio', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'4-5'   => esc_html__( '4:5 Editorial Portrait', 'rawnaq' ),
					'16-10' => esc_html__( '16:10 Cinematic Landscape', 'rawnaq' ),
					'1-1'   => esc_html__( '1:1 Square Focus', 'rawnaq' ),
				],
				'default'         => '4-5',
				'toggle_slug'     => 'layout',
			],
			'show_counter' => [
				'label'           => esc_html__( 'Show Chapter Counter (01 / 04)', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'on',
				'toggle_slug'     => 'layout',
			],
			'show_progress' => [
				'label'           => esc_html__( 'Show Sticky Progress Tracker', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'on',
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
				'description'     => esc_html__( 'JSON array of story chapters [{title, kicker, body, image, video, caption, metric_value, metric_label, metric_sub, ctaText, ctaUrl, anchor}]', 'rawnaq' ),
				'default'         => '[{"kicker":"Phase 01 · Discovery & Insight","title":"Architectural Vision & Spatial Blueprint","body":"Rooted in timeless aesthetics, the architectural journey begins with pure spatial harmony and natural light orientation. Every corridor and atrium is engineered to optimize daylight harvesting while maintaining acoustic insulation.","metric_value":"+140%","metric_label":"Spatial Efficiency","metric_sub":"Across 85,000 sq ft footprint","caption":"Conceptual Spatial Model — Initial Ideation","ctaText":"Explore Spatial Blueprint","ctaUrl":"#design"},{"kicker":"Phase 02 · Engineering & Craft","title":"Material Mastery & Tactile Sanctuary","body":"Custom Italian terrazzo, acoustically treated fluted timber, and brushed bronze create a tactile sanctuary. Materials were sustainably sourced across European quarries to ensure zero environmental drift and lifetime durability.","metric_value":"99.8%","metric_label":"Material Quality Index","metric_sub":"LEED Platinum Certified Specs","caption":"Textural Palette Selection — Milan Sourcing","ctaText":"View Material Specs","ctaUrl":"#materials"},{"kicker":"Phase 03 · Execution & Handover","title":"Precision Turnkey Handover & Impact","body":"Engineering tolerances within fractions of a millimeter ensure seamless performance. The landmark executive suite was delivered on time and below budget, setting a new benchmark for luxury enterprise architecture.","metric_value":"$12.4M","metric_label":"Project Valuation Delivered","metric_sub":"Completed 3 weeks ahead of schedule","caption":"Completed Executive Suite — Final Handover","ctaText":"Request Private Tour","ctaUrl":"#contact"}]',
				'toggle_slug'     => 'chapters',
			],
			'accent_color' => [
				'label'        => esc_html__( 'Accent & Glow Indicator Color', 'rawnaq' ),
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

		$theme         = sanitize_key( $this->props['story_theme'] ?? 'cards' );
		$side          = sanitize_key( $this->props['media_side'] ?? 'left' );
		$side          = ( 'right' === $side ) ? 'right' : 'left';
		$media_ratio   = sanitize_key( $this->props['media_ratio'] ?? '4-5' );
		$show_counter  = ( $this->props['show_counter'] ?? 'on' ) === 'on';
		$show_progress = ( $this->props['show_progress'] ?? 'on' ) === 'on';
		$pin_top       = max( 40, min( 180, absint( $this->props['pin_top'] ?? 96 ) ) );
		$accent_color  = sanitize_hex_color( $this->props['accent_color'] ?? '#0f766e' ) ?: '#0f766e';
		$raw_chaps     = $this->props['chapters_json'] ?? '';

		$raw = json_decode( $raw_chaps, true );
		if ( ! is_array( $raw ) || empty( $raw ) ) {
			$raw = [
				[
					'kicker'       => 'Phase 01 · Discovery',
					'title'        => '01. The Architectural Vision',
					'body'         => 'Rooted in timeless aesthetics, the architectural journey begins with pure spatial harmony.',
					'metric_value' => '+140%',
					'metric_label' => 'Spatial Efficiency',
					'metric_sub'   => '85k sq ft footprint',
					'caption'      => 'Conceptual Spatial Model',
					'ctaText'      => 'Explore Design',
					'ctaUrl'       => '#',
				],
				[
					'kicker'       => 'Phase 02 · Craftsmanship',
					'title'        => '02. Material Mastery',
					'body'         => 'Custom Italian terrazzo and acoustically treated fluted timber create a tactile sanctuary.',
					'metric_value' => '99.8%',
					'metric_label' => 'Material Quality',
					'metric_sub'   => 'LEED Platinum',
					'caption'      => 'Textural Palette',
					'ctaText'      => 'View Specs',
					'ctaUrl'       => '#',
				],
			];
		}

		$chapters = [];
		foreach ( $raw as $row ) {
			$chapters[] = [
				'kicker'       => sanitize_text_field( $row['kicker'] ?? '' ),
				'title'        => sanitize_text_field( $row['title'] ?? '' ),
				'body'         => wp_kses_post( $row['body'] ?? ( $row['desc'] ?? '' ) ),
				'image'        => esc_url_raw( $row['image'] ?? '' ),
				'imageAlt'     => sanitize_text_field( $row['title'] ?? '' ),
				'video'        => esc_url_raw( $row['video'] ?? '' ),
				'anchor'       => sanitize_title( $row['anchor'] ?? ( $row['title'] ?? '' ) ),
				'caption'      => sanitize_text_field( $row['caption'] ?? '' ),
				'metric_value' => sanitize_text_field( $row['metric_value'] ?? ( $row['metric'] ?? '' ) ),
				'metric_label' => sanitize_text_field( $row['metric_label'] ?? '' ),
				'metric_sub'   => sanitize_text_field( $row['metric_sub'] ?? '' ),
				'ctaText'      => sanitize_text_field( $row['ctaText'] ?? ( $row['cta_text'] ?? '' ) ),
				'ctaUrl'       => esc_url_raw( $row['ctaUrl'] ?? ( $row['cta_url'] ?? '' ) ),
				'ctaExt'       => false,
				'ctaNof'       => false,
				'projectId'    => sanitize_text_field( $row['projectId'] ?? '' ),
				'projectSlug'  => sanitize_title( $row['projectSlug'] ?? '' ),
			];
		}

		$options = [
			'card_style'    => $theme,
			'media_ratio'   => $media_ratio,
			'show_counter'  => $show_counter,
			'show_progress' => $show_progress,
		];

		ob_start();
		printf(
			'<div style="--story-accent: %1$s; --story-pin-top: %2$dpx;">',
			esc_attr( $accent_color ),
			(int) $pin_top
		);
		if ( function_exists( 'rawnaq_scroll_story_markup' ) ) {
			rawnaq_scroll_story_markup( $chapters, $side, $options );
		}
		echo '</div>';
		return ob_get_clean();
	}
}
