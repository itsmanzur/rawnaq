<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rawnaq_Admin_Dashboard {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'wp_ajax_rawnaq_save_modules', [ $this, 'save_modules_via_ajax' ] );
    }

    public function add_menu_page() {
        add_menu_page(
            esc_html__( 'Rawnaq', 'rawnaq' ),
            esc_html__( 'Rawnaq', 'rawnaq' ),
            'manage_options',
            'rawnaq',
            [ $this, 'render_dashboard' ],
            'dashicons-superhero',
            59
        );
    }

    public function enqueue_admin_assets( $hook ) {
        if ( 'toplevel_page_rawnaq' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'rawnaq-admin-css',
            RAWNAQ_URL . 'assets/css/admin.css',
            [],
            RAWNAQ_VERSION
        );

        wp_enqueue_script(
            'rawnaq-admin-js',
            RAWNAQ_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            RAWNAQ_VERSION,
            true
        );

        wp_localize_script( 'rawnaq-admin-js', 'rawnaq_admin_vars', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'rawnaq_admin_nonce' ),
        ] );
    }

    public function render_dashboard() {
        // Ensure helpers are available for consistent module state.
        if ( ! function_exists( 'rawnaq_get_modules' ) ) {
            require_once RAWNAQ_PATH . 'includes/rawnaq-helpers.php';
        }
        $modules = rawnaq_get_modules();

        // Check compatibility
        $php_version = phpversion();
        $wp_version  = get_bloginfo( 'version' );
        $elementor_active = did_action( 'elementor/loaded' ) ? 'Active' : 'Inactive';
        ?>
        <div class="rawnaq-admin-wrap">
            <!-- Header Banner -->
            <header class="rawnaq-header">
                <div class="rawnaq-logo">
                    <span class="logo-mark" aria-hidden="true">R</span>
                    <div>
                        <h1>Rawnaq</h1>
                        <p><?php esc_html_e( 'Performance addons for WordPress builders', 'rawnaq' ); ?></p>
                    </div>
                </div>
                <div class="plugin-badge">v<?php echo esc_html( RAWNAQ_VERSION ); ?></div>
            </header>

            <div class="rawnaq-layout">
                <!-- Navigation Tabs -->
                <aside class="rawnaq-sidebar">
                    <nav class="rawnaq-nav">
                        <a href="#welcome" class="nav-item active" data-tab="welcome">
                            <span class="nav-icon">&#x1F3E0;</span> Dashboard
                        </a>
                        <a href="#modules" class="nav-item" data-tab="modules">
                            <span class="nav-icon">&#x9881;</span> Elements Manager
                        </a>
                        <a href="#docs" class="nav-item" data-tab="docs">
                            <span class="nav-icon">&#x1F4D6;</span> Documentation
                        </a>
                        <a href="#system" class="nav-item" data-tab="system">
                            <span class="nav-icon">&#x1F4BB;</span> System Info
                        </a>
                    </nav>
                    <div class="sidebar-footer">
                        <p>Need help?</p>
                        <a href="https://github.com/Rawnaq/rawnaq/issues" target="_blank" class="doc-btn">Open Support Ticket</a>
                    </div>
                </aside>

                <!-- Content Area -->
                <main class="rawnaq-content">
                    <!-- TAB 1: WELCOME -->
                    <div id="tab-welcome" class="tab-panel active">
                        <div class="welcome-banner">
                            <h2>Welcome to Rawnaq! &#x1F389;</h2>
                            <p>An elite, speed-first modular library. Enable only the elements you need and keep your site running at lightning speeds.</p>
                        </div>

                        <div class="grid-2">
                            <div class="rawnaq-card">
                                <h3>Quick Start</h3>
                                <p>To use our widgets, simply go to your page builder and search for the active elements (e.g. <strong>Hub Diagram</strong>, <strong>3D Tilt Card</strong>, <strong>Scroll Sync Timeline</strong>, <strong>Floating Dock Menu</strong>) inside Elementor or Gutenberg.</p>
                                <a href="#modules" class="btn btn-primary trigger-tab-change" data-target="modules">Manage Elements</a>
                            </div>
                            <div class="rawnaq-card">
                                <h3>Speed First Principle</h3>
                                <p>We don't load external libraries, frameworks, or jQuery on the frontend for our elements. Everything is written in clean vanilla code, loading dynamically only where used.</p>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: MODULES MANAGER -->
                    <div id="tab-modules" class="tab-panel">
                        <div class="modules-hero">
                            <div class="modules-hero-copy">
                                <p class="modules-kicker"><?php esc_html_e( 'Performance control', 'rawnaq' ); ?></p>
                                <h2><?php esc_html_e( 'Elements Manager', 'rawnaq' ); ?></h2>
                                <p class="section-desc"><?php esc_html_e( 'Toggle widgets on or off. Disabled elements never load assets on the frontend — keep the site lean.', 'rawnaq' ); ?></p>
                            </div>
                            <div class="modules-stat" id="modules-active-stat" aria-live="polite">
                                <span class="modules-stat-num" id="modules-active-count">0</span>
                                <span class="modules-stat-label"><?php esc_html_e( 'Active', 'rawnaq' ); ?></span>
                            </div>
                        </div>

                        <form id="rawnaq-modules-form">
                            <div class="modules-grid">
                                <?php
                                $module_defs = [
                                    [
                                        'key'         => 'hub-diagram',
                                        'badge'       => __( 'Diagram', 'rawnaq' ),
                                        'tone'        => 'tone-diagram',
                                        'title'       => __( 'Hub Diagram', 'rawnaq' ),
                                        'desc'        => __( 'Interactive radial workflow chart with spokes and glow particle flow.', 'rawnaq' ),
                                        'icon'        => 'hub',
                                    ],
                                    [
                                        'key'         => 'tilt-card',
                                        'badge'       => __( 'Visuals', 'rawnaq' ),
                                        'tone'        => 'tone-visuals',
                                        'title'       => __( '3D Tilt Card', 'rawnaq' ),
                                        'desc'        => __( 'Perspective mouse-tilt cards with glare, overlay, and parallax depth.', 'rawnaq' ),
                                        'icon'        => 'tilt',
                                    ],
                                    [
                                        'key'         => 'scroll-timeline',
                                        'badge'       => __( 'Layouts', 'rawnaq' ),
                                        'tone'        => 'tone-layouts',
                                        'title'       => __( 'Scroll Sync Timeline', 'rawnaq' ),
                                        'desc'        => __( 'Vertical milestone timeline with a scroll-driven progress line.', 'rawnaq' ),
                                        'icon'        => 'timeline',
                                    ],
                                    [
                                        'key'         => 'floating-dock',
                                        'badge'       => __( 'Navigation', 'rawnaq' ),
                                        'tone'        => 'tone-nav',
                                        'title'       => __( 'Floating Dock Menu', 'rawnaq' ),
                                        'desc'        => __( 'macOS-style floating dock with proximity magnification and badges.', 'rawnaq' ),
                                        'icon'        => 'dock',
                                    ],
                                    [
                                        'key'         => 'flow-chart',
                                        'badge'       => __( 'Diagram', 'rawnaq' ),
                                        'tone'        => 'tone-diagram',
                                        'title'       => __( 'Flow Chart', 'rawnaq' ),
                                        'desc'        => __( 'Org tree and process flow diagrams with animated connectors.', 'rawnaq' ),
                                        'icon'        => 'flow',
                                    ],
                                    [
                                        'key'         => 'scroll-progress-toc',
                                        'badge'       => __( 'Layouts', 'rawnaq' ),
                                        'tone'        => 'tone-layouts',
                                        'title'       => __( 'Scroll Progress + TOC', 'rawnaq' ),
                                        'desc'        => __( 'Reading progress bar/ring with smart auto-highlighting table of contents.', 'rawnaq' ),
                                        'icon'        => 'toc',
                                    ],
                                ];

                                foreach ( $module_defs as $mod ) :
                                    $checked = isset( $modules[ $mod['key'] ] ) && $modules[ $mod['key'] ] === '1';
                                    ?>
                                    <div class="module-card <?php echo esc_attr( $mod['tone'] ); ?><?php echo $checked ? ' is-on' : ''; ?>">
                                        <div class="module-card-top">
                                            <div class="module-icon" aria-hidden="true">
                                                <?php if ( 'hub' === $mod['icon'] ) : ?>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="3.5"/><circle cx="12" cy="3.5" r="1.5"/><circle cx="20" cy="8" r="1.5"/><circle cx="20" cy="16" r="1.5"/><circle cx="12" cy="20.5" r="1.5"/><circle cx="4" cy="16" r="1.5"/><circle cx="4" cy="8" r="1.5"/><path d="M12 8.5V5M16 10.2l2.8-1.6M16 13.8l2.8 1.6M12 15.5v3.5M8 13.8l-2.8 1.6M8 10.2 5.2 8.6"/></svg>
                                                <?php elseif ( 'tilt' === $mod['icon'] ) : ?>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="5" y="4" width="14" height="16" rx="2.5" transform="rotate(-8 12 12)"/><path d="M9 10h6M9 14h4"/></svg>
                                                <?php elseif ( 'timeline' === $mod['icon'] ) : ?>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 3v18"/><circle cx="12" cy="7" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="17" r="2"/><path d="M14.5 7H19M5 12h7M14.5 17H19"/></svg>
                                                <?php elseif ( 'flow' === $mod['icon'] ) : ?>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="8" y="2" width="8" height="5" rx="1.5"/><rect x="2" y="17" width="7" height="5" rx="1.5"/><rect x="15" y="17" width="7" height="5" rx="1.5"/><path d="M12 7v4M12 11H5.5v6M12 11h6.5v6"/></svg>
                                                <?php elseif ( 'toc' === $mod['icon'] ) : ?>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M4 6h16M4 12h10M4 18h14"/><circle cx="19" cy="12" r="2.5"/></svg>
                                                <?php else : ?>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="14" width="18" height="6" rx="3"/><rect x="5" y="16" width="3.2" height="3.2" rx="0.8"/><rect x="10.4" y="15.2" width="4" height="4" rx="1"/><rect x="16.2" y="16" width="3.2" height="3.2" rx="0.8"/></svg>
                                                <?php endif; ?>
                                            </div>
                                            <label class="switch" title="<?php echo esc_attr( $mod['title'] ); ?>">
                                                <input type="checkbox" name="modules[<?php echo esc_attr( $mod['key'] ); ?>]" value="1" <?php checked( $checked ); ?> class="module-toggle-input">
                                                <span class="slider round"></span>
                                                <?php
                                                /* translators: %s: module / widget title */
                                                $enable_label = sprintf( __( 'Enable %s', 'rawnaq' ), $mod['title'] );
                                                ?>
                                                <span class="screen-reader-text"><?php echo esc_html( $enable_label ); ?></span>
                                            </label>
                                        </div>
                                        <div class="module-info">
                                            <span class="module-badge"><?php echo esc_html( $mod['badge'] ); ?></span>
                                            <h4><?php echo esc_html( $mod['title'] ); ?></h4>
                                            <p><?php echo esc_html( $mod['desc'] ); ?></p>
                                            <div class="module-meta">
                                                <span>Elementor</span>
                                                <span>Gutenberg</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="form-footer modules-footer">
                                <button type="submit" class="btn btn-save" id="btn-save-settings"><?php esc_html_e( 'Save Changes', 'rawnaq' ); ?></button>
                                <span class="save-status" id="save-status-msg"></span>
                            </div>
                        </form>
                    </div>

                    <!-- TAB 3: DOCUMENTATION -->
                    <div id="tab-docs" class="tab-panel">
                        <h2>Documentation &amp; Usage Guide</h2>
                        <p class="section-desc">Detailed usage guides on how to configure and deploy the active widgets.</p>

                        <div class="rawnaq-doc-card" style="margin-bottom: 24px;">
                            <h3>1. Hub Diagram</h3>
                            <p>A highly performant radial workflow diagram connecting spokes to a center circle. Features responsive auto-timeline columns on mobile and animated glow particle flows.</p>
                            <h4>Usage:</h4>
                            <ul>
                                <li><strong>Elementor:</strong> Search for "Hub Diagram" widget, drag and edit text details. In the Style tab, check the Layout option to switch from horizontal rows to 360° Radial mode, or enable Glow Flow lines.</li>
                                <li><strong>Gutenberg:</strong> In insert bar search "Hub Diagram (Rawnaq)", insert block, and use sidebar settings controls.</li>
                            </ul>
                        </div>

                        <div class="rawnaq-doc-card" style="margin-bottom: 24px;">
                            <h3>2. 3D Tilt Card</h3>
                            <p>A card widget that calculates coordinate locations of the mouse cursor inside the card box dynamically and tilts the element on rotateX/rotateY axes, creating a parallax 3D effect.</p>
                            <h4>Usage:</h4>
                            <ul>
                                <li><strong>Elementor:</strong> Drag "3D Tilt Card" widget, choose standard Dashicon, write title text, select redirect URL, and adjust the Max Tilt Intensity slider in settings.</li>
                                <li><strong>Gutenberg:</strong> Search and insert "3D Tilt Card" block. Settings sidebar contains title, description, link options, and tilt intensity controls.</li>
                            </ul>
                        </div>

                        <div class="rawnaq-doc-card" style="margin-bottom: 24px;">
                            <h3>3. Scroll Sync Timeline</h3>
                            <p>A vertical milestones process tracker. Utilizes IntersectionObserver on the client-side to track card viewport entry, revealing nodes smoothly. The main center line fills color based on vertical viewport scroll progress.</p>
                            <h4>Usage:</h4>
                            <ul>
                                <li><strong>Elementor:</strong> Drag "Scroll Sync Timeline" widget. Add steps inside the content repeater fields. Color codes can be customized under the Style tab.</li>
                                <li><strong>Gutenberg:</strong> Insert "Scroll Sync Timeline" block. Add new milestones in inspector control sidebar fields.</li>
                            </ul>
                        </div>

                        <div class="rawnaq-doc-card">
                            <h3>4. Floating Dock Menu</h3>
                            <p>A sticky action dock resembling macOS launch bars. Vanilla JS calculates cursor proximity distance to the icons, scaling nearest items exponentially. Tooltips overlay above each item on hover.</p>
                            <h4>Usage:</h4>
                            <ul>
                                <li><strong>Elementor:</strong> Drag "Floating Dock Menu" widget. Add action link repeaters (home, email, generic settings). Select Alignment Position: Bottom Center, Sidebar Left, or Sidebar Right.</li>
                                <li><strong>Gutenberg:</strong> Insert "Floating Dock Menu" block. Set position and add action links in block sidebar.</li>
                            </ul>
                        </div>
                    </div>

                    <!-- TAB 4: SYSTEM INFO -->
                    <div id="tab-system" class="tab-panel">
                        <h2>System Information</h2>
                        <p class="section-desc">Review your site's parameters and configuration settings below.</p>

                        <div class="rawnaq-card">
                            <table class="system-table">
                                <tr>
                                    <td><strong>WordPress Version:</strong></td>
                                    <td><?php echo esc_html( $wp_version ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>PHP Version:</strong></td>
                                    <td><?php echo esc_html( $php_version ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Elementor Status:</strong></td>
                                    <td>
                                        <span class="status-badge <?php echo $elementor_active === 'Active' ? 'active' : 'inactive'; ?>">
                                            <?php echo esc_html( $elementor_active ); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Gutenberg Status:</strong></td>
                                    <td><span class="status-badge active">Compatible</span></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        <?php
    }

    public function save_modules_via_ajax() {
        check_ajax_referer( 'rawnaq_admin_nonce', 'security' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( esc_html__( 'Unauthorized user request.', 'rawnaq' ) );
        }

        // Do NOT use sanitize_text_field() on the serialized blob — it strips %XX
        // from jQuery.serialize() (e.g. %5B/%5D) and breaks parse_str(). Values are
        // whitelisted and sanitized below.
        $form_data = [];
        if ( isset( $_POST['form_data'] ) && is_string( $_POST['form_data'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Whitelist-sanitized after parse_str().
            $raw = wp_unslash( $_POST['form_data'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Whitelist-sanitized after parse_str().
            parse_str( $raw, $form_data );
        }

        $posted  = ( isset( $form_data['modules'] ) && is_array( $form_data['modules'] ) )
            ? $form_data['modules']
            : [];
        $allowed = function_exists( 'rawnaq_default_modules' )
            ? array_keys( rawnaq_default_modules() )
            : [ 'hub-diagram', 'tilt-card', 'scroll-timeline', 'floating-dock', 'flow-chart', 'scroll-progress-toc' ];

        $sanitized_modules = [];
        foreach ( $allowed as $slug ) {
            $sanitized_modules[ $slug ] = ! empty( $posted[ $slug ] ) ? '1' : '0';
        }

        $settings            = get_option( 'rawnaq_settings', [] );
        if ( ! is_array( $settings ) ) {
            $settings = [];
        }
        $settings['modules'] = $sanitized_modules;
        update_option( 'rawnaq_settings', $settings );

        wp_send_json_success( esc_html__( 'Settings saved successfully!', 'rawnaq' ) );
    }
}
