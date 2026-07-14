<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rawnaq_Elementor_Loader {

    private $modules = [];

    public function __construct() {
        $settings = get_option( 'rawnaq_settings', [] );
        $this->modules = isset( $settings['modules'] ) ? $settings['modules'] : [
            'hub-diagram'     => '1',
            'tilt-card'       => '1',
            'scroll-timeline' => '1',
            'floating-dock'   => '1',
        ];

        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
        add_action( 'elementor/elements/categories_registered', [ $this, 'add_widget_category' ] );
    }

    public function add_widget_category( $elements_manager ) {
        $elements_manager->add_category(
            'rawnaq',
            [
                'title' => esc_html__( 'Rawnaq', 'rawnaq' ),
                'icon'  => 'fa fa-plug',
            ]
        );
    }

    public function register_widgets( $widgets_manager ) {
        // Conditionally load widgets if active in settings dashboard
        
        // 1. Hub Diagram
        if ( isset( $this->modules['hub-diagram'] ) && $this->modules['hub-diagram'] === '1' ) {
            require_once RAWNAQ_PATH . 'includes/elementor/widgets/class-hub-diagram-widget.php';
            $widgets_manager->register( new Rawnaq_Hub_Diagram_Widget() );
        }

        // 2. 3D Tilt Card
        if ( isset( $this->modules['tilt-card'] ) && $this->modules['tilt-card'] === '1' ) {
            require_once RAWNAQ_PATH . 'includes/elementor/widgets/class-tilt-card-widget.php';
            $widgets_manager->register( new Rawnaq_Tilt_Card_Widget() );
        }

        // 3. Scroll Timeline
        if ( isset( $this->modules['scroll-timeline'] ) && $this->modules['scroll-timeline'] === '1' ) {
            require_once RAWNAQ_PATH . 'includes/elementor/widgets/class-scroll-timeline-widget.php';
            $widgets_manager->register( new Rawnaq_Scroll_Timeline_Widget() );
        }

        // 4. Floating Dock
        if ( isset( $this->modules['floating-dock'] ) && $this->modules['floating-dock'] === '1' ) {
            require_once RAWNAQ_PATH . 'includes/elementor/widgets/class-floating-dock-widget.php';
            $widgets_manager->register( new Rawnaq_Floating_Dock_Widget() );
        }
    }
}

new Rawnaq_Elementor_Loader();
