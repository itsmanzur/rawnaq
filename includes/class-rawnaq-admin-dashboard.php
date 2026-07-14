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
        $settings = get_option( 'rawnaq_settings', [] );
        $modules  = isset( $settings['modules'] ) ? $settings['modules'] : [
            'hub-diagram'     => '1',
            'tilt-card'       => '1',
            'scroll-timeline' => '1',
            'floating-dock'   => '1',
        ];

        // Check compatibility
        $php_version = phpversion();
        $wp_version  = get_bloginfo( 'version' );
        $elementor_active = did_action( 'elementor/loaded' ) ? 'Active' : 'Inactive';
        ?>
        <div class="rawnaq-admin-wrap">
            <!-- Header Banner -->
            <header class="rawnaq-header">
                <div class="rawnaq-logo">
                    <span class="logo-icon">&#x1F9B8;</span>
                    <div>
                        <h1>Rawnaq</h1>
                        <p>Performance Addons for WordPress Page Builders</p>
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
                        <h2>Elements Manager</h2>
                        <p class="section-desc">Toggle the widgets on/off. Disabled elements will completely prevent their assets from loading on your frontend, maximizing page load speeds.</p>

                        <form id="rawnaq-modules-form">
                            <div class="modules-list">
                                <!-- Hub Diagram Card -->
                                <div class="module-card">
                                    <div class="module-info">
                                        <span class="module-badge">Diagram</span>
                                        <h4>Hub Diagram</h4>
                                        <p>Interactive, responsive, and editable workflow chart connecting spokes to a center circle.</p>
                                    </div>
                                    <div class="module-toggle">
                                        <label class="switch">
                                            <input type="checkbox" name="modules[hub-diagram]" value="1" <?php checked( isset( $modules['hub-diagram'] ) && $modules['hub-diagram'] === '1' ); ?>>
                                            <span class="slider round"></span>
                                        </label>
                                    </div>
                                </div>

                                <!-- 3D Tilt Card Card -->
                                <div class="module-card">
                                    <div class="module-info">
                                        <span class="module-badge">Visuals</span>
                                        <h4>3D Tilt Card</h4>
                                        <p>Stunning 3D perspective mouse-tilt card with dynamic light particle glow and parallax layers.</p>
                                    </div>
                                    <div class="module-toggle">
                                        <label class="switch">
                                            <input type="checkbox" name="modules[tilt-card]" value="1" <?php checked( isset( $modules['tilt-card'] ) && $modules['tilt-card'] === '1' ); ?>>
                                            <span class="slider round"></span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Scroll Timeline Card -->
                                <div class="module-card">
                                    <div class="module-info">
                                        <span class="module-badge">Layouts</span>
                                        <h4>Scroll Sync Timeline</h4>
                                        <p>Vertical progress timeline where the center connector line fills up dynamically on scroll.</p>
                                    </div>
                                    <div class="module-toggle">
                                        <label class="switch">
                                            <input type="checkbox" name="modules[scroll-timeline]" value="1" <?php checked( isset( $modules['scroll-timeline'] ) && $modules['scroll-timeline'] === '1' ); ?>>
                                            <span class="slider round"></span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Floating Dock Card -->
                                <div class="module-card">
                                    <div class="module-info">
                                        <span class="module-badge">Navigation</span>
                                        <h4>Floating Dock Menu</h4>
                                        <p>macOS style floating action dock menu with cursor proximity magnification effect.</p>
                                    </div>
                                    <div class="module-toggle">
                                        <label class="switch">
                                            <input type="checkbox" name="modules[floating-dock]" value="1" <?php checked( isset( $modules['floating-dock'] ) && $modules['floating-dock'] === '1' ); ?>>
                                            <span class="slider round"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-footer">
                                <button type="submit" class="btn btn-save" id="btn-save-settings">Save Changes</button>
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

        $form_data = [];
        if ( isset( $_POST['form_data'] ) ) {
            parse_str( sanitize_text_field( wp_unslash( $_POST['form_data'] ) ), $form_data );
        }

        $modules = isset( $form_data['modules'] ) ? $form_data['modules'] : [];
        $settings = get_option( 'rawnaq_settings', [] );

        $sanitized_modules = [];
        if ( is_array( $modules ) ) {
            foreach ( $modules as $key => $val ) {
                $sanitized_modules[ sanitize_key( $key ) ] = '1';
            }
        }

        $settings['modules'] = $sanitized_modules;
        update_option( 'rawnaq_settings', $settings );

        wp_send_json_success( esc_html__( 'Settings saved successfully!', 'rawnaq' ) );
    }
}
