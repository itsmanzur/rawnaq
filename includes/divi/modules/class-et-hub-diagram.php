<?php
/**
 * Rawnaq Divi Module: Interactive Hub Diagram & Ecosystem Map
 *
 * @package Rawnaq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_ET_Hub_Diagram extends ET_Builder_Module {

	public $slug       = 'rawnaq_et_hub_diagram';
	public $vb_support = 'on';

	public function init() {
		$this->name            = esc_html__( 'Rawnaq Hub Diagram', 'rawnaq' );
		$this->icon_path       = 'networking';
		$this->main_css_element = '%%order_class%% .hub-diagram-host';
	}

	public function get_fields() {
		return [
			'center_title' => [
				'label'           => esc_html__( 'Center Hub Title', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => 'STUDY 2D & 3D',
				'toggle_slug'     => 'main_content',
			],
			'center_subtitle' => [
				'label'           => esc_html__( 'Center Hub Subtitle', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'default'         => "REVIEW WITH\nCLIENT",
				'toggle_slug'     => 'main_content',
			],
			'center_icon' => [
				'label'           => esc_html__( 'Center Icon Token', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
				'toggle_slug'     => 'main_content',
			],
			'layout_flow' => [
				'label'           => esc_html__( 'Layout Alignment Flow', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'horizontal' => esc_html__( 'Horizontal (Top/Bottom Row)', 'rawnaq' ),
					'vertical'   => esc_html__( 'Vertical (Left/Right Column)', 'rawnaq' ),
					'radial'     => esc_html__( 'Radial (360° Circular)', 'rawnaq' ),
				],
				'default'         => 'horizontal',
				'toggle_slug'     => 'layout',
			],
			'line_curve' => [
				'label'           => esc_html__( 'Connector Path Geometry', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'orthogonal' => esc_html__( 'Orthogonal Elbows', 'rawnaq' ),
					'bezier'     => esc_html__( 'Organic Bezier Curves', 'rawnaq' ),
					'straight'   => esc_html__( 'Direct Straight Spokes', 'rawnaq' ),
				],
				'default'         => 'orthogonal',
				'toggle_slug'     => 'layout',
			],
			'card_shape' => [
				'label'           => esc_html__( 'Card Shape Style', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'rect'    => esc_html__( 'Rectangle Box', 'rawnaq' ),
					'pill'    => esc_html__( 'Rounded Pill', 'rawnaq' ),
					'outline' => esc_html__( 'Minimal Outline', 'rawnaq' ),
				],
				'default'         => 'rect',
				'toggle_slug'     => 'layout',
			],
			'line_style' => [
				'label'           => esc_html__( 'Connector Line Type', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'solid'  => esc_html__( 'Solid Line', 'rawnaq' ),
					'dashed' => esc_html__( 'Dashed Line', 'rawnaq' ),
					'dotted' => esc_html__( 'Dotted Line', 'rawnaq' ),
				],
				'default'         => 'solid',
				'toggle_slug'     => 'layout',
			],
			'glow_lines' => [
				'label'           => esc_html__( 'Animated Glow Flow Lines', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'off' => esc_html__( 'No', 'rawnaq' ),
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'effects',
			],
			'pulse_effect' => [
				'label'           => esc_html__( 'Center Ripple Wave Pulse', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'on',
				'toggle_slug'     => 'effects',
			],
			'diagram_height' => [
				'label'           => esc_html__( 'Diagram Height (px)', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'layout',
				'default'         => '540',
				'range_settings'  => [
					'min'  => 300,
					'max'  => 900,
					'step' => 10,
				],
				'toggle_slug'     => 'layout',
			],
			'top_nodes_json' => [
				'label'           => esc_html__( 'Top Row / Left Col Nodes JSON', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'default'         => '[{"label":"Design","desc":"Wireframe & system architecture","color":"#E8793A","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-art","link":"","target":"_self"},{"label":"P&ID","desc":"Piping & instrumentation diagram","color":"#D4A92A","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-editor-justify","link":"","target":"_self"},{"label":"Sketch","desc":"Concept drafts & visual ideation","color":"#26B8B8","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-welcome-write-blog","link":"","target":"_self"},{"label":"Specification","desc":"Technical scoping & constraints","color":"#E8793A","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-clipboard","link":"","target":"_self"}]',
				'toggle_slug'     => 'nodes',
			],
			'bot_nodes_json' => [
				'label'           => esc_html__( 'Bottom Row / Right Col Nodes JSON', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'default'         => '[{"label":"MTO/BOQ","desc":"Material takeoff & quantities","color":"#E8793A","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-list-view","link":"","target":"_self"},{"label":"3D CAD Model","desc":"Parametric solid rendering","color":"#D4A92A","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-format-image","link":"","target":"_self"},{"label":"Drawings","desc":"High-precision blueprints","color":"#26B8B8","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-portfolio","link":"","target":"_self"},{"label":"Pipe Isometric","desc":"Detailed spool breakdown","color":"#E8793A","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-chart-area","link":"","target":"_self"}]',
				'toggle_slug'     => 'nodes',
			],
			'line_color' => [
				'label'        => esc_html__( 'Connector Line Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#c2c2c2',
				'toggle_slug'  => 'style',
			],
			'seg1_color' => [
				'label'        => esc_html__( 'Segment 1 / Primary Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#E8793A',
				'toggle_slug'  => 'style',
			],
			'seg2_color' => [
				'label'        => esc_html__( 'Segment 2 Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#D4A92A',
				'toggle_slug'  => 'style',
			],
			'seg3_color' => [
				'label'        => esc_html__( 'Segment 3 Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#26B8B8',
				'toggle_slug'  => 'style',
			],
		];
	}

	public function render( $attrs, $content = null, $render_slug = '' ) {
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'rawnaq-hub-diagram' );
		wp_enqueue_script( 'rawnaq-hub-diagram' );

		$center_title   = sanitize_text_field( $this->props['center_title'] ?? 'STUDY 2D & 3D' );
		$center_sub     = sanitize_textarea_field( $this->props['center_subtitle'] ?? "REVIEW WITH\nCLIENT" );
		$center_icon    = sanitize_text_field( $this->props['center_icon'] ?? '' );
		$layout_flow    = sanitize_text_field( $this->props['layout_flow'] ?? 'horizontal' );
		$line_curve     = sanitize_text_field( $this->props['line_curve'] ?? 'orthogonal' );
		$card_shape     = sanitize_text_field( $this->props['card_shape'] ?? 'rect' );
		$line_style     = sanitize_text_field( $this->props['line_style'] ?? 'solid' );
		$glow_lines     = ( $this->props['glow_lines'] ?? 'off' ) === 'on' ? 'yes' : 'no';
		$pulse_effect   = ( $this->props['pulse_effect'] ?? 'on' ) === 'on' ? 'yes' : 'no';
		$diagram_height = intval( $this->props['diagram_height'] ?? 540 );
		$line_color     = sanitize_hex_color( $this->props['line_color'] ?? '#c2c2c2' ) ?: '#c2c2c2';
		$seg1_color     = sanitize_hex_color( $this->props['seg1_color'] ?? '#E8793A' ) ?: '#E8793A';
		$seg2_color     = sanitize_hex_color( $this->props['seg2_color'] ?? '#D4A92A' ) ?: '#D4A92A';
		$seg3_color     = sanitize_hex_color( $this->props['seg3_color'] ?? '#26B8B8' ) ?: '#26B8B8';

		$top_raw = $this->props['top_nodes_json'] ?? '';
		$bot_raw = $this->props['bot_nodes_json'] ?? '';

		$top = json_decode( $top_raw, true ) ?: [];
		$bot = json_decode( $bot_raw, true ) ?: [];

		$map = function( $nodes, $prefix ) {
			$out = [];
			foreach ( $nodes as $i => $n ) {
				$out[] = [
					'id'        => $prefix . $i,
					'label'     => $n['label'] ?? '',
					'desc'      => $n['desc'] ?? '',
					'badge'     => $n['badge'] ?? '',
					'color'     => $n['color'] ?? '#E8793A',
					'cardBg'    => $n['cardBg'] ?? '#ffffff',
					'cardColor' => $n['cardColor'] ?? '#1a1a1a',
					'icon'      => $n['icon'] ?? '',
					'link'      => $n['link'] ?? '',
					'target'    => $n['target'] ?? '_self',
				];
			}
			return $out;
		};

		$cfg = [
			'centerTitle'     => $center_title,
			'centerSubtitle'  => $center_sub,
			'centerIcon'      => $center_icon,
			'lineColor'       => $line_color,
			'seg1Color'       => $seg1_color,
			'seg2Color'       => $seg2_color,
			'seg3Color'       => $seg3_color,
			'cardShape'       => $card_shape,
			'lineStyle'       => $line_style,
			'lineCurve'       => $line_curve,
			'glowLines'       => $glow_lines,
			'pulseEffect'     => $pulse_effect,
			'showStepNumbers' => false,
			'centerStyle'     => 'conic',
			'layoutFlow'      => $layout_flow,
			'export'          => true,
			'top'             => $map( $top, 't' ),
			'bottom'          => $map( $bot, 'b' ),
		];

		$unique_id = 'divi-hub-' . wp_generate_uuid4();
		ob_start();
		?>
		<div class="hub-diagram-host"
			 id="<?php echo esc_attr( $unique_id ); ?>"
			 style="height: <?php echo esc_attr( (string) $diagram_height ); ?>px;"
			 data-hub="<?php echo esc_attr( wp_json_encode( $cfg ) ); ?>">
		</div>
		<?php
		return ob_get_clean();
	}
}
