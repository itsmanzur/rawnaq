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
		$this->name            = esc_html__( 'Rawnaq Case Study Grid', 'rawnaq' );
		$this->icon_path       = 'portfolio';
		$this->main_css_element = '%%order_class%% .rawnaq-case-study';
	}

	public function get_fields() {
		return [
			'data_source' => [
				'label'           => esc_html__( 'Data Source', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'configuration',
				'options'         => [
					'query'  => esc_html__( 'Case Study Custom Post Type (Dynamic Query)', 'rawnaq' ),
					'manual' => esc_html__( 'Manual Projects (JSON Config)', 'rawnaq' ),
				],
				'default'         => 'query',
				'toggle_slug'     => 'query',
			],
			'posts_per_page' => [
				'label'           => esc_html__( 'Projects Per Page (Query Mode)', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'configuration',
				'range_settings'  => [
					'min'  => 3,
					'max'  => 24,
					'step' => 3,
				],
				'default'         => '12',
				'show_if'         => [ 'data_source' => 'query' ],
				'toggle_slug'     => 'query',
			],
			'card_style' => [
				'label'           => esc_html__( 'Theme Preset', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'elevated'  => esc_html__( 'Modern Elevated (Default)', 'rawnaq' ),
					'glass'     => esc_html__( 'Minimal Glassmorphism', 'rawnaq' ),
					'spotlight' => esc_html__( 'Editorial Spotlight', 'rawnaq' ),
				],
				'default'         => 'elevated',
				'toggle_slug'     => 'layout',
			],
			'grid_layout' => [
				'label'           => esc_html__( 'Grid Layout Style', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'bento'   => esc_html__( 'Bento Showcase (Featured Cards Span 2 Cols)', 'rawnaq' ),
					'uniform' => esc_html__( 'Uniform Equal Grid', 'rawnaq' ),
					'masonry' => esc_html__( 'Masonry Waterfall Grid', 'rawnaq' ),
				],
				'default'         => 'bento',
				'toggle_slug'     => 'layout',
			],
			'grid_columns' => [
				'label'           => esc_html__( 'Desktop Columns', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'layout',
				'range_settings'  => [
					'min'  => 2,
					'max'  => 4,
					'step' => 1,
				],
				'default'         => '3',
				'toggle_slug'     => 'layout',
			],
			'show_filter' => [
				'label'           => esc_html__( 'Show Sector / Category Filter Tabs', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'on',
				'toggle_slug'     => 'filters',
			],
			'filter_year' => [
				'label'           => esc_html__( 'Show Year Facet Dropdown', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'off' => esc_html__( 'No', 'rawnaq' ),
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'filters',
			],
			'filter_service' => [
				'label'           => esc_html__( 'Show Service Facet Dropdown', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'off' => esc_html__( 'No', 'rawnaq' ),
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'filters',
			],
			'hide_budget' => [
				'label'           => esc_html__( 'NDA: Hide Budget Field', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'off' => esc_html__( 'No', 'rawnaq' ),
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'configuration',
			],
			'hide_client' => [
				'label'           => esc_html__( 'NDA: Hide Client Field', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'off' => esc_html__( 'No', 'rawnaq' ),
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'configuration',
			],
			'click_action' => [
				'label'           => esc_html__( 'Card Click Action', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'configuration',
				'options'         => [
					'modal' => esc_html__( 'Open Rich 3D Project Modal', 'rawnaq' ),
					'link'  => esc_html__( 'Navigate Directly to Project URL', 'rawnaq' ),
					'both'  => esc_html__( 'Open Modal with Direct Link CTA', 'rawnaq' ),
				],
				'default'         => 'modal',
				'toggle_slug'     => 'interaction',
			],
			'discuss_target' => [
				'label'           => esc_html__( 'Cross-Module "Discuss Similar Project" Target', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'configuration',
				'options'         => [
					'auto' => esc_html__( 'Auto-detect (Smart Form or Floating Dock)', 'rawnaq' ),
					'form' => esc_html__( 'Scroll & Prefill Smart Form', 'rawnaq' ),
					'dock' => esc_html__( 'Open Floating Dock WhatsApp Channel', 'rawnaq' ),
					'off'  => esc_html__( 'Disable Discuss Button', 'rawnaq' ),
				],
				'default'         => 'auto',
				'toggle_slug'     => 'interaction',
			],
			'projects_json' => [
				'label'           => esc_html__( 'Manual Projects JSON', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'JSON array of projects [{title, image, gallery, sector, year, client, budget, services, excerpt, detail, link, featured, col, row}]', 'rawnaq' ),
				'default'         => '[{"title":"Fintech Global App & Dashboard Redesign","image":"","gallery":[],"sector":"Fintech & SaaS","size":"+140% Conversion · $85M Vol","budget":"$85M Volume","year":"2025","client":"Stripe & Apex Financial","services":"Product Strategy, UI/UX, Design System","excerpt":"A high-performance financial operational canvas unifying multi-currency settlement and real-time risk intelligence.","detail":"End-to-end product overhaul including design system architecture, sub-50ms streaming transaction tables, and tactile mobile workflows across iOS and Android.","featured":true,"col":2,"row":2},{"title":"AI-Powered Medical Intelligence Platform","image":"","gallery":[],"sector":"HealthTech & AI","size":"1.2M Records · 99.9% Uptime","budget":"Enterprise Scope","year":"2025","client":"BioHealth Labs","services":"Cloud Architecture, Web App, AI Models","excerpt":"HIPAA-compliant diagnostic dashboard for rapid multi-modal patient record synthesis and risk prediction.","detail":"Built with zero-drift modular web components and real-time HL7/FHIR event ingestion, reducing diagnosis lookup from minutes to seconds.","featured":false,"col":1,"row":1},{"title":"Autonomous Mobility & Fleet Telemetry","image":"","gallery":[],"sector":"IoT & Automotive","size":"450k Connected Vehicles","budget":"$32M Scope","year":"2024","client":"Nova Transit Network","services":"Real-time Telemetry, Mobile App","excerpt":"Unified command center coordinating connected EV fleets, route optimization, and predictive charging.","detail":"Engineered high-concurrency WebSocket pipelines with live geospatial map clusters and automated charge-scheduling algorithms.","featured":false,"col":1,"row":1},{"title":"Luxury Sustainable Fashion Flagship","image":"","gallery":[],"sector":"E-Commerce & Brand","size":"+320% Growth · 18 Markets","budget":"Global Flagship","year":"2024","client":"Maison Aurelia","services":"Headless Shopify, Brand Identity","excerpt":"Immersive headless commerce platform delivering tactile 3D product previews and instant checkout.","detail":"Architected with global edge caching and WebGL product interactions, driving an 84% reduction in page load time and +320% global sales growth.","featured":false,"col":1,"row":1}]',
				'show_if'         => [ 'data_source' => 'manual' ],
				'toggle_slug'     => 'manual_data',
			],
			'accent_color' => [
				'label'        => esc_html__( 'Accent Glow & Badge Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#0f766e',
				'toggle_slug'  => 'style',
			],
			'card_bg' => [
				'label'        => esc_html__( 'Card Background Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#ffffff',
				'toggle_slug'  => 'style',
			],
			'card_border' => [
				'label'        => esc_html__( 'Card Border Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#e2e8f0',
				'toggle_slug'  => 'style',
			],
			'card_radius' => [
				'label'           => esc_html__( 'Card Border Radius (px)', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'layout',
				'default'         => '18',
				'range_settings'  => [
					'min'  => 0,
					'max'  => 36,
					'step' => 1,
				],
				'toggle_slug'     => 'style',
			],
		];
	}

	public function render( $attrs, $content = null, $render_slug = '' ) {
		wp_enqueue_style( 'rawnaq-case-study-grid' );
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_script( 'rawnaq-case-study-grid' );
		wp_enqueue_script( 'rawnaq-bridge' );

		$source         = sanitize_key( $this->props['data_source'] ?? 'query' );
		$posts_per_page = absint( $this->props['posts_per_page'] ?? 12 );
		$card_style     = sanitize_key( $this->props['card_style'] ?? 'elevated' );
		$layout         = sanitize_key( $this->props['grid_layout'] ?? 'bento' );
		$columns        = max( 2, min( 4, absint( $this->props['grid_columns'] ?? 3 ) ) );
		$show_filter    = ( $this->props['show_filter'] ?? 'on' ) === 'on';
		$filter_year    = ( $this->props['filter_year'] ?? 'off' ) === 'on';
		$filter_service = ( $this->props['filter_service'] ?? 'off' ) === 'on';
		$hide_budget    = ( $this->props['hide_budget'] ?? 'off' ) === 'on';
		$hide_client    = ( $this->props['hide_client'] ?? 'off' ) === 'on';
		$click_action   = sanitize_key( $this->props['click_action'] ?? 'modal' );
		$discuss_target = sanitize_key( $this->props['discuss_target'] ?? 'auto' );
		$accent_color   = sanitize_hex_color( $this->props['accent_color'] ?? '#0f766e' ) ?: '#0f766e';
		$card_bg        = sanitize_hex_color( $this->props['card_bg'] ?? '#ffffff' ) ?: '#ffffff';
		$card_border    = sanitize_hex_color( $this->props['card_border'] ?? '#e2e8f0' ) ?: '#e2e8f0';
		$card_radius    = absint( $this->props['card_radius'] ?? 18 );

		$projects = [];
		if ( 'manual' === $source ) {
			$raw_projects = json_decode( $this->props['projects_json'] ?? '[]', true );
			if ( is_array( $raw_projects ) ) {
				$projects = $raw_projects;
			}
		}

		$cfg = [
			'source'         => $source,
			'projects'       => $projects,
			'queryNumber'    => $posts_per_page,
			'queryOrderby'   => 'date',
			'queryOrder'     => 'DESC',
			'querySector'    => '',
			'cardStyle'      => $card_style,
			'layout'         => $layout,
			'columns'        => $columns,
			'showFilter'     => $show_filter,
			'filterYear'     => $filter_year,
			'filterService'  => $filter_service,
			'sort'           => 'custom',
			'hideBudget'     => $hide_budget,
			'hideClient'     => $hide_client,
			'clickAction'    => $click_action,
			'discussTarget'  => $discuss_target,
			'initialVisible' => 0,
			'loadChunk'      => 3,
			'accent'         => $accent_color,
			'cardBg'         => $card_bg,
			'cardBorder'     => $card_border,
			'radius'         => $card_radius,
		];

		$unique_id = 'divi-cs-' . wp_unique_id();

		ob_start();
		rawnaq_case_study_markup( $cfg, $unique_id );
		return ob_get_clean();
	}
}
