<?php
/**
 * Plugin Name:  Rawnaq
 * Plugin URI:   https://github.com/Rawnaq/rawnaq
 * Description:  A highly optimized, lightweight, and modular addon pack for Elementor, Gutenberg, and other page builders. Designed for maximum speed and clean output.
 * Version:      1.10.0
 * Author:       Rawnaq
 * Author URI:   https://github.com/Rawnaq
 * License:      GPL v2 or later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  rawnaq
 * Domain Path:  /languages
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define core constants.
define( 'RAWNAQ_VERSION', '1.10.0' );
define( 'RAWNAQ_PATH', plugin_dir_path( __FILE__ ) );
define( 'RAWNAQ_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoloader for Rawnaq.
 * Automatically loads classes based on namespace or class name structure.
 */
spl_autoload_register( function( $class ) {
    // Only autoload classes belonging to this plugin.
    if ( strpos( $class, 'Rawnaq_' ) !== 0 ) {
        return;
    }

    $class_name = strtolower( str_replace( '_', '-', $class ) );
    $file_name  = 'class-' . $class_name . '.php';

    // Map subfolders.
    $paths = [
        RAWNAQ_PATH . 'includes/',
        RAWNAQ_PATH . 'includes/elementor/',
        RAWNAQ_PATH . 'includes/gutenberg/',
    ];

    foreach ( $paths as $path ) {
        $file = $path . $file_name;
        if ( file_exists( $file ) ) {
            require_once $file;
            return;
        }
    }
} );

/**
 * Initialize Plugin Core.
 */
function rawnaq_run() {
    // Load Core Singleton.
    Rawnaq_Elements::get_instance();
}
add_action( 'plugins_loaded', 'rawnaq_run' );
