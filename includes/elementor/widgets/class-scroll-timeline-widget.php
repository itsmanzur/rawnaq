<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rawnaq_Scroll_Timeline_Widget extends \Elementor\Widget_Base {

    public function get_name()       { return 'rawnaq_scroll_timeline'; }
    public function get_title()      { return esc_html__( 'Scroll Sync Timeline', 'rawnaq' ); }
    public function get_icon()       { return 'eicon-time-line'; }
    public function get_categories() { return [ 'rawnaq' ]; }

    public function get_style_depends()  { return [ 'rawnaq-scroll-timeline', 'rawnaq-fonts' ]; }
    public function get_script_depends() { return [ 'rawnaq-scroll-timeline' ]; }

    protected function register_controls() {
        // Content Tab - Timeline List Repeater
        $this->start_controls_section( 's_content', [
            'label' => esc_html__( 'Timeline Steps', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $r = new \Elementor\Repeater();
        $r->add_control( 'title', [
            'label'       => esc_html__( 'Step Title', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'Milestone Step',
            'label_block' => true,
        ] );
        $r->add_control( 'desc', [
            'label'   => esc_html__( 'Description', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::TEXTAREA,
            'default' => 'Add some detail description about this milestone step.',
            'rows'    => 3,
        ] );

        $this->add_control( 'steps', [
            'label'       => esc_html__( 'Timeline Steps', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::REPEATER,
            'fields'      => $r->get_controls(),
            'default'     => [
                [ 'title' => 'Step 1: Ideation & Sketching', 'desc' => 'Gather initial ideas, build sketches and draft blueprints.' ],
                [ 'title' => 'Step 2: Prototype Review', 'desc' => 'Interactive mockup presentation and client reviews session.' ],
                [ 'title' => 'Step 3: Development & Coding', 'desc' => 'Refactoring logic classes, testing speeds and deploying code.' ],
            ],
            'title_field' => '{{{ title }}}',
        ] );
        $this->end_controls_section();

        // Style Tab
        $this->start_controls_section( 's_style', [
            'label' => esc_html__( 'Style &amp; Colors', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );
        $this->add_control( 'line_bg', [
            'label'     => esc_html__( 'Line Background Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#e2e8f0',
            'selectors' => [ '{{WRAPPER}} .rawnaq-timeline-line-bg' => 'background-color: {{VALUE}};' ],
        ] );
        $this->add_control( 'line_active', [
            'label'     => esc_html__( 'Active Line Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#6366f1',
            'selectors' => [ '{{WRAPPER}} .rawnaq-timeline-line-active' => 'background: {{VALUE}};' ],
        ] );
        $this->add_control( 'card_bg', [
            'label'     => esc_html__( 'Card Background Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [ '{{WRAPPER}} .rawnaq-timeline-card' => 'background-color: {{VALUE}};' ],
        ] );
        $this->add_control( 'title_color', [
            'label'     => esc_html__( 'Title Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#1a1a1a',
            'selectors' => [ '{{WRAPPER}} .rawnaq-timeline-card h4' => 'color: {{VALUE}};' ],
        ] );
        $this->add_control( 'desc_color', [
            'label'     => esc_html__( 'Description Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#666666',
            'selectors' => [ '{{WRAPPER}} .rawnaq-timeline-card p' => 'color: {{VALUE}};' ],
        ] );
        $this->end_controls_section();
    }

    protected function render() {
        $s = $this->get_settings_for_display();
        $steps = $s['steps'] ?? [];
        ?>
        <div class="rawnaq-timeline-wrapper">
            <div class="rawnaq-timeline-line-bg"></div>
            <div class="rawnaq-timeline-line-active"></div>

            <?php foreach ( $steps as $index => $step ) : 
                $alignment_class = ( $index % 2 === 0 ) ? 'left-item' : 'right-item';
                ?>
                <div class="rawnaq-timeline-item <?php echo esc_attr( $alignment_class ); ?>">
                    <span class="rawnaq-timeline-bullet"></span>
                    <div class="rawnaq-timeline-card">
                        <h4><?php echo esc_html( $step['title'] ); ?></h4>
                        <p><?php echo esc_html( $step['desc'] ); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    protected function content_template() {
        ?>
        <div class="rawnaq-timeline-wrapper">
            <div class="rawnaq-timeline-line-bg"></div>
            <div class="rawnaq-timeline-line-active"></div>

            <# 
            if ( settings.steps ) {
                _.each( settings.steps, function( step, index ) { 
                    var alignment = ( index % 2 === 0 ) ? 'left-item' : 'right-item';
                    #>
                    <div class="rawnaq-timeline-item {{ alignment }}">
                        <span class="rawnaq-timeline-bullet"></span>
                        <div class="rawnaq-timeline-card">
                            <h4>{{{ step.title }}}</h4>
                            <p>{{{ step.desc }}}</p>
                        </div>
                    </div>
                    <# 
                } );
            } 
            #>
        </div>
        <?php
    }
}
