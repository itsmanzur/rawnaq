<?php
/**
 * Rawnaq Material Quantity Calculator
 *
 * Single source of truth for the calculator definitions, config sanitising and markup.
 * The maths runs in assets/js/material-calc.js; the server renders the form so it works
 * with caching and shows a sensible state before the script loads.
 *
 * @package Rawnaq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calculator definitions.
 *
 * Field types (all values are stored and sent in metric): len (m), cm, mm, area (m2),
 * cov (m2 per litre), num, pct, select. `d` is the metric default.
 *
 * @return array<string, array>
 */
function rawnaq_mc_calculators() {
	return [
		'concrete' => [
			'label'  => __( 'Concrete', 'rawnaq' ),
			'hint'   => __( 'Slab, footing or column volume, with cement, sand and aggregate for the mix.', 'rawnaq' ),
			'fields' => [
				[ 'k' => 'length', 'label' => __( 'Length', 'rawnaq' ), 't' => 'len', 'd' => 5 ],
				[ 'k' => 'width', 'label' => __( 'Width', 'rawnaq' ), 't' => 'len', 'd' => 4 ],
				[ 'k' => 'depth', 'label' => __( 'Thickness / depth', 'rawnaq' ), 't' => 'cm', 'd' => 15 ],
				[
					'k'       => 'mix',
					'label'   => __( 'Mix ratio (cement : sand : aggregate)', 'rawnaq' ),
					't'       => 'select',
					'd'       => '1:2:4',
					'options' => [
						'1:1.5:3' => '1 : 1.5 : 3 (' . __( 'strong', 'rawnaq' ) . ')',
						'1:2:4'   => '1 : 2 : 4 (' . __( 'general', 'rawnaq' ) . ')',
						'1:3:6'   => '1 : 3 : 6 (' . __( 'lean', 'rawnaq' ) . ')',
					],
				],
				[ 'k' => 'waste', 'label' => __( 'Wastage', 'rawnaq' ), 't' => 'pct', 'd' => 5 ],
			],
		],
		'bricks'   => [
			'label'  => __( 'Bricks & mortar', 'rawnaq' ),
			'hint'   => __( 'Brick count for a wall, minus doors and windows, with the mortar it needs.', 'rawnaq' ),
			'fields' => [
				[ 'k' => 'length', 'label' => __( 'Wall length', 'rawnaq' ), 't' => 'len', 'd' => 10 ],
				[ 'k' => 'height', 'label' => __( 'Wall height', 'rawnaq' ), 't' => 'len', 'd' => 3 ],
				[ 'k' => 'thick', 'label' => __( 'Wall thickness', 'rawnaq' ), 't' => 'mm', 'd' => 230 ],
				[ 'k' => 'open', 'label' => __( 'Doors and windows (total area)', 'rawnaq' ), 't' => 'area', 'd' => 4 ],
				[ 'k' => 'bl', 'label' => __( 'Brick length', 'rawnaq' ), 't' => 'mm', 'd' => 240 ],
				[ 'k' => 'bw', 'label' => __( 'Brick width', 'rawnaq' ), 't' => 'mm', 'd' => 115 ],
				[ 'k' => 'bh', 'label' => __( 'Brick height', 'rawnaq' ), 't' => 'mm', 'd' => 70 ],
				[ 'k' => 'joint', 'label' => __( 'Mortar joint', 'rawnaq' ), 't' => 'mm', 'd' => 10 ],
				[
					'k'       => 'mortar',
					'label'   => __( 'Mortar ratio (cement : sand)', 'rawnaq' ),
					't'       => 'select',
					'd'       => '5',
					'options' => [
						'4' => '1 : 4',
						'5' => '1 : 5',
						'6' => '1 : 6',
					],
				],
				[ 'k' => 'waste', 'label' => __( 'Wastage', 'rawnaq' ), 't' => 'pct', 'd' => 5 ],
			],
		],
		'tiles'    => [
			'label'  => __( 'Tiles & flooring', 'rawnaq' ),
			'hint'   => __( 'Tiles or planks for a floor, with grout gap, wastage and boxes.', 'rawnaq' ),
			'fields' => [
				[ 'k' => 'length', 'label' => __( 'Floor length', 'rawnaq' ), 't' => 'len', 'd' => 4 ],
				[ 'k' => 'width', 'label' => __( 'Floor width', 'rawnaq' ), 't' => 'len', 'd' => 3.5 ],
				[ 'k' => 'tw', 'label' => __( 'Tile width', 'rawnaq' ), 't' => 'cm', 'd' => 60 ],
				[ 'k' => 'th', 'label' => __( 'Tile length', 'rawnaq' ), 't' => 'cm', 'd' => 60 ],
				[ 'k' => 'gap', 'label' => __( 'Grout gap', 'rawnaq' ), 't' => 'mm', 'd' => 3 ],
				[ 'k' => 'box', 'label' => __( 'Tiles per box (0 if sold singly)', 'rawnaq' ), 't' => 'num', 'd' => 4 ],
				[ 'k' => 'waste', 'label' => __( 'Wastage (cuts and breakage)', 'rawnaq' ), 't' => 'pct', 'd' => 10 ],
			],
		],
		'paint'    => [
			'label'  => __( 'Paint', 'rawnaq' ),
			'hint'   => __( 'Litres of paint for the walls of a room, with or without the ceiling.', 'rawnaq' ),
			'fields' => [
				[ 'k' => 'length', 'label' => __( 'Room length', 'rawnaq' ), 't' => 'len', 'd' => 4 ],
				[ 'k' => 'width', 'label' => __( 'Room width', 'rawnaq' ), 't' => 'len', 'd' => 3.5 ],
				[ 'k' => 'height', 'label' => __( 'Ceiling height', 'rawnaq' ), 't' => 'len', 'd' => 3 ],
				[ 'k' => 'open', 'label' => __( 'Doors and windows (total area)', 'rawnaq' ), 't' => 'area', 'd' => 3 ],
				[
					'k'       => 'surfaces',
					'label'   => __( 'Surfaces to paint', 'rawnaq' ),
					't'       => 'select',
					'd'       => 'walls',
					'options' => [
						'walls'         => __( 'Walls only', 'rawnaq' ),
						'walls_ceiling' => __( 'Walls and ceiling', 'rawnaq' ),
					],
				],
				[ 'k' => 'coats', 'label' => __( 'Coats', 'rawnaq' ), 't' => 'num', 'd' => 2 ],
				[ 'k' => 'cover', 'label' => __( 'Coverage of the paint (one coat)', 'rawnaq' ), 't' => 'cov', 'd' => 10 ],
				[ 'k' => 'waste', 'label' => __( 'Extra for touch-ups', 'rawnaq' ), 't' => 'pct', 'd' => 5 ],
			],
		],
		'rebar'    => [
			'label'  => __( 'Steel bars (rebar)', 'rawnaq' ),
			'hint'   => __( 'Weight of reinforcement bars from diameter, number and length.', 'rawnaq' ),
			'fields' => [
				[
					'k'       => 'dia',
					'label'   => __( 'Bar diameter (mm)', 'rawnaq' ),
					't'       => 'select',
					'd'       => '12',
					'options' => [
						'8'  => '8 mm',
						'10' => '10 mm',
						'12' => '12 mm',
						'16' => '16 mm',
						'20' => '20 mm',
						'25' => '25 mm',
						'32' => '32 mm',
					],
				],
				[ 'k' => 'count', 'label' => __( 'Number of bars', 'rawnaq' ), 't' => 'num', 'd' => 100 ],
				[ 'k' => 'len', 'label' => __( 'Length of each bar', 'rawnaq' ), 't' => 'len', 'd' => 12 ],
				[ 'k' => 'waste', 'label' => __( 'Laps and cutting waste', 'rawnaq' ), 't' => 'pct', 'd' => 5 ],
			],
		],
	];
}

