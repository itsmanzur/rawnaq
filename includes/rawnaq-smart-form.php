<?php
/**
 * Smart Form — shared helpers, markup, presets, delivery extras.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Site-wide default WhatsApp number (shared with Floating Dock when agent number empty).
 *
 * @return string
 */
function rawnaq_get_default_wa_number() {
	$settings = get_option( 'rawnaq_settings', [] );
	if ( ! is_array( $settings ) ) {
		return '';
	}
	return sanitize_text_field( $settings['default_wa_number'] ?? '' );
}

/**
 * Resolve form WA number → site default fallback.
 *
 * @param string $form_number Per-form number.
 * @return string
 */
function rawnaq_smart_form_resolve_wa_number( $form_number ) {
	$n = sanitize_text_field( (string) $form_number );
	if ( $n ) {
		return $n;
	}
	return rawnaq_get_default_wa_number();
}

/**
 * Digits-only WhatsApp phone (country code, no +).
 *
 * @param string $phone Raw phone.
 * @return string
 */
function rawnaq_smart_form_normalize_phone( $phone ) {
	return preg_replace( '/\D+/', '', (string) $phone );
}

/**
 * Build wa.me URL with prefilled text.
 *
 * @param string $phone Digits or formatted.
 * @param string $text  Message body.
 * @return string
 */
function rawnaq_smart_form_wa_url( $phone, $text ) {
	$digits = rawnaq_smart_form_normalize_phone( $phone );
	if ( ! $digits ) {
		return '';
	}
	return 'https://wa.me/' . $digits . '?text=' . rawurlencode( (string) $text );
}

/**
 * Capability check for managing Smart Form entries / exports.
 *
 * @return bool
 */
function rawnaq_smart_form_can_manage_entries() {
	return current_user_can( apply_filters( 'rawnaq_smart_form_entries_cap', 'manage_options' ) );
}

/**
 * Create a signed timestamp token for a form ID.
 *
 * Format: "{issued}.{hmac}" where hmac = hash_hmac( 'sha256', $form_id . '|' . $issued, wp_salt( 'auth' ) ).
 *
 * @param string   $form_id Unique form ID.
 * @param int|null $issued  Optional Unix timestamp.
 * @return string
 */
function rawnaq_smart_form_create_ts_token( $form_id, $issued = null ) {
	$issued = $issued ? absint( $issued ) : time();
	$hmac   = hash_hmac( 'sha256', (string) $form_id . '|' . $issued, wp_salt( 'auth' ) );
	return $issued . '.' . $hmac;
}

/**
 * Verify a signed timestamp token.
 *
 * Requires 3 seconds <= age <= 7 days.
 *
 * @param string $token   "{issued}.{hmac}"
 * @param string $form_id Form ID.
 * @return true|string True if valid, or 'invalid' | 'too_fast' | 'expired'.
 */
function rawnaq_smart_form_verify_ts_token( $token, $form_id ) {
	if ( ! is_string( $token ) || false === strpos( $token, '.' ) ) {
		return 'invalid';
	}
	$parts  = explode( '.', $token, 2 );
	$issued = absint( $parts[0] );
	$hmac   = (string) ( $parts[1] ?? '' );
	if ( ! $issued || ! $hmac ) {
		return 'invalid';
	}
	$expected = hash_hmac( 'sha256', (string) $form_id . '|' . $issued, wp_salt( 'auth' ) );
	if ( ! hash_equals( $expected, $hmac ) ) {
		return 'invalid';
	}
	$age = time() - $issued;
	if ( $age < 3 ) {
		return 'too_fast';
	}
	if ( $age > ( 7 * DAY_IN_SECONDS ) ) {
		return 'expired';
	}
	return true;
}

/**
 * Resolve client IP, trusting proxy headers only if filtered.
 *
 * @return string
 */
function rawnaq_smart_form_get_client_ip() {
	$filtered = apply_filters( 'rawnaq_smart_form_client_ip', '' );
	if ( $filtered && filter_var( $filtered, FILTER_VALIDATE_IP ) ) {
		return (string) $filtered;
	}
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '127.0.0.1';
}

/**
 * Check rate limits for client IP.
 *
 * Defaults: 5 submissions / 10 min / form and 30 / hour / site, filterable via 'rawnaq_smart_form_rate_limit'.
 * Transients are keyed by hash_hmac( 'sha256', $ip, wp_salt() ) — never stores raw IPs.
 *
 * @param string $ip      Client IP.
 * @param string $form_id Form ID.
 * @return bool True if allowed, false if limit exceeded.
 */
function rawnaq_smart_form_check_rate_limit( $ip, $form_id ) {
	$ip_hash  = hash_hmac( 'sha256', (string) $ip, wp_salt() );
	$defaults = [
		'form_limit'  => 5,
		'form_window' => 10 * MINUTE_IN_SECONDS,
		'site_limit'  => 30,
		'site_window' => HOUR_IN_SECONDS,
	];
	$cfg = apply_filters( 'rawnaq_smart_form_rate_limit', $defaults, $ip, $form_id );
	if ( is_numeric( $cfg ) ) {
		$cfg = array_merge( $defaults, [ 'site_limit' => absint( $cfg ) ] );
	} elseif ( ! is_array( $cfg ) ) {
		$cfg = $defaults;
	}

	$form_limit = absint( $cfg['form_limit'] ?? 5 );
	$site_limit = absint( $cfg['site_limit'] ?? 30 );

	$form_key  = 'rwq_sf_rf_' . substr( md5( (string) $form_id ), 0, 10 ) . '_' . $ip_hash;
	$site_key  = 'rwq_sf_rs_' . $ip_hash;
	$form_hits = (int) get_transient( $form_key );
	$site_hits = (int) get_transient( $site_key );

	if ( ( $form_limit > 0 && $form_hits >= $form_limit ) || ( $site_limit > 0 && $site_hits >= $site_limit ) ) {
		return false;
	}

	// Also check failure threshold
	$fail_key   = 'rwq_sf_rff_' . substr( md5( (string) $form_id ), 0, 10 ) . '_' . $ip_hash;
	$fail_hits  = (int) get_transient( $fail_key );
	$fail_limit = absint( apply_filters( 'rawnaq_smart_form_rate_limit_fail_max', $form_limit * 3, $ip, $form_id ) );

	if ( $fail_limit > 0 && $fail_hits >= $fail_limit ) {
		return false;
	}

	return true;
}

/**
 * Record a submission hit against the rate limiter.
 *
 * @param string $ip         Client IP.
 * @param string $form_id    Form ID.
 * @param bool   $is_failure True if recording a validation failure, false on success.
 */
function rawnaq_smart_form_bump_rate_limit( $ip, $form_id, $is_failure = false ) {
	$ip_hash  = hash_hmac( 'sha256', (string) $ip, wp_salt() );
	$defaults = [
		'form_limit'  => 5,
		'form_window' => 10 * MINUTE_IN_SECONDS,
		'site_limit'  => 30,
		'site_window' => HOUR_IN_SECONDS,
	];
	$cfg = apply_filters( 'rawnaq_smart_form_rate_limit', $defaults, $ip, $form_id );
	if ( is_numeric( $cfg ) ) {
		$cfg = array_merge( $defaults, [ 'site_limit' => absint( $cfg ) ] );
	} elseif ( ! is_array( $cfg ) ) {
		$cfg = $defaults;
	}

	$form_window = absint( $cfg['form_window'] ?? ( 10 * MINUTE_IN_SECONDS ) );
	$site_window = absint( $cfg['site_window'] ?? HOUR_IN_SECONDS );

	if ( $is_failure ) {
		$fail_key  = 'rwq_sf_rff_' . substr( md5( (string) $form_id ), 0, 10 ) . '_' . $ip_hash;
		$fail_hits = (int) get_transient( $fail_key );
		set_transient( $fail_key, $fail_hits + 1, $form_window );
		return;
	}

	$form_key  = 'rwq_sf_rf_' . substr( md5( (string) $form_id ), 0, 10 ) . '_' . $ip_hash;
	$site_key  = 'rwq_sf_rs_' . $ip_hash;
	$form_hits = (int) get_transient( $form_key );
	$site_hits = (int) get_transient( $site_key );

	set_transient( $form_key, $form_hits + 1, $form_window );
	set_transient( $site_key, $site_hits + 1, $site_window );
}

/**
 * Duplicate submission key for a given form and field values.
 *
 * @param string $form_id Form ID.
 * @param array  $values  Field values.
 * @return string Transient key.
 */
function rawnaq_smart_form_duplicate_key( $form_id, $values ) {
	return 'rwq_sf_dp_' . md5( (string) $form_id . '|' . wp_json_encode( (array) $values ) );
}

/**
 * Check if submission is an identical submission within 60 seconds.
 *
 * @param string $form_id Form ID.
 * @param array  $values  Field values.
 * @return bool True if duplicate.
 */
function rawnaq_smart_form_is_duplicate( $form_id, $values ) {
	$key = rawnaq_smart_form_duplicate_key( $form_id, $values );
	return (bool) get_transient( $key );
}

/**
 * Record duplicate submission guard for 60 seconds.
 *
 * @param string $form_id Form ID.
 * @param array  $values  Field values.
 */
function rawnaq_smart_form_record_duplicate( $form_id, $values ) {
	$key = rawnaq_smart_form_duplicate_key( $form_id, $values );
	set_transient( $key, 1, 60 );
}

/**
 * Forget recorded duplicate submission guard.
 *
 * @param string $form_id Form ID.
 * @param array  $values  Field values.
 * @return bool True if transient was deleted, false otherwise.
 */
function rawnaq_smart_form_forget_duplicate( $form_id, $values ) {
	$key = rawnaq_smart_form_duplicate_key( $form_id, $values );
	return (bool) delete_transient( $key );
}

/**
 * Build a unique, stable, HTML-class safe form identifier.
 *
 * @param string $builder    Builder identifier (e.g. 'el', 'divi', 'gb', 'sf').
 * @param array  $cfg        Form configuration.
 * @param string $element_id Optional builder-specific element ID.
 * @return string
 */
function rawnaq_smart_form_make_form_id( $builder, $cfg, $element_id = '' ) {
	$builder = sanitize_key( $builder ?: 'sf' );
	$page_id = absint( get_queried_object_id() );
	$fields  = rawnaq_smart_form_normalize_fields( $cfg['fields'] ?? [] );
	$schema  = [
		'deliveryEmail'    => ! empty( $cfg['deliveryEmail'] ),
		'deliveryWhatsapp' => ! empty( $cfg['deliveryWhatsapp'] ),
		'emailTo'          => $cfg['emailTo'] ?? '',
		'emailSubject'     => $cfg['emailSubject'] ?? '',
		'waNumber'         => $cfg['waNumber'] ?? '',
		'waTemplate'       => $cfg['waTemplate'] ?? '',
		'afterSubmit'      => $cfg['afterSubmit'] ?? '',
		'redirectUrl'      => $cfg['redirectUrl'] ?? '',
		'webhookEnabled'   => ! empty( $cfg['webhookEnabled'] ),
		'webhookUrl'       => $cfg['webhookUrl'] ?? '',
		'fields'           => $fields,
	];
	$hash = substr( md5( (string) wp_json_encode( [ $element_id, $schema ] ) ), 0, 12 );
	return sanitize_html_class( "{$builder}-{$page_id}-{$hash}" );
}

/**
 * Page + Woo context tokens for templates (mirrors Floating Dock).
 *
 * @param array<string, mixed> $page_ctx Optional pre-captured trusted page context.
 * @return array<string, string>
 */
function rawnaq_smart_form_page_tokens( $page_ctx = [] ) {
	$ctx = is_array( $page_ctx ) && ! empty( $page_ctx )
		? $page_ctx
		: ( function_exists( 'rawnaq_get_wa_page_context' ) ? rawnaq_get_wa_page_context() : [] );

	/**
	 * Filter WhatsApp page context used for dynamic prefilled messages.
	 *
	 * @param array<string, string> $ctx Context map.
	 */
	$ctx = apply_filters( 'rawnaq_wa_page_context', $ctx );

	$tokens = [
		'pageTitle'   => (string) ( $ctx['pageTitle'] ?? wp_get_document_title() ),
		'title'       => (string) ( $ctx['pageTitle'] ?? wp_get_document_title() ),
		'url'         => (string) ( $ctx['url'] ?? home_url( '/' ) ),
		'currentURL'  => (string) ( $ctx['url'] ?? home_url( '/' ) ),
		'siteTitle'   => (string) ( $ctx['siteTitle'] ?? get_bloginfo( 'name' ) ),
		'siteName'    => (string) ( $ctx['siteTitle'] ?? get_bloginfo( 'name' ) ),
		'date'        => wp_date( get_option( 'date_format' ) ),
		'time'        => wp_date( get_option( 'time_format' ) ),
		'productName' => (string) ( $ctx['productName'] ?? '' ),
		'price'       => (string) ( $ctx['price'] ?? '' ),
		'sku'         => (string) ( $ctx['sku'] ?? '' ),
		'productUrl'  => (string) ( $ctx['productUrl'] ?? '' ),
		'productId'   => (string) ( $ctx['productId'] ?? ( $ctx['postId'] ?? '' ) ),
		'utm_source'  => '',
		'utm_medium'  => '',
		'utm_campaign'=> '',
		'utm_term'    => '',
		'utm_content' => '',
		'gclid'       => '',
		'referrer_url'=> '',
	];
	return $tokens;
}

/**
 * Merge page tokens + field values (fields win on key clash).
 *
 * @param array<string,string> $values   Field map.
 * @param array<string,mixed>  $page_ctx Optional pre-captured page context.
 * @return array<string,string>
 */
function rawnaq_smart_form_template_values( $values, $page_ctx = [] ) {
	$base = rawnaq_smart_form_page_tokens( $page_ctx );
	if ( ! is_array( $values ) ) {
		$values = [];
	}
	return array_merge( $base, $values );
}

/**
 * Replace {token} placeholders in a template.
 *
 * @param string               $template Template string.
 * @param array<string,string> $values   Token map.
 * @return string
 */
function rawnaq_smart_form_fill_template( $template, $values ) {
	$out = (string) $template;
	if ( ! is_array( $values ) ) {
		$values = [];
	}
	foreach ( $values as $key => $val ) {
		$out = str_replace( '{' . $key . '}', (string) $val, $out );
	}
	$out = preg_replace( '/\{[a-zA-Z0-9_-]+\}/', '', $out );
	return trim( (string) $out );
}

/**
 * Layout presets for Elementor / Gutenberg one-click apply.
 *
 * @return array<string, array{label:string, fields:array}>
 */
