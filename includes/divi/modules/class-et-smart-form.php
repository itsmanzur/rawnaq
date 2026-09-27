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
			'layout_preset' => [
				'label'           => esc_html__( 'Form Preset', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => [
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
			'fields_json' => [
				'label'           => esc_html__( 'Custom Fields (JSON Override)', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'Optional. Custom JSON array of fields. Overrides the layout preset if provided.', 'rawnaq' ),
				'default'         => '',
				'toggle_slug'     => 'main_content',
			],
			'button_text' => [
				'label'           => esc_html__( 'Submit Button Text', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => 'Send Message',
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
				'default'         => 'New Website Inquiry',
				'toggle_slug'     => 'delivery',
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

		$preset        = sanitize_key( $this->props['layout_preset'] ?? 'quick_contact' );
		$fields_json   = $this->props['fields_json'] ?? '';
		$btn_text      = sanitize_text_field( $this->props['button_text'] ?? 'Send Message' );
		$btn_full      = 'on' === ( $this->props['button_full_width'] ?? 'off' );
		$email_to      = sanitize_email( $this->props['email_to'] ?? '' );
		$email_subject = sanitize_text_field( $this->props['email_subject'] ?? '' );
		$wa_number     = sanitize_text_field( $this->props['wa_number'] ?? '' );
		$wa_template   = sanitize_textarea_field( $this->props['wa_template'] ?? '' );
		$delivery_mode = sanitize_text_field( $this->props['delivery_mode'] ?? 'email_wa' );
		$after_submit  = sanitize_text_field( $this->props['after_submit'] ?? 'message' );
		$redirect_url  = esc_url_raw( $this->props['redirect_url'] ?? '' );
		$success_msg   = sanitize_text_field( $this->props['success_message'] ?? '' );
		$error_msg     = sanitize_text_field( $this->props['error_message'] ?? '' );
		$consent_on    = 'on' === ( $this->props['consent_enable'] ?? 'off' );
		$consent_text  = sanitize_text_field( $this->props['consent_text'] ?? '' );
		$recaptcha_on  = 'on' === ( $this->props['recaptcha_enable'] ?? 'off' );
		$webhook_on    = 'on' === ( $this->props['webhook_enable'] ?? 'off' );
		$webhook_url   = esc_url_raw( $this->props['webhook_url'] ?? '' );
		$crm_provider  = sanitize_key( $this->props['crm_provider'] ?? 'none' );
		$crm_audience  = sanitize_text_field( $this->props['crm_audience'] ?? '' );

		$accent        = sanitize_hex_color( $this->props['accent_color'] ?? '#fbbf24' ) ?: '#fbbf24';
		$accent_deep   = sanitize_hex_color( $this->props['accent_deep'] ?? '#0f766e' ) ?: '#0f766e';
		$btn_text_col  = sanitize_hex_color( $this->props['btn_text_color'] ?? '#92400e' ) ?: '#92400e';
		$label_col     = sanitize_hex_color( $this->props['label_color'] ?? '' ) ?: '';
		$input_bg      = sanitize_hex_color( $this->props['input_bg'] ?? '' ) ?: '';
		$input_border  = sanitize_hex_color( $this->props['input_border'] ?? '' ) ?: '';
		$input_text    = sanitize_hex_color( $this->props['input_text'] ?? '' ) ?: '';
		$input_radius  = sanitize_text_field( $this->props['input_radius'] ?? '12px' );

		$fields = [];
		if ( ! empty( $fields_json ) ) {
			$decoded = json_decode( $fields_json, true );
			if ( is_array( $decoded ) && ! empty( $decoded ) ) {
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

		$cfg = [
			'fields'           => $fields,
			'deliveryEmail'    => ( 'email_wa' === $delivery_mode || 'email_only' === $delivery_mode ),
			'deliveryWhatsapp' => ( 'email_wa' === $delivery_mode || 'wa_only' === $delivery_mode ),
			'emailTo'          => $email_to,
			'emailSubject'     => $email_subject,
			'waNumber'         => $wa_number,
			'waTemplate'       => $wa_template,
			'afterSubmit'      => $resolved_after,
			'redirectUrl'      => $redirect_url,
			'submitLabel'      => $btn_text,
			'successMessage'   => $success_msg,
			'errorMessage'     => $error_msg,
			'consentEnabled'   => $consent_on,
			'consentText'      => $consent_text,
			'logSubmissions'   => true,
			'recaptchaEnabled' => $recaptcha_on,
			'webhookEnabled'   => $webhook_on,
			'webhookUrl'       => $webhook_url,
			'crmProvider'      => $crm_provider,
			'crmAudience'      => $crm_audience,
			'buttonFullWidth'  => $btn_full,
			'labelColor'       => $label_col,
			'inputBg'          => $input_bg,
			'inputBorder'      => $input_border,
			'inputText'        => $input_text,
			'buttonBg'         => $accent,
			'buttonText'       => $btn_text_col,
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
		if ( function_exists( 'rawnaq_smart_form_markup' ) ) {
			rawnaq_smart_form_markup( $cfg, 'divi-' . wp_unique_id() );
		}
		echo '</div>';

		return ob_get_clean();
	}
}
