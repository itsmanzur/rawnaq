<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rawnaq_Floating_Dock_Widget extends \Elementor\Widget_Base {

    public function get_name()       { return 'rawnaq_floating_dock'; }
    public function get_title()      { return esc_html__( 'Floating Dock Menu', 'rawnaq' ); }
    public function get_icon()       { return 'eicon-navigator'; }
    public function get_categories() { return [ 'rawnaq' ]; }

    public function get_style_depends()  { return [ 'rawnaq-floating-dock', 'dashicons' ]; }
    public function get_script_depends() { return [ 'rawnaq-floating-dock', 'rawnaq-qrcode' ]; }

    protected function register_controls() {
        $this->start_controls_section( 's_whatsapp_mode', [
            'label' => esc_html__( 'WhatsApp Contact Mode', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'whatsapp_mode', [
            'label'        => esc_html__( 'Enable WhatsApp Mode', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'no',
        ] );

        $this->add_control( 'primary_channel', [
            'label'   => esc_html__( 'Primary Floating Channel', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'whatsapp',
            'options' => [
                'whatsapp'  => esc_html__( 'WhatsApp Chat', 'rawnaq' ),
                'call'      => esc_html__( 'Phone Call', 'rawnaq' ),
                'messenger' => esc_html__( 'FB Messenger', 'rawnaq' ),
            ],
            'condition' => [ 'whatsapp_mode' => 'yes' ],
        ] );

        $ar = new \Elementor\Repeater();
        $ar->add_control( 'agent_name', [
            'label'       => esc_html__( 'Agent / Department Name', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'Sales Support',
            'label_block' => true,
        ] );
        $ar->add_control( 'agent_role', [
            'label'       => esc_html__( 'Role Label', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'Online Agent',
        ] );
        $ar->add_control( 'agent_number', [
            'label'       => esc_html__( 'WhatsApp Number', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => '8801700000000',
            'description' => esc_html__( 'Include country code without + or spaces', 'rawnaq' ),
        ] );
        $ar->add_control( 'agent_avatar', [
            'label'   => esc_html__( 'Avatar Photo', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::MEDIA,
        ] );
        $ar->add_control( 'agent_msg', [
            'label'       => esc_html__( 'Prefilled Message', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXTAREA,
            'placeholder' => 'Hello, I saw {pageTitle} ({url}) and have a question…',
            'description' => esc_html__( 'Tokens: {pageTitle} {url} {siteTitle} {date} {time} · Woo: {productName} {price} {sku} {productUrl}', 'rawnaq' ),
        ] );

        $this->add_control( 'whatsapp_agents', [
            'label'       => esc_html__( 'WhatsApp Agents', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::REPEATER,
            'fields'      => $ar->get_controls(),
            'default'     => [
                [
                    'agent_name'   => 'Customer Support',
                    'agent_role'   => 'Live Support',
                    'agent_number' => '8801700000000',
                    'agent_msg'    => 'আসসালামু আলাইকুম, আমি {pageTitle} পেজ থেকে লিখছি ({url})।',
                ]
            ],
            'title_field' => '{{{ agent_name }}}',
            'condition'   => [ 'whatsapp_mode' => 'yes' ],
        ] );

        $this->add_control( 'default_msg', [
            'label'       => esc_html__( 'Default Prefilled Message', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXTAREA,
            'default'     => 'আসসালামু আলাইকুম, আমি {pageTitle} থেকে যোগাযোগ করছি।',
            'description' => esc_html__( 'Used when an agent has no message of their own. Same tokens as above.', 'rawnaq' ),
            'condition'   => [ 'whatsapp_mode' => 'yes' ],
        ] );

        $this->add_control( 'sec_call', [
            'label'       => esc_html__( 'Secondary Phone Number', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => '+8801700000000',
            'condition'   => [ 'whatsapp_mode' => 'yes' ],
        ] );
        $this->add_control( 'sec_messenger', [
            'label'       => esc_html__( 'Secondary Messenger User', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'username',
            'description' => 'e.g. rawnaq.support',
            'condition'   => [ 'whatsapp_mode' => 'yes' ],
        ] );
        $this->add_control( 'sec_email', [
            'label'       => esc_html__( 'Secondary Email Address', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'support@example.com',
            'condition'   => [ 'whatsapp_mode' => 'yes' ],
        ] );
        $this->add_control( 'sec_telegram', [
            'label'       => esc_html__( 'Secondary Telegram User', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'telegram_username',
            'condition'   => [ 'whatsapp_mode' => 'yes' ],
        ] );

        $this->add_control( 'timezone', [
            'label'       => esc_html__( 'Business Timezone', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::SELECT,
            'default'     => 'Asia/Dhaka',
            'description' => esc_html__( 'DST-aware IANA zones. Legacy fixed UTC offsets still work for older setups.', 'rawnaq' ),
            'options'     => [
                'UTC'                 => 'UTC (GMT)',
                'America/New_York'    => 'New York (Eastern)',
                'America/Chicago'     => 'Chicago (Central)',
                'America/Los_Angeles' => 'Los Angeles (Pacific)',
                'America/Sao_Paulo'   => 'São Paulo',
                'Europe/London'       => 'London',
                'Europe/Paris'        => 'Paris / Berlin',
                'Europe/Istanbul'     => 'Istanbul',
                'Africa/Cairo'        => 'Cairo',
                'Asia/Dubai'          => 'Dubai',
                'Asia/Riyadh'         => 'Riyadh',
                'Asia/Karachi'        => 'Karachi',
                'Asia/Kolkata'        => 'India (Kolkata)',
                'Asia/Dhaka'          => 'Bangladesh (Dhaka)',
                'Asia/Jakarta'        => 'Jakarta',
                'Asia/Singapore'      => 'Singapore',
                'Asia/Shanghai'       => 'China (Shanghai)',
                'Asia/Tokyo'          => 'Tokyo',
                'Australia/Sydney'    => 'Sydney',
                // Legacy fixed offsets (kept for backward compatibility).
                'UTC+0'               => 'UTC+0 (legacy)',
                'UTC+1'               => 'UTC+1 (legacy)',
                'UTC+5.5'             => 'UTC+5:30 (legacy)',
                'UTC+6'               => 'UTC+6 (legacy)',
                'UTC+7'               => 'UTC+7 (legacy)',
                'UTC+8'               => 'UTC+8 (legacy)',
            ],
            'condition'   => [ 'whatsapp_mode' => 'yes' ],
        ] );

        $this->add_control( 'schedule_heading', [
            'label'     => esc_html__( 'Business Weekly Schedule', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => [ 'whatsapp_mode' => 'yes' ],
        ] );

        $days = [
            'mon' => __( 'Monday', 'rawnaq' ),
            'tue' => __( 'Tuesday', 'rawnaq' ),
            'wed' => __( 'Wednesday', 'rawnaq' ),
            'thu' => __( 'Thursday', 'rawnaq' ),
            'fri' => __( 'Friday', 'rawnaq' ),
            'sat' => __( 'Saturday', 'rawnaq' ),
            'sun' => __( 'Sunday', 'rawnaq' ),
        ];
        foreach ( $days as $key => $name ) {
            $this->add_control( $key . '_enabled', [
                /* translators: %s: weekday name */
                'label'        => sprintf( esc_html__( 'Active %s', 'rawnaq' ), $name ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [ 'whatsapp_mode' => 'yes' ],
            ] );
            $this->add_control( $key . '_open', [
                /* translators: %s: weekday name */
                'label'       => sprintf( esc_html__( '%s Open Time', 'rawnaq' ), $name ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'default'     => '09:00',
                'placeholder' => 'HH:MM (24h)',
                'condition'   => [ 'whatsapp_mode' => 'yes', $key . '_enabled' => 'yes' ],
            ] );
            $this->add_control( $key . '_close', [
                /* translators: %s: weekday name */
                'label'       => sprintf( esc_html__( '%s Close Time', 'rawnaq' ), $name ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'default'     => '18:00',
                'placeholder' => 'HH:MM (24h)',
                'condition'   => [ 'whatsapp_mode' => 'yes', $key . '_enabled' => 'yes' ],
            ] );
        }

        $this->add_control( 'off_hours_behavior', [
            'label'   => esc_html__( 'Off-hours Behavior', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'offline_badge',
            'options' => [
                'hide'          => esc_html__( 'Hide Dock Completely', 'rawnaq' ),
                'offline_badge' => esc_html__( 'Show with Offline Badge', 'rawnaq' ),
                'lead_form'     => esc_html__( 'Offline lead / email form', 'rawnaq' ),
                'redirect'      => esc_html__( 'Redirect Clicks to URL', 'rawnaq' ),
            ],
            'separator' => 'before',
            'condition' => [ 'whatsapp_mode' => 'yes' ],
        ] );

        $this->add_control( 'off_hours_redirect_url', [
            'label'       => esc_html__( 'Offline Redirect URL / Contact link', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'https://example.com/contact',
            'condition'   => [ 'whatsapp_mode' => 'yes', 'off_hours_behavior' => 'redirect' ],
        ] );

        $this->add_control( 'off_hours_email', [
            'label'       => esc_html__( 'Offline Lead Email', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'hello@example.com',
            'description' => esc_html__( 'Used by the offline lead form (mailto). Falls back to Secondary Email if empty.', 'rawnaq' ),
            'condition'   => [ 'whatsapp_mode' => 'yes', 'off_hours_behavior' => 'lead_form' ],
        ] );

        $this->add_control( 'off_hours_form_note', [
            'label'       => esc_html__( 'Offline Form Note', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXTAREA,
            'default'     => 'We are offline right now. Leave a message and we will reply by email.',
            'rows'        => 2,
            'condition'   => [ 'whatsapp_mode' => 'yes', 'off_hours_behavior' => 'lead_form' ],
        ] );

        $this->add_control( 'desktop_action', [
            'label'   => esc_html__( 'Desktop Chat Action', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'choice',
            'options' => [
                'choice' => esc_html__( 'Show options (Web + QR)', 'rawnaq' ),
                'web'    => esc_html__( 'Open WhatsApp Web directly', 'rawnaq' ),
                'qr'     => esc_html__( 'QR first (Web still available)', 'rawnaq' ),
            ],
            'description' => esc_html__( 'On phones the app always opens directly. Desktop can offer both Web chat and QR scan.', 'rawnaq' ),
            'condition'   => [ 'whatsapp_mode' => 'yes' ],
        ] );

        $this->add_control( 'trigger_delay', [
            'label'   => esc_html__( 'Trigger Delay (sec)', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 0,
            'min'     => 0,
            'max'     => 60,
            'condition' => [ 'whatsapp_mode' => 'yes' ],
        ] );

        $this->add_control( 'trigger_scroll', [
            'label'   => esc_html__( 'Trigger Scroll (%)', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 0,
            'min'     => 0,
            'max'     => 100,
            'condition' => [ 'whatsapp_mode' => 'yes' ],
        ] );

        $this->add_control( 'greeting_text', [
            'label'       => esc_html__( 'Greeting Bubble Message', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => esc_html__( 'আসসালামু আলাইকুম, সাহায্য লাগবে?', 'rawnaq' ),
            'label_block' => true,
            'condition'   => [ 'whatsapp_mode' => 'yes' ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 's_content', [
            'label' => esc_html__( 'Dock Items', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            'condition' => [ 'whatsapp_mode!' => 'yes' ],
        ] );

        $r = new \Elementor\Repeater();

        $r->add_control( 'label', [
            'label'       => esc_html__( 'Name / Tooltip', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'Action Name',
            'label_block' => true,
        ] );

        $r->add_control( 'selected_icon', [
            'label'   => esc_html__( 'Icon', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::ICONS,
            'default' => [
                'value'   => 'fas fa-home',
                'library' => 'fa-solid',
            ],
        ] );

        $r->add_control( 'link', [
            'label'       => esc_html__( 'Link', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::URL,
            'placeholder' => 'https://example.com',
            'default'     => [
                'url'         => '#',
                'is_external' => false,
                'nofollow'    => false,
            ],
        ] );

        $r->add_control( 'badge', [
            'label'       => esc_html__( 'Badge', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => '3',
        ] );

        $r->add_control( 'color', [
            'label'   => esc_html__( 'Icon Hover Color', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::COLOR,
            'default' => '#6366f1',
        ] );

        $this->add_control( 'dock_items', [
            'label'       => esc_html__( 'Dock Menu Items', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::REPEATER,
            'fields'      => $r->get_controls(),
            'default'     => [
                [
                    'label'         => 'Home',
                    'selected_icon' => [ 'value' => 'fas fa-home', 'library' => 'fa-solid' ],
                    'link'          => [ 'url' => home_url( '/' ) ],
                    'badge'         => '',
                    'color'         => '#6366f1',
                ],
                [
                    'label'         => 'Messages',
                    'selected_icon' => [ 'value' => 'fas fa-envelope', 'library' => 'fa-solid' ],
                    'link'          => [ 'url' => '#' ],
                    'badge'         => '3',
                    'color'         => '#6366f1',
                ],
                [
                    'label'         => 'Statistics',
                    'selected_icon' => [ 'value' => 'fas fa-chart-bar', 'library' => 'fa-solid' ],
                    'link'          => [ 'url' => '#' ],
                    'badge'         => '',
                    'color'         => '#6366f1',
                ],
                [
                    'label'         => 'Settings',
                    'selected_icon' => [ 'value' => 'fas fa-cog', 'library' => 'fa-solid' ],
                    'link'          => [ 'url' => '#' ],
                    'badge'         => '',
                    'color'         => '#6366f1',
                ],
            ],
            'title_field' => '{{{ label }}}',
        ] );
        $this->end_controls_section();

        $this->start_controls_section( 's_layout', [
            'label' => esc_html__( 'Layout', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'position_wa', [
            'label'   => esc_html__( 'Dock Position', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'right',
            'options' => [
                'left'   => esc_html__( 'Bottom Left', 'rawnaq' ),
                'right'  => esc_html__( 'Bottom Right', 'rawnaq' ),
            ],
            'condition' => [ 'whatsapp_mode' => 'yes' ],
        ] );

        $this->add_control( 'position', [
            'label'   => esc_html__( 'Dock Position', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'bottom',
            'options' => [
                'bottom' => esc_html__( 'Bottom Center', 'rawnaq' ),
                'left'   => esc_html__( 'Sidebar Left', 'rawnaq' ),
                'right'  => esc_html__( 'Sidebar Right', 'rawnaq' ),
            ],
            'condition' => [ 'whatsapp_mode!' => 'yes' ],
        ] );

        $this->add_responsive_control( 'offset', [
            'label'      => esc_html__( 'Edge Offset', 'rawnaq' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 0, 'max' => 80 ] ],
            'default'    => [ 'unit' => 'px', 'size' => 20 ],
            'selectors'  => [
                '{{WRAPPER}} .rawnaq-dock-container' => '--dock-offset: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'hide_mobile', [
            'label'        => esc_html__( 'Hide on Mobile', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => esc_html__( 'Yes', 'rawnaq' ),
            'label_off'    => esc_html__( 'No', 'rawnaq' ),
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $this->add_control( 'mobile_labels', [
            'label'        => esc_html__( 'Show Labels on Mobile', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => esc_html__( 'Yes', 'rawnaq' ),
            'label_off'    => esc_html__( 'No', 'rawnaq' ),
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => [ 'hide_mobile!' => 'yes' ],
        ] );

        $this->add_control( 'hide_desktop', [
            'label'        => esc_html__( 'Hide on Desktop', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $this->add_responsive_control( 'safe_offset', [
            'label'      => esc_html__( 'Extra Bottom Offset (cookie / banners)', 'rawnaq' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 0, 'max' => 160 ] ],
            'default'    => [ 'unit' => 'px', 'size' => 0 ],
            'selectors'  => [
                '{{WRAPPER}} .rawnaq-dock-container' => '--dock-safe-offset: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 's_visibility', [
            'label' => esc_html__( 'Page Visibility', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'vis_mode', [
            'label'   => esc_html__( 'Show Dock On', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'all',
            'options' => [
                'all'     => esc_html__( 'Entire site', 'rawnaq' ),
                'include' => esc_html__( 'Only selected pages', 'rawnaq' ),
                // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- UI mode key, not a get_posts() arg.
                'exclude' => esc_html__( 'Everywhere except selected', 'rawnaq' ),
            ],
        ] );

        $this->add_control( 'vis_pages', [
            'label'       => esc_html__( 'Selected Pages / Posts', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::SELECT2,
            'multiple'    => true,
            'options'     => $this->get_visibility_page_options(),
            'label_block' => true,
            'condition'   => [ 'vis_mode!' => 'all' ],
        ] );

        $this->add_control( 'vis_include_front', [
            'label'        => esc_html__( 'Also match Front Page', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => [ 'vis_mode!' => 'all' ],
        ] );

        $this->add_control( 'vis_include_products', [
            'label'        => esc_html__( 'Also match WooCommerce Products', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => [ 'vis_mode!' => 'all' ],
        ] );

        $this->add_control( 'track_clicks', [
            'label'        => esc_html__( 'Track Clicks (site-wide counter)', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'description'  => esc_html__( 'Totals appear under Rawnaq → Dock Stats in wp-admin.', 'rawnaq' ),
            'separator'    => 'before',
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 's_style', [
            'label' => esc_html__( 'Dock Style', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'bg_color', [
            'label'     => esc_html__( 'Dock Background', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => 'rgba(255, 255, 255, 0.55)',
            'selectors' => [ '{{WRAPPER}} .rawnaq-dock-container' => '--dock-bg: {{VALUE}};' ],
        ] );

        $this->add_control( 'border_color', [
            'label'     => esc_html__( 'Dock Border Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => 'rgba(255, 255, 255, 0.5)',
            'selectors' => [ '{{WRAPPER}} .rawnaq-dock-container' => '--dock-border: {{VALUE}};' ],
        ] );

        $this->add_control( 'blur', [
            'label'   => esc_html__( 'Glass Blur (px)', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
            'default' => [ 'size' => 16 ],
            'selectors' => [ '{{WRAPPER}} .rawnaq-dock-container' => '--dock-blur: {{SIZE}}px;' ],
        ] );

        $this->add_control( 'dock_radius', [
            'label'   => esc_html__( 'Dock Radius', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
            'default' => [ 'size' => 24 ],
            'selectors' => [ '{{WRAPPER}} .rawnaq-dock-container' => '--dock-radius: {{SIZE}}px;' ],
        ] );

        $this->add_control( 'dock_gap', [
            'label'   => esc_html__( 'Item Gap', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => [ 'px' => [ 'min' => 0, 'max' => 32 ] ],
            'default' => [ 'size' => 12 ],
            'selectors' => [ '{{WRAPPER}} .rawnaq-dock-container' => '--dock-gap: {{SIZE}}px;' ],
        ] );

        $this->add_control( 'dock_padding', [
            'label'   => esc_html__( 'Dock Padding', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => [ 'px' => [ 'min' => 4, 'max' => 28 ] ],
            'default' => [ 'size' => 10 ],
            'selectors' => [ '{{WRAPPER}} .rawnaq-dock-container' => '--dock-pad: {{SIZE}}px;' ],
        ] );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'dock_shadow',
                'selector' => '{{WRAPPER}} .rawnaq-dock-container',
            ]
        );

        $this->add_control( 'item_bg', [
            'label'     => esc_html__( 'Item Background', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [ '{{WRAPPER}} .rawnaq-dock-container' => '--dock-item-bg: {{VALUE}};' ],
            'separator' => 'before',
        ] );

        $this->add_control( 'icon_color', [
            'label'     => esc_html__( 'Icon Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#444444',
            'selectors' => [ '{{WRAPPER}} .rawnaq-dock-container' => '--dock-icon: {{VALUE}};' ],
        ] );

        $this->add_control( 'icon_size', [
            'label'   => esc_html__( 'Item Size', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => [ 'px' => [ 'min' => 32, 'max' => 72 ] ],
            'default' => [ 'size' => 48 ],
            'selectors' => [ '{{WRAPPER}} .rawnaq-dock-container' => '--dock-item-size: {{SIZE}}px;' ],
        ] );

        $this->add_control( 'item_radius', [
            'label'   => esc_html__( 'Item Radius', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => [ 'px' => [ 'min' => 0, 'max' => 24 ] ],
            'default' => [ 'size' => 12 ],
            'selectors' => [ '{{WRAPPER}} .rawnaq-dock-container' => '--dock-item-radius: {{SIZE}}px;' ],
        ] );

        $this->add_control( 'badge_bg', [
            'label'     => esc_html__( 'Badge Background', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#ef4444',
            'selectors' => [ '{{WRAPPER}} .rawnaq-dock-container' => '--dock-badge-bg: {{VALUE}};' ],
            'separator' => 'before',
        ] );

        $this->add_control( 'badge_color', [
            'label'     => esc_html__( 'Badge Text Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [ '{{WRAPPER}} .rawnaq-dock-container' => '--dock-badge-color: {{VALUE}};' ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 's_motion', [
            'label' => esc_html__( 'Magnify Effect', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'magnify', [
            'label'        => esc_html__( 'Enable Magnify', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => esc_html__( 'On', 'rawnaq' ),
            'label_off'    => esc_html__( 'Off', 'rawnaq' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'max_scale', [
            'label'   => esc_html__( 'Max Scale', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => [
                'px' => [ 'min' => 1.1, 'max' => 2, 'step' => 0.05 ],
            ],
            'default' => [ 'size' => 1.6 ],
            'condition' => [ 'magnify' => 'yes' ],
        ] );

        $this->end_controls_section();
    }

    private function get_visibility_page_options() {
        $opts  = [];
        $posts = get_posts( [
            'post_type'      => [ 'page', 'post', 'product' ],
            'post_status'    => 'publish',
            'posts_per_page' => 200,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );
        foreach ( $posts as $p ) {
            $opts[ (string) $p->ID ] = sprintf( '%s (#%d)', $p->post_title, $p->ID );
        }
        return $opts;
    }

    private function url_attrs( $url_setting ) {
        if ( empty( $url_setting['url'] ) ) {
            return ' href="#"';
        }
        $attrs = ' href="' . esc_url( $url_setting['url'] ) . '"';
        if ( ! empty( $url_setting['is_external'] ) ) {
            $attrs .= ' target="_blank"';
        }
        $rel = [];
        if ( ! empty( $url_setting['is_external'] ) ) {
            $rel[] = 'noopener';
        }
        if ( ! empty( $url_setting['nofollow'] ) ) {
            $rel[] = 'nofollow';
        }
        if ( $rel ) {
            $attrs .= ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"';
        }
        return $attrs;
    }

    private function render_item_icon( $item ) {
        if ( ! empty( $item['selected_icon']['value'] ) ) {
            echo '<span class="rawnaq-dock-icon">';
            \Elementor\Icons_Manager::render_icon( $item['selected_icon'], [ 'aria-hidden' => 'true' ] );
            echo '</span>';
            return;
        }
        // Legacy dashicon fallback
        if ( ! empty( $item['icon'] ) ) {
            echo '<span class="rawnaq-dock-icon"><span class="dashicons ' . esc_attr( $item['icon'] ) . '" aria-hidden="true"></span></span>';
        }
    }

    private function get_weekly_schedule_payload( $s ) {
        $out = [];
        $days = [ 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ];
        foreach ( $days as $day ) {
            $out[ $day ] = [
                'enabled' => ( $s[ $day . '_enabled' ] ?? 'yes' ) === 'yes',
                'open'    => $s[ $day . '_open' ] ?? '09:00',
                'close'   => $s[ $day . '_close' ] ?? '18:00',
            ];
        }
        return $out;
    }

    private function get_whatsapp_agents_payload( $agents ) {
        $out = [];
        foreach ( $agents as $a ) {
            $out[] = [
                'name'   => sanitize_text_field( $a['agent_name'] ?? '' ),
                'role'   => sanitize_text_field( $a['agent_role'] ?? '' ),
                'number' => sanitize_text_field( $a['agent_number'] ?? '' ) ?: ( function_exists( 'rawnaq_get_default_wa_number' ) ? rawnaq_get_default_wa_number() : '' ),
                'avatar' => ! empty( $a['agent_avatar']['url'] ) ? esc_url_raw( $a['agent_avatar']['url'] ) : '',
                'msg'    => sanitize_textarea_field( $a['agent_msg'] ?? '' ),
            ];
        }
        return $out;
    }

    protected function render() {
        $s = $this->get_settings_for_display();

        $visible = rawnaq_dock_is_visible( [
            'mode'             => $s['vis_mode'] ?? 'all',
            'ids'              => $s['vis_pages'] ?? [],
            'include_front'    => ( $s['vis_include_front'] ?? '' ) === 'yes',
            'include_products' => ( $s['vis_include_products'] ?? '' ) === 'yes',
        ] );
        if ( ! $visible ) {
            return;
        }

        $is_wa_mode    = ( $s['whatsapp_mode'] ?? '' ) === 'yes';
        $items         = $s['dock_items'] ?? [];
        $pos           = $is_wa_mode ? ( $s['position_wa'] ?? 'right' ) : ( $s['position'] ?? 'bottom' );
        $hide_mobile   = ( $s['hide_mobile'] ?? '' ) === 'yes';
        $hide_desktop  = ( $s['hide_desktop'] ?? '' ) === 'yes';
        $mobile_labels = ( $s['mobile_labels'] ?? '' ) === 'yes';
        $magnify       = ( $s['magnify'] ?? 'yes' ) === 'yes';
        $track_clicks  = ( $s['track_clicks'] ?? 'yes' ) === 'yes';
        $max_scale     = isset( $s['max_scale']['size'] ) ? floatval( $s['max_scale']['size'] ) : 1.6;
        $item_size     = isset( $s['icon_size']['size'] ) ? intval( $s['icon_size']['size'] ) : 48;

        $classes = [ 'rawnaq-dock-container', 'pos-' . sanitize_html_class( $pos ) ];
        if ( $is_wa_mode ) {
            $classes[] = 'rawnaq-whatsapp-dock-mode';
        }
        if ( $hide_mobile ) {
            $classes[] = 'hide-mobile';
        }
        if ( $hide_desktop ) {
            $classes[] = 'hide-desktop';
        }
        if ( $mobile_labels ) {
            $classes[] = 'mobile-labels';
        }

        $wa_attr = '';
        if ( $is_wa_mode ) {
            $wa_cfg  = [
                'whatsappMode'     => true,
                'primaryChannel'   => sanitize_key( $s['primary_channel'] ?? 'whatsapp' ),
                'agents'           => $this->get_whatsapp_agents_payload( $s['whatsapp_agents'] ?? [] ),
                'defaultMsg'       => sanitize_textarea_field( $s['default_msg'] ?? '' ),
                'pageContext'      => function_exists( 'rawnaq_get_wa_page_context' ) ? rawnaq_get_wa_page_context() : [],
                'secCall'          => sanitize_text_field( $s['sec_call'] ?? '' ),
                'secMessenger'     => sanitize_text_field( $s['sec_messenger'] ?? '' ),
                'secEmail'         => sanitize_email( $s['sec_email'] ?? '' ),
                'secTelegram'      => sanitize_text_field( $s['sec_telegram'] ?? '' ),
                'timezone'         => sanitize_text_field( $s['timezone'] ?? 'UTC+6' ),
                'schedule'         => $this->get_weekly_schedule_payload( $s ),
                'offHoursBehavior' => sanitize_key( $s['off_hours_behavior'] ?? 'offline_badge' ),
                'offHoursRedirect' => esc_url_raw( $s['off_hours_redirect_url'] ?? '' ),
                'offHoursEmail'    => sanitize_email( $s['off_hours_email'] ?? '' ),
                'offHoursFormNote' => sanitize_textarea_field( $s['off_hours_form_note'] ?? '' ),
                'qrFallback'       => ( $s['desktop_action'] ?? 'choice' ) !== 'web',
                'desktopAction'    => in_array( ( $s['desktop_action'] ?? 'choice' ), [ 'choice', 'web', 'qr' ], true )
                    ? ( $s['desktop_action'] ?? 'choice' )
                    : 'choice',
                'triggerDelay'     => absint( $s['trigger_delay'] ?? 0 ),
                'triggerScroll'    => absint( $s['trigger_scroll'] ?? 0 ),
                'greetingText'     => sanitize_text_field( $s['greeting_text'] ?? '' ),
                'trackClicks'      => $track_clicks,
            ];
            $wa_attr = rawurlencode( wp_json_encode( $wa_cfg ) );
        }
        ?>
        <nav class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             aria-label="<?php echo esc_attr__( 'Floating dock', 'rawnaq' ); ?>"
             data-magnify="<?php echo $magnify ? '1' : '0'; ?>"
             data-max-scale="<?php echo esc_attr( $max_scale ); ?>"
             data-base-size="<?php echo esc_attr( $item_size ); ?>"
             data-track-clicks="<?php echo $track_clicks ? '1' : '0'; ?>"
             <?php if ( $wa_attr ) : ?>
             data-wa-dock="<?php echo esc_attr( $wa_attr ); ?>"
             <?php endif; ?>>
            <?php if ( ! $is_wa_mode ) : ?>
                <?php foreach ( $items as $item ) :
                    $label = $item['label'] ?? '';
                    $badge = trim( (string) ( $item['badge'] ?? '' ) );
                    $hover = $item['color'] ?? '#6366f1';
                    $link  = $item['link'] ?? [];
                    ?>
                    <a class="rawnaq-dock-item"
                       style="--hover-color: <?php echo esc_attr( $hover ); ?>;"
                       aria-label="<?php echo esc_attr( $label ); ?>"
                       <?php
					   // Already escaped inside url_attrs().
					   echo $this->url_attrs( $link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					   ?>>
                        <?php $this->render_item_icon( $item ); ?>
                        <?php if ( $badge !== '' ) : ?>
                            <span class="rawnaq-dock-badge"><?php echo esc_html( $badge ); ?></span>
                        <?php endif; ?>
                        <?php if ( $label ) : ?>
                            <span class="rawnaq-dock-tooltip"><?php echo esc_html( $label ); ?></span>
                            <span class="rawnaq-dock-mobile-label"><?php echo esc_html( $label ); ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </nav>
        <?php
    }

    protected function content_template() {
        ?>
        <#
        var isWa = settings.whatsapp_mode === 'yes';
        var pos = isWa ? ( settings.position_wa || 'right' ) : ( settings.position || 'bottom' );
        var hideMobile = settings.hide_mobile === 'yes';
        var hideDesktop = settings.hide_desktop === 'yes';
        var mobileLabels = settings.mobile_labels === 'yes';
        var magnify = settings.magnify === 'yes';
        var trackClicks = settings.track_clicks !== 'no';
        var maxScale = ( settings.max_scale && settings.max_scale.size ) ? settings.max_scale.size : 1.6;
        var baseSize = ( settings.icon_size && settings.icon_size.size ) ? settings.icon_size.size : 48;

        var classes = 'rawnaq-dock-container pos-' + pos;
        if ( isWa ) { classes += ' rawnaq-whatsapp-dock-mode'; }
        if ( hideMobile ) { classes += ' hide-mobile'; }
        if ( hideDesktop ) { classes += ' hide-desktop'; }
        if ( mobileLabels ) { classes += ' mobile-labels'; }

        var waDockAttr = '';
        if ( isWa ) {
            var days = ['mon','tue','wed','thu','fri','sat','sun'];
            var schedule = {};
            days.forEach(function(day) {
                schedule[day] = {
                    enabled: settings[day + '_enabled'] === 'yes',
                    open: settings[day + '_open'] || '09:00',
                    close: settings[day + '_close'] || '18:00'
                };
            });
            var agents = [];
            if ( settings.whatsapp_agents && settings.whatsapp_agents.length ) {
                settings.whatsapp_agents.forEach(function(a) {
                    agents.push({
                        name: a.agent_name || '',
                        role: a.agent_role || '',
                        number: a.agent_number || '',
                        avatar: (a.agent_avatar && a.agent_avatar.url) ? a.agent_avatar.url : '',
                        msg: a.agent_msg || ''
                    });
                });
            }
            var waCfg = {
                whatsappMode: true,
                primaryChannel: settings.primary_channel || 'whatsapp',
                agents: agents,
                defaultMsg: settings.default_msg || '',
                pageContext: {},
                secCall: settings.sec_call || '',
                secMessenger: settings.sec_messenger || '',
                secEmail: settings.sec_email || '',
                secTelegram: settings.sec_telegram || '',
                timezone: settings.timezone || 'UTC+6',
                schedule: schedule,
                offHoursBehavior: settings.off_hours_behavior || 'offline_badge',
                offHoursRedirect: settings.off_hours_redirect_url || '',
                offHoursEmail: settings.off_hours_email || '',
                offHoursFormNote: settings.off_hours_form_note || '',
                qrFallback: (settings.desktop_action || 'choice') !== 'web',
                desktopAction: settings.desktop_action || 'choice',
                triggerDelay: parseInt(settings.trigger_delay, 10) || 0,
                triggerScroll: parseInt(settings.trigger_scroll, 10) || 0,
                greetingText: settings.greeting_text || '',
                trackClicks: trackClicks
            };
            waDockAttr = encodeURIComponent(JSON.stringify(waCfg));
        }
        #>
        <nav class="{{ classes }}" aria-label="Floating dock"
             data-magnify="{{ magnify ? '1' : '0' }}"
             data-max-scale="{{ maxScale }}"
             data-base-size="{{ baseSize }}"
             data-track-clicks="{{ trackClicks ? '1' : '0' }}"
             <# if ( waDockAttr ) { #>data-wa-dock="{{ waDockAttr }}"<# } #>>
            <#
            if ( ! isWa && settings.dock_items ) {
                _.each( settings.dock_items, function( item ) {
                    var link = ( item.link && item.link.url ) ? item.link.url : '#';
                    var iconHTML = elementor.helpers.renderIcon( view, item.selected_icon, { 'aria-hidden': true }, 'i', 'object' );
                    #>
                    <a class="rawnaq-dock-item" href="{{ link }}"
                       style="--hover-color: {{ item.color || '#6366f1' }};"
                       aria-label="{{ item.label }}">
                        <span class="rawnaq-dock-icon">
                            <# if ( iconHTML && iconHTML.rendered ) { #>
                                {{{ iconHTML.value }}}
                            <# } else if ( item.icon ) { #>
                                <span class="dashicons {{ item.icon }}"></span>
                            <# } #>
                        </span>
                        <# if ( item.badge ) { #>
                            <span class="rawnaq-dock-badge">{{{ item.badge }}}</span>
                        <# } #>
                        <# if ( item.label ) { #>
                            <span class="rawnaq-dock-tooltip">{{{ item.label }}}</span>
                            <span class="rawnaq-dock-mobile-label">{{{ item.label }}}</span>
                        <# } #>
                    </a>
                    <#
                } );
            }
            #>
        </nav>
        <?php
    }
}
