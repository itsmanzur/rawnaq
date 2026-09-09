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
            // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- intentional reorder of our own admin submenu so Dashboard sits first.
            $submenu['rawnaq'] = array_merge( [ $dash ], $rest );
        }
    }

    public function enqueue_admin_assets( $hook ) {
        if ( 'toplevel_page_rawnaq' !== $hook ) {
            return;
        }

        $css_ver = file_exists( RAWNAQ_PATH . 'assets/css/admin.css' ) ? filemtime( RAWNAQ_PATH . 'assets/css/admin.css' ) : RAWNAQ_VERSION;
        $js_ver  = file_exists( RAWNAQ_PATH . 'assets/js/admin.js' ) ? filemtime( RAWNAQ_PATH . 'assets/js/admin.js' ) : RAWNAQ_VERSION;

        wp_enqueue_style(
            'rawnaq-admin-css',
            rawnaq_asset_url( 'css/admin.css' ),
            [],
            $css_ver
        );

        wp_enqueue_script(
            'rawnaq-admin-js',
            rawnaq_asset_url( 'js/admin.js' ),
            [ 'jquery' ],
            $js_ver,
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
                        <a href="https://github.com/itsmanzur/rawnaq/issues" target="_blank" rel="noopener noreferrer" class="doc-btn"><?php esc_html_e( 'Open Support Ticket', 'rawnaq' ); ?></a>
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

								/**
								 * Filter the cards shown in Elements Manager.
								 *
								 * Companion plugins should also add the matching slug through
								 * the rawnaq_default_modules filter so the toggle is persisted.
								 *
								 * @param array<int, array<string, string>> $module_defs Module card definitions.
								 */
								$module_defs = apply_filters( 'rawnaq_module_definitions', $module_defs );

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
												<?php elseif ( 'actions' === $mod['icon'] ) : ?>
													<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M9.5 14.5 14.5 9"/><path d="M7.2 17.8 5.8 19.2a3.5 3.5 0 0 1-5-5l3-3a3.5 3.5 0 0 1 5 0" transform="translate(2 -2)"/><path d="m16.8 6.2 1.4-1.4a3.5 3.5 0 0 1 5 5l-3 3a3.5 3.5 0 0 1-5 0" transform="translate(-2 2)"/><path d="M17 14v5M14.5 16.5h5"/></svg>
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
                                $mc_key      = sanitize_text_field( $sf_settings['mailchimp_api_key'] ?? '' );
                                $hs_portal   = sanitize_text_field( $sf_settings['hubspot_portal_id'] ?? '' );
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
                                <p>
                                    <label for="rawnaq-mc-key"><strong><?php esc_html_e( 'Mailchimp API key', 'rawnaq' ); ?></strong></label><br />
                                    <input type="password" class="regular-text" id="rawnaq-mc-key" name="mailchimp_api_key" value="<?php echo esc_attr( $mc_key ); ?>" autocomplete="off" placeholder="xxxxxxxx-us21" />
                                    <span class="description"><?php esc_html_e( 'Used by Smart Form CRM = Mailchimp. Set the Audience ID per form.', 'rawnaq' ); ?></span>
                                </p>
                                <p>
                                    <label for="rawnaq-hs-portal"><strong><?php esc_html_e( 'HubSpot Portal ID', 'rawnaq' ); ?></strong></label><br />
                                    <input type="text" class="regular-text" id="rawnaq-hs-portal" name="hubspot_portal_id" value="<?php echo esc_attr( $hs_portal ); ?>" placeholder="1234567" />
                                    <span class="description"><?php esc_html_e( 'Used by Smart Form CRM = HubSpot. Set the Form GUID per form.', 'rawnaq' ); ?></span>
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
                        <div class="rawnaq-docs-header-bar">
                            <div class="rawnaq-docs-intro">
                                <h2><?php esc_html_e( 'Documentation & Usage Guide', 'rawnaq' ); ?></h2>
                                <p class="section-desc"><?php esc_html_e( 'Interactive reference guides, ready-to-copy shortcodes, and dynamic triggers for all modules.', 'rawnaq' ); ?></p>
                            </div>
                            <div class="rawnaq-docs-actions">
                                <div class="rawnaq-docs-search-wrap">
                                    <span class="dashicons dashicons-search rawnaq-docs-search-icon"></span>
                                    <input type="text" id="rawnaq-docs-search" class="rawnaq-docs-search-input" placeholder="<?php esc_attr_e( 'Search guides, shortcodes, attributes...', 'rawnaq' ); ?>" autocomplete="off">
                                    <button type="button" id="rawnaq-docs-search-clear" class="rawnaq-docs-search-clear" title="Clear search">&times;</button>
                                </div>
                                <button type="button" id="btn-toggle-all-docs" class="btn-toggle-docs">
                                    <span class="dashicons dashicons-menu-alt3"></span>
                                    <span class="toggle-text"><?php esc_html_e( 'Expand All', 'rawnaq' ); ?></span>
                                </button>
                            </div>
                        </div>

                        <div class="rawnaq-docs-filter-pills">
                            <button type="button" class="docs-filter-btn active" data-filter="all">🌟 <?php esc_html_e( 'All Guides', 'rawnaq' ); ?> <span class="filter-count">9</span></button>
                            <button type="button" class="docs-filter-btn" data-filter="pro">⚡ <?php esc_html_e( 'Pro Solutions', 'rawnaq' ); ?> <span class="filter-count">2</span></button>
                            <button type="button" class="docs-filter-btn" data-filter="snippets">📋 <?php esc_html_e( 'Quick Cheat Sheet', 'rawnaq' ); ?> <span class="filter-count">1</span></button>
                            <button type="button" class="docs-filter-btn" data-filter="free">🧩 <?php esc_html_e( 'Core Widgets', 'rawnaq' ); ?> <span class="filter-count">6</span></button>
                        </div>

                        <!-- CHEAT SHEET & DYNAMIC HOOKS -->
                        <div class="rawnaq-doc-card is-pro is-expanded" data-category="snippets">
                            <div class="rawnaq-doc-card-header">
                                <div class="header-left">
                                    <div class="doc-icon-box" style="background: linear-gradient(135deg, #6366f1, #4338ca); color: #fff;">
                                        <span class="dashicons dashicons-editor-code"></span>
                                    </div>
                                    <div class="header-titles">
                                        <h3><?php esc_html_e( 'Quick Cheat Sheet & Dynamic Triggers', 'rawnaq' ); ?> <span class="rawnaq-doc-badge badge-cheat"><?php esc_html_e( 'Cheat Sheet', 'rawnaq' ); ?></span></h3>
                                        <p class="header-sub"><?php esc_html_e( 'Instant 1-click copy shortcodes, modal trigger anchors, and dynamic context prefill attributes.', 'rawnaq' ); ?></p>
                                    </div>
                                </div>
                                <span class="dashicons dashicons-arrow-down-alt2 rawnaq-card-toggle-icon"></span>
                            </div>
                            <div class="rawnaq-doc-card-body">
                                <h4><?php esc_html_e( '1. Dynamic Post & External Button Triggers for Get Quote Modal', 'rawnaq' ); ?></h4>
                                <p><?php esc_html_e( 'Open the Get Quote modal from ANY button, menu item, or link on your site (including Elementor Loop Grids, Archive Cards, and Single Post templates):', 'rawnaq' ); ?></p>
                                <table class="rawnaq-snippet-table">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e( 'Trigger Method', 'rawnaq' ); ?></th>
                                            <th><?php esc_html_e( 'Code / Snippet (Click to Copy)', 'rawnaq' ); ?></th>
                                            <th><?php esc_html_e( 'How to Use', 'rawnaq' ); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Anchor Link (URL)</strong></td>
                                            <td><span class="rawnaq-copy-badge" data-copy="#rawnaq-get-quote">#rawnaq-get-quote <span class="dashicons dashicons-admin-page"></span></span></td>
                                            <td>Set as the link/URL of any button, menu item, or card.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>CSS Class</strong></td>
                                            <td><span class="rawnaq-copy-badge" data-copy="rawnaq-gq-open-modal">rawnaq-gq-open-modal <span class="dashicons dashicons-admin-page"></span></span></td>
                                            <td>Add this CSS class to any Elementor button or HTML element.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Data Attribute</strong></td>
                                            <td><span class="rawnaq-copy-badge" data-copy='data-rawnaq-open-quote="1"'>data-rawnaq-open-quote="1" <span class="dashicons dashicons-admin-page"></span></span></td>
                                            <td>Add custom HTML attribute to any interactive tag.</td>
                                        </tr>
                                    </tbody>
                                </table>

                                <h4><?php esc_html_e( '2. Dynamic Context Prefilling Attributes (For Posts, Portfolios & Services)', 'rawnaq' ); ?></h4>
                                <p><?php esc_html_e( 'Attach these data attributes to your trigger button or card to auto-select services, fill project names, and preset quantities inside the modal:', 'rawnaq' ); ?></p>
                                <table class="rawnaq-snippet-table">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e( 'Attribute', 'rawnaq' ); ?></th>
                                            <th><?php esc_html_e( 'Example Code', 'rawnaq' ); ?></th>
                                            <th><?php esc_html_e( 'Prefill Action in Modal', 'rawnaq' ); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Project / Post Title</strong></td>
                                            <td><span class="rawnaq-copy-badge" data-copy='data-gq-project="{post_title}"'>data-gq-project="Luxury Villa Palm" <span class="dashicons dashicons-admin-page"></span></span></td>
                                            <td>Pre-fills project/case-study name in client inquiry.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Service Category</strong></td>
                                            <td><span class="rawnaq-copy-badge" data-copy='data-gq-service="Interior Design"'>data-gq-service="Interior Design" <span class="dashicons dashicons-admin-page"></span></span></td>
                                            <td>Automatically selects this service in the dropdown/step.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Property / Space Type</strong></td>
                                            <td><span class="rawnaq-copy-badge" data-copy='data-gq-property="Commercial Office"'>data-gq-property="Commercial Office" <span class="dashicons dashicons-admin-page"></span></span></td>
                                            <td>Auto-selects property/space type in dropdown.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Estimated Area / Scope</strong></td>
                                            <td><span class="rawnaq-copy-badge" data-copy='data-gq-area="3500"'>data-gq-area="3500" <span class="dashicons dashicons-admin-page"></span></span></td>
                                            <td>Sets the area/scope range slider or input value.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Budget Level</strong></td>
                                            <td><span class="rawnaq-copy-badge" data-copy='data-gq-budget="Premium"'>data-gq-budget="Premium" <span class="dashicons dashicons-admin-page"></span></span></td>
                                            <td>Auto-selects budget option (Standard / Premium / Luxury).</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Quantity / Units</strong></td>
                                            <td><span class="rawnaq-copy-badge" data-copy='data-gq-quantity="4"'>data-gq-quantity="4" <span class="dashicons dashicons-admin-page"></span></span></td>
                                            <td>Sets room/unit quantity stepper count.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Custom Requirements / Notes</strong></td>
                                            <td><span class="rawnaq-copy-badge" data-copy='data-gq-notes="Inquiry from Portfolio page"'>data-gq-notes="Custom note" <span class="dashicons dashicons-admin-page"></span></span></td>
                                            <td>Pre-populates the additional notes / requirements textarea.</td>
                                        </tr>
                                    </tbody>
                                </table>

                                <h4><?php esc_html_e( '3. Ready-to-Use Shortcodes', 'rawnaq' ); ?></h4>
                                <table class="rawnaq-snippet-table">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e( 'Feature / Module', 'rawnaq' ); ?></th>
                                            <th><?php esc_html_e( 'Shortcode (Click to Copy)', 'rawnaq' ); ?></th>
                                            <th><?php esc_html_e( 'Description', 'rawnaq' ); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Pricing Calculator (Default)</strong></td>
                                            <td><span class="rawnaq-copy-badge" data-copy="[rawnaq_pricing_calculator]">[rawnaq_pricing_calculator] <span class="dashicons dashicons-admin-page"></span></span></td>
                                            <td>Embeds interactive pricing calculator with auto-calculation.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Pricing Calculator (Interior)</strong></td>
                                            <td><span class="rawnaq-copy-badge" data-copy='[rawnaq_pricing_calculator preset="interior_design"]'>[rawnaq_pricing_calculator preset="interior_design"] <span class="dashicons dashicons-admin-page"></span></span></td>
                                            <td>Embeds calculator pre-configured for Interior Design.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Pricing Calculator (Software)</strong></td>
                                            <td><span class="rawnaq-copy-badge" data-copy='[rawnaq_pricing_calculator preset="software_dev"]'>[rawnaq_pricing_calculator preset="software_dev"] <span class="dashicons dashicons-admin-page"></span></span></td>
                                            <td>Embeds calculator pre-configured for Software & Web.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Pricing Calculator (Marketing)</strong></td>
                                            <td><span class="rawnaq-copy-badge" data-copy='[rawnaq_pricing_calculator preset="marketing_seo"]'>[rawnaq_pricing_calculator preset="marketing_seo"] <span class="dashicons dashicons-admin-page"></span></span></td>
                                            <td>Embeds calculator pre-configured for Marketing & SEO.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Get Quote Modal / Form</strong></td>
                                            <td><span class="rawnaq-copy-badge" data-copy="[rawnaq_get_quote]">[rawnaq_get_quote] <span class="dashicons dashicons-admin-page"></span></span></td>
                                            <td>Embeds Get Quote wizard or trigger button anywhere.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Smart Form</strong></td>
                                            <td><span class="rawnaq-copy-badge" data-copy="[rawnaq_smart_form]">[rawnaq_smart_form] <span class="dashicons dashicons-admin-page"></span></span></td>
                                            <td>Embeds lightweight Smart Form lead capture.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- PRO MODULE 1: GET QUOTE ENTERPRISE ENGINE -->
                        <div class="rawnaq-doc-card is-pro is-expanded" data-category="pro">
                            <div class="rawnaq-doc-card-header">
                                <div class="header-left">
                                    <div class="doc-icon-box" style="background: linear-gradient(135deg, #0f766e, #115e59); color: #fff;">
                                        <span class="dashicons dashicons-clipboard"></span>
                                    </div>
                                    <div class="header-titles">
                                        <h3><?php esc_html_e( '1. Get Quote Enterprise Engine', 'rawnaq' ); ?> <span class="rawnaq-doc-badge badge-pro"><?php esc_html_e( 'Pro Enterprise', 'rawnaq' ); ?></span></h3>
                                        <p class="header-sub"><?php esc_html_e( 'Multi-step quotation wizard, Google Sheets auto-sync, CRM webhooks, WhatsApp, proposals & PDF export.', 'rawnaq' ); ?></p>
                                    </div>
                                </div>
                                <span class="dashicons dashicons-arrow-down-alt2 rawnaq-card-toggle-icon"></span>
                            </div>
                            <div class="rawnaq-doc-card-body">
                                <p><?php esc_html_e( 'The Get Quote Enterprise Engine is a high-converting, multi-step quotation system designed for agencies, studios, and high-ticket service businesses. It supports custom dynamic steps, real-time validations, Google Sheet auto-sync, CRM webhooks, WhatsApp routing, digital interactive proposals, and instant PDF invoice downloads.', 'rawnaq' ); ?></p>

                                <h4><?php esc_html_e( 'Key Capabilities & Modes:', 'rawnaq' ); ?></h4>
                                <ul>
                                    <li><strong>Form Layout Modes:</strong> Choose between a <em>Multi-Step Animated Wizard</em> (guided step-by-step experience) or a streamlined <em>Single-Step Compact Form</em> in the Elementor settings.</li>
                                    <li><strong>100% Customizable Elements:</strong> Customize step titles, step subtitles, step badge numbers/icons, progress bars, and navigation button text ("Next", "Previous", "Submit").</li>
                                    <li><strong>Dynamic Scope & Area Inputs:</strong> Allow clients to input square footage, square meters, or unit dimensions with customizable units and range slider steps.</li>
                                    <li><strong>Preferred Communication Mode & Time Slots:</strong> Client can choose their preferred contact method (WhatsApp, Phone, Email, Video Consultation) along with custom time windows (Morning, Afternoon, Evening).</li>
                                    <li><strong>File & Floor Plan Attachments:</strong> Secure drag-and-drop file uploader with extension validation and file size limits.</li>
                                </ul>

                                <h4><?php esc_html_e( 'Enterprise Integrations & Automations:', 'rawnaq' ); ?></h4>
                                <ul>
                                    <li><strong>Google Sheets 1-Click Sync:</strong> Automatically append every quote submission as a new row in your connected Google Sheet in real time.</li>
                                    <li><strong>CRM Webhook Dispatcher:</strong> Send JSON webhooks on every submission directly to Zapier, Make, HubSpot, Slack, or custom CRM webhooks.</li>
                                    <li><strong>WhatsApp Direct Routing:</strong> Notify sales agents instantly or redirect the client with a prefilled WhatsApp message containing their quote summary.</li>
                                    <li><strong>Interactive Digital Proposal Portal:</strong> Generates a shareable URL (<code>/rawnaq-proposal/?quote_id=...</code>) where clients can review scope, pricing, and approve proposals interactively.</li>
                                    <li><strong>Instant PDF Download:</strong> Built-in vector PDF generator creates branded proposals and invoices with agency logo, quote breakdown, and terms.</li>
                                </ul>

                                <div class="rawnaq-doc-tip">
                                    <strong>💡 Pro Tip:</strong> To trigger the Get Quote modal from an Elementor Loop Grid or Portfolio card, simply set the button link to <span class="rawnaq-copy-badge" data-copy="#rawnaq-get-quote">#rawnaq-get-quote</span> and add custom attributes like <span class="rawnaq-copy-badge" data-copy='data-gq-project="Villa 101"'>data-gq-project="Villa 101"</span>.
                                </div>
                            </div>
                        </div>

                        <!-- PRO MODULE 2: SERVICES & PRICING CALCULATOR -->
                        <div class="rawnaq-doc-card is-pro" data-category="pro">
                            <div class="rawnaq-doc-card-header">
                                <div class="header-left">
                                    <div class="doc-icon-box" style="background: linear-gradient(135deg, #d97706, #b45309); color: #fff;">
                                        <span class="dashicons dashicons-calculator"></span>
                                    </div>
                                    <div class="header-titles">
                                        <h3><?php esc_html_e( '2. Services & Interactive Pricing Calculator', 'rawnaq' ); ?> <span class="rawnaq-doc-badge badge-pro"><?php esc_html_e( 'Pro Enterprise', 'rawnaq' ); ?></span></h3>
                                        <p class="header-sub"><?php esc_html_e( 'Interactive estimate builder with 7 industry presets, scope sliders, promo codes, currencies & quote bridge.', 'rawnaq' ); ?></p>
                                    </div>
                                </div>
                                <span class="dashicons dashicons-arrow-down-alt2 rawnaq-card-toggle-icon"></span>
                            </div>
                            <div class="rawnaq-doc-card-body">
                                <p><?php esc_html_e( 'The Services & Pricing Calculator allows your clients to build custom project estimates in real time, choose service tiers, toggle add-on options, apply promo codes, switch currencies, and seamlessly bridge their calculation into the Get Quote system.', 'rawnaq' ); ?></p>

                                <h4><?php esc_html_e( '7 Ready-to-Use Industry Presets:', 'rawnaq' ); ?></h4>
                                <ul>
                                    <li><strong>1. Interior Design & Architecture:</strong> Calculates area (sq ft / sq m), residential vs commercial packages, 3D visualization add-ons, and site supervision.</li>
                                    <li><strong>2. Software & Web Development:</strong> Calculates app pages/screens, MVP vs Enterprise tiers, API integrations, and ongoing maintenance.</li>
                                    <li><strong>3. Marketing & SEO:</strong> Calculates monthly budget, organic search optimization, PPC management, and content creation add-ons.</li>
                                    <li><strong>4. Photography & Video Production:</strong> Calculates shooting hours, commercial licensing, drone footage, and rush color grading.</li>
                                    <li><strong>5. Construction & Renovation:</strong> Calculates floor area, structural tier levels, premium material add-ons, and permits.</li>
                                    <li><strong>6. Event & Wedding Planning:</strong> Calculates guest count, decor tier, catering coordination, and audiovisual staging.</li>
                                    <li><strong>7. Custom / General Agency:</strong> Fully customizable blank canvas for any subscription, service, or product package.</li>
                                </ul>

                                <h4><?php esc_html_e( 'Dynamic Calculation Features:', 'rawnaq' ); ?></h4>
                                <ul>
                                    <li><strong>Dynamic Scope Slider:</strong> Live slider with custom minimum, maximum, step, base price, and unit rate (Formula: <code>Total = Base + (Metric Value × Rate)</code>).</li>
                                    <li><strong>Tier Radio Cards & Add-on Repeaters:</strong> Unlimited tiers (Standard, Pro, Enterprise) and checkbox add-ons with fixed or percentage pricing.</li>
                                    <li><strong>Quantity Stepper:</strong> Incremental counter for rooms, pages, or units with instant multiplier calculation.</li>
                                    <li><strong>Urgency Speed Multiplier:</strong> Offers Standard (1.0x), Express (1.25x), and Rush (1.5x) project turnaround speeds.</li>
                                    <li><strong>Promo Code Engine:</strong> Validates coupons (e.g. <code>SAVE20</code>, <code>FLAT50</code>) with percentage or flat discounts, minimum spend rules, and expiry dates.</li>
                                    <li><strong>Multi-Currency Switcher:</strong> Real-time conversion between USD, EUR, GBP, SAR, AED, BDT, and custom currencies.</li>
                                    <li><strong>1-Click Get Quote Bridge:</strong> Clients can click "Get Official Quote" to open the quote modal with their exact calculated breakdown, tier, and total price pre-populated!</li>
                                </ul>
                            </div>
                        </div>

                        <!-- FREE MODULE 1: SMART FORM -->
                        <div class="rawnaq-doc-card" data-category="free">
                            <div class="rawnaq-doc-card-header">
                                <div class="header-left">
                                    <div class="doc-icon-box" style="background: #e6f3ef; color: var(--rq-accent-deep);">
                                        <span class="dashicons dashicons-feedback"></span>
                                    </div>
                                    <div class="header-titles">
                                        <h3><?php esc_html_e( '3. Smart Form & WhatsApp Delivery', 'rawnaq' ); ?> <span class="rawnaq-doc-badge badge-free"><?php esc_html_e( 'Core Feature', 'rawnaq' ); ?></span></h3>
                                        <p class="header-sub"><?php esc_html_e( 'Lightweight lead generation form with multi-step support, conditional logic, and WhatsApp template delivery.', 'rawnaq' ); ?></p>
                                    </div>
                                </div>
                                <span class="dashicons dashicons-arrow-down-alt2 rawnaq-card-toggle-icon"></span>
                            </div>
                            <div class="rawnaq-doc-card-body">
                                <p><?php esc_html_e( 'Smart Form is Rawnaq’s lightweight lead capture form for agency and SMB sites. Submissions are delivered via email (wp_mail) and/or open WhatsApp with a prefilled message template.', 'rawnaq' ); ?></p>
                                <h4><?php esc_html_e( 'What you can configure:', 'rawnaq' ); ?></h4>
                                <ul>
                                    <li><strong>Field Types:</strong> Text, Email, Phone, Textarea, Select dropdown, Checkboxes, Date, Number, URL, Rating, and File uploads with multi-step grouping and "Show if" conditionals.</li>
                                    <li><strong>Spam Protection:</strong> Built-in silent honeypot + time trap prevents automated bot spam without irritating CAPTCHAs.</li>
                                    <li><strong>Template Placeholders:</strong> Use <code>{field_id}</code>, <code>{pageTitle}</code>, <code>{url}</code>, <code>{date}</code>, and <code>{time}</code> in your Email and WhatsApp message templates.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- FREE MODULE 2: FLOATING DOCK MENU -->
                        <div class="rawnaq-doc-card" data-category="free">
                            <div class="rawnaq-doc-card-header">
                                <div class="header-left">
                                    <div class="doc-icon-box" style="background: #e6f3ef; color: var(--rq-accent-deep);">
                                        <span class="dashicons dashicons-format-chat"></span>
                                    </div>
                                    <div class="header-titles">
                                        <h3><?php esc_html_e( '4. Floating Dock Menu (WhatsApp Contact Mode)', 'rawnaq' ); ?> <span class="rawnaq-doc-badge badge-free"><?php esc_html_e( 'Core Feature', 'rawnaq' ); ?></span></h3>
                                        <p class="header-sub"><?php esc_html_e( 'macOS-style dock menu, multi-agent WhatsApp routing, business hours, and click counters.', 'rawnaq' ); ?></p>
                                    </div>
                                </div>
                                <span class="dashicons dashicons-arrow-down-alt2 rawnaq-card-toggle-icon"></span>
                            </div>
                            <div class="rawnaq-doc-card-body">
                                <p><?php esc_html_e( 'Combines a macOS-style icon magnification dock with WhatsApp Contact Mode for business websites.', 'rawnaq' ); ?></p>
                                <ul>
                                    <li><strong>Multi-Agent Routing:</strong> Route inquiries to different team members (e.g. Sales, Support, Consultation) based on topic or schedule.</li>
                                    <li><strong>Business Hours & Timezone:</strong> Automatically display online status or off-hours message based on your company timezone.</li>
                                    <li><strong>Desktop QR Chooser:</strong> Visitors on desktop can scan a live QR code on their mobile phone or launch WhatsApp Web instantly.</li>
                                    <li><strong>Click Analytics:</strong> Built-in click counter tracks visitor interactions across all contact channels.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- FREE MODULE 3: SCROLL SYNC TIMELINE -->
                        <div class="rawnaq-doc-card" data-category="free">
                            <div class="rawnaq-doc-card-header">
                                <div class="header-left">
                                    <div class="doc-icon-box" style="background: #e6f3ef; color: var(--rq-accent-deep);">
                                        <span class="dashicons dashicons-backup"></span>
                                    </div>
                                    <div class="header-titles">
                                        <h3><?php esc_html_e( '5. Scroll Sync Timeline', 'rawnaq' ); ?> <span class="rawnaq-doc-badge badge-free"><?php esc_html_e( 'Core Feature', 'rawnaq' ); ?></span></h3>
                                        <p class="header-sub"><?php esc_html_e( 'Native CSS animation-timeline 60fps compositor scroll animations, CPT queries & Bento sync.', 'rawnaq' ); ?></p>
                                    </div>
                                </div>
                                <span class="dashicons dashicons-arrow-down-alt2 rawnaq-card-toggle-icon"></span>
                            </div>
                            <div class="rawnaq-doc-card-body">
                                <p><?php esc_html_e( 'Flagship motion module. Uses native CSS animation-timeline: view() for compositor-thread 60fps animations with lightweight vanilla JS fallback.', 'rawnaq' ); ?></p>
                                <ul>
                                    <li>Supports horizontal & vertical layouts, RTL, media embeds, and CPT Query sources.</li>
                                    <li>Supports Named Timeline Sync with Bento Grid to highlight related cards as visitors scroll.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- FREE MODULE 4: FLOW CHART & HUB DIAGRAM -->
                        <div class="rawnaq-doc-card" data-category="free">
                            <div class="rawnaq-doc-card-header">
                                <div class="header-left">
                                    <div class="doc-icon-box" style="background: #e6f3ef; color: var(--rq-accent-deep);">
                                        <span class="dashicons dashicons-networking"></span>
                                    </div>
                                    <div class="header-titles">
                                        <h3><?php esc_html_e( '6. Flow Chart & Hub Diagram', 'rawnaq' ); ?> <span class="rawnaq-doc-badge badge-free"><?php esc_html_e( 'Core Feature', 'rawnaq' ); ?></span></h3>
                                        <p class="header-sub"><?php esc_html_e( 'Process flows, Org Trees from WP Users, 360° radial workflows, and PNG/SVG export.', 'rawnaq' ); ?></p>
                                    </div>
                                </div>
                                <span class="dashicons dashicons-arrow-down-alt2 rawnaq-card-toggle-icon"></span>
                            </div>
                            <div class="rawnaq-doc-card-body">
                                <p><?php esc_html_e( 'Interactive tree structures, organizational charts, and radial process diagrams.', 'rawnaq' ); ?></p>
                                <ul>
                                    <li><strong>Flow Chart:</strong> Supports Org Trees, Process Flows, and Freeform nodes. Can dynamically build company org charts directly from WordPress Users. Includes high-res PNG / SVG export.</li>
                                    <li><strong>Hub Diagram:</strong> Radial workflow diagram connecting spokes to a center circle with responsive auto-timeline on mobile and glowing animated particle lines.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- FREE MODULE 5: BENTO GRID, SCROLL STORY & TOC -->
                        <div class="rawnaq-doc-card" data-category="free">
                            <div class="rawnaq-doc-card-header">
                                <div class="header-left">
                                    <div class="doc-icon-box" style="background: #e6f3ef; color: var(--rq-accent-deep);">
                                        <span class="dashicons dashicons-grid-view"></span>
                                    </div>
                                    <div class="header-titles">
                                        <h3><?php esc_html_e( '7. Bento Grid, Scroll Story & Progress TOC', 'rawnaq' ); ?> <span class="rawnaq-doc-badge badge-free"><?php esc_html_e( 'Core Feature', 'rawnaq' ); ?></span></h3>
                                        <p class="header-sub"><?php esc_html_e( 'CSS Grid bento cards, Scrollytelling chapters, and auto-reading progress TOC indicator.', 'rawnaq' ); ?></p>
                                    </div>
                                </div>
                                <span class="dashicons dashicons-arrow-down-alt2 rawnaq-card-toggle-icon"></span>
                            </div>
                            <div class="rawnaq-doc-card-body">
                                <p><?php esc_html_e( 'Advanced layout and interactive storytelling components.', 'rawnaq' ); ?></p>
                                <ul>
                                    <li><strong>Bento Grid:</strong> Modern CSS Grid marketing layouts with Elementor canvas drag-resize handles and Gutenberg InnerBlocks.</li>
                                    <li><strong>Scroll Story Chapters:</strong> Scrollytelling layout with sticky pinned media that swaps dynamically as each chapter enters view.</li>
                                    <li><strong>Scroll Progress + TOC:</strong> Live reading progress bar/ring with automatic H2–H4 heading index, search filter, and mobile floating action button.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- FREE MODULE 6: CASE STUDY GRID -->
                        <div class="rawnaq-doc-card" data-category="free">
                            <div class="rawnaq-doc-card-header">
                                <div class="header-left">
                                    <div class="doc-icon-box" style="background: #e6f3ef; color: var(--rq-accent-deep);">
                                        <span class="dashicons dashicons-portfolio"></span>
                                    </div>
                                    <div class="header-titles">
                                        <h3><?php esc_html_e( '8. Case-Study Grid (Portfolio Engine)', 'rawnaq' ); ?> <span class="rawnaq-doc-badge badge-free"><?php esc_html_e( 'Core Feature', 'rawnaq' ); ?></span></h3>
                                        <p class="header-sub"><?php esc_html_e( 'Portfolio showcase with lightbox gallery, sector filters, NDA confidential masking & quote triggers.', 'rawnaq' ); ?></p>
                                    </div>
                                </div>
                                <span class="dashicons dashicons-arrow-down-alt2 rawnaq-card-toggle-icon"></span>
                            </div>
                            <div class="rawnaq-doc-card-body">
                                <p><?php esc_html_e( 'Structured portfolio showcase for AEC firms, agencies, and studios. Source projects from the built-in Case Studies CPT or manual repeaters.', 'rawnaq' ); ?></p>
                                <ul>
                                    <li>Supports multi-image lightbox sliders, sector & service filters, and client-side AJAX load more.</li>
                                    <li>NDA confidentiality toggles allow hiding sensitive budgets and client names on cards while keeping metadata in editor.</li>
                                    <li>Integrated "Discuss This Project" button that prefills project name and details directly into Get Quote modal or Smart Form.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- NO RESULTS EMPTY STATE -->
                        <div id="rawnaq-docs-no-results" class="rawnaq-no-results">
                            <h4><?php esc_html_e( 'No matching documentation found', 'rawnaq' ); ?></h4>
                            <p><?php esc_html_e( 'Try searching with different keywords like "calculator", "quote", "shortcode", or clear your filter.', 'rawnaq' ); ?></p>
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
        $settings['mailchimp_api_key']    = isset( $form_data['mailchimp_api_key'] ) ? sanitize_text_field( $form_data['mailchimp_api_key'] ) : '';
        $settings['hubspot_portal_id']    = isset( $form_data['hubspot_portal_id'] ) ? sanitize_text_field( $form_data['hubspot_portal_id'] ) : '';
        $settings['sf_max_upload_mb']     = isset( $form_data['sf_max_upload_mb'] ) ? max( 1, min( 25, absint( $form_data['sf_max_upload_mb'] ) ) ) : 5;
        update_option( 'rawnaq_settings', $settings );

        wp_send_json_success( esc_html__( 'Settings saved successfully!', 'rawnaq' ) );
    }
}
