<?php
/**
 * Rawnaq Divi Module: Interactive Flow Chart
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
		$this->name       = esc_html__( 'Rawnaq Flow Chart', 'rawnaq' );
		$this->icon_path  = 'chart';
		$this->main_css_element = '%%order_class%%.rawnaq-flow-chart-wrap';
	}

	public function get_fields() {
		return [
			'chart_title' => [
				'label'           => esc_html__( 'Chart Title', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => 'Design & Execution Workflow',
				'toggle_slug'     => 'main_content',
			],
			'layout_type' => [
				'label'           => esc_html__( 'Flow Layout Direction', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'horizontal' => esc_html__( 'Horizontal Flow', 'rawnaq' ),
					'vertical'   => esc_html__( 'Vertical Tree', 'rawnaq' ),
					'radial'     => esc_html__( 'Circular Cycle', 'rawnaq' ),
				],
				'default'         => 'horizontal',
				'toggle_slug'     => 'layout',
			],
			'accent_color' => [
				'label'        => esc_html__( 'Connector & Node Accent Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#0f766e',
				'toggle_slug'  => 'style',
			],
			'nodes_json' => [
				'label'           => esc_html__( 'Custom Nodes JSON', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'JSON array of nodes [{title, desc, step, color}]', 'rawnaq' ),
				'default'         => '[{"title":"Concept & Brief","desc":"Initial client discovery & moodboard","step":"01"},{"title":"3D Spatial Planning","desc":"Photorealistic rendering & layouts","step":"02"},{"title":"Material Procurement","desc":"Sourcing luxury finishes & fixtures","step":"03"},{"title":"Turnkey Handover","desc":"Final styling & project delivery","step":"04"}]',
				'toggle_slug'     => 'nodes',
			],
		];
	}

	public function render( $attrs, $content = null, $render_slug = '' ) {
		wp_enqueue_style( 'rawnaq-flow-chart' );
		wp_enqueue_script( 'rawnaq-flow-chart' );

		$title        = sanitize_text_field( $this->props['chart_title'] ?? 'Design & Execution Workflow' );
		$layout       = sanitize_text_field( $this->props['layout_type'] ?? 'horizontal' );
		$accent_color = sanitize_hex_color( $this->props['accent_color'] ?? '#0f766e' ) ?: '#0f766e';
		$raw_nodes    = $this->props['nodes_json'] ?? '';

		$nodes = json_decode( $raw_nodes, true );
		if ( ! is_array( $nodes ) || empty( $nodes ) ) {
			$nodes = [
				[ 'title' => 'Concept & Brief', 'desc' => 'Initial client discovery & moodboard', 'step' => '01' ],
				[ 'title' => '3D Spatial Planning', 'desc' => 'Photorealistic rendering & layouts', 'step' => '02' ],
				[ 'title' => 'Material Procurement', 'desc' => 'Sourcing luxury finishes & fixtures', 'step' => '03' ],
				[ 'title' => 'Turnkey Handover', 'desc' => 'Final styling & project delivery', 'step' => '04' ],
			];
		}

		ob_start();
		?>
		<div class="rawnaq-flow-chart-wrap layout-<?php echo esc_attr( $layout ); ?>" style="--rq-accent: <?php echo esc_attr( $accent_color ); ?>;">
			<?php if ( $title ) : ?>
				<h3 class="rawnaq-flow-chart-title"><?php echo esc_html( $title ); ?></h3>
			<?php endif; ?>
			<div class="rawnaq-flow-nodes-container">
				<?php foreach ( $nodes as $idx => $node ) : ?>
					<div class="rawnaq-flow-node" data-step="<?php echo esc_attr( (string) ( $idx + 1 ) ); ?>">
						<div class="rawnaq-node-badge"><?php echo esc_html( $node['step'] ?? str_pad( (string) ( $idx + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></div>
						<div class="rawnaq-node-content">
							<h4 class="rawnaq-node-title"><?php echo esc_html( $node['title'] ?? 'Step' ); ?></h4>
							<?php if ( ! empty( $node['desc'] ) ) : ?>
								<p class="rawnaq-node-desc"><?php echo esc_html( $node['desc'] ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
