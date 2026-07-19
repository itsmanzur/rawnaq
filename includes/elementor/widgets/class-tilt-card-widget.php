<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rawnaq_Tilt_Card_Widget extends \Elementor\Widget_Base {

    public function get_name()       { return 'rawnaq_tilt_card'; }
    public function get_title()      { return esc_html__( '3D Tilt Card', 'rawnaq' ); }
    public function get_icon()       { return 'eicon-parallax'; }
    public function get_categories() { return [ 'rawnaq' ]; }

    public function get_style_depends()  { return [ 'rawnaq-tilt-card', 'rawnaq-fonts', 'dashicons' ]; }
    public function get_script_depends() { return [ 'rawnaq-tilt-card' ]; }

    protected function register_controls() {
        // ── Content ──
        $this->start_controls_section( 's_content', [
            'label' => esc_html__( 'Card Content', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'image', [
            'label'   => esc_html__( 'Card Image', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::MEDIA,
            'default' => [ 'url' => '' ],
        ] );

        $this->add_control( 'badge', [
            'label'       => esc_html__( 'Badge / Eyebrow', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => 'New',
            'label_block' => true,
        ] );

        $this->add_control( 'title', [
            'label'       => esc_html__( 'Title', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'Creative Service',
            'label_block' => true,
        ] );

        $this->add_control( 'desc', [
            'label'   => esc_html__( 'Description', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::TEXTAREA,
            'default' => 'We design premium, high-speed interfaces tailored to stand out from competitors.',
            'rows'    => 3,
        ] );

        $this->add_control( 'selected_icon', [
            'label'   => esc_html__( 'Icon', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::ICONS,
            'default' => [
                'value'   => 'fas fa-star',
                'library' => 'fa-solid',
            ],
        ] );

        // Legacy dashicon fallback (hidden unless old content still uses it).
        $this->add_control( 'icon', [
            'label'       => esc_html__( 'Legacy Dashicon Class', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::HIDDEN,
            'default'     => '',
        ] );

        $this->add_control( 'cta_text', [
            'label'       => esc_html__( 'CTA Button Text', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'Learn more',
            'label_block' => true,
        ] );

        $this->add_control( 'cta_link', [
            'label'       => esc_html__( 'CTA Link', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::URL,
            'placeholder' => 'https://example.com',
            'default'     => [
                'url'         => '',
                'is_external' => false,
                'nofollow'    => false,
            ],
        ] );

        $this->add_control( 'link', [
            'label'       => esc_html__( 'Card Link (stretch)', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::URL,
            'placeholder' => 'https://example.com',
            'description' => esc_html__( 'Makes the whole card clickable. CTA stays above this link.', 'rawnaq' ),
            'default'     => [
                'url'         => '',
                'is_external' => false,
                'nofollow'    => false,
            ],
        ] );

        $this->end_controls_section();

        // ── Content: Back Face (Flip) ──
        $this->start_controls_section( 's_flip', [
            'label' => esc_html__( 'Flip / Back Face', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'enable_flip', [
            'label'        => esc_html__( 'Enable Flip', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'description'  => esc_html__( 'Reveal a back face on hover or click. 3D tilt is paused while flip is on.', 'rawnaq' ),
        ] );

        $this->add_control( 'flip_trigger', [
            'label'     => esc_html__( 'Flip Trigger', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'default'   => 'hover',
            'options'   => [
                'hover' => esc_html__( 'Hover', 'rawnaq' ),
                'click' => esc_html__( 'Click / Tap', 'rawnaq' ),
            ],
            'condition' => [ 'enable_flip' => 'yes' ],
        ] );

        $this->add_control( 'back_title', [
            'label'       => esc_html__( 'Back Title', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => esc_html__( 'Why choose us', 'rawnaq' ),
            'label_block' => true,
            'condition'   => [ 'enable_flip' => 'yes' ],
        ] );

        $this->add_control( 'back_desc', [
            'label'     => esc_html__( 'Back Description', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::TEXTAREA,
            'default'   => esc_html__( 'Add the extra detail, specs, or a persuasive reason that lives on the reverse side.', 'rawnaq' ),
            'rows'      => 4,
            'condition' => [ 'enable_flip' => 'yes' ],
        ] );

        $this->add_control( 'back_cta_text', [
            'label'       => esc_html__( 'Back CTA Text', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => esc_html__( 'Get started', 'rawnaq' ),
            'label_block' => true,
            'condition'   => [ 'enable_flip' => 'yes' ],
        ] );

        $this->add_control( 'back_cta_link', [
            'label'       => esc_html__( 'Back CTA Link', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::URL,
            'placeholder' => 'https://example.com',
            'default'     => [ 'url' => '', 'is_external' => false, 'nofollow' => false ],
            'condition'   => [ 'enable_flip' => 'yes' ],
        ] );

        $this->end_controls_section();

        // ── Style: Layout & Motion ──
        $this->start_controls_section( 's_layout', [
            'label' => esc_html__( 'Layout & Motion', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'content_align', [
            'label'   => esc_html__( 'Content Vertical Align', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'bottom',
            'options' => [
                'top'    => esc_html__( 'Top', 'rawnaq' ),
                'center' => esc_html__( 'Center', 'rawnaq' ),
                'bottom' => esc_html__( 'Bottom', 'rawnaq' ),
            ],
        ] );

        $this->add_control( 'max_tilt', [
            'label'   => esc_html__( 'Max Tilt Intensity', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'min'     => 0,
            'max'     => 45,
            'step'    => 1,
            'default' => 15,
        ] );

        $this->add_control( 'hover_scale', [
            'label'   => esc_html__( 'Hover Scale', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => [
                'px' => [ 'min' => 1, 'max' => 1.08, 'step' => 0.01 ],
            ],
            'default' => [ 'size' => 1.03 ],
        ] );

        $this->add_control( 'glare_intensity', [
            'label'   => esc_html__( 'Glare Intensity', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => [
                'px' => [ 'min' => 0, 'max' => 1, 'step' => 0.05 ],
            ],
            'default' => [ 'size' => 0.45 ],
        ] );

        $this->add_control( 'overlay_strength', [
            'label'   => esc_html__( 'Image Overlay Strength', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => [
                'px' => [ 'min' => 0, 'max' => 1, 'step' => 0.05 ],
            ],
            'default' => [ 'size' => 0.7 ],
            'selectors' => [
                '{{WRAPPER}} .rawnaq-tilt-overlay' => 'opacity: {{SIZE}};',
            ],
        ] );

        $this->add_responsive_control( 'card_height', [
            'label'      => esc_html__( 'Card Height', 'rawnaq' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => 220, 'max' => 560, 'step' => 10 ],
            ],
            'default'    => [ 'unit' => 'px', 'size' => 380 ],
            'selectors'  => [
                '{{WRAPPER}} .rawnaq-tilt-card' => 'height: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'border_radius', [
            'label'      => esc_html__( 'Border Radius', 'rawnaq' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 48, 'step' => 1 ],
            ],
            'default'    => [ 'unit' => 'px', 'size' => 20 ],
            'selectors'  => [
                '{{WRAPPER}} .rawnaq-tilt-card' => 'border-radius: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'card_shadow',
                'selector' => '{{WRAPPER}} .rawnaq-tilt-card',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section( 's_colors', [
            'label' => esc_html__( 'Colors & Typography', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'card_bg', [
            'label'     => esc_html__( 'Card Background Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [ '{{WRAPPER}} .rawnaq-tilt-card' => '--tilt-card-bg: {{VALUE}}; background-color: {{VALUE}};' ],
        ] );

        $this->add_control( 'badge_bg', [
            'label'     => esc_html__( 'Badge Background', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#6366f1',
            'selectors' => [ '{{WRAPPER}} .rawnaq-tilt-card' => '--tilt-badge-bg: {{VALUE}};' ],
        ] );

        $this->add_control( 'badge_color', [
            'label'     => esc_html__( 'Badge Text Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [ '{{WRAPPER}} .rawnaq-tilt-card' => '--tilt-badge-color: {{VALUE}};' ],
        ] );

        $this->add_control( 'title_color', [
            'label'     => esc_html__( 'Title Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '',
            'selectors' => [ '{{WRAPPER}} .rawnaq-tilt-card' => '--tilt-title: {{VALUE}};' ],
        ] );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'title_typo',
                'selector' => '{{WRAPPER}} .rawnaq-tilt-title',
            ]
        );

        $this->add_control( 'desc_color', [
            'label'     => esc_html__( 'Description Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '',
            'selectors' => [ '{{WRAPPER}} .rawnaq-tilt-card' => '--tilt-desc: {{VALUE}};' ],
        ] );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'desc_typo',
                'selector' => '{{WRAPPER}} .rawnaq-tilt-desc',
            ]
        );

        $this->add_control( 'icon_color', [
            'label'     => esc_html__( 'Icon Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '',
            'selectors' => [ '{{WRAPPER}} .rawnaq-tilt-card' => '--tilt-icon: {{VALUE}};' ],
        ] );

        $this->add_control( 'btn_heading', [
            'label'     => esc_html__( 'CTA Button', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_control( 'btn_bg', [
            'label'     => esc_html__( 'Button Background', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#6366f1',
            'selectors' => [ '{{WRAPPER}} .rawnaq-tilt-card' => '--tilt-btn-bg: {{VALUE}};' ],
        ] );

        $this->add_control( 'btn_color', [
            'label'     => esc_html__( 'Button Text Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [ '{{WRAPPER}} .rawnaq-tilt-card' => '--tilt-btn-color: {{VALUE}};' ],
        ] );

        $this->add_control( 'btn_radius', [
            'label'      => esc_html__( 'Button Radius', 'rawnaq' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
            'default'    => [ 'unit' => 'px', 'size' => 999 ],
            'selectors'  => [ '{{WRAPPER}} .rawnaq-tilt-btn' => 'border-radius: {{SIZE}}{{UNIT}};' ],
        ] );

        $this->add_control( 'back_heading', [
            'label'     => esc_html__( 'Back Face', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => [ 'enable_flip' => 'yes' ],
        ] );

        $this->add_control( 'back_bg', [
            'label'     => esc_html__( 'Back Background', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#4338ca',
            'selectors' => [ '{{WRAPPER}} .rawnaq-tilt-back' => '--tilt-back-bg: {{VALUE}};' ],
            'condition' => [ 'enable_flip' => 'yes' ],
        ] );

        $this->add_control( 'back_color', [
            'label'     => esc_html__( 'Back Text Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [ '{{WRAPPER}} .rawnaq-tilt-back' => '--tilt-back-color: {{VALUE}};' ],
            'condition' => [ 'enable_flip' => 'yes' ],
        ] );

        $this->end_controls_section();
    }

    private function render_icon( $s ) {
        if ( ! empty( $s['selected_icon']['value'] ) ) {
            echo '<span class="rawnaq-tilt-icon">';
            \Elementor\Icons_Manager::render_icon( $s['selected_icon'], [ 'aria-hidden' => 'true' ] );
            echo '</span>';
            return;
        }
        if ( ! empty( $s['icon'] ) ) {
            echo '<span class="rawnaq-tilt-icon dashicons ' . esc_attr( $s['icon'] ) . '"></span>';
        }
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

    protected function render() {
        $s = $this->get_settings_for_display();

        $image_url   = ! empty( $s['image']['url'] ) ? $s['image']['url'] : '';
        $image_alt   = ! empty( $s['image']['alt'] ) ? $s['image']['alt'] : ( $s['title'] ?? '' );
        $has_image   = (bool) $image_url;
        $align       = $s['content_align'] ?? 'bottom';
        $max_tilt    = isset( $s['max_tilt'] ) ? (float) $s['max_tilt'] : 15;
        $hover_scale = isset( $s['hover_scale']['size'] ) ? (float) $s['hover_scale']['size'] : 1.03;
        $glare       = isset( $s['glare_intensity']['size'] ) ? (float) $s['glare_intensity']['size'] : 0.45;
        $overlay     = isset( $s['overlay_strength']['size'] ) ? (float) $s['overlay_strength']['size'] : 0.7;

        $card_link = [];
        if ( ! empty( $s['link']['url'] ) ) {
            $card_link = $s['link'];
        } elseif ( ! empty( $s['link'] ) && is_string( $s['link'] ) ) {
            // Backward compat for older text-based link control.
            $card_link = [
                'url'         => $s['link'],
                'is_external' => ( ( $s['target'] ?? '_self' ) === '_blank' ),
                'nofollow'    => false,
            ];
        }

        $cta_text = trim( (string) ( $s['cta_text'] ?? '' ) );
        $cta_link = [];
        if ( ! empty( $s['cta_link']['url'] ) ) {
            $cta_link = $s['cta_link'];
        } elseif ( $card_link ) {
            $cta_link = $card_link;
        }

        $enable_flip = ( $s['enable_flip'] ?? '' ) === 'yes';
        $flip_trigger = ( $s['flip_trigger'] ?? 'hover' ) === 'click' ? 'click' : 'hover';

        $classes = [ 'rawnaq-tilt-card', 'align-' . sanitize_html_class( $align ) ];
        if ( $has_image ) {
            $classes[] = 'has-image';
        }
        if ( $enable_flip ) {
            $classes[] = 'is-flip';
            $classes[] = 'flip-' . $flip_trigger;
        }

        $style = sprintf(
            '--overlay:%s;--glare:%s;--hover-scale:%s;',
            esc_attr( $overlay ),
            esc_attr( $glare ),
            esc_attr( $hover_scale )
        );

        $card_attrs = '';
        if ( $enable_flip && 'click' === $flip_trigger ) {
            $card_attrs = ' tabindex="0" role="button" aria-pressed="false" aria-label="' . esc_attr( $s['title'] ?: __( 'Flip card', 'rawnaq' ) ) . '"';
        }
        ?>
        <div class="rawnaq-tilt-container">
            <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
                 style="<?php echo esc_attr( $style ); ?>"
                 data-tilt-max="<?php echo esc_attr( $max_tilt ); ?>"
                 data-hover-scale="<?php echo esc_attr( $hover_scale ); ?>"
                 data-glare="<?php echo esc_attr( $glare ); ?>"<?php
					echo $card_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr above.
				?>>
                <?php if ( $enable_flip ) : ?>
                    <div class="rawnaq-tilt-flip">
                        <div class="rawnaq-tilt-face rawnaq-tilt-front">
                            <?php $this->render_front_children( $s, $has_image, $image_url, $image_alt, $cta_text, $cta_link ); ?>
                        </div>
                        <div class="rawnaq-tilt-back">
                            <div class="rawnaq-tilt-back-inner">
                                <?php if ( ! empty( $s['back_title'] ) ) : ?>
                                    <h3 class="rawnaq-tilt-back-title"><?php echo esc_html( $s['back_title'] ); ?></h3>
                                <?php endif; ?>
                                <?php if ( ! empty( $s['back_desc'] ) ) : ?>
                                    <p class="rawnaq-tilt-back-desc"><?php echo esc_html( $s['back_desc'] ); ?></p>
                                <?php endif; ?>
                                <?php
                                $back_cta_text = trim( (string) ( $s['back_cta_text'] ?? '' ) );
                                if ( $back_cta_text && ! empty( $s['back_cta_link']['url'] ) ) :
                                    ?>
                                    <a class="rawnaq-tilt-btn rawnaq-tilt-back-btn"<?php
										echo $this->url_attrs( $s['back_cta_link'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									?>><?php echo esc_html( $back_cta_text ); ?></a>
                                <?php elseif ( $back_cta_text ) : ?>
                                    <span class="rawnaq-tilt-btn is-static"><?php echo esc_html( $back_cta_text ); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else : ?>
                    <?php $this->render_front_children( $s, $has_image, $image_url, $image_alt, $cta_text, $cta_link ); ?>
                    <?php if ( ! empty( $card_link['url'] ) ) : ?>
                        <a class="rawnaq-tilt-stretch-link"<?php
							echo $this->url_attrs( $card_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?> aria-label="<?php echo esc_attr( $s['title'] ?: __( 'Open link', 'rawnaq' ) ); ?>"></a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Shared front-face children (image, overlay, glare, badge, icon, content).
     */
    private function render_front_children( $s, $has_image, $image_url, $image_alt, $cta_text, $cta_link ) {
        ?>
        <?php if ( $has_image ) : ?>
            <img class="rawnaq-tilt-image" src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" loading="lazy" />
            <span class="rawnaq-tilt-overlay" aria-hidden="true"></span>
        <?php endif; ?>

        <span class="rawnaq-tilt-glare" aria-hidden="true"></span>

        <?php if ( ! empty( $s['badge'] ) ) : ?>
            <span class="rawnaq-tilt-badge"><?php echo esc_html( $s['badge'] ); ?></span>
        <?php endif; ?>

        <?php $this->render_icon( $s ); ?>

        <div class="rawnaq-tilt-content">
            <?php if ( ! empty( $s['title'] ) ) : ?>
                <h3 class="rawnaq-tilt-title"><?php echo esc_html( $s['title'] ); ?></h3>
            <?php endif; ?>
            <?php if ( ! empty( $s['desc'] ) ) : ?>
                <p class="rawnaq-tilt-desc"><?php echo esc_html( $s['desc'] ); ?></p>
            <?php endif; ?>
            <?php if ( $cta_text && ! empty( $cta_link['url'] ) ) : ?>
                <a class="rawnaq-tilt-btn"<?php
					echo $this->url_attrs( $cta_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>><?php echo esc_html( $cta_text ); ?></a>
            <?php elseif ( $cta_text ) : ?>
                <span class="rawnaq-tilt-btn is-static"><?php echo esc_html( $cta_text ); ?></span>
            <?php endif; ?>
        </div>
        <?php
    }

    protected function content_template() {
        ?>
        <#
        var imageUrl = ( settings.image && settings.image.url ) ? settings.image.url : '';
        var hasImage = !! imageUrl;
        var align = settings.content_align || 'bottom';
        var maxTilt = settings.max_tilt != null ? settings.max_tilt : 15;
        var hoverScale = ( settings.hover_scale && settings.hover_scale.size != null ) ? settings.hover_scale.size : 1.03;
        var glare = ( settings.glare_intensity && settings.glare_intensity.size != null ) ? settings.glare_intensity.size : 0.45;
        var overlay = ( settings.overlay_strength && settings.overlay_strength.size != null ) ? settings.overlay_strength.size : 0.7;
        var cardClass = 'rawnaq-tilt-card align-' + align + ( hasImage ? ' has-image' : '' );
        var imageAlt = ( settings.image && settings.image.alt ) ? settings.image.alt : ( settings.title || '' );
        var cardUrl = ( settings.link && settings.link.url ) ? settings.link.url : '';
        var ctaText = settings.cta_text || '';
        var ctaUrl = ( settings.cta_link && settings.cta_link.url ) ? settings.cta_link.url : cardUrl;
        var iconHTML = elementor.helpers.renderIcon( view, settings.selected_icon, { 'aria-hidden': true }, 'i', 'object' );
        var enableFlip = settings.enable_flip === 'yes';
        var flipTrigger = settings.flip_trigger === 'click' ? 'click' : 'hover';
        if ( enableFlip ) { cardClass += ' is-flip flip-' + flipTrigger; }
        var backCtaText = settings.back_cta_text || '';
        var backCtaUrl = ( settings.back_cta_link && settings.back_cta_link.url ) ? settings.back_cta_link.url : '';
        #>
        <div class="rawnaq-tilt-container">
            <div class="{{ cardClass }}"
                 style="--overlay:{{ overlay }};--glare:{{ glare }};--hover-scale:{{ hoverScale }};"
                 data-tilt-max="{{ maxTilt }}"
                 data-hover-scale="{{ hoverScale }}"
                 data-glare="{{ glare }}">
                <# if ( enableFlip ) { #>
                <div class="rawnaq-tilt-flip">
                    <div class="rawnaq-tilt-face rawnaq-tilt-front">
                <# } #>
                <# if ( hasImage ) { #>
                    <img class="rawnaq-tilt-image" src="{{ imageUrl }}" alt="{{ imageAlt }}" />
                    <span class="rawnaq-tilt-overlay"></span>
                <# } #>
                <span class="rawnaq-tilt-glare"></span>
                <# if ( settings.badge ) { #>
                    <span class="rawnaq-tilt-badge">{{{ settings.badge }}}</span>
                <# } #>
                <# if ( iconHTML && iconHTML.rendered ) { #>
                    <span class="rawnaq-tilt-icon">{{{ iconHTML.value }}}</span>
                <# } else if ( settings.icon ) { #>
                    <span class="rawnaq-tilt-icon dashicons {{ settings.icon }}"></span>
                <# } #>
                <div class="rawnaq-tilt-content">
                    <# if ( settings.title ) { #>
                        <h3 class="rawnaq-tilt-title">{{{ settings.title }}}</h3>
                    <# } #>
                    <# if ( settings.desc ) { #>
                        <p class="rawnaq-tilt-desc">{{{ settings.desc }}}</p>
                    <# } #>
                    <# if ( ctaText && ctaUrl ) { #>
                        <a class="rawnaq-tilt-btn" href="{{ ctaUrl }}">{{{ ctaText }}}</a>
                    <# } else if ( ctaText ) { #>
                        <span class="rawnaq-tilt-btn is-static">{{{ ctaText }}}</span>
                    <# } #>
                </div>
                <# if ( enableFlip ) { #>
                    </div>
                    <div class="rawnaq-tilt-back">
                        <div class="rawnaq-tilt-back-inner">
                            <# if ( settings.back_title ) { #>
                                <h3 class="rawnaq-tilt-back-title">{{{ settings.back_title }}}</h3>
                            <# } #>
                            <# if ( settings.back_desc ) { #>
                                <p class="rawnaq-tilt-back-desc">{{{ settings.back_desc }}}</p>
                            <# } #>
                            <# if ( backCtaText && backCtaUrl ) { #>
                                <a class="rawnaq-tilt-btn rawnaq-tilt-back-btn" href="{{ backCtaUrl }}">{{{ backCtaText }}}</a>
                            <# } else if ( backCtaText ) { #>
                                <span class="rawnaq-tilt-btn is-static">{{{ backCtaText }}}</span>
                            <# } #>
                        </div>
                    </div>
                </div>
                <# } else if ( cardUrl ) { #>
                    <a class="rawnaq-tilt-stretch-link" href="{{ cardUrl }}"></a>
                <# } #>
            </div>
        </div>
        <?php
    }
}
