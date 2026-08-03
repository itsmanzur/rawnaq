<?php
/**
 * Starter Sections — Gutenberg integration.
 *
 * Registers WordPress Block Patterns for each bundled template so
 * editors can insert them via the patterns inserter (⊞ → Patterns → Rawnaq).
 * Also provides an AJAX endpoint that returns the raw PHP block markup.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_Template_Kit_Gutenberg {

	public function __construct() {
		add_action( 'init', [ $this, 'register_pattern_category' ] );
		add_action( 'init', [ $this, 'register_patterns' ], 20 );
	}

	/** Register the "Rawnaq Starter Sections" pattern category. */
	public function register_pattern_category(): void {
		register_block_pattern_category(
			'rawnaq-starter',
			[ 'label' => __( 'Rawnaq Starter Sections', 'rawnaq' ) ]
		);
	}

	/** Register one Block Pattern per bundled template. */
	public function register_patterns(): void {
		$registry = Rawnaq_Template_Kit::get_registry();

		foreach ( $registry as $tpl ) {
			$php_path = Rawnaq_Template_Kit::TEMPLATES_DIR . 'gutenberg/' . sanitize_file_name( $tpl['id'] ) . '.php';
			if ( ! file_exists( $php_path ) ) {
				continue;
			}

			// Check required modules before registering.
			$all_active = true;
			foreach ( $tpl['required_modules'] as $slug ) {
				if ( ! rawnaq_is_module_enabled( $slug ) ) {
					$all_active = false;
					break;
				}
			}

			$content = $this->get_pattern_content( $php_path );

			register_block_pattern(
				'rawnaq/' . $tpl['id'],
				[
					'title'         => $tpl['title'],
					'description'   => sprintf(
						/* translators: %s: template title */
						__( 'Rawnaq Starter Section: %s', 'rawnaq' ),
						$tpl['title']
					),
					'categories'    => [ 'rawnaq-starter' ],
					'content'       => $content,
					/* Grey out the card in the UI with a notice if modules are missing. */
					'inserterSync'  => $all_active,
					'blockTypes'    => [],
					'viewportWidth' => 1200,
				]
			);
		}
	}

	/**
	 * Safely evaluate a pattern PHP file and return its output as a string.
	 *
	 * The file is expected to contain raw block HTML (not PHP logic); we
	 * still use include+ob so the file can optionally call helper functions.
	 *
	 * @param string $path Absolute path to the .php pattern file.
	 * @return string Block HTML markup.
	 */
	private function get_pattern_content( string $path ): string {
		ob_start();
		include $path; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		return (string) ob_get_clean();
	}
}
