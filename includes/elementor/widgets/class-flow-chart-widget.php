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
                'org'     => esc_html__( 'Org (vertical tree)', 'rawnaq' ),
                'process' => esc_html__( 'Process (flow + branches)', 'rawnaq' ),
            ],
        ] );

        $this->add_control( 'connector', [
            'label'   => esc_html__( 'Connector Style', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'curved',
            'options' => [
                'curved'  => esc_html__( 'Curved', 'rawnaq' ),
                'elbow'   => esc_html__( 'Elbow (90°)', 'rawnaq' ),
                'straight'=> esc_html__( 'Straight', 'rawnaq' ),
                'dashed'  => esc_html__( 'Dashed flow', 'rawnaq' ),
            ],
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
            'description' => esc_html__( 'Leave empty for root / start node.', 'rawnaq' ),
        ] );

        $r->add_control( 'title', [
            'label'   => esc_html__( 'Title', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'Node Title',
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

        $this->add_control( 'nodes', [
            'label'       => esc_html__( 'Nodes', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::REPEATER,
            'fields'      => $r->get_controls(),
            'title_field' => '{{{ title }}} ({{{ node_id }}})',
            'default'     => [
                [ 'node_id' => 'ceo', 'parent_id' => '', 'title' => 'Founder / CEO', 'role' => 'Leadership', 'icon' => '★', 'detail' => 'Leads the company and client relationships.', 'is_decision' => '' ],
                [ 'node_id' => 'eng', 'parent_id' => 'ceo', 'title' => 'Engineering Head', 'role' => 'Product', 'icon' => '⚙', 'detail' => 'Owns engineering roadmap.', 'is_decision' => '' ],
                [ 'node_id' => 'ops', 'parent_id' => 'ceo', 'title' => 'Operations Head', 'role' => 'Delivery', 'icon' => '◆', 'detail' => 'Coordinates project delivery.', 'is_decision' => '' ],
                [ 'node_id' => 'biz', 'parent_id' => 'ceo', 'title' => 'Business Dev', 'role' => 'Growth', 'icon' => '▲', 'detail' => 'Client acquisition.', 'is_decision' => '' ],
                [ 'node_id' => 'e1', 'parent_id' => 'eng', 'title' => 'Frontend Team', 'role' => 'Team, 4', 'icon' => '▪', 'detail' => 'UI implementation.', 'is_decision' => '' ],
                [ 'node_id' => 'e2', 'parent_id' => 'eng', 'title' => 'Backend Team', 'role' => 'Team, 3', 'icon' => '▪', 'detail' => 'API & infrastructure.', 'is_decision' => '' ],
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

    private function build_nodes_payload( $items ) {
        $out  = [];
        $seen = [];
        foreach ( $items as $index => $item ) {
            $id = sanitize_key( $item['node_id'] ?? ( 'node-' . ( $index + 1 ) ) );
            if ( '' === $id ) {
                $id = 'node-' . ( $index + 1 );
            }
            // Avoid circular self-parent.
            $parent = sanitize_key( $item['parent_id'] ?? '' );
            if ( $parent === $id ) {
                $parent = '';
            }
            // Duplicate IDs: suffix.
            $base = $id;
            $n = 2;
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
            ];
        }
        return $out;
    }

    protected function render() {
        $s     = $this->get_settings_for_display();
        $mode  = ( $s['mode'] ?? 'org' ) === 'process' ? 'process' : 'org';
        $conn  = $s['connector'] ?? 'curved';
        $nodes = $this->build_nodes_payload( $s['nodes'] ?? [] );
        $cfg = [
            'mode'      => $mode,
            'connector' => $conn,
            'nodes'     => $nodes,
        ];
        $flow_attr = rawurlencode( wp_json_encode( $cfg ) );
        ?>
        <div class="rawnaq-flow-chart"
             data-flow="<?php echo esc_attr( $flow_attr ); ?>">
            <div class="rawnaq-flow-stage is-responsive"></div>
        </div>
        <?php
    }

    protected function content_template() {
        ?>
        <#
        var mode = settings.mode === 'process' ? 'process' : 'org';
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
                decision: item.is_decision === 'yes'
            });
        });
        var cfg = { mode: mode, connector: settings.connector || 'curved', nodes: nodes };
        var flowAttr = encodeURIComponent( JSON.stringify( cfg ) );
        #>
        <div class="rawnaq-flow-chart" data-flow="{{ flowAttr }}">
            <div class="rawnaq-flow-stage is-responsive"></div>
        </div>
        <?php
    }
}
