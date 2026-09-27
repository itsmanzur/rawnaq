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

        $this->add_control( 'content_selector', [
            'label'       => esc_html__( 'Content Container (CSS selector)', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => 'main, .entry-content, article',
            'description' => esc_html__( 'Where headings are scanned in Auto mode. Leave blank for smart auto-detect. Set this for FSE/block themes with custom wrappers.', 'rawnaq' ),
            'condition'   => [ 'source' => 'auto' ],
        ] );

        $this->add_control( 'hide_if_short', [
            'label'        => esc_html__( 'Hide on Short Pages', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'description'  => esc_html__( 'Hide the progress/TOC when the page is barely taller than the viewport.', 'rawnaq' ),
        ] );

        $this->add_control( 'reading_time', [
            'label'        => esc_html__( 'Show Reading Time', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => [ 'toc_position!' => 'none' ],
        ] );

        $this->add_control( 'show_search', [
            'label'        => esc_html__( 'TOC Search Filter', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => [ 'toc_position!' => 'none' ],
        ] );

        $this->add_control( 'mobile_collapse', [
            'label'        => esc_html__( 'Mobile FAB for TOC', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => [ 'toc_position' => [ 'sticky', 'floating' ] ],
        ] );

        $this->add_control( 'click_to_top', [
            'label'        => esc_html__( 'Click Ring for Back to Top', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => [ 'progress' => [ 'ring', 'both' ] ],
        ] );

        $this->add_control( 'toc_collapsible', [
            'label'        => esc_html__( 'Collapsible Header Accordion', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => [ 'toc_position!' => 'none' ],
        ] );

        $this->add_control( 'url_hash_sync', [
            'label'        => esc_html__( 'Sync URL Hash on Scroll', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => [ 'toc_position!' => 'none' ],
        ] );

        $this->add_control( 'section_reading_time', [
            'label'        => esc_html__( 'Per-Section Time Badges', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'description'  => esc_html__( 'Display estimated ~Xm badges next to H2 items.', 'rawnaq' ),
            'condition'    => [ 'toc_position!' => 'none' ],
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

        $this->add_control( 'ring_size', [
            'label'      => esc_html__( 'Ring Size', 'rawnaq' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 40, 'max' => 96 ] ],
            'default'    => [ 'size' => 56 ],
            'selectors'  => [ '{{WRAPPER}} .rawnaq-spt' => '--spt-ring-size: {{SIZE}}{{UNIT}};' ],
            'condition'  => [ 'progress' => [ 'ring', 'both' ] ],
            'description'=> esc_html__( 'Applies to the fixed progress ring on the page.', 'rawnaq' ),
        ] );

        $this->add_control( 'sync_timeline', [
            'label'       => esc_html__( 'Sync Timeline ID', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => 'rawnaq-tl-hero',
            'description' => esc_html__( 'Optional. Same Named Timeline ID as Scroll Sync Timeline — shows the active step as “Chapter …” near the TOC.', 'rawnaq' ),
            'condition'   => [ 'toc_position!' => 'none' ],
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
            'progress'           => $s['progress'] ?? 'both',
            'barPosition'        => $s['bar_position'] ?? 'top',
            'showPercent'        => ( $s['show_percent'] ?? '' ) === 'yes',
            'clickToTop'         => ( $s['click_to_top'] ?? 'yes' ) === 'yes',
            'tocPosition'        => $s['toc_position'] ?? 'sticky',
            'tocTitle'           => $s['toc_title'] ?? 'Contents',
            'tocCollapsible'     => ( $s['toc_collapsible'] ?? 'yes' ) === 'yes',
            'source'             => $s['source'] ?? 'auto',
            'levels'             => array_values( $levels ),
            'manual'             => $manual,
            'collapseSubs'       => ( $s['collapse_subs'] ?? '' ) === 'yes',
            'showSearch'         => ( $s['show_search'] ?? '' ) === 'yes',
            'urlHashSync'        => ( $s['url_hash_sync'] ?? 'yes' ) === 'yes',
            'sectionReadingTime' => ( $s['section_reading_time'] ?? '' ) === 'yes',
            'smooth'             => ( $s['smooth'] ?? 'yes' ) === 'yes',
            'scrollOffset'       => isset( $s['scroll_offset'] ) ? (int) $s['scroll_offset'] : 80,
            'readingTime'        => ( $s['reading_time'] ?? '' ) === 'yes',
            'mobileCollapse'     => ( $s['mobile_collapse'] ?? 'yes' ) === 'yes',
            'dockAttach'         => ( $s['dock_attach'] ?? '' ) === 'yes',
            'syncTimeline'       => sanitize_text_field( $s['sync_timeline'] ?? '' ),
            'scope'              => sanitize_text_field( $s['content_selector'] ?? '' ),
            'hideIfShort'        => ( $s['hide_if_short'] ?? 'yes' ) === 'yes',
        ];
    }

    protected function render() {
        $s   = $this->get_settings_for_display();
        $cfg = $this->build_cfg( $s );
        $pos = $cfg['tocPosition'];
        $ring = isset( $s['ring_size']['size'] ) ? (int) $s['ring_size']['size'] : 56;
        if ( $ring < 40 ) {
            $ring = 40;
        }
        if ( $ring > 96 ) {
            $ring = 96;
        }
        ?>
        <div class="rawnaq-spt"
             style="--spt-offset: <?php echo esc_attr( (string) $cfg['scrollOffset'] ); ?>px; --spt-ring-size: <?php echo esc_attr( (string) $ring ); ?>px;"
             data-spt="<?php echo esc_attr( wp_json_encode( $cfg ) ); ?>">
            <?php if ( 'none' !== $pos ) : ?>
                <nav class="rawnaq-spt-toc is-<?php echo esc_attr( $pos ); ?>" role="navigation" aria-label="<?php echo esc_attr( $cfg['tocTitle'] ); ?>">
                    <div class="rawnaq-spt-header-wrap">
                        <p class="rawnaq-spt-reading" hidden></p>
                        <p class="rawnaq-spt-chapter" hidden></p>
                        <div class="rawnaq-spt-title-row">
                            <h3 class="rawnaq-spt-title"><?php echo esc_html( $cfg['tocTitle'] ); ?></h3>
                            <?php if ( ! empty( $cfg['tocCollapsible'] ) ) : ?>
                                <button type="button" class="rawnaq-spt-toggle-btn" aria-expanded="true" aria-label="<?php esc_attr_e( 'Toggle Table of Contents', 'rawnaq' ); ?>">
                                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <ul class="rawnaq-spt-list"></ul>
                </nav>
            <?php endif; ?>
        </div>
        <?php
    }
}
