<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rawnaq_Elements {

    private static $instance = null;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        require_once RAWNAQ_PATH . 'includes/rawnaq-helpers.php';

        // Migrate legacy Manzur Elements settings once.
        if ( false === get_option( 'rawnaq_settings', false ) ) {
            $legacy = get_option( 'manzur_elements_settings', false );
            if ( false !== $legacy && is_array( $legacy ) ) {
                update_option( 'rawnaq_settings', $legacy );
                delete_option( 'manzur_elements_settings' );
            } else {
                update_option( 'rawnaq_settings', [ 'modules' => rawnaq_default_modules() ] );
            }
        }

        // Elementor may load after this plugin on plugins_loaded — hook both paths.
        if ( did_action( 'elementor/loaded' ) ) {
            require_once RAWNAQ_PATH . 'includes/elementor/class-elementor-loader.php';
        } else {
            add_action( 'elementor/loaded', function () {
                require_once RAWNAQ_PATH . 'includes/elementor/class-elementor-loader.php';
            } );
        }

        // Load Gutenberg Loader
        require_once RAWNAQ_PATH . 'includes/gutenberg/class-gutenberg-loader.php';

        // Load Admin Dashboard
        if ( is_admin() ) {
            require_once RAWNAQ_PATH . 'includes/class-rawnaq-admin-dashboard.php';
            new Rawnaq_Admin_Dashboard();
        }
    }

    private function init_hooks() {
        // Frontend + block editor both need these handles registered.
        add_action( 'wp_enqueue_scripts', [ $this, 'register_shared_assets' ] );
        add_action( 'enqueue_block_editor_assets', [ $this, 'register_shared_assets' ] );

        add_action( 'wp_ajax_rawnaq_dock_click', [ $this, 'ajax_dock_click' ] );
        add_action( 'wp_ajax_nopriv_rawnaq_dock_click', [ $this, 'ajax_dock_click' ] );
        add_action( 'wp_ajax_rawnaq_dock_reset_clicks', [ $this, 'ajax_dock_reset_clicks' ] );
        add_action( 'wp_ajax_rawnaq_timeline_load_more', [ $this, 'ajax_timeline_load_more' ] );
        add_action( 'wp_ajax_nopriv_rawnaq_timeline_load_more', [ $this, 'ajax_timeline_load_more' ] );
    }

    /**
     * Public AJAX: bump floating dock click counters.
     */
    public function ajax_dock_click() {
        check_ajax_referer( 'rawnaq_dock_click', 'nonce' );
        $type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
        if ( ! rawnaq_dock_track_click( $type ) ) {
            wp_send_json_error( [ 'message' => 'invalid' ], 400 );
        }
        wp_send_json_success( [ 'ok' => 1 ] );
    }

    /**
     * Admin AJAX: reset dock click counters.
     */
    public function ajax_dock_reset_clicks() {
        check_ajax_referer( 'rawnaq_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
        }
        update_option( 'rawnaq_dock_clicks', rawnaq_dock_click_defaults(), false );
        wp_send_json_success( [ 'clicks' => rawnaq_dock_get_clicks() ] );
    }

    /**
     * Public AJAX: load next query-mode timeline steps.
     */
    public function ajax_timeline_load_more() {
        check_ajax_referer( 'rawnaq_timeline_load_more', 'nonce' );

        $raw_query = '';
        if ( isset( $_POST['query'] ) ) {
            $raw_query = sanitize_text_field( wp_unslash( $_POST['query'] ) );
        }
        if ( is_string( $raw_query ) && '' !== $raw_query ) {
            $decoded = json_decode( base64_decode( $raw_query ), true );
            if ( ! is_array( $decoded ) ) {
                $decoded = json_decode( $raw_query, true );
            }
        } else {
            $decoded = [];
        }
        if ( ! is_array( $decoded ) ) {
            $decoded = [];
        }

        $offset = isset( $_POST['offset'] ) ? max( 0, absint( $_POST['offset'] ) ) : 0;
        $chunk  = isset( $_POST['chunk'] ) ? max( 1, min( 20, absint( $_POST['chunk'] ) ) ) : 3;
        $layout = isset( $_POST['layout'] ) ? sanitize_html_class( wp_unslash( $_POST['layout'] ) ) : 'alternating';
        $show_numbers = ! empty( $_POST['show_numbers'] );

        $q = rawnaq_timeline_sanitize_query_args( $decoded );
        $max = (int) $q['max'];
        if ( $offset >= $max ) {
            wp_send_json_success( [
                'html'        => '',
                'has_more'    => false,
                'next_offset' => $offset,
            ] );
        }

        $per_page = min( $chunk, $max - $offset );
        $result   = rawnaq_timeline_query_result(
            array_merge( $q, [
                'posts_per_page' => $per_page,
                'offset'         => $offset,
            ] ),
            [ 'builder' => 'ajax' ]
        );

        $steps      = $result['steps'];
        $next       = $offset + count( $steps );
        $found      = (int) $result['found_posts'];
        $has_more   = $next < $max && $next < $found && count( $steps ) > 0;
        $html       = rawnaq_timeline_render_items_html( $steps, $layout, $show_numbers, $offset );

        wp_send_json_success( [
            'html'        => $html,
            'has_more'    => $has_more,
            'next_offset' => $next,
        ] );
    }

    /**
     * Register frontend assets globally.
     * WordPress will only actually LOAD them on demand.
     */
    public function register_shared_assets() {
        // Fonts
        wp_register_style(
            'rawnaq-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap',
            [],
            RAWNAQ_VERSION
        );

        // 1. Hub Diagram Assets
        wp_register_style(
            'rawnaq-hub-diagram',
            RAWNAQ_URL . 'assets/css/hub-diagram.css',
            [],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-hub-diagram',
            RAWNAQ_URL . 'assets/js/hub-diagram.js',
            [],
            RAWNAQ_VERSION,
            true
        );

        // 2. 3D Tilt Card Assets
        wp_register_style(
            'rawnaq-tilt-card',
            RAWNAQ_URL . 'assets/css/tilt-card.css',
            [],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-tilt-card',
            RAWNAQ_URL . 'assets/js/tilt-card.js',
            [],
            RAWNAQ_VERSION,
            true
        );

        // 3. Scroll-Sync Timeline Assets
        wp_register_style(
            'rawnaq-scroll-timeline',
            RAWNAQ_URL . 'assets/css/scroll-timeline.css',
            [],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-scroll-timeline',
            RAWNAQ_URL . 'assets/js/scroll-timeline.js',
            [],
            RAWNAQ_VERSION,
            true
        );
        wp_localize_script( 'rawnaq-scroll-timeline', 'rawnaqTimeline', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'rawnaq_timeline_load_more' ),
        ] );

        // 4. macOS Floating Dock Assets
        // 4. Floating Dock (+ QR for WhatsApp mode)
        wp_register_script(
            'rawnaq-qrcode',
            RAWNAQ_URL . 'assets/js/qrcode.min.js',
            [],
            '1.0.0',
            true
        );
        wp_register_style(
            'rawnaq-floating-dock',
            RAWNAQ_URL . 'assets/css/floating-dock.css',
            [],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-floating-dock',
            RAWNAQ_URL . 'assets/js/floating-dock.js',
            [ 'rawnaq-qrcode' ],
            RAWNAQ_VERSION,
            true
        );
        wp_localize_script( 'rawnaq-floating-dock', 'rawnaqDock', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'rawnaq_dock_click' ),
        ] );

        // 5. Flow Chart
        wp_register_style(
            'rawnaq-flow-chart',
            RAWNAQ_URL . 'assets/css/flow-chart.css',
            [],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-flow-chart',
            RAWNAQ_URL . 'assets/js/flow-chart.js',
            [],
            RAWNAQ_VERSION,
            true
        );

        // 6. Scroll Progress + TOC
        wp_register_style(
            'rawnaq-scroll-progress-toc',
            RAWNAQ_URL . 'assets/css/scroll-progress-toc.css',
            [],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-scroll-progress-toc',
            RAWNAQ_URL . 'assets/js/scroll-progress-toc.js',
            [],
            RAWNAQ_VERSION,
            true
        );

        // 7. Bento Grid
        wp_register_style(
            'rawnaq-bento-grid',
            RAWNAQ_URL . 'assets/css/bento-grid.css',
            [],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-bento-grid',
            RAWNAQ_URL . 'assets/js/bento-grid.js',
            [],
            RAWNAQ_VERSION,
            true
        );
    }
}
