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
    public function get_script_depends() { return [ 'rawnaq-scroll-timeline', 'rawnaq-bridge' ]; }

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

        $r->add_control( 'video_url', [
            'label'       => esc_html__( 'Video URL', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::URL,
            'placeholder' => 'https://www.youtube.com/watch?v=…',
            'description' => esc_html__( 'YouTube, Vimeo, or mp4/webm. Shown instead of the image when set.', 'rawnaq' ),
            'default'     => [
                'url'         => '',
                'is_external' => false,
                'nofollow'    => false,
            ],
            'show_external' => false,
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

        $r->add_control( 'project_id', [
            'label'       => esc_html__( 'Case-Study project ID', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'description' => esc_html__( 'Match Case-Study card id (e.g. post-123).', 'rawnaq' ),
        ] );

        $r->add_control( 'project_slug', [
            'label'   => esc_html__( 'Case-Study project slug', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ] );

        $this->add_control( 'source', [
            'label'   => esc_html__( 'Steps Source', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'manual',
            'options' => [
                'manual' => esc_html__( 'Manual steps', 'rawnaq' ),
                'query'  => esc_html__( 'Posts / CPT query', 'rawnaq' ),
            ],
        ] );

        $preset_options = [ '' => esc_html__( '— Choose a preset —', 'rawnaq' ) ];
        if ( function_exists( 'rawnaq_timeline_presets' ) ) {
            foreach ( rawnaq_timeline_presets() as $key => $pack ) {
                $preset_options[ $key ] = $pack['label'] ?? $key;
            }
        }

        $this->add_control( 'agency_preset', [
            'label'       => esc_html__( 'Agency Preset', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::SELECT,
            'default'     => '',
            'options'     => $preset_options,
            'condition'   => [ 'source' => 'manual' ],
            'description' => esc_html__( 'Seed steps for Company Story, Changelog, or Case Study. Apply replaces the Steps repeater.', 'rawnaq' ),
        ] );

        $this->add_control( 'apply_agency_preset', [
            'type'        => \Elementor\Controls_Manager::BUTTON,
            'label'       => esc_html__( 'Apply Preset', 'rawnaq' ),
            'text'        => esc_html__( 'Apply Preset to Steps', 'rawnaq' ),
            'button_type' => 'success',
            'event'       => 'rawnaq:timeline:applyPreset',
            'separator'   => 'after',
            'condition'   => [
                'source'         => 'manual',
                'agency_preset!' => '',
            ],
            'description' => esc_html__( 'Replaces timeline steps with the selected preset.', 'rawnaq' ),
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
            'condition'   => [ 'source' => 'manual' ],
        ] );
        $this->end_controls_section();

        $this->start_controls_section( 's_query', [
            'label'     => esc_html__( 'Query', 'rawnaq' ),
            'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
            'condition' => [ 'source' => 'query' ],
        ] );

        $pt_options = [];
        foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $pt ) {
            $pt_options[ $pt->name ] = $pt->labels->singular_name;
        }
        if ( ! $pt_options ) {
            $pt_options = [ 'post' => esc_html__( 'Post', 'rawnaq' ) ];
        }

        $this->add_control( 'query_post_type', [
            'label'   => esc_html__( 'Post Type', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'post',
            'options' => $pt_options,
        ] );

        $this->add_control( 'query_posts_per_page', [
            'label'   => esc_html__( 'Posts Per Page', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 6,
            'min'     => 1,
            'max'     => 50,
        ] );

        $this->add_control( 'query_orderby', [
            'label'   => esc_html__( 'Order By', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'date',
            'options' => [
                'date'       => esc_html__( 'Date', 'rawnaq' ),
                'title'      => esc_html__( 'Title', 'rawnaq' ),
                'menu_order' => esc_html__( 'Menu order', 'rawnaq' ),
                'modified'   => esc_html__( 'Modified', 'rawnaq' ),
                'rand'       => esc_html__( 'Random', 'rawnaq' ),
                'ID'         => esc_html__( 'ID', 'rawnaq' ),
            ],
        ] );

        $this->add_control( 'query_order', [
            'label'   => esc_html__( 'Order', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'DESC',
            'options' => [
                'DESC' => esc_html__( 'Descending', 'rawnaq' ),
                'ASC'  => esc_html__( 'Ascending', 'rawnaq' ),
            ],
        ] );

        $this->add_control( 'query_taxonomy', [
            'label'       => esc_html__( 'Taxonomy', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'category',
            'description' => esc_html__( 'Optional. e.g. category, post_tag, or a CPT taxonomy slug.', 'rawnaq' ),
        ] );

        $this->add_control( 'query_terms', [
            'label'       => esc_html__( 'Term Slugs', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'news, updates',
            'description' => esc_html__( 'Comma-separated term slugs (requires Taxonomy).', 'rawnaq' ),
        ] );

        $this->add_control( 'query_include', [
            'label'       => esc_html__( 'Include IDs', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => '12, 34, 56',
            'description' => esc_html__( 'Optional. When set, only these posts are shown (order preserved).', 'rawnaq' ),
        ] );

        $this->add_control( 'query_exclude', [
            'label'       => esc_html__( 'Exclude IDs', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => '78, 90',
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
                'horizontal'  => esc_html__( 'Horizontal', 'rawnaq' ),
            ],
        ] );

        $this->add_control( 'timeline_name', [
            'label'       => esc_html__( 'Named Timeline ID', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => 'rawnaq-tl-my-section',
            'description' => esc_html__( 'Optional CSS scroll-timeline name. Leave empty for auto (rawnaq-tl-{id}). Paste the same ID into Bento cells to sync.', 'rawnaq' ),
            'label_block' => true,
        ] );

        $this->add_control( 'show_numbers', [
            'label'        => esc_html__( 'Show Step Numbers', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => esc_html__( 'Yes', 'rawnaq' ),
            'label_off'    => esc_html__( 'No', 'rawnaq' ),
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'initial_visible', [
            'label'       => esc_html__( 'Initial Visible Steps', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::NUMBER,
            'default'     => 0,
            'min'         => 0,
            'max'         => 50,
            'description' => esc_html__( '0 = show all. Otherwise hide remaining steps behind Load More.', 'rawnaq' ),
        ] );

        $this->add_control( 'load_chunk', [
            'label'       => esc_html__( 'Load More Chunk Size', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::NUMBER,
            'default'     => 3,
            'min'         => 1,
            'max'         => 20,
            'condition'   => [ 'initial_visible!' => 0 ],
        ] );

        $this->add_control( 'load_more_text', [
            'label'     => esc_html__( 'Load More Label', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => esc_html__( 'Load more', 'rawnaq' ),
            'condition' => [ 'initial_visible!' => 0 ],
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
        $this->add_responsive_control( 'line_width', [
            'label'      => esc_html__( 'Line Thickness', 'rawnaq' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 1, 'max' => 12 ] ],
            'default'    => [ 'unit' => 'px', 'size' => 4 ],
            'selectors'  => [ '{{WRAPPER}} .rawnaq-timeline-wrapper' => '--tl-line-width: {{SIZE}}{{UNIT}};' ],
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
        if ( 'horizontal' === $layout ) {
            return 'h-item';
        }
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

    private function render_step_media( $step ) {
        $video_url = '';
        if ( ! empty( $step['video_url']['url'] ) ) {
            $video_url = $step['video_url']['url'];
        } elseif ( ! empty( $step['video_url'] ) && is_string( $step['video_url'] ) ) {
            $video_url = $step['video_url'];
        } elseif ( ! empty( $step['video'] ) && is_string( $step['video'] ) ) {
            $video_url = $step['video'];
        }

        if ( $video_url && function_exists( 'rawnaq_bento_video_markup' ) ) {
            echo '<div class="rawnaq-timeline-media">';
            // Markup escaped inside helper.
            echo rawnaq_bento_video_markup( $video_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '</div>';
            return;
        }

        $img = ! empty( $step['image']['url'] ) ? $step['image']['url'] : ( $step['imageUrl'] ?? '' );
        if ( ! $img ) {
            return;
        }
        $alt = ! empty( $step['image']['alt'] ) ? $step['image']['alt'] : ( $step['title'] ?? '' );
        echo '<img class="rawnaq-timeline-thumb" src="' . esc_url( $img ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" />';
    }

    private function is_elementor_edit_mode() {
        return class_exists( '\Elementor\Plugin' )
            && \Elementor\Plugin::$instance->editor
            && \Elementor\Plugin::$instance->editor->is_edit_mode();
    }

    private function query_payload( $s ) {
        return [
            'post_type' => $s['query_post_type'] ?? 'post',
            'orderby'   => $s['query_orderby'] ?? 'date',
            'order'     => $s['query_order'] ?? 'DESC',
            'taxonomy'  => $s['query_taxonomy'] ?? '',
            'terms'     => $s['query_terms'] ?? '',
            'include'     => $s['query_include'] ?? '',
            'exclude_ids' => $s['query_exclude'] ?? '',
            'max'         => max( 1, absint( $s['query_posts_per_page'] ?? 6 ) ),
        ];
    }

    private function resolve_steps_bundle( $s ) {
        $source = sanitize_key( $s['source'] ?? 'manual' );
        $initial_visible = max( 0, absint( $s['initial_visible'] ?? 0 ) );
        $bundle = [
            'steps'      => [],
            'use_ajax'   => false,
            'offset'     => 0,
            'has_more'   => false,
            'query_b64'  => '',
            'found'      => 0,
        ];

        if ( 'query' === $source && function_exists( 'rawnaq_timeline_query_result' ) ) {
            $payload = $this->query_payload( $s );
            $max     = (int) $payload['max'];
            $use_ajax = $initial_visible > 0;
            $per_page = $use_ajax ? min( $initial_visible, $max ) : $max;
            $result   = rawnaq_timeline_query_result(
                array_merge( $payload, [
                    'posts_per_page' => $per_page,
                    'offset'         => 0,
                ] ),
                [
                    'builder'   => 'elementor',
                    'widget_id' => $this->get_id(),
                ]
            );
            $bundle['steps']     = $result['steps'];
            $bundle['found']     = (int) $result['found_posts'];
            $bundle['offset']    = count( $result['steps'] );
            $bundle['use_ajax']  = $use_ajax;
            $bundle['has_more']  = $use_ajax && $bundle['offset'] < $max && $bundle['offset'] < $bundle['found'];
            // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- packing sanitized query args for a data attribute, not obfuscation.
            $bundle['query_b64'] = base64_encode( wp_json_encode( rawnaq_timeline_sanitize_query_args( $payload ) ) );
            return $bundle;
        }

        $steps = $s['steps'] ?? [];
        if ( function_exists( 'rawnaq_timeline_filter_steps' ) ) {
            $steps = rawnaq_timeline_filter_steps( $steps, [
                'source'    => 'manual',
                'builder'   => 'elementor',
                'widget_id' => $this->get_id(),
            ] );
        }
        $bundle['steps'] = is_array( $steps ) ? $steps : [];
        return $bundle;
    }

    private function resolve_tl_name( $s ) {
        $custom = trim( (string) ( $s['timeline_name'] ?? '' ) );
        $fallback = 'rawnaq-tl-' . $this->get_id();
        if ( function_exists( 'rawnaq_timeline_sanitize_tl_name' ) ) {
            return rawnaq_timeline_sanitize_tl_name( $custom ? $custom : $fallback, $fallback );
        }
        return $custom ? $custom : $fallback;
    }

    protected function render() {
        $s               = $this->get_settings_for_display();
        $bundle          = $this->resolve_steps_bundle( $s );
        $steps           = $bundle['steps'];
        $layout          = $s['layout'] ?? 'alternating';
        $show_numbers    = ( $s['show_numbers'] ?? '' ) === 'yes';
        $initial_visible = max( 0, absint( $s['initial_visible'] ?? 0 ) );
        $load_chunk      = max( 1, absint( $s['load_chunk'] ?? 3 ) );
        $load_more_text  = trim( (string) ( $s['load_more_text'] ?? '' ) );
        if ( '' === $load_more_text ) {
            $load_more_text = function_exists( 'rawnaq_translate' )
                ? rawnaq_translate( 'load_more', __( 'Load more', 'rawnaq' ) )
                : __( 'Load more', 'rawnaq' );
        } elseif ( function_exists( 'rawnaq_translate' ) ) {
            $load_more_text = rawnaq_translate( 'load_more', $load_more_text );
        }
        $tl_name    = $this->resolve_tl_name( $s );
        $wrap_class = 'rawnaq-timeline-wrapper layout-' . sanitize_html_class( $layout );
        if ( $show_numbers ) {
            $wrap_class .= ' show-numbers';
        }
        if ( $this->is_elementor_edit_mode() ) {
            $wrap_class .= ' is-editor';
        }
        $wrap_style = 'scroll-timeline-name: --' . $tl_name . ';';
        $show_load  = $bundle['use_ajax']
            ? $bundle['has_more']
            : ( $initial_visible > 0 && count( $steps ) > $initial_visible );

        if ( 'query' === $source && ! $this->is_elementor_edit_mode() && function_exists( 'rawnaq_schema_print' ) && function_exists( 'rawnaq_schema_timeline' ) ) {
            rawnaq_schema_print( rawnaq_schema_timeline( $steps ), 'timeline' );
        }
        ?>
        <div
            class="<?php echo esc_attr( $wrap_class ); ?>"
            data-show-numbers="<?php echo $show_numbers ? '1' : '0'; ?>"
            data-tl-name="<?php echo esc_attr( $tl_name ); ?>"
            data-initial-visible="<?php echo esc_attr( (string) ( $bundle['use_ajax'] ? 0 : $initial_visible ) ); ?>"
            data-load-chunk="<?php echo esc_attr( (string) $load_chunk ); ?>"
            <?php if ( $bundle['use_ajax'] ) : ?>
                data-tl-ajax="1"
                data-tl-offset="<?php echo esc_attr( (string) $bundle['offset'] ); ?>"
                data-tl-query="<?php echo esc_attr( $bundle['query_b64'] ); ?>"
                data-tl-layout="<?php echo esc_attr( $layout ); ?>"
            <?php endif; ?>
            style="<?php echo esc_attr( $wrap_style ); ?>"
        >
            <div class="rawnaq-timeline-engine-badge" aria-hidden="true">
                <?php echo esc_html( function_exists( 'rawnaq_translate' ) ? rawnaq_translate( 'engine_badge', __( 'Native CSS scroll animations — no motion JS in this browser.', 'rawnaq' ) ) : __( 'Native CSS scroll animations — no motion JS in this browser.', 'rawnaq' ) ); ?>
            </div>
            <div class="rawnaq-timeline-line-bg"></div>
            <div class="rawnaq-timeline-line-active"></div>

            <?php foreach ( $steps as $index => $step ) :
                $side     = $this->side_class( $index, $layout );
                $num      = str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT );
                $cta_text = trim( (string) ( $step['cta_text'] ?? $step['ctaText'] ?? '' ) );
                $cta_link = $step['cta_link'] ?? [];
                if ( empty( $cta_link['url'] ) && ! empty( $step['ctaLink'] ) ) {
                    $cta_link = [ 'url' => $step['ctaLink'] ];
                }
                $project_id = (string) ( $step['project_id'] ?? $step['projectId'] ?? '' );
                if ( ! $project_id && ! empty( $step['post_id'] ) ) {
                    $project_id = 'post-' . absint( $step['post_id'] );
                }
                $project_slug = (string) ( $step['project_slug'] ?? $step['projectSlug'] ?? '' );
                ?>
                <div class="rawnaq-timeline-item <?php echo esc_attr( $side ); ?>"
                    <?php if ( $project_id ) : ?>data-project-id="<?php echo esc_attr( $project_id ); ?>"<?php endif; ?>
                    <?php if ( $project_slug ) : ?>data-project-slug="<?php echo esc_attr( $project_slug ); ?>"<?php endif; ?>>
                    <span class="rawnaq-timeline-bullet">
                        <?php if ( $show_numbers ) : ?>
                            <span class="num"><?php echo esc_html( $num ); ?></span>
                        <?php endif; ?>
                    </span>
                    <div class="rawnaq-timeline-card">
                        <?php $this->render_step_media( $step ); ?>
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

            <?php if ( $show_load ) : ?>
                <div class="rawnaq-timeline-load-more">
                    <button type="button"><?php echo esc_html( $load_more_text ); ?></button>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    protected function content_template() {
        ?>
        <#
        var layout = settings.layout || 'alternating';
        var showNumbers = settings.show_numbers === 'yes';
        var initialVisible = parseInt( settings.initial_visible, 10 ) || 0;
        var loadChunk = parseInt( settings.load_chunk, 10 ) || 3;
        var loadMoreText = settings.load_more_text || 'Load more';
        var source = settings.source || 'manual';
        var customTl = ( settings.timeline_name || '' ).toString().replace(/[^a-zA-Z0-9_-]/g, '');
        var tlName = customTl || ( 'rawnaq-tl-' + view.getID() );
        if ( /^[0-9]/.test( tlName ) ) { tlName = 'tl-' + tlName; }
        var wrapClass = 'rawnaq-timeline-wrapper layout-' + layout + ' is-editor' + ( showNumbers ? ' show-numbers' : '' );
        var stepCount = ( settings.steps && settings.steps.length ) ? settings.steps.length : 0;
        #>
        <div
            class="{{ wrapClass }}"
            data-show-numbers="{{ showNumbers ? '1' : '0' }}"
            data-tl-name="{{ tlName }}"
            data-initial-visible="{{ initialVisible }}"
            data-load-chunk="{{ loadChunk }}"
            style="scroll-timeline-name: --{{ tlName }};"
        >
            <div class="rawnaq-timeline-engine-badge" aria-hidden="true">
                <?php echo esc_html__( 'Native CSS scroll animations — no motion JS in this browser.', 'rawnaq' ); ?>
            </div>
            <div class="rawnaq-timeline-line-bg"></div>
            <div class="rawnaq-timeline-line-active"></div>
            <# if ( source === 'query' ) { #>
                <div class="rawnaq-timeline-item left-item item-active">
                    <span class="rawnaq-timeline-bullet"><# if ( showNumbers ) { #><span class="num">01</span><# } #></span>
                    <div class="rawnaq-timeline-card">
                        <span class="rawnaq-timeline-meta"><?php echo esc_html__( 'Query mode', 'rawnaq' ); ?></span>
                        <h4><?php echo esc_html__( 'Posts load on the frontend', 'rawnaq' ); ?></h4>
                        <p><?php echo esc_html__( 'Save and preview the page to see live CPT / post results.', 'rawnaq' ); ?></p>
                        <p style="margin-top:8px;font-size:12px;opacity:.8;"><?php echo esc_html__( 'Named timeline:', 'rawnaq' ); ?> <code>{{ tlName }}</code></p>
                    </div>
                </div>
            <# } else if ( settings.steps ) {
                _.each( settings.steps, function( step, index ) {
                    var side = 'h-item';
                    if ( layout === 'left' ) {
                        side = 'left-item';
                    } else if ( layout === 'right' ) {
                        side = 'right-item';
                    } else if ( layout === 'alternating' ) {
                        side = ( index % 2 === 0 ) ? 'left-item' : 'right-item';
                    } else if ( layout !== 'horizontal' ) {
                        side = ( index % 2 === 0 ) ? 'left-item' : 'right-item';
                    }
                    var num = ( index + 1 < 10 ) ? ( '0' + ( index + 1 ) ) : String( index + 1 );
                    var videoUrl = ( step.video_url && step.video_url.url ) ? step.video_url.url : '';
                    var img = ( step.image && step.image.url ) ? step.image.url : '';
                    var iconHTML = elementor.helpers.renderIcon( view, step.selected_icon, { 'aria-hidden': true }, 'i', 'object' );
                    var ctaUrl = ( step.cta_link && step.cta_link.url ) ? step.cta_link.url : '';
                    #>
                    <div class="rawnaq-timeline-item {{ side }} item-active">
                        <span class="rawnaq-timeline-bullet">
                            <# if ( showNumbers ) { #><span class="num">{{ num }}</span><# } #>
                        </span>
                        <div class="rawnaq-timeline-card">
                            <# if ( videoUrl ) { #>
                                <div class="rawnaq-timeline-media">
                                    <div class="rawnaq-bento-media" style="background:#1e1b2e;position:absolute;inset:0;" aria-hidden="true"></div>
                                </div>
                            <# } else if ( img ) { #>
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
            <# if ( source !== 'query' && initialVisible > 0 && stepCount > initialVisible ) { #>
                <div class="rawnaq-timeline-load-more">
                    <button type="button">{{{ loadMoreText }}}</button>
                </div>
            <# } #>
        </div>
        <?php
    }
}
