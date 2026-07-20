<?php
/**
 * Plugin Name:  Rawnaq
 * Plugin URI:   https://github.com/itsmanzur/rawnaq
 * Description:  A highly optimized, lightweight, and modular addon pack for Elementor, Gutenberg, and other page builders. Designed for maximum speed and clean output.
 * Version:      1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author:       itsmanzur
 * Author URI:   https://github.com/itsmanzur
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
define( 'RAWNAQ_VERSION', '1.0.0' );
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

/**
 * Add a "Settings" link on the Plugins list row.
 *
 * @param array $links Existing action links.
 * @return array
 */
function rawnaq_plugin_action_links( $links ) {
    $settings = '<a href="' . esc_url( admin_url( 'admin.php?page=rawnaq' ) ) . '">'
        . esc_html__( 'Settings', 'rawnaq' ) . '</a>';
    array_unshift( $links, $settings );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'rawnaq_plugin_action_links' );
