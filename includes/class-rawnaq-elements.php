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
        // Migrate legacy Manzur Elements settings once.
        if ( false === get_option( 'rawnaq_settings', false ) ) {
            $legacy = get_option( 'manzur_elements_settings', false );
            if ( false !== $legacy ) {
                update_option( 'rawnaq_settings', $legacy );
                delete_option( 'manzur_elements_settings' );
            }
        }

        // Load settings to check enabled modules
        $settings = get_option( 'rawnaq_settings', [] );
        $modules  = isset( $settings['modules'] ) ? $settings['modules'] : [
            'hub-diagram'     => '1',
            'tilt-card'       => '1',
            'scroll-timeline' => '1',
            'floating-dock'   => '1',
        ];

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
        add_action( 'init', [ $this, 'load_textdomain' ] );
        // Frontend + block editor both need these handles registered.
        add_action( 'wp_enqueue_scripts', [ $this, 'register_shared_assets' ] );
        add_action( 'enqueue_block_editor_assets', [ $this, 'register_shared_assets' ] );
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'rawnaq', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
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
            null
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

        // 4. macOS Floating Dock Assets
        wp_register_style(
            'rawnaq-floating-dock',
            RAWNAQ_URL . 'assets/css/floating-dock.css',
            [],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-floating-dock',
            RAWNAQ_URL . 'assets/js/floating-dock.js',
            [],
            RAWNAQ_VERSION,
            true
        );
    }
}
