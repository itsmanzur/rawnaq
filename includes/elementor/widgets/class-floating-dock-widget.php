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
    public function get_script_depends() { return [ 'rawnaq-floating-dock' ]; }

    protected function register_controls() {
        $this->start_controls_section( 's_content', [
            'label' => esc_html__( 'Dock Items', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
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

        $this->add_control( 'position', [
            'label'   => esc_html__( 'Dock Position', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'bottom',
            'options' => [
                'bottom' => esc_html__( 'Bottom Center', 'rawnaq' ),
                'left'   => esc_html__( 'Sidebar Left', 'rawnaq' ),
                'right'  => esc_html__( 'Sidebar Right', 'rawnaq' ),
            ],
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

    protected function render() {
        $s             = $this->get_settings_for_display();
        $items         = $s['dock_items'] ?? [];
        $pos           = $s['position'] ?? 'bottom';
        $hide_mobile   = ( $s['hide_mobile'] ?? '' ) === 'yes';
        $mobile_labels = ( $s['mobile_labels'] ?? '' ) === 'yes';
        $magnify       = ( $s['magnify'] ?? 'yes' ) === 'yes';
        $max_scale     = isset( $s['max_scale']['size'] ) ? floatval( $s['max_scale']['size'] ) : 1.6;
        $item_size     = isset( $s['icon_size']['size'] ) ? intval( $s['icon_size']['size'] ) : 48;

        $classes = [ 'rawnaq-dock-container', 'pos-' . sanitize_html_class( $pos ) ];
        if ( $hide_mobile ) {
            $classes[] = 'hide-mobile';
        }
        if ( $mobile_labels ) {
            $classes[] = 'mobile-labels';
        }
        ?>
        <nav class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             aria-label="<?php echo esc_attr__( 'Floating dock', 'rawnaq' ); ?>"
             data-magnify="<?php echo $magnify ? '1' : '0'; ?>"
             data-max-scale="<?php echo esc_attr( $max_scale ); ?>"
             data-base-size="<?php echo esc_attr( $item_size ); ?>">
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
        </nav>
        <?php
    }

    protected function content_template() {
        ?>
        <#
        var pos = settings.position || 'bottom';
        var hideMobile = settings.hide_mobile === 'yes';
        var mobileLabels = settings.mobile_labels === 'yes';
        var magnify = settings.magnify === 'yes';
        var maxScale = ( settings.max_scale && settings.max_scale.size ) ? settings.max_scale.size : 1.6;
        var baseSize = ( settings.icon_size && settings.icon_size.size ) ? settings.icon_size.size : 48;
        var classes = 'rawnaq-dock-container pos-' + pos;
        if ( hideMobile ) { classes += ' hide-mobile'; }
        if ( mobileLabels ) { classes += ' mobile-labels'; }
        #>
        <nav class="{{ classes }}" aria-label="Floating dock"
             data-magnify="{{ magnify ? '1' : '0' }}"
             data-max-scale="{{ maxScale }}"
             data-base-size="{{ baseSize }}">
            <#
            if ( settings.dock_items ) {
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
