<?php
/**
 * Rawnaq Divi Module: Floating Dock & Fast Action Bar
 *
 * @package Rawnaq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_ET_Floating_Dock extends ET_Builder_Module {

	public $slug       = 'rawnaq_et_floating_dock';
	public $vb_support = 'on';

	public function init() {
		$this->name            = esc_html__( 'Rawnaq Floating Dock', 'rawnaq' );
		$this->icon_path       = 'admin-links';
		$this->main_css_element = '%%order_class%% .rawnaq-dock-container';
	}

	public function get_fields() {
		return [
			'dock_position' => [
				'label'           => esc_html__( 'Screen Dock Position', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => [
					'bottom' => esc_html__( 'Bottom Center (Mac Dock Style)', 'rawnaq' ),
					'right'  => esc_html__( 'Right Side / Floating Corner', 'rawnaq' ),
					'left'   => esc_html__( 'Left Side', 'rawnaq' ),
				],
				'default'         => 'bottom',
				'toggle_slug'     => 'main_content',
			],
			'whatsapp_mode' => [
				'label'           => esc_html__( 'Enable WhatsApp / Omni-Channel Contact Mode', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'off' => esc_html__( 'No (Classic Action Dock)', 'rawnaq' ),
					'on'  => esc_html__( 'Yes (WhatsApp Multi-Agent)', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'main_content',
			],
			'items_json' => [
				'label'           => esc_html__( 'Dock Action Items JSON', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'JSON array of items with label, icon, link, target, badge, and color', 'rawnaq' ),
				'default'         => '[{"label":"WhatsApp","icon":"dashicons-format-chat","link":"https://wa.me/15551234567","badge":"","color":"#25D366"},{"label":"Call Studio","icon":"dashicons-phone","link":"tel:+15551234567","badge":"","color":"#6366f1"},{"label":"Get Quote","icon":"dashicons-clipboard","link":"#rawnaq-get-quote","badge":"Fast","color":"#f59e0b"},{"label":"Scroll to Top","icon":"dashicons-arrow-up-alt2","link":"#top","badge":"","color":"#10b981"}]',
				'toggle_slug'     => 'items',
			],
			'wa_agents_json' => [
				'label'           => esc_html__( 'WhatsApp Agents JSON', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'JSON array of agents [{name, role, number, msg, avatar}]', 'rawnaq' ),
				'default'         => '[{"name":"Support Desk","role":"Customer Service","number":"15551234567","msg":"Hello! I have a question about your services."},{"name":"Sales Team","role":"Commercial Inquiries","number":"15559876543","msg":"Hi! I would like to get a quote."}]',
				'toggle_slug'     => 'items',
			],
			'enable_magnify' => [
				'label'           => esc_html__( 'Enable Mac OS Fisheye Magnification', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'on',
				'toggle_slug'     => 'style',
			],
			'max_scale' => [
				'label'           => esc_html__( 'Magnification Max Scale Factor', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'layout',
				'default'         => '1.6',
				'range_settings'  => [
					'min'  => 1.1,
					'max'  => 2.2,
					'step' => 0.05,
				],
				'toggle_slug'     => 'style',
			],
			'item_size' => [
				'label'           => esc_html__( 'Item Base Size (px)', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'layout',
				'default'         => '48',
				'range_settings'  => [
					'min'  => 32,
					'max'  => 72,
					'step' => 2,
				],
				'toggle_slug'     => 'style',
			],
			'dock_bg' => [
				'label'        => esc_html__( 'Dock Glass Background', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => 'rgba(255, 255, 255, 0.55)',
				'toggle_slug'  => 'style',
			],
			'dock_border' => [
				'label'        => esc_html__( 'Dock Glass Border', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => 'rgba(255, 255, 255, 0.5)',
				'toggle_slug'  => 'style',
			],
			'dock_blur' => [
				'label'           => esc_html__( 'Glass Backdrop Blur (px)', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'layout',
				'default'         => '16',
				'range_settings'  => [
					'min'  => 0,
					'max'  => 40,
					'step' => 1,
				],
				'toggle_slug'     => 'style',
			],
			'dock_radius' => [
				'label'           => esc_html__( 'Dock Border Radius (px)', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'layout',
				'default'         => '24',
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
		wp_enqueue_style( 'rawnaq-floating-dock' );
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_script( 'rawnaq-floating-dock' );

		$position      = sanitize_html_class( $this->props['dock_position'] ?? 'bottom' );
		if ( ! in_array( $position, [ 'bottom', 'left', 'right' ], true ) ) {
			$position = 'bottom';
		}

		$is_wa_mode    = ( $this->props['whatsapp_mode'] ?? 'off' ) === 'on';
		if ( $is_wa_mode ) {
			wp_enqueue_script( 'rawnaq-qrcode' );
		}

		$magnify       = ( $this->props['enable_magnify'] ?? 'on' ) === 'on';
		$max_scale     = floatval( $this->props['max_scale'] ?? 1.6 );
		$item_size     = intval( $this->props['item_size'] ?? 48 );
		$dock_bg       = $this->props['dock_bg'] ?? 'rgba(255, 255, 255, 0.55)';
		$dock_border   = $this->props['dock_border'] ?? 'rgba(255, 255, 255, 0.5)';
		$dock_blur     = intval( $this->props['dock_blur'] ?? 16 );
		$dock_radius   = intval( $this->props['dock_radius'] ?? 24 );

		$raw_items     = $this->props['items_json'] ?? '';
		$items         = json_decode( $raw_items, true ) ?: [];

		$classes = [ 'rawnaq-dock-container', 'pos-' . $position ];
		if ( $is_wa_mode ) {
			$classes[] = 'rawnaq-whatsapp-dock-mode';
		}

		$style_parts = [
			'--dock-bg: ' . esc_attr( $dock_bg ),
			'--dock-border: ' . esc_attr( $dock_border ),
			'--dock-blur: ' . esc_attr( (string) $dock_blur ) . 'px',
			'--dock-radius: ' . esc_attr( (string) $dock_radius ) . 'px',
			'--dock-item-size: ' . esc_attr( (string) $item_size ) . 'px',
			'--dock-offset: 20px',
		];
		$style = implode( ';', $style_parts ) . ';';

		$wa_attr = '';
		if ( $is_wa_mode ) {
			$agents_raw = json_decode( $this->props['wa_agents_json'] ?? '[]', true ) ?: [];
			$agents     = [];
			foreach ( $agents_raw as $agent ) {
				$agents[] = [
					'name'   => sanitize_text_field( $agent['name'] ?? '' ),
					'role'   => sanitize_text_field( $agent['role'] ?? '' ),
					'number' => sanitize_text_field( $agent['number'] ?? '' ) ?: ( function_exists( 'rawnaq_get_default_wa_number' ) ? rawnaq_get_default_wa_number() : '' ),
					'avatar' => ! empty( $agent['avatar'] ) ? esc_url_raw( $agent['avatar'] ) : '',
					'msg'    => sanitize_textarea_field( $agent['msg'] ?? '' ),
				];
			}
			$wa_cfg = [
				'whatsappMode'     => true,
				'primaryChannel'   => 'whatsapp',
				'agents'           => $agents,
				'defaultMsg'       => '',
				'pageContext'      => function_exists( 'rawnaq_get_wa_page_context' ) ? rawnaq_get_wa_page_context() : [],
				'secCall'          => '',
				'secMessenger'     => '',
				'secEmail'         => '',
				'secTelegram'      => '',
				'timezone'         => 'Asia/Dhaka',
				'schedule'         => [],
				'offHoursBehavior' => 'offline_badge',
				'offHoursRedirect' => '',
				'offHoursEmail'    => '',
				'offHoursFormNote' => '',
				'qrFallback'       => true,
				'desktopAction'    => 'choice',
				'triggerDelay'     => 0,
				'triggerScroll'    => 0,
				'greetingText'     => '',
				'trackClicks'      => true,
			];
			$wa_attr = rawurlencode( wp_json_encode( $wa_cfg ) );
		}

		ob_start();
		?>
		<nav class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			 style="<?php echo esc_attr( $style ); ?>"
			 aria-label="<?php echo esc_attr__( 'Floating dock', 'rawnaq' ); ?>"
			 data-magnify="<?php echo $magnify ? '1' : '0'; ?>"
			 data-max-scale="<?php echo esc_attr( (string) $max_scale ); ?>"
			 data-base-size="<?php echo esc_attr( (string) $item_size ); ?>"
			 data-track-clicks="1"
			 <?php if ( $wa_attr ) : ?>
			 data-wa-dock="<?php echo esc_attr( $wa_attr ); ?>"
			 <?php endif; ?>>
			<?php if ( ! $is_wa_mode ) : ?>
				<?php foreach ( $items as $item ) :
					$label     = $item['label'] ?? ( $item['title'] ?? '' );
					$icon      = $item['icon'] ?? 'dashicons-admin-generic';
					$link      = ! empty( $item['link'] ) ? $item['link'] : '#';
					$target    = ( isset( $item['target'] ) && $item['target'] === '_blank' ) ? '_blank' : '_self';
					$rel_value = $target === '_blank' ? 'noopener' : '';
					$badge     = trim( (string) ( $item['badge'] ?? '' ) );
					$color     = sanitize_hex_color( $item['color'] ?? '' ) ?: '#6366f1';
					?>
					<a class="rawnaq-dock-item"
					   href="<?php echo esc_url( $link ); ?>"
					   target="<?php echo esc_attr( $target ); ?>"<?php if ( $rel_value ) : ?> rel="<?php echo esc_attr( $rel_value ); ?>"<?php endif; ?>
					   style="--hover-color: <?php echo esc_attr( $color ); ?>;"
					   aria-label="<?php echo esc_attr( $label ); ?>">
						<span class="rawnaq-dock-icon"><span class="dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span></span>
						<?php if ( $badge !== '' ) : ?>
							<span class="rawnaq-dock-badge"><?php echo esc_html( $badge ); ?></span>
						<?php endif; ?>
						<?php if ( $label ) : ?>
							<span class="rawnaq-dock-tooltip"><?php echo esc_html( $label ); ?></span>
							<span class="rawnaq-dock-mobile-label"><?php echo esc_html( $label ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			<?php endif; ?>
		</nav>
		<?php
		return ob_get_clean();
	}
}
