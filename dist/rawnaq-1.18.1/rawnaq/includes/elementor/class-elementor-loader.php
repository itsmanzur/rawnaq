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
        add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'enqueue_editor_scripts' ] );
    }

    public function enqueue_editor_scripts() {
        if ( rawnaq_is_module_enabled( 'bento-grid' ) ) {
            wp_enqueue_script(
                'rawnaq-bento-grid-editor',
                RAWNAQ_URL . 'assets/js/bento-grid-editor.js',
                [ 'jquery', 'elementor-editor' ],
                RAWNAQ_VERSION,
                true
            );

            $presets = [];
            foreach ( [ 'featured', 'equal', 'wide' ] as $key ) {
                $pack = rawnaq_bento_preset_for_elementor( $key );
                if ( $pack ) {
                    $presets[ $key ] = $pack;
                }
            }

            wp_localize_script( 'rawnaq-bento-grid-editor', 'rawnaqBentoEditor', [
                'presets' => $presets,
                'i18n'    => [
                    'applied'    => __( 'Preset applied — cells updated.', 'rawnaq' ),
                    'customHint' => __( 'Pick a layout preset (not Custom), then Apply.', 'rawnaq' ),
                ],
            ] );
        }

        if ( rawnaq_is_module_enabled( 'scroll-timeline' ) && function_exists( 'rawnaq_timeline_presets' ) ) {
            wp_enqueue_script(
                'rawnaq-scroll-timeline-editor',
                RAWNAQ_URL . 'assets/js/scroll-timeline-editor.js',
                [ 'jquery', 'elementor-editor' ],
                RAWNAQ_VERSION,
                true
            );

            $tl_presets = [];
            foreach ( array_keys( rawnaq_timeline_presets() ) as $key ) {
                $pack = rawnaq_timeline_preset_for_elementor( $key );
                if ( $pack ) {
                    $tl_presets[ $key ] = $pack;
                }
            }

            wp_localize_script( 'rawnaq-scroll-timeline-editor', 'rawnaqTimelineEditor', [
                'presets' => $tl_presets,
                'i18n'    => [
                    'applied'  => __( 'Preset applied — steps updated.', 'rawnaq' ),
                    'pickHint' => __( 'Choose an agency preset, then Apply.', 'rawnaq' ),
                ],
            ] );
        }

        if ( rawnaq_is_module_enabled( 'smart-form' ) && function_exists( 'rawnaq_smart_form_preset_for_elementor' ) ) {
            wp_enqueue_script(
                'rawnaq-smart-form-editor',
                RAWNAQ_URL . 'assets/js/smart-form-editor.js',
                [ 'jquery', 'elementor-editor' ],
                RAWNAQ_VERSION,
                true
            );
            $sf_presets = [];
            foreach ( array_keys( rawnaq_smart_form_presets() ) as $key ) {
                $pack = rawnaq_smart_form_preset_for_elementor( $key );
                if ( $pack ) {
                    $sf_presets[ $key ] = $pack;
                }
            }
            wp_localize_script( 'rawnaq-smart-form-editor', 'rawnaqSmartFormEditor', [
                'presets' => $sf_presets,
                'i18n'    => [
                    'applied' => __( 'Preset applied — fields updated.', 'rawnaq' ),
                    'pick'    => __( 'Pick a layout preset, then Apply.', 'rawnaq' ),
                ],
            ] );
        }

        if ( rawnaq_is_module_enabled( 'flow-chart' ) ) {
            wp_enqueue_script(
                'rawnaq-flow-chart-editor',
                RAWNAQ_URL . 'assets/js/flow-chart-editor.js',
                [ 'jquery', 'elementor-editor' ],
                RAWNAQ_VERSION,
                true
            );

            $user_nodes = function_exists( 'rawnaq_flow_nodes_from_users' )
                ? rawnaq_flow_nodes_from_users( [ 'number' => 20 ] )
                : [];

            wp_localize_script( 'rawnaq-flow-chart-editor', 'rawnaqFlowEditor', [
                'userNodes' => $user_nodes,
                'i18n'      => [
                    'root' => __( '— Root (no parent) —', 'rawnaq' ),
                ],
            ] );
        }
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

        // 7. Bento Grid
        if ( rawnaq_is_module_enabled( 'bento-grid' ) ) {
            require_once RAWNAQ_PATH . 'includes/elementor/widgets/class-bento-grid-widget.php';
            $widgets_manager->register( new Rawnaq_Bento_Grid_Widget() );
        }

        // 8. Scroll Story Chapters
        if ( rawnaq_is_module_enabled( 'scroll-story' ) ) {
            require_once RAWNAQ_PATH . 'includes/elementor/widgets/class-scroll-story-widget.php';
            $widgets_manager->register( new Rawnaq_Scroll_Story_Widget() );
        }

        // 9. Smart Form
        if ( rawnaq_is_module_enabled( 'smart-form' ) ) {
            require_once RAWNAQ_PATH . 'includes/elementor/widgets/class-smart-form-widget.php';
            $widgets_manager->register( new Rawnaq_Smart_Form_Widget() );
        }

        // 10. Case-Study Grid
        if ( rawnaq_is_module_enabled( 'case-study-grid' ) ) {
            require_once RAWNAQ_PATH . 'includes/elementor/widgets/class-case-study-grid-widget.php';
            $widgets_manager->register( new Rawnaq_Case_Study_Grid_Widget() );
        }
    }
}

new Rawnaq_Elementor_Loader();
