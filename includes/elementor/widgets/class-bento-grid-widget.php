<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rawnaq_Bento_Grid_Widget extends \Elementor\Widget_Base {

    public function get_name()       { return 'rawnaq_bento_grid'; }
    public function get_title()      { return esc_html__( 'Bento Grid', 'rawnaq' ); }
    public function get_icon()       { return 'eicon-gallery-grid'; }
    public function get_categories() { return [ 'rawnaq' ]; }

    public function get_style_depends()  { return [ 'rawnaq-bento-grid', 'dashicons' ]; }
    public function get_script_depends() { return [ 'rawnaq-bento-grid' ]; }

    protected function register_controls() {
        $this->start_controls_section( 's_layout', [
            'label' => esc_html__( 'Grid Layout', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'preset', [
            'label'   => esc_html__( 'Layout Preset', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'featured',
            'options' => [
                'featured' => esc_html__( '1 large + 4 small', 'rawnaq' ),
                'equal'    => esc_html__( '2×2 equal (4 cols)', 'rawnaq' ),
                'wide'     => esc_html__( '1 wide + stacked (3 cols)', 'rawnaq' ),
                'custom'   => esc_html__( 'Custom columns', 'rawnaq' ),
            ],
            'description' => esc_html__( 'Choose a layout, then click Apply Preset to replace cells (spans + content).', 'rawnaq' ),
        ] );

        $this->add_control( 'apply_preset', [
            'type'         => \Elementor\Controls_Manager::BUTTON,
            'label'        => esc_html__( 'Apply Preset', 'rawnaq' ),
            'text'         => esc_html__( 'Apply Preset to Cells', 'rawnaq' ),
            'button_type'  => 'success',
            'event'        => 'rawnaq:bento:applyPreset',
            'separator'    => 'after',
            'condition'    => [ 'preset!' => 'custom' ],
            'description'  => esc_html__( 'Replaces the Cells repeater with this preset. Your current cells will be overwritten.', 'rawnaq' ),
        ] );

        $this->add_control( 'columns', [
            'label'     => esc_html__( 'Columns', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::NUMBER,
            'default'   => 4,
            'min'       => 2,
            'max'       => 6,
            'condition' => [ 'preset' => 'custom' ],
        ] );

        $this->add_control( 'row_height', [
            'label'   => esc_html__( 'Row Height (px)', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => [ 'px' => [ 'min' => 80, 'max' => 240 ] ],
            'default' => [ 'size' => 140 ],
            'selectors' => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-row: {{SIZE}}px;',
            ],
        ] );

        $this->add_control( 'column_gap', [
            'label'   => esc_html__( 'Column Gap', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
            'default' => [ 'size' => 16 ],
            'selectors' => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-gap-col: {{SIZE}}px;',
            ],
        ] );

        $this->add_control( 'row_gap', [
            'label'   => esc_html__( 'Row Gap', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
            'default' => [ 'size' => 16 ],
            'selectors' => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-gap-row: {{SIZE}}px;',
            ],
        ] );

        $this->add_control( 'reveal', [
            'label'        => esc_html__( 'Scroll Reveal', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'hover_effect', [
            'label'   => esc_html__( 'Hover Effect', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'lift',
            'options' => [
                'lift' => esc_html__( 'Lift', 'rawnaq' ),
                'zoom' => esc_html__( 'Zoom media', 'rawnaq' ),
                'tint' => esc_html__( 'Tint', 'rawnaq' ),
                'none' => esc_html__( 'None', 'rawnaq' ),
            ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 's_cells', [
            'label' => esc_html__( 'Cells', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $r = new \Elementor\Repeater();

        $r->add_control( 'cell_type', [
            'label'   => esc_html__( 'Content Type', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'text',
            'options' => [
                'text'         => esc_html__( 'Icon + Text', 'rawnaq' ),
                'featured'     => esc_html__( 'Featured (accent)', 'rawnaq' ),
                'image'        => esc_html__( 'Image', 'rawnaq' ),
                'stat'         => esc_html__( 'Stat Counter', 'rawnaq' ),
                'video'        => esc_html__( 'Video', 'rawnaq' ),
                'testimonial'  => esc_html__( 'Testimonial', 'rawnaq' ),
            ],
        ] );

        $r->add_control( 'col_span', [
            'label'   => esc_html__( 'Column Span', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 1,
            'min'     => 1,
            'max'     => 6,
        ] );

        $r->add_control( 'row_span', [
            'label'   => esc_html__( 'Row Span', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 1,
            'min'     => 1,
            'max'     => 4,
        ] );

        $r->add_control( 'content_align', [
            'label'   => esc_html__( 'Content Align', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => '',
            'options' => [
                ''       => esc_html__( 'Default (by type)', 'rawnaq' ),
                'top'    => esc_html__( 'Top', 'rawnaq' ),
                'center' => esc_html__( 'Center', 'rawnaq' ),
                'bottom' => esc_html__( 'Bottom', 'rawnaq' ),
            ],
            'description' => esc_html__( 'Vertical alignment of cell content.', 'rawnaq' ),
        ] );

        $r->add_control( 'order_desktop', [
            'label'       => esc_html__( 'Order (Desktop)', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::NUMBER,
            'default'     => 0,
            'min'         => -20,
            'max'         => 20,
            'description' => esc_html__( '0 = natural order. Lower numbers appear first.', 'rawnaq' ),
        ] );

        $r->add_control( 'responsive_heading', [
            'label'     => esc_html__( 'Tablet / Mobile Overrides', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $r->add_control( 'col_span_tablet', [
            'label'       => esc_html__( 'Col Span (Tablet ≤900px)', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::NUMBER,
            'default'     => 0,
            'min'         => 0,
            'max'         => 6,
            'description' => esc_html__( '0 = inherit desktop. Grid is 2 columns on tablet.', 'rawnaq' ),
        ] );

        $r->add_control( 'row_span_tablet', [
            'label'   => esc_html__( 'Row Span (Tablet)', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 0,
            'min'     => 0,
            'max'     => 4,
        ] );

        $r->add_control( 'order_tablet', [
            'label'   => esc_html__( 'Order (Tablet)', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 0,
            'min'     => -20,
            'max'     => 20,
        ] );

        $r->add_control( 'col_span_mobile', [
            'label'       => esc_html__( 'Col Span (Mobile ≤640px)', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::NUMBER,
            'default'     => 0,
            'min'         => 0,
            'max'         => 2,
            'description' => esc_html__( '0 = full width. Set 1 for half-width on 2-col mobile grid.', 'rawnaq' ),
        ] );

        $r->add_control( 'row_span_mobile', [
            'label'   => esc_html__( 'Row Span (Mobile)', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 0,
            'min'     => 0,
            'max'     => 4,
        ] );

        $r->add_control( 'order_mobile', [
            'label'   => esc_html__( 'Order (Mobile)', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 0,
            'min'     => -20,
            'max'     => 20,
        ] );

        $r->add_control( 'tag', [
            'label'       => esc_html__( 'Tag / Eyebrow', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'label_block' => true,
        ] );

        $r->add_control( 'cell_tag_bg', [
            'label'       => esc_html__( 'Tag Background', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::COLOR,
            'default'     => '',
            'description' => esc_html__( 'Optional. Leave empty to use global Tag / Badge colors.', 'rawnaq' ),
            'condition'   => [ 'tag!' => '' ],
        ] );

        $r->add_control( 'cell_tag_color', [
            'label'     => esc_html__( 'Tag Text', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '',
            'condition' => [ 'tag!' => '' ],
        ] );

        $r->add_control( 'title', [
            'label'       => esc_html__( 'Title / Author', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'Cell title',
            'label_block' => true,
            'condition'   => [ 'cell_type!' => 'stat' ],
            'description' => esc_html__( 'For testimonials, this is the author name.', 'rawnaq' ),
        ] );

        $r->add_control( 'subtitle', [
            'label'       => esc_html__( 'Subtitle / Quote', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXTAREA,
            'default'     => '',
            'rows'        => 2,
            'description' => esc_html__( 'For testimonials, this is the quote text.', 'rawnaq' ),
        ] );

        $r->add_control( 'testimonial_role', [
            'label'       => esc_html__( 'Author Role / Company', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'label_block' => true,
            'condition'   => [ 'cell_type' => 'testimonial' ],
        ] );

        $r->add_control( 'testimonial_avatar', [
            'label'     => esc_html__( 'Author Avatar', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::MEDIA,
            'condition' => [ 'cell_type' => 'testimonial' ],
        ] );

        $r->add_control( 'testimonial_rating', [
            'label'       => esc_html__( 'Star Rating', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::NUMBER,
            'default'     => 5,
            'min'         => 0,
            'max'         => 5,
            'description' => esc_html__( '0 hides stars.', 'rawnaq' ),
            'condition'   => [ 'cell_type' => 'testimonial' ],
        ] );

        $r->add_control( 'selected_icon', [
            'label'     => esc_html__( 'Icon', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::ICONS,
            'default'   => [
                'value'   => 'fas fa-bolt',
                'library' => 'fa-solid',
            ],
            'condition' => [ 'cell_type' => [ 'text', 'featured' ] ],
        ] );

        $r->add_control( 'image', [
            'label'     => esc_html__( 'Image', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::MEDIA,
            'condition' => [ 'cell_type' => 'image' ],
        ] );

        $r->add_control( 'video_url', [
            'label'       => esc_html__( 'Video URL', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::URL,
            'placeholder' => 'https://www.youtube.com/watch?v=…',
            'description' => esc_html__( 'YouTube, Vimeo, or direct mp4/webm URL.', 'rawnaq' ),
            'condition'   => [ 'cell_type' => 'video' ],
        ] );

        $r->add_control( 'stat_value', [
            'label'     => esc_html__( 'Stat Value', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '42',
            'condition' => [ 'cell_type' => 'stat' ],
        ] );

        $r->add_control( 'stat_suffix', [
            'label'     => esc_html__( 'Stat Suffix', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '+',
            'condition' => [ 'cell_type' => 'stat' ],
        ] );

        $r->add_control( 'stat_prefix', [
            'label'     => esc_html__( 'Stat Prefix', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '',
            'condition' => [ 'cell_type' => 'stat' ],
        ] );

        $r->add_control( 'sync_timeline', [
            'label'       => esc_html__( 'Sync Timeline ID', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => 'rawnaq-tl-my-section',
            'description' => esc_html__( 'Paste the Named Timeline ID from Scroll Sync Timeline to link this cell’s reveal to that scroll progress.', 'rawnaq' ),
            'label_block' => true,
        ] );

        $r->add_control( 'link', [
            'label'         => esc_html__( 'Cell Link', 'rawnaq' ),
            'type'          => \Elementor\Controls_Manager::URL,
            'placeholder'   => 'https://example.com',
            'show_external' => true,
            'description'   => esc_html__( 'Makes the whole cell clickable when CTA is empty.', 'rawnaq' ),
        ] );

        $r->add_control( 'cta_text', [
            'label'       => esc_html__( 'CTA Button Text', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => esc_html__( 'Learn more', 'rawnaq' ),
            'label_block' => true,
        ] );

        $r->add_control( 'cta_link', [
            'label'         => esc_html__( 'CTA Button Link', 'rawnaq' ),
            'type'          => \Elementor\Controls_Manager::URL,
            'placeholder'   => 'https://example.com',
            'show_external' => true,
            'description'   => esc_html__( 'Optional. Falls back to Cell Link if empty.', 'rawnaq' ),
            'condition'     => [ 'cta_text!' => '' ],
        ] );

        $r->add_control( 'bg_color', [
            'label'     => esc_html__( 'Background Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'condition' => [ 'cell_type' => [ 'text', 'stat', 'testimonial' ] ],
        ] );

        $this->add_control( 'cells', [
            'label'       => esc_html__( 'Grid Cells', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::REPEATER,
            'fields'      => $r->get_controls(),
            'title_field' => '{{{ title || tag || cell_type }}}',
            'default'     => $this->get_default_cells(),
        ] );

        $this->end_controls_section();

        // ── Style: Surface ──
        $this->start_controls_section( 's_style_surface', [
            'label' => esc_html__( 'Surface', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'radius', [
            'label'   => esc_html__( 'Cell Radius', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
            'default' => [ 'size' => 18 ],
            'selectors' => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-radius: {{SIZE}}px;',
            ],
        ] );

        $this->add_control( 'cell_pad', [
            'label'   => esc_html__( 'Cell Padding', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => [ 'px' => [ 'min' => 8, 'max' => 40 ] ],
            'default' => [ 'size' => 18 ],
            'selectors' => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-pad: {{SIZE}}px;',
            ],
        ] );

        $this->add_control( 'cell_bg', [
            'label'     => esc_html__( 'Cell Background', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-panel: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'cell_border', [
            'label'     => esc_html__( 'Cell Border', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#d7e2dc',
            'selectors' => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-line: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'hairline', [
            'label'        => esc_html__( 'Hairline Borders', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $this->add_control( 'overlay_opacity', [
            'label'       => esc_html__( 'Image Overlay Opacity', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::SLIDER,
            'size_units'  => [ '%' ],
            'range'       => [
                '%' => [ 'min' => 0, 'max' => 100 ],
            ],
            'default'     => [ 'unit' => '%', 'size' => 100 ],
            'description' => esc_html__( 'Dark gradient over image/video cells. 0% = none, 100% = default strength.', 'rawnaq' ),
            'selectors'   => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-overlay-opacity: calc({{SIZE}} / 100);',
            ],
        ] );

        $this->end_controls_section();

        // ── Style: Tag / Badge (the yellow pill) ──
        $this->start_controls_section( 's_style_tag', [
            'label' => esc_html__( 'Tag / Badge', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'tag_help', [
            'type'            => \Elementor\Controls_Manager::RAW_HTML,
            'raw_html'        => '<p style="margin:0;font-size:12px;color:#5c6f66;">' . esc_html__( 'Controls the small pill label (e.g. HIGHLIGHT / SHOWCASE) on cells.', 'rawnaq' ) . '</p>',
            'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
        ] );

        $this->add_control( 'tag_bg', [
            'label'     => esc_html__( 'Tag Background', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#fef3c7',
            'selectors' => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-tag-bg: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'tag_color', [
            'label'     => esc_html__( 'Tag Text', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#92400e',
            'selectors' => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-tag-color: {{VALUE}};',
            ],
        ] );

        $this->end_controls_section();

        // ── Style: Typography ──
        $this->start_controls_section( 's_style_type', [
            'label' => esc_html__( 'Typography', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'title_color', [
            'label'     => esc_html__( 'Title Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#13231c',
            'selectors' => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-title-color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'sub_color', [
            'label'     => esc_html__( 'Subtitle Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#5c6f66',
            'selectors' => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-sub-color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'icon_color', [
            'label'     => esc_html__( 'Icon Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#0f766e',
            'selectors' => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-icon-color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'title_size', [
            'label'   => esc_html__( 'Title Size', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => [ 'px' => [ 'min' => 12, 'max' => 28 ] ],
            'default' => [ 'size' => 15 ],
            'selectors' => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-title-size: {{SIZE}}px;',
            ],
        ] );

        $this->add_control( 'sub_size', [
            'label'   => esc_html__( 'Subtitle Size', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => [ 'px' => [ 'min' => 10, 'max' => 18 ] ],
            'default' => [ 'size' => 12 ],
            'selectors' => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-sub-size: {{SIZE}}px;',
            ],
        ] );

        $this->end_controls_section();

        // ── Style: Featured ──
        $this->start_controls_section( 's_style_featured', [
            'label' => esc_html__( 'Featured Cell', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'featured_from', [
            'label'     => esc_html__( 'Gradient From', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#0f766e',
            'selectors' => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-featured-from: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'featured_to', [
            'label'     => esc_html__( 'Gradient To', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#134e4a',
            'selectors' => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-featured-to: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'featured_ring', [
            'label'     => esc_html__( 'Amber Ring', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => 'rgba(251, 191, 36, 0.7)',
            'selectors' => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-ring: {{VALUE}};',
            ],
        ] );

        $this->end_controls_section();

        // ── Style: Stat + Media ──
        $this->start_controls_section( 's_style_stat', [
            'label' => esc_html__( 'Stat & Media', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'stat_color', [
            'label'     => esc_html__( 'Stat Number Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#0f766e',
            'selectors' => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-stat-color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'stat_size', [
            'label'   => esc_html__( 'Stat Size', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => [ 'px' => [ 'min' => 24, 'max' => 56 ] ],
            'default' => [ 'size' => 38 ],
            'selectors' => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-stat-size: {{SIZE}}px;',
            ],
        ] );

        $this->add_control( 'accent_color', [
            'label'     => esc_html__( 'Accent (hover / icons)', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#0f766e',
            'selectors' => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-accent: {{VALUE}};',
            ],
        ] );

        $this->end_controls_section();

        // ── Style: CTA ──
        $this->start_controls_section( 's_style_cta', [
            'label' => esc_html__( 'CTA Button', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'cta_bg', [
            'label'     => esc_html__( 'Button Background', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#fbbf24',
            'selectors' => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-cta-bg: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'cta_color', [
            'label'     => esc_html__( 'Button Text', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#92400e',
            'selectors' => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-cta-color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'cta_radius', [
            'label'      => esc_html__( 'Button Radius', 'rawnaq' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
            'default'    => [ 'unit' => 'px', 'size' => 999 ],
            'selectors'  => [
                '{{WRAPPER}} .rawnaq-bento-grid' => '--bento-cta-radius: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();
    }

    private function get_default_cells() {
        if ( function_exists( 'rawnaq_bento_preset_for_elementor' ) ) {
            $pack = rawnaq_bento_preset_for_elementor( 'featured' );
            if ( ! empty( $pack['cells'] ) ) {
                return $pack['cells'];
            }
        }
        return [];
    }

    private function resolve_columns( $s ) {
        $preset = $s['preset'] ?? 'featured';
        if ( 'wide' === $preset ) {
            return 3;
        }
        if ( 'custom' === $preset ) {
            $cols = absint( $s['columns'] ?? 4 );
            return max( 2, min( 6, $cols ?: 4 ) );
        }
        return 4;
    }

    private function url_attrs( $link ) {
        if ( empty( $link['url'] ) ) {
            return '';
        }
        $attrs = ' href="' . esc_url( $link['url'] ) . '"';
        if ( ! empty( $link['is_external'] ) ) {
            $attrs .= ' target="_blank"';
        }
        $rel = [];
        if ( ! empty( $link['is_external'] ) ) {
            $rel[] = 'noopener';
        }
        if ( ! empty( $link['nofollow'] ) ) {
            $rel[] = 'nofollow';
        }
        if ( $rel ) {
            $attrs .= ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"';
        }
        return $attrs;
    }

    private function render_cell_icon( $cell ) {
        if ( empty( $cell['selected_icon']['value'] ) ) {
            return;
        }
        echo '<div class="rawnaq-bento-icon" aria-hidden="true">';
        \Elementor\Icons_Manager::render_icon( $cell['selected_icon'], [ 'aria-hidden' => 'true' ] );
        echo '</div>';
    }

    private function render_cta( $cell ) {
        $text = trim( (string) ( $cell['cta_text'] ?? '' ) );
        if ( '' === $text ) {
            return;
        }

        $cta_link  = $cell['cta_link'] ?? [];
        $cell_link = $cell['link'] ?? [];
        $url       = ! empty( $cta_link['url'] ) ? $cta_link : $cell_link;

        if ( ! empty( $url['url'] ) ) {
            echo '<a class="rawnaq-bento-cta"';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in url_attrs().
            echo $this->url_attrs( $url );
            echo '>' . esc_html( $text ) . '</a>';
            return;
        }

        echo '<span class="rawnaq-bento-cta is-static">' . esc_html( $text ) . '</span>';
    }

    private function render_tag( $cell ) {
        $tag = $cell['tag'] ?? '';
        if ( '' === $tag ) {
            return;
        }

        $attrs = function_exists( 'rawnaq_bento_tag_attrs' )
            ? rawnaq_bento_tag_attrs( $cell['cell_tag_bg'] ?? '', $cell['cell_tag_color'] ?? '' )
            : [ 'class' => 'rawnaq-bento-tag', 'style' => '' ];

        echo '<div class="' . esc_attr( $attrs['class'] ) . '"';
        if ( ! empty( $attrs['style'] ) ) {
            echo ' style="' . esc_attr( $attrs['style'] ) . '"';
        }
        echo '>' . esc_html( $tag ) . '</div>';
    }

    private function render_stars( $rating ) {
        $n = max( 0, min( 5, absint( $rating ) ) );
        if ( $n < 1 ) {
            return;
        }
        echo '<div class="rawnaq-bento-stars" aria-label="' . esc_attr( sprintf( /* translators: %d: star count */ __( '%d out of 5 stars', 'rawnaq' ), $n ) ) . '">';
        echo esc_html( str_repeat( '★', $n ) );
        echo '</div>';
    }

    private function render_testimonial( $cell ) {
        $quote  = $cell['subtitle'] ?? '';
        $author = $cell['title'] ?? '';
        $role   = $cell['testimonial_role'] ?? '';
        $avatar = $cell['testimonial_avatar']['url'] ?? '';
        $rating = $cell['testimonial_rating'] ?? 0;

        $this->render_tag( $cell );

        if ( $quote ) {
            echo '<blockquote class="rawnaq-bento-quote">' . esc_html( $quote ) . '</blockquote>';
        }

        $this->render_stars( $rating );

        if ( $author || $role || $avatar ) {
            echo '<div class="rawnaq-bento-author">';
            if ( $avatar ) {
                echo '<img class="rawnaq-bento-avatar" src="' . esc_url( $avatar ) . '" alt="" loading="lazy" decoding="async" />';
            } elseif ( $author ) {
                $initial = function_exists( 'mb_substr' ) ? mb_substr( $author, 0, 1 ) : substr( $author, 0, 1 );
                echo '<div class="rawnaq-bento-avatar is-placeholder" aria-hidden="true">' . esc_html( strtoupper( $initial ) ) . '</div>';
            }
            if ( $author || $role ) {
                echo '<div class="rawnaq-bento-author-meta">';
                if ( $author ) {
                    echo '<div class="rawnaq-bento-author-name">' . esc_html( $author ) . '</div>';
                }
                if ( $role ) {
                    echo '<div class="rawnaq-bento-author-role">' . esc_html( $role ) . '</div>';
                }
                echo '</div>';
            }
            echo '</div>';
        }

        $this->render_cta( $cell );
    }

    private function render_cell( $cell, $index = 0 ) {
        $type     = sanitize_key( $cell['cell_type'] ?? 'text' );
        $title    = $cell['title'] ?? '';
        $subtitle = $cell['subtitle'] ?? '';
        $link     = $cell['link'] ?? [];
        $cta_text = trim( (string) ( $cell['cta_text'] ?? '' ) );
        $has_cta  = '' !== $cta_text;
        $has_link = ! empty( $link['url'] );
        // Avoid nested anchors: whole-cell link only when there is no CTA button.
        $tag_name = ( $has_link && ! $has_cta ) ? 'a' : 'div';
        if ( ! in_array( $tag_name, [ 'a', 'div' ], true ) ) {
            $tag_name = 'div';
        }

        $layout = function_exists( 'rawnaq_bento_cell_layout' )
            ? rawnaq_bento_cell_layout( [
                'col'      => $cell['col_span'] ?? 1,
                'row'      => $cell['row_span'] ?? 1,
                'order'    => $cell['order_desktop'] ?? 0,
                'col_md'   => $cell['col_span_tablet'] ?? 0,
                'row_md'   => $cell['row_span_tablet'] ?? 0,
                'order_md' => $cell['order_tablet'] ?? 0,
                'col_sm'   => $cell['col_span_mobile'] ?? 0,
                'row_sm'   => $cell['row_span_mobile'] ?? 0,
                'order_sm' => $cell['order_mobile'] ?? 0,
            ] )
            : [
                'style'   => sprintf(
                    'grid-column:span %d;grid-row:span %d;',
                    max( 1, absint( $cell['col_span'] ?? 1 ) ),
                    max( 1, absint( $cell['row_span'] ?? 1 ) )
                ),
                'classes' => [],
            ];

        $classes = array_merge( [ 'rawnaq-bento-cell' ], $layout['classes'] );
        $align_class = function_exists( 'rawnaq_bento_align_class' )
            ? rawnaq_bento_align_class( $cell['content_align'] ?? '' )
            : '';
        if ( $align_class ) {
            $classes[] = $align_class;
        }
        if ( 'featured' === $type ) {
            $classes[] = 'is-featured';
        } elseif ( 'image' === $type ) {
            $classes[] = 'is-image';
        } elseif ( 'stat' === $type ) {
            $classes[] = 'is-stat';
        } elseif ( 'video' === $type ) {
            $classes[] = 'is-video';
            $vid_url    = $cell['video_url']['url'] ?? '';
            $vid_parsed = function_exists( 'rawnaq_bento_parse_video' ) ? rawnaq_bento_parse_video( $vid_url ) : null;
            if ( $vid_parsed && in_array( $vid_parsed['kind'], [ 'youtube', 'vimeo' ], true ) ) {
                $classes[] = 'is-embed';
            }
        } elseif ( 'testimonial' === $type ) {
            $classes[] = 'is-testimonial';
        }

        $sync_raw = trim( (string) ( $cell['sync_timeline'] ?? '' ) );
        $sync_name = '';
        if ( $sync_raw ) {
            $sync_name = function_exists( 'rawnaq_timeline_sanitize_tl_name' )
                ? rawnaq_timeline_sanitize_tl_name( $sync_raw, $sync_raw )
                : preg_replace( '/[^a-zA-Z0-9_-]/', '', $sync_raw );
        }
        if ( $sync_name ) {
            $classes[] = 'tl-sync';
        }

        $style = $layout['style'];
        if ( $sync_name ) {
            $style .= 'animation-timeline:--' . $sync_name . ';';
        }
        if ( ! empty( $cell['bg_color'] ) && in_array( $type, [ 'text', 'stat', 'testimonial' ], true ) ) {
            $style .= 'background-color:' . esc_attr( $cell['bg_color'] ) . ';';
        }

        echo '<' . tag_escape( $tag_name ) . ' class="' . esc_attr( implode( ' ', $classes ) ) . '" style="' . esc_attr( $style ) . '" role="listitem" data-bento-index="' . esc_attr( (string) $index ) . '"';
        if ( $sync_name ) {
            echo ' data-tl-sync="' . esc_attr( $sync_name ) . '"';
        }
        if ( $has_link && ! $has_cta ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in url_attrs().
            echo $this->url_attrs( $link );
        }
        echo '>';

        if ( 'testimonial' === $type ) {
            $this->render_testimonial( $cell );
        } elseif ( 'image' === $type ) {
            $img = $cell['image']['url'] ?? '';
            if ( $img ) {
                echo '<img class="rawnaq-bento-media" src="' . esc_url( $img ) . '" alt="" loading="lazy" decoding="async" />';
            } else {
                echo '<div class="rawnaq-bento-media" style="background:linear-gradient(135deg,#0f766e,#134e4a);" aria-hidden="true"></div>';
            }
            echo '<div class="rawnaq-bento-overlay" aria-hidden="true"></div>';
            echo '<div class="rawnaq-bento-body">';
            $this->render_tag( $cell );
            if ( $title ) {
                echo '<div class="rawnaq-bento-title">' . esc_html( $title ) . '</div>';
            }
            if ( $subtitle ) {
                echo '<div class="rawnaq-bento-sub">' . esc_html( $subtitle ) . '</div>';
            }
            $this->render_cta( $cell );
            echo '</div>';
        } elseif ( 'video' === $type ) {
            $vid = $cell['video_url']['url'] ?? '';
            if ( function_exists( 'rawnaq_bento_video_markup' ) ) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup helper escapes.
                echo rawnaq_bento_video_markup( $vid );
            } elseif ( $vid ) {
                echo '<video class="rawnaq-bento-video" muted playsinline loop preload="metadata" src="' . esc_url( $vid ) . '"></video>';
            } else {
                echo '<div class="rawnaq-bento-media" style="background:#1e1b2e;" aria-hidden="true"></div>';
            }
            echo '<div class="rawnaq-bento-overlay" aria-hidden="true"></div>';
            echo '<div class="rawnaq-bento-body">';
            $this->render_tag( $cell );
            if ( $title ) {
                echo '<div class="rawnaq-bento-title">' . esc_html( $title ) . '</div>';
            }
            if ( $subtitle ) {
                echo '<div class="rawnaq-bento-sub">' . esc_html( $subtitle ) . '</div>';
            }
            $this->render_cta( $cell );
            echo '</div>';
        } elseif ( 'stat' === $type ) {
            $val    = $cell['stat_value'] ?? '0';
            $suffix = $cell['stat_suffix'] ?? '';
            $prefix = $cell['stat_prefix'] ?? '';
            $num    = floatval( preg_replace( '/[^\d.]/', '', (string) $val ) );
            $this->render_tag( $cell );
            echo '<div class="rawnaq-bento-num" data-count="' . esc_attr( (string) $num ) . '" data-suffix="' . esc_attr( $suffix ) . '" data-prefix="' . esc_attr( $prefix ) . '">' . esc_html( $prefix . $val . $suffix ) . '</div>';
            if ( $subtitle ) {
                echo '<div class="rawnaq-bento-sub">' . esc_html( $subtitle ) . '</div>';
            }
            $this->render_cta( $cell );
        } else {
            $this->render_tag( $cell );
            $this->render_cell_icon( $cell );
            if ( $title ) {
                echo '<div class="rawnaq-bento-title">' . esc_html( $title ) . '</div>';
            }
            if ( $subtitle ) {
                echo '<div class="rawnaq-bento-sub">' . esc_html( $subtitle ) . '</div>';
            }
            $this->render_cta( $cell );
        }

        echo '</' . tag_escape( $tag_name ) . '>';
    }

    protected function render() {
        $s         = $this->get_settings_for_display();
        $cols      = $this->resolve_columns( $s );
        $reveal    = ( $s['reveal'] ?? 'yes' ) === 'yes';
        $hover     = sanitize_key( $s['hover_effect'] ?? 'lift' );
        $hairline  = ( $s['hairline'] ?? '' ) === 'yes';
        $cells     = $s['cells'] ?? [];
        $classes   = [ 'rawnaq-bento-grid' ];
        if ( $hairline ) {
            $classes[] = 'rawnaq-bento-hairline';
        }
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             data-cols="<?php echo esc_attr( (string) $cols ); ?>"
             data-reveal="<?php echo $reveal ? '1' : '0'; ?>"
             data-hover="<?php echo esc_attr( $hover ); ?>"
             role="list">
            <?php foreach ( $cells as $index => $cell ) : ?>
                <?php $this->render_cell( $cell, (int) $index ); ?>
            <?php endforeach; ?>
        </div>
        <?php
        // JSON-LD Review schema from testimonial cells.
        if ( ! $this->is_editor_preview() && function_exists( 'rawnaq_schema_print' ) && function_exists( 'rawnaq_schema_reviews' ) ) {
            $testimonials = [];
            foreach ( $cells as $cell ) {
                if ( ( $cell['cell_type'] ?? '' ) === 'testimonial' && ! empty( $cell['subtitle'] ) ) {
                    $testimonials[] = [
                        'body'   => $cell['subtitle'],
                        'author' => $cell['title'] ?? '',
                        'rating' => max( 0, min( 5, absint( $cell['testimonial_rating'] ?? 0 ) ) ),
                    ];
                }
            }
            if ( $testimonials ) {
                rawnaq_schema_print( rawnaq_schema_reviews( $testimonials ), 'review' );
            }
        }
    }

    /**
     * Whether we are rendering inside the Elementor editor canvas.
     *
     * @return bool
     */
    private function is_editor_preview() {
        return class_exists( '\Elementor\Plugin' )
            && \Elementor\Plugin::$instance
            && isset( \Elementor\Plugin::$instance->editor )
            && \Elementor\Plugin::$instance->editor->is_edit_mode();
    }

    protected function content_template() {
        ?>
        <#
        var preset = settings.preset || 'featured';
        var cols = 4;
        if ( preset === 'wide' ) { cols = 3; }
        else if ( preset === 'custom' ) { cols = Math.max(2, Math.min(6, parseInt(settings.columns, 10) || 4)); }
        var reveal = settings.reveal === 'yes';
        var hover = settings.hover_effect || 'lift';
        var hair = settings.hairline === 'yes' ? ' rawnaq-bento-hairline' : '';
        #>
        <div class="rawnaq-bento-grid{{ hair }}"
             data-cols="{{ cols }}"
             data-reveal="{{ reveal ? '1' : '0' }}"
             data-hover="{{ hover }}"
             role="list">
            <# _.each( settings.cells || [], function( cell ) {
                var type = cell.cell_type || 'text';
                var col = Math.max(1, parseInt(cell.col_span, 10) || 1);
                var row = Math.max(1, parseInt(cell.row_span, 10) || 1);
                var order = parseInt(cell.order_desktop, 10) || 0;
                var colMd = parseInt(cell.col_span_tablet, 10) || 0;
                var rowMd = parseInt(cell.row_span_tablet, 10) || 0;
                var orderMd = parseInt(cell.order_tablet, 10) || 0;
                var colSm = parseInt(cell.col_span_mobile, 10) || 0;
                var rowSm = parseInt(cell.row_span_mobile, 10) || 0;
                var orderSm = parseInt(cell.order_mobile, 10) || 0;
                var cls = 'rawnaq-bento-cell';
                if ( type === 'featured' ) cls += ' is-featured';
                if ( type === 'image' ) cls += ' is-image';
                if ( type === 'stat' ) cls += ' is-stat';
                if ( type === 'video' ) cls += ' is-video';
                if ( type === 'testimonial' ) cls += ' is-testimonial';
                var contentAlign = ( cell.content_align || '' ).toString();
                if ( contentAlign === 'top' || contentAlign === 'center' || contentAlign === 'bottom' ) {
                    cls += ' is-align-' + contentAlign;
                }
                var syncTl = ( cell.sync_timeline || '' ).toString().replace(/[^a-zA-Z0-9_-]/g, '');
                if ( syncTl && /^[0-9]/.test( syncTl ) ) { syncTl = 'tl-' + syncTl; }
                if ( syncTl ) { cls += ' tl-sync'; }
                var vidUrl = ( cell.video_url && cell.video_url.url ) ? cell.video_url.url : '';
                var ytMatch = vidUrl.match(/(?:youtube\.com\/(?:watch\?(?:[^#]*&)?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/i);
                var vimMatch = vidUrl.match(/(?:player\.)?vimeo\.com\/(?:video\/)?(\d+)/i);
                var embedSrc = '';
                if ( ytMatch ) {
                    embedSrc = 'https://www.youtube-nocookie.com/embed/' + ytMatch[1] + '?rel=0&modestbranding=1';
                    cls += ' is-embed';
                } else if ( vimMatch ) {
                    embedSrc = 'https://player.vimeo.com/video/' + vimMatch[1] + '?title=0&byline=0&portrait=0';
                    cls += ' is-embed';
                }
                if ( colSm > 0 ) cls += ' has-sm-span';
                var style = 'grid-column:span ' + col + ';grid-row:span ' + row + ';--bento-span-col:' + col + ';--bento-span-row:' + row + ';';
                if ( syncTl ) { style += 'animation-timeline:--' + syncTl + ';'; }
                if ( order !== 0 ) { style += 'order:' + order + ';--bento-order:' + order + ';'; }
                if ( colMd > 0 ) { style += '--bento-span-col-md:' + colMd + ';'; }
                if ( rowMd > 0 ) { style += '--bento-span-row-md:' + rowMd + ';'; }
                if ( orderMd !== 0 ) { style += '--bento-order-md:' + orderMd + ';'; }
                if ( colSm > 0 ) { style += '--bento-span-col-sm:' + colSm + ';'; }
                if ( rowSm > 0 ) { style += '--bento-span-row-sm:' + rowSm + ';'; }
                if ( orderSm !== 0 ) { style += '--bento-order-sm:' + orderSm + ';'; }
                if ( cell.bg_color && ( type === 'text' || type === 'stat' || type === 'testimonial' ) ) {
                    style += 'background-color:' + cell.bg_color + ';';
                }
                var iconHTML = elementor.helpers.renderIcon( view, cell.selected_icon, { 'aria-hidden': true }, 'i', 'object' );
                var ctaText = ( cell.cta_text || '' ).toString().trim();
                var ctaUrl = ( cell.cta_link && cell.cta_link.url ) ? cell.cta_link.url : ( ( cell.link && cell.link.url ) ? cell.link.url : '' );
                var tagCls = 'rawnaq-bento-tag';
                var tagStyle = '';
                if ( cell.cell_tag_bg || cell.cell_tag_color ) {
                    tagCls += ' has-custom';
                    if ( cell.cell_tag_bg ) { tagStyle += 'background:' + cell.cell_tag_bg + ';--bento-tag-cell-bg:' + cell.cell_tag_bg + ';'; }
                    if ( cell.cell_tag_color ) { tagStyle += 'color:' + cell.cell_tag_color + ';--bento-tag-cell-color:' + cell.cell_tag_color + ';'; }
                }
                var rating = Math.max(0, Math.min(5, parseInt(cell.testimonial_rating, 10) || 0));
                var avatar = ( cell.testimonial_avatar && cell.testimonial_avatar.url ) ? cell.testimonial_avatar.url : '';
                var authorInitial = ( cell.title || '' ).toString().charAt(0).toUpperCase();
                var stars = '';
                for ( var si = 0; si < rating; si++ ) { stars += '★'; }
            #>
                <div class="{{ cls }} is-in" style="{{ style }}"<# if ( syncTl ) { #> data-tl-sync="{{ syncTl }}"<# } #>>
                    <# if ( type === 'testimonial' ) { #>
                        <# if ( cell.tag ) { #><div class="{{ tagCls }}" style="{{ tagStyle }}">{{{ cell.tag }}}</div><# } #>
                        <# if ( cell.subtitle ) { #><blockquote class="rawnaq-bento-quote">{{{ cell.subtitle }}}</blockquote><# } #>
                        <# if ( rating > 0 ) { #><div class="rawnaq-bento-stars">{{{ stars }}}</div><# } #>
                        <# if ( cell.title || cell.testimonial_role || avatar ) { #>
                            <div class="rawnaq-bento-author">
                                <# if ( avatar ) { #>
                                    <img class="rawnaq-bento-avatar" src="{{ avatar }}" alt="" />
                                <# } else if ( cell.title ) { #>
                                    <div class="rawnaq-bento-avatar is-placeholder">{{ authorInitial }}</div>
                                <# } #>
                                <# if ( cell.title || cell.testimonial_role ) { #>
                                    <div class="rawnaq-bento-author-meta">
                                        <# if ( cell.title ) { #><div class="rawnaq-bento-author-name">{{{ cell.title }}}</div><# } #>
                                        <# if ( cell.testimonial_role ) { #><div class="rawnaq-bento-author-role">{{{ cell.testimonial_role }}}</div><# } #>
                                    </div>
                                <# } #>
                            </div>
                        <# } #>
                        <# if ( ctaText ) { #>
                            <# if ( ctaUrl ) { #><a class="rawnaq-bento-cta" href="{{ ctaUrl }}">{{{ ctaText }}}</a><# } else { #><span class="rawnaq-bento-cta is-static">{{{ ctaText }}}</span><# } #>
                        <# } #>
                    <# } else if ( type === 'image' ) {
                        var img = ( cell.image && cell.image.url ) ? cell.image.url : '';
                    #>
                        <# if ( img ) { #>
                            <img class="rawnaq-bento-media" src="{{ img }}" alt="" />
                        <# } else { #>
                            <div class="rawnaq-bento-media" style="background:linear-gradient(135deg,#0f766e,#134e4a);"></div>
                        <# } #>
                        <div class="rawnaq-bento-overlay"></div>
                        <div class="rawnaq-bento-body">
                            <# if ( cell.tag ) { #><div class="{{ tagCls }}" style="{{ tagStyle }}">{{{ cell.tag }}}</div><# } #>
                            <# if ( cell.title ) { #><div class="rawnaq-bento-title">{{{ cell.title }}}</div><# } #>
                            <# if ( cell.subtitle ) { #><div class="rawnaq-bento-sub">{{{ cell.subtitle }}}</div><# } #>
                            <# if ( ctaText ) { #>
                                <# if ( ctaUrl ) { #><a class="rawnaq-bento-cta" href="{{ ctaUrl }}">{{{ ctaText }}}</a><# } else { #><span class="rawnaq-bento-cta is-static">{{{ ctaText }}}</span><# } #>
                            <# } #>
                        </div>
                    <# } else if ( type === 'video' ) { #>
                        <# if ( embedSrc ) { #>
                            <iframe class="rawnaq-bento-embed" src="{{ embedSrc }}" title="Video" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                        <# } else if ( vidUrl ) { #>
                            <video class="rawnaq-bento-video" muted playsinline loop preload="metadata" src="{{ vidUrl }}"></video>
                        <# } else { #>
                            <div class="rawnaq-bento-media" style="background:#1e1b2e;"></div>
                        <# } #>
                        <div class="rawnaq-bento-overlay"></div>
                        <div class="rawnaq-bento-body">
                            <# if ( cell.tag ) { #><div class="{{ tagCls }}" style="{{ tagStyle }}">{{{ cell.tag }}}</div><# } #>
                            <# if ( cell.title ) { #><div class="rawnaq-bento-title">{{{ cell.title }}}</div><# } #>
                            <# if ( cell.subtitle ) { #><div class="rawnaq-bento-sub">{{{ cell.subtitle }}}</div><# } #>
                            <# if ( ctaText ) { #>
                                <# if ( ctaUrl ) { #><a class="rawnaq-bento-cta" href="{{ ctaUrl }}">{{{ ctaText }}}</a><# } else { #><span class="rawnaq-bento-cta is-static">{{{ ctaText }}}</span><# } #>
                            <# } #>
                        </div>
                    <# } else if ( type === 'stat' ) { #>
                        <# if ( cell.tag ) { #><div class="{{ tagCls }}" style="{{ tagStyle }}">{{{ cell.tag }}}</div><# } #>
                        <div class="rawnaq-bento-num">{{{ (cell.stat_prefix||'') + (cell.stat_value||'0') + (cell.stat_suffix||'') }}}</div>
                        <# if ( cell.subtitle ) { #><div class="rawnaq-bento-sub">{{{ cell.subtitle }}}</div><# } #>
                        <# if ( ctaText ) { #>
                            <# if ( ctaUrl ) { #><a class="rawnaq-bento-cta" href="{{ ctaUrl }}">{{{ ctaText }}}</a><# } else { #><span class="rawnaq-bento-cta is-static">{{{ ctaText }}}</span><# } #>
                        <# } #>
                    <# } else { #>
                        <# if ( cell.tag ) { #><div class="{{ tagCls }}" style="{{ tagStyle }}">{{{ cell.tag }}}</div><# } #>
                        <# if ( iconHTML && iconHTML.rendered ) { #>
                            <div class="rawnaq-bento-icon">{{{ iconHTML.value }}}</div>
                        <# } #>
                        <# if ( cell.title ) { #><div class="rawnaq-bento-title">{{{ cell.title }}}</div><# } #>
                        <# if ( cell.subtitle ) { #><div class="rawnaq-bento-sub">{{{ cell.subtitle }}}</div><# } #>
                        <# if ( ctaText ) { #>
                            <# if ( ctaUrl ) { #><a class="rawnaq-bento-cta" href="{{ ctaUrl }}">{{{ ctaText }}}</a><# } else { #><span class="rawnaq-bento-cta is-static">{{{ ctaText }}}</span><# } #>
                        <# } #>
                    <# } #>
                </div>
            <# }); #>
        </div>
        <?php
    }
}
