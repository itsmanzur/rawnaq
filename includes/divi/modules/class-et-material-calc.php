<?php
/**
 * Rawnaq Divi Module: Material Quantity Calculator
 *
 * @package Rawnaq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_ET_Material_Calc extends ET_Builder_Module {

	public $slug       = 'rawnaq_et_material_calc';
	public $vb_support = 'on';

	public function init() {
		$this->name       = esc_html__( 'Rawnaq Material Calculator', 'rawnaq' );
		$this->icon_path  = 'calculator';
		$this->main_css_element = '%%order_class%%.rawnaq-mc';
	}

	public function get_fields() {
		$options = [];
		foreach ( rawnaq_mc_calculators() as $key => $def ) {
			$options[ $key ] = $def['label'];
		}

		$fields = [];
		foreach ( $options as $key => $label ) {
			/* translators: %s: calculator name */
			$fields[ 'show_' . $key ] = [
				'label'           => sprintf( esc_html__( 'Show %s', 'rawnaq' ), $label ),
				'type'            => 'yes_no_button',
				'option_category' => 'basic_option',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'on',
				'toggle_slug'     => 'main_content',
			];
		}

		return array_merge(
			$fields,
			[
				'unit'     => [
					'label'           => esc_html__( 'Default Units', 'rawnaq' ),
					'type'            => 'select',
					'option_category' => 'basic_option',
					'options'         => [
						'metric'   => esc_html__( 'Metric (m, cm, mm)', 'rawnaq' ),
						'imperial' => esc_html__( 'Feet and inches', 'rawnaq' ),
					],
					'default'         => 'metric',
					'toggle_slug'     => 'main_content',
				],
				'title'    => [
					'label'           => esc_html__( 'Title', 'rawnaq' ),
					'type'            => 'text',
					'option_category' => 'basic_option',
					'default'         => esc_html__( 'Material quantity calculator', 'rawnaq' ),
					'toggle_slug'     => 'main_content',
				],
				'intro'    => [
					'label'           => esc_html__( 'Intro Text', 'rawnaq' ),
					'type'            => 'textarea',
					'option_category' => 'basic_option',
					'default'         => esc_html__( 'Quick estimates for concrete, bricks, tiles, paint and steel.', 'rawnaq' ),
					'toggle_slug'     => 'main_content',
				],
				'show_cta' => [
					'label'           => esc_html__( 'Show Quote Button', 'rawnaq' ),
					'type'            => 'yes_no_button',
					'option_category' => 'basic_option',
					'options'         => [
						'on'  => esc_html__( 'Yes', 'rawnaq' ),
						'off' => esc_html__( 'No', 'rawnaq' ),
					],
					'default'         => 'off',
					'toggle_slug'     => 'main_content',
				],
				'cta_text' => [
					'label'           => esc_html__( 'Button Text', 'rawnaq' ),
					'type'            => 'text',
					'option_category' => 'basic_option',
					'default'         => esc_html__( 'Get a quote for this project', 'rawnaq' ),
					'toggle_slug'     => 'main_content',
					'show_if'         => [ 'show_cta' => 'on' ],
				],
				'cta_url'  => [
					'label'           => esc_html__( 'Button Link', 'rawnaq' ),
					'type'            => 'text',
					'option_category' => 'basic_option',
					'toggle_slug'     => 'main_content',
					'show_if'         => [ 'show_cta' => 'on' ],
				],
				'accent'   => [
					'label'           => esc_html__( 'Accent Colour', 'rawnaq' ),
					'type'            => 'color',
					'option_category' => 'basic_option',
					'default'         => '#fbbf24',
					'toggle_slug'     => 'main_content',
				],
			]
		);
	}

	public function render( $attrs, $content = null, $render_slug = '' ) {
		wp_enqueue_style( 'rawnaq-material-calc' );
		wp_enqueue_script( 'rawnaq-material-calc' );

		$list = [];
		foreach ( array_keys( rawnaq_mc_calculators() ) as $key ) {
			if ( 'off' !== ( $this->props[ 'show_' . $key ] ?? 'on' ) ) {
				$list[] = $key;
			}
		}

		$cfg = rawnaq_mc_cfg_sanitize(
			[
				'calculators' => $list,
				'unit'        => $this->props['unit'] ?? 'metric',
				'title'       => $this->props['title'] ?? '',
				'intro'       => $this->props['intro'] ?? '',
				'showCta'     => 'on' === ( $this->props['show_cta'] ?? 'off' ),
				'ctaText'     => $this->props['cta_text'] ?? '',
				'ctaUrl'      => $this->props['cta_url'] ?? '',
				'accent'      => $this->props['accent'] ?? '',
			]
		);

		return rawnaq_mc_markup( $cfg );
	}
}
