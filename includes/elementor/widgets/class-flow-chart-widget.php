<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rawnaq_Flow_Chart_Widget extends \Elementor\Widget_Base {

    public function get_name()       { return 'rawnaq_flow_chart'; }
    public function get_title()      { return esc_html__( 'Flow Chart', 'rawnaq' ); }
    public function get_icon()       { return 'eicon-sitemap'; }
    public function get_categories() { return [ 'rawnaq' ]; }

    public function get_style_depends()  { return [ 'rawnaq-flow-chart', 'dashicons' ]; }
    public function get_script_depends() { return [ 'rawnaq-flow-chart' ]; }

    protected function register_controls() {
        $this->start_controls_section( 's_layout', [
            'label' => esc_html__( 'Layout', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'mode', [
            'label'   => esc_html__( 'Layout Mode', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'org',
            'options' => [
                'org'      => esc_html__( 'Org (vertical tree)', 'rawnaq' ),
                'process'  => esc_html__( 'Process (flow + branches)', 'rawnaq' ),
                'freeform' => esc_html__( 'Freeform (manual X/Y)', 'rawnaq' ),
            ],
        ] );

        $this->add_control( 'direction', [
            'label'     => esc_html__( 'Direction', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'default'   => 'tb',
            'options'   => [
                'tb' => esc_html__( 'Top to bottom', 'rawnaq' ),
                'lr' => esc_html__( 'Left to right', 'rawnaq' ),
                'rl' => esc_html__( 'Right to left', 'rawnaq' ),
            ],
            'condition' => [ 'mode!' => 'freeform' ],
        ] );

        $this->add_control( 'shape', [
            'label'   => esc_html__( 'Node Shape', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'rect',
            'options' => [
                'rect'   => esc_html__( 'Rounded rectangle', 'rawnaq' ),
                'circle' => esc_html__( 'Circle', 'rawnaq' ),
                'hex'    => esc_html__( 'Hexagon', 'rawnaq' ),
            ],
        ] );

        $this->add_control( 'connector', [
            'label'   => esc_html__( 'Connector Style', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'curved',
            'options' => [
                'curved'   => esc_html__( 'Curved', 'rawnaq' ),
                'elbow'    => esc_html__( 'Elbow (90°)', 'rawnaq' ),
                'straight' => esc_html__( 'Straight', 'rawnaq' ),
                'dashed'   => esc_html__( 'Dashed flow', 'rawnaq' ),
            ],
        ] );

        $this->add_control( 'enable_zoom', [
            'label'        => esc_html__( 'Zoom / Pan Controls', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ] );

        $this->add_control( 'show_export', [
            'label'        => esc_html__( 'PNG / SVG Export', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'description'  => esc_html__( 'Show download buttons for proposal-ready PNG or SVG.', 'rawnaq' ),
        ] );

        $this->add_control( 'data_source', [
            'label'   => esc_html__( 'Nodes Source', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'manual',
            'options' => [
                'manual'   => esc_html__( 'Manual nodes', 'rawnaq' ),
                'wp_users' => esc_html__( 'WordPress users (org chart)', 'rawnaq' ),
            ],
            'description' => esc_html__( 'Users mode builds an org tree. Optional user meta key: rawnaq_reports_to (manager user ID).', 'rawnaq' ),
        ] );

        $this->add_control( 'users_role', [
            'label'     => esc_html__( 'Filter by Role', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '',
            'placeholder' => 'administrator, editor…',
            'condition' => [ 'data_source' => 'wp_users' ],
        ] );

        $this->add_control( 'users_number', [
            'label'     => esc_html__( 'Max Users', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::NUMBER,
            'default'   => 20,
            'min'       => 1,
            'max'       => 50,
            'condition' => [ 'data_source' => 'wp_users' ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 's_nodes', [
            'label'     => esc_html__( 'Nodes', 'rawnaq' ),
            'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
            'condition' => [ 'data_source' => 'manual' ],
        ] );

        $r = new \Elementor\Repeater();

        $r->add_control( 'node_id', [
            'label'       => esc_html__( 'Node ID', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'node-1',
            'description' => esc_html__( 'Unique key used as parent reference.', 'rawnaq' ),
        ] );

        $r->add_control( 'parent_id', [
            'label'       => esc_html__( 'Parent Node', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::SELECT,
            'default'     => '',
            'options'     => [
                ''    => esc_html__( '— Root (no parent) —', 'rawnaq' ),
                'ceo' => 'ceo — Founder / CEO',
                'eng' => 'eng — Engineering Head',
                'ops' => 'ops — Operations Head',
                'biz' => 'biz — Business Dev',
                'e1'  => 'e1 — Frontend Team',
                'e2'  => 'e2 — Backend Team',
            ],
            'description' => esc_html__( 'Choose the parent’s Node ID. The dropdown refreshes from current rows when you edit Node IDs (Elementor editor). Cycles are auto-cleared.', 'rawnaq' ),
            'label_block' => true,
        ] );

        $r->add_control( 'title', [
            'label'       => esc_html__( 'Title', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'Node Title',
            'label_block' => true,
        ] );

        $r->add_control( 'role', [
            'label'   => esc_html__( 'Subtitle / Role', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ] );

        $r->add_control( 'edge_label', [
            'label'       => esc_html__( 'Connector Label', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => esc_html__( 'e.g. Yes / No / Approved', 'rawnaq' ),
            'description' => esc_html__( 'Text shown on the line coming from this node’s parent.', 'rawnaq' ),
        ] );

        $r->add_control( 'lane', [
            'label'       => esc_html__( 'Swimlane', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => esc_html__( 'e.g. Sales / Ops', 'rawnaq' ),
            'description' => esc_html__( 'Group nodes into a labelled band. Nodes with the same lane name share a band.', 'rawnaq' ),
        ] );

        $r->add_control( 'selected_icon', [
            'label'   => esc_html__( 'Icon', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::ICONS,
            'default' => [
                'value'   => 'fas fa-circle',
                'library' => 'fa-solid',
            ],
        ] );

        $r->add_control( 'icon', [
            'label'   => esc_html__( 'Legacy Icon (emoji / dashicons-*)', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::HIDDEN,
            'default' => '●',
        ] );

        $r->add_control( 'image', [
            'label'       => esc_html__( 'Image', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::MEDIA,
            'default'     => [ 'url' => '' ],
            'description' => esc_html__( 'Optional photo. When set, it replaces the icon badge and stays cropped to the avatar box.', 'rawnaq' ),
        ] );

        $r->add_control( 'detail', [
            'label'   => esc_html__( 'Detail (popover)', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::TEXTAREA,
            'default' => '',
            'rows'    => 3,
        ] );

        $r->add_control( 'link', [
            'label'         => esc_html__( 'Link', 'rawnaq' ),
            'type'          => \Elementor\Controls_Manager::URL,
            'placeholder'   => 'https://',
            'show_external' => true,
            'default'       => [ 'url' => '' ],
        ] );

        $r->add_control( 'is_decision', [
            'label'        => esc_html__( 'Decision / Branch Node', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ] );

        $r->add_control( 'pos_x', [
            'label'     => esc_html__( 'Freeform X (%)', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::NUMBER,
            'default'   => 10,
            'min'       => 0,
            'max'       => 100,
        ] );

        $r->add_control( 'pos_y', [
            'label'     => esc_html__( 'Freeform Y (%)', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::NUMBER,
            'default'   => 10,
            'min'       => 0,
            'max'       => 100,
        ] );

        $this->add_control( 'nodes', [
            'label'       => esc_html__( 'Nodes', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::REPEATER,
            'fields'      => $r->get_controls(),
            'title_field' => '{{{ title }}} ({{{ node_id }}})',
            'default'     => [
                [ 'node_id' => 'ceo', 'parent_id' => '', 'title' => 'Founder / CEO', 'role' => 'Leadership', 'selected_icon' => [ 'value' => 'fas fa-star', 'library' => 'fa-solid' ], 'icon' => '★', 'detail' => 'Leads the company and client relationships.', 'is_decision' => '', 'pos_x' => 40, 'pos_y' => 5 ],
                [ 'node_id' => 'eng', 'parent_id' => 'ceo', 'title' => 'Engineering Head', 'role' => 'Product', 'selected_icon' => [ 'value' => 'fas fa-cogs', 'library' => 'fa-solid' ], 'icon' => '⚙', 'detail' => 'Owns engineering roadmap.', 'is_decision' => '', 'pos_x' => 15, 'pos_y' => 40 ],
                [ 'node_id' => 'ops', 'parent_id' => 'ceo', 'title' => 'Operations Head', 'role' => 'Delivery', 'selected_icon' => [ 'value' => 'fas fa-tasks', 'library' => 'fa-solid' ], 'icon' => '◆', 'detail' => 'Coordinates project delivery.', 'is_decision' => '', 'pos_x' => 40, 'pos_y' => 40 ],
                [ 'node_id' => 'biz', 'parent_id' => 'ceo', 'title' => 'Business Dev', 'role' => 'Growth', 'selected_icon' => [ 'value' => 'fas fa-chart-line', 'library' => 'fa-solid' ], 'icon' => '▲', 'detail' => 'Client acquisition.', 'is_decision' => '', 'pos_x' => 65, 'pos_y' => 40 ],
                [ 'node_id' => 'e1', 'parent_id' => 'eng', 'title' => 'Frontend Team', 'role' => 'Team, 4', 'selected_icon' => [ 'value' => 'fas fa-laptop-code', 'library' => 'fa-solid' ], 'icon' => '▪', 'detail' => 'UI implementation.', 'is_decision' => '', 'pos_x' => 5, 'pos_y' => 75 ],
                [ 'node_id' => 'e2', 'parent_id' => 'eng', 'title' => 'Backend Team', 'role' => 'Team, 3', 'selected_icon' => [ 'value' => 'fas fa-server', 'library' => 'fa-solid' ], 'icon' => '▪', 'detail' => 'API & infrastructure.', 'is_decision' => '', 'pos_x' => 25, 'pos_y' => 75 ],
            ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 's_style', [
            'label' => esc_html__( 'Style', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'accent', [
            'label'     => esc_html__( 'Accent / Connector Highlight', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#FBBF24',
            'selectors' => [ '{{WRAPPER}} .rawnaq-flow-chart' => '--fc-amber: {{VALUE}};' ],
        ] );

        $this->add_control( 'root_from', [
            'label'     => esc_html__( 'Root Gradient From', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#4338CA',
            'selectors' => [ '{{WRAPPER}} .rawnaq-flow-chart' => '--fc-indigo: {{VALUE}};' ],
        ] );

        $this->add_control( 'root_to', [
            'label'     => esc_html__( 'Root Gradient To', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#7C3AED',
            'selectors' => [ '{{WRAPPER}} .rawnaq-flow-chart' => '--fc-violet: {{VALUE}};' ],
        ] );

        $this->add_control( 'line_color', [
            'label'     => esc_html__( 'Connector Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#E6E2F0',
            'selectors' => [ '{{WRAPPER}} .rawnaq-flow-chart' => '--fc-line: {{VALUE}};' ],
        ] );

        $this->add_control( 'avatar_heading', [
            'label'     => esc_html__( 'Node Avatar (Icon / Image)', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_control( 'avatar_shape', [
            'label'   => esc_html__( 'Avatar Shape', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'rounded',
            'options' => [
                'rounded' => esc_html__( 'Rounded square', 'rawnaq' ),
                'circle'  => esc_html__( 'Circle', 'rawnaq' ),
                'square'  => esc_html__( 'Square', 'rawnaq' ),
            ],
        ] );

        $this->add_control( 'avatar_size', [
            'label'      => esc_html__( 'Avatar Size', 'rawnaq' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => 24, 'max' => 96, 'step' => 1 ],
            ],
            'default'    => [ 'size' => 30, 'unit' => 'px' ],
            'selectors'  => [
                '{{WRAPPER}} .rawnaq-flow-chart' => '--fc-avatar: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'avatar_gap', [
            'label'      => esc_html__( 'Avatar Spacing', 'rawnaq' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 24, 'step' => 1 ],
            ],
            'default'    => [ 'size' => 8, 'unit' => 'px' ],
            'selectors'  => [
                '{{WRAPPER}} .rawnaq-flow-chart' => '--fc-avatar-gap: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'avatar_bg', [
            'label'     => esc_html__( 'Avatar Background', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#FEF3C7',
            'selectors' => [
                '{{WRAPPER}} .rawnaq-flow-chart' => '--fc-avatar-bg: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'avatar_icon_color', [
            'label'     => esc_html__( 'Icon Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#92400E',
            'selectors' => [
                '{{WRAPPER}} .rawnaq-flow-chart' => '--fc-avatar-icon: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'avatar_icon_size', [
            'label'      => esc_html__( 'Icon Size', 'rawnaq' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => 10, 'max' => 48, 'step' => 1 ],
            ],
            'default'    => [ 'size' => 14, 'unit' => 'px' ],
            'selectors'  => [
                '{{WRAPPER}} .rawnaq-flow-chart' => '--fc-avatar-icon-size: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'avatar_border_color', [
            'label'     => esc_html__( 'Avatar Border Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .rawnaq-flow-chart' => '--fc-avatar-border: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'avatar_border_width', [
            'label'      => esc_html__( 'Avatar Border Width', 'rawnaq' ),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 8, 'step' => 1 ],
            ],
            'default'    => [ 'size' => 0, 'unit' => 'px' ],
            'selectors'  => [
                '{{WRAPPER}} .rawnaq-flow-chart' => '--fc-avatar-border-w: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'avatar_object_fit', [
            'label'   => esc_html__( 'Image Fit', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'cover',
            'options' => [
                'cover'   => esc_html__( 'Cover (crop)', 'rawnaq' ),
                'contain' => esc_html__( 'Contain (full image)', 'rawnaq' ),
                'fill'    => esc_html__( 'Fill (stretch)', 'rawnaq' ),
            ],
            'selectors' => [
                '{{WRAPPER}} .rawnaq-flow-chart' => '--fc-avatar-fit: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'avatar_shadow', [
            'label'        => esc_html__( 'Avatar Shadow', 'rawnaq' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'prefix_class' => 'rawnaq-fc-avatar-shadow-',
        ] );

        $this->end_controls_section();
    }

    /**
     * Break parent cycles with DFS ancestor walk.
     *
     * @param array $nodes Nodes with id/parent.
     * @return array
     */
    private function break_parent_cycles( $nodes ) {
        $by_id = [];
        foreach ( $nodes as $n ) {
            $by_id[ $n['id'] ] = $n;
        }
        foreach ( $nodes as &$n ) {
            $parent = $n['parent'];
            if ( '' === $parent || ! isset( $by_id[ $parent ] ) ) {
                $n['parent'] = '';
                continue;
            }
            $seen = [ $n['id'] => true ];
            $cur  = $parent;
            $ok   = true;
            while ( '' !== $cur && isset( $by_id[ $cur ] ) ) {
                if ( isset( $seen[ $cur ] ) ) {
                    $ok = false;
                    break;
                }
                $seen[ $cur ] = true;
                $cur = $by_id[ $cur ]['parent'] ?? '';
            }
            if ( ! $ok ) {
                $n['parent'] = '';
            }
        }
        unset( $n );
        return $nodes;
    }

    private function build_nodes_payload( $items ) {
        $out  = [];
        $seen = [];
        foreach ( $items as $index => $item ) {
            $id = sanitize_key( $item['node_id'] ?? ( 'node-' . ( $index + 1 ) ) );
            if ( '' === $id ) {
                $id = 'node-' . ( $index + 1 );
            }
            $parent = sanitize_key( $item['parent_id'] ?? '' );
            if ( $parent === $id ) {
                $parent = '';
            }
            $base = $id;
            $n    = 2;
            while ( isset( $seen[ $id ] ) ) {
                $id = $base . '-' . $n;
                $n++;
            }
            $seen[ $id ] = true;

            $link = '';
            if ( ! empty( $item['link']['url'] ) ) {
                $link = esc_url_raw( $item['link']['url'] );
            }

            if ( ! empty( $item['selected_icon']['value'] ) && is_string( $item['selected_icon']['value'] ) && class_exists( '\Elementor\Icons_Manager' ) ) {
                if ( method_exists( '\Elementor\Icons_Manager', 'enqueue_icon' ) ) {
                    \Elementor\Icons_Manager::enqueue_icon( $item['selected_icon'] );
                } elseif ( ! empty( $item['selected_icon']['library'] ) && 'svg' !== $item['selected_icon']['library'] ) {
                    wp_enqueue_style( 'elementor-icons-' . $item['selected_icon']['library'] );
                }
            }

            $image = '';
            if ( ! empty( $item['image']['url'] ) ) {
                $image = esc_url_raw( $item['image']['url'] );
            }

            $out[] = [
                'id'       => $id,
                'parent'   => $parent,
                'title'    => $item['title'] ?? '',
                'role'     => $item['role'] ?? '',
                'icon'     => function_exists( 'rawnaq_elementor_icon_token' )
                    ? rawnaq_elementor_icon_token( $item['selected_icon'] ?? [], $item['icon'] ?? '' )
                    : ( $item['icon'] ?? '' ),
                'image'    => $image,
                'edgeLabel' => $item['edge_label'] ?? '',
                'lane'     => $item['lane'] ?? '',
                'detail'   => $item['detail'] ?? '',
                'link'     => $link,
                'decision' => ( $item['is_decision'] ?? '' ) === 'yes',
                'x'        => max( 0, min( 100, (float) ( $item['pos_x'] ?? 10 ) ) ),
                'y'        => max( 0, min( 100, (float) ( $item['pos_y'] ?? 10 ) ) ),
            ];
        }
        return $this->break_parent_cycles( $out );
    }

    protected function render() {
        $s     = $this->get_settings_for_display();
        $mode  = sanitize_key( $s['mode'] ?? 'org' );
        if ( ! in_array( $mode, [ 'org', 'process', 'freeform' ], true ) ) {
            $mode = 'org';
        }
        $dir = sanitize_key( $s['direction'] ?? 'tb' );
        if ( ! in_array( $dir, [ 'tb', 'lr', 'rl' ], true ) ) {
            $dir = 'tb';
        }
        $shape = sanitize_key( $s['shape'] ?? 'rect' );
        if ( ! in_array( $shape, [ 'rect', 'circle', 'hex' ], true ) ) {
            $shape = 'rect';
        }
        $conn = $s['connector'] ?? 'curved';
        $source = sanitize_key( $s['data_source'] ?? 'manual' );
        if ( 'wp_users' === $source && function_exists( 'rawnaq_flow_nodes_from_users' ) ) {
            $nodes = rawnaq_flow_nodes_from_users( [
                'number' => absint( $s['users_number'] ?? 20 ),
                'role'   => sanitize_key( $s['users_role'] ?? '' ),
            ] );
            $nodes = $this->break_parent_cycles( $nodes );
            if ( 'freeform' === $mode ) {
                $mode = 'org';
            }
        } else {
            $nodes = $this->build_nodes_payload( $s['nodes'] ?? [] );
        }
        $avatar_shape = sanitize_key( $s['avatar_shape'] ?? 'rounded' );
        if ( ! in_array( $avatar_shape, [ 'rounded', 'circle', 'square' ], true ) ) {
            $avatar_shape = 'rounded';
        }
        $cfg = [
            'mode'         => $mode,
            'direction'    => $dir,
            'shape'        => $shape,
            'connector'    => $conn,
            'avatarShape'  => $avatar_shape,
            'zoom'         => ( $s['enable_zoom'] ?? 'yes' ) === 'yes',
            'export'       => ( $s['show_export'] ?? 'yes' ) === 'yes',
            'nodes'        => $nodes,
        ];
        $flow_attr = rawurlencode( wp_json_encode( $cfg ) );
        ?>
        <div class="rawnaq-flow-chart avatar-<?php echo esc_attr( $avatar_shape ); ?>"
             data-flow="<?php echo esc_attr( $flow_attr ); ?>">
            <div class="rawnaq-flow-viewport">
                <div class="rawnaq-flow-stage is-responsive"></div>
            </div>
        </div>
        <?php
    }

    protected function content_template() {
        ?>
        <#
        var mode = settings.mode || 'org';
        if ( mode !== 'org' && mode !== 'process' && mode !== 'freeform' ) { mode = 'org'; }
        var direction = settings.direction || 'tb';
        var shape = settings.shape || 'rect';
        var nodes = [];
        var seen = {};
        var source = settings.data_source || 'manual';
        if ( source === 'wp_users' && typeof rawnaqFlowEditor !== 'undefined' && rawnaqFlowEditor.userNodes && rawnaqFlowEditor.userNodes.length ) {
            nodes = rawnaqFlowEditor.userNodes.slice();
            if ( mode === 'freeform' ) { mode = 'org'; }
        } else {
            _.each( settings.nodes || [], function( item, index ) {
                var id = ( item.node_id || ( 'node-' + ( index + 1 ) ) ).toString().replace( /[^a-zA-Z0-9_-]/g, '' );
                if ( ! id ) { id = 'node-' + ( index + 1 ); }
                var parent = ( item.parent_id || '' ).toString().replace( /[^a-zA-Z0-9_-]/g, '' );
                if ( parent === id ) { parent = ''; }
                var base = id; var n = 2;
                while ( seen[id] ) { id = base + '-' + n; n++; }
                seen[id] = true;
                nodes.push({
                    id: id,
                    parent: parent,
                    title: item.title || '',
                    role: item.role || '',
                    icon: ( item.selected_icon && item.selected_icon.value && typeof item.selected_icon.value === 'string' )
                        ? item.selected_icon.value
                        : ( item.icon || '' ),
                    image: ( item.image && item.image.url ) ? item.image.url : '',
                    edgeLabel: item.edge_label || '',
                    lane: item.lane || '',
                    detail: item.detail || '',
                    link: ( item.link && item.link.url ) ? item.link.url : '',
                    decision: item.is_decision === 'yes',
                    x: Math.max( 0, Math.min( 100, parseFloat( item.pos_x ) || 10 ) ),
                    y: Math.max( 0, Math.min( 100, parseFloat( item.pos_y ) || 10 ) )
                });
            });
        }
        var avatarShape = settings.avatar_shape || 'rounded';
        if ( avatarShape !== 'circle' && avatarShape !== 'square' && avatarShape !== 'rounded' ) { avatarShape = 'rounded'; }
        var cfg = {
            mode: mode,
            direction: direction,
            shape: shape,
            connector: settings.connector || 'curved',
            avatarShape: avatarShape,
            zoom: settings.enable_zoom !== '',
            export: settings.show_export !== '',
            nodes: nodes
        };
        var flowAttr = encodeURIComponent( JSON.stringify( cfg ) );
        #>
        <div class="rawnaq-flow-chart avatar-{{ avatarShape }}" data-flow="{{ flowAttr }}">
            <div class="rawnaq-flow-viewport">
                <div class="rawnaq-flow-stage is-responsive"></div>
            </div>
        </div>
        <?php
    }
}
