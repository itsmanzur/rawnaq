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

        $this->end_controls_section();

        $this->start_controls_section( 's_nodes', [
            'label' => esc_html__( 'Nodes', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $r = new \Elementor\Repeater();

        $r->add_control( 'node_id', [
            'label'       => esc_html__( 'Node ID', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'node-1',
            'description' => esc_html__( 'Unique key used as parent reference.', 'rawnaq' ),
        ] );

        $r->add_control( 'parent_id', [
            'label'       => esc_html__( 'Parent Node ID', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'description' => esc_html__( 'Leave empty for root / start node. Cycles are auto-cleared.', 'rawnaq' ),
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

        $r->add_control( 'icon', [
            'label'       => esc_html__( 'Icon (emoji or dashicons-*)', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '●',
            'placeholder' => '⚙ or dashicons-admin-users',
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
                [ 'node_id' => 'ceo', 'parent_id' => '', 'title' => 'Founder / CEO', 'role' => 'Leadership', 'icon' => '★', 'detail' => 'Leads the company and client relationships.', 'is_decision' => '', 'pos_x' => 40, 'pos_y' => 5 ],
                [ 'node_id' => 'eng', 'parent_id' => 'ceo', 'title' => 'Engineering Head', 'role' => 'Product', 'icon' => '⚙', 'detail' => 'Owns engineering roadmap.', 'is_decision' => '', 'pos_x' => 15, 'pos_y' => 40 ],
                [ 'node_id' => 'ops', 'parent_id' => 'ceo', 'title' => 'Operations Head', 'role' => 'Delivery', 'icon' => '◆', 'detail' => 'Coordinates project delivery.', 'is_decision' => '', 'pos_x' => 40, 'pos_y' => 40 ],
                [ 'node_id' => 'biz', 'parent_id' => 'ceo', 'title' => 'Business Dev', 'role' => 'Growth', 'icon' => '▲', 'detail' => 'Client acquisition.', 'is_decision' => '', 'pos_x' => 65, 'pos_y' => 40 ],
                [ 'node_id' => 'e1', 'parent_id' => 'eng', 'title' => 'Frontend Team', 'role' => 'Team, 4', 'icon' => '▪', 'detail' => 'UI implementation.', 'is_decision' => '', 'pos_x' => 5, 'pos_y' => 75 ],
                [ 'node_id' => 'e2', 'parent_id' => 'eng', 'title' => 'Backend Team', 'role' => 'Team, 3', 'icon' => '▪', 'detail' => 'API & infrastructure.', 'is_decision' => '', 'pos_x' => 25, 'pos_y' => 75 ],
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

            $out[] = [
                'id'       => $id,
                'parent'   => $parent,
                'title'    => $item['title'] ?? '',
                'role'     => $item['role'] ?? '',
                'icon'     => $item['icon'] ?? '',
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
        $nodes = $this->build_nodes_payload( $s['nodes'] ?? [] );
        $cfg = [
            'mode'      => $mode,
            'direction' => $dir,
            'shape'     => $shape,
            'connector' => $conn,
            'zoom'      => ( $s['enable_zoom'] ?? 'yes' ) === 'yes',
            'nodes'     => $nodes,
        ];
        $flow_attr = rawurlencode( wp_json_encode( $cfg ) );
        ?>
        <div class="rawnaq-flow-chart"
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
                icon: item.icon || '',
                detail: item.detail || '',
                link: ( item.link && item.link.url ) ? item.link.url : '',
                decision: item.is_decision === 'yes',
                x: Math.max( 0, Math.min( 100, parseFloat( item.pos_x ) || 10 ) ),
                y: Math.max( 0, Math.min( 100, parseFloat( item.pos_y ) || 10 ) )
            });
        });
        var cfg = {
            mode: mode,
            direction: direction,
            shape: shape,
            connector: settings.connector || 'curved',
            zoom: settings.enable_zoom !== '',
            nodes: nodes
        };
        var flowAttr = encodeURIComponent( JSON.stringify( cfg ) );
        #>
        <div class="rawnaq-flow-chart" data-flow="{{ flowAttr }}">
            <div class="rawnaq-flow-viewport">
                <div class="rawnaq-flow-stage is-responsive"></div>
            </div>
        </div>
        <?php
    }
}
