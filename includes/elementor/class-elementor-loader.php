<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rawnaq_Elementor_Loader {

    private $modules = [];

    public function __construct() {
        $this->modules = rawnaq_get_modules();

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
        // 1. Hub Diagram
        if ( rawnaq_is_module_enabled( 'hub-diagram' ) ) {
            require_once RAWNAQ_PATH . 'includes/elementor/widgets/class-hub-diagram-widget.php';
            $widgets_manager->register( new Rawnaq_Hub_Diagram_Widget() );
        }

        // 2. 3D Tilt Card
        if ( rawnaq_is_module_enabled( 'tilt-card' ) ) {
            require_once RAWNAQ_PATH . 'includes/elementor/widgets/class-tilt-card-widget.php';
            $widgets_manager->register( new Rawnaq_Tilt_Card_Widget() );
        }

        // 3. Scroll Timeline
        if ( rawnaq_is_module_enabled( 'scroll-timeline' ) ) {
            require_once RAWNAQ_PATH . 'includes/elementor/widgets/class-scroll-timeline-widget.php';
            $widgets_manager->register( new Rawnaq_Scroll_Timeline_Widget() );
        }

        // 4. Floating Dock
        if ( rawnaq_is_module_enabled( 'floating-dock' ) ) {
            require_once RAWNAQ_PATH . 'includes/elementor/widgets/class-floating-dock-widget.php';
            $widgets_manager->register( new Rawnaq_Floating_Dock_Widget() );
        }

        // 5. Flow Chart
        if ( rawnaq_is_module_enabled( 'flow-chart' ) ) {
            require_once RAWNAQ_PATH . 'includes/elementor/widgets/class-flow-chart-widget.php';
            $widgets_manager->register( new Rawnaq_Flow_Chart_Widget() );
        }

        // 6. Scroll Progress + TOC
        if ( rawnaq_is_module_enabled( 'scroll-progress-toc' ) ) {
            require_once RAWNAQ_PATH . 'includes/elementor/widgets/class-scroll-progress-toc-widget.php';
            $widgets_manager->register( new Rawnaq_Scroll_Progress_Toc_Widget() );
        }
    }
}

new Rawnaq_Elementor_Loader();
