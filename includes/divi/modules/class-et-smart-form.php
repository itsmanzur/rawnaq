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
		$this->name       = esc_html__( 'Rawnaq Smart Form', 'rawnaq' );
		$this->icon_path  = 'feedback';
		$this->main_css_element = '%%order_class%%.rawnaq-smart-form-wrapper';
	}

	public function get_fields() {
		return [
			'form_title' => [
				'label'           => esc_html__( 'Form Title', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'Enter the heading for the smart form.', 'rawnaq' ),
				'default'         => 'Request Consultation',
				'toggle_slug'     => 'main_content',
			],
			'form_subtitle' => [
				'label'           => esc_html__( 'Form Subtitle', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'Enter a subtitle or helper text.', 'rawnaq' ),
				'default'         => 'Fill out the form to get started immediately.',
				'toggle_slug'     => 'main_content',
			],
			'button_text' => [
				'label'           => esc_html__( 'Submit Button Text', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => 'Send Message',
				'toggle_slug'     => 'main_content',
			],
			'wa_number' => [
				'label'           => esc_html__( 'WhatsApp Delivery Number', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'Enter phone number with international country code (e.g. 15551234567).', 'rawnaq' ),
				'default'         => '',
				'toggle_slug'     => 'delivery',
			],
			'delivery_mode' => [
				'label'           => esc_html__( 'Delivery Mode', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => [
					'email_wa'  => esc_html__( 'Both Email Notification & WhatsApp Redirect', 'rawnaq' ),
					'wa_only'   => esc_html__( 'WhatsApp Direct Redirect Only', 'rawnaq' ),
					'email_only'=> esc_html__( 'Email & Database Only', 'rawnaq' ),
				],
				'default'         => 'email_wa',
				'toggle_slug'     => 'delivery',
			],
			'accent_color' => [
				'label'           => esc_html__( 'Accent Color', 'rawnaq' ),
				'type'            => 'color-alpha',
				'custom_color'    => true,
				'default'         => '#0f766e',
				'toggle_slug'     => 'style',
			],
		];
	}

	public function render( $attrs, $content = null, $render_slug = '' ) {
		wp_enqueue_style( 'rawnaq-smart-form' );
		wp_enqueue_script( 'rawnaq-smart-form' );

		$title         = sanitize_text_field( $this->props['form_title'] ?? 'Request Consultation' );
		$subtitle      = sanitize_text_field( $this->props['form_subtitle'] ?? '' );
		$btn_text      = sanitize_text_field( $this->props['button_text'] ?? 'Send Message' );
		$wa_number     = sanitize_text_field( $this->props['wa_number'] ?? '' );
		$delivery_mode = sanitize_text_field( $this->props['delivery_mode'] ?? 'email_wa' );
		$accent_color  = sanitize_hex_color( $this->props['accent_color'] ?? '#0f766e' ) ?: '#0f766e';

		if ( function_exists( 'rawnaq_smart_form_render_html' ) ) {
			return rawnaq_smart_form_render_html( [
				'title'         => $title,
				'subtitle'      => $subtitle,
				'button_text'   => $btn_text,
				'wa_number'     => $wa_number,
				'delivery_mode' => $delivery_mode,
				'accent_color'  => $accent_color,
			] );
		}

		return '<div class="rawnaq-smart-form-wrapper" style="--rq-accent:' . esc_attr( $accent_color ) . ';">'
			. ( $title ? '<h3 class="rawnaq-sf-title">' . esc_html( $title ) . '</h3>' : '' )
			. '</div>';
	}
}
