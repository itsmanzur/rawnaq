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
        add_action( 'wp_ajax_rawnaq_smart_form_submit', [ $this, 'ajax_smart_form_submit' ] );
        add_action( 'wp_ajax_nopriv_rawnaq_smart_form_submit', [ $this, 'ajax_smart_form_submit' ] );
        if ( function_exists( 'rawnaq_case_study_ajax_query' ) ) {
            add_action( 'wp_ajax_rawnaq_cs_query', 'rawnaq_case_study_ajax_query' );
            add_action( 'wp_ajax_nopriv_rawnaq_cs_query', 'rawnaq_case_study_ajax_query' );
        }
        add_action( 'init', [ $this, 'maybe_register_smart_form_cpt' ] );
    }

    /**
     * Register form submission CPT when Smart Form module is on.
     */
    public function maybe_register_smart_form_cpt() {
        if ( function_exists( 'rawnaq_is_module_enabled' ) && rawnaq_is_module_enabled( 'smart-form' )
            && function_exists( 'rawnaq_smart_form_register_cpt' ) ) {
            rawnaq_smart_form_register_cpt();
            if ( is_admin() && class_exists( 'Rawnaq_Smart_Form_Admin' ) ) {
                static $sf_admin = null;
                if ( null === $sf_admin ) {
                    $sf_admin = new Rawnaq_Smart_Form_Admin();
                }
            } elseif ( is_admin() ) {
                $admin_file = RAWNAQ_PATH . 'includes/class-rawnaq-smart-form-admin.php';
                if ( file_exists( $admin_file ) ) {
                    require_once $admin_file;
                    static $sf_admin2 = null;
                    if ( null === $sf_admin2 ) {
                        $sf_admin2 = new Rawnaq_Smart_Form_Admin();
                    }
                }
            }
        }
    }

    /**
     * Public AJAX: Smart Form submit (email, WA redirect, files, webhook, recaptcha).
     */
    public function ajax_smart_form_submit() {
        check_ajax_referer( 'rawnaq_smart_form', 'nonce' );

        $hp = isset( $_POST['rawnaq_hp'] ) ? sanitize_text_field( wp_unslash( $_POST['rawnaq_hp'] ) ) : '';
        if ( '' !== $hp ) {
            wp_send_json_error( [ 'message' => 'spam' ], 400 );
        }

        $ts  = isset( $_POST['rawnaq_ts'] ) ? absint( wp_unslash( $_POST['rawnaq_ts'] ) ) : 0;
        $now = time();
        if ( ! $ts || ( $now - $ts ) < 2 || ( $now - $ts ) > WEEK_IN_SECONDS ) {
            wp_send_json_error( [ 'message' => __( 'Please wait a moment and try again.', 'rawnaq' ) ], 400 );
        }

        // Load trusted config from server store — never trust client-posted delivery settings.
        $form_id = isset( $_POST['form_id'] ) ? sanitize_text_field( wp_unslash( $_POST['form_id'] ) ) : '';
        $cfg     = function_exists( 'rawnaq_smart_form_get_stored_config' )
            ? rawnaq_smart_form_get_stored_config( $form_id )
            : null;
        if ( ! is_array( $cfg ) || empty( $cfg['fields'] ) ) {
            wp_send_json_error( [
                'message' => __( 'Form configuration expired. Please reload the page and try again.', 'rawnaq' ),
            ], 400 );
        }

        if ( ! empty( $cfg['recaptchaEnabled'] ) ) {
            $token = isset( $_POST['rawnaq_recaptcha'] ) ? sanitize_text_field( wp_unslash( $_POST['rawnaq_recaptcha'] ) ) : '';
            if ( ! function_exists( 'rawnaq_smart_form_verify_recaptcha' ) || ! rawnaq_smart_form_verify_recaptcha( $token ) ) {
                wp_send_json_error( [ 'message' => __( 'Spam check failed. Please try again.', 'rawnaq' ) ], 400 );
            }
        }

        $fields_cfg = function_exists( 'rawnaq_smart_form_normalize_fields' )
            ? rawnaq_smart_form_normalize_fields( $cfg['fields'] ?? [] )
            : [];
        $posted = [];
        if ( isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- map_deep sanitizes every leaf.
            $posted = map_deep( wp_unslash( $_POST['fields'] ), 'sanitize_text_field' );
        }

        $values = [];
        foreach ( $fields_cfg as $field ) {
            if ( 'file' === $field['type'] ) {
                continue;
            }
            // Skip conditional fields that are empty and not visible (client may omit).
            $id  = $field['id'];
            $raw = isset( $posted[ $id ] ) ? $posted[ $id ] : '';
            if ( is_array( $raw ) ) {
                $raw = implode( ', ', $raw );
            }
            $val = sanitize_text_field( (string) $raw );
            if ( 'textarea' === $field['type'] ) {
                $val = sanitize_textarea_field( (string) $raw );
            } elseif ( 'email' === $field['type'] ) {
                $val = sanitize_email( (string) $raw );
            } elseif ( 'url' === $field['type'] ) {
                $val = esc_url_raw( (string) $raw );
            } elseif ( 'number' === $field['type'] || 'rating' === $field['type'] ) {
                $val = is_numeric( $raw ) ? (string) $raw : '';
            } elseif ( 'hidden' === $field['type'] && '' === $val ) {
                $val = sanitize_text_field( $field['defaultValue'] ?? '' );
            }

            $conditional_hidden = false;
            if ( ! empty( $field['showIf'] ) ) {
                $dep = isset( $posted[ $field['showIf'] ] ) ? sanitize_text_field( (string) $posted[ $field['showIf'] ] ) : '';
                $want = (string) ( $field['showIfValue'] ?? '' );
                if ( '' !== $want && $dep !== $want ) {
                    $conditional_hidden = true;
                }
            }
            if ( $conditional_hidden ) {
                continue;
            }

            if ( ! empty( $field['required'] ) && '' === $val ) {
                wp_send_json_error( [
                    'message' => sanitize_text_field( $cfg['errorMessage'] ?? __( 'Please fill in the required fields correctly.', 'rawnaq' ) ),
                ], 400 );
            }
            if ( 'email' === $field['type'] && $val && ! is_email( $val ) ) {
                wp_send_json_error( [
                    'message' => sanitize_text_field( $cfg['errorMessage'] ?? __( 'Please fill in the required fields correctly.', 'rawnaq' ) ),
                ], 400 );
            }
            if ( 'url' === $field['type'] && $val && ! filter_var( $val, FILTER_VALIDATE_URL ) ) {
                wp_send_json_error( [
                    'message' => sanitize_text_field( $cfg['errorMessage'] ?? __( 'Please fill in the required fields correctly.', 'rawnaq' ) ),
                ], 400 );
            }
            $values[ $id ] = $val;
        }

        $upload = function_exists( 'rawnaq_smart_form_handle_uploads' )
            ? rawnaq_smart_form_handle_uploads( $fields_cfg )
            : [ 'values' => [], 'attachments' => [], 'errors' => [] ];
        if ( ! empty( $upload['errors'] ) ) {
            wp_send_json_error( [
                'message' => __( 'File upload failed. Check size and type, then try again.', 'rawnaq' ),
            ], 400 );
        }
        foreach ( $upload['values'] as $k => $v ) {
            $values[ $k ] = $v;
        }
        $attachments = $upload['attachments'] ?? [];

        if ( ! empty( $cfg['consentEnabled'] ) ) {
            $consent = isset( $posted['consent'] ) ? sanitize_text_field( (string) $posted['consent'] ) : '';
            if ( '1' !== $consent ) {
                wp_send_json_error( [
                    'message' => __( 'Please accept the consent checkbox.', 'rawnaq' ),
                ], 400 );
            }
            $values['consent'] = 'yes';
        }

        $tpl_vals = function_exists( 'rawnaq_smart_form_template_values' )
            ? rawnaq_smart_form_template_values( $values )
            : $values;

        $mail_ok = true;
        if ( ! empty( $cfg['deliveryEmail'] ) ) {
            $to = sanitize_email( $cfg['emailTo'] ?? '' );
            if ( ! $to ) {
                $to = get_option( 'admin_email' );
            }
            $subject = sanitize_text_field( $cfg['emailSubject'] ?? '' );
            if ( ! $subject ) {
                $subject = __( 'New website inquiry', 'rawnaq' );
            }
            if ( function_exists( 'rawnaq_smart_form_fill_template' ) ) {
                $subject = rawnaq_smart_form_fill_template( $subject, $tpl_vals );
            }
            $use_html = ( ! isset( $cfg['emailHtml'] ) || ! empty( $cfg['emailHtml'] ) )
                && function_exists( 'rawnaq_smart_form_email_html' );
            if ( $use_html ) {
                $body    = rawnaq_smart_form_email_html( $values, $tpl_vals, $cfg );
                $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
            } else {
                $body_lines = [];
                foreach ( $values as $k => $v ) {
                    $body_lines[] = $k . ': ' . $v;
                }
                $body_lines[] = '';
                $body_lines[] = 'Page: ' . ( $tpl_vals['pageTitle'] ?? '' );
                $body_lines[] = 'URL: ' . ( $tpl_vals['url'] ?? '' );
                $body    = implode( "\n", $body_lines );
                $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
            }
            if ( ! empty( $values['email'] ) && is_email( $values['email'] ) ) {
                $headers[] = 'Reply-To: ' . $values['email'];
            }
            $mail_ok = wp_mail( $to, $subject, $body, $headers, $attachments );
        }

        if ( ! empty( $cfg['logSubmissions'] ) && function_exists( 'rawnaq_smart_form_log_submission' ) ) {
            rawnaq_smart_form_log_submission( $form_id, $values, $cfg );
        }

        if ( function_exists( 'rawnaq_smart_form_dispatch_crm' ) ) {
            rawnaq_smart_form_dispatch_crm( $values, $tpl_vals, $cfg, $form_id );
        }

        if ( ! empty( $cfg['webhookEnabled'] ) && ! empty( $cfg['webhookUrl'] ) && function_exists( 'rawnaq_smart_form_send_webhook' ) ) {
            rawnaq_smart_form_send_webhook(
                $cfg['webhookUrl'],
                [
                    'subject' => $cfg['emailSubject'] ?? 'Smart Form',
                    'form_id' => $form_id,
                    'fields'  => $values,
                    'page'    => [
                        'title' => $tpl_vals['pageTitle'] ?? '',
                        'url'   => $tpl_vals['url'] ?? '',
                    ],
                ]
            );
        }

        $wa_url = '';
        $wa_num = function_exists( 'rawnaq_smart_form_resolve_wa_number' )
            ? rawnaq_smart_form_resolve_wa_number( $cfg['waNumber'] ?? '' )
            : ( $cfg['waNumber'] ?? '' );
        if ( ( ! empty( $cfg['deliveryWhatsapp'] ) || ( ( $cfg['afterSubmit'] ?? '' ) === 'whatsapp' ) )
            && $wa_num
            && function_exists( 'rawnaq_smart_form_wa_url' ) ) {
            $tpl = $cfg['waTemplate'] ?? '';
            if ( ! $tpl ) {
                $tpl = "New inquiry:\nName: {name}\nPhone: {phone}\nEmail: {email}\nMessage: {message}\nPage: {pageTitle}\nURL: {url}";
            }
            $text   = function_exists( 'rawnaq_smart_form_fill_template' )
                ? rawnaq_smart_form_fill_template( $tpl, $tpl_vals )
                : $tpl;
            $wa_url = rawnaq_smart_form_wa_url( $wa_num, $text );
        }

        $after    = sanitize_key( $cfg['afterSubmit'] ?? 'message' );
        $redirect = '';
        if ( 'redirect' === $after && ! empty( $cfg['redirectUrl'] ) ) {
            $redirect = esc_url_raw( $cfg['redirectUrl'] );
        }
        $open_wa = ( 'whatsapp' === $after );

        if ( ! empty( $cfg['deliveryEmail'] ) && ! $mail_ok && ! $wa_url ) {
            wp_send_json_error( [ 'message' => __( 'Could not send email. Please try again later.', 'rawnaq' ) ], 500 );
        }

        wp_send_json_success( [
            'message'      => sanitize_text_field( $cfg['successMessage'] ?? __( 'Message sent successfully.', 'rawnaq' ) ),
            'whatsappUrl'  => $wa_url,
            'openWhatsapp' => $open_wa,
            'redirectUrl'  => $redirect,
        ] );
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
            [ 'rawnaq-diagram-export' ],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-hub-diagram',
            RAWNAQ_URL . 'assets/js/hub-diagram.js',
            [ 'rawnaq-diagram-export' ],
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

        // Diagram PNG/SVG export (shared by Flow + Hub)
        wp_register_style(
            'rawnaq-diagram-export',
            RAWNAQ_URL . 'assets/css/diagram-export.css',
            [],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-diagram-export',
            RAWNAQ_URL . 'assets/js/diagram-export.js',
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
        // QRCode.js (davidshimjs) — ship min + unminified source for wp.org Guideline 4.
        $qrcode_file = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? 'qrcode.js' : 'qrcode.min.js';
        wp_register_script(
            'rawnaq-qrcode',
            RAWNAQ_URL . 'assets/js/' . $qrcode_file,
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
            [ 'rawnaq-diagram-export' ],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-flow-chart',
            RAWNAQ_URL . 'assets/js/flow-chart.js',
            [ 'rawnaq-diagram-export' ],
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

        // 8. Scroll Story / Scrollytelling
        wp_register_style(
            'rawnaq-scroll-story',
            RAWNAQ_URL . 'assets/css/scroll-story.css',
            [],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-scroll-story',
            RAWNAQ_URL . 'assets/js/scroll-story.js',
            [],
            RAWNAQ_VERSION,
            true
        );

        // 9. Smart Form
        wp_register_style(
            'rawnaq-smart-form',
            RAWNAQ_URL . 'assets/css/smart-form.css',
            [],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-smart-form',
            RAWNAQ_URL . 'assets/js/smart-form.js',
            [],
            RAWNAQ_VERSION,
            true
        );
        wp_localize_script( 'rawnaq-smart-form', 'rawnaqSmartForm', [
            'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
            'nonce'           => wp_create_nonce( 'rawnaq_smart_form' ),
            'recaptchaSiteKey'=> function_exists( 'rawnaq_smart_form_recaptcha_keys' ) ? rawnaq_smart_form_recaptcha_keys()['site'] : '',
        ] );

        // 10. Case-Study Grid
        wp_register_style(
            'rawnaq-case-study-grid',
            RAWNAQ_URL . 'assets/css/case-study-grid.css',
            [],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-case-study-grid',
            RAWNAQ_URL . 'assets/js/case-study-grid.js',
            [],
            RAWNAQ_VERSION,
            true
        );
        wp_localize_script(
            'rawnaq-case-study-grid',
            'rawnaqCaseStudy',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            ]
        );

        // Cross-module bridge (Case-Study discuss + scroll highlight)
        wp_register_script(
            'rawnaq-bridge',
            RAWNAQ_URL . 'assets/js/rawnaq-bridge.js',
            [],
            RAWNAQ_VERSION,
            true
        );
    }
}