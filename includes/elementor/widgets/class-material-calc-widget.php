<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_Material_Calc_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rawnaq_material_calc';
	}

	public function get_title() {
		return esc_html__( 'Material Quantity Calculator', 'rawnaq' );
	}

	public function get_icon() {
		return 'eicon-product-categories';
	}

	public function get_categories() {
		return [ 'rawnaq' ];
	}

	public function get_keywords() {
		return [ 'calculator', 'concrete', 'brick', 'tile', 'paint', 'construction', 'rebar', 'material' ];
	}

	public function get_style_depends() {
		return [ 'rawnaq-material-calc' ];
	}

	public function get_script_depends() {
		return [ 'rawnaq-material-calc' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			's_content',
			[
				'label' => esc_html__( 'Calculator', 'rawnaq' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$options = [];
		foreach ( rawnaq_mc_calculators() as $key => $def ) {
			$options[ $key ] = $def['label'];
		}

		$this->add_control(
			'calculators',
			[
				'label'       => esc_html__( 'Calculators to show', 'rawnaq' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => $options,
				'default'     => array_keys( $options ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'unit',
			[
				'label'   => esc_html__( 'Default units', 'rawnaq' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'metric',
				'options' => [
					'metric'   => esc_html__( 'Metric (m, cm, mm)', 'rawnaq' ),
					'imperial' => esc_html__( 'Feet and inches', 'rawnaq' ),
				],
			]
		);

		$this->add_control(
			'title',
			[
				'label'   => esc_html__( 'Title', 'rawnaq' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Material quantity calculator', 'rawnaq' ),
			]
		);

		$this->add_control(
			'intro',
			[
				'label'   => esc_html__( 'Intro text', 'rawnaq' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'Quick estimates for concrete, bricks, tiles, paint and steel.', 'rawnaq' ),
			]
		);

		$this->add_control(
			'show_cta',
			[
				'label'        => esc_html__( 'Show quote button', 'rawnaq' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'cta_text',
			[
				'label'     => esc_html__( 'Button text', 'rawnaq' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => esc_html__( 'Get a quote for this project', 'rawnaq' ),
				'condition' => [ 'show_cta' => 'yes' ],
			]
		);

		$this->add_control(
			'cta_url',
			[
				'label'     => esc_html__( 'Button link', 'rawnaq' ),
				'type'      => \Elementor\Controls_Manager::URL,
				'condition' => [ 'show_cta' => 'yes' ],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			's_style',
			[
				'label' => esc_html__( 'Style', 'rawnaq' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'accent',
			[
				'label'     => esc_html__( 'Accent colour', 'rawnaq' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}} .rawnaq-mc' => '--mc-accent: {{VALUE}};' ],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$s   = $this->get_settings_for_display();
		$cfg = rawnaq_mc_cfg_sanitize(
			[
				'calculators' => $s['calculators'] ?? [],
				'unit'        => $s['unit'] ?? 'metric',
				'title'       => $s['title'] ?? '',
				'intro'       => $s['intro'] ?? '',
				'showCta'     => 'yes' === ( $s['show_cta'] ?? '' ),
				'ctaText'     => $s['cta_text'] ?? '',
				'ctaUrl'      => $s['cta_url']['url'] ?? '',
			]
		);

		echo rawnaq_mc_markup( $cfg ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
