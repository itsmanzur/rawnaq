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

        // Load Divi Builder Loader
        if ( file_exists( RAWNAQ_PATH . 'includes/divi/class-divi-loader.php' ) ) {
            require_once RAWNAQ_PATH . 'includes/divi/class-divi-loader.php';
        }

        // Load Admin Dashboard
        if ( is_admin() ) {
            require_once RAWNAQ_PATH . 'includes/class-rawnaq-admin-dashboard.php';
            new Rawnaq_Admin_Dashboard();
        }
    }

    private function init_hooks() {
        // Frontend + block editor both need these handles registered early (priority 5)
        add_action( 'wp_enqueue_scripts', [ $this, 'register_shared_assets' ], 5 );
        add_action( 'elementor/frontend/after_register_scripts', [ $this, 'register_shared_assets' ] );
        add_action( 'elementor/frontend/after_register_styles', [ $this, 'register_shared_assets' ] );
        add_action( 'enqueue_block_editor_assets', [ $this, 'register_shared_assets' ] );

        add_action( 'wp_ajax_rawnaq_dock_click', [ $this, 'ajax_dock_click' ] );
        add_action( 'wp_ajax_nopriv_rawnaq_dock_click', [ $this, 'ajax_dock_click' ] );
        add_action( 'wp_ajax_rawnaq_dock_reset_clicks', [ $this, 'ajax_dock_reset_clicks' ] );
        add_action( 'wp_ajax_rawnaq_dock_set_status_override', [ $this, 'ajax_dock_set_status_override' ] );
        add_action( 'wp_ajax_rawnaq_timeline_load_more', [ $this, 'ajax_timeline_load_more' ] );
        add_action( 'wp_ajax_nopriv_rawnaq_timeline_load_more', [ $this, 'ajax_timeline_load_more' ] );
        add_action( 'wp_ajax_rawnaq_smart_form_submit', [ $this, 'ajax_smart_form_submit' ] );
        add_action( 'wp_ajax_nopriv_rawnaq_smart_form_submit', [ $this, 'ajax_smart_form_submit' ] );
        add_action( 'wp_ajax_rawnaq_sf_token', [ $this, 'ajax_sf_token' ] );
        add_action( 'wp_ajax_nopriv_rawnaq_sf_token', [ $this, 'ajax_sf_token' ] );
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
     * Public AJAX: fetch fresh nonce and signed timestamp for cached pages.
     */
    public function ajax_sf_token() {
        nocache_headers();
        $form_id = isset( $_REQUEST['form_id'] ) ? sanitize_html_class( wp_unslash( $_REQUEST['form_id'] ) ) : '';
        if ( ! $form_id ) {
            wp_send_json_error( [ 'message' => __( 'Missing form identifier.', 'rawnaq' ) ], 400 );
        }
        $nonce = wp_create_nonce( 'rawnaq_smart_form' );
        $ts    = function_exists( 'rawnaq_smart_form_create_ts_token' )
            ? rawnaq_smart_form_create_ts_token( $form_id )
            : time() . '.' . hash_hmac( 'sha256', (string) $form_id . '|' . time(), wp_salt( 'auth' ) );

        wp_send_json_success( [
            'nonce' => $nonce,
            'ts'    => $ts,
        ] );
    }

    /**
     * Public AJAX: Smart Form submit (email, WA redirect, files, webhook, recaptcha).
     */
    public function ajax_smart_form_submit() {
        check_ajax_referer( 'rawnaq_smart_form', 'nonce' );

        $hp = isset( $_POST['rawnaq_hp'] ) ? sanitize_text_field( wp_unslash( $_POST['rawnaq_hp'] ) ) : '';
        if ( '' !== $hp ) {
            wp_send_json_error( [ 'message' => __( 'Spam check failed. Please try again.', 'rawnaq' ) ], 400 );
        }

        $form_id = isset( $_POST['form_id'] ) ? sanitize_html_class( wp_unslash( $_POST['form_id'] ) ) : '';
        if ( ! $form_id ) {
            wp_send_json_error( [ 'message' => __( 'Missing form identifier.', 'rawnaq' ) ], 400 );
        }

        // Signed timestamp verification (3 seconds <= age <= 7 days)
        $raw_ts   = isset( $_POST['rawnaq_ts'] ) ? sanitize_text_field( wp_unslash( $_POST['rawnaq_ts'] ) ) : '';
        $ts_check = function_exists( 'rawnaq_smart_form_verify_ts_token' )
            ? rawnaq_smart_form_verify_ts_token( $raw_ts, $form_id )
            : ( ( time() - absint( $raw_ts ) ) >= 2 && ( time() - absint( $raw_ts ) ) <= WEEK_IN_SECONDS ? true : 'expired' );

        if ( true !== $ts_check ) {
            if ( 'too_fast' === $ts_check ) {
                wp_send_json_error( [ 'message' => __( 'Please wait a moment and try again.', 'rawnaq' ) ], 400 );
            } else {
                wp_send_json_error( [ 'message' => __( 'Security check expired. Please reload the page and try again.', 'rawnaq' ) ], 400 );
            }
        }

        // Rate limit: 5 / 10 min / form, 30 / hour / site
        $client_ip = function_exists( 'rawnaq_smart_form_get_client_ip' )
            ? rawnaq_smart_form_get_client_ip()
            : ( ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1' );

        if ( function_exists( 'rawnaq_smart_form_check_rate_limit' ) && ! rawnaq_smart_form_check_rate_limit( $client_ip, $form_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Too many submissions. Please wait a few minutes and try again.', 'rawnaq' ) ], 429 );
        }

        // Load trusted config from server store — never trust client-posted delivery settings.
        $cfg = function_exists( 'rawnaq_smart_form_get_stored_config' )
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

        // Server-side validation
        if ( function_exists( 'rawnaq_smart_form_validate_submission' ) ) {
            $validation = rawnaq_smart_form_validate_submission( $fields_cfg, $posted, $cfg );
            $values     = $validation['values'];
            $errors     = $validation['errors'];
        } else {
            $values = [];
            $errors = [];
        }

        // File uploads
        $created_files = [];
        $upload        = function_exists( 'rawnaq_smart_form_handle_uploads' )
            ? rawnaq_smart_form_handle_uploads( $fields_cfg )
            : [ 'values' => [], 'attachments' => [], 'files_meta' => [], 'errors' => [] ];
        if ( ! empty( $upload['attachments'] ) ) {
            $created_files = array_merge( $created_files, $upload['attachments'] );
        }
        if ( ! empty( $upload['errors'] ) ) {
            foreach ( $upload['errors'] as $err_id ) {
                $errors[ $err_id ] = __( 'File upload failed. Check size and type, then try again.', 'rawnaq' );
            }
        }
        foreach ( $upload['values'] as $k => $v ) {
            $values[ $k ] = $v;
        }
        $attachments = $upload['attachments'] ?? [];
        $files_meta  = $upload['files_meta'] ?? [];

        // Signatures as files
        foreach ( $fields_cfg as $field ) {
            if ( 'signature' !== $field['type'] ) {
                continue;
            }
            $sig_id = $field['id'];
            if ( ! empty( $values[ $sig_id ] ) && 0 === strpos( $values[ $sig_id ], 'data:image/png;base64,' ) ) {
                $sig_res = function_exists( 'rawnaq_smart_form_save_signature' )
                    ? rawnaq_smart_form_save_signature( $values[ $sig_id ], $sig_id )
                    : new WP_Error( 'sig_save_unavailable', __( 'Signature processing unavailable.', 'rawnaq' ) );
                if ( is_wp_error( $sig_res ) ) {
                    $errors[ $sig_id ] = $sig_res->get_error_message();
                } else {
                    $values[ $sig_id ]     = 'signature.png'; // Never keep base64 in values/meta/email/webhook/CSV.
                    $files_meta[ $sig_id ] = [
                        'path'          => $sig_res['filename'],
                        'original_name' => 'signature.png',
                        'name'          => 'signature.png',
                        'mime'          => 'image/png',
                        'size'          => $sig_res['size'],
                    ];
                    $attachments[]   = $sig_res['full_path'];
                    $created_files[] = $sig_res['full_path'];
                }
            }
        }

        // Consent checkbox
        $consent_meta = null;
        if ( ! empty( $cfg['consentEnabled'] ) ) {
            $consent = isset( $posted['consent'] ) ? sanitize_text_field( (string) $posted['consent'] ) : '';
            if ( '1' !== $consent ) {
                $errors['consent'] = __( 'Please accept the consent checkbox.', 'rawnaq' );
            } else {
                $values['consent'] = 'yes';
                $consent_text      = ! empty( $cfg['consentText'] ) ? sanitize_text_field( $cfg['consentText'] ) : __( 'I agree to the processing of my data.', 'rawnaq' );
                $consent_meta      = [
                    'text'      => $consent_text,
                    'timestamp' => time(),
                ];
            }
        }

        // Return field-level errors if any failed validation
        if ( ! empty( $errors ) ) {
            if ( function_exists( 'rawnaq_smart_form_discard_files' ) ) {
                rawnaq_smart_form_discard_files( $created_files );
            }
            if ( function_exists( 'rawnaq_smart_form_bump_rate_limit' ) ) {
                rawnaq_smart_form_bump_rate_limit( $client_ip, $form_id, true );
            }
            wp_send_json_error( [
                'message' => sanitize_text_field( $cfg['errorMessage'] ?? __( 'Please fix the errors below and try again.', 'rawnaq' ) ),
                'fields'  => $errors,
            ], 400 );
        }

        // Duplicate guard: hash of form_id + all field values (within 60 seconds)
        if ( function_exists( 'rawnaq_smart_form_is_duplicate' ) && rawnaq_smart_form_is_duplicate( $form_id, $values ) ) {
            if ( function_exists( 'rawnaq_smart_form_discard_files' ) ) {
                rawnaq_smart_form_discard_files( $created_files );
            }
            $already_msg = sanitize_text_field( apply_filters(
                'rawnaq_smart_form_duplicate_message',
                __( 'We already received your submission. Thank you!', 'rawnaq' )
            ) );
            wp_send_json_success( [
                'message'      => $already_msg,
                'duplicate'    => true,
                'whatsappUrl'  => '',
                'openWhatsapp' => false,
                'redirectUrl'  => '',
            ] );
        }

        // Rate limit check before logging/sending
        if ( function_exists( 'rawnaq_smart_form_check_rate_limit' ) && ! rawnaq_smart_form_check_rate_limit( $client_ip, $form_id ) ) {
            if ( function_exists( 'rawnaq_smart_form_discard_files' ) ) {
                rawnaq_smart_form_discard_files( $created_files );
            }
            wp_send_json_error( [ 'message' => __( 'Too many submissions. Please wait a few minutes and try again.', 'rawnaq' ) ], 429 );
        }

        // Record duplicate transient and record rate limit hits
        if ( function_exists( 'rawnaq_smart_form_record_duplicate' ) ) {
            rawnaq_smart_form_record_duplicate( $form_id, $values );
        }
        if ( function_exists( 'rawnaq_smart_form_bump_rate_limit' ) ) {
            rawnaq_smart_form_bump_rate_limit( $client_ip, $form_id );
        }

        $page_ctx = $cfg['pageCtx'] ?? [];
        if ( empty( $page_ctx ) || empty( $page_ctx['url'] ) ) {
            // Only if pageCtx is empty: accept the posted landing_page URL when its host equals the site host.
            $posted_landing = isset( $posted['landing_page'] ) ? esc_url_raw( wp_unslash( $posted['landing_page'] ) ) : '';
            if ( $posted_landing ) {
                $site_host = wp_parse_url( home_url(), PHP_URL_HOST );
                $land_host = wp_parse_url( $posted_landing, PHP_URL_HOST );
                if ( $site_host && $land_host && strtolower( $site_host ) === strtolower( $land_host ) ) {
                    if ( ! is_array( $page_ctx ) ) {
                        $page_ctx = [];
                    }
                    $page_ctx['url'] = $posted_landing;
                    $landing_post_id = url_to_postid( $posted_landing );
                    if ( $landing_post_id ) {
                        $page_ctx['postId']    = (string) $landing_post_id;
                        $page_ctx['pageTitle'] = get_the_title( $landing_post_id );
                    }
                }
            }
        }

        $tpl_vals = function_exists( 'rawnaq_smart_form_template_values' )
            ? rawnaq_smart_form_template_values( $values, $page_ctx )
            : $values;

        // Attachment size threshold check (default 10 MB, filterable)
        $total_att_size = 0;
        foreach ( $attachments as $att_path ) {
            if ( file_exists( $att_path ) ) {
                $total_att_size += filesize( $att_path );
            }
        }
        $max_attach_size = function_exists( 'rawnaq_smart_form_max_attachments_size' )
            ? rawnaq_smart_form_max_attachments_size()
            : 10 * MB_IN_BYTES;
        $links_only = ( $total_att_size > $max_attach_size );

        // Log entry before email send if logging enabled, files uploaded, or links required
        $log_enabled   = ! empty( $cfg['logSubmissions'] );
        $has_files     = ! empty( $files_meta );
        $need_log      = $log_enabled || $has_files;
        $entry_id      = 0;

        if ( $need_log && function_exists( 'rawnaq_smart_form_log_submission' ) ) {
            $entry_id = rawnaq_smart_form_log_submission( $form_id, $values, $cfg, $files_meta, $consent_meta );
        }

        $delivery_results = [
            'email'         => 'off',
            'webhook'       => 'off',
            'crm'           => 'off',
            'whatsapp'      => 'off',
            'autoresponder' => 'off',
            'errors'        => [],
        ];
        $delivery_errors = [];

        // 1. Email Delivery
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
            $subject = sanitize_text_field( $subject );
            $use_html = ( ! isset( $cfg['emailHtml'] ) || ! empty( $cfg['emailHtml'] ) )
                && function_exists( 'rawnaq_smart_form_email_html' );
            if ( $use_html ) {
                $body    = rawnaq_smart_form_email_html( $values, $tpl_vals, $cfg, $files_meta, $entry_id, $links_only );
                $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
            } else {
                $body_lines = [];
                foreach ( $values as $k => $v ) {
                    if ( isset( $files_meta[ $k ] ) && 'signature.png' === ( $files_meta[ $k ]['name'] ?? '' ) ) {
                        $display_val = __( 'Signature attached', 'rawnaq' );
                    } elseif ( isset( $files_meta[ $k ] ) ) {
                        $display_val = $files_meta[ $k ]['name'] ?? (string) $v;
                    } else {
                        $display_val = is_array( $v ) ? implode( ', ', $v ) : (string) $v;
                    }
                    if ( $links_only && isset( $files_meta[ $k ] ) && $entry_id && function_exists( 'rawnaq_smart_form_get_download_url' ) ) {
                        $dl_url       = rawnaq_smart_form_get_download_url( $entry_id, $k );
                        $display_val .= ' (' . __( 'Download', 'rawnaq' ) . ': ' . $dl_url . ')';
                    }
                    $body_lines[] = $k . ': ' . $display_val;
                }
                $body_lines[] = '';
                $body_lines[] = 'Page: ' . ( $tpl_vals['pageTitle'] ?? '' );
                $body_lines[] = 'URL: ' . ( $tpl_vals['url'] ?? '' );
                $body    = implode( "\n", $body_lines );
                $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
            }
            $contact = function_exists( 'rawnaq_smart_form_resolve_contact' )
                ? rawnaq_smart_form_resolve_contact( $values, $fields_cfg )
                : [ 'email' => $values['email'] ?? '', 'first_name' => '', 'last_name' => '', 'phone' => '' ];

            if ( ! empty( $contact['email'] ) && is_email( $contact['email'] ) ) {
                $reply_name = trim( $contact['first_name'] . ' ' . $contact['last_name'] );
                if ( $reply_name ) {
                    $headers[] = 'Reply-To: ' . $reply_name . ' <' . $contact['email'] . '>';
                } else {
                    $headers[] = 'Reply-To: ' . $contact['email'];
                }
            }

            $mail_err_msg   = '';
            $mail_failed_cb = function( $wp_error ) use ( &$mail_err_msg ) {
                if ( is_wp_error( $wp_error ) ) {
                    $mail_err_msg = $wp_error->get_error_message();
                }
            };
            add_action( 'wp_mail_failed', $mail_failed_cb );
            $mail_attachments = $links_only ? [] : $attachments;
            $mail_ok = wp_mail( $to, $subject, $body, $headers, $mail_attachments );
            remove_action( 'wp_mail_failed', $mail_failed_cb );

            if ( $mail_ok ) {
                $delivery_results['email'] = 'ok';
            } else {
                $delivery_results['email'] = 'fail';
                $delivery_errors[]         = 'Email: ' . ( $mail_err_msg ?: __( 'wp_mail returned false.', 'rawnaq' ) );
            }
        }

        // 2. Webhook Delivery
        if ( ! empty( $cfg['webhookEnabled'] ) && ! empty( $cfg['webhookUrl'] ) && function_exists( 'rawnaq_smart_form_send_webhook' ) ) {
            $wh_res = rawnaq_smart_form_send_webhook(
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
            if ( is_wp_error( $wh_res ) ) {
                $delivery_results['webhook'] = 'fail';
                $delivery_errors[]           = 'Webhook: ' . $wh_res->get_error_message();
            } else {
                $delivery_results['webhook'] = 'ok';
            }
        }

        // 3. CRM Delivery
        if ( function_exists( 'rawnaq_smart_form_dispatch_crm' ) ) {
            $crm_res = rawnaq_smart_form_dispatch_crm( $values, $tpl_vals, $cfg, $form_id );
            if ( is_wp_error( $crm_res ) ) {
                $delivery_results['crm'] = 'fail';
                $delivery_errors[]       = 'CRM: ' . $crm_res->get_error_message();
            } elseif ( true === $crm_res ) {
                $delivery_results['crm'] = 'ok';
            } elseif ( is_string( $crm_res ) ) {
                $delivery_results['crm'] = $crm_res;
            }
        }

        // 4. WhatsApp
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
            if ( $wa_url ) {
                $delivery_results['whatsapp'] = 'link';
            }
        }

        // 5. Auto-responder
        if ( ! empty( $cfg['autoResponderEnabled'] ) && function_exists( 'rawnaq_smart_form_send_auto_responder' ) ) {
            $ar_res = rawnaq_smart_form_send_auto_responder( $values, $cfg, $fields_cfg );
            if ( is_wp_error( $ar_res ) ) {
                $delivery_results['autoresponder'] = 'fail';
                $delivery_errors[]                 = 'Auto-responder: ' . $ar_res->get_error_message();
            } elseif ( true === $ar_res ) {
                $delivery_results['autoresponder'] = 'ok';
            } else {
                $delivery_results['autoresponder'] = (string) $ar_res;
            }
        }

        $delivery_results['errors'] = $delivery_errors;

        // Never lose a lead: store entry if logging enabled OR if email failed / off
        $need_fallback = ! $log_enabled && ( empty( $cfg['deliveryEmail'] ) || ! $mail_ok );
        if ( ! $entry_id && $need_fallback && function_exists( 'rawnaq_smart_form_log_submission' ) ) {
            $entry_id = rawnaq_smart_form_log_submission( $form_id, $values, $cfg, $files_meta, $consent_meta );
        }

        if ( $entry_id ) {
            update_post_meta( $entry_id, '_rawnaq_sf_delivery', $delivery_results );
            if ( $need_fallback || ( ! empty( $cfg['deliveryEmail'] ) && ! $mail_ok ) ) {
                update_post_meta( $entry_id, '_rawnaq_sf_fallback', '1' );
            }
        }

        $after    = sanitize_key( $cfg['afterSubmit'] ?? 'message' );
        $redirect = '';
        if ( 'redirect' === $after && ! empty( $cfg['redirectUrl'] ) ) {
            $redirect = esc_url_raw( $cfg['redirectUrl'] );
        }
        $open_wa = ( 'whatsapp' === $after );

        if ( ! empty( $cfg['deliveryEmail'] ) && ! $mail_ok && ! $wa_url ) {
            if ( ! $entry_id ) {
                if ( function_exists( 'rawnaq_smart_form_discard_files' ) ) {
                    rawnaq_smart_form_discard_files( $created_files );
                }
                if ( function_exists( 'rawnaq_smart_form_forget_duplicate' ) ) {
                    rawnaq_smart_form_forget_duplicate( $form_id, $values );
                }
                wp_send_json_error( [ 'message' => __( 'Could not send email. Please try again later.', 'rawnaq' ) ], 500 );
            }
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
     * Admin AJAX: set the site-wide WhatsApp dock status override.
     */
    public function ajax_dock_set_status_override() {
        check_ajax_referer( 'rawnaq_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
        }
        $value = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'auto';
        if ( ! in_array( $value, [ 'auto', 'online', 'offline' ], true ) ) {
            wp_send_json_error( [ 'message' => 'invalid' ], 400 );
        }
        update_option( 'rawnaq_dock_status_override', $value, false );
        wp_send_json_success( [ 'status' => $value ] );
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
            // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- decoding our own base64-packed timeline query args, not obfuscated code.
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
        $node_style = isset( $_POST['node_style'] ) ? sanitize_key( wp_unslash( $_POST['node_style'] ) ) : 'number';
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
        $html       = rawnaq_timeline_render_items_html( $steps, $layout, $show_numbers, $offset, $node_style );

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
        // 1. Hub Diagram Assets
        wp_register_style(
            'rawnaq-hub-diagram',
            rawnaq_asset_url( 'css/hub-diagram.css' ),
            [ 'rawnaq-diagram-export' ],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-hub-diagram',
            rawnaq_asset_url( 'js/hub-diagram.js' ),
            [ 'rawnaq-diagram-export' ],
            RAWNAQ_VERSION,
            true
        );

        // 2. 3D Tilt Card Assets
        wp_register_style(
            'rawnaq-tilt-card',
            rawnaq_asset_url( 'css/tilt-card.css' ),
            [],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-tilt-card',
            rawnaq_asset_url( 'js/tilt-card.js' ),
            [],
            RAWNAQ_VERSION,
            true
        );

        // Diagram PNG/SVG export (shared by Flow + Hub)
        wp_register_style(
            'rawnaq-diagram-export',
            rawnaq_asset_url( 'css/diagram-export.css' ),
            [],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-diagram-export',
            rawnaq_asset_url( 'js/diagram-export.js' ),
            [],
            RAWNAQ_VERSION,
            true
        );

        // 3. Scroll-Sync Timeline Assets
        wp_register_style(
            'rawnaq-scroll-timeline',
            rawnaq_asset_url( 'css/scroll-timeline.css' ),
            [],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-scroll-timeline',
            rawnaq_asset_url( 'js/scroll-timeline.js' ),
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
            rawnaq_asset_url( 'css/floating-dock.css' ),
            [],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-floating-dock',
            rawnaq_asset_url( 'js/floating-dock.js' ),
            [ 'rawnaq-qrcode' ],
            RAWNAQ_VERSION,
            true
        );
        wp_localize_script( 'rawnaq-floating-dock', 'rawnaqDock', [
            'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
            'nonce'            => wp_create_nonce( 'rawnaq_dock_click' ),
            'defaultAvatarUrl' => rawnaq_asset_url( 'img/default-avatar.svg' ),
        ] );

        // 5. Flow Chart
        wp_register_style(
            'rawnaq-flow-chart',
            rawnaq_asset_url( 'css/flow-chart.css' ),
            [ 'rawnaq-diagram-export' ],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-flow-chart',
            rawnaq_asset_url( 'js/flow-chart.js' ),
            [ 'rawnaq-diagram-export' ],
            RAWNAQ_VERSION,
            true
        );

        // 6. Scroll Progress + TOC
        wp_register_style(
            'rawnaq-scroll-progress-toc',
            rawnaq_asset_url( 'css/scroll-progress-toc.css' ),
            [],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-scroll-progress-toc',
            rawnaq_asset_url( 'js/scroll-progress-toc.js' ),
            [],
            RAWNAQ_VERSION,
            true
        );
        wp_localize_script( 'rawnaq-scroll-progress-toc', 'rawnaqSPT', [
            'i18n' => [
                'minRead'           => __( '%s min read', 'rawnaq' ),
                'minLeft'           => __( '%s min left', 'rawnaq' ),
                'finishedReading'   => __( 'Finished reading', 'rawnaq' ),
                'secBadge'          => __( '~%sm', 'rawnaq' ),
                'secTitle'          => __( 'Estimated %s min section', 'rawnaq' ),
                'searchPlaceholder' => __( 'Search headings...', 'rawnaq' ),
                'searchAriaLabel'   => __( 'Search headings', 'rawnaq' ),
                'toggleToc'         => __( 'Toggle Table of Contents', 'rawnaq' ),
                'scrollToTop'       => __( 'Scroll to top of page', 'rawnaq' ),
                'backToTop'         => __( 'Back to top', 'rawnaq' ),
                'copyLink'          => __( 'Copy page link', 'rawnaq' ),
                'linkCopied'        => __( 'Link Copied!', 'rawnaq' ),
                'chapterN'          => __( 'Chapter %s', 'rawnaq' ),
                'chapterNTitle'     => __( 'Chapter %1$s: %2$s', 'rawnaq' ),
                'contents'          => __( 'Contents', 'rawnaq' ),
                'toggleNavActions'  => __( 'Toggle Navigation Actions', 'rawnaq' ),
            ],
        ] );

        // 7. Bento Grid
        wp_register_style(
            'rawnaq-bento-grid',
            rawnaq_asset_url( 'css/bento-grid.css' ),
            [],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-bento-grid',
            rawnaq_asset_url( 'js/bento-grid.js' ),
            [],
            RAWNAQ_VERSION,
            true
        );

        // 8. Scroll Story / Scrollytelling
        wp_register_style(
            'rawnaq-scroll-story',
            rawnaq_asset_url( 'css/scroll-story.css' ),
            [],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-scroll-story',
            rawnaq_asset_url( 'js/scroll-story.js' ),
            [],
            RAWNAQ_VERSION,
            true
        );

        // 9. Smart Form
        wp_register_style(
            'rawnaq-smart-form',
            rawnaq_asset_url( 'css/smart-form.css' ),
            [],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-smart-form',
            rawnaq_asset_url( 'js/smart-form.js' ),
            [],
            RAWNAQ_VERSION,
            true
        );
        wp_localize_script( 'rawnaq-smart-form', 'rawnaqSmartForm', [
            'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
            'nonce'            => wp_create_nonce( 'rawnaq_smart_form' ),
            'recaptchaSiteKey' => function_exists( 'rawnaq_smart_form_recaptcha_keys' ) ? rawnaq_smart_form_recaptcha_keys()['site'] : '',
            'i18n'             => [
                'errorRequired'           => __( 'Please fill in the required fields correctly.', 'rawnaq' ),
                'errorRequiredField'      => __( 'This field is required.', 'rawnaq' ),
                'errorInvalidEmail'       => __( 'Please enter a valid email address.', 'rawnaq' ),
                'errorInvalidPhone'       => __( 'Please enter a valid phone number.', 'rawnaq' ),
                'errorInvalidUrl'         => __( 'Please enter a valid website URL.', 'rawnaq' ),
                'errorFileTooLarge'       => __( 'File exceeds maximum size of %s MB.', 'rawnaq' ),
                'errorFileType'           => __( 'File type not allowed. Accepted: %s', 'rawnaq' ),
                'errorRatingRequired'     => __( 'Please select a rating.', 'rawnaq' ),
                'errorNumberMin'          => __( 'Value must be at least %s.', 'rawnaq' ),
                'errorNumberMax'          => __( 'Value must not exceed %s.', 'rawnaq' ),
                'errorNumberRange'        => __( 'Value must be between %1$s and %2$s.', 'rawnaq' ),
                'errorGeneric'            => __( 'Something went wrong. Please try again.', 'rawnaq' ),
                'successDefault'          => __( 'Message sent successfully.', 'rawnaq' ),
                'removeFile'              => __( 'Remove file', 'rawnaq' ),
                'removeFileWithName'      => __( 'Remove file: %s', 'rawnaq' ),
                'fileAttached'            => __( 'File selected: %1$s (%2$s)', 'rawnaq' ),
                'reviewSubmission'        => __( 'Review Your Submission', 'rawnaq' ),
                'stepOf'                  => __( 'Step %1$d of %2$d', 'rawnaq' ),
                'stepAnnounce'            => __( 'Step %1$d of %2$d', 'rawnaq' ),
                'ratingStar'              => __( '%1$d out of %2$d stars', 'rawnaq' ),
                'typeSignature'           => __( 'Type your name instead', 'rawnaq' ),
                'drawSignature'           => __( 'Draw signature instead', 'rawnaq' ),
                'clearSignature'          => __( 'Clear signature', 'rawnaq' ),
                'typeSignaturePlaceholder'=> __( 'Type your full name here...', 'rawnaq' ),
                'signaturePad'            => __( 'Signature pad', 'rawnaq' ),
                'fileMb'                  => __( 'MB', 'rawnaq' ),
                'fileKb'                  => __( 'KB', 'rawnaq' ),
                'inquiryRegarding'        => __( 'Inquiry regarding %s', 'rawnaq' ),
                'caseStudy'               => __( 'Case Study', 'rawnaq' ),
                'projectDiscuss'          => __( 'I am interested in discussing a project similar to "%s"%s.', 'rawnaq' ),
                'clientLabel'             => __( ' (Client: %s)', 'rawnaq' ),
                'forClient'               => __( ' for %s', 'rawnaq' ),
                'sessionExpired'          => __( 'Your session expired. Please reload the page and try again.', 'rawnaq' ),
                'estimatedTotal'          => __( 'Estimated total', 'rawnaq' ),
            ],
        ] );

        // 10. Case-Study Grid
        wp_register_style(
            'rawnaq-case-study-grid',
            rawnaq_asset_url( 'css/case-study-grid.css' ),
            [],
            RAWNAQ_VERSION
        );
        wp_register_script(
            'rawnaq-case-study-grid',
            rawnaq_asset_url( 'js/case-study-grid.js' ),
            [],
            RAWNAQ_VERSION,
            true
        );
        wp_localize_script(
            'rawnaq-case-study-grid',
            'rawnaqCaseStudy',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'i18n'    => [
                    'year'      => __( 'Year', 'rawnaq' ),
                    'scope'     => __( 'Scope', 'rawnaq' ),
                    'budget'    => __( 'Budget', 'rawnaq' ),
                    'client'    => __( 'Client', 'rawnaq' ),
                    'imageOf'   => __( 'Image %1$d of %2$d', 'rawnaq' ),
                    'showing'   => __( 'Showing %d projects', 'rawnaq' ),
                    'noResults' => __( 'No matching projects.', 'rawnaq' ),
                    'loadError' => __( 'Could not load projects. Please try again.', 'rawnaq' ),
                    'retry'     => __( 'Retry', 'rawnaq' ),
                    'close'     => __( 'Close', 'rawnaq' ),
                ],
            ]
        );

        // Cross-module bridge (Case-Study discuss + scroll highlight)
        wp_register_script(
            'rawnaq-bridge',
            rawnaq_asset_url( 'js/rawnaq-bridge.js' ),
            [],
            RAWNAQ_VERSION,
            true
        );
    }
}