function rawnaq_smart_form_presets() {
	return [
		'quick_contact' => [
			'label'  => __( 'Quick Contact (First + Last Name, Email, Phone)', 'rawnaq' ),
			'fields' => [
				[ 'field_id' => 'first_name', 'id' => 'first_name', 'type' => 'text', 'label' => __( 'First Name', 'rawnaq' ), 'placeholder' => __( 'Jane', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'last_name', 'id' => 'last_name', 'type' => 'text', 'label' => __( 'Last Name', 'rawnaq' ), 'placeholder' => __( 'Doe', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'email', 'id' => 'email', 'type' => 'email', 'label' => __( 'Email Address', 'rawnaq' ), 'placeholder' => __( 'jane@example.com', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'phone', 'id' => 'phone', 'type' => 'phone', 'label' => __( 'Phone Number', 'rawnaq' ), 'placeholder' => __( '+1 (555) 000-0000', 'rawnaq' ), 'required' => '', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'message', 'id' => 'message', 'type' => 'textarea', 'label' => __( 'Message', 'rawnaq' ), 'placeholder' => __( 'Tell us about your project or inquiry...', 'rawnaq' ), 'required' => 'yes', 'width' => '100', 'step' => '1' ],
			],
		],
		'compact_lead'  => [
			'label'  => __( 'Compact Lead (Hero / Landing Page)', 'rawnaq' ),
			'fields' => [
				[ 'field_id' => 'name', 'id' => 'name', 'type' => 'text', 'label' => __( 'Your Name', 'rawnaq' ), 'placeholder' => __( 'Jane Doe', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'phone', 'id' => 'phone', 'type' => 'phone', 'label' => __( 'Phone / WhatsApp', 'rawnaq' ), 'placeholder' => __( '+1 (555) 000-0000', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'message', 'id' => 'message', 'type' => 'textarea', 'label' => __( 'How can we help you?', 'rawnaq' ), 'placeholder' => __( 'Briefly describe what you need...', 'rawnaq' ), 'required' => 'yes', 'width' => '100', 'step' => '1' ],
			],
		],
		'b2b_rfp'       => [
			'label'  => __( 'B2B Request for Proposal / Quote (with File Upload)', 'rawnaq' ),
			'fields' => [
				[ 'field_id' => 'full_name', 'id' => 'full_name', 'type' => 'text', 'label' => __( 'Full Name', 'rawnaq' ), 'placeholder' => __( 'Jane Doe', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'work_email', 'id' => 'work_email', 'type' => 'email', 'label' => __( 'Work Email', 'rawnaq' ), 'placeholder' => __( 'jane@company.com', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'company', 'id' => 'company', 'type' => 'text', 'label' => __( 'Company Name', 'rawnaq' ), 'placeholder' => __( 'Acme Corp', 'rawnaq' ), 'required' => '', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'phone', 'id' => 'phone', 'type' => 'phone', 'label' => __( 'Phone Number', 'rawnaq' ), 'placeholder' => __( '+1 (555) 000-0000', 'rawnaq' ), 'required' => '', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'service', 'id' => 'service', 'type' => 'select', 'label' => __( 'Service Required', 'rawnaq' ), 'placeholder' => __( 'Select a service...', 'rawnaq' ), 'options' => "Web Design & Development\nUI/UX Design\nBrand Identity\nCustom Software\nSEO & Growth", 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'budget', 'id' => 'budget', 'type' => 'select', 'label' => __( 'Estimated Budget', 'rawnaq' ), 'placeholder' => __( 'Select budget range...', 'rawnaq' ), 'options' => "$1,000 – $5,000\n$5,000 – $15,000\n$15,000 – $50,000\n$50,000+", 'required' => '', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'project_details', 'id' => 'project_details', 'type' => 'textarea', 'label' => __( 'Project Overview & Goals', 'rawnaq' ), 'placeholder' => __( 'Tell us about your project, timeline, and goals...', 'rawnaq' ), 'required' => 'yes', 'width' => '100', 'step' => '1' ],
				[ 'field_id' => 'attachment', 'id' => 'attachment', 'type' => 'file', 'label' => __( 'Attach Project Brief / RFP (optional)', 'rawnaq' ), 'required' => '', 'width' => '100', 'step' => '1', 'max_mb' => 10, 'accept' => '.pdf,.doc,.docx,.zip,.png,.jpg' ],
			],
		],
		'multi_project' => [
			'label'  => __( 'Multi-Step Project Discovery Planner (3 Steps)', 'rawnaq' ),
			'fields' => [
				[ 'field_id' => 'project_type', 'id' => 'project_type', 'type' => 'select', 'label' => __( 'What type of project is this?', 'rawnaq' ), 'placeholder' => __( 'Select project type...', 'rawnaq' ), 'required' => 'yes', 'width' => '100', 'step' => '1', 'options' => "New Website\nWebsite Redesign\nE-Commerce Store\nWeb Application\nMobile App" ],
				[ 'field_id' => 'budget_tier', 'id' => 'budget_tier', 'type' => 'select', 'label' => __( 'Anticipated Budget Range', 'rawnaq' ), 'placeholder' => __( 'Select your budget...', 'rawnaq' ), 'required' => 'yes', 'width' => '100', 'step' => '1', 'options' => "Under $3,000\n$3,000 – $10,000\n$10,000 – $25,000\n$25,000+" ],
				[ 'field_id' => 'timeline', 'id' => 'timeline', 'type' => 'select', 'label' => __( 'Desired Launch Timeline', 'rawnaq' ), 'placeholder' => __( 'Select timeline...', 'rawnaq' ), 'required' => '', 'width' => '50', 'step' => '2', 'options' => "ASAP (1-2 weeks)\n1 Month\n2-3 Months\nFlexible" ],
				[ 'field_id' => 'start_date', 'id' => 'start_date', 'type' => 'date', 'label' => __( 'Target Start Date', 'rawnaq' ), 'required' => '', 'width' => '50', 'step' => '2' ],
				[ 'field_id' => 'project_description', 'id' => 'project_description', 'type' => 'textarea', 'label' => __( 'Brief Description & Key Requirements', 'rawnaq' ), 'placeholder' => __( 'Describe what you want to achieve...', 'rawnaq' ), 'required' => 'yes', 'width' => '100', 'step' => '2' ],
				[ 'field_id' => 'contact_name', 'id' => 'contact_name', 'type' => 'text', 'label' => __( 'Your Full Name', 'rawnaq' ), 'placeholder' => __( 'Jane Doe', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '3' ],
				[ 'field_id' => 'contact_email', 'id' => 'contact_email', 'type' => 'email', 'label' => __( 'Email Address', 'rawnaq' ), 'placeholder' => __( 'jane@company.com', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '3' ],
				[ 'field_id' => 'contact_phone', 'id' => 'contact_phone', 'type' => 'phone', 'label' => __( 'Phone / WhatsApp Number', 'rawnaq' ), 'placeholder' => __( '+1 (555) 000-0000', 'rawnaq' ), 'required' => '', 'width' => '100', 'step' => '3' ],
			],
		],
		'feedback_review' => [
			'label'  => __( 'Customer Feedback & Star Rating', 'rawnaq' ),
			'fields' => [
				[ 'field_id' => 'satisfaction', 'id' => 'satisfaction', 'type' => 'rating', 'label' => __( 'Overall Experience & Satisfaction', 'rawnaq' ), 'required' => 'yes', 'width' => '100', 'step' => '1', 'rating_max' => '5' ],
				[ 'field_id' => 'highlight', 'id' => 'highlight', 'type' => 'textarea', 'label' => __( 'What did you like the most?', 'rawnaq' ), 'placeholder' => __( 'Share what stood out to you...', 'rawnaq' ), 'required' => 'yes', 'width' => '100', 'step' => '1' ],
				[ 'field_id' => 'suggestions', 'id' => 'suggestions', 'type' => 'textarea', 'label' => __( 'Suggestions for Improvement', 'rawnaq' ), 'placeholder' => __( 'Anything we could do better?', 'rawnaq' ), 'required' => '', 'width' => '100', 'step' => '1' ],
				[ 'field_id' => 'reviewer_name', 'id' => 'reviewer_name', 'type' => 'text', 'label' => __( 'Your Name', 'rawnaq' ), 'placeholder' => __( 'Jane Doe', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'reviewer_email', 'id' => 'reviewer_email', 'type' => 'email', 'label' => __( 'Email Address', 'rawnaq' ), 'placeholder' => __( 'jane@example.com', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '1' ],
			],
		],
		'job_application' => [
			'label'  => __( 'Job Application & Career Inquiry (with Resume Upload)', 'rawnaq' ),
			'fields' => [
				[ 'field_id' => 'applicant_name', 'id' => 'applicant_name', 'type' => 'text', 'label' => __( 'Full Name', 'rawnaq' ), 'placeholder' => __( 'Jane Doe', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'applicant_email', 'id' => 'applicant_email', 'type' => 'email', 'label' => __( 'Email Address', 'rawnaq' ), 'placeholder' => __( 'jane@example.com', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'position', 'id' => 'position', 'type' => 'select', 'label' => __( 'Position Applying For', 'rawnaq' ), 'placeholder' => __( 'Select a position...', 'rawnaq' ), 'options' => "Senior Frontend Developer\nUI/UX Designer\nWordPress Specialist\nFull-Stack Engineer\nDigital Marketer", 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'portfolio_url', 'id' => 'portfolio_url', 'type' => 'url', 'label' => __( 'Portfolio / LinkedIn URL', 'rawnaq' ), 'placeholder' => __( 'https://linkedin.com/in/janedoe', 'rawnaq' ), 'required' => '', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'cover_letter', 'id' => 'cover_letter', 'type' => 'textarea', 'label' => __( 'Why are you a great fit?', 'rawnaq' ), 'placeholder' => __( 'Tell us about your background and experience...', 'rawnaq' ), 'required' => 'yes', 'width' => '100', 'step' => '1' ],
				[ 'field_id' => 'resume_cv', 'id' => 'resume_cv', 'type' => 'file', 'label' => __( 'Upload Resume / CV (PDF or DOCX)', 'rawnaq' ), 'required' => 'yes', 'width' => '100', 'step' => '1', 'max_mb' => 5, 'accept' => '.pdf,.doc,.docx' ],
			],
		],
		// Legacy aliases for backward compatibility (hidden from builder dropdowns)
		'side_by_side'  => [
			'label'    => __( 'Quick Contact (Classic)', 'rawnaq' ),
			'internal' => true,
			'fields'   => [
				[ 'field_id' => 'first_name', 'id' => 'first_name', 'type' => 'text', 'label' => __( 'First Name', 'rawnaq' ), 'placeholder' => __( 'Jane', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'last_name', 'id' => 'last_name', 'type' => 'text', 'label' => __( 'Last Name', 'rawnaq' ), 'placeholder' => __( 'Doe', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'email', 'id' => 'email', 'type' => 'email', 'label' => __( 'Email Address', 'rawnaq' ), 'placeholder' => __( 'jane@example.com', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'phone', 'id' => 'phone', 'type' => 'phone', 'label' => __( 'Phone Number', 'rawnaq' ), 'placeholder' => __( '+1 (555) 000-0000', 'rawnaq' ), 'required' => '', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'message', 'id' => 'message', 'type' => 'textarea', 'label' => __( 'Message', 'rawnaq' ), 'placeholder' => __( 'Tell us about your project...', 'rawnaq' ), 'required' => 'yes', 'width' => '100', 'step' => '1' ],
			],
		],
		'full_contact'  => [
			'label'    => __( 'Full Contact (Company & Subject)', 'rawnaq' ),
			'internal' => true,
			'fields'   => [
				[ 'field_id' => 'name', 'id' => 'name', 'type' => 'text', 'label' => __( 'Name', 'rawnaq' ), 'placeholder' => __( 'Jane Doe', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'email', 'id' => 'email', 'type' => 'email', 'label' => __( 'Email', 'rawnaq' ), 'placeholder' => __( 'jane@example.com', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'phone', 'id' => 'phone', 'type' => 'phone', 'label' => __( 'Phone', 'rawnaq' ), 'placeholder' => __( '+1 (555) 000-0000', 'rawnaq' ), 'required' => '', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'company', 'id' => 'company', 'type' => 'text', 'label' => __( 'Company', 'rawnaq' ), 'placeholder' => __( 'Acme Corp', 'rawnaq' ), 'required' => '', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'subject', 'id' => 'subject', 'type' => 'text', 'label' => __( 'Subject', 'rawnaq' ), 'placeholder' => __( 'General Inquiry', 'rawnaq' ), 'required' => '', 'width' => '100', 'step' => '1' ],
				[ 'field_id' => 'message', 'id' => 'message', 'type' => 'textarea', 'label' => __( 'Message', 'rawnaq' ), 'placeholder' => __( 'How can we help?', 'rawnaq' ), 'required' => 'yes', 'width' => '100', 'step' => '1' ],
			],
		],
	];
}

/**
 * Filtered preset options for builder dropdowns (excludes legacy internal presets).
 *
 * @return array<string, string>
 */
function rawnaq_smart_form_preset_options() {
	$options = [];
	foreach ( rawnaq_smart_form_presets() as $key => $pack ) {
		if ( empty( $pack['internal'] ) ) {
			$options[ $key ] = $pack['label'];
		}
	}
	return $options;
}

/**
 * Master option schema for Rawnaq Smart Form.
 * Single source of truth for options across all builders (Elementor, Divi, Gutenberg).
 *
 * @return array<string, array{type:string, default:mixed, label:string, description?:string, options?:array, category:string}>
 */
function rawnaq_smart_form_option_schema() {
	return [
		'theme'                 => [
			'type'        => 'select',
			'default'     => 'elevated',
			'label'       => __( 'Theme Preset', 'rawnaq' ),
			'description' => __( 'Visual styling preset for the form container and controls.', 'rawnaq' ),
			'options'     => [
				'elevated'  => __( 'Modern Elevated (Default)', 'rawnaq' ),
				'glass'     => __( 'Minimal Glassmorphism', 'rawnaq' ),
				'obsidian'  => __( 'Dark Obsidian / Cyber', 'rawnaq' ),
				'spotlight' => __( 'Editorial Spotlight', 'rawnaq' ),
			],
			'category'    => 'style',
		],
		'fields'                => [
			'type'        => 'array',
			'default'     => [],
			'label'       => __( 'Form Fields', 'rawnaq' ),
			'description' => __( 'List of input fields, types, labels, widths, steps and validation rules.', 'rawnaq' ),
			'category'    => 'content',
		],
		'deliveryEmail'         => [
			'type'        => 'boolean',
			'default'     => true,
			'label'       => __( 'Email Delivery', 'rawnaq' ),
			'description' => __( 'Send email notifications upon form submission.', 'rawnaq' ),
			'category'    => 'delivery',
		],
		'emailTo'               => [
			'type'        => 'string',
			'default'     => '',
			'label'       => __( 'Notification Email', 'rawnaq' ),
			'description' => __( 'Recipient email address (defaults to WordPress admin email if empty).', 'rawnaq' ),
			'category'    => 'delivery',
		],
		'emailSubject'          => [
			'type'        => 'string',
			'default'     => __( 'New website inquiry', 'rawnaq' ),
			'label'       => __( 'Email Subject', 'rawnaq' ),
			'description' => __( 'Subject line for inquiry notification emails. Supports {tokens}.', 'rawnaq' ),
			'category'    => 'delivery',
		],
		'emailHtml'             => [
			'type'        => 'boolean',
			'default'     => true,
			'label'       => __( 'Branded HTML Email', 'rawnaq' ),
			'description' => __( 'Send emails with clean responsive HTML styling instead of plain text.', 'rawnaq' ),
			'category'    => 'delivery',
		],
		'afterSubmit'           => [
			'type'        => 'select',
			'default'     => 'message',
			'label'       => __( 'After Submit Action', 'rawnaq' ),
			'description' => __( 'Action to perform after successful submission.', 'rawnaq' ),
			'options'     => [
				'message'  => __( 'Show thank-you message', 'rawnaq' ),
				'redirect' => __( 'Redirect to URL', 'rawnaq' ),
				'whatsapp' => __( 'Open WhatsApp (visitor)', 'rawnaq' ),
			],
			'category'    => 'actions',
		],
		'redirectUrl'           => [
			'type'        => 'string',
			'default'     => '',
			'label'       => __( 'Redirect URL', 'rawnaq' ),
			'description' => __( 'Target URL when after-submit action is set to redirect.', 'rawnaq' ),
			'category'    => 'actions',
		],
		'submitLabel'           => [
			'type'        => 'string',
			'default'     => __( 'Send message', 'rawnaq' ),
			'label'       => __( 'Submit Button Text', 'rawnaq' ),
			'description' => __( 'Label displayed on the submit button.', 'rawnaq' ),
			'category'    => 'content',
		],
		'successMessage'        => [
			'type'        => 'string',
			'default'     => __( 'Message sent successfully.', 'rawnaq' ),
			'label'       => __( 'Success Message', 'rawnaq' ),
			'description' => __( 'Message displayed to user upon successful submission.', 'rawnaq' ),
			'category'    => 'content',
		],
		'errorMessage'          => [
			'type'        => 'string',
			'default'     => __( 'Please fill in the required fields correctly.', 'rawnaq' ),
			'label'       => __( 'Error Message', 'rawnaq' ),
			'description' => __( 'Message displayed when validation fails.', 'rawnaq' ),
			'category'    => 'content',
		],
		'consentEnabled'        => [
			'type'        => 'boolean',
			'default'     => false,
			'label'       => __( 'Consent Checkbox', 'rawnaq' ),
			'description' => __( 'Require user to check a GDPR/privacy consent checkbox before submitting.', 'rawnaq' ),
			'category'    => 'consent',
		],
		'consentText'           => [
			'type'        => 'string',
			'default'     => __( 'I agree to the processing of my data.', 'rawnaq' ),
			'label'       => __( 'Consent Agreement Text', 'rawnaq' ),
			'description' => __( 'Text displayed next to the consent checkbox.', 'rawnaq' ),
			'category'    => 'consent',
		],
		'logSubmissions'        => [
			'type'        => 'boolean',
			'default'     => true,
			'label'       => __( 'Log Submissions in Admin', 'rawnaq' ),
			'description' => __( 'Store form submissions as entries in WordPress admin database.', 'rawnaq' ),
			'category'    => 'submission',
		],
		'celebrationEffect'     => [
			'type'        => 'boolean',
			'default'     => false,
			'label'       => __( 'Celebration Effect (Confetti)', 'rawnaq' ),
			'description' => __( 'Show celebratory confetti animation upon successful submission.', 'rawnaq' ),
			'category'    => 'submission',
		],
		'urlPrefill'            => [
			'type'        => 'boolean',
			'default'     => false,
			'label'       => __( 'Allow URL Prefill', 'rawnaq' ),
			'description' => __( 'Prefill fields from URL query parameters (e.g. ?name=Jane).', 'rawnaq' ),
			'category'    => 'submission',
		],
		'estimator'             => [
			'type'        => 'boolean',
			'default'     => false,
			'label'       => __( 'Price Estimator', 'rawnaq' ),
			'description' => __( 'Dynamically calculate and display estimated total from selected options.', 'rawnaq' ),
			'category'    => 'estimator',
		],
		'estimatorLabel'        => [
			'type'        => 'string',
			'default'     => __( 'Estimated total', 'rawnaq' ),
			'label'       => __( 'Estimator Label', 'rawnaq' ),
			'description' => __( 'Label displayed preceding the calculated estimate.', 'rawnaq' ),
			'category'    => 'estimator',
		],
		'estimatorCurrency'     => [
			'type'        => 'string',
			'default'     => '$',
			'label'       => __( 'Currency Symbol', 'rawnaq' ),
			'description' => __( 'Currency prefix or symbol for the price estimator.', 'rawnaq' ),
			'category'    => 'estimator',
		],
		'recaptchaEnabled'      => [
			'type'        => 'boolean',
			'default'     => false,
			'label'       => __( 'reCAPTCHA v3', 'rawnaq' ),
			'description' => __( 'Protect the form using Google reCAPTCHA v3 invisible score verification.', 'rawnaq' ),
			'category'    => 'security',
		],
		'webhookEnabled'        => [
			'type'        => 'boolean',
			'default'     => false,
			'label'       => __( 'Webhook Delivery', 'rawnaq' ),
			'description' => __( 'Forward submissions to an HTTPS endpoint or Slack incoming webhook.', 'rawnaq' ),
			'category'    => 'integrations',
		],
		'webhookUrl'            => [
			'type'        => 'string',
			'default'     => '',
			'label'       => __( 'Webhook URL', 'rawnaq' ),
			'description' => __( 'HTTPS destination URL for webhook payloads.', 'rawnaq' ),
			'category'    => 'integrations',
		],
		'crmProvider'           => [
			'type'        => 'select',
			'default'     => 'none',
			'label'       => __( 'CRM / ESP Provider', 'rawnaq' ),
			'description' => __( 'Automatically sync contact info to an external CRM or ESP.', 'rawnaq' ),
			'options'     => [
				'none'      => __( 'None', 'rawnaq' ),
				'mailchimp' => __( 'Mailchimp', 'rawnaq' ),
				'hubspot'   => __( 'HubSpot', 'rawnaq' ),
			],
			'category'    => 'integrations',
		],
		'crmAudience'           => [
			'type'        => 'string',
			'default'     => '',
			'label'       => __( 'CRM Audience ID / Form GUID', 'rawnaq' ),
			'description' => __( 'Mailchimp Audience ID or HubSpot Form GUID.', 'rawnaq' ),
			'category'    => 'integrations',
		],
		'mailchimpStatus'       => [
			'type'        => 'select',
			'default'     => 'pending',
			'label'       => __( 'Mailchimp Subscriber Status', 'rawnaq' ),
			'description' => __( 'Initial status for new subscribers.', 'rawnaq' ),
			'options'     => [
				'pending'    => __( 'Pending (Double Opt-In)', 'rawnaq' ),
				'subscribed' => __( 'Subscribed (Single Opt-In)', 'rawnaq' ),
			],
			'category'    => 'integrations',
		],
		'autoResponderEnabled'  => [
			'type'        => 'boolean',
			'default'     => false,
			'label'       => __( 'Send Confirmation Email', 'rawnaq' ),
			'description' => __( 'Send an automated confirmation email to the submitter.', 'rawnaq' ),
			'category'    => 'autoresponder',
		],
		'autoResponderSubject'  => [
			'type'        => 'string',
			'default'     => __( 'We received your message', 'rawnaq' ),
			'label'       => __( 'Confirmation Subject', 'rawnaq' ),
			'description' => __( 'Subject line of the confirmation email sent to the submitter.', 'rawnaq' ),
			'category'    => 'autoresponder',
		],
		'autoResponderMessage'  => [
			'type'        => 'textarea',
			'default'     => __( "Hi {first_name},\n\nThank you for reaching out to us. We have received your submission and will get back to you shortly.\n\nBest regards,\n{site_title}", 'rawnaq' ),
			'label'       => __( 'Confirmation Message', 'rawnaq' ),
			'description' => __( 'Body of the confirmation email. Supports {tokens}.', 'rawnaq' ),
			'category'    => 'autoresponder',
		],
		'autoResponderFromName' => [
			'type'        => 'string',
			'default'     => '',
			'label'       => __( 'Confirmation From Name', 'rawnaq' ),
			'description' => __( 'Custom sender name (defaults to site title).', 'rawnaq' ),
			'category'    => 'autoresponder',
		],
		'waNumber'              => [
			'type'        => 'string',
			'default'     => '',
			'label'       => __( 'WhatsApp Delivery Number', 'rawnaq' ),
			'description' => __( 'Phone number with country code for WhatsApp redirect.', 'rawnaq' ),
			'category'    => 'delivery',
		],
		'waTemplate'            => [
			'type'        => 'textarea',
			'default'     => "New inquiry:\nName: {name}\nPhone: {phone}\nEmail: {email}\nMessage: {message}\nPage: {pageTitle}\nURL: {url}",
			'label'       => __( 'WhatsApp Message Template', 'rawnaq' ),
			'description' => __( 'Template used to format prefilled WhatsApp messages.', 'rawnaq' ),
			'category'    => 'delivery',
		],
		'deliveryWhatsapp'      => [
			'type'        => 'boolean',
			'default'     => false,
			'label'       => __( 'WhatsApp Delivery', 'rawnaq' ),
			'description' => __( 'Legacy toggle for WhatsApp delivery.', 'rawnaq' ),
			'category'    => 'delivery',
		],
		'buttonFullWidth'       => [
			'type'        => 'boolean',
			'default'     => false,
			'label'       => __( 'Full Width Button', 'rawnaq' ),
			'description' => __( 'Stretch submit button to 100% width.', 'rawnaq' ),
			'category'    => 'style',
		],
		'inputSize'             => [
			'type'        => 'select',
			'default'     => 'md',
			'label'       => __( 'Input Size', 'rawnaq' ),
			'description' => __( 'Padding and font size for form inputs.', 'rawnaq' ),
			'options'     => [
				'sm' => __( 'Small', 'rawnaq' ),
				'md' => __( 'Medium (Default)', 'rawnaq' ),
				'lg' => __( 'Large', 'rawnaq' ),
			],
			'category'    => 'style',
		],
		'buttonBg'              => [
			'type'        => 'color',
			'default'     => '#fbbf24',
			'label'       => __( 'Button Background Color', 'rawnaq' ),
			'description' => __( 'Accent background color for submit button.', 'rawnaq' ),
			'category'    => 'style',
		],
		'buttonText'            => [
			'type'        => 'color',
			'default'     => '#92400e',
			'label'       => __( 'Button Text Color', 'rawnaq' ),
			'description' => __( 'Text color for submit button.', 'rawnaq' ),
			'category'    => 'style',
		],
		'labelColor'            => [
			'type'        => 'color',
			'default'     => '',
			'label'       => __( 'Label Color', 'rawnaq' ),
			'description' => __( 'Custom color for field labels.', 'rawnaq' ),
			'category'    => 'style',
		],
		'inputBg'               => [
			'type'        => 'color',
			'default'     => '',
			'label'       => __( 'Input Background Color', 'rawnaq' ),
			'description' => __( 'Custom background color for inputs.', 'rawnaq' ),
			'category'    => 'style',
		],
		'inputBorder'           => [
			'type'        => 'color',
			'default'     => '',
			'label'       => __( 'Input Border Color', 'rawnaq' ),
			'description' => __( 'Custom border color for inputs.', 'rawnaq' ),
			'category'    => 'style',
		],
		'inputText'             => [
			'type'        => 'color',
			'default'     => '',
			'label'       => __( 'Input Text Color', 'rawnaq' ),
			'description' => __( 'Custom text color for inputs.', 'rawnaq' ),
			'category'    => 'style',
		],
		'radius'                => [
			'type'        => 'string',
			'default'     => '12px',
			'label'       => __( 'Border Radius', 'rawnaq' ),
			'description' => __( 'Corner rounding for form inputs and cards.', 'rawnaq' ),
			'category'    => 'style',
		],
		'formLabel'             => [
			'type'        => 'string',
			'default'     => '',
			'label'       => __( 'Form Label', 'rawnaq' ),
			'description' => __( 'Friendly name for the form in submissions list.', 'rawnaq' ),
			'category'    => 'meta',
		],
	];
}

/**
 * Preset fields shaped for Elementor repeater.
 *
 * @param string $key Preset key.
 * @return array|null
 */
function rawnaq_smart_form_preset_for_elementor( $key ) {
	$all = rawnaq_smart_form_presets();
	if ( empty( $all[ $key ]['fields'] ) ) {
		return null;
	}
	$fields = [];
	foreach ( $all[ $key ]['fields'] as $f ) {
		$fields[] = [
			'field_id'      => $f['field_id'] ?? $f['id'],
			'type'          => $f['type'] ?? 'text',
			'label'         => $f['label'] ?? '',
			'placeholder'   => $f['placeholder'] ?? '',
			'required'      => ! empty( $f['required'] ) && 'no' !== $f['required'] ? 'yes' : '',
			'options'       => $f['options'] ?? '',
			'width'         => $f['width'] ?? '100',
			'step'          => (string) ( $f['step'] ?? '1' ),
			'show_if'       => $f['show_if'] ?? '',
			'show_if_value' => $f['show_if_value'] ?? '',
			'default_value' => $f['default_value'] ?? '',
			'max_mb'        => $f['max_mb'] ?? '5',
			'accept'        => $f['accept'] ?? '',
			'rating_max'    => $f['rating_max'] ?? '5',
			'help_text'     => $f['help_text'] ?? ( $f['helpText'] ?? '' ),
		];
	}
	return [ 'fields' => $fields ];
}

/**
 * Normalize field list from builder config.
 *
 * @param array $fields Raw fields.
 * @return array<int, array<string, mixed>>
 */
function rawnaq_smart_form_normalize_fields( $fields ) {
	$out  = [];
	$seen = [];
	if ( ! is_array( $fields ) ) {
		return $out;
	}
	$allowed = [ 'text', 'email', 'phone', 'textarea', 'select', 'checkbox', 'date', 'number', 'url', 'hidden', 'rating', 'file', 'cards', 'radio', 'range', 'slider', 'signature' ];
	foreach ( $fields as $i => $f ) {
		$type = sanitize_key( $f['type'] ?? 'text' );
		if ( ! in_array( $type, $allowed, true ) ) {
			$type = 'text';
		}
		$id = sanitize_key( $f['id'] ?? ( $f['field_id'] ?? '' ) );
		if ( ! $id ) {
			$id = 'field_' . ( $i + 1 );
		}
		$base = $id;
		$n    = 2;
		while ( isset( $seen[ $id ] ) ) {
			$id = $base . '_' . $n;
			$n++;
		}
		$seen[ $id ] = true;
		$options_raw = $f['options'] ?? '';
		if ( is_array( $options_raw ) ) {
			$options = array_values( array_filter( array_map( 'sanitize_text_field', $options_raw ), static function ( $v ) {
				return '' !== $v;
			} ) );
		} else {
			$options_str = (string) $options_raw;
			if ( false !== strpos( $options_str, "\n" ) || false !== strpos( $options_str, "\r" ) ) {
				$parts = preg_split( '/\r\n|\r|\n/', $options_str );
			} else {
				$parts = explode( ',', $options_str );
			}
			$parts   = array_map( 'trim', (array) $parts );
			$options = array_values( array_filter( array_map( 'sanitize_text_field', $parts ), static function ( $v ) {
				return '' !== $v;
			} ) );
		}
		$width = sanitize_key( (string) ( $f['width'] ?? '100' ) );
		if ( ! in_array( $width, [ '100', '75', '66', '50', '33', '25' ], true ) ) {
			$width = '100';
		}
		$step = max( 1, absint( $f['step'] ?? 1 ) );
		$out[] = [
			'id'            => $id,
			'type'          => $type,
			'label'         => sanitize_text_field( $f['label'] ?? '' ),
			'helpText'      => sanitize_text_field( $f['helpText'] ?? ( $f['help_text'] ?? ( $f['help'] ?? '' ) ) ),
			'placeholder'   => sanitize_text_field( $f['placeholder'] ?? '' ),
			'required'      => ! empty( $f['required'] ) && 'no' !== $f['required'] && 'false' !== $f['required'],
			'options'       => $options,
			'width'         => $width,
			'step'          => $step,
			'showIf'        => sanitize_key( $f['showIf'] ?? ( $f['show_if'] ?? '' ) ),
			'showIfValue'   => sanitize_text_field( $f['showIfValue'] ?? ( $f['show_if_value'] ?? '' ) ),
			'defaultValue'  => sanitize_text_field( $f['defaultValue'] ?? ( $f['default_value'] ?? '' ) ),
			'countryPicker' => ! empty( $f['country_picker'] ) || ! empty( $f['countryPicker'] ) || 'yes' === ( $f['country_picker'] ?? '' ),
			'min'           => isset( $f['min'] ) ? intval( $f['min'] ) : ( isset( $f['slider_min'] ) ? intval( $f['slider_min'] ) : 0 ),
			'max'           => isset( $f['max'] ) ? intval( $f['max'] ) : ( isset( $f['slider_max'] ) ? intval( $f['slider_max'] ) : 100 ),
			'step_val'      => isset( $f['step_val'] ) ? intval( $f['step_val'] ) : ( isset( $f['slider_step'] ) ? intval( $f['slider_step'] ) : 1 ),
			'unit_prefix'   => sanitize_text_field( $f['unit_prefix'] ?? ( $f['prefix'] ?? '' ) ),
			'unit_suffix'   => sanitize_text_field( $f['unit_suffix'] ?? ( $f['suffix'] ?? '' ) ),
			'maxMb'           => max( 1, min( 25, absint( $f['maxMb'] ?? ( $f['max_mb'] ?? 5 ) ) ) ),
			'accept'          => sanitize_text_field( $f['accept'] ?? '' ),
			'ratingMax'       => max( 3, min( 10, absint( $f['ratingMax'] ?? ( $f['rating_max'] ?? 5 ) ) ) ),
			'optin_mailchimp' => ! empty( $f['optin_mailchimp'] ) || ! empty( $f['optinMailchimp'] ) || 'yes' === ( $f['optin_mailchimp'] ?? '' ),
			'phoneMin'        => isset( $f['phone_min'] ) ? max( 3, absint( $f['phone_min'] ) ) : ( isset( $f['phoneMin'] ) ? max( 3, absint( $f['phoneMin'] ) ) : 6 ),
			'phoneMax'        => isset( $f['phone_max'] ) ? min( 30, absint( $f['phone_max'] ) ) : ( isset( $f['phoneMax'] ) ? min( 30, absint( $f['phoneMax'] ) ) : 16 ),
		];
	}
	return $out;
}

/**
 * Generate accessibility attributes (autocomplete, inputmode) for form fields.
 *
 * @param array $field Normalized field config.
 * @return string HTML attributes string.
 */
function rawnaq_smart_form_field_accessibility_attrs( $field ) {
	$id    = strtolower( (string) ( $field['id'] ?? '' ) );
	$type  = (string) ( $field['type'] ?? 'text' );
	$attrs = [];

	if ( 'email' === $type || false !== strpos( $id, 'email' ) ) {
		$attrs[] = 'autocomplete="email"';
	} elseif ( 'phone' === $type || false !== strpos( $id, 'phone' ) || false !== strpos( $id, 'tel' ) ) {
		$attrs[] = 'autocomplete="tel"';
	} elseif ( 'url' === $type || false !== strpos( $id, 'url' ) || false !== strpos( $id, 'website' ) ) {
		$attrs[] = 'autocomplete="url"';
	} elseif ( false !== strpos( $id, 'first_name' ) || false !== strpos( $id, 'fname' ) || 'firstname' === $id ) {
		$attrs[] = 'autocomplete="given-name"';
	} elseif ( false !== strpos( $id, 'last_name' ) || false !== strpos( $id, 'lname' ) || 'lastname' === $id ) {
		$attrs[] = 'autocomplete="family-name"';
	} elseif ( false !== strpos( $id, 'name' ) && false === strpos( $id, 'company' ) ) {
		$attrs[] = 'autocomplete="name"';
	} elseif ( false !== strpos( $id, 'company' ) || false !== strpos( $id, 'org' ) ) {
		$attrs[] = 'autocomplete="organization"';
	} elseif ( false !== strpos( $id, 'address' ) || false !== strpos( $id, 'street' ) ) {
		$attrs[] = 'autocomplete="street-address"';
	} elseif ( false !== strpos( $id, 'zip' ) || false !== strpos( $id, 'postal' ) ) {
		$attrs[] = 'autocomplete="postal-code"';
	} elseif ( false !== strpos( $id, 'country' ) ) {
		$attrs[] = 'autocomplete="country-name"';
	}

	if ( 'phone' === $type || false !== strpos( $id, 'phone' ) || false !== strpos( $id, 'tel' ) ) {
		$attrs[] = 'inputmode="tel"';
	} elseif ( 'email' === $type ) {
		$attrs[] = 'inputmode="email"';
	} elseif ( 'url' === $type ) {
		$attrs[] = 'inputmode="url"';
	} elseif ( 'number' === $type ) {
		$attrs[] = 'inputmode="numeric"';
	} elseif ( false !== strpos( $id, 'zip' ) || false !== strpos( $id, 'postal' ) ) {
		$attrs[] = 'inputmode="numeric"';
	}

	return $attrs ? ( ' ' . implode( ' ', array_unique( $attrs ) ) ) : '';
}

/**
 * Resolve contact information (email, first_name, last_name, phone) from submission.
 *
 * Checks field definitions and submitted values to identify contact properties
 * across various field presets (first_name, work_email, applicant_name, etc.).
 *
 * @param array<string,mixed> $values     Submitted values.
 * @param array<int,array>    $fields_cfg Normalized field configurations.
 * @return array{email:string,first_name:string,last_name:string,phone:string}
 */
function rawnaq_smart_form_resolve_contact( $values, $fields_cfg = [] ) {
	$values     = is_array( $values ) ? $values : [];
	$fields_cfg = is_array( $fields_cfg ) ? $fields_cfg : [];

	$email      = '';
	$first_name = '';
	$last_name  = '';
	$phone      = '';

	// 1. Email: first field of type 'email'
	foreach ( $fields_cfg as $f ) {
		if ( 'email' === ( $f['type'] ?? '' ) ) {
			$fid = $f['id'] ?? '';
			if ( $fid && ! empty( $values[ $fid ] ) && is_string( $values[ $fid ] ) && is_email( trim( $values[ $fid ] ) ) ) {
				$email = strtolower( trim( $values[ $fid ] ) );
				break;
			}
		}
	}
	// Fallback: search values for common email keys or any valid email
	if ( ! $email ) {
		$email_keys = [ 'email', 'work_email', 'contact_email', 'applicant_email', 'user_email', 'your_email' ];
		foreach ( $email_keys as $k ) {
			if ( ! empty( $values[ $k ] ) && is_string( $values[ $k ] ) && is_email( trim( $values[ $k ] ) ) ) {
				$email = strtolower( trim( $values[ $k ] ) );
				break;
			}
		}
	}
	if ( ! $email ) {
		foreach ( $values as $k => $v ) {
			if ( is_string( $v ) && is_email( trim( $v ) ) ) {
				$email = strtolower( trim( $v ) );
				break;
			}
		}
	}

	// 2. Names:
	// Priority A: Distinct first/last name fields (first_name, last_name, fname, lname)
	$fn_keys = [ 'first_name', 'fname', 'firstname', 'first-name' ];
	$ln_keys = [ 'last_name', 'lname', 'lastname', 'last-name' ];

	foreach ( $fn_keys as $k ) {
		if ( ! empty( $values[ $k ] ) && is_string( $values[ $k ] ) ) {
			$first_name = sanitize_text_field( trim( $values[ $k ] ) );
			break;
		}
	}
	foreach ( $ln_keys as $k ) {
		if ( ! empty( $values[ $k ] ) && is_string( $values[ $k ] ) ) {
			$last_name = sanitize_text_field( trim( $values[ $k ] ) );
			break;
		}
	}

	// Priority B: If first_name not found, check single name/full_name/your_name/contact_name/applicant_name/reviewer_name field split on first space
	if ( ! $first_name ) {
		$full_name_keys = [
			'name', 'full_name', 'your_name', 'contact_name',
			'applicant_name', 'reviewer_name', 'fullname',
		];
		$single_name = '';
		foreach ( $full_name_keys as $k ) {
			if ( ! empty( $values[ $k ] ) && is_string( $values[ $k ] ) ) {
				$single_name = sanitize_text_field( trim( $values[ $k ] ) );
				break;
			}
		}
		if ( ! $single_name ) {
			foreach ( $fields_cfg as $f ) {
				$fid = $f['id'] ?? '';
				if ( ( $f['type'] ?? '' ) === 'text' && preg_match( '/\b(name|applicant|reviewer)\b/i', $fid ) ) {
					if ( ! empty( $values[ $fid ] ) && is_string( $values[ $fid ] ) ) {
						$single_name = sanitize_text_field( trim( $values[ $fid ] ) );
						break;
					}
				}
			}
		}

		if ( $single_name ) {
			$parts      = preg_split( '/\s+/', $single_name, 2 );
			$first_name = $parts[0] ?? '';
			if ( ! $last_name && ! empty( $parts[1] ) ) {
				$last_name = $parts[1];
			}
		}
	}

	// 3. Phone: first field of type 'phone'
	foreach ( $fields_cfg as $f ) {
		if ( 'phone' === ( $f['type'] ?? '' ) ) {
			$fid = $f['id'] ?? '';
			if ( $fid && ! empty( $values[ $fid ] ) ) {
				$phone = sanitize_text_field( trim( (string) $values[ $fid ] ) );
				break;
			}
		}
	}
	if ( ! $phone ) {
		$phone_keys = [ 'phone', 'phone_number', 'telephone', 'mobile', 'tel', 'whatsapp_number', 'contact_phone' ];
		foreach ( $phone_keys as $k ) {
			if ( ! empty( $values[ $k ] ) ) {
				$phone = sanitize_text_field( trim( (string) $values[ $k ] ) );
				break;
			}
		}
	}

	return [
		'email'      => $email,
		'first_name' => $first_name,
		'last_name'  => $last_name,
		'phone'      => $phone,
	];
}

/**
 * Validate submitted field values against schema and server rules.
 *
 * Enforces max lengths, option allowlists for select/radio/cards,
 * number/range min/max/step, rating 1..max, valid Y-m-d dates,
 * and required checkboxes.
 *
 * @param array $fields_cfg Normalized field schema.
 * @param array $posted     Sanitized POST field array.
 * @param array $cfg        Form configuration.
 * @return array{values:array<string,mixed>,errors:array<string,string>}
 */
function rawnaq_smart_form_validate_submission( $fields_cfg, $posted, $cfg ) {
	$values = [];
	$errors = [];

	$max_lengths = apply_filters(
		'rawnaq_smart_form_max_lengths',
		[
			'text'     => 255,
			'email'    => 254,
			'url'      => 2048,
			'textarea' => 5000,
			'phone'    => 50,
		]
	);

	foreach ( $fields_cfg as $field ) {
		if ( 'file' === $field['type'] ) {
			continue;
		}

		$id    = $field['id'];
		$type  = $field['type'];
		$label = ! empty( $field['label'] ) ? $field['label'] : $id;
		$req   = ! empty( $field['required'] );

		// Skip conditional fields that are not visible.
		if ( ! empty( $field['showIf'] ) ) {
			$dep  = isset( $posted[ $field['showIf'] ] ) ? sanitize_text_field( (string) $posted[ $field['showIf'] ] ) : '';
			$want = (string) ( $field['showIfValue'] ?? '' );
			if ( '' !== $want && $dep !== $want ) {
				continue;
			}
		}

		$raw = isset( $posted[ $id ] ) ? $posted[ $id ] : '';
		if ( is_array( $raw ) ) {
			$raw = implode( ', ', array_map( 'strval', $raw ) );
		}
		$raw_str = (string) $raw;

		if ( 'signature' === $type ) {
			if ( $req && '' === trim( $raw_str ) ) {
				$errors[ $id ] = sprintf( /* translators: %s: Field label */ __( '%s is required.', 'rawnaq' ), $label );
				continue;
			}
			if ( '' === trim( $raw_str ) ) {
				$values[ $id ] = '';
				continue;
			}
			$sig_check = rawnaq_smart_form_validate_signature( $raw_str );
			if ( is_wp_error( $sig_check ) ) {
				$errors[ $id ] = $sig_check->get_error_message();
				continue;
			}
			$values[ $id ] = $raw_str;
			continue;
		}

		// Type-specific extraction / sanitization
		if ( 'textarea' === $type ) {
			$val = sanitize_textarea_field( $raw_str );
		} elseif ( 'email' === $type ) {
			$val = sanitize_email( $raw_str );
		} elseif ( 'url' === $type ) {
			$val = esc_url_raw( trim( $raw_str ) );
		} elseif ( 'number' === $type || 'range' === $type || 'slider' === $type ) {
			$val = trim( $raw_str );
		} elseif ( 'hidden' === $type && '' === trim( $raw_str ) ) {
			$val = sanitize_text_field( (string) ( $field['defaultValue'] ?? '' ) );
		} else {
			$val = sanitize_text_field( $raw_str );
		}

		// 1. Required Check
		if ( $req ) {
			if ( 'checkbox' === $type ) {
				if ( '1' !== $val ) {
					$errors[ $id ] = sprintf( /* translators: %s: Field label */ __( '%s is required.', 'rawnaq' ), $label );
					continue;
				}
			} elseif ( '' === $val ) {
				$errors[ $id ] = sprintf( /* translators: %s: Field label */ __( '%s is required.', 'rawnaq' ), $label );
				continue;
			}
		}

		// If value is empty and field was not required, skip further value checks.
		if ( '' === $val ) {
			$values[ $id ] = '';
			continue;
		}

		// 2. Max length checks (filterable)
		$length_type = in_array( $type, [ 'text', 'email', 'url', 'textarea', 'phone' ], true ) ? $type : 'text';
		if ( isset( $max_lengths[ $length_type ] ) ) {
			$limit = absint( $max_lengths[ $length_type ] );
			if ( $limit > 0 && function_exists( 'mb_strlen' ) && mb_strlen( $val, 'UTF-8' ) > $limit ) {
				$errors[ $id ] = sprintf(
					/* translators: 1: Field label, 2: Maximum characters */
					__( '%1$s must not exceed %2$d characters.', 'rawnaq' ),
					$label,
					$limit
				);
				continue;
			}
		}

		// 3. Option value enforcement for select / radio / cards
		if ( in_array( $type, [ 'select', 'radio', 'cards' ], true ) ) {
			$allowed = [];
			foreach ( (array) ( $field['options'] ?? [] ) as $opt ) {
				$opt_sanitized = sanitize_text_field( (string) $opt );
				$allowed[]     = $opt_sanitized;
				if ( false !== strpos( $opt, '|' ) ) {
					$parts     = explode( '|', $opt, 2 );
					$allowed[] = sanitize_text_field( trim( $parts[0] ) );
				}
			}
			if ( ! in_array( $val, $allowed, true ) ) {
				$errors[ $id ] = sprintf(
					/* translators: %s: Field label */
					__( 'Please select a valid option for %s.', 'rawnaq' ),
					$label
				);
				continue;
			}
		}

		// 4. Number / range / slider: enforce min / max / step
		if ( in_array( $type, [ 'number', 'range', 'slider' ], true ) ) {
			if ( ! is_numeric( $val ) ) {
				$errors[ $id ] = sprintf( /* translators: %s: Field label */ __( '%s must be a valid number.', 'rawnaq' ), $label );
				continue;
			}
			$num  = (float) $val;
			$min  = isset( $field['min'] ) && '' !== $field['min'] ? (float) $field['min'] : null;
			$max  = isset( $field['max'] ) && '' !== $field['max'] ? (float) $field['max'] : null;
			$step = isset( $field['step_val'] ) && '' !== $field['step_val'] ? (float) $field['step_val'] : ( isset( $field['step'] ) && '' !== $field['step'] ? (float) $field['step'] : null );

			if ( null !== $min && $num < $min ) {
				$errors[ $id ] = sprintf(
					/* translators: 1: Field label, 2: Minimum number */
					__( '%1$s must be at least %2$s.', 'rawnaq' ),
					$label,
					(string) $min
				);
				continue;
			}
			if ( null !== $max && $num > $max ) {
				$errors[ $id ] = sprintf(
					/* translators: 1: Field label, 2: Maximum number */
					__( '%1$s must not exceed %2$s.', 'rawnaq' ),
					$label,
					(string) $max
				);
				continue;
			}
			if ( null !== $step && $step > 0 ) {
				$base = null !== $min ? $min : 0.0;
				$diff = abs( $num - $base );
				$rem  = fmod( round( $diff, 5 ), round( $step, 5 ) );
				if ( $rem > 0.0001 && abs( $rem - $step ) > 0.0001 ) {
					$errors[ $id ] = sprintf(
						/* translators: 1: Field label, 2: Step value */
						__( '%1$s must be a multiple of %2$s.', 'rawnaq' ),
						$label,
						(string) $step
					);
					continue;
				}
			}
		}

		// 5. Rating: 1..ratingMax
		if ( 'rating' === $type ) {
			$rating_max = max( 1, (int) ( $field['ratingMax'] ?? 5 ) );
			$r_val      = (int) $val;
			if ( ! is_numeric( $val ) || (string) $r_val !== (string) $val || $r_val < 1 || $r_val > $rating_max ) {
				$errors[ $id ] = sprintf(
					/* translators: 1: Field label, 2: Maximum rating */
					__( '%1$s must be between 1 and %2$d.', 'rawnaq' ),
					$label,
					$rating_max
				);
				continue;
			}
		}

		// 6. Date: valid Y-m-d
		if ( 'date' === $type ) {
			$d = DateTime::createFromFormat( 'Y-m-d', $val );
			if ( ! $d || $d->format( 'Y-m-d' ) !== $val ) {
				$errors[ $id ] = sprintf(
					/* translators: %s: Field label */
					__( '%s must be a valid date (YYYY-MM-DD).', 'rawnaq' ),
					$label
				);
				continue;
			}
		}

		// 7. Email format
		if ( 'email' === $type && ! is_email( $val ) ) {
			$errors[ $id ] = sprintf( /* translators: %s: Field label */ __( '%s must be a valid email address.', 'rawnaq' ), $label );
			continue;
		}

		// 8. URL format
		if ( 'url' === $type && ! filter_var( $val, FILTER_VALIDATE_URL ) ) {
			$errors[ $id ] = sprintf( /* translators: %s: Field label */ __( '%s must be a valid URL.', 'rawnaq' ), $label );
			continue;
		}

		// 9. Phone digits and bounds
		if ( 'phone' === $type ) {
			$digits    = preg_replace( '/\D+/', '', $val );
			$len       = strlen( $digits );
			$phone_min = isset( $field['phoneMin'] ) ? (int) $field['phoneMin'] : 6;
			$phone_max = isset( $field['phoneMax'] ) ? (int) $field['phoneMax'] : 16;
			if ( $len < $phone_min || $len > $phone_max ) {
				$errors[ $id ] = sprintf(
					/* translators: %s: Field label */
					__( '%s must be a valid phone number.', 'rawnaq' ),
					$label
				);
				continue;
			}
		}

		$values[ $id ] = $val;
	}

	return [
		'values' => $values,
		'errors' => $errors,
	];
}

/**
 * Max upload size in bytes (site setting, capped by PHP).
 *
 * @return int
 */
function rawnaq_smart_form_max_upload_bytes() {
	$settings = get_option( 'rawnaq_settings', [] );
	$mb       = isset( $settings['sf_max_upload_mb'] ) ? absint( $settings['sf_max_upload_mb'] ) : 5;
	$mb       = max( 1, min( 25, $mb ? $mb : 5 ) );
	$limit    = $mb * MB_IN_BYTES;
	$wp       = wp_max_upload_size();
	return min( $limit, $wp );
}

/**
 * reCAPTCHA keys from settings.
 *
 * @return array{site:string,secret:string}
 */
function rawnaq_smart_form_recaptcha_keys() {
	$settings = get_option( 'rawnaq_settings', [] );
	if ( ! is_array( $settings ) ) {
		$settings = [];
	}
	return [
		'site'   => sanitize_text_field( $settings['recaptcha_site_key'] ?? '' ),
		'secret' => sanitize_text_field( $settings['recaptcha_secret_key'] ?? '' ),
	];
}

/**
 * Verify reCAPTCHA v3 token.
 *
 * @param string $token Token from client.
 * @return bool
 */
function rawnaq_smart_form_verify_recaptcha( $token ) {
	$keys = rawnaq_smart_form_recaptcha_keys();
	if ( ! $keys['secret'] || ! $token ) {
		return false;
	}
	$response = wp_remote_post(
		'https://www.google.com/recaptcha/api/siteverify',
		[
			'timeout' => 8,
			'body'    => [
				'secret'   => $keys['secret'],
				'response' => $token,
				'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			],
		]
	);
	if ( is_wp_error( $response ) ) {
		return false;
	}
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $body['success'] ) ) {
		return false;
	}
	$score     = isset( $body['score'] ) ? (float) $body['score'] : 1.0;
	$min_score = (float) apply_filters( 'rawnaq_smart_form_recaptcha_min_score', 0.4 );
	return $score >= $min_score;
}

/**
 * Allowed MIME types for Smart Form file fields.
 *
 * @return array<string,string> Extension => mime.
 */
function rawnaq_smart_form_allowed_mimes() {
	$mimes = [
		'jpg|jpeg|jpe' => 'image/jpeg',
		'gif'          => 'image/gif',
		'png'          => 'image/png',
		'webp'         => 'image/webp',
		'pdf'          => 'application/pdf',
		'txt'          => 'text/plain',
		'doc'          => 'application/msword',
		'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	];
	/**
	 * Filter Smart Form upload MIME allowlist.
	 *
	 * @param array<string,string> $mimes Extension pattern => MIME type.
	 */
	return apply_filters( 'rawnaq_smart_form_allowed_mimes', $mimes );
}

/**
 * Protected upload directory for Smart Form attachments and signatures.
 *
 * Located at wp_upload_dir()['basedir'] . '/rawnaq-forms/' with index.php,
 * .htaccess (Apache 2.4/2.2 deny), and web.config (IIS deny).
 *
 * @return string Directory path without trailing slash.
 */
function rawnaq_smart_form_get_upload_dir() {
	$upload_dir = wp_upload_dir();
	$base       = $upload_dir['basedir'] . '/rawnaq-forms';

	if ( ! file_exists( $base ) ) {
		wp_mkdir_p( $base );
	}

	$index_file = $base . '/index.php';
	if ( ! file_exists( $index_file ) ) {
		@file_put_contents( $index_file, "<?php\n// Silence is golden.\n" );
	}

	$htaccess = $base . '/.htaccess';
	if ( ! file_exists( $htaccess ) ) {
		$ht_content = "# Apache 2.4+\n"
			. "<IfModule mod_authz_core.c>\n"
			. "Require all denied\n"
			. "</IfModule>\n"
			. "# Apache 2.2\n"
			. "<IfModule !mod_authz_core.c>\n"
			. "Order deny,allow\n"
			. "Deny from all\n"
			. "</IfModule>\n";
		@file_put_contents( $htaccess, $ht_content );
	}

	$web_config = $base . '/web.config';
	if ( ! file_exists( $web_config ) ) {
		$wc_content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
			. "<configuration>\n"
			. "  <system.webServer>\n"
			. "    <authorization>\n"
			. "      <deny users=\"*\" />\n"
			. "    </authorization>\n"
			. "  </system.webServer>\n"
			. "</configuration>\n";
		@file_put_contents( $web_config, $wc_content );
	}

	return untrailingslashit( $base );
}

/**
 * Check whether a filename has a disallowed extension.
 *
 * SVG, HTML, PHP, EXE, JS, etc. are blocked regardless of filters.
 *
 * @param string $filename File name or extension.
 * @return bool
 */
function rawnaq_smart_form_is_blocked_extension( $filename ) {
	$ext = strtolower( pathinfo( (string) $filename, PATHINFO_EXTENSION ) );
	if ( ! $ext ) {
		$ext = strtolower( trim( (string) $filename, '.' ) );
	}
	$blocked = [
		'svg', 'svgz', 'html', 'htm', 'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps',
		'exe', 'js', 'bat', 'cmd', 'sh', 'pl', 'py', 'cgi', 'com', 'vbs', 'scr', 'msi',
	];
	return in_array( $ext, $blocked, true );
}

/**
 * Enforce field 'accept' list on the server.
 *
 * @param string $filename    File name.
 * @param string $mime        Detected MIME type.
 * @param string $accept_spec Comma-separated accept patterns (e.g. '.pdf,.docx' or 'image/*').
 * @return bool
 */
function rawnaq_smart_form_validate_file_accept( $filename, $mime, $accept_spec ) {
	if ( ! $accept_spec || ! is_string( $accept_spec ) ) {
		return true;
	}
	$parts = array_map( 'trim', explode( ',', strtolower( $accept_spec ) ) );
	$parts = array_filter( $parts );
	if ( empty( $parts ) ) {
		return true;
	}

	$ext     = strtolower( pathinfo( (string) $filename, PATHINFO_EXTENSION ) );
	$dot_ext = '.' . $ext;
	$mime    = strtolower( (string) $mime );

	foreach ( $parts as $p ) {
		if ( 0 === strpos( $p, '.' ) ) {
			if ( $p === $dot_ext ) {
				return true;
			}
		} elseif ( false !== strpos( $p, '/*' ) ) {
			$prefix = str_replace( '/*', '', $p );
			if ( 0 === strpos( $mime, $prefix . '/' ) ) {
				return true;
			}
		} elseif ( false !== strpos( $p, '/' ) ) {
			if ( $mime === $p ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Validate signature data URI and image properties without writing to disk.
 *
 * @param string $raw_sig Base64 data URI (data:image/png;base64,...).
 * @return array{decoded:string,size:int,width:int,height:int}|WP_Error
 */
function rawnaq_smart_form_validate_signature( $raw_sig ) {
	if ( ! is_string( $raw_sig ) || 0 !== strpos( $raw_sig, 'data:image/png;base64,' ) ) {
		return new WP_Error( 'invalid_signature_format', __( 'Invalid signature format.', 'rawnaq' ) );
	}
	$b64     = substr( $raw_sig, strlen( 'data:image/png;base64,' ) );
	$decoded = base64_decode( $b64, true );
	if ( false === $decoded ) {
		return new WP_Error( 'invalid_signature_encoding', __( 'Invalid signature data.', 'rawnaq' ) );
	}
	$size = strlen( $decoded );
	if ( $size > 200 * KB_IN_BYTES ) {
		return new WP_Error( 'signature_too_large', __( 'Signature file exceeds 200 KB.', 'rawnaq' ) );
	}
	$info = @getimagesizefromstring( $decoded );
	if ( ! $info || empty( $info[0] ) || empty( $info[1] ) || 'image/png' !== ( $info['mime'] ?? '' ) ) {
		return new WP_Error( 'invalid_signature_image', __( 'Invalid signature image.', 'rawnaq' ) );
	}
	if ( $info[0] > 1000 || $info[1] > 400 ) {
		return new WP_Error( 'signature_dimensions', __( 'Signature dimensions must not exceed 1000x400.', 'rawnaq' ) );
	}
	return [
		'decoded' => $decoded,
		'size'    => $size,
		'width'   => $info[0],
		'height'  => $info[1],
	];
}

/**
 * Save validated signature to protected upload folder.
 *
 * @param string $raw_sig  Base64 data URI.
 * @param string $field_id Field identifier.
 * @return array{filename:string,full_path:string,size:int}|WP_Error
 */
function rawnaq_smart_form_save_signature( $raw_sig, $field_id = 'signature' ) {
	$validated = rawnaq_smart_form_validate_signature( $raw_sig );
	if ( is_wp_error( $validated ) ) {
		return $validated;
	}
	$custom_dir = rawnaq_smart_form_get_upload_dir();
	$filename   = wp_generate_password( 12, false, false ) . '-sig-' . sanitize_key( $field_id ) . '.png';
	$full_path  = $custom_dir . '/' . $filename;
	$written    = @file_put_contents( $full_path, $validated['decoded'] );
	if ( false === $written ) {
		return new WP_Error( 'signature_save_failed', __( 'Could not save signature file.', 'rawnaq' ) );
	}
	return [
		'filename'  => $filename,
		'full_path' => $full_path,
		'size'      => $validated['size'],
	];
}

/**
 * Maximum total attachment size for emails before sending download links instead (bytes).
 *
 * @return int
 */
function rawnaq_smart_form_max_attachments_size() {
	return (int) apply_filters( 'rawnaq_smart_form_max_attachments_size', 10 * MB_IN_BYTES );
}

/**
 * Generate secure admin download URL for an uploaded file or signature.
 *
 * @param int    $entry_id Entry ID.
 * @param string $field_id Field ID.
 * @param bool   $inline   Whether to preview inline (for images).
 * @return string
 */
function rawnaq_smart_form_get_download_url( $entry_id, $field_id, $inline = false ) {
	$entry_id = absint( $entry_id );
	$field_id = sanitize_key( $field_id );
	$url      = admin_url( 'admin-post.php?action=rawnaq_sf_download&entry=' . $entry_id . '&field=' . $field_id . '&_wpnonce=' . wp_create_nonce( 'rawnaq_sf_download_' . $entry_id . '_' . $field_id ) );
	if ( $inline ) {
		$url = add_query_arg( 'inline', '1', $url );
	}
	return $url;
}

/**
 * Delete uploaded files and signatures associated with an entry.
 *
 * @param int $post_id Entry post ID.
 */
function rawnaq_smart_form_delete_entry_files( $post_id ) {
	$files = get_post_meta( $post_id, '_rawnaq_sf_files', true );
	if ( ! is_array( $files ) || empty( $files ) ) {
		return;
	}
	$upload_dir = rawnaq_smart_form_get_upload_dir();
	$real_dir   = realpath( $upload_dir );
	foreach ( $files as $file_info ) {
		$rel_path = basename( $file_info['path'] ?? '' );
		if ( ! $rel_path ) {
			continue;
		}
		$abs_path  = $upload_dir . '/' . $rel_path;
		$real_file = realpath( $abs_path );
		if ( $real_file && 0 === strpos( $real_file, $real_dir ) && is_file( $real_file ) ) {
			@unlink( $real_file );
		}
	}
}

add_action( 'before_delete_post', static function( $post_id ) {
	if ( 'rawnaq_sf_entry' === get_post_type( $post_id ) ) {
		rawnaq_smart_form_delete_entry_files( $post_id );
	}
} );

/**
 * Whether a webhook URL is safe to call (HTTPS + no private/loopback hosts).
 *
 * @param string $url Candidate URL.
 * @return bool
 */
function rawnaq_smart_form_is_safe_webhook_url( $url ) {
	$url = esc_url_raw( $url );
	if ( ! $url || 0 !== stripos( $url, 'https://' ) ) {
		return false;
	}
	$parts = wp_parse_url( $url );
	if ( empty( $parts['host'] ) || ! empty( $parts['user'] ) || ! empty( $parts['pass'] ) ) {
		return false;
	}
	$host = strtolower( $parts['host'] );

	/**
	 * Filter allow-listed webhook hosts to bypass private IP / loopback blocks.
	 *
	 * @param string[] $allowed_hosts Array of hostnames (e.g. ['localhost', 'webhook.internal']).
	 */
	$allowed_hosts = apply_filters( 'rawnaq_smart_form_allowed_webhook_hosts', [] );
	if ( is_array( $allowed_hosts ) && in_array( $host, array_map( 'strtolower', (array) $allowed_hosts ), true ) ) {
		return true;
	}

	if ( in_array( $host, [ 'localhost', '127.0.0.1', '::1', '0.0.0.0' ], true ) ) {
		return false;
	}
	if ( preg_match( '/\.local$|\.internal$|\.localhost$/i', $host ) ) {
		return false;
	}
	// Literal IPv4 — block private / link-local / reserved ranges.
	if ( filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
		return (bool) filter_var(
			$host,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		);
	}
	// Literal IPv6 — block private / reserved.
	if ( filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
		return (bool) filter_var(
			$host,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		);
	}
	// Hostname: resolve once and reject private targets (basic SSRF guard).
	$ips = [];
	if ( function_exists( 'dns_get_record' ) ) {
		$a = @dns_get_record( $host, DNS_A ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( is_array( $a ) ) {
			foreach ( $a as $row ) {
				if ( ! empty( $row['ip'] ) ) {
					$ips[] = $row['ip'];
				}
			}
		}
	}
	if ( ! $ips ) {
		$resolved = gethostbyname( $host );
		if ( $resolved && $resolved !== $host ) {
			$ips[] = $resolved;
		}
	}
	foreach ( $ips as $ip ) {
		$ok = filter_var(
			$ip,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		);
		if ( ! $ok ) {
			return false;
		}
	}
	return true;
}

/**
 * Retrieve or generate per-site webhook HMAC secret.
 *
 * @return string 32-character secret.
 */
function rawnaq_smart_form_webhook_secret() {
	$settings = get_option( 'rawnaq_settings', [] );
	if ( ! is_array( $settings ) ) {
		$settings = [];
	}
	if ( empty( $settings['webhook_secret'] ) ) {
		$secret                     = wp_generate_password( 32, false, false );
		$settings['webhook_secret'] = $secret;
		update_option( 'rawnaq_settings', $settings );
		return $secret;
	}
	return sanitize_text_field( $settings['webhook_secret'] );
}

/**
 * POST JSON to webhook / Slack incoming webhook.
 *
 * @param string              $url     Endpoint.
 * @param array<string,mixed> $payload Payload.
 * @return true|WP_Error
 */
function rawnaq_smart_form_send_webhook( $url, $payload ) {
	$url = esc_url_raw( $url );
	if ( ! $url || ! rawnaq_smart_form_is_safe_webhook_url( $url ) ) {
		return new WP_Error( 'invalid_url', __( 'Invalid or unsafe webhook URL.', 'rawnaq' ) );
	}
	$is_slack    = ( false !== strpos( $url, 'hooks.slack.com' ) );
	$body        = $payload;
	$delivery_id = ! empty( $payload['delivery_id'] ) ? sanitize_text_field( $payload['delivery_id'] ) : ( 'rwq_' . wp_generate_uuid4() );

	if ( $is_slack ) {
		$lines = [];
		foreach ( (array) ( $payload['fields'] ?? [] ) as $k => $v ) {
			$lines[] = '*' . $k . '*: ' . ( is_array( $v ) ? implode( ', ', $v ) : (string) $v );
		}
		$body = [
			'text' => ( $payload['subject'] ?? 'New Smart Form submission' ) . "\n" . implode( "\n", $lines ),
		];
	} else {
		$body['delivery_id'] = $delivery_id;
	}

	$json_body = wp_json_encode( $body );
	$secret    = rawnaq_smart_form_webhook_secret();
	$signature = $secret ? hash_hmac( 'sha256', (string) $json_body, $secret ) : '';

	$headers = [
		'Content-Type'         => 'application/json',
		'X-Rawnaq-Delivery-ID' => $delivery_id,
	];
	if ( $signature ) {
		$headers['X-Rawnaq-Signature'] = $signature;
	}

	$res = wp_remote_post(
		$url,
		[
			'timeout'            => 8,
			'redirection'        => 0,
			'reject_unsafe_urls' => true,
			'headers'            => $headers,
			'body'               => $json_body,
		]
	);
	if ( is_wp_error( $res ) ) {
		return new WP_Error( 'webhook_failed', $res->get_error_message() );
	}
	$code = wp_remote_retrieve_response_code( $res );
	if ( $code >= 400 ) {
		return new WP_Error( 'webhook_http_error', sprintf( /* translators: %d: HTTP status code */ __( 'Webhook endpoint responded with HTTP %d.', 'rawnaq' ), $code ) );
	}
	return true;
}

/**
 * Build a branded HTML email body for a submission.
 *
 * @param array<string,string> $values     Submitted field values.
 * @param array<string,mixed>  $tpl_vals   Template values (pageTitle, url, …).
 * @param array<string,mixed>  $cfg        Stored form config.
 * @param array<string,mixed>  $files_meta Uploaded files metadata.
 * @param int                  $entry_id   Stored entry ID (for admin download links).
 * @param bool                 $links_only Whether attachments exceeded threshold and require download links.
 * @return string HTML email.
 */
function rawnaq_smart_form_email_html( $values, $tpl_vals, $cfg, $files_meta = [], $entry_id = 0, $links_only = false ) {
	$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$accent    = '#4338ca';
	$heading   = ! empty( $cfg['emailSubject'] ) ? $cfg['emailSubject'] : __( 'New website inquiry', 'rawnaq' );
	if ( function_exists( 'rawnaq_smart_form_fill_template' ) ) {
		$heading = rawnaq_smart_form_fill_template( $heading, $tpl_vals );
	}
	$heading = sanitize_text_field( $heading );

	$rows = '';
	foreach ( $values as $k => $v ) {
		if ( 'consent' === $k ) {
			continue;
		}
		$label = ucwords( str_replace( [ '_', '-' ], ' ', (string) $k ) );

		if ( isset( $files_meta[ $k ] ) && 'signature.png' === ( $files_meta[ $k ]['name'] ?? '' ) ) {
			if ( $links_only && $entry_id ) {
				$dl_url = rawnaq_smart_form_get_download_url( $entry_id, $k );
				$val    = esc_html__( 'Signature attached', 'rawnaq' ) . ' (<a href="' . esc_url( $dl_url ) . '">' . esc_html__( 'Download', 'rawnaq' ) . '</a>)';
			} else {
				$val = esc_html__( 'Signature attached', 'rawnaq' );
			}
		} elseif ( isset( $files_meta[ $k ] ) ) {
			$f_name = esc_html( $files_meta[ $k ]['name'] ?? (string) $v );
			if ( $links_only && $entry_id ) {
				$dl_url = rawnaq_smart_form_get_download_url( $entry_id, $k );
				$val    = $f_name . ' (<a href="' . esc_url( $dl_url ) . '">' . esc_html__( 'Download from admin', 'rawnaq' ) . '</a>)';
			} else {
				$val = $f_name;
			}
		} else {
			$val_str = is_array( $v ) ? implode( ', ', array_map( 'strval', $v ) ) : (string) $v;
			$val     = nl2br( esc_html( $val_str ) );
		}

		$rows .= '<tr>'
			. '<td style="padding:10px 14px;border-bottom:1px solid #ececf5;font-weight:600;color:#1e1b2e;vertical-align:top;width:34%;">' . esc_html( $label ) . '</td>'
			. '<td style="padding:10px 14px;border-bottom:1px solid #ececf5;color:#444;">' . $val . '</td>'
			. '</tr>';
	}

	$page_title = esc_html( (string) ( $tpl_vals['pageTitle'] ?? '' ) );
	$page_url   = esc_url( (string) ( $tpl_vals['url'] ?? '' ) );

	ob_start();
	?>
	<div style="background:#f4f4f9;padding:24px 0;font-family:'Segoe UI',Helvetica,Arial,sans-serif;">
		<div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #ececf5;">
			<div style="background:<?php echo esc_attr( $accent ); ?>;padding:20px 24px;">
				<div style="color:#ffffff;font-size:18px;font-weight:700;"><?php echo esc_html( $heading ); ?></div>
				<div style="color:rgba(255,255,255,.8);font-size:12px;margin-top:4px;"><?php echo esc_html( $site_name ); ?></div>
			</div>
			<div style="padding:8px 10px 4px;">
				<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:14px;">
					<?php echo $rows; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rows built with esc_html above. ?>
				</table>
			</div>
			<div style="padding:14px 24px 22px;color:#8a8698;font-size:12px;border-top:1px solid #ececf5;">
				<?php if ( $page_title || $page_url ) : ?>
					<div><?php esc_html_e( 'Submitted from:', 'rawnaq' ); ?>
						<?php if ( $page_url ) : ?>
							<a href="<?php echo esc_url( $page_url ); ?>" style="color:<?php echo esc_attr( $accent ); ?>;"><?php echo $page_title ? esc_html( $page_title ) : esc_html( $page_url ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $page_title ); ?>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<div style="margin-top:4px;"><?php echo esc_html( sprintf( /* translators: %s: site name */ __( 'Sent by %s via Rawnaq Smart Form', 'rawnaq' ), $site_name ) ); ?></div>
			</div>
		</div>
	</div>
	<?php
	$html = ob_get_clean();

	/**
	 * Filter the Smart Form HTML email body.
	 *
	 * @param string $html     Rendered HTML.
	 * @param array  $values   Submitted values.
	 * @param array  $tpl_vals Template values.
	 * @param array  $cfg      Form config.
	 */
	return apply_filters( 'rawnaq_smart_form_email_html', $html, $values, $tpl_vals, $cfg );
}

/**
 * Mailchimp API key from site settings.
 *
 * @return string
 */
function rawnaq_smart_form_mailchimp_key() {
	$settings = get_option( 'rawnaq_settings', [] );
	if ( ! is_array( $settings ) ) {
		$settings = [];
	}
	return sanitize_text_field( $settings['mailchimp_api_key'] ?? '' );
}

/**
 * Subscribe an email to a Mailchimp audience (list).
 *
 * Uses the datacenter suffix embedded in the API key (…-usX).
 *
 * @param string $email       Subscriber email.
 * @param string $audience_id Mailchimp audience/list ID.
 * @param array  $merge       Optional merge fields (e.g. FNAME, LNAME, PHONE).
 * @param string $status      Subscriber status: 'subscribed' or 'pending' (double opt-in).
 * @return true|WP_Error
 */
function rawnaq_smart_form_mailchimp_subscribe( $email, $audience_id, $merge = [], $status = 'pending' ) {
	$key = rawnaq_smart_form_mailchimp_key();
	if ( ! $key || ! $audience_id || ! is_email( $email ) ) {
		return new WP_Error( 'mailchimp_config', __( 'Mailchimp API key, audience ID or subscriber email missing.', 'rawnaq' ) );
	}
	$parts = explode( '-', $key );
	$dc    = end( $parts );
	if ( ! $dc || $dc === $key ) {
		return new WP_Error( 'mailchimp_key', __( 'Invalid Mailchimp API key format.', 'rawnaq' ) );
	}
	$status = in_array( $status, [ 'subscribed', 'pending' ], true ) ? $status : 'pending';
	$hash   = md5( strtolower( $email ) );
	$url    = 'https://' . rawurlencode( $dc ) . '.api.mailchimp.com/3.0/lists/' . rawurlencode( $audience_id ) . '/members/' . $hash;
	$res    = wp_remote_request(
		$url,
		[
			'method'  => 'PUT',
			'timeout' => 8,
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => 'apikey ' . $key,
			],
			'body'    => wp_json_encode( [
				'email_address' => $email,
				'status_if_new' => $status,
				'status'        => $status,
				'merge_fields'  => (object) array_map( 'strval', $merge ),
			] ),
		]
	);
	if ( is_wp_error( $res ) ) {
		return new WP_Error( 'mailchimp_failed', $res->get_error_message() );
	}
	$code = wp_remote_retrieve_response_code( $res );
	if ( $code >= 400 ) {
		return new WP_Error( 'mailchimp_http_error', sprintf( /* translators: %d: HTTP status code */ __( 'Mailchimp responded with HTTP %d.', 'rawnaq' ), $code ) );
	}
	return true;
}

/**
 * HubSpot portal ID from site settings.
 *
 * @return string
 */
function rawnaq_smart_form_hubspot_portal() {
	$settings = get_option( 'rawnaq_settings', [] );
	if ( ! is_array( $settings ) ) {
		$settings = [];
	}
	return sanitize_text_field( $settings['hubspot_portal_id'] ?? '' );
}

/**
 * Submit a lead to a HubSpot form (Forms API v3 — no API key needed, uses
 * the public portal + form GUID pair).
 *
 * @param string               $email     Contact email.
 * @param string               $form_guid HubSpot form GUID.
 * @param array<string,mixed>  $values    Raw submitted values (name/phone/message…).
 * @param array<string,string> $contact   Resolved contact info (first_name, last_name, phone).
 * @param array<string,mixed>  $page_ctx  Trusted page context (url, pageTitle).
 * @return true|WP_Error
 */
function rawnaq_smart_form_hubspot_submit( $email, $form_guid, $values, $contact = [], $page_ctx = [] ) {
	$portal = rawnaq_smart_form_hubspot_portal();
	if ( ! $portal || ! $form_guid || ! is_email( $email ) ) {
		return new WP_Error( 'hubspot_config', __( 'HubSpot portal ID, form GUID or contact email missing.', 'rawnaq' ) );
	}
	$fields = [ [ 'name' => 'email', 'value' => $email ] ];

	if ( ! empty( $contact['first_name'] ) ) {
		$fields[] = [ 'name' => 'firstname', 'value' => (string) $contact['first_name'] ];
	}
	if ( ! empty( $contact['last_name'] ) ) {
		$fields[] = [ 'name' => 'lastname', 'value' => (string) $contact['last_name'] ];
	}
	if ( ! empty( $contact['phone'] ) ) {
		$fields[] = [ 'name' => 'phone', 'value' => (string) $contact['phone'] ];
	}

	$message_val = '';
	foreach ( [ 'message', 'your_message', 'comment', 'inquiry', 'notes' ] as $mk ) {
		if ( ! empty( $values[ $mk ] ) ) {
			$message_val = (string) $values[ $mk ];
			break;
		}
	}
	if ( $message_val ) {
		$fields[] = [ 'name' => 'message', 'value' => $message_val ];
	}

	$payload = [
		'fields' => $fields,
	];

	$context = [];
	if ( ! empty( $page_ctx['url'] ) ) {
		$context['pageUri'] = (string) $page_ctx['url'];
	} elseif ( ! empty( $page_ctx['pageUri'] ) ) {
		$context['pageUri'] = (string) $page_ctx['pageUri'];
	}
	if ( ! empty( $page_ctx['pageTitle'] ) ) {
		$context['pageName'] = (string) $page_ctx['pageTitle'];
	} elseif ( ! empty( $page_ctx['title'] ) ) {
		$context['pageName'] = (string) $page_ctx['title'];
	} elseif ( ! empty( $page_ctx['pageName'] ) ) {
		$context['pageName'] = (string) $page_ctx['pageName'];
	}
	if ( ! empty( $context ) ) {
		$payload['context'] = $context;
	}

	$url = 'https://api.hsforms.com/submissions/v3/integration/submit/'
		. rawurlencode( $portal ) . '/' . rawurlencode( $form_guid );
	$res = wp_remote_post(
		$url,
		[
			'timeout' => 8,
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( $payload ),
		]
	);
	if ( is_wp_error( $res ) ) {
		return new WP_Error( 'hubspot_failed', $res->get_error_message() );
	}
	$code = wp_remote_retrieve_response_code( $res );
	if ( $code >= 400 ) {
		return new WP_Error( 'hubspot_http_error', sprintf( /* translators: %d: HTTP status code */ __( 'HubSpot responded with HTTP %d.', 'rawnaq' ), $code ) );
	}
	return true;
}

/**
 * Dispatch a submission to configured CRM/ESP integrations + fire the
 * generic extension hook so third-party code (Zapier, custom) can react.
 *
 * @param array<string,string> $values   Submitted values.
 * @param array<string,mixed>  $tpl_vals Template values.
 * @param array<string,mixed>  $cfg      Form config.
 * @param string               $form_id  Form ID.
 * @return true|WP_Error|string 'off' if not configured, true on success, string status or WP_Error on failure.
 */
function rawnaq_smart_form_dispatch_crm( $values, $tpl_vals, $cfg, $form_id ) {
	$fields_cfg = function_exists( 'rawnaq_smart_form_normalize_fields' ) ? rawnaq_smart_form_normalize_fields( $cfg['fields'] ?? [] ) : [];
	$contact    = function_exists( 'rawnaq_smart_form_resolve_contact' )
		? rawnaq_smart_form_resolve_contact( $values, $fields_cfg )
		: [ 'email' => '', 'first_name' => '', 'last_name' => '', 'phone' => '' ];
	$email = $contact['email'];

	$provider = $cfg['crmProvider'] ?? '';
	$crm_res  = 'off';

	if ( 'mailchimp' === $provider ) {
		if ( empty( $cfg['crmAudience'] ) || ! $email ) {
			$crm_res = new WP_Error( 'mailchimp_missing', __( 'Mailchimp audience ID or email missing.', 'rawnaq' ) );
		} else {
			// Explicit opt-in check:
			// 1) A checkbox field flagged 'optin_mailchimp' is ticked
			// 2) OR the form consent checkbox is enabled and ticked
			$has_optin = false;
			foreach ( $fields_cfg as $f ) {
				if ( 'checkbox' === ( $f['type'] ?? '' ) && ! empty( $f['optin_mailchimp'] ) ) {
					$fval = $values[ $f['id'] ] ?? '';
					if ( '1' === (string) $fval || 'yes' === (string) $fval || 'true' === (string) $fval || ! empty( $fval ) ) {
						$has_optin = true;
						break;
					}
				}
			}
			if ( ! $has_optin && ! empty( $cfg['consentEnabled'] ) ) {
				$c_val = $values['consent'] ?? '';
				if ( 'yes' === $c_val || '1' === (string) $c_val ) {
					$has_optin = true;
				}
			}

			if ( ! $has_optin ) {
				$crm_res = 'skipped: no opt-in';
			} else {
				$merge = [];
				if ( ! empty( $contact['first_name'] ) ) {
					$merge['FNAME'] = $contact['first_name'];
				}
				if ( ! empty( $contact['last_name'] ) ) {
					$merge['LNAME'] = $contact['last_name'];
				}
				if ( ! empty( $contact['phone'] ) ) {
					$merge['PHONE'] = $contact['phone'];
				}
				$status  = sanitize_key( $cfg['mailchimpStatus'] ?? 'pending' );
				$crm_res = rawnaq_smart_form_mailchimp_subscribe( $email, $cfg['crmAudience'], $merge, $status );
			}
		}
	} elseif ( 'hubspot' === $provider ) {
		if ( empty( $cfg['crmAudience'] ) ) {
			$crm_res = new WP_Error( 'hubspot_missing', __( 'HubSpot form GUID missing.', 'rawnaq' ) );
		} elseif ( ! $email ) {
			// Skip cleanly when no email exists
			$crm_res = 'skipped: no email';
		} else {
			$crm_res = rawnaq_smart_form_hubspot_submit( $email, $cfg['crmAudience'], $values, $contact, $cfg['pageCtx'] ?? [] );
		}
	}

	/**
	 * Fires after a Smart Form submission is processed — extension point for
	 * CRM/ESP integrations (HubSpot, Zapier bridges, custom sync, etc.).
	 *
	 * @param array  $values   Submitted values.
	 * @param array  $tpl_vals Template values (pageTitle, url).
	 * @param array  $cfg      Form config.
	 * @param string $form_id  Form ID.
	 */
	do_action( 'rawnaq_smart_form_submission', $values, $tpl_vals, $cfg, $form_id );
	return $crm_res;
}

/**
 * Handle uploaded files for file-type fields.
 *
 * Stores uploads in protected folder wp_upload_dir()['basedir'] . '/rawnaq-forms/'
 * with random 12-character prefix and strict server-side validation.
 * Nonce must already be verified by the AJAX caller (ajax_smart_form_submit).
 *
 * @param array $fields_cfg Normalized fields.
 * @return array{values:array<string,string>,attachments:string[],files_meta:array<string,array>,errors:string[]}
 */
function rawnaq_smart_form_handle_uploads( $fields_cfg ) {
	$values      = [];
	$attachments = [];
	$files_meta  = [];
	$errors      = [];

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified in ajax_smart_form_submit before call.
	if ( empty( $_FILES['sf_files'] ) || ! isset( $_FILES['sf_files']['name'] ) || ! is_array( $_FILES['sf_files']['name'] ) ) {
		return compact( 'values', 'attachments', 'files_meta', 'errors' );
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	$max = rawnaq_smart_form_max_upload_bytes();

	foreach ( $fields_cfg as $field ) {
		if ( 'file' !== $field['type'] ) {
			continue;
		}
		$id = $field['id'];
		if ( empty( $_FILES['sf_files']['name'][ $id ] ) ) {
			if ( ! empty( $field['required'] ) ) {
				$errors[] = $id;
			}
			continue;
		}
		if (
			! isset( $_FILES['sf_files']['type'][ $id ], $_FILES['sf_files']['tmp_name'][ $id ], $_FILES['sf_files']['error'][ $id ], $_FILES['sf_files']['size'][ $id ] )
		) {
			$errors[] = $id;
			continue;
		}
		$raw_name = sanitize_file_name( wp_unslash( $_FILES['sf_files']['name'][ $id ] ) );
		$file     = [
			'name'     => $raw_name,
			'type'     => sanitize_mime_type( wp_unslash( $_FILES['sf_files']['type'][ $id ] ) ),
			'tmp_name' => sanitize_text_field( wp_unslash( $_FILES['sf_files']['tmp_name'][ $id ] ) ),
			'error'    => absint( $_FILES['sf_files']['error'][ $id ] ),
			'size'     => absint( $_FILES['sf_files']['size'][ $id ] ),
		];
		if ( UPLOAD_ERR_OK !== $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ) {
			$errors[] = $id;
			continue;
		}

		// Hard-block dangerous extensions regardless of filters.
		if ( rawnaq_smart_form_is_blocked_extension( $file['name'] ) ) {
			$errors[] = $id;
			continue;
		}

		// Validate with wp_check_filetype_and_ext against allowed MIME types.
		$checked = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], rawnaq_smart_form_allowed_mimes() );
		if ( empty( $checked['ext'] ) || empty( $checked['type'] ) ) {
			$errors[] = $id;
			continue;
		}
		if ( rawnaq_smart_form_is_blocked_extension( $checked['ext'] ) ) {
			$errors[] = $id;
			continue;
		}

		// Enforce field 'accept' list on the server.
		if ( ! empty( $field['accept'] ) && ! rawnaq_smart_form_validate_file_accept( $file['name'], $checked['type'], $field['accept'] ) ) {
			$errors[] = $id;
			continue;
		}

		// Size check against site setting and field max MB.
		$field_max = max( 1, (int) $field['maxMb'] ) * MB_IN_BYTES;
		$cap       = min( $max, $field_max );
		if ( $file['size'] > $cap ) {
			$errors[] = $id;
			continue;
		}

		// Store in protected folder with random 12-character prefix.
		$custom_dir    = rawnaq_smart_form_get_upload_dir();
		$random_prefix = wp_generate_password( 12, false, false ) . '-';
		$disk_name     = $random_prefix . $file['name'];

		$upload_file = [
			'name'     => $disk_name,
			'type'     => $checked['type'],
			'tmp_name' => $file['tmp_name'],
			'error'    => $file['error'],
			'size'     => $file['size'],
		];

		$upload_dir_filter = function( $dirs ) use ( $custom_dir ) {
			$dirs['path']    = $custom_dir;
			$dirs['basedir'] = $custom_dir;
			$dirs['url']     = '';
			$dirs['baseurl'] = '';
			return $dirs;
		};

		add_filter( 'upload_dir', $upload_dir_filter );
		$moved = wp_handle_upload(
			$upload_file,
			[
				'test_form' => false,
				'mimes'     => rawnaq_smart_form_allowed_mimes(),
			]
		);
		remove_filter( 'upload_dir', $upload_dir_filter );

		if ( isset( $moved['error'] ) || empty( $moved['file'] ) ) {
			$errors[] = $id;
			continue;
		}

		$attachments[]     = $moved['file'];
		$values[ $id ]     = $file['name']; // Keep sanitized original name, NEVER a public URL.
		$files_meta[ $id ] = [
			'path'          => basename( $moved['file'] ),
			'original_name' => $file['name'],
			'name'          => $file['name'],
			'mime'          => $checked['type'],
			'size'          => $file['size'],
		];
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	return compact( 'values', 'attachments', 'files_meta', 'errors' );
}

/**
 * Safely discard uploaded files on failed submission.
 * Only deletes files whose realpath is strictly inside rawnaq_smart_form_get_upload_dir().
 *
 * @param array $paths Array of absolute file paths to delete.
 * @return int Number of files successfully unlinked.
 */
function rawnaq_smart_form_discard_files( array $paths ) {
	if ( empty( $paths ) ) {
		return 0;
	}
	$upload_dir = function_exists( 'rawnaq_smart_form_get_upload_dir' )
		? rawnaq_smart_form_get_upload_dir()
		: ( wp_upload_dir()['basedir'] . '/rawnaq-forms' );
	$real_dir   = realpath( $upload_dir );
	if ( ! $real_dir ) {
		return 0;
	}
	$norm_dir = rtrim( wp_normalize_path( $real_dir ), '/' ) . '/';

	$unlinked = 0;
	foreach ( $paths as $path ) {
		if ( ! is_string( $path ) || '' === $path ) {
			continue;
		}
		$real_file = realpath( $path );
		if ( ! $real_file || ! is_file( $real_file ) ) {
			continue;
		}
		$norm_file = wp_normalize_path( $real_file );
		if ( 0 === strpos( $norm_file, $norm_dir ) ) {
			if ( @unlink( $real_file ) ) {
				$unlinked++;
			}
		}
	}
	return $unlinked;
}

/**
 * Register private CPT for form submissions (once).
 */
function rawnaq_smart_form_register_cpt() {
	if ( post_type_exists( 'rawnaq_sf_entry' ) ) {
		return;
	}
	register_post_type(
		'rawnaq_sf_entry',
		[
			'labels'              => [
				'name'          => __( 'Form Submissions', 'rawnaq' ),
				'singular_name' => __( 'Form Submission', 'rawnaq' ),
			],
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'rawnaq',
			'capability_type'     => 'post',
			'capabilities'        => [
				'create_posts' => 'do_not_allow',
			],
			'map_meta_cap'        => true,
			'supports'            => [ 'title', 'editor' ],
			'exclude_from_search' => true,
		]
	);
}

/**
 * Persist a submission for admin review.
 *
 * @param string               $form_id      Form instance id.
 * @param array<string,string> $values       Field map.
 * @param array                $cfg          Form config.
 * @param array                $files_meta   Optional uploaded files metadata.
 * @param array|null           $consent_meta Optional consent info { text, timestamp }.
 * @return int Post ID or 0.
 */
function rawnaq_smart_form_log_submission( $form_id, $values, $cfg, $files_meta = [], $consent_meta = null ) {
	rawnaq_smart_form_register_cpt();

	$fields_cfg = function_exists( 'rawnaq_smart_form_normalize_fields' ) ? rawnaq_smart_form_normalize_fields( $cfg['fields'] ?? [] ) : [];
	$contact    = function_exists( 'rawnaq_smart_form_resolve_contact' )
		? rawnaq_smart_form_resolve_contact( $values, $fields_cfg )
		: [ 'email' => '', 'first_name' => '', 'last_name' => '', 'phone' => '' ];

	$full_name = trim( $contact['first_name'] . ( $contact['last_name'] ? ' ' . $contact['last_name'] : '' ) );
	$title     = $full_name
		? sprintf( /* translators: %s: submitter name */ __( 'Inquiry from %s', 'rawnaq' ), $full_name )
		: __( 'Smart Form submission', 'rawnaq' );
	$title     = sanitize_text_field( $title );

	$lines = [];
	foreach ( $values as $k => $v ) {
		$v_str   = is_array( $v ) ? implode( ', ', array_map( 'strval', $v ) ) : (string) $v;
		$lines[] = $k . ': ' . $v_str;
	}
	$post_id = wp_insert_post(
		[
			'post_type'    => 'rawnaq_sf_entry',
			'post_status'  => 'private',
			'post_title'   => $title,
			'post_content' => implode( "\n", $lines ),
		],
		true
	);
	if ( is_wp_error( $post_id ) || ! $post_id ) {
		return 0;
	}

	// Index lowercase email from contact resolver (or fallback scanning) for fast privacy queries.
	$first_email = ! empty( $contact['email'] ) ? strtolower( trim( $contact['email'] ) ) : '';
	if ( ! $first_email && is_array( $values ) ) {
		foreach ( $values as $v ) {
			if ( is_string( $v ) && is_email( $v ) ) {
				$first_email = strtolower( trim( $v ) );
				break;
			}
		}
	}
	update_post_meta( $post_id, '_rawnaq_sf_email', $first_email );

	$form_label = ! empty( $cfg['formLabel'] ) ? sanitize_text_field( $cfg['formLabel'] ) : '';
	if ( ! $form_label && ! empty( $form_id ) ) {
		$form_label = $form_id;
	}
	update_post_meta( $post_id, '_rawnaq_sf_form_label', $form_label );

	// Register form for admin filter dropdowns
	if ( $form_id ) {
		$reg_forms = get_option( 'rawnaq_sf_registered_forms', [] );
		if ( ! is_array( $reg_forms ) ) {
			$reg_forms = [];
		}
		if ( ! isset( $reg_forms[ $form_id ] ) || $reg_forms[ $form_id ] !== $form_label ) {
			$reg_forms[ $form_id ] = $form_label;
			update_option( 'rawnaq_sf_registered_forms', $reg_forms, false );
		}
	}

	update_post_meta( $post_id, '_rawnaq_sf_form_id', sanitize_text_field( $form_id ) );
	update_post_meta( $post_id, '_rawnaq_sf_values', $values );
	update_post_meta( $post_id, '_rawnaq_sf_unread', '1' );
	update_post_meta( $post_id, '_rawnaq_sf_channels', [
		'email'    => ! empty( $cfg['deliveryEmail'] ),
		'whatsapp' => ! empty( $cfg['deliveryWhatsapp'] ),
	] );

	if ( ! empty( $files_meta ) ) {
		update_post_meta( $post_id, '_rawnaq_sf_files', $files_meta );
	}

	if ( is_array( $consent_meta ) && ! empty( $consent_meta['text'] ) ) {
		update_post_meta( $post_id, '_rawnaq_sf_consent', [
			'text'      => sanitize_text_field( $consent_meta['text'] ),
			'timestamp' => absint( $consent_meta['timestamp'] ?? time() ),
		] );
	}

	return (int) $post_id;
}

/**
 * Count unread submissions.
 *
 * @return int
 */
function rawnaq_smart_form_unread_count() {
	$q = new WP_Query(
		[
			'post_type'      => 'rawnaq_sf_entry',
			'post_status'    => 'private',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			// Intentional unread badge count by meta flag.
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_key'       => '_rawnaq_sf_unread',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'meta_value'     => '1',
			'no_found_rows'  => false,
		]
	);
	return (int) $q->found_posts;
}

/**
 * Backfill _rawnaq_sf_email meta for legacy entries that do not have it.
 */
function rawnaq_smart_form_backfill_emails() {
	$legacy = get_posts( [
		'post_type'      => 'rawnaq_sf_entry',
		'post_status'    => 'any',
		'posts_per_page' => 200,
		'fields'         => 'ids',
		'meta_query'     => [
			[
				'key'     => '_rawnaq_sf_email',
				'compare' => 'NOT EXISTS',
			],
		],
	] );
	foreach ( $legacy as $pid ) {
		$vals  = get_post_meta( $pid, '_rawnaq_sf_values', true );
		$found = '';
		if ( is_array( $vals ) ) {
			foreach ( $vals as $v ) {
				if ( is_string( $v ) && is_email( $v ) ) {
					$found = strtolower( trim( $v ) );
					break;
				}
			}
		}
		update_post_meta( $pid, '_rawnaq_sf_email', $found );
	}
}

/**
 * Whether a stored entry's values contain the given email (case-insensitive fallback).
 *
 * @param array  $values Field values.
 * @param string $email  Email to match.
 * @return bool
 */
function rawnaq_smart_form_entry_has_email( $values, $email ) {
	if ( ! is_array( $values ) ) {
		return false;
	}
	$email = strtolower( trim( $email ) );
	foreach ( $values as $v ) {
		if ( is_string( $v ) && is_email( $v ) && strtolower( $v ) === $email ) {
			return true;
		}
	}
	return false;
}

/**
 * Register the Smart Form personal-data exporter (WP privacy tools).
 *
 * @param array $exporters Registered exporters.
 * @return array
 */
function rawnaq_smart_form_register_exporter( $exporters ) {
	$exporters['rawnaq-smart-form'] = [
		'exporter_friendly_name' => __( 'Rawnaq Smart Form submissions', 'rawnaq' ),
		'callback'               => 'rawnaq_smart_form_privacy_exporter',
	];
	return $exporters;
}
add_filter( 'wp_privacy_personal_data_exporters', 'rawnaq_smart_form_register_exporter' );

/**
 * Export Smart Form submissions tied to an email for WP's data-export tool.
 *
 * @param string $email_address Requested email.
 * @param int    $page          1-based page.
 * @return array{data:array,done:bool}
 */
function rawnaq_smart_form_privacy_exporter( $email_address, $page = 1 ) {
	$email_clean = strtolower( trim( $email_address ) );
	rawnaq_smart_form_backfill_emails();

	$page = max( 1, (int) $page );
	$q    = new WP_Query( [
		'post_type'      => 'rawnaq_sf_entry',
		'post_status'    => 'any',
		'posts_per_page' => 20,
		'paged'          => $page,
		'fields'         => 'ids',
		'no_found_rows'  => false,
		'meta_query'     => [
			[
				'key'     => '_rawnaq_sf_email',
				'value'   => $email_clean,
				'compare' => '=',
			],
		],
	] );

	$export = [];
	foreach ( $q->posts as $pid ) {
		$values     = get_post_meta( $pid, '_rawnaq_sf_values', true ) ?: [];
		$files_meta = get_post_meta( $pid, '_rawnaq_sf_files', true ) ?: [];
		$delivery   = get_post_meta( $pid, '_rawnaq_sf_delivery', true ) ?: [];
		$consent    = get_post_meta( $pid, '_rawnaq_sf_consent', true ) ?: [];

		$data = [];
		foreach ( $values as $k => $v ) {
			if ( isset( $files_meta[ $k ] ) ) {
				$val_str = $files_meta[ $k ]['name'] ?? (string) $v;
			} elseif ( is_string( $v ) && ( 0 === strpos( $v, 'data:' ) || 0 === strpos( $v, 'data:image/' ) ) ) {
				$val_str = '[signature]';
			} else {
				$val_str = is_scalar( $v ) ? (string) $v : wp_json_encode( $v );
			}
			$data[] = [
				'name'  => ucwords( str_replace( [ '_', '-' ], ' ', (string) $k ) ),
				'value' => $val_str,
			];
		}

		// Files list (names only, not contents)
		if ( ! empty( $files_meta ) ) {
			$file_names = [];
			foreach ( $files_meta as $fm ) {
				$file_names[] = $fm['name'] ?? '';
			}
			$data[] = [
				'name'  => __( 'Uploaded files', 'rawnaq' ),
				'value' => implode( ', ', array_filter( $file_names ) ),
			];
		}

		// Delivery info
		if ( ! empty( $delivery ) ) {
			$deliv_parts = [];
			foreach ( [ 'email' => 'Email', 'webhook' => 'Webhook', 'crm' => 'CRM', 'whatsapp' => 'WhatsApp' ] as $ch => $lbl ) {
				if ( isset( $delivery[ $ch ] ) && 'off' !== $delivery[ $ch ] ) {
					$deliv_parts[] = $lbl . ': ' . $delivery[ $ch ];
				}
			}
			if ( $deliv_parts ) {
				$data[] = [
					'name'  => __( 'Delivery status', 'rawnaq' ),
					'value' => implode( ', ', $deliv_parts ),
				];
			}
		}

		// Consent info
		if ( ! empty( $consent['text'] ) ) {
			$data[] = [
				'name'  => __( 'Consent text', 'rawnaq' ),
				'value' => $consent['text'],
			];
			if ( ! empty( $consent['timestamp'] ) ) {
				$data[] = [
					'name'  => __( 'Consent timestamp', 'rawnaq' ),
					'value' => wp_date( 'Y-m-d H:i:s', (int) $consent['timestamp'] ),
				];
			}
		}

		$data[] = [
			'name'  => __( 'Submitted on', 'rawnaq' ),
			'value' => get_the_date( '', $pid ) . ' ' . get_the_time( '', $pid ),
		];

		$export[] = [
			'group_id'    => 'rawnaq_sf',
			'group_label' => __( 'Rawnaq Smart Form submissions', 'rawnaq' ),
			'item_id'     => 'rawnaq-sf-' . $pid,
			'data'        => $data,
		];
	}

	return [
		'data' => $export,
		'done' => $page >= (int) $q->max_num_pages,
	];
}

/**
 * Register the Smart Form personal-data eraser (WP privacy tools).
 *
 * @param array $erasers Registered erasers.
 * @return array
 */
function rawnaq_smart_form_register_eraser( $erasers ) {
	$erasers['rawnaq-smart-form'] = [
		'eraser_friendly_name' => __( 'Rawnaq Smart Form submissions', 'rawnaq' ),
		'callback'             => 'rawnaq_smart_form_privacy_eraser',
	];
	return $erasers;
}
add_filter( 'wp_privacy_personal_data_erasers', 'rawnaq_smart_form_register_eraser' );

/**
 * Erase Smart Form submissions tied to an email for WP's data-erasure tool.
 *
 * Always queries page 1 of remaining matches to prevent the pagination skip bug
 * when deleting items in batches, and removes files and signatures from disk.
 *
 * @param string $email_address Requested email.
 * @param int    $page          1-based page.
 * @return array{items_removed:bool,items_retained:bool,messages:array,done:bool}
 */
function rawnaq_smart_form_privacy_eraser( $email_address, $page = 1 ) {
	$email_clean = strtolower( trim( $email_address ) );
	rawnaq_smart_form_backfill_emails();

	// Fetch up to 20 posts matching this email
	$posts_to_delete = get_posts( [
		'post_type'      => 'rawnaq_sf_entry',
		'post_status'    => 'any',
		'posts_per_page' => 20,
		'fields'         => 'ids',
		'meta_query'     => [
			[
				'key'     => '_rawnaq_sf_email',
				'value'   => $email_clean,
				'compare' => '=',
			],
		],
	] );

	$removed = false;
	foreach ( $posts_to_delete as $pid ) {
		rawnaq_smart_form_delete_entry_files( $pid );
		wp_delete_post( $pid, true );
		$removed = true;
	}

	// Count remaining matches for this email to set done flag accurately
	$remaining_query = new WP_Query( [
		'post_type'      => 'rawnaq_sf_entry',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => false,
		'meta_query'     => [
			[
				'key'     => '_rawnaq_sf_email',
				'value'   => $email_clean,
				'compare' => '=',
			],
		],
	] );
	$remaining_count = (int) $remaining_query->found_posts;

	return [
		'items_removed'  => $removed,
		'items_retained' => false,
		'messages'       => [],
		'done'           => 0 === $remaining_count,
	];
}

/**
 * Option key for durable Smart Form configs (survives object-cache / transient flush).
 */
function rawnaq_smart_form_configs_option_key() {
	return 'rawnaq_sf_configs';
}

/**
 * Transient key for a Smart Form trusted config.
 *
 * @param string $form_id Form instance id.
 * @return string
 */
function rawnaq_smart_form_config_transient_key( $form_id ) {
	return 'rawnaq_sf_cfg_' . md5( (string) $form_id );
}

/**
 * Persist delivery/validation config server-side (never trust client POST cfg).
 *
 * @param string $form_id Form instance id.
 * @param array  $cfg     Trusted config.
 */
function rawnaq_smart_form_store_config( $form_id, $cfg ) {
	if ( ! $form_id || ! is_array( $cfg ) ) {
		return;
	}
	set_transient( rawnaq_smart_form_config_transient_key( $form_id ), $cfg, MONTH_IN_SECONDS );

	$hash = md5( (string) $form_id );
	$all  = get_option( rawnaq_smart_form_configs_option_key(), [] );
	if ( ! is_array( $all ) ) {
		$all = [];
	}
	$all[ $hash ] = [
		'form_id' => sanitize_text_field( $form_id ),
		'cfg'     => $cfg,
		'updated' => time(),
	];
	update_option( rawnaq_smart_form_configs_option_key(), $all, false );
}

/**
 * Load trusted Smart Form config by form id.
 *
 * @param string $form_id Form instance id.
 * @return array|null
 */
function rawnaq_smart_form_get_stored_config( $form_id ) {
	if ( ! $form_id ) {
		return null;
	}
	$all  = get_option( rawnaq_smart_form_configs_option_key(), [] );
	$hash = md5( (string) $form_id );
	if ( is_array( $all ) && isset( $all[ $hash ]['cfg'] ) && is_array( $all[ $hash ]['cfg'] ) ) {
		// Touch updated timestamp on every load/submission.
		$all[ $hash ]['updated'] = time();
		update_option( rawnaq_smart_form_configs_option_key(), $all, false );
		set_transient( rawnaq_smart_form_config_transient_key( $form_id ), $all[ $hash ]['cfg'], MONTH_IN_SECONDS );
		return $all[ $hash ]['cfg'];
	}
	$cfg = get_transient( rawnaq_smart_form_config_transient_key( $form_id ) );
	if ( is_array( $cfg ) ) {
		return $cfg;
	}
	return null;
}

/**
 * Daily cron callback to prune configs untouched for 180 days.
 */
function rawnaq_smart_form_prune_configs() {
	$all = get_option( rawnaq_smart_form_configs_option_key(), [] );
	if ( ! is_array( $all ) || empty( $all ) ) {
		return;
	}
	$cutoff  = time() - ( 180 * DAY_IN_SECONDS );
	$changed = false;
	foreach ( $all as $hash => $entry ) {
		$updated = (int) ( $entry['updated'] ?? 0 );
		if ( $updated < $cutoff ) {
			unset( $all[ $hash ] );
			if ( ! empty( $entry['form_id'] ) ) {
				delete_transient( rawnaq_smart_form_config_transient_key( $entry['form_id'] ) );
			}
			$changed = true;
		}
	}
	if ( $changed ) {
		update_option( rawnaq_smart_form_configs_option_key(), $all, false );
	}
}
add_action( 'rawnaq_smart_form_prune_configs', 'rawnaq_smart_form_prune_configs' );

// Schedule daily cron if not already scheduled.
if ( ! wp_next_scheduled( 'rawnaq_smart_form_prune_configs' ) ) {
	wp_schedule_event( time(), 'daily', 'rawnaq_smart_form_prune_configs' );
}

/**
 * Daily cron callback to prune files and entries according to site retention settings.
 */
function rawnaq_smart_form_daily_cleanup() {
	$settings = get_option( 'rawnaq_settings', [] );
	if ( ! is_array( $settings ) ) {
		return;
	}

	// 1. Delete uploaded files after N days
	$del_files_days = absint( $settings['sf_delete_files_days'] ?? 0 );
	if ( $del_files_days > 0 ) {
		$upload_dir = function_exists( 'rawnaq_smart_form_get_upload_dir' ) ? rawnaq_smart_form_get_upload_dir() : ( wp_upload_dir()['basedir'] . '/rawnaq-forms' );
		if ( is_dir( $upload_dir ) ) {
			$cutoff = time() - ( $del_files_days * DAY_IN_SECONDS );
			$items  = scandir( $upload_dir );
			if ( is_array( $items ) ) {
				$protected = [ '.', '..', '.htaccess', 'index.php', 'web.config' ];
				foreach ( $items as $item ) {
					if ( in_array( $item, $protected, true ) ) {
						continue;
					}
					$file_path = $upload_dir . '/' . $item;
					if ( is_file( $file_path ) && filemtime( $file_path ) < $cutoff ) {
						@unlink( $file_path );
					}
				}
			}
		}
	}

	// 2. Delete entries older than N days
	$del_entries_days = absint( $settings['sf_delete_entries_days'] ?? 0 );
	if ( $del_entries_days > 0 ) {
		$cutoff_date = gmdate( 'Y-m-d H:i:s', time() - ( $del_entries_days * DAY_IN_SECONDS ) );
		$old_posts   = get_posts( [
			'post_type'      => 'rawnaq_sf_entry',
			'post_status'    => 'any',
			'posts_per_page' => 100,
			'fields'         => 'ids',
			'date_query'     => [
				[
					'before' => $cutoff_date,
				],
			],
		] );
		foreach ( $old_posts as $pid ) {
			if ( function_exists( 'rawnaq_smart_form_delete_entry_files' ) ) {
				rawnaq_smart_form_delete_entry_files( $pid );
			}
			wp_delete_post( $pid, true );
		}
	}
}
add_action( 'rawnaq_smart_form_daily_cleanup', 'rawnaq_smart_form_daily_cleanup' );
add_action( 'rawnaq_smart_form_prune_configs', 'rawnaq_smart_form_daily_cleanup' );

if ( ! wp_next_scheduled( 'rawnaq_smart_form_daily_cleanup' ) ) {
	wp_schedule_event( time(), 'daily', 'rawnaq_smart_form_daily_cleanup' );
}

/**
 * Cron runner: rerun failed delivery channels for a submission.
 *
 * @param int $post_id Submission post ID.
 */
function rawnaq_smart_form_run_retry_delivery( $post_id ) {
	$post_id = absint( $post_id );
	if ( ! $post_id || 'rawnaq_sf_entry' !== get_post_type( $post_id ) ) {
		return;
	}
	$attempts = (int) get_post_meta( $post_id, '_rawnaq_sf_retry_attempts', true );
	if ( $attempts >= 3 ) {
		return;
	}
	update_post_meta( $post_id, '_rawnaq_sf_retry_attempts', $attempts + 1 );

	$delivery = get_post_meta( $post_id, '_rawnaq_sf_delivery', true );
	if ( ! is_array( $delivery ) ) {
		return;
	}
	$form_id = get_post_meta( $post_id, '_rawnaq_sf_form_id', true );
	$cfg     = rawnaq_smart_form_get_stored_config( $form_id );
	if ( ! is_array( $cfg ) ) {
		return;
	}
	$values   = get_post_meta( $post_id, '_rawnaq_sf_values', true ) ?: [];
	$tpl_vals = rawnaq_smart_form_template_values( $values, $cfg['pageCtx'] ?? [] );

	$errors = [];

	// Retry Email
	if ( ( $delivery['email'] ?? '' ) === 'fail' ) {
		$to = sanitize_email( $cfg['emailTo'] ?? '' );
		if ( ! $to ) {
			$to = get_option( 'admin_email' );
		}
		$subject = sanitize_text_field( $cfg['emailSubject'] ?? __( 'New website inquiry', 'rawnaq' ) );
		if ( function_exists( 'rawnaq_smart_form_fill_template' ) ) {
			$subject = rawnaq_smart_form_fill_template( $subject, $tpl_vals );
		}

		$files_meta        = get_post_meta( $post_id, '_rawnaq_sf_files', true ) ?: [];
		$retry_attachments = [];
		$total_att_size    = 0;
		if ( ! empty( $files_meta ) ) {
			$upload_dir = function_exists( 'rawnaq_smart_form_get_upload_dir' ) ? rawnaq_smart_form_get_upload_dir() : ( wp_upload_dir()['basedir'] . '/rawnaq-forms' );
			foreach ( $files_meta as $fm ) {
				$fpath = $upload_dir . '/' . basename( $fm['path'] ?? '' );
				if ( file_exists( $fpath ) ) {
					$retry_attachments[] = $fpath;
					$total_att_size     += filesize( $fpath );
				}
			}
		}
		$max_attach_size = function_exists( 'rawnaq_smart_form_max_attachments_size' ) ? rawnaq_smart_form_max_attachments_size() : 10 * MB_IN_BYTES;
		$links_only      = ( $total_att_size > $max_attach_size );
		if ( $links_only ) {
			$retry_attachments = [];
		}

		$use_html = ( ! isset( $cfg['emailHtml'] ) || ! empty( $cfg['emailHtml'] ) ) && function_exists( 'rawnaq_smart_form_email_html' );
		if ( $use_html ) {
			$body    = rawnaq_smart_form_email_html( $values, $tpl_vals, $cfg, $files_meta, $post_id, $links_only );
			$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
		} else {
			$lines = [];
			foreach ( $values as $k => $v ) {
				if ( isset( $files_meta[ $k ] ) && 'signature.png' === ( $files_meta[ $k ]['name'] ?? '' ) ) {
					$display_val = __( 'Signature attached', 'rawnaq' );
				} elseif ( isset( $files_meta[ $k ] ) ) {
					$display_val = $files_meta[ $k ]['name'] ?? (string) $v;
				} else {
					$display_val = is_array( $v ) ? implode( ', ', array_map( 'strval', $v ) ) : (string) $v;
				}
				if ( $links_only && isset( $files_meta[ $k ] ) ) {
					$dl_url       = rawnaq_smart_form_get_download_url( $post_id, $k );
					$display_val .= ' (' . __( 'Download', 'rawnaq' ) . ': ' . $dl_url . ')';
				}
				$lines[] = $k . ': ' . $display_val;
			}
			$body    = implode( "\n", $lines );
			$headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
		}
		$contact = function_exists( 'rawnaq_smart_form_resolve_contact' )
			? rawnaq_smart_form_resolve_contact( $values, $cfg['fields'] ?? [] )
			: [ 'email' => $values['email'] ?? '', 'first_name' => '', 'last_name' => '' ];
		if ( ! empty( $contact['email'] ) && is_email( $contact['email'] ) ) {
			$reply_name = trim( $contact['first_name'] . ' ' . $contact['last_name'] );
			$headers[]  = $reply_name ? 'Reply-To: ' . $reply_name . ' <' . $contact['email'] . '>' : 'Reply-To: ' . $contact['email'];
		}
		$mail_err = '';
		$cb       = function( $wp_err ) use ( &$mail_err ) {
			if ( is_wp_error( $wp_err ) ) {
				$mail_err = $wp_err->get_error_message();
			}
		};
		add_action( 'wp_mail_failed', $cb );
		$mail_ok = wp_mail( $to, $subject, $body, $headers, $retry_attachments );
		remove_action( 'wp_mail_failed', $cb );

		if ( $mail_ok ) {
			$delivery['email'] = 'ok';
		} else {
			$errors[] = 'Email: ' . ( $mail_err ?: __( 'wp_mail returned false.', 'rawnaq' ) );
		}
	}

	// Retry Webhook
	if ( ( $delivery['webhook'] ?? '' ) === 'fail' && ! empty( $cfg['webhookEnabled'] ) && ! empty( $cfg['webhookUrl'] ) ) {
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
			$errors[] = 'Webhook: ' . $wh_res->get_error_message();
		} else {
			$delivery['webhook'] = 'ok';
		}
	}

	// Retry CRM
	if ( ( $delivery['crm'] ?? '' ) === 'fail' && ! empty( $cfg['crmProvider'] ) && 'none' !== $cfg['crmProvider'] ) {
		$crm_res = rawnaq_smart_form_dispatch_crm( $values, $tpl_vals, $cfg, $form_id );
		if ( is_wp_error( $crm_res ) ) {
			$errors[] = 'CRM: ' . $crm_res->get_error_message();
		} else {
			$delivery['crm'] = 'ok';
		}
	}

	$delivery['errors'] = $errors;
	update_post_meta( $post_id, '_rawnaq_sf_delivery', $delivery );
}
add_action( 'rawnaq_sf_retry_delivery', 'rawnaq_smart_form_run_retry_delivery' );

/**
 * Client-safe subset of config (no delivery secrets in HTML/POST).
 *
 * @param array $cfg Trusted config.
 * @return array
 */
function rawnaq_smart_form_public_cfg( $cfg ) {
	return [
		'afterSubmit'       => sanitize_key( $cfg['afterSubmit'] ?? 'message' ),
		'successMessage'    => sanitize_text_field( $cfg['successMessage'] ?? '' ),
		'errorMessage'      => sanitize_text_field( $cfg['errorMessage'] ?? '' ),
		'consentEnabled'    => ! empty( $cfg['consentEnabled'] ),
		'recaptchaEnabled'  => ! empty( $cfg['recaptchaEnabled'] ),
		'multiStep'         => ! empty( $cfg['multiStep'] ),
		'buttonFullWidth'   => ! empty( $cfg['buttonFullWidth'] ),
		'estimator'         => ! empty( $cfg['estimator'] ),
		'estimatorLabel'    => sanitize_text_field( $cfg['estimatorLabel'] ?? ( $cfg['estimator_label'] ?? __( 'Estimated total', 'rawnaq' ) ) ),
		'estimatorCurrency' => sanitize_text_field( $cfg['estimatorCurrency'] ?? ( $cfg['estimator_currency'] ?? '$' ) ),
		'celebrationEffect' => ! empty( $cfg['celebrationEffect'] ),
		'urlPrefill'        => ! empty( $cfg['urlPrefill'] ),
	];
}

/**
 * Render Smart Form markup (Elementor + Gutenberg).
 *
 * @param array  $cfg     Normalized config.
 * @param string $form_id Unique form id.
 */
function rawnaq_smart_form_markup( $cfg, $form_id = '' ) {
	$fields = rawnaq_smart_form_normalize_fields( $cfg['fields'] ?? [] );
	if ( ! $fields ) {
		$pack   = rawnaq_smart_form_preset_for_elementor( 'side_by_side' );
		$fields = rawnaq_smart_form_normalize_fields( $pack['fields'] ?? [] );
	}
	$form_id = $form_id ? sanitize_html_class( $form_id ) : rawnaq_smart_form_make_form_id( 'sf', $cfg );

	$wa_number = rawnaq_smart_form_resolve_wa_number( $cfg['waNumber'] ?? '' );
	$rc_keys   = rawnaq_smart_form_recaptcha_keys();
	$recaptcha = ! empty( $cfg['recaptchaEnabled'] ) && $rc_keys['site'] && $rc_keys['secret'];

	$steps = [];
	foreach ( $fields as $field ) {
		$steps[ (int) $field['step'] ] = true;
	}
	$step_nums  = array_keys( $steps );
	sort( $step_nums, SORT_NUMERIC );
	$multi_step = count( $step_nums ) > 1;

	// Capture trusted page context in the rendering environment (never trust client POST).
	$page_ctx = function_exists( 'rawnaq_get_wa_page_context' ) ? rawnaq_get_wa_page_context() : [];
	$page_id  = get_the_ID() ?: get_queried_object_id();
	if ( $page_id ) {
		$page_ctx['postId'] = (string) $page_id;
		if ( empty( $page_ctx['url'] ) || home_url( '/' ) === $page_ctx['url'] ) {
			$permalink = get_permalink( $page_id );
			if ( $permalink ) {
				$page_ctx['url'] = (string) $permalink;
			}
		}
		if ( empty( $page_ctx['pageTitle'] ) ) {
			$page_ctx['pageTitle'] = get_the_title( $page_id );
		}
	}

	// Celebration effect: default OFF for new forms, backward compat kept ON for existing forms
	$has_celeb_setting = isset( $cfg['celebrationEffect'] ) || isset( $cfg['celebration_effect'] );
	$celebration       = $has_celeb_setting
		? ( ! empty( $cfg['celebrationEffect'] ) || ! empty( $cfg['celebration_effect'] ) || 'yes' === ( $cfg['celebration_effect'] ?? '' ) )
		: true;
	$url_prefill       = ! empty( $cfg['urlPrefill'] ) || ! empty( $cfg['url_prefill'] ) || 'yes' === ( $cfg['url_prefill'] ?? '' );

	$cfg_out = [
		'deliveryEmail'     => ! empty( $cfg['deliveryEmail'] ),
		'deliveryWhatsapp'  => ! empty( $cfg['deliveryWhatsapp'] ),
		'emailTo'           => sanitize_email( $cfg['emailTo'] ?? '' ),
		'emailSubject'      => sanitize_text_field( $cfg['emailSubject'] ?? '' ),
		'waNumber'          => $wa_number,
		'waTemplate'        => sanitize_textarea_field( $cfg['waTemplate'] ?? '' ),
		'afterSubmit'       => sanitize_key( $cfg['afterSubmit'] ?? 'message' ),
		'redirectUrl'       => esc_url_raw( $cfg['redirectUrl'] ?? '' ),
		'successMessage'    => sanitize_text_field( $cfg['successMessage'] ?? '' ),
		'errorMessage'      => sanitize_text_field( $cfg['errorMessage'] ?? '' ),
		'submitLabel'       => sanitize_text_field( $cfg['submitLabel'] ?? '' ),
		'consentEnabled'    => ! empty( $cfg['consentEnabled'] ),
		'consentText'       => sanitize_text_field( $cfg['consentText'] ?? '' ),
		'logSubmissions'    => ! empty( $cfg['logSubmissions'] ),
		'recaptchaEnabled'  => $recaptcha,
		'webhookEnabled'    => ! empty( $cfg['webhookEnabled'] ),
		'webhookUrl'        => ( ! empty( $cfg['webhookUrl'] ) && rawnaq_smart_form_is_safe_webhook_url( $cfg['webhookUrl'] ) )
			? esc_url_raw( $cfg['webhookUrl'] )
			: '',
		'emailHtml'         => ! isset( $cfg['emailHtml'] ) || ! empty( $cfg['emailHtml'] ),
		'crmProvider'       => sanitize_key( $cfg['crmProvider'] ?? 'none' ),
		'crmAudience'       => sanitize_text_field( $cfg['crmAudience'] ?? '' ),
		'buttonFullWidth'   => ! empty( $cfg['buttonFullWidth'] ),
		'multiStep'         => $multi_step,
		'fields'            => $fields,
		'pageCtx'           => $page_ctx,
		'estimator'         => ! empty( $cfg['estimator'] ),
		'estimatorLabel'    => sanitize_text_field( $cfg['estimatorLabel'] ?? ( $cfg['estimator_label'] ?? __( 'Estimated total', 'rawnaq' ) ) ),
		'estimatorCurrency' => sanitize_text_field( $cfg['estimatorCurrency'] ?? ( $cfg['estimator_currency'] ?? '$' ) ),
		'celebrationEffect'     => $celebration,
		'urlPrefill'            => $url_prefill,
		'autoResponderEnabled'  => ! empty( $cfg['autoResponderEnabled'] ),
		'autoResponderSubject'  => sanitize_text_field( $cfg['autoResponderSubject'] ?? '' ),
		'autoResponderMessage'  => sanitize_textarea_field( $cfg['autoResponderMessage'] ?? '' ),
		'autoResponderFromName' => sanitize_text_field( $cfg['autoResponderFromName'] ?? '' ),
		'formLabel'             => sanitize_text_field( $cfg['formLabel'] ?? '' ),
	];
	$theme = sanitize_key( $cfg['theme'] ?? ( $cfg['form_theme'] ?? 'elevated' ) );
	if ( ! in_array( $theme, [ 'elevated', 'glass', 'obsidian', 'dark', 'spotlight' ], true ) ) {
		$theme = 'elevated';
	}
	$cfg_out['theme'] = $theme;
	if ( ! in_array( $cfg_out['afterSubmit'], [ 'message', 'redirect', 'whatsapp' ], true ) ) {
		$cfg_out['afterSubmit'] = 'message';
	}
	$submit  = $cfg_out['submitLabel'] ?: __( 'Send message', 'rawnaq' );
	$success = $cfg_out['successMessage'] ?: __( 'Message sent successfully.', 'rawnaq' );
	$error   = $cfg_out['errorMessage'] ?: __( 'Please fill in the required fields correctly.', 'rawnaq' );
	$cfg_out['successMessage'] = $success;
	$cfg_out['errorMessage']   = $error;

	// Trusted delivery + field schema for AJAX (ignore any client-posted cfg).
	rawnaq_smart_form_store_config( $form_id, $cfg_out );
	$public_cfg = rawnaq_smart_form_public_cfg( $cfg_out );

	$style_vars = '';
	if ( ! empty( $cfg['labelColor'] ) ) {
		$style_vars .= '--sf-label:' . esc_attr( $cfg['labelColor'] ) . ';';
	}
	if ( ! empty( $cfg['inputBg'] ) ) {
		$style_vars .= '--sf-panel:' . esc_attr( $cfg['inputBg'] ) . ';';
	}
	if ( ! empty( $cfg['inputBorder'] ) ) {
		$style_vars .= '--sf-line:' . esc_attr( $cfg['inputBorder'] ) . ';';
	}
	if ( ! empty( $cfg['inputText'] ) ) {
		$style_vars .= '--sf-ink:' . esc_attr( $cfg['inputText'] ) . ';';
	}
	if ( ! empty( $cfg['buttonBg'] ) ) {
		$style_vars .= '--sf-accent:' . esc_attr( $cfg['buttonBg'] ) . ';';
	}
	if ( ! empty( $cfg['buttonText'] ) ) {
		$style_vars .= '--sf-btn-text:' . esc_attr( $cfg['buttonText'] ) . ';';
	}
	if ( ! empty( $cfg['radius'] ) ) {
		$style_vars .= '--sf-radius:' . esc_attr( $cfg['radius'] ) . ';';
	}
	if ( ! empty( $cfg['inputSize'] ) ) {
		$map = [ 'sm' => '10px 12px', 'md' => '12px 14px', 'lg' => '14px 16px' ];
		$pad = $map[ $cfg['inputSize'] ] ?? $map['md'];
		$style_vars .= '--sf-input-pad:' . esc_attr( $pad ) . ';';
	}

	$has_file = false;
	foreach ( $fields as $f ) {
		if ( 'file' === $f['type'] ) {
			$has_file = true;
			break;
		}
	}

	$wrap_class = 'rawnaq-smart-form is-theme-' . $theme . ( ! empty( $cfg_out['buttonFullWidth'] ) ? ' is-btn-full' : '' );
	?>
	<div class="<?php echo esc_attr( $wrap_class ); ?>" data-form-id="<?php echo esc_attr( $form_id ); ?>"<?php echo $style_vars ? ' style="' . esc_attr( $style_vars ) . '"' : ''; ?>>
		<form class="rawnaq-smart-form-el" method="post" novalidate
			data-form-id="<?php echo esc_attr( $form_id ); ?>"
			data-sf="<?php echo esc_attr( wp_json_encode( $public_cfg ) ); ?>"
			data-summary-title="<?php esc_attr_e( 'Review Your Submission', 'rawnaq' ); ?>"
			<?php echo $has_file ? ' enctype="multipart/form-data"' : ''; ?>>

			<?php if ( $multi_step ) : ?>
				<ol class="rawnaq-sf-steps" aria-label="<?php esc_attr_e( 'Form steps', 'rawnaq' ); ?>">
					<div class="rawnaq-sf-progress-track" aria-hidden="true">
						<div class="rawnaq-sf-progress-fill" style="width: 0%;"></div>
					</div>
					<?php foreach ( $step_nums as $si => $sn ) : ?>
						<li class="rawnaq-sf-step-dot<?php echo 0 === $si ? ' is-active' : ''; ?>" data-step="<?php echo esc_attr( (string) $sn ); ?>"<?php echo 0 === $si ? ' aria-current="step"' : ''; ?>>
							<span class="rawnaq-sf-step-num"><?php echo esc_html( (string) ( $si + 1 ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ol>
				<div class="rawnaq-sf-step-announcement rawnaq-sf-sr-only" aria-live="polite" aria-atomic="true"></div>
			<?php endif; ?>

			<?php foreach ( $step_nums as $si => $sn ) : ?>
				<div class="rawnaq-smart-form-fields rawnaq-sf-step-panel<?php echo 0 === $si ? ' is-active' : ''; ?>" data-step="<?php echo esc_attr( (string) $sn ); ?>" <?php echo 0 !== $si ? 'hidden' : ''; ?>>
					<?php
					foreach ( $fields as $field ) :
						if ( (int) $field['step'] !== (int) $sn ) {
							continue;
						}
						rawnaq_smart_form_render_field( $field, $form_id, $error );
					endforeach;

					if ( ! $multi_step && ! empty( $cfg_out['consentEnabled'] ) ) {
						rawnaq_smart_form_render_consent( $cfg_out, $error );
					}
					?>
				</div>
			<?php endforeach; ?>

			<?php if ( $multi_step ) : ?>
				<div class="rawnaq-sf-summary-card" data-summary-last hidden></div>
			<?php endif; ?>

			<?php if ( $multi_step && ! empty( $cfg_out['consentEnabled'] ) ) : ?>
				<div class="rawnaq-sf-consent-wrap" data-consent-last hidden>
					<?php rawnaq_smart_form_render_consent( $cfg_out, $error ); ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $cfg_out['estimator'] ) ) : ?>
			<div class="rawnaq-sf-estimator-card" hidden>
				<div class="rawnaq-sf-estimator-inner">
					<span class="rawnaq-sf-estimator-label"><?php echo esc_html( $cfg_out['estimatorLabel'] ?: __( 'Estimated total', 'rawnaq' ) ); ?>:</span>
					<span class="rawnaq-sf-estimator-total"><?php echo esc_html( $cfg_out['estimatorCurrency'] ?: '$' ); ?>0</span>
				</div>
			</div>
			<?php endif; ?>

			<input class="rawnaq-sf-hp" type="text" name="rawnaq_hp" value="" tabindex="-1" autocomplete="off" aria-hidden="true" />
			<input type="hidden" name="rawnaq_ts" value="<?php echo esc_attr( rawnaq_smart_form_create_ts_token( $form_id ) ); ?>" />
			<?php if ( $recaptcha ) : ?>
				<input type="hidden" name="rawnaq_recaptcha" value="" class="rawnaq-sf-recaptcha" />
			<?php endif; ?>

			<div class="rawnaq-sf-actions">
				<?php if ( $multi_step ) : ?>
					<button type="button" class="rawnaq-sf-prev" hidden><?php esc_html_e( 'Back', 'rawnaq' ); ?></button>
					<button type="button" class="rawnaq-sf-next"><?php esc_html_e( 'Next', 'rawnaq' ); ?></button>
				<?php endif; ?>
				<button type="submit" class="rawnaq-sf-submit"<?php echo $multi_step ? ' hidden' : ''; ?>>
					<span class="rawnaq-sf-spinner" aria-hidden="true"></span>
					<span class="rawnaq-sf-submit-label"><?php echo esc_html( $submit ); ?></span>
				</button>
			</div>

			<div class="rawnaq-sf-status" role="status" aria-live="polite" tabindex="-1">
				<svg class="rawnaq-sf-success-mark" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg>
				<span class="rawnaq-sf-status-text"></span>
			</div>
		</form>
	</div>
	<?php
}

/**
 * Render one field.
 *
 * @param array  $field   Field cfg.
 * @param string $form_id Form id.
 * @param string $error   Error text.
 */
function rawnaq_smart_form_render_field( $field, $form_id, $error ) {
	$id           = $field['id'];
	$type         = $field['type'];
	$label        = $field['label'] ?: $id;
	$ph           = $field['placeholder'];
	$help_text    = ! empty( $field['helpText'] ) ? $field['helpText'] : '';
	$req          = ! empty( $field['required'] );
	$width        = $field['width'] ?? '100';
	$input_id     = $form_id . '-' . $id;
	$described_by = $input_id . '-error' . ( $help_text ? ' ' . $input_id . '-help' : '' );
	$attr_req     = $req ? ' required aria-required="true"' : '';
	$show_if      = $field['showIf'] ?? '';
	$show_val     = $field['showIfValue'] ?? '';
	$cond         = '';
	$a11y_attrs   = rawnaq_smart_form_field_accessibility_attrs( $field );
	if ( $show_if ) {
		$cond = ' data-show-if="' . esc_attr( $show_if ) . '" data-show-if-value="' . esc_attr( $show_val ) . '" hidden';
	}
	$is_group    = in_array( $type, [ 'cards', 'radio', 'rating', 'signature' ], true );
	$field_tag   = $is_group ? 'fieldset' : 'div';
	$field_class = 'rawnaq-sf-field' . ( $is_group ? ' rawnaq-sf-fieldset' : '' );
	?>
	<<?php echo esc_attr( $field_tag ); ?> class="<?php echo esc_attr( $field_class ); ?>" data-field="<?php echo esc_attr( $id ); ?>" data-width="<?php echo esc_attr( $width ); ?>" data-type="<?php echo esc_attr( $type ); ?>"<?php echo $cond; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( $is_group ) : ?>
			<legend class="rawnaq-sf-label" id="<?php echo esc_attr( $input_id ); ?>-label">
				<?php echo esc_html( $label ); ?>
				<?php if ( $req ) : ?><span class="req" aria-hidden="true">*</span><?php endif; ?>
			</legend>
		<?php elseif ( ! in_array( $type, [ 'checkbox', 'hidden' ], true ) ) : ?>
			<label class="rawnaq-sf-label" for="<?php echo esc_attr( $input_id ); ?>" id="<?php echo esc_attr( $input_id ); ?>-label">
				<?php echo esc_html( $label ); ?>
				<?php if ( $req ) : ?><span class="req" aria-hidden="true">*</span><?php endif; ?>
			</label>
		<?php endif; ?>

		<?php if ( 'hidden' === $type ) : ?>
			<input type="hidden" name="sf_<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $field['defaultValue'] ?? '' ); ?>" data-sf-type="hidden" />
		<?php elseif ( 'cards' === $type || 'radio' === $type ) : ?>
			<div class="rawnaq-sf-cards-grid" role="radiogroup" aria-labelledby="<?php echo esc_attr( $input_id ); ?>-label">
				<?php foreach ( $field['options'] as $idx => $opt ) :
					$opt_title = $opt;
					$opt_desc  = '';
					if ( strpos( $opt, '|' ) !== false ) {
						$parts     = explode( '|', $opt, 2 );
						$opt_title = trim( $parts[0] );
						$opt_desc  = trim( $parts[1] );
					}
					$card_id = $input_id . '-' . $idx;
				?>
					<label class="rawnaq-sf-card-option" for="<?php echo esc_attr( $card_id ); ?>">
						<input type="radio" id="<?php echo esc_attr( $card_id ); ?>"
							name="sf_<?php echo esc_attr( $id ); ?>"
							value="<?php echo esc_attr( $opt_title ); ?>"
							data-sf-type="cards"<?php echo $attr_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							aria-describedby="<?php echo esc_attr( $described_by ); ?>" />
						<div class="rawnaq-sf-card-content">
							<span class="rawnaq-sf-card-radio-indicator" aria-hidden="true"></span>
							<div class="rawnaq-sf-card-text">
								<span class="rawnaq-sf-card-title"><?php echo esc_html( $opt_title ); ?></span>
								<?php if ( $opt_desc ) : ?>
									<span class="rawnaq-sf-card-desc"><?php echo esc_html( $opt_desc ); ?></span>
								<?php endif; ?>
							</div>
						</div>
					</label>
				<?php endforeach; ?>
			</div>
		<?php elseif ( 'textarea' === $type ) : ?>
			<div class="rawnaq-sf-input-wrap">
				<textarea class="rawnaq-sf-textarea" id="<?php echo esc_attr( $input_id ); ?>"
					name="sf_<?php echo esc_attr( $id ); ?>" data-sf-type="textarea"
					placeholder="<?php echo esc_attr( $ph ); ?>"<?php echo $attr_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $a11y_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					aria-describedby="<?php echo esc_attr( $described_by ); ?>"></textarea>
				<span class="rawnaq-sf-live-badge" aria-hidden="true"></span>
			</div>
		<?php elseif ( 'select' === $type ) : ?>
			<div class="rawnaq-sf-input-wrap">
				<select class="rawnaq-sf-select" id="<?php echo esc_attr( $input_id ); ?>"
					name="sf_<?php echo esc_attr( $id ); ?>" data-sf-type="select"<?php echo $attr_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					aria-describedby="<?php echo esc_attr( $described_by ); ?>">
					<option value=""><?php echo esc_html( $ph ?: __( 'Select…', 'rawnaq' ) ); ?></option>
					<?php foreach ( $field['options'] as $opt ) : ?>
						<option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		<?php elseif ( 'checkbox' === $type ) : ?>
			<label class="rawnaq-sf-check" for="<?php echo esc_attr( $input_id ); ?>">
				<input type="checkbox" id="<?php echo esc_attr( $input_id ); ?>"
					name="sf_<?php echo esc_attr( $id ); ?>" value="1" data-sf-type="checkbox"<?php echo $attr_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					aria-describedby="<?php echo esc_attr( $described_by ); ?>" />
				<span><?php echo esc_html( $label ); ?><?php if ( $req ) : ?> <span class="req">*</span><?php endif; ?></span>
			</label>
		<?php elseif ( 'rating' === $type ) : ?>
			<div class="rawnaq-sf-rating" data-max="<?php echo esc_attr( (string) $field['ratingMax'] ); ?>" role="radiogroup" aria-labelledby="<?php echo esc_attr( $input_id ); ?>-label">
				<?php for ( $r = 1; $r <= (int) $field['ratingMax']; $r++ ) : ?>
					<button type="button" class="rawnaq-sf-star" role="radio" aria-checked="false" tabindex="<?php echo 1 === $r ? '0' : '-1'; ?>" data-value="<?php echo esc_attr( (string) $r ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: 1: Star value, 2: Max stars */ __( '%1$d out of %2$d stars', 'rawnaq' ), $r, (int) $field['ratingMax'] ) ); ?>">★</button>
				<?php endfor; ?>
				<input type="hidden" id="<?php echo esc_attr( $input_id ); ?>" name="sf_<?php echo esc_attr( $id ); ?>" value="" data-sf-type="rating"<?php echo $attr_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-describedby="<?php echo esc_attr( $described_by ); ?>" />
			</div>
		<?php elseif ( 'phone' === $type && ! empty( $field['countryPicker'] ) ) : ?>
			<div class="rawnaq-sf-phone-wrap">
				<div class="rawnaq-sf-country-select-wrap">
					<select class="rawnaq-sf-country-code" aria-label="<?php esc_attr_e( 'Country dialing code', 'rawnaq' ); ?>">
						<option value="+1" data-flag="🇺🇸">🇺🇸 +1</option>
						<option value="+44" data-flag="🇬🇧">🇬🇧 +44</option>
						<option value="+880" data-flag="🇧🇩" selected>🇧🇩 +880</option>
						<option value="+91" data-flag="🇮🇳">🇮🇳 +91</option>
						<option value="+971" data-flag="🇦🇪">🇦🇪 +971</option>
						<option value="+966" data-flag="🇸🇦">🇸🇦 +966</option>
						<option value="+61" data-flag="🇦🇺">🇦🇺 +61</option>
						<option value="+49" data-flag="🇩🇪">🇩🇪 +49</option>
						<option value="+33" data-flag="🇫🇷">🇫🇷 +33</option>
						<option value="+81" data-flag="🇯🇵">🇯🇵 +81</option>
						<option value="+65" data-flag="🇸🇬">🇸🇬 +65</option>
						<option value="+60" data-flag="🇲🇾">🇲🇾 +60</option>
						<option value="+39" data-flag="🇮🇹">🇮🇹 +39</option>
						<option value="+34" data-flag="🇪🇸">🇪🇸 +34</option>
						<option value="+1" data-flag="🇨🇦">🇨🇦 +1</option>
						<option value="+86" data-flag="🇨🇳">🇨🇳 +86</option>
						<option value="+92" data-flag="🇵🇰">🇵🇰 +92</option>
						<option value="+90" data-flag="🇹🇷">🇹🇷 +90</option>
						<option value="+7" data-flag="🇷🇺">🇷🇺 +7</option>
						<option value="+55" data-flag="🇧🇷">🇧🇷 +55</option>
						<option value="+27" data-flag="🇿🇦">🇿🇦 +27</option>
						<option value="+20" data-flag="🇪🇬">🇪🇬 +20</option>
						<option value="+974" data-flag="🇶🇦">🇶🇦 +974</option>
						<option value="+965" data-flag="🇰🇼">🇰🇼 +965</option>
						<option value="+968" data-flag="🇴🇲">🇴🇲 +968</option>
					</select>
				</div>
				<div class="rawnaq-sf-input-wrap">
					<input class="rawnaq-sf-input rawnaq-sf-phone-input" type="tel"
						id="<?php echo esc_attr( $input_id ); ?>"
						name="sf_<?php echo esc_attr( $id ); ?>"
						data-sf-type="phone"
						data-phone-min="<?php echo esc_attr( (string) ( $field['phoneMin'] ?? 6 ) ); ?>"
						data-phone-max="<?php echo esc_attr( (string) ( $field['phoneMax'] ?? 16 ) ); ?>"
						placeholder="<?php echo esc_attr( $ph ?: '1700-000000' ); ?>"
						value=""
						<?php echo $attr_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo $a11y_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						aria-describedby="<?php echo esc_attr( $described_by ); ?>" />
					<span class="rawnaq-sf-live-badge" aria-hidden="true"></span>
				</div>
			</div>
		<?php elseif ( 'file' === $type ) : ?>
			<div class="rawnaq-sf-dropzone" tabindex="0" role="button" aria-label="<?php esc_attr_e( 'Upload file. Choose a file or drag & drop here', 'rawnaq' ); ?>">
				<svg class="rawnaq-sf-dropzone-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
				<span class="rawnaq-sf-dropzone-text"><?php esc_html_e( 'Choose a file or drag & drop here', 'rawnaq' ); ?></span>
				<span class="rawnaq-sf-dropzone-sub"><?php
					printf(
						/* translators: %d: max MB */
						esc_html__( 'Maximum file size: %d MB', 'rawnaq' ),
						(int) $field['maxMb']
					);
				?></span>
				<input class="rawnaq-sf-file" type="file" id="<?php echo esc_attr( $input_id ); ?>"
					name="sf_files[<?php echo esc_attr( $id ); ?>]" data-sf-type="file"
					data-max-mb="<?php echo esc_attr( (string) $field['maxMb'] ); ?>"
					<?php echo ! empty( $field['accept'] ) ? ' accept="' . esc_attr( $field['accept'] ) . '"' : ''; ?>
					<?php echo $attr_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					aria-describedby="<?php echo esc_attr( $described_by ); ?>" />
			</div>
			<div class="rawnaq-sf-preview-wrap"></div>
		<?php elseif ( 'range' === $type || 'slider' === $type ) :
			$min     = isset( $field['min'] ) ? intval( $field['min'] ) : 0;
			$max     = isset( $field['max'] ) ? intval( $field['max'] ) : 100;
			$step_v  = isset( $field['step_val'] ) ? intval( $field['step_val'] ) : 1;
			$def_v   = ( isset( $field['defaultValue'] ) && '' !== $field['defaultValue'] ) ? intval( $field['defaultValue'] ) : $min;
			$prefix  = (string) ( $field['unit_prefix'] ?? '' );
			$suffix  = (string) ( $field['unit_suffix'] ?? '' );
		?>
			<div class="rawnaq-sf-range-wrap"
				data-min="<?php echo esc_attr( (string) $min ); ?>"
				data-max="<?php echo esc_attr( (string) $max ); ?>"
				data-prefix="<?php echo esc_attr( $prefix ); ?>"
				data-suffix="<?php echo esc_attr( $suffix ); ?>">
				<div class="rawnaq-sf-range-header">
					<span class="rawnaq-sf-range-val"><?php echo esc_html( $prefix . $def_v . $suffix ); ?></span>
				</div>
				<input class="rawnaq-sf-range-input" type="range"
					id="<?php echo esc_attr( $input_id ); ?>"
					name="sf_<?php echo esc_attr( $id ); ?>"
					min="<?php echo esc_attr( (string) $min ); ?>"
					max="<?php echo esc_attr( (string) $max ); ?>"
					step="<?php echo esc_attr( (string) $step_v ); ?>"
					value="<?php echo esc_attr( (string) $def_v ); ?>"
					data-sf-type="range"<?php echo $attr_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					aria-describedby="<?php echo esc_attr( $described_by ); ?>" />
				<div class="rawnaq-sf-range-scale">
					<span><?php echo esc_html( $prefix . $min . $suffix ); ?></span>
					<span><?php echo esc_html( $prefix . $max . $suffix ); ?></span>
				</div>
			</div>
		<?php elseif ( 'signature' === $type ) : ?>
			<div class="rawnaq-sf-signature-wrap" id="sig-wrap-<?php echo esc_attr( $input_id ); ?>">
				<canvas class="rawnaq-sf-signature-canvas" width="500" height="150" role="img" aria-label="<?php esc_attr_e( 'Signature pad', 'rawnaq' ); ?>"></canvas>
				<div class="rawnaq-sf-signature-type-wrap" hidden>
					<input type="text" class="rawnaq-sf-input rawnaq-sf-signature-type-input" placeholder="<?php esc_attr_e( 'Type your full name here...', 'rawnaq' ); ?>" aria-label="<?php esc_attr_e( 'Type your name as signature', 'rawnaq' ); ?>" />
				</div>
				<div class="rawnaq-sf-signature-tools">
					<button type="button" class="rawnaq-sf-signature-toggle-mode" data-mode="draw"><?php esc_html_e( 'Type your name instead', 'rawnaq' ); ?></button>
					<button type="button" class="rawnaq-sf-signature-clear"><?php esc_html_e( 'Clear signature', 'rawnaq' ); ?></button>
				</div>
				<input type="hidden" id="<?php echo esc_attr( $input_id ); ?>"
					name="sf_<?php echo esc_attr( $id ); ?>"
					value=""
					data-sf-type="signature"<?php echo $attr_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					aria-describedby="<?php echo esc_attr( $described_by ); ?>" />
			</div>
		<?php else :
			$html_type = 'text';
			$extra_attrs = '';
			if ( 'email' === $type ) {
				$html_type = 'email';
			} elseif ( 'phone' === $type ) {
				$html_type = 'tel';
				$extra_attrs = sprintf( ' data-phone-min="%d" data-phone-max="%d"', (int) ( $field['phoneMin'] ?? 6 ), (int) ( $field['phoneMax'] ?? 16 ) );
			} elseif ( 'date' === $type ) {
				$html_type = 'date';
			} elseif ( 'number' === $type ) {
				$html_type = 'number';
			} elseif ( 'url' === $type ) {
				$html_type = 'url';
			}
			?>
			<div class="rawnaq-sf-input-wrap">
				<input class="rawnaq-sf-input" type="<?php echo esc_attr( $html_type ); ?>"
					id="<?php echo esc_attr( $input_id ); ?>"
					name="sf_<?php echo esc_attr( $id ); ?>"
					data-sf-type="<?php echo esc_attr( $type ); ?>"
					placeholder="<?php echo esc_attr( $ph ); ?>"
					value="<?php echo 'hidden' === $type ? esc_attr( $field['defaultValue'] ?? '' ) : ''; ?>"
					<?php echo $attr_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo $extra_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo $a11y_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					aria-describedby="<?php echo esc_attr( $described_by ); ?>" />
				<span class="rawnaq-sf-live-badge" aria-hidden="true"></span>
			</div>
		<?php endif; ?>
		<?php if ( $help_text && 'hidden' !== $type ) : ?>
			<p class="rawnaq-sf-help" id="<?php echo esc_attr( $input_id ); ?>-help"><?php echo esc_html( $help_text ); ?></p>
		<?php endif; ?>
		<?php if ( 'hidden' !== $type ) : ?>
			<p class="rawnaq-sf-error" id="<?php echo esc_attr( $input_id ); ?>-error" role="alert"><?php echo esc_html( $error ); ?></p>
		<?php endif; ?>
	</<?php echo esc_attr( $field_tag ); ?>>
	<?php
}

/**
 * Consent checkbox markup.
 *
 * @param array  $cfg_out Config.
 * @param string $error   Error text.
 */
function rawnaq_smart_form_render_consent( $cfg_out, $error ) {
	$consent_id = 'sf-consent-' . wp_unique_id();
	?>
	<div class="rawnaq-sf-field" data-field="consent" data-width="100">
		<label class="rawnaq-sf-check" for="<?php echo esc_attr( $consent_id ); ?>">
			<input type="checkbox" id="<?php echo esc_attr( $consent_id ); ?>" name="sf_consent" value="1" data-sf-type="checkbox" required aria-required="true" aria-describedby="<?php echo esc_attr( $consent_id ); ?>-error" />
			<span><?php echo esc_html( $cfg_out['consentText'] ?: __( 'I agree to the processing of my data.', 'rawnaq' ) ); ?> <span class="req">*</span></span>
		</label>
		<?php if ( ! empty( $cfg_out['autoResponderEnabled'] ) ) : ?>
			<p class="rawnaq-sf-help rawnaq-sf-consent-subtext"><?php esc_html_e( 'You will receive a confirmation email upon submission.', 'rawnaq' ); ?></p>
		<?php endif; ?>
		<p class="rawnaq-sf-error" id="<?php echo esc_attr( $consent_id ); ?>-error" role="alert"><?php echo esc_html( $error ); ?></p>
	</div>
	<?php
}

/**
 * Send an automated confirmation email to the submitter.
 *
 * @param array $values     Submitted sanitized field values.
 * @param array $cfg        Form configuration.
 * @param array $fields_cfg Normalized fields configuration.
 * @return true|string|WP_Error Returns true on success, string status or WP_Error on failure.
 */
function rawnaq_smart_form_send_auto_responder( $values, $cfg, $fields_cfg = [] ) {
	if ( empty( $cfg['autoResponderEnabled'] ) ) {
		return 'off';
	}

	$contact = function_exists( 'rawnaq_smart_form_resolve_contact' )
		? rawnaq_smart_form_resolve_contact( $values, $fields_cfg )
		: [ 'email' => $values['email'] ?? '', 'first_name' => '', 'last_name' => '', 'phone' => '' ];

	$recipient = sanitize_email( $contact['email'] ?? '' );
	if ( ! $recipient || ! is_email( $recipient ) ) {
		return 'skipped: no recipient email';
	}

	$site_title = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$first_name = $contact['first_name'] ?: ( $contact['last_name'] ?: __( 'there', 'rawnaq' ) );
	$full_name  = trim( $contact['first_name'] . ' ' . $contact['last_name'] ) ?: $first_name;
	$form_title = ! empty( $cfg['formLabel'] )
		? sanitize_text_field( $cfg['formLabel'] )
		: ( ! empty( $cfg['emailSubject'] ) ? sanitize_text_field( $cfg['emailSubject'] ) : __( 'Smart Form', 'rawnaq' ) );

	$token_map = [
		'{name}'       => $full_name,
		'{first_name}' => $first_name,
		'{last_name}'  => $contact['last_name'],
		'{email}'      => $recipient,
		'{phone}'      => $contact['phone'] ?? '',
		'{form_title}' => $form_title,
		'{site_title}' => $site_title,
		'{date}'       => wp_date( get_option( 'date_format' ) ),
		'{time}'       => wp_date( get_option( 'time_format' ) ),
	];

	// Map individual submitted field values for token substitution
	foreach ( $values as $k => $v ) {
		if ( ! is_array( $v ) && ! isset( $token_map[ '{' . $k . '}' ] ) ) {
			$v_str = (string) $v;
			if ( 0 !== strpos( $v_str, 'data:image/' ) ) {
				$token_map[ '{' . $k . '}' ] = sanitize_text_field( $v_str );
			}
		}
	}

	// 1. Prepare Subject
	$subject_raw = ! empty( $cfg['autoResponderSubject'] )
		? $cfg['autoResponderSubject']
		: __( 'We received your message', 'rawnaq' );
	$subject = strtr( $subject_raw, $token_map );
	$subject = sanitize_text_field( $subject );

	// 2. Prepare Message Body
	$message_raw = ! empty( $cfg['autoResponderMessage'] )
		? $cfg['autoResponderMessage']
		: __( "Hi {first_name},\n\nThank you for reaching out to us. We have received your submission and will get back to you shortly.\n\nBest regards,\n{site_title}", 'rawnaq' );
	$message = strtr( $message_raw, $token_map );

	// 3. Sender / From headers
	$from_name = ! empty( $cfg['autoResponderFromName'] )
		? sanitize_text_field( $cfg['autoResponderFromName'] )
		: $site_title;
	$admin_email = get_option( 'admin_email' );
	$from_header = 'From: ' . $from_name . ' <' . $admin_email . '>';

	$use_html = ( ! isset( $cfg['emailHtml'] ) || ! empty( $cfg['emailHtml'] ) );
	if ( $use_html ) {
		$body    = rawnaq_smart_form_autoresponder_html( $message, $contact, $cfg, $subject );
		$headers = [
			$from_header,
			'Content-Type: text/html; charset=UTF-8',
		];
	} else {
		$body    = $message;
		$headers = [
			$from_header,
			'Content-Type: text/plain; charset=UTF-8',
		];
	}

	$mail_err_msg   = '';
	$mail_failed_cb = function( $wp_error ) use ( &$mail_err_msg ) {
		if ( is_wp_error( $wp_error ) ) {
			$mail_err_msg = $wp_error->get_error_message();
		}
	};
	add_action( 'wp_mail_failed', $mail_failed_cb );
	$sent = wp_mail( $recipient, $subject, $body, $headers );
	remove_action( 'wp_mail_failed', $mail_failed_cb );

	if ( $sent ) {
		return true;
	}

	return new WP_Error( 'mail_failed', $mail_err_msg ?: __( 'wp_mail failed to send auto-responder email.', 'rawnaq' ) );
}

/**
 * Render responsive HTML template for auto-responder confirmation email.
 *
 * @param string $message Formatted message content.
 * @param array  $contact Contact information.
 * @param array  $cfg     Form configuration.
 * @param string $subject Email subject.
 * @return string HTML email markup.
 */
function rawnaq_smart_form_autoresponder_html( $message, $contact, $cfg, $subject ) {
	$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$accent    = ! empty( $cfg['buttonBg'] ) ? $cfg['buttonBg'] : '#4338ca';

	ob_start();
	?>
	<div style="background:#f4f4f9;padding:28px 0;font-family:'Segoe UI',-apple-system,BlinkMacSystemFont,Roboto,Helvetica,Arial,sans-serif;line-height:1.6;">
		<div style="max-width:580px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
			<div style="background:<?php echo esc_attr( $accent ); ?>;padding:24px 28px;">
				<div style="color:#ffffff;font-size:20px;font-weight:700;letter-spacing:-0.02em;"><?php echo esc_html( $subject ); ?></div>
				<div style="color:rgba(255,255,255,0.85);font-size:13px;margin-top:4px;"><?php echo esc_html( $site_name ); ?></div>
			</div>
			<div style="padding:28px;color:#334155;font-size:15px;line-height:1.7;">
				<?php echo nl2br( esc_html( $message ) ); ?>
			</div>
			<div style="padding:16px 28px;background:#f8fafc;color:#94a3b8;font-size:12px;border-top:1px solid #e2e8f0;">
				<div><?php echo esc_html( sprintf( /* translators: %s: site name */ __( 'This is an automated confirmation from %s. Please do not reply directly if this is an unattended inbox.', 'rawnaq' ), $site_name ) ); ?></div>
			</div>
		</div>
	</div>
	<?php
	$html = ob_get_clean();

	/**
	 * Filter the auto-responder HTML email template.
	 *
	 * @param string $html    Email HTML.
	 * @param string $message Raw message.
	 * @param array  $contact Contact details.
	 * @param array  $cfg     Form config.
	 */
	return apply_filters( 'rawnaq_smart_form_autoresponder_html', $html, $message, $contact, $cfg );
}

