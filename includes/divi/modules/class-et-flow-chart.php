<?php
/**
 * Rawnaq Divi Module: Interactive Flow Chart & Org Tree
 *
 * @package Rawnaq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_ET_Flow_Chart extends ET_Builder_Module {

	public $slug       = 'rawnaq_et_flow_chart';
	public $vb_support = 'on';

	public function init() {
		$this->name            = esc_html__( 'Rawnaq Flow Chart', 'rawnaq' );
		$this->icon_path       = 'chart';
		$this->main_css_element = '%%order_class%% .rawnaq-flow-chart';
	}

	public function get_fields() {
		return [
			'chart_mode' => [
				'label'           => esc_html__( 'Flow Chart Mode', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'org'      => esc_html__( 'Organization / Tree Hierarchy', 'rawnaq' ),
					'process'  => esc_html__( 'Process Pipeline Flow', 'rawnaq' ),
					'freeform' => esc_html__( 'Freeform Canvas Layout', 'rawnaq' ),
				],
				'default'         => 'org',
				'toggle_slug'     => 'main_content',
			],
			'flow_direction' => [
				'label'           => esc_html__( 'Flow Direction', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'tb' => esc_html__( 'Top to Bottom (Vertical)', 'rawnaq' ),
					'lr' => esc_html__( 'Left to Right (Horizontal)', 'rawnaq' ),
					'rl' => esc_html__( 'Right to Left', 'rawnaq' ),
				],
				'default'         => 'tb',
				'toggle_slug'     => 'main_content',
			],
			'node_shape' => [
				'label'           => esc_html__( 'Node Shape', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'rect'   => esc_html__( 'Rounded Card', 'rawnaq' ),
					'circle' => esc_html__( 'Circular Badge', 'rawnaq' ),
					'hex'    => esc_html__( 'Hexagon', 'rawnaq' ),
				],
				'default'         => 'rect',
				'toggle_slug'     => 'main_content',
			],
			'connector_type' => [
				'label'           => esc_html__( 'Connector Line Type', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'curved'   => esc_html__( 'Smooth Curved Bezier', 'rawnaq' ),
					'elbow'    => esc_html__( 'Orthogonal Elbow Steps', 'rawnaq' ),
					'straight' => esc_html__( 'Direct Straight Line', 'rawnaq' ),
					'dashed'   => esc_html__( 'Dashed Line', 'rawnaq' ),
				],
				'default'         => 'curved',
				'toggle_slug'     => 'main_content',
			],
			'avatar_shape' => [
				'label'           => esc_html__( 'Avatar Shape', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'rounded' => esc_html__( 'Rounded Squircle', 'rawnaq' ),
					'circle'  => esc_html__( 'Circle', 'rawnaq' ),
					'square'  => esc_html__( 'Square', 'rawnaq' ),
				],
				'default'         => 'rounded',
				'toggle_slug'     => 'main_content',
			],
			'enable_zoom' => [
				'label'           => esc_html__( 'Enable Pan & Zoom Controls', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'on',
				'toggle_slug'     => 'main_content',
			],
			'show_export' => [
				'label'           => esc_html__( 'Enable PNG / SVG Export Toolbar', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'on',
				'toggle_slug'     => 'main_content',
			],
			'nodes_json' => [
				'label'           => esc_html__( 'Nodes Configuration (JSON)', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'Enter JSON array of nodes with id, parent, title, role, icon, detail, link etc.', 'rawnaq' ),
				'default'         => '[{"id":"ceo","parent":"","title":"Elena Rostova","role":"Chief Executive Officer","icon":"dashicons-businessman","detail":"Oversees executive strategy and global operations."},{"id":"cto","parent":"ceo","title":"Marcus Vance","role":"VP Engineering & Tech","icon":"dashicons-laptop","detail":"Directs engineering and technical architecture."},{"id":"cdo","parent":"ceo","title":"Sophia Lin","role":"VP Creative & Design","icon":"dashicons-art","detail":"Directs brand identity and design standards."},{"id":"lead-dev","parent":"cto","title":"Liam Thorne","role":"Lead Architect","icon":"dashicons-code-standards","detail":"Core system development."},{"id":"lead-des","parent":"cdo","title":"Aria Sterling","role":"Principal Designer","icon":"dashicons-format-image","detail":"Product UX and visual systems."}]',
				'toggle_slug'     => 'nodes',
			],
			'accent_color' => [
				'label'        => esc_html__( 'Accent Glow Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#fbbf24',
				'toggle_slug'  => 'style',
			],
			'root_color_from' => [
				'label'        => esc_html__( 'Root Node Gradient From', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#4338ca',
				'toggle_slug'  => 'style',
			],
			'root_color_to' => [
				'label'        => esc_html__( 'Root Node Gradient To', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#7c3aed',
				'toggle_slug'  => 'style',
			],
			'line_color' => [
				'label'        => esc_html__( 'Connector Line Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#e6e2f0',
				'toggle_slug'  => 'style',
			],
			'node_bg' => [
				'label'        => esc_html__( 'Node Card Background', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#ffffff',
				'toggle_slug'  => 'style',
			],
			'node_radius' => [
				'label'           => esc_html__( 'Node Card Border Radius (px)', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'layout',
				'default'         => '14',
				'range_settings'  => [
					'min'  => 0,
					'max'  => 40,
					'step' => 1,
				],
				'toggle_slug'     => 'style',
			],
		];
	}

	public function render( $attrs, $content = null, $render_slug = '' ) {
		wp_enqueue_style( 'rawnaq-flow-chart' );
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_script( 'rawnaq-flow-chart' );

		$mode           = sanitize_key( $this->props['chart_mode'] ?? 'org' );
		$direction      = sanitize_key( $this->props['flow_direction'] ?? 'tb' );
		$shape          = sanitize_key( $this->props['node_shape'] ?? 'rect' );
		$connector      = sanitize_key( $this->props['connector_type'] ?? 'curved' );
		$avatar_shape   = sanitize_key( $this->props['avatar_shape'] ?? 'rounded' );
		$enable_zoom    = ( $this->props['enable_zoom'] ?? 'on' ) === 'on';
		$show_export    = ( $this->props['show_export'] ?? 'on' ) === 'on';
		$accent         = sanitize_hex_color( $this->props['accent_color'] ?? '#fbbf24' ) ?: '#fbbf24';
		$root_from      = sanitize_hex_color( $this->props['root_color_from'] ?? '#4338ca' ) ?: '#4338ca';
		$root_to        = sanitize_hex_color( $this->props['root_color_to'] ?? '#7c3aed' ) ?: '#7c3aed';
		$line_color     = sanitize_hex_color( $this->props['line_color'] ?? '#e6e2f0' ) ?: '#e6e2f0';
		$node_bg        = sanitize_hex_color( $this->props['node_bg'] ?? '#ffffff' ) ?: '#ffffff';
		$node_radius    = intval( $this->props['node_radius'] ?? 14 );

		$raw_nodes = json_decode( $this->props['nodes_json'] ?? '[]', true );
		if ( ! is_array( $raw_nodes ) || empty( $raw_nodes ) ) {
			$raw_nodes = [
				[ 'id' => 'ceo', 'parent' => '', 'title' => 'Elena Rostova', 'role' => 'Chief Executive Officer', 'icon' => 'dashicons-businessman', 'detail' => 'Oversees executive strategy and global operations.' ],
				[ 'id' => 'cto', 'parent' => 'ceo', 'title' => 'Marcus Vance', 'role' => 'VP Engineering & Tech', 'icon' => 'dashicons-laptop', 'detail' => 'Directs engineering and technical architecture.' ],
				[ 'id' => 'cdo', 'parent' => 'ceo', 'title' => 'Sophia Lin', 'role' => 'VP Creative & Design', 'icon' => 'dashicons-art', 'detail' => 'Directs brand identity and design standards.' ],
			];
		}

		$nodes = [];
		$seen  = [];
		foreach ( $raw_nodes as $index => $item ) {
			$id = sanitize_key( $item['id'] ?? ( 'node-' . ( $index + 1 ) ) );
			if ( '' === $id ) {
				$id = 'node-' . ( $index + 1 );
			}
			$base = $id;
			$n    = 2;
			while ( isset( $seen[ $id ] ) ) {
				$id = $base . '-' . $n;
				$n++;
			}
			$seen[ $id ] = true;
			$parent = sanitize_key( $item['parent'] ?? '' );
			if ( $parent === $id ) {
				$parent = '';
			}
			$nodes[] = [
				'id'        => $id,
				'parent'    => $parent,
				'title'     => sanitize_text_field( $item['title'] ?? '' ),
				'role'      => sanitize_text_field( $item['role'] ?? '' ),
				'icon'      => sanitize_text_field( $item['icon'] ?? '' ),
				'image'     => ! empty( $item['image'] ) ? esc_url_raw( $item['image'] ) : '',
				'edgeLabel' => sanitize_text_field( $item['edgeLabel'] ?? '' ),
				'lane'      => sanitize_text_field( $item['lane'] ?? '' ),
				'detail'    => sanitize_textarea_field( $item['detail'] ?? '' ),
				'link'      => ! empty( $item['link'] ) ? esc_url_raw( $item['link'] ) : '',
				'decision'  => ! empty( $item['decision'] ),
				'x'         => max( 0, min( 100, floatval( $item['x'] ?? 10 ) ) ),
				'y'         => max( 0, min( 100, floatval( $item['y'] ?? 10 ) ) ),
			];
		}

		// Cycle break for hierarchy
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
			$walk = [ $n['id'] => true ];
			$cur  = $parent;
			while ( '' !== $cur && isset( $by_id[ $cur ] ) ) {
				if ( isset( $walk[ $cur ] ) ) {
					$n['parent'] = '';
					break;
				}
				$walk[ $cur ] = true;
				$cur = $by_id[ $cur ]['parent'] ?? '';
			}
		}
		unset( $n );

		$cfg = [
			'mode'        => $mode,
			'direction'   => $direction,
			'shape'       => $shape,
			'connector'   => $connector,
			'avatarShape' => $avatar_shape,
			'zoom'        => $enable_zoom,
			'export'      => $show_export,
			'nodes'       => $nodes,
		];

		$style = sprintf(
			'--fc-amber:%1$s;--fc-indigo:%2$s;--fc-violet:%3$s;--fc-line:%4$s;--fc-panel:%5$s;--fc-radius:%6$dpx;',
			$accent,
			$root_from,
			$root_to,
			$line_color,
			$node_bg,
			$node_radius
		);

		$flow_attr = rawurlencode( wp_json_encode( $cfg ) );

		ob_start();
		?>
		<div class="rawnaq-flow-chart avatar-<?php echo esc_attr( $avatar_shape ); ?>"
			 style="<?php echo esc_attr( $style ); ?>"
			 data-flow="<?php echo esc_attr( $flow_attr ); ?>">
			<div class="rawnaq-flow-viewport">
				<div class="rawnaq-flow-stage is-responsive"></div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
