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
			'button_text' => [
				'label'           => esc_html__( 'Submit Button Text', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => 'Send Message',
				'toggle_slug'     => 'main_content',
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
			'accent_color' => [
				'label'           => esc_html__( 'Accent Color', 'rawnaq' ),
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
		];
	}

	public function render( $attrs, $content = null, $render_slug = '' ) {
		wp_enqueue_style( 'rawnaq-smart-form' );
		wp_enqueue_script( 'rawnaq-smart-form' );

		$preset        = sanitize_key( $this->props['layout_preset'] ?? 'quick_contact' );
		$btn_text      = sanitize_text_field( $this->props['button_text'] ?? 'Send Message' );
		$email_to      = sanitize_email( $this->props['email_to'] ?? '' );
		$email_subject = sanitize_text_field( $this->props['email_subject'] ?? '' );
		$wa_number     = sanitize_text_field( $this->props['wa_number'] ?? '' );
		$delivery_mode = sanitize_text_field( $this->props['delivery_mode'] ?? 'email_wa' );
		$consent_on    = 'on' === ( $this->props['consent_enable'] ?? 'off' );
		$consent_text  = sanitize_text_field( $this->props['consent_text'] ?? '' );
		$accent        = sanitize_hex_color( $this->props['accent_color'] ?? '#fbbf24' ) ?: '#fbbf24';
		$accent_deep   = sanitize_hex_color( $this->props['accent_deep'] ?? '#0f766e' ) ?: '#0f766e';
		$btn_text_col  = sanitize_hex_color( $this->props['btn_text_color'] ?? '#92400e' ) ?: '#92400e';

		$fields = [];
		if ( function_exists( 'rawnaq_smart_form_preset_for_elementor' ) ) {
			$pack = rawnaq_smart_form_preset_for_elementor( $preset );
			if ( ! empty( $pack['fields'] ) ) {
				$fields = $pack['fields'];
			}
		}

		$cfg = [
			'fields'           => $fields,
			'deliveryEmail'    => ( 'email_wa' === $delivery_mode || 'email_only' === $delivery_mode ),
			'deliveryWhatsapp' => ( 'email_wa' === $delivery_mode || 'wa_only' === $delivery_mode ),
			'emailTo'          => $email_to,
			'emailSubject'     => $email_subject,
			'waNumber'         => $wa_number,
			'afterSubmit'      => ( 'wa_only' === $delivery_mode ) ? 'whatsapp' : 'message',
			'submitLabel'      => $btn_text,
			'consentEnabled'   => $consent_on,
			'consentText'      => $consent_text,
			'logSubmissions'   => true,
			'buttonBg'         => $accent,
			'buttonText'       => $btn_text_col,
		];

		ob_start();
		echo '<div class="rawnaq-smart-form-wrapper" style="--sf-accent:' . esc_attr( $accent ) . ';--sf-accent-deep:' . esc_attr( $accent_deep ) . ';--sf-btn-text:' . esc_attr( $btn_text_col ) . ';">';
		if ( function_exists( 'rawnaq_smart_form_markup' ) ) {
			rawnaq_smart_form_markup( $cfg, 'divi-' . wp_unique_id() );
		}
		echo '</div>';

		return ob_get_clean();
	}
}
