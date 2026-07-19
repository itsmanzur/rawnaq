<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_Case_Study_Grid_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rawnaq_case_study_grid';
	}

	public function get_title() {
		return esc_html__( 'Case-Study Grid', 'rawnaq' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_categories() {
		return [ 'rawnaq' ];
	}

	public function get_style_depends() {
		return [ 'rawnaq-case-study-grid' ];
	}

	public function get_script_depends() {
		return [ 'rawnaq-case-study-grid', 'rawnaq-bridge' ];
	}

	protected function register_controls() {
		$this->start_controls_section( 's_source', [
			'label' => esc_html__( 'Source', 'rawnaq' ),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'source', [
			'label'   => esc_html__( 'Projects source', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'manual',
			'options' => [
				'manual' => esc_html__( 'Manual list (repeater)', 'rawnaq' ),
				'query'  => esc_html__( 'Case Study posts (query)', 'rawnaq' ),
			],
		] );

		$this->add_control( 'query_number', [
			'label'     => esc_html__( 'Number of posts', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::NUMBER,
			'default'   => 12,
			'min'       => -1,
			'max'       => 100,
			'description' => esc_html__( 'Use -1 to show all published case studies.', 'rawnaq' ),
			'condition' => [ 'source' => 'query' ],
		] );
		$this->add_control( 'query_orderby', [
			'label'     => esc_html__( 'Order by', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::SELECT,
			'default'   => 'date',
			'options'   => [
				'date'       => esc_html__( 'Date', 'rawnaq' ),
				'title'      => esc_html__( 'Title', 'rawnaq' ),
				'menu_order' => esc_html__( 'Menu order', 'rawnaq' ),
				'modified'   => esc_html__( 'Last modified', 'rawnaq' ),
				'rand'       => esc_html__( 'Random', 'rawnaq' ),
			],
			'condition' => [ 'source' => 'query' ],
		] );
		$this->add_control( 'query_order', [
			'label'     => esc_html__( 'Order', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::SELECT,
			'default'   => 'DESC',
			'options'   => [
				'DESC' => esc_html__( 'Descending', 'rawnaq' ),
				'ASC'  => esc_html__( 'Ascending', 'rawnaq' ),
			],
			'condition' => [ 'source' => 'query' ],
		] );
		$this->add_control( 'query_sector', [
			'label'       => esc_html__( 'Sector slug (optional)', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => '',
			'label_block' => true,
			'description' => esc_html__( 'Limit to a single Case Study Sector term slug. Leave blank for all sectors.', 'rawnaq' ),
			'condition'   => [ 'source' => 'query' ],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 's_projects', [
			'label'     => esc_html__( 'Projects', 'rawnaq' ),
			'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
			'condition' => [ 'source' => 'manual' ],
		] );

		$repeater = new \Elementor\Repeater();
		$repeater->add_control( 'image', [
			'label'   => esc_html__( 'Cover image', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::MEDIA,
			'default' => [ 'url' => '' ],
		] );
		$repeater->add_control( 'gallery', [
			'label'       => esc_html__( 'Gallery images', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::GALLERY,
			'default'     => [],
			'description' => esc_html__( 'Shown in the detail-modal slider alongside the cover image.', 'rawnaq' ),
		] );
		$repeater->add_control( 'link', [
			'label'       => esc_html__( 'Project link', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::URL,
			'placeholder' => 'https://',
			'default'     => [ 'url' => '' ],
			'description' => esc_html__( 'Used by "Link" / "Both" click actions and the modal CTA.', 'rawnaq' ),
		] );
		$repeater->add_control( 'title', [
			'label'   => esc_html__( 'Title', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => esc_html__( 'Project title', 'rawnaq' ),
		] );
		$repeater->add_control( 'project_id', [
			'label'       => esc_html__( 'Project ID (sync)', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => '',
			'description' => esc_html__( 'Optional id for Timeline/Story sync (e.g. post-123 or custom slug key).', 'rawnaq' ),
		] );
		$repeater->add_control( 'project_slug', [
			'label'   => esc_html__( 'Project slug (sync)', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => '',
		] );
		$repeater->add_control( 'sector', [
			'label'   => esc_html__( 'Sector / category', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => esc_html__( 'Civic', 'rawnaq' ),
		] );
		$repeater->add_control( 'size', [
			'label'   => esc_html__( 'Size / scope', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => '',
		] );
		$repeater->add_control( 'budget', [
			'label'   => esc_html__( 'Budget range', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => '',
		] );
		$repeater->add_control( 'year', [
			'label'   => esc_html__( 'Year completed', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => '',
		] );
		$repeater->add_control( 'client', [
			'label'   => esc_html__( 'Client', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => '',
		] );
		$repeater->add_control( 'services', [
			'label'       => esc_html__( 'Services (comma-separated)', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => '',
			'label_block' => true,
		] );
		$repeater->add_control( 'excerpt', [
			'label'   => esc_html__( 'Card excerpt', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::TEXTAREA,
			'default' => '',
		] );
		$repeater->add_control( 'detail', [
			'label'   => esc_html__( 'Detail (modal)', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::TEXTAREA,
			'default' => '',
		] );
		$repeater->add_control( 'featured', [
			'label'        => esc_html__( 'Featured (larger in bento)', 'rawnaq' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
		] );
		$repeater->add_control( 'col', [
			'label'     => esc_html__( 'Bento column span', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::NUMBER,
			'default'   => 1,
			'min'       => 1,
			'max'       => 4,
			'condition' => [ 'featured' => '' ],
		] );
		$repeater->add_control( 'row', [
			'label'     => esc_html__( 'Bento row span', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::NUMBER,
			'default'   => 1,
			'min'       => 1,
			'max'       => 3,
			'condition' => [ 'featured' => '' ],
		] );

		$defaults = [];
		if ( function_exists( 'rawnaq_case_study_sample_projects' ) ) {
			foreach ( rawnaq_case_study_sample_projects() as $p ) {
				$defaults[] = [
					'image'    => [ 'url' => '' ],
					'gallery'  => [],
					'link'     => [ 'url' => '' ],
					'title'    => $p['title'],
					'sector'   => $p['sector'],
					'size'     => $p['size'],
					'budget'   => $p['budget'],
					'year'     => $p['year'],
					'client'   => $p['client'],
					'services' => $p['services'],
					'excerpt'  => $p['excerpt'],
					'detail'   => $p['detail'],
					'featured' => ! empty( $p['featured'] ) ? 'yes' : '',
					'col'      => $p['col'] ?? 1,
					'row'      => $p['row'] ?? 1,
				];
			}
		}

		$this->add_control( 'projects', [
			'label'       => esc_html__( 'Projects', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'default'     => $defaults,
			'title_field' => '{{{ title }}} · {{{ sector }}}',
		] );

		$this->end_controls_section();

		$this->start_controls_section( 's_layout', [
			'label' => esc_html__( 'Layout & filter', 'rawnaq' ),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'layout', [
			'label'   => esc_html__( 'Layout mode', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'bento',
			'options' => [
				'bento'   => esc_html__( 'Bento (asymmetric)', 'rawnaq' ),
				'uniform' => esc_html__( 'Uniform grid', 'rawnaq' ),
				'masonry' => esc_html__( 'Masonry', 'rawnaq' ),
			],
		] );
		$this->add_control( 'columns', [
			'label'     => esc_html__( 'Columns', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::NUMBER,
			'default'   => 3,
			'min'       => 2,
			'max'       => 4,
			'condition' => [ 'layout!' => 'bento' ],
		] );
		$this->add_control( 'show_filter', [
			'label'        => esc_html__( 'Sector filter bar', 'rawnaq' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );
		$this->add_control( 'filter_year', [
			'label'        => esc_html__( 'Year filter bar', 'rawnaq' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
		] );
		$this->add_control( 'filter_service', [
			'label'        => esc_html__( 'Service filter bar', 'rawnaq' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
		] );
		$this->add_control( 'sort', [
			'label'   => esc_html__( 'Sort', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'custom',
			'options' => [
				'custom'    => esc_html__( 'Custom order', 'rawnaq' ),
				'year_desc' => esc_html__( 'Year (newest first)', 'rawnaq' ),
				'sector'    => esc_html__( 'Sector A–Z', 'rawnaq' ),
			],
		] );
		$this->add_control( 'hide_budget', [
			'label'        => esc_html__( 'NDA: hide budget', 'rawnaq' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
		] );
		$this->add_control( 'hide_client', [
			'label'        => esc_html__( 'NDA: hide client', 'rawnaq' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
		] );
		$this->add_control( 'click_action', [
			'label'   => esc_html__( 'Card click action', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'modal',
			'options' => [
				'modal' => esc_html__( 'Open detail modal', 'rawnaq' ),
				'link'  => esc_html__( 'Go to project link', 'rawnaq' ),
				'both'  => esc_html__( 'Open modal (with link CTA)', 'rawnaq' ),
			],
		] );
		$this->add_control( 'discuss_target', [
			'label'       => esc_html__( 'Discuss this project', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::SELECT,
			'default'     => 'auto',
			'options'     => [
				'auto' => esc_html__( 'Auto (Smart Form → Dock WA)', 'rawnaq' ),
				'form' => esc_html__( 'Smart Form only', 'rawnaq' ),
				'dock' => esc_html__( 'Floating Dock WhatsApp', 'rawnaq' ),
				'off'  => esc_html__( 'Hide CTA', 'rawnaq' ),
			],
			'description' => esc_html__( 'Prefills Smart Form message or opens Dock WhatsApp with project context.', 'rawnaq' ),
		] );
		$this->add_control( 'initial_visible', [
			'label'       => esc_html__( 'Initially visible cards', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::NUMBER,
			'default'     => 0,
			'min'         => 0,
			'max'         => 48,
			'description' => esc_html__( '0 shows all cards with no "Load more" button.', 'rawnaq' ),
		] );
		$this->add_control( 'load_chunk', [
			'label'     => esc_html__( 'Load more chunk size', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::NUMBER,
			'default'   => 3,
			'min'       => 1,
			'max'       => 24,
			'condition' => [ 'initial_visible!' => 0 ],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 's_style', [
			'label' => esc_html__( 'Style', 'rawnaq' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'accent', [
			'label'     => esc_html__( 'Accent (sector badge)', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#fbbf24',
			'selectors' => [ '{{WRAPPER}} .rawnaq-case-study' => '--cs-accent: {{VALUE}};' ],
		] );
		$this->add_control( 'card_bg', [
			'label'     => esc_html__( 'Card background', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => [ '{{WRAPPER}} .rawnaq-case-study' => '--cs-card-bg: {{VALUE}};' ],
		] );
		$this->add_control( 'card_border', [
			'label'     => esc_html__( 'Card border', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#d7e2dc',
			'selectors' => [ '{{WRAPPER}} .rawnaq-case-study' => '--cs-card-border: {{VALUE}};' ],
		] );
		$this->add_control( 'radius', [
			'label'      => esc_html__( 'Card radius', 'rawnaq' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 32 ] ],
			'default'    => [ 'size' => 18 ],
			'selectors'  => [ '{{WRAPPER}} .rawnaq-case-study' => '--cs-radius: {{SIZE}}{{UNIT}};' ],
		] );

		$this->end_controls_section();
	}

	/**
	 * @param array $s Settings.
	 * @return array
	 */
	private function build_cfg( $s ) {
		$source = ( 'query' === ( $s['source'] ?? 'manual' ) ) ? 'query' : 'manual';

		$projects = [];
		if ( 'manual' === $source ) {
			foreach ( ( $s['projects'] ?? [] ) as $row ) {
				$gallery = [];
				foreach ( ( $row['gallery'] ?? [] ) as $g ) {
					if ( ! empty( $g['url'] ) ) {
						$gallery[] = $g['url'];
					}
				}

				$projects[] = [
					'id'       => $row['project_id'] ?? '',
					'slug'     => $row['project_slug'] ?? '',
					'title'    => $row['title'] ?? '',
					'image'    => ! empty( $row['image']['url'] ) ? $row['image']['url'] : '',
					'gallery'  => $gallery,
					'link'     => ! empty( $row['link']['url'] ) ? $row['link']['url'] : '',
					'sector'   => $row['sector'] ?? '',
					'size'     => $row['size'] ?? '',
					'budget'   => $row['budget'] ?? '',
					'year'     => $row['year'] ?? '',
					'client'   => $row['client'] ?? '',
					'services' => $row['services'] ?? '',
					'excerpt'  => $row['excerpt'] ?? '',
					'detail'   => $row['detail'] ?? '',
					'featured' => ( $row['featured'] ?? '' ) === 'yes',
					'col'      => $row['col'] ?? 1,
					'row'      => $row['row'] ?? 1,
				];
			}
		}

		$initial_visible = isset( $s['initial_visible'] ) ? (int) $s['initial_visible'] : 0;

		return [
			'source'         => $source,
			'projects'       => $projects,
			'queryNumber'    => isset( $s['query_number'] ) ? (int) $s['query_number'] : 12,
			'queryOrderby'   => $s['query_orderby'] ?? 'date',
			'queryOrder'     => $s['query_order'] ?? 'DESC',
			'querySector'    => $s['query_sector'] ?? '',
			'layout'         => $s['layout'] ?? 'bento',
			'columns'        => $s['columns'] ?? 3,
			'showFilter'     => ( $s['show_filter'] ?? 'yes' ) === 'yes',
			'filterYear'     => ( $s['filter_year'] ?? '' ) === 'yes',
			'filterService'  => ( $s['filter_service'] ?? '' ) === 'yes',
			'sort'           => $s['sort'] ?? 'custom',
			'hideBudget'     => ( $s['hide_budget'] ?? '' ) === 'yes',
			'hideClient'     => ( $s['hide_client'] ?? '' ) === 'yes',
			'clickAction'    => $s['click_action'] ?? 'modal',
			'discussTarget'  => $s['discuss_target'] ?? 'auto',
			'initialVisible' => max( 0, $initial_visible ),
			'loadChunk'      => max( 1, isset( $s['load_chunk'] ) ? (int) $s['load_chunk'] : 3 ),
			'accent'         => $s['accent'] ?? '',
			'cardBg'         => $s['card_bg'] ?? '',
			'cardBorder'     => $s['card_border'] ?? '',
			'radius'         => isset( $s['radius']['size'] ) ? $s['radius']['size'] : 18,
		];
	}

	protected function render() {
		$s   = $this->get_settings_for_display();
		$cfg = $this->build_cfg( $s );
		rawnaq_case_study_markup( $cfg, 'el-' . $this->get_id() );
	}
}
