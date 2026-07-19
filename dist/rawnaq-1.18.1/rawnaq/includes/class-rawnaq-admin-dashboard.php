<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rawnaq_Admin_Dashboard {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu_page' ], 9 );
        add_action( 'admin_menu', [ $this, 'prioritize_dashboard_submenu' ], 999 );
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

        // Keep Dashboard as first submenu so the top-level "Rawnaq" link
        // opens the settings UI instead of the first CPT (Case Studies).
        add_submenu_page(
            'rawnaq',
            esc_html__( 'Dashboard', 'rawnaq' ),
            esc_html__( 'Dashboard', 'rawnaq' ),
            'manage_options',
            'rawnaq',
            [ $this, 'render_dashboard' ]
        );
    }

    /**
     * Ensure Dashboard stays the first submenu under Rawnaq.
     */
    public function prioritize_dashboard_submenu() {
        global $submenu;
        if ( empty( $submenu['rawnaq'] ) || ! is_array( $submenu['rawnaq'] ) ) {
            return;
        }
        $dash = null;
        $rest = [];
        foreach ( $submenu['rawnaq'] as $item ) {
            if ( isset( $item[2] ) && 'rawnaq' === $item[2] ) {
                $dash = $item;
            } else {
                $rest[] = $item;
            }
        }
        if ( $dash ) {
            $submenu['rawnaq'] = array_merge( [ $dash ], $rest );
        }
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
        $settings = get_option( 'rawnaq_settings', [] );
        if ( ! is_array( $settings ) ) {
            $settings = [];
        }
        $clicks  = function_exists( 'rawnaq_dock_get_clicks' ) ? rawnaq_dock_get_clicks() : [];

        // Check compatibility
        $php_version = phpversion();
        $wp_version  = get_bloginfo( 'version' );
        $elementor_ok     = (bool) did_action( 'elementor/loaded' );
        $elementor_active = $elementor_ok
            ? __( 'Active', 'rawnaq' )
            : __( 'Inactive', 'rawnaq' );
        $clicks_updated = ! empty( $clicks['updated'] ) ? (int) $clicks['updated'] : 0;
        ?>
        <div class="rawnaq-admin-wrap">
            <!-- Header Banner -->
            <header class="rawnaq-header">
                <div class="rawnaq-logo">
                    <span class="logo-mark" aria-hidden="true">R</span>
                    <div>
                        <h1><?php esc_html_e( 'Rawnaq', 'rawnaq' ); ?></h1>
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
                            <span class="nav-icon" aria-hidden="true">&#x1F3E0;</span> <?php esc_html_e( 'Dashboard', 'rawnaq' ); ?>
                        </a>
                        <a href="#modules" class="nav-item" data-tab="modules">
                            <span class="nav-icon" aria-hidden="true">&#x9881;</span> <?php esc_html_e( 'Elements Manager', 'rawnaq' ); ?>
                        </a>
                        <a href="#docs" class="nav-item" data-tab="docs">
                            <span class="nav-icon" aria-hidden="true">&#x1F4D6;</span> <?php esc_html_e( 'Documentation', 'rawnaq' ); ?>
                        </a>
                        <a href="#dock-stats" class="nav-item" data-tab="dock-stats">
                            <span class="nav-icon" aria-hidden="true">&#x1F4CA;</span> <?php esc_html_e( 'Dock Stats', 'rawnaq' ); ?>
                        </a>
                        <a href="#system" class="nav-item" data-tab="system">
                            <span class="nav-icon" aria-hidden="true">&#x1F4BB;</span> <?php esc_html_e( 'System Info', 'rawnaq' ); ?>
                        </a>
                    </nav>
                    <div class="sidebar-footer">
                        <p><?php esc_html_e( 'Need help?', 'rawnaq' ); ?></p>
                        <a href="https://github.com/Rawnaq/rawnaq/issues" target="_blank" rel="noopener noreferrer" class="doc-btn"><?php esc_html_e( 'Open Support Ticket', 'rawnaq' ); ?></a>
                    </div>
                </aside>

                <!-- Content Area -->
                <main class="rawnaq-content">
                    <!-- TAB 1: WELCOME -->
                    <div id="tab-welcome" class="tab-panel active">
                        <div class="welcome-banner">
                            <h2><?php esc_html_e( 'Welcome to Rawnaq!', 'rawnaq' ); ?></h2>
                            <p><?php esc_html_e( 'An elite, speed-first modular library. Enable only the elements you need and keep your site running at lightning speeds.', 'rawnaq' ); ?></p>
                        </div>

                        <div class="grid-2">
                            <div class="rawnaq-card">
                                <h3><?php esc_html_e( 'Quick Start', 'rawnaq' ); ?></h3>
                                <p><?php esc_html_e( 'To use our widgets, search for any active element in Elementor or Gutenberg: Hub Diagram, 3D Tilt Card, Scroll Sync Timeline, Floating Dock, Flow Chart, Scroll Progress + TOC, Bento Grid, Scroll Story Chapters, Smart Form, or Case-Study Grid.', 'rawnaq' ); ?></p>
                                <a href="#modules" class="btn btn-primary trigger-tab-change" data-target="modules"><?php esc_html_e( 'Manage Elements', 'rawnaq' ); ?></a>
                            </div>
                            <div class="rawnaq-card">
                                <h3><?php esc_html_e( 'Speed First Principle', 'rawnaq' ); ?></h3>
                                <p><?php esc_html_e( 'We do not load external libraries, frameworks, or jQuery on the frontend for our elements. Everything is written in clean vanilla code, loading dynamically only where used.', 'rawnaq' ); ?></p>
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
                                    [
                                        'key'         => 'bento-grid',
                                        'badge'       => __( 'Layouts', 'rawnaq' ),
                                        'tone'        => 'tone-layouts',
                                        'title'       => __( 'Bento Grid', 'rawnaq' ),
                                        'desc'        => __( 'Apple-style asymmetric CSS grid with presets, stats, image & featured cells.', 'rawnaq' ),
                                        'icon'        => 'bento',
                                    ],
                                    [
                                        'key'         => 'scroll-story',
                                        'badge'       => __( 'Layouts', 'rawnaq' ),
                                        'tone'        => 'tone-layouts',
                                        'title'       => __( 'Scroll Story Chapters', 'rawnaq' ),
                                        'desc'        => __( 'Scrollytelling: pinned media column that swaps as chapter text scrolls into view.', 'rawnaq' ),
                                        'icon'        => 'story',
                                    ],
                                    [
                                        'key'         => 'smart-form',
                                        'badge'       => __( 'Conversion', 'rawnaq' ),
                                        'tone'        => 'tone-nav',
                                        'title'       => __( 'Smart Form', 'rawnaq' ),
                                        'desc'        => __( 'Lead form with email + WhatsApp redirect (Phase 1), honeypot spam guard, admin logs.', 'rawnaq' ),
                                        'icon'        => 'form',
                                    ],
                                    [
                                        'key'         => 'case-study-grid',
                                        'badge'       => __( 'Portfolio', 'rawnaq' ),
                                        'tone'        => 'tone-layouts',
                                        'title'       => __( 'Case-Study Grid', 'rawnaq' ),
                                        'desc'        => __( 'CPT/manual portfolio with Discuss CTA (Form/Dock) and Story/Timeline highlight sync.', 'rawnaq' ),
                                        'icon'        => 'cases',
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
                                                <?php elseif ( 'bento' === $mod['icon'] ) : ?>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="3" width="10" height="10" rx="2"/><rect x="15" y="3" width="6" height="4" rx="1.5"/><rect x="15" y="9" width="6" height="4" rx="1.5"/><rect x="3" y="15" width="6" height="6" rx="1.5"/><rect x="11" y="15" width="10" height="6" rx="1.5"/></svg>
                                                <?php elseif ( 'story' === $mod['icon'] ) : ?>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="4" width="8" height="16" rx="2"/><path d="M14 7h7M14 12h7M14 17h5"/></svg>
                                                <?php elseif ( 'form' === $mod['icon'] ) : ?>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
                                                <?php elseif ( 'cases' === $mod['icon'] ) : ?>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="4" width="8" height="7" rx="1.5"/><rect x="13" y="4" width="8" height="10" rx="1.5"/><rect x="3" y="13" width="8" height="7" rx="1.5"/><rect x="13" y="16" width="8" height="4" rx="1.5"/></svg>
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
                                                <span><?php esc_html_e( 'Elementor', 'rawnaq' ); ?></span>
                                                <span><?php esc_html_e( 'Gutenberg', 'rawnaq' ); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="rawnaq-card" style="margin: 24px 0;">
                                <h3 style="margin-top:0;"><?php esc_html_e( 'Smart Form & shared WhatsApp', 'rawnaq' ); ?></h3>
                                <p class="section-desc"><?php esc_html_e( 'Site-wide defaults used by Smart Form and Floating Dock (when an agent number is blank).', 'rawnaq' ); ?></p>
                                <?php
                                $sf_settings = is_array( $settings ) ? $settings : [];
                                $default_wa  = sanitize_text_field( $sf_settings['default_wa_number'] ?? '' );
                                $rc_site     = sanitize_text_field( $sf_settings['recaptcha_site_key'] ?? '' );
                                $rc_secret   = sanitize_text_field( $sf_settings['recaptcha_secret_key'] ?? '' );
                                $max_up      = isset( $sf_settings['sf_max_upload_mb'] ) ? absint( $sf_settings['sf_max_upload_mb'] ) : 5;
                                if ( $max_up < 1 ) {
                                    $max_up = 5;
                                }
                                ?>
                                <p>
                                    <label for="rawnaq-default-wa"><strong><?php esc_html_e( 'Default WhatsApp number', 'rawnaq' ); ?></strong></label><br />
                                    <input type="text" class="regular-text" id="rawnaq-default-wa" name="default_wa_number" value="<?php echo esc_attr( $default_wa ); ?>" placeholder="8801XXXXXXXXX" />
                                </p>
                                <p>
                                    <label for="rawnaq-sf-max-mb"><strong><?php esc_html_e( 'Smart Form max upload (MB)', 'rawnaq' ); ?></strong></label><br />
                                    <input type="number" min="1" max="25" id="rawnaq-sf-max-mb" name="sf_max_upload_mb" value="<?php echo esc_attr( (string) $max_up ); ?>" />
                                </p>
                                <p>
                                    <label for="rawnaq-rc-site"><strong><?php esc_html_e( 'reCAPTCHA v3 site key', 'rawnaq' ); ?></strong></label><br />
                                    <input type="text" class="regular-text" id="rawnaq-rc-site" name="recaptcha_site_key" value="<?php echo esc_attr( $rc_site ); ?>" />
                                </p>
                                <p>
                                    <label for="rawnaq-rc-secret"><strong><?php esc_html_e( 'reCAPTCHA v3 secret key', 'rawnaq' ); ?></strong></label><br />
                                    <input type="password" class="regular-text" id="rawnaq-rc-secret" name="recaptcha_secret_key" value="<?php echo esc_attr( $rc_secret ); ?>" autocomplete="off" />
                                </p>
                            </div>

                            <div class="form-footer modules-footer">
                                <button type="submit" class="btn btn-save" id="btn-save-settings"><?php esc_html_e( 'Save Changes', 'rawnaq' ); ?></button>
                                <span class="save-status" id="save-status-msg"></span>
                            </div>
                        </form>
                    </div>

                    <!-- TAB 3: DOCUMENTATION -->
                    <div id="tab-docs" class="tab-panel">
                        <h2><?php esc_html_e( 'Documentation & Usage Guide', 'rawnaq' ); ?></h2>
                        <p class="section-desc"><?php esc_html_e( 'Detailed usage guides on how to configure and deploy the active widgets.', 'rawnaq' ); ?></p>

                        <div class="rawnaq-doc-card" style="margin-bottom: 24px;">
                            <h3>1. Hub Diagram</h3>
                            <p>A highly performant radial workflow diagram connecting spokes to a center circle. Features responsive auto-timeline columns on mobile and animated glow particle flows. Pick icons with the Elementor Icons control (Font Awesome / Dashicons). Frontend <strong>PNG / SVG export</strong> for proposals.</p>
                            <h4>Usage:</h4>
                            <ul>
                                <li><strong>Elementor:</strong> Search for "Hub Diagram" widget, drag and edit text details. In the Style tab, check the Layout option to switch from horizontal rows to 360° Radial mode, or enable Glow Flow lines.</li>
                                <li><strong>Gutenberg:</strong> In insert bar search "Hub Diagram (Rawnaq)", insert block, and use sidebar settings controls.</li>
                            </ul>
                        </div>

                        <div class="rawnaq-doc-card" style="margin-bottom: 24px;">
                            <h3>2. 3D Tilt Card</h3>
                            <p>A lightweight taste card: mouse tilt / glare with reduced-motion and coarse-pointer respect. Kept intentionally small — not a deep media-card suite.</p>
                            <h4>Usage:</h4>
                            <ul>
                                <li><strong>Elementor:</strong> Drag "3D Tilt Card" widget, choose an icon from the Icons picker, write title text, select redirect URL, and adjust the Max Tilt Intensity slider in settings.</li>
                                <li><strong>Gutenberg:</strong> Search and insert "3D Tilt Card" block. Settings sidebar contains title, description, link options, and tilt intensity controls.</li>
                            </ul>
                        </div>

                        <div class="rawnaq-doc-card" style="margin-bottom: 24px;">
                            <h3>3. Scroll Sync Timeline</h3>
                            <p><strong>Flagship motion module.</strong> On modern browsers the line fill and step reveals use native CSS <code>animation-timeline</code> / <code>view()</code> (compositor-thread, near zero JS). Older browsers get a lightweight vanilla JS fallback. Supports horizontal and vertical layouts, RTL, media embeds, CPT query source, Load More / AJAX, Named Timeline Sync with Bento, agency presets (Company Story / Changelog / Case Study), and WPML/Polylang readiness.</p>
                            <p>
                                <a class="doc-btn" href="<?php echo esc_url( RAWNAQ_URL . 'assets/demo/scroll-timeline-benchmark.html' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open CSS vs JS benchmark demo', 'rawnaq' ); ?></a>
                            </p>
                            <h4>Usage:</h4>
                            <ul>
                                <li><strong>Elementor:</strong> Drag "Scroll Sync Timeline" widget. Add steps in the repeater or switch to Query mode. Look for the CSS-driven badge in the editor when supported.</li>
                                <li><strong>Gutenberg:</strong> Insert "Scroll Sync Timeline" block. Configure milestones, layout, and query options in the inspector.</li>
                            </ul>
                        </div>

                        <div class="rawnaq-doc-card" style="margin-bottom: 24px;">
                            <h3>4. Floating Dock Menu (WhatsApp Contact Mode)</h3>
                            <p>Two products in one: a classic macOS-style magnify dock, and <strong>WhatsApp Contact Mode</strong> (recommended for SMB/agency sites) with multi-agent routing, business hours / timezone, offline behaviors, desktop QR + Web chooser, page-aware message placeholders, and optional click stats.</p>
                            <h4>Usage:</h4>
                            <ul>
                                <li><strong>Elementor:</strong> Drag "Floating Dock Menu". Enable WhatsApp mode for agents and schedules, or stay in classic mode for icon links. Set position and visibility rules.</li>
                                <li><strong>Gutenberg:</strong> Insert "Floating Dock Menu" block. Configure agents/schedule JSON and position in the sidebar.</li>
                            </ul>
                        </div>

                        <div class="rawnaq-doc-card" style="margin-bottom: 24px;">
                            <h3>5. Flow Chart</h3>
                            <p>Interactive tree and process workflow charts. Supports Org tree, Process flow, and Freeform (manual X/Y %) modes; node shapes (rect / circle / hex); direction TB/LR/RL with RTL flip; zoom/pan on desktop; lazy mount for 20+ nodes. Parent cycles are auto-cleared (DFS). Optional <strong>WordPress Users</strong> data source builds an org chart from users (parent via user meta <code>rawnaq_reports_to</code>, else nest under first admin). Frontend <strong>PNG / SVG export</strong> for proposals.</p>
                            <h4>Usage:</h4>
                            <ul>
                                <li><strong>Elementor:</strong> Drag "Flow Chart". Choose Manual or WP Users source, map parent Node IDs in the repeater when manual, pick icons from the Icons control, choose shape and direction.</li>
                                <li><strong>Gutenberg:</strong> Insert "Flow Chart (Rawnaq)". Switch Nodes Source to WP Users for live org, or use the Parent dropdown for manual trees; freeform mode exposes X/Y ranges.</li>
                            </ul>
                        </div>

                        <div class="rawnaq-doc-card" style="margin-bottom: 24px;">
                            <h3>6. Scroll Progress + TOC</h3>
                            <p>Page scroll progress (bar / ring / both) plus a smart Table of Contents: auto H2–H4 or manual entries, collapse subs, optional search filter, reading time, mobile FAB, optional <strong>Attach to Floating Dock</strong>, ring size control, and optional <strong>Sync Timeline ID</strong> to show the active Scroll Sync Timeline step as “Chapter …” near the TOC.</p>
                            <h4>Usage:</h4>
                            <ul>
                                <li><strong>Elementor:</strong> Drag "Scroll Progress + TOC". Enable TOC Search Filter, Dock Attach, Sync Timeline ID, and Ring Size under content/style when needed.</li>
                                <li><strong>Gutenberg:</strong> Insert "Scroll Progress + TOC (Rawnaq)". Toggle search, dock attach, ring size, and Sync Timeline ID in the inspector.</li>
                            </ul>
                        </div>

                        <div class="rawnaq-doc-card" style="margin-bottom: 24px;">
                            <h3>7. Bento Grid</h3>
                            <p>CSS Grid marketing layouts with Apply Preset, cell types (featured, image, video, stat, text, testimonial), tablet/mobile span overrides, CTAs, Elementor canvas resize handles, and optional Sync Timeline ID to pair with Scroll Sync Timeline. In <strong>Gutenberg</strong>, cells are InnerBlocks (`Bento Cell`) so you can nest core headings, images, and buttons.</p>
                            <h4>Usage:</h4>
                            <ul>
                                <li><strong>Elementor:</strong> Drag "Bento Grid". Pick a preset to replace cells, then refine per-cell content and responsive spans. In the editor, drag the SE handle on a cell to change col/row span.</li>
                                <li><strong>Gutenberg:</strong> Insert "Bento Grid (Rawnaq)". Edit nested Bento Cell blocks on the canvas; Apply Preset reseeds cells. Legacy cellsJson still renders if no InnerBlocks content is saved.</li>
                            </ul>
                        </div>

                        <div class="rawnaq-doc-card" style="margin-bottom: 24px;">
                            <h3>8. Scroll Story Chapters</h3>
                            <p>Scrollytelling MVP: sticky pinned media that swaps as each chapter scrolls into view, progress dots, captions, optional CTAs, reduced-motion fallback. Dual Elementor + Gutenberg; assets load only when used.</p>
                            <h4>Usage:</h4>
                            <ul>
                                <li><strong>Elementor:</strong> Drag "Scroll Story Chapters". Add chapter repeater rows (title, body, image, caption, CTA). Choose media left/right and accent color.</li>
                                <li><strong>Gutenberg:</strong> Insert "Scroll Story Chapters (Rawnaq)". Edit chapters in the sidebar; preview lists chapter titles in the editor canvas.</li>
                            </ul>
                        </div>

                        <div class="rawnaq-doc-card">
                            <h3>9. Smart Form (Phase 1)</h3>

                            <h4><?php esc_html_e( 'Introduction', 'rawnaq' ); ?></h4>
                            <p><strong>Smart Form</strong> is Rawnaq’s lightweight lead / contact form for agency and SMB sites. Visitors fill a customizable set of fields; submissions go out by <strong>email</strong> (WordPress <code>wp_mail</code>) and/or open <strong>WhatsApp</strong> with a prefilled message via <code>wa.me</code>. No third-party form SaaS and no WhatsApp Business API in Phase 1 — keep the stack simple and the frontend vanilla JS.</p>
                            <p>It ships on both <strong>Elementor</strong> and <strong>Gutenberg</strong>, loads CSS/JS only when the form is on the page, and includes built-in spam guards (honeypot + time trap), optional GDPR-style consent, loading / double-submit protection, and an optional admin submission log.</p>
                            <p><em>Phase 1 note:</em> WhatsApp delivery means a browser redirect to WhatsApp Web / the app with your template text already filled in. Official Business API, CRM webhooks, and multi-step forms are out of scope for this release.</p>

                            <h4><?php esc_html_e( 'What you can configure', 'rawnaq' ); ?></h4>
                            <ul>
                                <li><strong>Fields:</strong> Text, Email, Phone, Textarea, Select, Checkbox, Date, Number, URL, Hidden, Rating, File — each with ID, label, width, optional <strong>step</strong> (multi-step), and <strong>Show if</strong> conditionals.</li>
                                <li><strong>Layout presets:</strong> Name+Email side by side, Compact lead, Full contact, Multi-step project inquiry — one-click Apply Preset.</li>
                                <li><strong>Email / WhatsApp:</strong> Templates support <code>{field_id}</code> plus <code>{pageTitle}</code>, <code>{url}</code>, <code>{siteTitle}</code>, <code>{date}</code>, <code>{time}</code>. Blank WA number uses the shared site default (Modules tab).</li>
                                <li><strong>After submit:</strong> Thank-you, redirect, or open WhatsApp.</li>
                                <li><strong>Extras:</strong> Consent, admin log + CSV/unread, file attach (size limit), optional reCAPTCHA v3, webhook/Slack.</li>
                                <li><strong>Style:</strong> Label/input/button colors, input size, radius, full-width button.</li>
                            </ul>

                            <h4><?php esc_html_e( 'How to use', 'rawnaq' ); ?></h4>
                            <ol>
                                <li>Enable the <strong>Smart Form</strong> module on the Rawnaq Modules tab (if it is not already on).</li>
                                <li><strong>Elementor:</strong> Search for <em>Smart Form</em>, drag it onto the page. Use the Fields repeater, Delivery panel, and Style tab.</li>
                                <li><strong>Gutenberg:</strong> Insert <em>Smart Form (Rawnaq)</em>. Edit fields and delivery in the block inspector; the canvas preview lists field labels.</li>
                                <li>Set at least one delivery channel (email and/or WhatsApp). For WhatsApp, enter a full international number and customize the template.</li>
                                <li>Choose <em>After submit</em>: message (default), redirect (set URL), or open WhatsApp (requires WhatsApp delivery + number).</li>
                                <li>Publish and test once as a visitor — confirm email arrives and/or WhatsApp opens with the expected text.</li>
                            </ol>

                            <h4><?php esc_html_e( 'Tips', 'rawnaq' ); ?></h4>
                            <ul>
                                <li>Field <strong>IDs</strong> must match placeholders in the WhatsApp template: a field with ID <code>phone</code> becomes <code>{phone}</code>.</li>
                                <li>Keep Field IDs short, lowercase, and unique (letters, numbers, underscores).</li>
                                <li><strong>Layout:</strong> set consecutive fields to 50% for a two-column row, or 33% for three columns. Below ~640px all fields stack full-width.</li>
                                <li>Spam: bots that fill the hidden honeypot or submit in under ~2 seconds are rejected silently on the server.</li>
                                <li>For reliable email on local / shared hosts, pair WordPress with an SMTP plugin; Smart Form uses core <code>wp_mail</code>.</li>
                                <li>Logged submissions are private (admin-only CPT) — turn logging off if you do not want stored lead copies.</li>
                            </ul>
                        </div>

                        <div class="rawnaq-doc-card">
                            <h3>10. Case-Study Grid</h3>
                            <h4><?php esc_html_e( 'Introduction', 'rawnaq' ); ?></h4>
                            <p><strong>Case-Study Grid</strong> is a structured project portfolio for AEC firms and agencies. Source projects from the <em>Case Studies</em> CPT (under Rawnaq) or enter them manually. Cards support multi-image galleries, sector / year / service filters, modal or link-out, and client-side load more for large portfolios.</p>
                            <h4><?php esc_html_e( 'How to use', 'rawnaq' ); ?></h4>
                            <ul>
                                <li><strong>CPT:</strong> Add posts under <em>Rawnaq → Case Studies</em> (sector taxonomy + meta for budget, year, gallery URLs, services). Set the widget/block source to <em>Query</em> — no repeater needed.</li>
                                <li><strong>Elementor:</strong> Drag <em>Case-Study Grid</em>. Choose Manual or Query, layouts (Bento / Uniform / Masonry), multi-filters, click action (modal / URL / both), and load-more chunk.</li>
                                <li><strong>Gutenberg:</strong> Insert <em>Case-Study Grid</em>. Manual mode uses one <em>Case-Study Card</em> InnerBlock per project (cover + gallery + link). Query mode pulls from the CPT.</li>
                                <li>Modal gallery is a multi-image slider when more than one URL is set. With click = link or both, cards open the case-study page URL.</li>
                                <li>NDA toggles hide budget and/or client on cards and in the modal while keeping the data in the editor.</li>
                            </ul>
                            <h4><?php esc_html_e( 'Discuss this project', 'rawnaq' ); ?></h4>
                            <ul>
                                <li>Modal (and link-only cards) show a <em>Discuss this project</em> button. Target: Auto (Smart Form → Dock WA), Form only, Dock only, or Off.</li>
                                <li>Form mode prefills <code>sf_message</code> (and <code>sf_project</code> / <code>sf_project_id</code> if those fields exist) then scrolls to the form. Include <code>{message}</code> in the Smart Form WhatsApp template so WA delivery keeps the context.</li>
                                <li>Dock mode calls <code>rawnaqDockOpen({ message })</code> with a one-shot message — agent templates are not permanently changed.</li>
                            </ul>
                            <h4><?php esc_html_e( 'Scroll Story / Timeline sync', 'rawnaq' ); ?></h4>
                            <ul>
                                <li>On each Story chapter or Timeline step, set <em>Case-Study project ID</em> (e.g. <code>post-123</code> for CPT posts) or slug.</li>
                                <li>As the chapter becomes active, the matching Case-Study card gets <code>.is-related</code> and scrolls into view if needed.</li>
                                <li>Query-driven Timeline steps auto-set <code>data-project-id="post-{ID}"</code> from the post ID.</li>
                            </ul>
                            <h4><?php esc_html_e( 'Static demo', 'rawnaq' ); ?></h4>
                            <p><?php esc_html_e( 'Marketing showcase of all modules (no WordPress): open assets/demo/index.html in a browser.', 'rawnaq' ); ?></p>
                        </div>
                    </div>

                    <!-- TAB: DOCK STATS -->
                    <div id="tab-dock-stats" class="tab-panel">
                        <h2><?php esc_html_e( 'Floating Dock Stats', 'rawnaq' ); ?></h2>
                        <p class="section-desc"><?php esc_html_e( 'Simple site-wide click counters for Floating Dock / WhatsApp Contact Mode. Enable tracking per widget.', 'rawnaq' ); ?></p>

                        <div class="grid-2" style="margin-bottom: 20px;">
                            <div class="rawnaq-card">
                                <h3 style="margin-top:0;"><?php esc_html_e( 'Total clicks', 'rawnaq' ); ?></h3>
                                <p class="dock-stat-big" id="dock-stat-total"><?php echo esc_html( (string) absint( $clicks['total'] ?? 0 ) ); ?></p>
                                <p class="section-desc" style="margin:0;">
                                    <?php
                                    if ( $clicks_updated ) {
                                        printf(
                                            /* translators: %s: localized datetime */
                                            esc_html__( 'Last update: %s', 'rawnaq' ),
                                            esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $clicks_updated ) )
                                        );
                                    } else {
                                        esc_html_e( 'No clicks recorded yet.', 'rawnaq' );
                                    }
                                    ?>
                                </p>
                            </div>
                            <div class="rawnaq-card">
                                <h3 style="margin-top:0;"><?php esc_html_e( 'Actions', 'rawnaq' ); ?></h3>
                                <p><?php esc_html_e( 'Reset clears all counters. This cannot be undone.', 'rawnaq' ); ?></p>
                                <button type="button" class="btn btn-save" id="btn-reset-dock-clicks"><?php esc_html_e( 'Reset Counters', 'rawnaq' ); ?></button>
                                <span class="save-status" id="dock-stats-status"></span>
                            </div>
                        </div>

                        <div class="rawnaq-card">
                            <table class="system-table" id="dock-stats-table">
                                <tr>
                                    <td><strong><?php esc_html_e( 'FAB / main button', 'rawnaq' ); ?></strong></td>
                                    <td id="dock-stat-fab"><?php echo esc_html( (string) absint( $clicks['fab'] ?? 0 ) ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Agent selected', 'rawnaq' ); ?></strong></td>
                                    <td id="dock-stat-agent"><?php echo esc_html( (string) absint( $clicks['agent'] ?? 0 ) ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'WhatsApp opened (Web / mobile)', 'rawnaq' ); ?></strong></td>
                                    <td id="dock-stat-web"><?php echo esc_html( (string) absint( $clicks['web'] ?? 0 ) ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Desktop chooser shown (QR + Web)', 'rawnaq' ); ?></strong></td>
                                    <td id="dock-stat-chooser"><?php echo esc_html( (string) absint( $clicks['chooser'] ?? 0 ) ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Secondary channel', 'rawnaq' ); ?></strong></td>
                                    <td id="dock-stat-secondary"><?php echo esc_html( (string) absint( $clicks['secondary'] ?? 0 ) ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Classic dock item', 'rawnaq' ); ?></strong></td>
                                    <td id="dock-stat-classic"><?php echo esc_html( (string) absint( $clicks['classic'] ?? 0 ) ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Offline diverted (lead / redirect)', 'rawnaq' ); ?></strong></td>
                                    <td id="dock-stat-offline"><?php echo esc_html( (string) absint( $clicks['offline'] ?? 0 ) ); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 4: SYSTEM INFO -->
                    <div id="tab-system" class="tab-panel">
                        <h2><?php esc_html_e( 'System Information', 'rawnaq' ); ?></h2>
                        <p class="section-desc"><?php esc_html_e( 'Review your site\'s parameters and configuration settings below.', 'rawnaq' ); ?></p>

                        <div class="rawnaq-card">
                            <table class="system-table">
                                <tr>
                                    <td><strong><?php esc_html_e( 'WordPress Version:', 'rawnaq' ); ?></strong></td>
                                    <td><?php echo esc_html( $wp_version ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'PHP Version:', 'rawnaq' ); ?></strong></td>
                                    <td><?php echo esc_html( $php_version ); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Elementor Status:', 'rawnaq' ); ?></strong></td>
                                    <td>
                                        <span class="status-badge <?php echo $elementor_ok ? 'active' : 'inactive'; ?>">
                                            <?php echo esc_html( $elementor_active ); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e( 'Gutenberg Status:', 'rawnaq' ); ?></strong></td>
                                    <td><span class="status-badge active"><?php esc_html_e( 'Compatible', 'rawnaq' ); ?></span></td>
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
            : [ 'hub-diagram', 'tilt-card', 'scroll-timeline', 'floating-dock', 'flow-chart', 'scroll-progress-toc', 'bento-grid', 'scroll-story', 'smart-form', 'case-study-grid' ];

        $sanitized_modules = [];
        foreach ( $allowed as $slug ) {
            $sanitized_modules[ $slug ] = ! empty( $posted[ $slug ] ) ? '1' : '0';
        }

        $settings            = get_option( 'rawnaq_settings', [] );
        if ( ! is_array( $settings ) ) {
            $settings = [];
        }
        $settings['modules'] = $sanitized_modules;
        $settings['default_wa_number']    = isset( $form_data['default_wa_number'] ) ? sanitize_text_field( $form_data['default_wa_number'] ) : '';
        $settings['recaptcha_site_key']   = isset( $form_data['recaptcha_site_key'] ) ? sanitize_text_field( $form_data['recaptcha_site_key'] ) : '';
        $settings['recaptcha_secret_key'] = isset( $form_data['recaptcha_secret_key'] ) ? sanitize_text_field( $form_data['recaptcha_secret_key'] ) : '';
        $settings['sf_max_upload_mb']     = isset( $form_data['sf_max_upload_mb'] ) ? max( 1, min( 25, absint( $form_data['sf_max_upload_mb'] ) ) ) : 5;
        update_option( 'rawnaq_settings', $settings );

        wp_send_json_success( esc_html__( 'Settings saved successfully!', 'rawnaq' ) );
    }
}
