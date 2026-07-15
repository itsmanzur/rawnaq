<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rawnaq_Scroll_Timeline_Widget extends \Elementor\Widget_Base {

    public function get_name()       { return 'rawnaq_scroll_timeline'; }
    public function get_title()      { return esc_html__( 'Scroll Sync Timeline', 'rawnaq' ); }
    public function get_icon()       { return 'eicon-time-line'; }
    public function get_categories() { return [ 'rawnaq' ]; }

    public function get_style_depends()  { return [ 'rawnaq-scroll-timeline', 'rawnaq-fonts', 'dashicons' ]; }
    public function get_script_depends() { return [ 'rawnaq-scroll-timeline' ]; }

    protected function register_controls() {
        $this->start_controls_section( 's_content', [
            'label' => esc_html__( 'Timeline Steps', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $r = new \Elementor\Repeater();

        $r->add_control( 'meta', [
            'label'       => esc_html__( 'Date / Label', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => '2024 · Phase 1',
            'label_block' => true,
        ] );

        $r->add_control( 'title', [
            'label'       => esc_html__( 'Step Title', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'Milestone Step',
            'label_block' => true,
        ] );

        $r->add_control( 'desc', [
            'label'   => esc_html__( 'Description', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::TEXTAREA,
            'default' => 'Add some detail description about this milestone step.',
            'rows'    => 3,
        ] );

        $r->add_control( 'selected_icon', [
            'label'   => esc_html__( 'Icon', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::ICONS,
            'default' => [
                'value'   => '',
                'library' => '',
            ],
        ] );

        $r->add_control( 'image', [
            'label'   => esc_html__( 'Image', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::MEDIA,
            'default' => [ 'url' => '' ],
        ] );

        $r->add_control( 'cta_text', [
            'label'       => esc_html__( 'CTA Text', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => 'Learn more',
        ] );

        $r->add_control( 'cta_link', [
            'label'       => esc_html__( 'CTA Link', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::URL,
            'placeholder' => 'https://example.com',
            'default'     => [
                'url'         => '',
                'is_external' => false,
                'nofollow'    => false,
            ],
        ] );

        $this->add_control( 'steps', [
            'label'       => esc_html__( 'Timeline Steps', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::REPEATER,
            'fields'      => $r->get_controls(),
            'default'     => [
                [
                    'meta'  => 'Phase 1',
                    'title' => 'Ideation & Sketching',
                    'desc'  => 'Gather initial ideas, build sketches and draft blueprints.',
                ],
                [
                    'meta'  => 'Phase 2',
                    'title' => 'Prototype Review',
                    'desc'  => 'Interactive mockup presentation and client reviews session.',
                ],
                [
                    'meta'  => 'Phase 3',
                    'title' => 'Development & Coding',
                    'desc'  => 'Refactoring logic classes, testing speeds and deploying code.',
                ],
            ],
            'title_field' => '{{{ title }}}',
        ] );
        $this->end_controls_section();

        $this->start_controls_section( 's_layout', [
            'label' => esc_html__( 'Layout', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'layout', [
            'label'   => esc_html__( 'Layout Mode', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'alternating',
            'options' => [
                'alternating' => esc_html__( 'Alternating (Left / Right)', 'rawnaq' ),
                'left'        => esc_html__( 'All Left', 'rawnaq' ),
                'right'       => esc_html__( 'All Right', 'rawnaq' ),
            ],
        ] );

        $this->add_control( 'show_numbers', [
            'label'        => esc_html__( 'Show Step Numbers', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => esc_html__( 'Yes', 'rawnaq' ),
            'label_off'    => esc_html__( 'No', 'rawnaq' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->end_controls_section();

        // Style
        $this->start_controls_section( 's_style', [
            'label' => esc_html__( 'Style & Colors', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'line_bg', [
            'label'     => esc_html__( 'Line Background Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#e2e8f0',
            'selectors' => [ '{{WRAPPER}} .rawnaq-timeline-line-bg' => 'background-color: {{VALUE}};' ],
        ] );
        $this->add_control( 'line_active', [
            'label'     => esc_html__( 'Active Line Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#6366f1',
            'selectors' => [ '{{WRAPPER}} .rawnaq-timeline-line-active' => 'background: {{VALUE}};' ],
        ] );
        $this->add_control( 'bullet_border', [
            'label'     => esc_html__( 'Bullet Border Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#cbd5e1',
            'selectors' => [ '{{WRAPPER}} .rawnaq-timeline-bullet' => 'border-color: {{VALUE}};' ],
        ] );
        $this->add_control( 'bullet_active', [
            'label'     => esc_html__( 'Active Bullet Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#6366f1',
            'selectors' => [
                '{{WRAPPER}} .rawnaq-timeline-item.item-active .rawnaq-timeline-bullet' => 'border-color: {{VALUE}};',
                '{{WRAPPER}} .rawnaq-timeline-item.item-active .rawnaq-timeline-bullet .num' => 'color: {{VALUE}};',
            ],
        ] );
        $this->add_responsive_control( 'bullet_size', [
            'label'      => esc_html__( 'Bullet Size', 'rawnaq' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 16, 'max' => 48 ] ],
            'default'    => [ 'unit' => 'px', 'size' => 28 ],
            'selectors'  => [
                '{{WRAPPER}} .rawnaq-timeline-bullet' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .rawnaq-timeline-item.left-item .rawnaq-timeline-bullet' => 'right: calc({{SIZE}}{{UNIT}} / -2);',
                '{{WRAPPER}} .rawnaq-timeline-item.right-item .rawnaq-timeline-bullet' => 'left: calc({{SIZE}}{{UNIT}} / -2);',
            ],
        ] );
        $this->add_control( 'card_bg', [
            'label'     => esc_html__( 'Card Background Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [ '{{WRAPPER}} .rawnaq-timeline-card' => 'background-color: {{VALUE}};' ],
        ] );
        $this->add_responsive_control( 'card_radius', [
            'label'      => esc_html__( 'Card Radius', 'rawnaq' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
            'default'    => [ 'unit' => 'px', 'size' => 16 ],
            'selectors'  => [ '{{WRAPPER}} .rawnaq-timeline-card' => 'border-radius: {{SIZE}}{{UNIT}};' ],
        ] );
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'card_shadow',
                'selector' => '{{WRAPPER}} .rawnaq-timeline-card',
            ]
        );
        $this->add_responsive_control( 'item_gap', [
            'label'      => esc_html__( 'Item Spacing', 'rawnaq' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 8, 'max' => 80 ] ],
            'default'    => [ 'unit' => 'px', 'size' => 20 ],
            'selectors'  => [ '{{WRAPPER}} .rawnaq-timeline-item' => 'padding-top: {{SIZE}}{{UNIT}}; padding-bottom: {{SIZE}}{{UNIT}};' ],
        ] );
        $this->add_control( 'meta_color', [
            'label'     => esc_html__( 'Meta Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#6366f1',
            'selectors' => [ '{{WRAPPER}} .rawnaq-timeline-meta' => 'color: {{VALUE}};' ],
        ] );
        $this->add_control( 'title_color', [
            'label'     => esc_html__( 'Title Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#1a1a1a',
            'selectors' => [ '{{WRAPPER}} .rawnaq-timeline-card h4' => 'color: {{VALUE}};' ],
        ] );
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'title_typo',
                'selector' => '{{WRAPPER}} .rawnaq-timeline-card h4',
            ]
        );
        $this->add_control( 'desc_color', [
            'label'     => esc_html__( 'Description Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#666666',
            'selectors' => [ '{{WRAPPER}} .rawnaq-timeline-card p' => 'color: {{VALUE}};' ],
        ] );
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'desc_typo',
                'selector' => '{{WRAPPER}} .rawnaq-timeline-card p',
            ]
        );
        $this->add_control( 'cta_color', [
            'label'     => esc_html__( 'CTA Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#6366f1',
            'selectors' => [ '{{WRAPPER}} .rawnaq-timeline-cta' => 'color: {{VALUE}};' ],
        ] );
        $this->end_controls_section();
    }

    private function side_class( $index, $layout ) {
        if ( 'left' === $layout ) {
            return 'left-item';
        }
        if ( 'right' === $layout ) {
            return 'right-item';
        }
        return ( 0 === ( $index % 2 ) ) ? 'left-item' : 'right-item';
    }

    private function url_attrs( $url_setting ) {
        if ( empty( $url_setting['url'] ) ) {
            return '';
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

    private function render_step_icon( $step ) {
        if ( ! empty( $step['selected_icon']['value'] ) ) {
            echo '<span class="rawnaq-timeline-icon">';
            \Elementor\Icons_Manager::render_icon( $step['selected_icon'], [ 'aria-hidden' => 'true' ] );
            echo '</span>';
        }
    }

    protected function render() {
        $s            = $this->get_settings_for_display();
        $steps        = $s['steps'] ?? [];
        $layout       = $s['layout'] ?? 'alternating';
        $show_numbers = ( $s['show_numbers'] ?? '' ) === 'yes';
        $wrap_class   = 'rawnaq-timeline-wrapper layout-' . sanitize_html_class( $layout );
        if ( $show_numbers ) {
            $wrap_class .= ' show-numbers';
        }
        ?>
        <div class="<?php echo esc_attr( $wrap_class ); ?>" data-show-numbers="<?php echo $show_numbers ? '1' : '0'; ?>">
            <div class="rawnaq-timeline-line-bg"></div>
            <div class="rawnaq-timeline-line-active"></div>

            <?php foreach ( $steps as $index => $step ) :
                $side = $this->side_class( $index, $layout );
                $num  = str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT );
                $img  = ! empty( $step['image']['url'] ) ? $step['image']['url'] : '';
                $alt  = ! empty( $step['image']['alt'] ) ? $step['image']['alt'] : ( $step['title'] ?? '' );
                $cta_text = trim( (string) ( $step['cta_text'] ?? '' ) );
                $cta_link = $step['cta_link'] ?? [];
                ?>
                <div class="rawnaq-timeline-item <?php echo esc_attr( $side ); ?>">
                    <span class="rawnaq-timeline-bullet">
                        <?php if ( $show_numbers ) : ?>
                            <span class="num"><?php echo esc_html( $num ); ?></span>
                        <?php endif; ?>
                    </span>
                    <div class="rawnaq-timeline-card">
                        <?php if ( $img ) : ?>
                            <img class="rawnaq-timeline-thumb" src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $alt ); ?>" loading="lazy" />
                        <?php endif; ?>
                        <?php if ( ! empty( $step['meta'] ) ) : ?>
                            <span class="rawnaq-timeline-meta"><?php echo esc_html( $step['meta'] ); ?></span>
                        <?php endif; ?>
                        <?php $this->render_step_icon( $step ); ?>
                        <?php if ( ! empty( $step['title'] ) ) : ?>
                            <h4><?php echo esc_html( $step['title'] ); ?></h4>
                        <?php endif; ?>
                        <?php if ( ! empty( $step['desc'] ) ) : ?>
                            <p><?php echo esc_html( $step['desc'] ); ?></p>
                        <?php endif; ?>
                        <?php if ( $cta_text && ! empty( $cta_link['url'] ) ) : ?>
                            <a class="rawnaq-timeline-cta"<?php
								// Already escaped inside url_attrs().
								echo $this->url_attrs( $cta_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>><?php echo esc_html( $cta_text ); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    protected function content_template() {
        ?>
        <#
        var layout = settings.layout || 'alternating';
        var showNumbers = settings.show_numbers === 'yes';
        var wrapClass = 'rawnaq-timeline-wrapper layout-' + layout + ( showNumbers ? ' show-numbers' : '' );
        #>
        <div class="{{ wrapClass }}" data-show-numbers="{{ showNumbers ? '1' : '0' }}">
            <div class="rawnaq-timeline-line-bg"></div>
            <div class="rawnaq-timeline-line-active"></div>
            <#
            if ( settings.steps ) {
                _.each( settings.steps, function( step, index ) {
                    var side = 'left-item';
                    if ( layout === 'right' ) {
                        side = 'right-item';
                    } else if ( layout === 'alternating' ) {
                        side = ( index % 2 === 0 ) ? 'left-item' : 'right-item';
                    }
                    var num = ( index + 1 < 10 ) ? ( '0' + ( index + 1 ) ) : String( index + 1 );
                    var img = ( step.image && step.image.url ) ? step.image.url : '';
                    var iconHTML = elementor.helpers.renderIcon( view, step.selected_icon, { 'aria-hidden': true }, 'i', 'object' );
                    var ctaUrl = ( step.cta_link && step.cta_link.url ) ? step.cta_link.url : '';
                    #>
                    <div class="rawnaq-timeline-item {{ side }}">
                        <span class="rawnaq-timeline-bullet">
                            <# if ( showNumbers ) { #><span class="num">{{ num }}</span><# } #>
                        </span>
                        <div class="rawnaq-timeline-card">
                            <# if ( img ) { #>
                                <img class="rawnaq-timeline-thumb" src="{{ img }}" alt="" />
                            <# } #>
                            <# if ( step.meta ) { #>
                                <span class="rawnaq-timeline-meta">{{{ step.meta }}}</span>
                            <# } #>
                            <# if ( iconHTML && iconHTML.rendered ) { #>
                                <span class="rawnaq-timeline-icon">{{{ iconHTML.value }}}</span>
                            <# } #>
                            <# if ( step.title ) { #><h4>{{{ step.title }}}</h4><# } #>
                            <# if ( step.desc ) { #><p>{{{ step.desc }}}</p><# } #>
                            <# if ( step.cta_text && ctaUrl ) { #>
                                <a class="rawnaq-timeline-cta" href="{{ ctaUrl }}">{{{ step.cta_text }}}</a>
                            <# } #>
                        </div>
                    </div>
                    <#
                } );
            }
            #>
        </div>
        <?php
    }
}
