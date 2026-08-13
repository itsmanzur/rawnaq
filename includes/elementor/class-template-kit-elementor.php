<?php
/**
 * Starter Sections — Elementor integration.
 *
 * Adds a "Starter Sections" button to the Elementor editor panel, enqueues
 * the popup UI script and styles, and localises the nonce + template list so the JS
 * popup can render template cards without an extra AJAX round-trip.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_Template_Kit_Elementor {

	public function __construct() {
		add_action( 'elementor/editor/after_enqueue_styles',  [ $this, 'enqueue_popup_styles' ] );
		add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'enqueue_popup_scripts' ] );
		add_action( 'elementor/editor/footer',               [ $this, 'render_popup_markup' ] );
	}

	/** Enqueue popup CSS in Elementor editor context. */
	public function enqueue_popup_styles(): void {
		wp_enqueue_style(
			'rawnaq-admin',
			rawnaq_asset_url( 'css/admin.css' ),
			[],
			RAWNAQ_VERSION
		);
	}

	/** Enqueue the popup JS + localize data. */
	public function enqueue_popup_scripts(): void {
		wp_enqueue_script(
			'rawnaq-template-kit-popup',
			rawnaq_asset_url( 'js/template-kit-popup.js' ),
			[ 'wp-i18n', 'jquery' ],
			RAWNAQ_VERSION,
			true
		);

		wp_localize_script(
			'rawnaq-template-kit-popup',
			'rawnaqTemplateKit',
			[
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'rawnaq_template_kit' ),
				'templates' => Rawnaq_Template_Kit::get_registry_with_status(),
				'page_kits' => Rawnaq_Template_Kit::get_page_kit_registry_with_status(),
				'i18n'      => [
					'title'          => __( 'Rawnaq Starter Sections', 'rawnaq' ),
					'insert'         => __( 'Insert Section', 'rawnaq' ),
					'inserting'      => __( 'Inserting…', 'rawnaq' ),
					'moduleDisabled' => __( 'Required module is disabled. Enable it first in Rawnaq → Elements Manager.', 'rawnaq' ),
					'insertError'    => __( 'Could not insert template. Please try again.', 'rawnaq' ),
					'all'            => __( 'All', 'rawnaq' ),
				],
			]
		);
	}

	/** Render the hidden popup container the JS will populate. */
	public function render_popup_markup(): void {
		?>
		<div id="rawnaq-tk-popup" class="rawnaq-tk-popup" style="display:none;" aria-modal="true" role="dialog" aria-label="<?php esc_attr_e( 'Rawnaq Starter Sections', 'rawnaq' ); ?>">
			<div class="rawnaq-tk-popup__inner">
				<div class="rawnaq-tk-popup__header">
					<span class="rawnaq-tk-popup__title"><?php esc_html_e( 'Rawnaq Starter Sections', 'rawnaq' ); ?></span>
					<div class="rawnaq-tk-popup__filters" id="rawnaq-tk-filters"></div>
					<button class="rawnaq-tk-popup__close" type="button" aria-label="<?php esc_attr_e( 'Close', 'rawnaq' ); ?>">&#x2715;</button>
				</div>
				<div class="rawnaq-tk-popup__grid" id="rawnaq-tk-grid"></div>
			</div>
		</div>
		<?php
	}
}
