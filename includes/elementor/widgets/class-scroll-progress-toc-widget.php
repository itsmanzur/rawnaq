<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rawnaq_Scroll_Progress_Toc_Widget extends \Elementor\Widget_Base {

    public function get_name()       { return 'rawnaq_scroll_progress_toc'; }
    public function get_title()      { return esc_html__( 'Scroll Progress + TOC', 'rawnaq' ); }
    public function get_icon()       { return 'eicon-navigation-horizontal'; }
    public function get_categories() { return [ 'rawnaq' ]; }

    public function get_style_depends()  { return [ 'rawnaq-scroll-progress-toc' ]; }
    public function get_script_depends() { return [ 'rawnaq-scroll-progress-toc' ]; }

    protected function register_controls() {
        $this->start_controls_section( 's_content', [
            'label' => esc_html__( 'Progress & TOC', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'progress', [
            'label'   => esc_html__( 'Progress Style', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'both',
            'options' => [
                'bar'  => esc_html__( 'Top / Bottom bar', 'rawnaq' ),
                'ring' => esc_html__( 'Circular ring', 'rawnaq' ),
                'both' => esc_html__( 'Bar + Ring', 'rawnaq' ),
                'none' => esc_html__( 'None', 'rawnaq' ),
            ],
        ] );

        $this->add_control( 'bar_position', [
            'label'     => esc_html__( 'Bar Position', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'default'   => 'top',
            'options'   => [
                'top'    => esc_html__( 'Top', 'rawnaq' ),
                'bottom' => esc_html__( 'Bottom', 'rawnaq' ),
            ],
            'condition' => [ 'progress' => [ 'bar', 'both' ] ],
        ] );

        $this->add_control( 'show_percent', [
            'label'        => esc_html__( 'Show % in Ring', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => [ 'progress' => [ 'ring', 'both' ] ],
        ] );

        $this->add_control( 'toc_position', [
            'label'   => esc_html__( 'TOC Position', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'sticky',
            'options' => [
                'sticky'   => esc_html__( 'Sidebar sticky', 'rawnaq' ),
                'floating' => esc_html__( 'Floating panel', 'rawnaq' ),
                'inline'   => esc_html__( 'Inline box', 'rawnaq' ),
                'none'     => esc_html__( 'Hidden (progress only)', 'rawnaq' ),
            ],
        ] );

        $this->add_control( 'toc_title', [
            'label'     => esc_html__( 'TOC Title', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => 'Contents',
            'condition' => [ 'toc_position!' => 'none' ],
        ] );

        $this->add_control( 'source', [
            'label'     => esc_html__( 'TOC Source', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'default'   => 'auto',
            'options'   => [
                'auto'   => esc_html__( 'Auto-detect headings', 'rawnaq' ),
                'manual' => esc_html__( 'Manual entries', 'rawnaq' ),
            ],
            'condition' => [ 'toc_position!' => 'none' ],
        ] );

        $this->add_control( 'levels', [
            'label'       => esc_html__( 'Heading Levels', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::SELECT2,
            'multiple'    => true,
            'default'     => [ 'h2', 'h3' ],
            'options'     => [
                'h2' => 'H2',
                'h3' => 'H3',
                'h4' => 'H4',
            ],
            'condition'   => [
                'toc_position!' => 'none',
                'source'        => 'auto',
            ],
        ] );

        $r = new \Elementor\Repeater();
        $r->add_control( 'title', [
            'label'   => esc_html__( 'Label', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'Section',
        ] );
        $r->add_control( 'anchor', [
            'label'       => esc_html__( 'Heading ID / Anchor', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'section-id',
        ] );
        $r->add_control( 'level', [
            'label'   => esc_html__( 'Level', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => '2',
            'options' => [ '2' => 'H2', '3' => 'H3', '4' => 'H4' ],
        ] );

        $this->add_control( 'manual_items', [
            'label'     => esc_html__( 'Manual TOC Items', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::REPEATER,
            'fields'    => $r->get_controls(),
            'default'   => [],
            'condition' => [
                'toc_position!' => 'none',
                'source'        => 'manual',
            ],
            'title_field' => '{{{ title }}}',
        ] );

        $this->add_control( 'collapse_subs', [
            'label'        => esc_html__( 'Collapse Sub-headings', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => [ 'toc_position!' => 'none' ],
        ] );

        $this->add_control( 'smooth', [
            'label'        => esc_html__( 'Smooth Scroll', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => [ 'toc_position!' => 'none' ],
        ] );

        $this->add_control( 'scroll_offset', [
            'label'     => esc_html__( 'Scroll Offset (px)', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::NUMBER,
            'default'   => 80,
            'min'       => 0,
            'max'       => 200,
            'condition' => [ 'toc_position!' => 'none' ],
        ] );

        $this->add_control( 'reading_time', [
            'label'        => esc_html__( 'Show Reading Time', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => [ 'toc_position!' => 'none' ],
        ] );

        $this->add_control( 'mobile_collapse', [
            'label'        => esc_html__( 'Mobile FAB for TOC', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => [ 'toc_position' => [ 'sticky', 'floating' ] ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 's_style', [
            'label' => esc_html__( 'Style', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'accent', [
            'label'     => esc_html__( 'Accent Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#FBBF24',
            'selectors' => [ '{{WRAPPER}} .rawnaq-spt' => '--spt-accent: {{VALUE}};' ],
        ] );

        $this->add_control( 'accent_deep', [
            'label'     => esc_html__( 'Active / Bar Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#4338CA',
            'selectors' => [ '{{WRAPPER}} .rawnaq-spt' => '--spt-accent-deep: {{VALUE}};' ],
        ] );

        $this->add_control( 'bar_height', [
            'label'      => esc_html__( 'Bar Thickness', 'rawnaq' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 2, 'max' => 10 ] ],
            'default'    => [ 'size' => 4 ],
            'selectors'  => [ '{{WRAPPER}} .rawnaq-spt' => '--spt-bar-h: {{SIZE}}{{UNIT}};' ],
        ] );

        $this->end_controls_section();
    }

    private function build_cfg( $s ) {
        $levels = $s['levels'] ?? [ 'h2', 'h3' ];
        if ( ! is_array( $levels ) ) {
            $levels = [ 'h2', 'h3' ];
        }
        $manual = [];
        foreach ( ( $s['manual_items'] ?? [] ) as $item ) {
            $manual[] = [
                'title' => $item['title'] ?? '',
                'id'    => sanitize_title( $item['anchor'] ?? '' ),
                'level' => $item['level'] ?? '2',
            ];
        }
        return [
            'progress'       => $s['progress'] ?? 'both',
            'barPosition'    => $s['bar_position'] ?? 'top',
            'showPercent'    => ( $s['show_percent'] ?? '' ) === 'yes',
            'tocPosition'    => $s['toc_position'] ?? 'sticky',
            'tocTitle'       => $s['toc_title'] ?? 'Contents',
            'source'         => $s['source'] ?? 'auto',
            'levels'         => array_values( $levels ),
            'manual'         => $manual,
            'collapseSubs'   => ( $s['collapse_subs'] ?? '' ) === 'yes',
            'smooth'         => ( $s['smooth'] ?? 'yes' ) === 'yes',
            'scrollOffset'   => isset( $s['scroll_offset'] ) ? (int) $s['scroll_offset'] : 80,
            'readingTime'    => ( $s['reading_time'] ?? '' ) === 'yes',
            'mobileCollapse' => ( $s['mobile_collapse'] ?? 'yes' ) === 'yes',
            'hideIfShort'    => true,
        ];
    }

    protected function render() {
        $s   = $this->get_settings_for_display();
        $cfg = $this->build_cfg( $s );
        $pos = $cfg['tocPosition'];
        ?>
        <div class="rawnaq-spt"
             style="--spt-offset: <?php echo esc_attr( (string) $cfg['scrollOffset'] ); ?>px;"
             data-spt="<?php echo esc_attr( wp_json_encode( $cfg ) ); ?>">
            <?php if ( 'none' !== $pos ) : ?>
                <nav class="rawnaq-spt-toc is-<?php echo esc_attr( $pos ); ?>" aria-label="<?php echo esc_attr( $cfg['tocTitle'] ); ?>">
                    <p class="rawnaq-spt-reading" hidden></p>
                    <h3 class="rawnaq-spt-title"><?php echo esc_html( $cfg['tocTitle'] ); ?></h3>
                    <ul class="rawnaq-spt-list"></ul>
                </nav>
            <?php endif; ?>
        </div>
        <?php
    }
}
