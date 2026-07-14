<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rawnaq_Hub_Diagram_Widget extends \Elementor\Widget_Base {

    public function get_name()       { return 'rawnaq_hub_diagram'; }
    public function get_title()      { return esc_html__( 'Hub Diagram', 'rawnaq' ); }
    public function get_icon()       { return 'eicon-flow'; }
    public function get_categories() { return [ 'rawnaq' ]; }

    public function get_style_depends()  { return [ 'rawnaq-hub-diagram', 'rawnaq-fonts', 'dashicons' ]; }
    public function get_script_depends() { return [ 'rawnaq-hub-diagram' ]; }

    protected function register_controls() {
        // Content Tab - Center Node
        $this->start_controls_section( 's_center', [
            'label' => esc_html__( 'Center Circle', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );
        $this->add_control( 'center_title', [
            'label'       => esc_html__( 'Title', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'STUDY 2D &amp; 3D',
            'label_block' => true,
        ] );
        $this->add_control( 'center_sub', [
            'label'   => esc_html__( 'Subtitle', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::TEXTAREA,
            'default' => "REVIEW WITH\nCLIENT",
            'rows'    => 2,
        ] );
        $this->add_control( 'import_json', [
            'label'       => esc_html__( 'JSON Config Import/Export Override', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXTAREA,
            'placeholder' => '[{"label":"Step 1","color":"#E8793A"}]',
            'description' => esc_html__( 'Paste a JSON array of node settings here to override the repeaters below. Great for backup & transfer!', 'rawnaq' ),
        ] );
        $this->end_controls_section();

        // Repeater Settings Helper
        $setup_repeater = function() {
            $r = new \Elementor\Repeater();
            $r->add_control( 'label', [
                'label'       => esc_html__( 'Label', 'rawnaq' ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'default'     => 'Node Name',
                'label_block' => true,
            ] );
            $r->add_control( 'bar_color', [
                'label'   => esc_html__( 'Accent Color', 'rawnaq' ),
                'type'    => \Elementor\Controls_Manager::COLOR,
                'default' => '#E8793A',
            ] );
            $r->add_control( 'card_bg', [
                'label'   => esc_html__( 'Card Background', 'rawnaq' ),
                'type'    => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
            ] );
            $r->add_control( 'card_color', [
                'label'   => esc_html__( 'Card Text Color', 'rawnaq' ),
                'type'    => \Elementor\Controls_Manager::COLOR,
                'default' => '#1a1a1a',
            ] );
            $r->add_control( 'icon', [
                'label'       => esc_html__( 'Dashicon Class', 'rawnaq' ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'placeholder' => 'dashicons-admin-generic',
                'description' => esc_html__( 'Find names at developer.wordpress.org/resource/dashicons/', 'rawnaq' ),
            ] );
            $r->add_control( 'link', [
                'label'       => esc_html__( 'Redirect URL', 'rawnaq' ),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'placeholder' => 'https://example.com',
            ] );
            $r->add_control( 'target', [
                'label'   => esc_html__( 'Link Target', 'rawnaq' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => '_self',
                'options' => [
                    '_self'  => esc_html__( 'Same Window', 'rawnaq' ),
                    '_blank' => esc_html__( 'New Tab', 'rawnaq' ),
                ],
            ] );
            return $r;
        };

        // Content Tab - Top Nodes
        $this->start_controls_section( 's_top', [
            'label' => esc_html__( 'Top Row / Left Col Nodes', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );
        $this->add_control( 'top_nodes', [
            'label'       => esc_html__( 'Nodes', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::REPEATER,
            'fields'      => $setup_repeater()->get_controls(),
            'default'     => [
                [ 'label' => 'Design',        'bar_color' => '#E8793A', 'icon' => 'dashicons-art' ],
                [ 'label' => 'P&amp;ID',      'bar_color' => '#D4A92A', 'icon' => 'dashicons-editor-justify' ],
                [ 'label' => 'Sketch',        'bar_color' => '#26B8B8', 'icon' => 'dashicons-welcome-write-blog' ],
                [ 'label' => 'Specification', 'bar_color' => '#E8793A', 'icon' => 'dashicons-clipboard' ],
            ],
            'title_field' => '{{{ label }}}',
        ] );
        $this->end_controls_section();

        // Content Tab - Bottom Nodes
        $this->start_controls_section( 's_bottom', [
            'label' => esc_html__( 'Bottom Row / Right Col Nodes', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );
        $this->add_control( 'bottom_nodes', [
            'label'       => esc_html__( 'Nodes', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::REPEATER,
            'fields'      => $setup_repeater()->get_controls(),
            'default'     => [
                [ 'label' => 'MTO/BOQ',        'bar_color' => '#E8793A', 'icon' => 'dashicons-list-view' ],
                [ 'label' => '3D CAD Model',   'bar_color' => '#D4A92A', 'icon' => 'dashicons-format-image' ],
                [ 'label' => 'Drawings',       'bar_color' => '#26B8B8', 'icon' => 'dashicons-portfolio' ],
                [ 'label' => 'Pipe Isometric',  'bar_color' => '#E8793A', 'icon' => 'dashicons-chart-area' ],
            ],
            'title_field' => '{{{ label }}}',
        ] );
        $this->end_controls_section();

        // Style Tab
        $this->start_controls_section( 's_style', [
            'label' => esc_html__( 'Style &amp; Colors', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );
        $this->add_responsive_control( 'height', [
            'label'      => esc_html__( 'Diagram Height', 'rawnaq' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px', 'vh' ],
            'range'      => [
                'px' => [ 'min' => 300, 'max' => 900, 'step' => 10 ],
            ],
            'default'    => [ 'unit' => 'px', 'size' => 540 ],
            'selectors'  => [ '{{WRAPPER}} .hub-diagram-host' => 'height: {{SIZE}}{{UNIT}};' ],
        ] );

        // LAYOUT & SHAPES OPTIONS
        $this->add_control( 'layout_flow', [
            'label'   => esc_html__( 'Layout Alignment Flow', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'horizontal',
            'options' => [
                'horizontal' => esc_html__( 'Horizontal (Top/Bottom Row)', 'rawnaq' ),
                'vertical'   => esc_html__( 'Vertical (Left/Right Column)', 'rawnaq' ),
                'radial'     => esc_html__( 'Radial (360° Circular)', 'rawnaq' ),
            ],
        ] );
        $this->add_control( 'card_shape', [
            'label'   => esc_html__( 'Card Shape Style', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'rect',
            'options' => [
                'rect'    => esc_html__( 'Rectangle Box', 'rawnaq' ),
                'pill'    => esc_html__( 'Rounded Pill', 'rawnaq' ),
                'outline' => esc_html__( 'Minimal Outline', 'rawnaq' ),
            ],
        ] );
        $this->add_control( 'line_style', [
            'label'   => esc_html__( 'Connector Line Type', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'solid',
            'options' => [
                'solid'  => esc_html__( 'Solid Line', 'rawnaq' ),
                'dashed' => esc_html__( 'Dashed Line', 'rawnaq' ),
                'dotted' => esc_html__( 'Dotted Line', 'rawnaq' ),
            ],
        ] );
        $this->add_control( 'glow_lines', [
            'label'   => esc_html__( 'Animated Glow Flow Lines', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'no',
            'options' => [
                'no'  => esc_html__( 'Disable', 'rawnaq' ),
                'yes' => esc_html__( 'Enable', 'rawnaq' ),
            ],
        ] );
        $this->add_control( 'center_style', [
            'label'   => esc_html__( 'Center Circle Ring Style', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'conic',
            'options' => [
                'conic' => esc_html__( 'Conic Color Ring', 'rawnaq' ),
                'solid' => esc_html__( 'Solid Flat Ring', 'rawnaq' ),
            ],
        ] );

        $this->add_control( 'line_color', [
            'label'   => esc_html__( 'Line Color', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::COLOR,
            'default' => '#c2c2c2',
        ] );
        
        $this->add_control( 's_center_ring', [
            'label'     => esc_html__( 'Center Ring Color segments', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ] );
        $this->add_control( 'seg1_color', [
            'label'   => esc_html__( 'Segment 1 / Solid Ring Color', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::COLOR,
            'default' => '#E8793A',
        ] );
        $this->add_control( 'seg2_color', [
            'label'   => esc_html__( 'Segment 2 Color', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::COLOR,
            'default' => '#D4A92A',
        ] );
        $this->add_control( 'seg3_color', [
            'label'   => esc_html__( 'Segment 3 Color', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::COLOR,
            'default' => '#26B8B8',
        ] );
        $this->end_controls_section();
    }

    protected function render() {
        $s   = $this->get_settings_for_display();
        $id  = $this->get_id();

        $map = function ( array $nodes ) {
            $out = [];
            foreach ( $nodes as $i => $n ) {
                $out[] = [
                    'id'        => 'n' . $i,
                    'label'     => $n['label'] ?? '',
                    'color'     => ( $n['bar_color'] ?: '#E8793A' ),
                    'cardBg'    => $n['card_bg'] ?? '#ffffff',
                    'cardColor' => $n['card_color'] ?? '#1a1a1a',
                    'icon'      => $n['icon'] ?? '',
                    'link'      => $n['link'] ?? '',
                    'target'    => $n['target'] ?? '_self',
                ];
            }
            return $out;
        };

        $cfg = [
            'centerTitle'    => $s['center_title'] ?? 'STUDY 2D & 3D',
            'centerSubtitle' => $s['center_sub']   ?? "REVIEW WITH\nCLIENT",
            'lineColor'      => $s['line_color']   ?? '#c2c2c2',
            'seg1Color'      => $s['seg1_color']   ?? '#E8793A',
            'seg2Color'      => $s['seg2_color']   ?? '#D4A92A',
            'seg3Color'      => $s['seg3_color']   ?? '#26B8B8',
            'cardShape'      => $s['card_shape']   ?? 'rect',
            'lineStyle'      => $s['line_style']   ?? 'solid',
            'glowLines'      => $s['glow_lines']   ?? 'no',
            'centerStyle'    => $s['center_style'] ?? 'conic',
            'layoutFlow'     => $s['layout_flow']  ?? 'horizontal',
            'importJson'     => $s['import_json']  ?? '',
            'top'            => $map( $s['top_nodes']    ?? [] ),
            'bottom'         => $map( $s['bottom_nodes'] ?? [] ),
        ];
        ?>
        <div class="hub-diagram-host"
             id="hub-<?php echo esc_attr( $id ); ?>"
             data-hub="<?php echo esc_attr( wp_json_encode( $cfg ) ); ?>">
        </div>
        <?php
    }

    protected function content_template() {
        ?>
        <#
        var topNodes = settings.top_nodes    || [];
        var botNodes = settings.bottom_nodes || [];

        var map = function(nodes, prefix) {
            return nodes.map(function(n, i) {
                return {
                    id: prefix + i,
                    label: n.label || '',
                    color: n.bar_color || '#E8793A',
                    cardBg: n.card_bg || '#ffffff',
                    cardColor: n.card_color || '#1a1a1a',
                    icon: n.icon || '',
                    link: n.link || '',
                    target: n.target || '_self'
                };
            });
        };

        var cfg = {
            centerTitle:    settings.center_title || 'STUDY 2D & 3D',
            centerSubtitle: settings.center_sub   || 'REVIEW WITH\nCLIENT',
            lineColor:      settings.line_color   || '#c2c2c2',
            seg1Color:      settings.seg1_color   || '#E8793A',
            seg2Color:      settings.seg2_color   || '#D4A92A',
            seg3Color:      settings.seg3_color   || '#26B8B8',
            cardShape:      settings.card_shape   || 'rect',
            lineStyle:      settings.line_style   || 'solid',
            glowLines:      settings.glow_lines   || 'no',
            centerStyle:    settings.center_style || 'conic',
            layoutFlow:     settings.layout_flow  || 'horizontal',
            importJson:     settings.import_json  || '',
            top:            map(topNodes, 't'),
            bottom:         map(botNodes, 'b')
        };
        #>
        <div class="hub-diagram-host"
             data-hub="{{ JSON.stringify(cfg) }}">
        </div>
        <?php
    }
}
