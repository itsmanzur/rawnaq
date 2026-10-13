<?php
/**
 * Rawnaq Divi Module: Smart Form & WhatsApp Delivery
 *
 * @package Rawnaq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_ET_Smart_Form extends ET_Builder_Module {

	public $slug       = 'rawnaq_et_smart_form';
	public $vb_support = 'on';

	public function init() {
		$this->name             = esc_html__( 'Rawnaq Smart Form', 'rawnaq' );
		$this->icon_path        = 'feedback';
		$this->main_css_element = '%%order_class%%.rawnaq-smart-form-wrapper';
	}

	public function get_fields() {
		return [
			'form_theme' => [
				'label'           => esc_html__( 'Theme Preset', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => [
					'elevated'  => esc_html__( 'Modern Elevated (Default)', 'rawnaq' ),
					'glass'     => esc_html__( 'Minimal Glassmorphism', 'rawnaq' ),
					'obsidian'  => esc_html__( 'Dark Obsidian / Cyber', 'rawnaq' ),
					'spotlight' => esc_html__( 'Editorial Spotlight', 'rawnaq' ),
				],
				'default'         => 'elevated',
				'toggle_slug'     => 'main_content',
			],
			'layout_preset' => [
				'label'           => esc_html__( 'Form Preset', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => function_exists( 'rawnaq_smart_form_preset_options' ) ? rawnaq_smart_form_preset_options() : [
					'quick_contact'   => esc_html__( 'Quick Contact (2-Column)', 'rawnaq' ),
					'compact_lead'    => esc_html__( 'Compact Lead Gen (Hero / Popup)', 'rawnaq' ),
					'b2b_rfp'         => esc_html__( 'B2B RFP / Project Inquiry', 'rawnaq' ),
					'multi_project'   => esc_html__( 'Multi-Step Project Discovery', 'rawnaq' ),
					'feedback_review' => esc_html__( 'Customer Feedback & Rating', 'rawnaq' ),
					'job_application' => esc_html__( 'Job Application & CV Upload', 'rawnaq' ),
				],
				'default'         => 'quick_contact',
				'toggle_slug'     => 'main_content',
			],
			'form_label' => [
				'label'           => esc_html__( 'Form Label (Admin)', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
				'toggle_slug'     => 'main_content',
			],
			'log_submissions' => [
				'label'           => esc_html__( 'Log Submissions in Admin', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'off' => esc_html__( 'No', 'rawnaq' ),
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
				],
				'default'         => 'on',
				'toggle_slug'     => 'main_content',
			],
			'fields_json' => [
				'label'           => esc_html__( 'Custom Fields (JSON Override)', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'Optional. Custom JSON array of fields. Overrides the layout preset if provided.', 'rawnaq' ),
				'default'         => '',
				'toggle_slug'     => 'advanced',
			],
			'button_text' => [
				'label'           => esc_html__( 'Submit Button Text', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => esc_html__( 'Send message', 'rawnaq' ),
				'toggle_slug'     => 'main_content',
			],
			'button_full_width' => [
				'label'           => esc_html__( 'Full Width Button', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'layout',
				'options'         => [
					'off' => esc_html__( 'No', 'rawnaq' ),
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'main_content',
			],
			'delivery_mode' => [
				'label'           => esc_html__( 'Delivery Mode', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => [
					'email_wa'   => esc_html__( 'Both Email Notification & WhatsApp Redirect', 'rawnaq' ),
					'email_only' => esc_html__( 'Email & Database Only', 'rawnaq' ),
					'wa_only'    => esc_html__( 'WhatsApp Direct Redirect Only', 'rawnaq' ),
				],
				'default'         => 'email_wa',
				'toggle_slug'     => 'delivery',
			],
			'email_to' => [
				'label'           => esc_html__( 'Notification Email', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'Email to receive form submissions (leave blank for admin email).', 'rawnaq' ),
				'default'         => '',
				'toggle_slug'     => 'delivery',
			],
			'email_subject' => [
				'label'           => esc_html__( 'Email Subject', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => esc_html__( 'New website inquiry', 'rawnaq' ),
				'toggle_slug'     => 'delivery',
			],
			'email_html' => [
				'label'           => esc_html__( 'Branded HTML Email', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'off' => esc_html__( 'No', 'rawnaq' ),
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
				],
				'default'         => 'on',
				'toggle_slug'     => 'delivery',
			],
			'auto_responder_enable' => [
				'label'           => esc_html__( 'Send Confirmation Email', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'off' => esc_html__( 'No', 'rawnaq' ),
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'delivery',
			],
			'auto_responder_subject' => [
				'label'           => esc_html__( 'Confirmation Subject', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => esc_html__( 'We received your message', 'rawnaq' ),
				'toggle_slug'     => 'delivery',
				'show_if'         => [ 'auto_responder_enable' => 'on' ],
			],
			'auto_responder_message' => [
				'label'           => esc_html__( 'Confirmation Message', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'default'         => "Hi {first_name},\n\nThank you for reaching out to us. We have received your submission and will get back to you shortly.\n\nBest regards,\n{site_title}",
				'toggle_slug'     => 'delivery',
				'show_if'         => [ 'auto_responder_enable' => 'on' ],
			],
			'auto_responder_from_name' => [
				'label'           => esc_html__( 'Confirmation From Name', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
				'toggle_slug'     => 'delivery',
				'show_if'         => [ 'auto_responder_enable' => 'on' ],
			],
			'wa_number' => [
				'label'           => esc_html__( 'WhatsApp Delivery Number', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'Phone number with country code (e.g. 15551234567 or 8801700000000).', 'rawnaq' ),
				'default'         => '',
				'toggle_slug'     => 'delivery',
			],
			'wa_template' => [
				'label'           => esc_html__( 'WhatsApp Message Template', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'Custom template using tokens like {name}, {email}, {phone}, {message}, {pageTitle}. Leave blank for default format.', 'rawnaq' ),
				'default'         => '',
				'toggle_slug'     => 'delivery',
			],
			'after_submit' => [
				'label'           => esc_html__( 'After Submit Action', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'configuration',
				'options'         => [
					'message'  => esc_html__( 'Show Success Message', 'rawnaq' ),
					'redirect' => esc_html__( 'Redirect to URL', 'rawnaq' ),
					'whatsapp' => esc_html__( 'Open WhatsApp Chat', 'rawnaq' ),
				],
				'default'         => 'message',
				'toggle_slug'     => 'actions',
			],
			'redirect_url' => [
				'label'           => esc_html__( 'Redirect URL', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'default'         => '',
				'toggle_slug'     => 'actions',
			],
			'success_message' => [
				'label'           => esc_html__( 'Custom Success Message', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
				'toggle_slug'     => 'actions',
			],
			'error_message' => [
				'label'           => esc_html__( 'Custom Error Message', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
				'toggle_slug'     => 'actions',
			],
			'consent_enable' => [
				'label'           => esc_html__( 'Enable Consent Checkbox', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'off' => esc_html__( 'No', 'rawnaq' ),
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'consent',
			],
			'consent_text' => [
				'label'           => esc_html__( 'Consent Agreement Text', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => 'I agree to the processing of my personal data.',
				'toggle_slug'     => 'consent',
			],
			'recaptcha_enable' => [
				'label'           => esc_html__( 'Enable reCAPTCHA v3', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'off' => esc_html__( 'No', 'rawnaq' ),
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'security',
			],
			'webhook_enable' => [
				'label'           => esc_html__( 'Enable Webhook Delivery', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'off' => esc_html__( 'No', 'rawnaq' ),
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'integrations',
			],
			'webhook_url' => [
				'label'           => esc_html__( 'Webhook HTTPS URL', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'default'         => '',
				'toggle_slug'     => 'integrations',
			],
			'crm_provider' => [
				'label'           => esc_html__( 'CRM Integration', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'configuration',
				'options'         => [
					'none'      => esc_html__( 'None', 'rawnaq' ),
					'mailchimp' => esc_html__( 'Mailchimp', 'rawnaq' ),
					'hubspot'   => esc_html__( 'HubSpot', 'rawnaq' ),
				],
				'default'         => 'none',
				'toggle_slug'     => 'integrations',
			],
			'crm_audience' => [
				'label'           => esc_html__( 'CRM Audience / List ID', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'default'         => '',
				'toggle_slug'     => 'integrations',
			],
			'mailchimp_status' => [
				'label'           => esc_html__( 'Mailchimp Subscriber Status', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'configuration',
				'options'         => [
					'pending'    => esc_html__( 'Pending (double opt-in)', 'rawnaq' ),
					'subscribed' => esc_html__( 'Subscribed (single opt-in)', 'rawnaq' ),
				],
				'default'         => 'pending',
				'toggle_slug'     => 'integrations',
				'show_if'         => [ 'crm_provider' => 'mailchimp' ],
			],
			'estimator' => [
				'label'           => esc_html__( 'Price Estimator', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'options'         => [
					'off' => esc_html__( 'Off', 'rawnaq' ),
					'on'  => esc_html__( 'On', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'main_content',
				'description'     => esc_html__( 'Calculate and display estimated total from selected options.', 'rawnaq' ),
			],
			'estimator_label' => [
				'label'           => esc_html__( 'Estimator Label', 'rawnaq' ),
				'type'            => 'text',
				'default'         => esc_html__( 'Estimated total', 'rawnaq' ),
				'toggle_slug'     => 'main_content',
				'show_if'         => [ 'estimator' => 'on' ],
			],
			'estimator_currency' => [
				'label'           => esc_html__( 'Currency Symbol', 'rawnaq' ),
				'type'            => 'text',
				'default'         => '$',
				'toggle_slug'     => 'main_content',
				'show_if'         => [ 'estimator' => 'on' ],
			],
			'celebration_effect' => [
				'label'           => esc_html__( 'Confetti Celebration Effect', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'options'         => [
					'off' => esc_html__( 'Off', 'rawnaq' ),
					'on'  => esc_html__( 'On', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'main_content',
				'description'     => esc_html__( 'Show celebration confetti on successful submission.', 'rawnaq' ),
			],
			'url_prefill' => [
				'label'           => esc_html__( 'Allow URL Prefill', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'options'         => [
					'off' => esc_html__( 'Off', 'rawnaq' ),
					'on'  => esc_html__( 'On', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'main_content',
				'description'     => esc_html__( 'Prefill field values from URL parameters (?name=value).', 'rawnaq' ),
			],
			'accent_color' => [
				'label'           => esc_html__( 'Accent / Button Color', 'rawnaq' ),
				'type'            => 'color-alpha',
				'custom_color'    => true,
				'default'         => '#fbbf24',
				'toggle_slug'     => 'style',
			],
			'accent_deep' => [
				'label'           => esc_html__( 'Accent Deep Color', 'rawnaq' ),
				'type'            => 'color-alpha',
				'custom_color'    => true,
				'default'         => '#0f766e',
				'toggle_slug'     => 'style',
			],
			'btn_text_color' => [
				'label'           => esc_html__( 'Button Text Color', 'rawnaq' ),
				'type'            => 'color-alpha',
				'custom_color'    => true,
				'default'         => '#92400e',
				'toggle_slug'     => 'style',
			],
			'label_color' => [
				'label'           => esc_html__( 'Label Color', 'rawnaq' ),
				'type'            => 'color-alpha',
				'custom_color'    => true,
				'default'         => '',
				'toggle_slug'     => 'style',
			],
			'input_bg' => [
				'label'           => esc_html__( 'Input Background', 'rawnaq' ),
				'type'            => 'color-alpha',
				'custom_color'    => true,
				'default'         => '',
				'toggle_slug'     => 'style',
			],
			'input_border' => [
				'label'           => esc_html__( 'Input Border Color', 'rawnaq' ),
				'type'            => 'color-alpha',
				'custom_color'    => true,
				'default'         => '',
				'toggle_slug'     => 'style',
			],
			'input_text' => [
				'label'           => esc_html__( 'Input Text Color', 'rawnaq' ),
				'type'            => 'color-alpha',
				'custom_color'    => true,
				'default'         => '',
				'toggle_slug'     => 'style',
			],
			'input_radius' => [
				'label'           => esc_html__( 'Input Corner Radius', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'layout',
				'range_settings'  => [
					'min'  => '0',
					'max'  => '32',
					'step' => '1',
				],
				'default'         => '12px',
				'toggle_slug'     => 'style',
			],
		];
	}

	public function render( $attrs, $content = null, $render_slug = '' ) {
		wp_enqueue_style( 'rawnaq-smart-form' );
		wp_enqueue_script( 'rawnaq-smart-form' );

		$theme         = sanitize_key( $this->props['form_theme'] ?? 'elevated' );
		if ( ! in_array( $theme, [ 'elevated', 'glass', 'obsidian', 'dark', 'spotlight' ], true ) ) {
			$theme = 'elevated';
		}
		$preset        = sanitize_key( $this->props['layout_preset'] ?? 'quick_contact' );
		$fields_json   = $this->props['fields_json'] ?? '';
		$btn_text      = sanitize_text_field( $this->props['button_text'] ?? __( 'Send message', 'rawnaq' ) );
		$btn_full      = 'on' === ( $this->props['button_full_width'] ?? 'off' );
		$email_to      = sanitize_email( $this->props['email_to'] ?? '' );
		$email_subject = sanitize_text_field( $this->props['email_subject'] ?? __( 'New website inquiry', 'rawnaq' ) );
		$wa_number     = sanitize_text_field( $this->props['wa_number'] ?? '' );
		$wa_template   = sanitize_textarea_field( $this->props['wa_template'] ?? '' );
		$delivery_mode = sanitize_key( $this->props['delivery_mode'] ?? 'email_wa' );
		$after_submit  = sanitize_key( $this->props['after_submit'] ?? 'message' );
		$redirect_url  = esc_url_raw( $this->props['redirect_url'] ?? '' );
		$success_msg   = sanitize_text_field( $this->props['success_message'] ?? '' );
		$error_msg     = sanitize_text_field( $this->props['error_message'] ?? '' );
		$consent_on    = 'on' === ( $this->props['consent_enable'] ?? 'off' );
		$consent_text  = sanitize_text_field( $this->props['consent_text'] ?? '' );
		$recaptcha_on  = 'on' === ( $this->props['recaptcha_enable'] ?? 'off' );
		$webhook_on    = 'on' === ( $this->props['webhook_enable'] ?? 'off' );
		$webhook_url   = esc_url_raw( $this->props['webhook_url'] ?? '' );
		$crm_provider     = sanitize_key( $this->props['crm_provider'] ?? 'none' );
		$crm_audience     = sanitize_text_field( $this->props['crm_audience'] ?? '' );
		$mailchimp_status = sanitize_key( $this->props['mailchimp_status'] ?? 'pending' );
		$estimator_on  = 'on' === ( $this->props['estimator'] ?? 'off' );
		$estimator_lbl = sanitize_text_field( $this->props['estimator_label'] ?? __( 'Estimated total', 'rawnaq' ) );
		$estimator_cur = sanitize_text_field( $this->props['estimator_currency'] ?? '$' );

		$accent        = sanitize_hex_color( $this->props['accent_color'] ?? '#fbbf24' ) ?: '#fbbf24';
		$accent_deep   = sanitize_hex_color( $this->props['accent_deep'] ?? '#0f766e' ) ?: '#0f766e';
		$btn_text_col  = sanitize_hex_color( $this->props['btn_text_color'] ?? '#92400e' ) ?: '#92400e';
		$label_col     = sanitize_hex_color( $this->props['label_color'] ?? '' ) ?: '';
		$input_bg      = sanitize_hex_color( $this->props['input_bg'] ?? '' ) ?: '';
		$input_border  = sanitize_hex_color( $this->props['input_border'] ?? '' ) ?: '';
		$input_text    = sanitize_hex_color( $this->props['input_text'] ?? '' ) ?: '';
		$input_radius  = sanitize_text_field( $this->props['input_radius'] ?? '12px' );

		$json_error_html = '';
		$fields          = [];
		if ( ! empty( $fields_json ) ) {
			$decoded = json_decode( $fields_json, true );
			if ( null === $decoded || ! is_array( $decoded ) ) {
				$json_error_html = '<div class="rawnaq-sf-builder-warning" style="background:#fee2e2;border:1px solid #ef4444;color:#991b1b;padding:12px;border-radius:6px;margin-bottom:14px;font-size:13px;"><strong>' . esc_html__( 'Smart Form Warning:', 'rawnaq' ) . '</strong> ' . esc_html__( 'Custom Fields JSON syntax is invalid. Please check your JSON format.', 'rawnaq' ) . '</div>';
			} else {
				$fields = $decoded;
			}
		}

		if ( empty( $fields ) && function_exists( 'rawnaq_smart_form_preset_for_elementor' ) ) {
			$pack = rawnaq_smart_form_preset_for_elementor( $preset );
			if ( ! empty( $pack['fields'] ) ) {
				$fields = $pack['fields'];
			}
		}

		$resolved_after = $after_submit;
		if ( 'wa_only' === $delivery_mode && 'redirect' !== $after_submit ) {
			$resolved_after = 'whatsapp';
		}

		$celeb_val     = $this->props['celebration_effect'] ?? null;
		$celeb_on      = null !== $celeb_val ? ( 'on' === $celeb_val ) : true;
		$prefill_on    = 'on' === ( $this->props['url_prefill'] ?? 'off' );

		$cfg = [
			'theme'             => $theme,
			'fields'            => $fields,
			'deliveryEmail'     => ( 'email_wa' === $delivery_mode || 'email_only' === $delivery_mode ),
			'deliveryWhatsapp'  => ( 'email_wa' === $delivery_mode || 'wa_only' === $delivery_mode ),
			'emailTo'           => $email_to,
			'emailSubject'      => $email_subject,
			'emailHtml'         => 'off' !== ( $this->props['email_html'] ?? 'on' ),
			'waNumber'          => $wa_number,
			'waTemplate'        => $wa_template,
			'afterSubmit'       => $resolved_after,
			'redirectUrl'       => $redirect_url,
			'submitLabel'       => $btn_text,
			'successMessage'    => $success_msg,
			'errorMessage'      => $error_msg,
			'consentEnabled'    => $consent_on,
			'consentText'       => $consent_text,
			'logSubmissions'    => 'off' !== ( $this->props['log_submissions'] ?? 'on' ),
			'autoResponderEnabled'  => 'on' === ( $this->props['auto_responder_enable'] ?? 'off' ),
			'autoResponderSubject'  => sanitize_text_field( $this->props['auto_responder_subject'] ?? '' ),
			'autoResponderMessage'  => sanitize_textarea_field( $this->props['auto_responder_message'] ?? '' ),
			'autoResponderFromName' => sanitize_text_field( $this->props['auto_responder_from_name'] ?? '' ),
			'formLabel'             => ! empty( $this->props['form_label'] ) ? sanitize_text_field( $this->props['form_label'] ) : ( get_the_title() . ' (Divi)' ),
			'celebrationEffect' => $celeb_on,
			'urlPrefill'        => $prefill_on,
			'recaptchaEnabled'  => $recaptcha_on,
			'webhookEnabled'    => $webhook_on,
			'webhookUrl'        => $webhook_url,
			'crmProvider'       => $crm_provider,
			'crmAudience'       => $crm_audience,
			'mailchimpStatus'   => $mailchimp_status,
			'buttonFullWidth'   => $btn_full,
			'labelColor'        => $label_col,
			'inputBg'           => $input_bg,
			'inputBorder'       => $input_border,
			'inputText'         => $input_text,
			'buttonBg'          => $accent,
			'buttonText'        => $btn_text_col,
			'estimator'         => $estimator_on,
			'estimatorLabel'    => $estimator_lbl,
			'estimatorCurrency' => $estimator_cur,
		];

		$style_vars = [
			'--sf-accent:' . esc_attr( $accent ),
			'--sf-accent-deep:' . esc_attr( $accent_deep ),
			'--sf-btn-text:' . esc_attr( $btn_text_col ),
		];
		if ( $label_col ) {
			$style_vars[] = '--sf-label:' . esc_attr( $label_col );
		}
		if ( $input_bg ) {
			$style_vars[] = '--sf-panel:' . esc_attr( $input_bg );
		}
		if ( $input_border ) {
			$style_vars[] = '--sf-line:' . esc_attr( $input_border );
		}
		if ( $input_text ) {
			$style_vars[] = '--sf-ink:' . esc_attr( $input_text );
		}
		if ( $input_radius ) {
			$style_vars[] = '--sf-radius:' . esc_attr( $input_radius );
		}

		$style_attr = implode( ';', $style_vars ) . ';';

		ob_start();
		echo '<div class="rawnaq-smart-form-wrapper" style="' . esc_attr( $style_attr ) . '">';
		if ( $json_error_html ) {
			echo $json_error_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		if ( function_exists( 'rawnaq_smart_form_markup' ) ) {
			$form_id = function_exists( 'rawnaq_smart_form_make_form_id' )
				? rawnaq_smart_form_make_form_id( 'divi', $cfg, $this->props['module_id'] ?? '' )
				: ( 'divi-' . wp_unique_id() );
			rawnaq_smart_form_markup( $cfg, $form_id );
		}
		echo '</div>';

		return ob_get_clean();
	}
}