/**
 * Sanitise module configuration.
 *
 * @param array $raw Unsanitised settings.
 * @return array
 */
function rawnaq_mc_cfg_sanitize( array $raw ): array {
	$all = array_keys( rawnaq_mc_calculators() );

	$list = $raw['calculators'] ?? $all;
	if ( is_string( $list ) ) {
		$list = array_filter( array_map( 'trim', explode( ',', $list ) ) );
	}
	$list = array_values( array_intersect( $all, array_map( 'sanitize_key', (array) $list ) ) );
	if ( ! $list ) {
		$list = $all;
	}

	$unit = sanitize_key( $raw['unit'] ?? 'metric' );
	if ( ! in_array( $unit, [ 'metric', 'imperial' ], true ) ) {
		$unit = 'metric';
	}

	$accent = sanitize_hex_color( $raw['accent'] ?? '' );

	return [
		'calculators' => $list,
		'unit'        => $unit,
		'title'       => sanitize_text_field( $raw['title'] ?? __( 'Material quantity calculator', 'rawnaq' ) ),
		'intro'       => sanitize_text_field( $raw['intro'] ?? __( 'Quick estimates for concrete, bricks, tiles, paint and steel.', 'rawnaq' ) ),
		'showCta'     => ! empty( $raw['showCta'] ),
		'ctaText'     => sanitize_text_field( $raw['ctaText'] ?? __( 'Get a quote for this project', 'rawnaq' ) ),
		'ctaUrl'      => esc_url_raw( $raw['ctaUrl'] ?? '' ),
		'accent'      => $accent ? $accent : '',
	];
}

