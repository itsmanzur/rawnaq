<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_Smart_Form_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rawnaq_smart_form';
	}

	public function get_title() {
		return esc_html__( 'Smart Form', 'rawnaq' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_categories() {
		return [ 'rawnaq' ];
	}

	public function get_style_depends() {
		return [ 'rawnaq-smart-form' ];
	}

	public function get_script_depends() {
		return [ 'rawnaq-smart-form' ];
	}

	protected function register_controls() {
		$this->start_controls_section( 's_preset', [
			'label' => esc_html__( 'Layout preset', 'rawnaq' ),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
		] );

		$preset_opts = [ '' => esc_html__( '— Choose —', 'rawnaq' ) ];
		if ( function_exists( 'rawnaq_smart_form_presets' ) ) {
			foreach ( rawnaq_smart_form_presets() as $key => $pack ) {
				$preset_opts[ $key ] = $pack['label'];
			}
		}

		$this->add_control( 'layout_preset', [
			'label'   => esc_html__( 'Preset', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => '',
			'options' => $preset_opts,
			'description' => esc_html__( 'Pick a layout, then click Apply Preset to replace fields.', 'rawnaq' ),
		] );
		$this->add_control( 'apply_preset', [
			'type'        => \Elementor\Controls_Manager::BUTTON,
			'label'       => esc_html__( 'Apply Preset', 'rawnaq' ),
			'text'        => esc_html__( 'Apply Preset to Fields', 'rawnaq' ),
			'button_type' => 'success',
			'event'       => 'rawnaq:smartform:applyPreset',
			'separator'   => 'after',
			'description' => esc_html__( 'Overwrites the current Fields repeater.', 'rawnaq' ),
		] );
		$this->end_controls_section();

		$this->start_controls_section( 's_fields', [
			'label' => esc_html__( 'Fields', 'rawnaq' ),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
		] );

		$repeater = new \Elementor\Repeater();
		$repeater->add_control( 'field_id', [
			'label'       => esc_html__( 'Field ID', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => 'name',
			'description' => esc_html__( 'Used in templates as {id}. Also for conditionals.', 'rawnaq' ),
		] );
		$repeater->add_control( 'type', [
			'label'   => esc_html__( 'Type', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'text',
			'options' => [
				'text'     => esc_html__( 'Text', 'rawnaq' ),
				'email'    => esc_html__( 'Email', 'rawnaq' ),
				'phone'    => esc_html__( 'Phone', 'rawnaq' ),
				'textarea' => esc_html__( 'Textarea', 'rawnaq' ),
				'select'   => esc_html__( 'Select', 'rawnaq' ),
				'checkbox' => esc_html__( 'Checkbox', 'rawnaq' ),
				'date'     => esc_html__( 'Date', 'rawnaq' ),
				'number'   => esc_html__( 'Number', 'rawnaq' ),
				'url'      => esc_html__( 'URL', 'rawnaq' ),
				'hidden'   => esc_html__( 'Hidden', 'rawnaq' ),
				'rating'   => esc_html__( 'Rating', 'rawnaq' ),
				'file'     => esc_html__( 'File upload', 'rawnaq' ),
			],
		] );
		$repeater->add_control( 'label', [
			'label'   => esc_html__( 'Label', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => esc_html__( 'Name', 'rawnaq' ),
		] );
		$repeater->add_control( 'placeholder', [
			'label'   => esc_html__( 'Placeholder', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => '',
		] );
		$repeater->add_control( 'required', [
			'label'        => esc_html__( 'Required', 'rawnaq' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );
		$repeater->add_control( 'options', [
			'label'       => esc_html__( 'Select options (comma-separated)', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => '',
			'condition'   => [ 'type' => 'select' ],
			'label_block' => true,
		] );
		$repeater->add_control( 'width', [
			'label'   => esc_html__( 'Field width', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => '100',
			'options' => [
				'100' => esc_html__( 'Full (100%)', 'rawnaq' ),
				'75'  => esc_html__( 'Three quarters (75%)', 'rawnaq' ),
				'66'  => esc_html__( 'Two thirds (66%)', 'rawnaq' ),
				'50'  => esc_html__( 'Half (50%)', 'rawnaq' ),
				'33'  => esc_html__( 'One third (33%)', 'rawnaq' ),
				'25'  => esc_html__( 'Quarter (25%)', 'rawnaq' ),
			],
		] );
		$repeater->add_control( 'step', [
			'label'   => esc_html__( 'Step (multi-step)', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 1,
			'min'     => 1,
			'max'     => 8,
		] );
		$repeater->add_control( 'show_if', [
			'label'       => esc_html__( 'Show if field ID', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => '',
			'description' => esc_html__( 'Leave blank to always show. Example: project_type', 'rawnaq' ),
		] );
		$repeater->add_control( 'show_if_value', [
			'label'       => esc_html__( 'Equals value', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => '',
			'description' => esc_html__( 'Show when the field above equals this value (e.g. Renovation).', 'rawnaq' ),
		] );
		$repeater->add_control( 'default_value', [
			'label'     => esc_html__( 'Default / hidden value', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::TEXT,
			'default'   => '',
			'condition' => [ 'type' => 'hidden' ],
		] );
		$repeater->add_control( 'max_mb', [
			'label'     => esc_html__( 'Max file size (MB)', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::NUMBER,
			'default'   => 5,
			'min'       => 1,
			'max'       => 25,
			'condition' => [ 'type' => 'file' ],
		] );
		$repeater->add_control( 'accept', [
			'label'       => esc_html__( 'Accepted types', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => '.pdf,.doc,.docx,.jpg,.png',
			'condition'   => [ 'type' => 'file' ],
			'description' => esc_html__( 'HTML accept attribute, e.g. .pdf,image/*', 'rawnaq' ),
		] );
		$repeater->add_control( 'rating_max', [
			'label'     => esc_html__( 'Max rating', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::NUMBER,
			'default'   => 5,
			'min'       => 3,
			'max'       => 10,
			'condition' => [ 'type' => 'rating' ],
		] );

		$default_fields = [];
		if ( function_exists( 'rawnaq_smart_form_preset_for_elementor' ) ) {
			$pack = rawnaq_smart_form_preset_for_elementor( 'side_by_side' );
			$default_fields = $pack['fields'] ?? [];
		}

		$this->add_control( 'fields', [
			'label'       => esc_html__( 'Form Fields', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'default'     => $default_fields,
			'title_field' => '{{{ label }}} ({{{ type }}}) · {{{ width }}}% · step {{{ step }}}',
		] );

		$this->end_controls_section();

		$this->start_controls_section( 's_delivery', [
			'label' => esc_html__( 'Delivery', 'rawnaq' ),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'delivery_email', [
			'label'        => esc_html__( 'Email delivery', 'rawnaq' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );
		$this->add_control( 'email_to', [
			'label'       => esc_html__( 'Send email to', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => get_option( 'admin_email' ),
			'condition'   => [ 'delivery_email' => 'yes' ],
			'label_block' => true,
		] );
		$this->add_control( 'email_subject', [
			'label'       => esc_html__( 'Email subject', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => esc_html__( 'New website inquiry', 'rawnaq' ),
			'condition'   => [ 'delivery_email' => 'yes' ],
			'description' => esc_html__( 'Supports {name}, {pageTitle}, {url}, etc.', 'rawnaq' ),
		] );
		$this->add_control( 'email_html', [
			'label'        => esc_html__( 'Branded HTML email', 'rawnaq' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'description'  => esc_html__( 'Send a styled HTML receipt instead of plain text.', 'rawnaq' ),
			'condition'    => [ 'delivery_email' => 'yes' ],
		] );

		$default_wa = function_exists( 'rawnaq_get_default_wa_number' ) ? rawnaq_get_default_wa_number() : '';
		$this->add_control( 'delivery_whatsapp', [
			'label'        => esc_html__( 'WhatsApp delivery (redirect)', 'rawnaq' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'description'  => esc_html__( 'Opens wa.me with a prefilled message. Blank number uses site default (Rawnaq settings).', 'rawnaq' ),
		] );
		$this->add_control( 'wa_number', [
			'label'       => esc_html__( 'WhatsApp number', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => $default_wa,
			'placeholder' => $default_wa ? $default_wa : '8801XXXXXXXXX',
			'condition'   => [ 'delivery_whatsapp' => 'yes' ],
			'description' => esc_html__( 'Leave blank to use the shared site default number.', 'rawnaq' ),
		] );
		$this->add_control( 'wa_template', [
			'label'       => esc_html__( 'WhatsApp message template', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::TEXTAREA,
			'default'     => "New inquiry:\nName: {name}\nPhone: {phone}\nEmail: {email}\nMessage: {message}\nPage: {pageTitle}\nURL: {url}",
			'condition'   => [ 'delivery_whatsapp' => 'yes' ],
			'description' => esc_html__( '{field_id} plus {pageTitle}, {url}, {siteTitle}, {date}, {time}.', 'rawnaq' ),
		] );

		$this->add_control( 'after_submit', [
			'label'   => esc_html__( 'After submit', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'message',
			'options' => [
				'message'  => esc_html__( 'Show thank-you message', 'rawnaq' ),
				'redirect' => esc_html__( 'Redirect to URL', 'rawnaq' ),
				'whatsapp' => esc_html__( 'Open WhatsApp (visitor)', 'rawnaq' ),
			],
		] );
		$this->add_control( 'redirect_url', [
			'label'       => esc_html__( 'Redirect URL', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::URL,
			'condition'   => [ 'after_submit' => 'redirect' ],
			'placeholder' => 'https://',
		] );

		$this->add_control( 'submit_label', [
			'label'   => esc_html__( 'Submit button text', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => esc_html__( 'Send message', 'rawnaq' ),
		] );
		$this->add_control( 'success_message', [
			'label'   => esc_html__( 'Success message', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => esc_html__( 'Message sent successfully.', 'rawnaq' ),
		] );
		$this->add_control( 'error_message', [
			'label'   => esc_html__( 'Error message', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => esc_html__( 'Please fill in the required fields correctly.', 'rawnaq' ),
		] );

		$this->add_control( 'consent_enabled', [
			'label'        => esc_html__( 'Consent checkbox', 'rawnaq' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
		] );
		$this->add_control( 'consent_text', [
			'label'     => esc_html__( 'Consent text', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::TEXT,
			'default'   => esc_html__( 'I agree to the processing of my data.', 'rawnaq' ),
			'condition' => [ 'consent_enabled' => 'yes' ],
		] );
		$this->add_control( 'log_submissions', [
			'label'        => esc_html__( 'Log submissions in WP admin', 'rawnaq' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->add_control( 'recaptcha_enabled', [
			'label'        => esc_html__( 'reCAPTCHA v3', 'rawnaq' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
			'description'  => esc_html__( 'Requires site + secret keys in Rawnaq → Modules (Smart Form settings).', 'rawnaq' ),
		] );
		$this->add_control( 'webhook_enabled', [
			'label'        => esc_html__( 'Webhook / Slack notify', 'rawnaq' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
		] );
		$this->add_control( 'webhook_url', [
			'label'       => esc_html__( 'Webhook URL', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => '',
			'condition'   => [ 'webhook_enabled' => 'yes' ],
			'label_block' => true,
			'description' => esc_html__( 'Any HTTPS endpoint or Slack Incoming Webhook.', 'rawnaq' ),
		] );

		$this->add_control( 'crm_provider', [
			'label'   => esc_html__( 'CRM / ESP', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'none',
			'options' => [
				'none'      => esc_html__( 'None', 'rawnaq' ),
				'mailchimp' => esc_html__( 'Mailchimp', 'rawnaq' ),
				'hubspot'   => esc_html__( 'HubSpot', 'rawnaq' ),
			],
			'description' => esc_html__( 'Add Mailchimp API key / HubSpot portal ID under Rawnaq → settings. Other CRMs: use the webhook or the rawnaq_smart_form_submission hook.', 'rawnaq' ),
		] );
		$this->add_control( 'crm_audience', [
			'label'       => esc_html__( 'Mailchimp Audience ID', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => '',
			'condition'   => [ 'crm_provider' => 'mailchimp' ],
			'label_block' => true,
		] );
		$this->add_control( 'crm_audience_hs', [
			'label'       => esc_html__( 'HubSpot Form GUID', 'rawnaq' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => '',
			'condition'   => [ 'crm_provider' => 'hubspot' ],
			'label_block' => true,
			'description' => esc_html__( 'The GUID of a HubSpot form in the same portal.', 'rawnaq' ),
		] );

		$this->end_controls_section();

		$this->start_controls_section( 's_style', [
			'label' => esc_html__( 'Style', 'rawnaq' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'accent', [
			'label'     => esc_html__( 'Button background', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#fbbf24',
			'selectors' => [ '{{WRAPPER}} .rawnaq-smart-form' => '--sf-accent: {{VALUE}};' ],
		] );
		$this->add_control( 'button_text', [
			'label'     => esc_html__( 'Button text', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#92400e',
			'selectors' => [ '{{WRAPPER}} .rawnaq-smart-form' => '--sf-btn-text: {{VALUE}};' ],
		] );
		$this->add_control( 'accent_deep', [
			'label'     => esc_html__( 'Deep accent / focus', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#0f766e',
			'selectors' => [ '{{WRAPPER}} .rawnaq-smart-form' => '--sf-accent-deep: {{VALUE}};' ],
		] );
		$this->add_control( 'label_color', [
			'label'     => esc_html__( 'Label color', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rawnaq-smart-form' => '--sf-label: {{VALUE}};' ],
		] );
		$this->add_control( 'input_bg', [
			'label'     => esc_html__( 'Input background', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rawnaq-smart-form' => '--sf-panel: {{VALUE}};' ],
		] );
		$this->add_control( 'input_border', [
			'label'     => esc_html__( 'Input border', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rawnaq-smart-form' => '--sf-line: {{VALUE}};' ],
		] );
		$this->add_control( 'input_text', [
			'label'     => esc_html__( 'Input text', 'rawnaq' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .rawnaq-smart-form' => '--sf-ink: {{VALUE}};' ],
		] );
		$this->add_control( 'input_size', [
			'label'   => esc_html__( 'Input size', 'rawnaq' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'md',
			'options' => [
				'sm' => esc_html__( 'Small', 'rawnaq' ),
				'md' => esc_html__( 'Medium', 'rawnaq' ),
				'lg' => esc_html__( 'Large', 'rawnaq' ),
			],
		] );
		$this->add_control( 'button_full_width', [
			'label'        => esc_html__( 'Full-width button', 'rawnaq' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
		] );
		$this->add_control( 'radius', [
			'label'      => esc_html__( 'Input radius', 'rawnaq' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 28 ] ],
			'default'    => [ 'size' => 12 ],
			'selectors'  => [ '{{WRAPPER}} .rawnaq-smart-form' => '--sf-radius: {{SIZE}}{{UNIT}};' ],
		] );

		$this->end_controls_section();
	}

	/**
	 * @param array $s Settings.
	 * @return array
	 */
	private function build_cfg( $s ) {
		$fields = [];
		foreach ( ( $s['fields'] ?? [] ) as $row ) {
			$fields[] = [
				'id'            => $row['field_id'] ?? '',
				'type'          => $row['type'] ?? 'text',
				'label'         => $row['label'] ?? '',
				'placeholder'   => $row['placeholder'] ?? '',
				'required'      => ( $row['required'] ?? '' ) === 'yes',
				'options'       => $row['options'] ?? '',
				'width'         => $row['width'] ?? '100',
				'step'          => $row['step'] ?? 1,
				'show_if'       => $row['show_if'] ?? '',
				'show_if_value' => $row['show_if_value'] ?? '',
				'default_value' => $row['default_value'] ?? '',
				'max_mb'        => $row['max_mb'] ?? 5,
				'accept'        => $row['accept'] ?? '',
				'rating_max'    => $row['rating_max'] ?? 5,
			];
		}
		$redirect = '';
		if ( ! empty( $s['redirect_url']['url'] ) ) {
			$redirect = $s['redirect_url']['url'];
		}
		$size = $s['input_size'] ?? 'md';
		return [
			'fields'            => $fields,
			'deliveryEmail'     => ( $s['delivery_email'] ?? '' ) === 'yes',
			'deliveryWhatsapp'  => ( $s['delivery_whatsapp'] ?? '' ) === 'yes',
			'emailTo'           => $s['email_to'] ?? '',
			'emailSubject'      => $s['email_subject'] ?? '',
			'waNumber'          => $s['wa_number'] ?? '',
			'waTemplate'        => $s['wa_template'] ?? '',
			'afterSubmit'       => $s['after_submit'] ?? 'message',
			'redirectUrl'       => $redirect,
			'submitLabel'       => $s['submit_label'] ?? '',
			'successMessage'    => $s['success_message'] ?? '',
			'errorMessage'      => $s['error_message'] ?? '',
			'consentEnabled'    => ( $s['consent_enabled'] ?? '' ) === 'yes',
			'consentText'       => $s['consent_text'] ?? '',
			'logSubmissions'    => ( $s['log_submissions'] ?? 'yes' ) === 'yes',
			'recaptchaEnabled'  => ( $s['recaptcha_enabled'] ?? '' ) === 'yes',
			'webhookEnabled'    => ( $s['webhook_enabled'] ?? '' ) === 'yes',
			'webhookUrl'        => $s['webhook_url'] ?? '',
			'emailHtml'         => ( $s['email_html'] ?? 'yes' ) === 'yes',
			'crmProvider'       => $s['crm_provider'] ?? 'none',
			'crmAudience'       => ( ( $s['crm_provider'] ?? 'none' ) === 'hubspot' )
				? ( $s['crm_audience_hs'] ?? '' )
				: ( $s['crm_audience'] ?? '' ),
			'buttonFullWidth'   => ( $s['button_full_width'] ?? '' ) === 'yes',
			'inputSize'         => $size,
			'labelColor'        => $s['label_color'] ?? '',
			'inputBg'           => $s['input_bg'] ?? '',
			'inputBorder'       => $s['input_border'] ?? '',
			'inputText'         => $s['input_text'] ?? '',
			'buttonBg'          => $s['accent'] ?? '',
			'buttonText'        => $s['button_text'] ?? '',
		];
	}

	protected function render() {
		$s   = $this->get_settings_for_display();
		$cfg = $this->build_cfg( $s );
		rawnaq_smart_form_markup( $cfg, 'el-' . $this->get_id() );
	}
}
