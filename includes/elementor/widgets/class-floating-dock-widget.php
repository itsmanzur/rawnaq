<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rawnaq_Floating_Dock_Widget extends \Elementor\Widget_Base {

    public function get_name()       { return 'rawnaq_floating_dock'; }
    public function get_title()      { return esc_html__( 'Floating Dock Menu', 'rawnaq' ); }
    public function get_icon()       { return 'eicon-navigator'; }
    public function get_categories() { return [ 'rawnaq' ]; }

    public function get_style_depends()  { return [ 'rawnaq-floating-dock', 'dashicons' ]; }
    public function get_script_depends() { return [ 'rawnaq-floating-dock' ]; }

    protected function register_controls() {
        // Content Tab - Dock Items Repeater
        $this->start_controls_section( 's_content', [
            'label' => esc_html__( 'Dock Items', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $r = new \Elementor\Repeater();
        $r->add_control( 'label', [
            'label'       => esc_html__( 'Name / Tooltip', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'Action Name',
            'label_block' => true,
        ] );
        $r->add_control( 'icon', [
            'label'       => esc_html__( 'Dashicon Icon', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'dashicons-admin-home',
            'placeholder' => 'dashicons-admin-comments',
        ] );
        $r->add_control( 'link', [
            'label'       => esc_html__( 'Link URL', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'https://...',
        ] );
        $r->add_control( 'color', [
            'label'   => esc_html__( 'Icon Hover Color', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::COLOR,
            'default' => '#6366f1',
        ] );

        $this->add_control( 'dock_items', [
            'label'       => esc_html__( 'Dock Menu Items', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::REPEATER,
            'fields'      => $r->get_controls(),
            'default'     => [
                [ 'label' => 'Home',       'icon' => 'dashicons-admin-home',   'link' => home_url( '/' ) ],
                [ 'label' => 'Messages',   'icon' => 'dashicons-email-alt',    'link' => '#' ],
                [ 'label' => 'Statistics', 'icon' => 'dashicons-chart-bar',   'link' => '#' ],
                [ 'label' => 'Settings',   'icon' => 'dashicons-admin-generic', 'link' => '#' ],
            ],
            'title_field' => '{{{ label }}}',
        ] );
        $this->end_controls_section();

        // Style Tab
        $this->start_controls_section( 's_style', [
            'label' => esc_html__( 'Dock Settings &amp; Colors', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );
        $this->add_control( 'position', [
            'label'   => esc_html__( 'Dock Alignment Position', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'bottom',
            'options' => [
                'bottom' => esc_html__( 'Bottom Center', 'rawnaq' ),
                'left'   => esc_html__( 'Sidebar Left', 'rawnaq' ),
                'right'  => esc_html__( 'Sidebar Right', 'rawnaq' ),
            ],
        ] );
        $this->add_control( 'bg_color', [
            'label'     => esc_html__( 'Dock Background', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => 'rgba(255, 255, 255, 0.4)',
            'selectors' => [ '{{WRAPPER}} .rawnaq-dock-container' => 'background-color: {{VALUE}};' ],
        ] );
        $this->end_controls_section();
    }

    protected function render() {
        $s = $this->get_settings_for_display();
        $items = $s['dock_items'] ?? [];
        $pos = $s['position'] ?? 'bottom';
        ?>
        <div class="rawnaq-dock-container pos-<?php echo esc_attr( $pos ); ?>">
            <?php foreach ( $items as $item ) : 
                $link = ! empty( $item['link'] ) ? esc_url( $item['link'] ) : '#';
                $hover_color = $item['color'] ?? '#6366f1';
                ?>
                <a href="<?php echo esc_url( $link ); ?>" class="rawnaq-dock-item" style="--hover-color: <?php echo esc_attr( $hover_color ); ?>;">
                    <span class="dashicons <?php echo esc_attr( $item['icon'] ); ?>"></span>
                    <span class="rawnaq-dock-tooltip"><?php echo esc_html( $item['label'] ); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <style>
            /* Dynamic inline color changes */
            .rawnaq-dock-item:hover span.dashicons {
                color: var(--hover-color) !important;
            }
        </style>
        <?php
    }

    protected function content_template() {
        ?>
        <#
        var pos = settings.position || 'bottom';
        #>
        <div class="rawnaq-dock-container pos-{{ pos }}">
            <# 
            if ( settings.dock_items ) {
                _.each( settings.dock_items, function( item ) { 
                    var link = item.link || '#';
                    #>
                    <a href="{{ link }}" class="rawnaq-dock-item" style="--hover-color: {{ item.color }};">
                        <span class="dashicons {{ item.icon }}"></span>
                        <span class="rawnaq-dock-tooltip">{{{ item.label }}}</span>
                    </a>
                    <# 
                } );
            } 
            #>
        </div>
        <?php
    }
}