/**
 * Render one field.
 *
 * @param string $uid   Instance id.
 * @param string $calc  Calculator key.
 * @param array  $f     Field definition.
 */
function rawnaq_mc_field( $uid, $calc, $f ) {
	$id    = $uid . '-' . $calc . '-' . $f['k'];
	$units = [
		'len'  => 'm',
		'cm'   => 'cm',
		'mm'   => 'mm',
		'area' => 'm²',
		'cov'  => 'm²/L',
		'pct'  => '%',
	];
	?>
	<div class="rawnaq-mc-field">
		<label for="<?php echo esc_attr( $id ); ?>">
			<?php echo esc_html( $f['label'] ); ?>
			<?php if ( isset( $units[ $f['t'] ] ) ) : ?>
				<em class="rawnaq-mc-unit" data-unit="<?php echo esc_attr( $f['t'] ); ?>">(<?php echo esc_html( $units[ $f['t'] ] ); ?>)</em>
			<?php endif; ?>
		</label>
		<?php if ( 'select' === $f['t'] ) : ?>
			<select id="<?php echo esc_attr( $id ); ?>" data-k="<?php echo esc_attr( $f['k'] ); ?>" data-t="select">
				<?php foreach ( $f['options'] as $val => $txt ) : ?>
					<option value="<?php echo esc_attr( (string) $val ); ?>"<?php selected( (string) $f['d'], (string) $val ); ?>><?php echo esc_html( $txt ); ?></option>
				<?php endforeach; ?>
			</select>
		<?php else : ?>
			<input type="number" id="<?php echo esc_attr( $id ); ?>" inputmode="decimal" min="0" step="any"
				data-k="<?php echo esc_attr( $f['k'] ); ?>" data-t="<?php echo esc_attr( $f['t'] ); ?>"
				value="<?php echo esc_attr( (string) $f['d'] ); ?>" />
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render the calculator.
 *
 * @param array $cfg           Sanitised config.
 * @param array $style_vars    CSS custom properties.
 * @param array $wrapper_attrs Extra wrapper attributes (class, id).
 * @return string
 */
function rawnaq_mc_markup( array $cfg, array $style_vars = [], array $wrapper_attrs = [] ): string {
	$defs = rawnaq_mc_calculators();
	$uid  = 'rwmc-' . wp_unique_id();

	if ( ! empty( $cfg['accent'] ) && empty( $style_vars['--mc-accent'] ) ) {
		$style_vars['--mc-accent'] = $cfg['accent'];
	}
	$style = '';
	foreach ( $style_vars as $prop => $val ) {
		if ( '' !== (string) $val && preg_match( '/^--[a-z0-9-]+$/', $prop ) ) {
			$style .= $prop . ':' . esc_attr( $val ) . ';';
		}
	}

	$class = 'rawnaq-mc' . ( ! empty( $wrapper_attrs['class'] ) ? ' ' . $wrapper_attrs['class'] : '' );
	$first = $cfg['calculators'][0];

	ob_start();
	?>
	<div class="<?php echo esc_attr( $class ); ?>" data-rawnaq-mc data-unit="<?php echo esc_attr( $cfg['unit'] ); ?>"<?php echo $style ? ' style="' . $style . '"' : ''; ?>>
		<?php if ( $cfg['title'] || $cfg['intro'] ) : ?>
			<div class="rawnaq-mc-head">
				<?php if ( $cfg['title'] ) : ?>
					<h3 class="rawnaq-mc-title"><?php echo esc_html( $cfg['title'] ); ?></h3>
				<?php endif; ?>
				<?php if ( $cfg['intro'] ) : ?>
					<p class="rawnaq-mc-intro"><?php echo esc_html( $cfg['intro'] ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="rawnaq-mc-bar">
			<div class="rawnaq-mc-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Calculator type', 'rawnaq' ); ?>">
				<?php foreach ( $cfg['calculators'] as $key ) : ?>
					<button type="button" role="tab" class="rawnaq-mc-tab" id="<?php echo esc_attr( $uid . '-tab-' . $key ); ?>"
						aria-controls="<?php echo esc_attr( $uid . '-panel-' . $key ); ?>"
						aria-selected="<?php echo $key === $first ? 'true' : 'false'; ?>"
						tabindex="<?php echo $key === $first ? '0' : '-1'; ?>" data-calc="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $defs[ $key ]['label'] ); ?></button>
				<?php endforeach; ?>
			</div>
			<div class="rawnaq-mc-units" role="group" aria-label="<?php esc_attr_e( 'Units', 'rawnaq' ); ?>">
				<button type="button" class="rawnaq-mc-unit-btn" data-set-unit="metric" aria-pressed="<?php echo 'metric' === $cfg['unit'] ? 'true' : 'false'; ?>"><?php esc_html_e( 'Metric', 'rawnaq' ); ?></button>
				<button type="button" class="rawnaq-mc-unit-btn" data-set-unit="imperial" aria-pressed="<?php echo 'imperial' === $cfg['unit'] ? 'true' : 'false'; ?>"><?php esc_html_e( 'Feet / inches', 'rawnaq' ); ?></button>
			</div>
		</div>

		<?php foreach ( $cfg['calculators'] as $key ) : ?>
			<div class="rawnaq-mc-panel" role="tabpanel" id="<?php echo esc_attr( $uid . '-panel-' . $key ); ?>"
				aria-labelledby="<?php echo esc_attr( $uid . '-tab-' . $key ); ?>" data-calc="<?php echo esc_attr( $key ); ?>"<?php echo $key === $first ? '' : ' hidden'; ?>>
				<p class="rawnaq-mc-hint"><?php echo esc_html( $defs[ $key ]['hint'] ); ?></p>
				<div class="rawnaq-mc-body">
					<div class="rawnaq-mc-fields">
						<?php foreach ( $defs[ $key ]['fields'] as $f ) : ?>
							<?php rawnaq_mc_field( $uid, $key, $f ); ?>
						<?php endforeach; ?>
					</div>
					<div class="rawnaq-mc-result" aria-live="polite"></div>
				</div>
			</div>
		<?php endforeach; ?>

		<p class="rawnaq-mc-note"><?php esc_html_e( 'Estimates only. Quantities depend on site conditions and the materials you use, so confirm with your engineer or supplier before ordering.', 'rawnaq' ); ?></p>

		<?php if ( $cfg['showCta'] && $cfg['ctaUrl'] ) : ?>
			<a class="rawnaq-mc-cta" href="<?php echo esc_url( $cfg['ctaUrl'] ); ?>"><?php echo esc_html( $cfg['ctaText'] ); ?></a>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
