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
 * Page + Woo context tokens for templates (mirrors Floating Dock).
 *
 * @return array<string, string>
 */
function rawnaq_smart_form_page_tokens() {
	$ctx = function_exists( 'rawnaq_get_wa_page_context' ) ? rawnaq_get_wa_page_context() : [];
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
		'productId'   => (string) ( $ctx['productId'] ?? '' ),
	];
	return $tokens;
}

/**
 * Merge page tokens + field values (fields win on key clash).
 *
 * @param array<string,string> $values Field map.
 * @return array<string,string>
 */
function rawnaq_smart_form_template_values( $values ) {
	$base = rawnaq_smart_form_page_tokens();
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
		'side_by_side'  => [
			'label'  => __( 'Name + Email side by side', 'rawnaq' ),
			'fields' => [
				[ 'field_id' => 'name', 'id' => 'name', 'type' => 'text', 'label' => __( 'Name', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'email', 'id' => 'email', 'type' => 'email', 'label' => __( 'Email', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'phone', 'id' => 'phone', 'type' => 'phone', 'label' => __( 'Phone', 'rawnaq' ), 'required' => '', 'width' => '100', 'step' => '1' ],
				[ 'field_id' => 'message', 'id' => 'message', 'type' => 'textarea', 'label' => __( 'Message', 'rawnaq' ), 'required' => 'yes', 'width' => '100', 'step' => '1' ],
			],
		],
		'compact_lead'  => [
			'label'  => __( 'Compact lead', 'rawnaq' ),
			'fields' => [
				[ 'field_id' => 'name', 'id' => 'name', 'type' => 'text', 'label' => __( 'Name', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'phone', 'id' => 'phone', 'type' => 'phone', 'label' => __( 'Phone', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'message', 'id' => 'message', 'type' => 'textarea', 'label' => __( 'How can we help?', 'rawnaq' ), 'required' => 'yes', 'width' => '100', 'step' => '1' ],
			],
		],
		'full_contact'  => [
			'label'  => __( 'Full contact', 'rawnaq' ),
			'fields' => [
				[ 'field_id' => 'name', 'id' => 'name', 'type' => 'text', 'label' => __( 'Name', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'email', 'id' => 'email', 'type' => 'email', 'label' => __( 'Email', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'phone', 'id' => 'phone', 'type' => 'phone', 'label' => __( 'Phone', 'rawnaq' ), 'required' => '', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'company', 'id' => 'company', 'type' => 'text', 'label' => __( 'Company', 'rawnaq' ), 'required' => '', 'width' => '50', 'step' => '1' ],
				[ 'field_id' => 'subject', 'id' => 'subject', 'type' => 'text', 'label' => __( 'Subject', 'rawnaq' ), 'required' => '', 'width' => '100', 'step' => '1' ],
				[ 'field_id' => 'message', 'id' => 'message', 'type' => 'textarea', 'label' => __( 'Message', 'rawnaq' ), 'required' => 'yes', 'width' => '100', 'step' => '1' ],
			],
		],
		'multi_project' => [
			'label'  => __( 'Multi-step project inquiry', 'rawnaq' ),
			'fields' => [
				[ 'field_id' => 'project_type', 'id' => 'project_type', 'type' => 'select', 'label' => __( 'Project type', 'rawnaq' ), 'required' => 'yes', 'width' => '100', 'step' => '1', 'options' => 'New build, Renovation, Consulting' ],
				[ 'field_id' => 'budget', 'id' => 'budget', 'type' => 'select', 'label' => __( 'Budget', 'rawnaq' ), 'required' => '', 'width' => '100', 'step' => '1', 'options' => 'Under 50k, 50–150k, 150k+', 'show_if' => 'project_type', 'show_if_value' => 'Renovation' ],
				[ 'field_id' => 'scope', 'id' => 'scope', 'type' => 'textarea', 'label' => __( 'Project scope', 'rawnaq' ), 'required' => 'yes', 'width' => '100', 'step' => '2' ],
				[ 'field_id' => 'name', 'id' => 'name', 'type' => 'text', 'label' => __( 'Name', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '3' ],
				[ 'field_id' => 'email', 'id' => 'email', 'type' => 'email', 'label' => __( 'Email', 'rawnaq' ), 'required' => 'yes', 'width' => '50', 'step' => '3' ],
				[ 'field_id' => 'phone', 'id' => 'phone', 'type' => 'phone', 'label' => __( 'Phone', 'rawnaq' ), 'required' => '', 'width' => '100', 'step' => '3' ],
			],
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
	$allowed = [ 'text', 'email', 'phone', 'textarea', 'select', 'checkbox', 'date', 'number', 'url', 'hidden', 'rating', 'file' ];
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
			$options = array_values( array_filter( array_map( 'sanitize_text_field', $options_raw ) ) );
		} else {
			$parts   = array_map( 'trim', explode( ',', (string) $options_raw ) );
			$options = array_values( array_filter( array_map( 'sanitize_text_field', $parts ) ) );
		}
		$width = sanitize_key( (string) ( $f['width'] ?? '100' ) );
		if ( ! in_array( $width, [ '100', '75', '66', '50', '33', '25' ], true ) ) {
			$width = '100';
		}
		$step = max( 1, absint( $f['step'] ?? 1 ) );
		$out[] = [
			'id'           => $id,
			'type'         => $type,
			'label'        => sanitize_text_field( $f['label'] ?? '' ),
			'placeholder'  => sanitize_text_field( $f['placeholder'] ?? '' ),
			'required'     => ! empty( $f['required'] ) && 'no' !== $f['required'] && 'false' !== $f['required'],
			'options'      => $options,
			'width'        => $width,
			'step'         => $step,
			'showIf'       => sanitize_key( $f['showIf'] ?? ( $f['show_if'] ?? '' ) ),
			'showIfValue'  => sanitize_text_field( $f['showIfValue'] ?? ( $f['show_if_value'] ?? '' ) ),
			'defaultValue' => sanitize_text_field( $f['defaultValue'] ?? ( $f['default_value'] ?? '' ) ),
			'maxMb'        => max( 1, min( 25, absint( $f['maxMb'] ?? ( $f['max_mb'] ?? 5 ) ) ) ),
			'accept'       => sanitize_text_field( $f['accept'] ?? '' ),
			'ratingMax'    => max( 3, min( 10, absint( $f['ratingMax'] ?? ( $f['rating_max'] ?? 5 ) ) ) ),
		];
	}
	return $out;
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
	$score = isset( $body['score'] ) ? (float) $body['score'] : 1.0;
	return $score >= 0.4;
}

/**
 * POST JSON to webhook / Slack incoming webhook.
 *
 * @param string               $url    Endpoint.
 * @param array<string,mixed>  $payload Payload.
 * @return bool
 */
function rawnaq_smart_form_send_webhook( $url, $payload ) {
	$url = esc_url_raw( $url );
	if ( ! $url ) {
		return false;
	}
	$is_slack = ( false !== strpos( $url, 'hooks.slack.com' ) );
	$body     = $payload;
	if ( $is_slack ) {
		$lines = [];
		foreach ( (array) ( $payload['fields'] ?? [] ) as $k => $v ) {
			$lines[] = '*' . $k . '*: ' . $v;
		}
		$body = [
			'text' => ( $payload['subject'] ?? 'New Smart Form submission' ) . "\n" . implode( "\n", $lines ),
		];
	}
	$res = wp_remote_post(
		$url,
		[
			'timeout' => 8,
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( $body ),
		]
	);
	return ! is_wp_error( $res ) && wp_remote_retrieve_response_code( $res ) < 400;
}

/**
 * Handle uploaded files for file-type fields.
 *
 * Nonce must already be verified by the AJAX caller (ajax_smart_form_submit).
 *
 * @param array $fields_cfg Normalized fields.
 * @return array{values:array<string,string>,attachments:string[],errors:string[]}
 */
function rawnaq_smart_form_handle_uploads( $fields_cfg ) {
	$values      = [];
	$attachments = [];
	$errors      = [];

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified in ajax_smart_form_submit before call.
	if ( empty( $_FILES['sf_files'] ) || ! isset( $_FILES['sf_files']['name'] ) || ! is_array( $_FILES['sf_files']['name'] ) ) {
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		return compact( 'values', 'attachments', 'errors' );
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
		$file = [
			'name'     => sanitize_file_name( wp_unslash( $_FILES['sf_files']['name'][ $id ] ) ),
			'type'     => sanitize_mime_type( wp_unslash( $_FILES['sf_files']['type'][ $id ] ) ),
			'tmp_name' => sanitize_text_field( wp_unslash( $_FILES['sf_files']['tmp_name'][ $id ] ) ),
			'error'    => absint( $_FILES['sf_files']['error'][ $id ] ),
			'size'     => absint( $_FILES['sf_files']['size'][ $id ] ),
		];
		if ( UPLOAD_ERR_OK !== $file['error'] ) {
			$errors[] = $id;
			continue;
		}
		$field_max = max( 1, (int) $field['maxMb'] ) * MB_IN_BYTES;
		$cap       = min( $max, $field_max );
		if ( $file['size'] > $cap ) {
			$errors[] = $id;
			continue;
		}
		$moved = wp_handle_upload(
			$file,
			[
				'test_form' => false,
				'mimes'     => null,
			]
		);
		if ( isset( $moved['error'] ) || empty( $moved['file'] ) ) {
			$errors[] = $id;
			continue;
		}
		$attachments[] = $moved['file'];
		$values[ $id ] = ! empty( $moved['url'] ) ? $moved['url'] : basename( $moved['file'] );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	return compact( 'values', 'attachments', 'errors' );
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
			'map_meta_cap'        => true,
			'supports'            => [ 'title', 'editor' ],
			'exclude_from_search' => true,
		]
	);
}

/**
 * Persist a submission for admin review.
 *
 * @param string               $form_id Form instance id.
 * @param array<string,string> $values  Field map.
 * @param array                $cfg     Form config.
 * @return int Post ID or 0.
 */
function rawnaq_smart_form_log_submission( $form_id, $values, $cfg ) {
	rawnaq_smart_form_register_cpt();
	$name = '';
	foreach ( [ 'name', 'full_name', 'your_name' ] as $k ) {
		if ( ! empty( $values[ $k ] ) ) {
			$name = $values[ $k ];
			break;
		}
	}
	$title = $name
		? sprintf( /* translators: %s: submitter name */ __( 'Inquiry from %s', 'rawnaq' ), $name )
		: __( 'Smart Form submission', 'rawnaq' );

	$lines = [];
	foreach ( $values as $k => $v ) {
		$lines[] = $k . ': ' . $v;
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
	update_post_meta( $post_id, '_rawnaq_sf_form_id', sanitize_text_field( $form_id ) );
	update_post_meta( $post_id, '_rawnaq_sf_values', $values );
	update_post_meta( $post_id, '_rawnaq_sf_unread', '1' );
	update_post_meta( $post_id, '_rawnaq_sf_channels', [
		'email'    => ! empty( $cfg['deliveryEmail'] ),
		'whatsapp' => ! empty( $cfg['deliveryWhatsapp'] ),
	] );
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
	$form_id = $form_id ? sanitize_html_class( $form_id ) : ( 'sf-' . wp_unique_id() );

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
		'webhookUrl'        => esc_url_raw( $cfg['webhookUrl'] ?? '' ),
		'buttonFullWidth'   => ! empty( $cfg['buttonFullWidth'] ),
		'multiStep'         => $multi_step,
		'fields'            => $fields,
	];
	if ( ! in_array( $cfg_out['afterSubmit'], [ 'message', 'redirect', 'whatsapp' ], true ) ) {
		$cfg_out['afterSubmit'] = 'message';
	}
	$submit  = $cfg_out['submitLabel'] ?: __( 'Send message', 'rawnaq' );
	$success = $cfg_out['successMessage'] ?: __( 'Message sent successfully.', 'rawnaq' );
	$error   = $cfg_out['errorMessage'] ?: __( 'Please fill in the required fields correctly.', 'rawnaq' );
	$cfg_out['successMessage'] = $success;
	$cfg_out['errorMessage']   = $error;

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

	$wrap_class = 'rawnaq-smart-form' . ( ! empty( $cfg_out['buttonFullWidth'] ) ? ' is-btn-full' : '' );
	?>
	<div class="<?php echo esc_attr( $wrap_class ); ?>" data-form-id="<?php echo esc_attr( $form_id ); ?>"<?php echo $style_vars ? ' style="' . esc_attr( $style_vars ) . '"' : ''; ?>>
		<form class="rawnaq-smart-form-el" method="post" novalidate
			data-form-id="<?php echo esc_attr( $form_id ); ?>"
			data-sf="<?php echo esc_attr( wp_json_encode( $cfg_out ) ); ?>"
			<?php echo $has_file ? ' enctype="multipart/form-data"' : ''; ?>>

			<?php if ( $multi_step ) : ?>
				<div class="rawnaq-sf-steps" role="tablist" aria-label="<?php esc_attr_e( 'Form steps', 'rawnaq' ); ?>">
					<?php foreach ( $step_nums as $si => $sn ) : ?>
						<span class="rawnaq-sf-step-dot<?php echo 0 === $si ? ' is-active' : ''; ?>" data-step="<?php echo esc_attr( (string) $sn ); ?>"><?php echo esc_html( (string) ( $si + 1 ) ); ?></span>
					<?php endforeach; ?>
				</div>
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

			<?php if ( $multi_step && ! empty( $cfg_out['consentEnabled'] ) ) : ?>
				<div class="rawnaq-sf-consent-wrap" data-consent-last hidden>
					<?php rawnaq_smart_form_render_consent( $cfg_out, $error ); ?>
				</div>
			<?php endif; ?>

			<input class="rawnaq-sf-hp" type="text" name="rawnaq_hp" value="" tabindex="-1" autocomplete="off" aria-hidden="true" />
			<input type="hidden" name="rawnaq_ts" value="" />
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

			<div class="rawnaq-sf-status" role="status" aria-live="polite">
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
	$id    = $field['id'];
	$type  = $field['type'];
	$label = $field['label'] ?: $id;
	$ph    = $field['placeholder'];
	$req   = ! empty( $field['required'] );
	$width = $field['width'] ?? '100';
	$input_id = $form_id . '-' . $id;
	$attr_req = $req ? ' required aria-required="true"' : '';
	$show_if  = $field['showIf'] ?? '';
	$show_val = $field['showIfValue'] ?? '';
	$cond     = '';
	if ( $show_if ) {
		$cond = ' data-show-if="' . esc_attr( $show_if ) . '" data-show-if-value="' . esc_attr( $show_val ) . '" hidden';
	}
	?>
	<div class="rawnaq-sf-field" data-field="<?php echo esc_attr( $id ); ?>" data-width="<?php echo esc_attr( $width ); ?>" data-type="<?php echo esc_attr( $type ); ?>"<?php echo $cond; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( ! in_array( $type, [ 'checkbox', 'hidden', 'rating' ], true ) ) : ?>
			<label class="rawnaq-sf-label" for="<?php echo esc_attr( $input_id ); ?>">
				<?php echo esc_html( $label ); ?>
				<?php if ( $req ) : ?><span class="req" aria-hidden="true">*</span><?php endif; ?>
			</label>
		<?php endif; ?>

		<?php if ( 'hidden' === $type ) : ?>
			<input type="hidden" name="sf_<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $field['defaultValue'] ?? '' ); ?>" data-sf-type="hidden" />
		<?php elseif ( 'textarea' === $type ) : ?>
			<textarea class="rawnaq-sf-textarea" id="<?php echo esc_attr( $input_id ); ?>"
				name="sf_<?php echo esc_attr( $id ); ?>" data-sf-type="textarea"
				placeholder="<?php echo esc_attr( $ph ); ?>"<?php echo $attr_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>></textarea>
		<?php elseif ( 'select' === $type ) : ?>
			<select class="rawnaq-sf-select" id="<?php echo esc_attr( $input_id ); ?>"
				name="sf_<?php echo esc_attr( $id ); ?>" data-sf-type="select"<?php echo $attr_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<option value=""><?php echo esc_html( $ph ?: __( 'Select…', 'rawnaq' ) ); ?></option>
				<?php foreach ( $field['options'] as $opt ) : ?>
					<option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
				<?php endforeach; ?>
			</select>
		<?php elseif ( 'checkbox' === $type ) : ?>
			<label class="rawnaq-sf-check">
				<input type="checkbox" id="<?php echo esc_attr( $input_id ); ?>"
					name="sf_<?php echo esc_attr( $id ); ?>" value="1" data-sf-type="checkbox"<?php echo $attr_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />
				<span><?php echo esc_html( $label ); ?><?php if ( $req ) : ?> <span class="req">*</span><?php endif; ?></span>
			</label>
		<?php elseif ( 'rating' === $type ) : ?>
			<label class="rawnaq-sf-label"><?php echo esc_html( $label ); ?><?php if ( $req ) : ?><span class="req">*</span><?php endif; ?></label>
			<div class="rawnaq-sf-rating" data-max="<?php echo esc_attr( (string) $field['ratingMax'] ); ?>">
				<?php for ( $r = 1; $r <= (int) $field['ratingMax']; $r++ ) : ?>
					<button type="button" class="rawnaq-sf-star" data-value="<?php echo esc_attr( (string) $r ); ?>" aria-label="<?php echo esc_attr( (string) $r ); ?>">★</button>
				<?php endfor; ?>
				<input type="hidden" name="sf_<?php echo esc_attr( $id ); ?>" value="" data-sf-type="rating"<?php echo $attr_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />
			</div>
		<?php elseif ( 'file' === $type ) : ?>
			<input class="rawnaq-sf-file" type="file" id="<?php echo esc_attr( $input_id ); ?>"
				name="sf_files[<?php echo esc_attr( $id ); ?>]" data-sf-type="file"
				data-max-mb="<?php echo esc_attr( (string) $field['maxMb'] ); ?>"
				<?php echo ! empty( $field['accept'] ) ? ' accept="' . esc_attr( $field['accept'] ) . '"' : ''; ?>
				<?php echo $attr_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />
			<p class="rawnaq-sf-hint"><?php
				printf(
					/* translators: %d: max MB */
					esc_html__( 'Max %d MB', 'rawnaq' ),
					(int) $field['maxMb']
				);
			?></p>
		<?php else :
			$html_type = 'text';
			if ( 'email' === $type ) {
				$html_type = 'email';
			} elseif ( 'phone' === $type ) {
				$html_type = 'tel';
			} elseif ( 'date' === $type ) {
				$html_type = 'date';
			} elseif ( 'number' === $type ) {
				$html_type = 'number';
			} elseif ( 'url' === $type ) {
				$html_type = 'url';
			}
			?>
			<input class="rawnaq-sf-input" type="<?php echo esc_attr( $html_type ); ?>"
				id="<?php echo esc_attr( $input_id ); ?>"
				name="sf_<?php echo esc_attr( $id ); ?>"
				data-sf-type="<?php echo esc_attr( $type ); ?>"
				placeholder="<?php echo esc_attr( $ph ); ?>"
				value="<?php echo 'hidden' === $type ? esc_attr( $field['defaultValue'] ?? '' ) : ''; ?>"
				<?php echo $attr_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />
		<?php endif; ?>
		<?php if ( 'hidden' !== $type ) : ?>
			<p class="rawnaq-sf-error"><?php echo esc_html( $error ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Consent checkbox markup.
 *
 * @param array  $cfg_out Config.
 * @param string $error   Error text.
 */
function rawnaq_smart_form_render_consent( $cfg_out, $error ) {
	?>
	<div class="rawnaq-sf-field" data-field="consent" data-width="100">
		<label class="rawnaq-sf-check">
			<input type="checkbox" name="sf_consent" value="1" data-sf-type="checkbox" required aria-required="true" />
			<span><?php echo esc_html( $cfg_out['consentText'] ?: __( 'I agree to the processing of my data.', 'rawnaq' ) ); ?> <span class="req">*</span></span>
		</label>
		<p class="rawnaq-sf-error"><?php echo esc_html( $error ); ?></p>
	</div>
	<?php
}
