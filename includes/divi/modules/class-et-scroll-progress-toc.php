<?php
/**
 * Rawnaq Divi Module: Scroll Progress Table of Contents
 *
 * @package Rawnaq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rawnaq_ET_Scroll_Progress_TOC extends ET_Builder_Module {

	public $slug       = 'rawnaq_et_scroll_progress_toc';
	public $vb_support = 'on';

	public function init() {
		$this->name       = esc_html__( 'Rawnaq Scroll Progress & TOC', 'rawnaq' );
		$this->icon_path  = 'list-view';
		$this->main_css_element = '%%order_class%%.rawnaq-spt';
	}

	public function get_fields() {
		return [
			'progress' => [
				'label'           => esc_html__( 'Progress Style', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'Fixed progress bar and scroll ring are displayed once per page. If multiple widgets are added, the first instance takes precedence.', 'rawnaq' ),
				'options'         => [
					'both' => esc_html__( 'Bar + Ring', 'rawnaq' ),
					'bar'  => esc_html__( 'Top / Bottom Bar', 'rawnaq' ),
					'ring' => esc_html__( 'Circular Ring', 'rawnaq' ),
					'none' => esc_html__( 'None', 'rawnaq' ),
				],
				'default'         => 'both',
				'toggle_slug'     => 'main_content',
			],
			'bar_position' => [
				'label'           => esc_html__( 'Bar Position', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => [
					'top'    => esc_html__( 'Top', 'rawnaq' ),
					'bottom' => esc_html__( 'Bottom', 'rawnaq' ),
				],
				'default'         => 'top',
				'toggle_slug'     => 'main_content',
			],
			'progress_target' => [
				'label'           => esc_html__( 'Progress based on', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => [
					'page'    => esc_html__( 'Whole Page', 'rawnaq' ),
					'article' => esc_html__( 'Article / Content Area', 'rawnaq' ),
					'custom'  => esc_html__( 'Custom CSS Selector', 'rawnaq' ),
				],
				'default'         => 'page',
				'toggle_slug'     => 'main_content',
			],
			'progress_selector' => [
				'label'           => esc_html__( 'Progress Selector', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
				'toggle_slug'     => 'main_content',
			],
			'toc_position' => [
				'label'           => esc_html__( 'TOC Position', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => [
					'sticky'   => esc_html__( 'Sidebar Sticky', 'rawnaq' ),
					'floating' => esc_html__( 'Floating Panel', 'rawnaq' ),
					'inline'   => esc_html__( 'Inline Box', 'rawnaq' ),
					'none'     => esc_html__( 'Hidden (Progress only)', 'rawnaq' ),
				],
				'default'         => 'sticky',
				'toggle_slug'     => 'main_content',
			],
			'toc_title' => [
				'label'           => esc_html__( 'TOC Heading', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => 'Contents',
				'toggle_slug'     => 'main_content',
			],
			'source' => [
				'label'           => esc_html__( 'TOC Source', 'rawnaq' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => [
					'auto'   => esc_html__( 'Auto-detect headings', 'rawnaq' ),
					'manual' => esc_html__( 'Manual entries', 'rawnaq' ),
				],
				'default'         => 'auto',
				'toggle_slug'     => 'main_content',
			],
			'levels' => [
				'label'           => esc_html__( 'Heading Levels', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'default'         => 'h2,h3',
				'description'     => esc_html__( 'Comma-separated heading levels, e.g. h2,h3,h4', 'rawnaq' ),
				'toggle_slug'     => 'settings',
			],
			'manual_items' => [
				'label'           => esc_html__( 'Manual Entries', 'rawnaq' ),
				'type'            => 'textarea',
				'option_category' => 'configuration',
				'default'         => '',
				'description'     => esc_html__( 'One item per line: Title|anchor|level (e.g. Introduction|intro|2)', 'rawnaq' ),
				'toggle_slug'     => 'settings',
			],
			'target_selector' => [
				'label'           => esc_html__( 'Content Scope (CSS Selector)', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'default'         => '.entry-content, main, article, .et_pb_section',
				'toggle_slug'     => 'settings',
			],
			'collapse_subs' => [
				'label'           => esc_html__( 'Collapse Sub-headings', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'settings',
			],
			'scroll_offset' => [
				'label'           => esc_html__( 'Scroll Offset (px)', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'configuration',
				'default'         => '80',
				'range_settings'  => [
					'min'  => '0',
					'max'  => '400',
					'step' => '5',
				],
				'toggle_slug'     => 'settings',
			],
			'reading_time' => [
				'label'           => esc_html__( 'Show Reading Time', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'on',
				'toggle_slug'     => 'settings',
			],
			'show_search' => [
				'label'           => esc_html__( 'Show Search Filter', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'settings',
			],
			'toc_collapsible' => [
				'label'           => esc_html__( 'Collapsible Accordion Header', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'on',
				'toggle_slug'     => 'settings',
			],
			'url_hash_sync' => [
				'label'           => esc_html__( 'Sync URL Hash on Scroll', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'settings',
			],
			'click_to_top' => [
				'label'           => esc_html__( 'Click Ring for Back to Top', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'on',
				'toggle_slug'     => 'settings',
			],
			'dock_attach' => [
				'label'           => esc_html__( 'Attach to Floating Dock', 'rawnaq' ),
				'type'            => 'yes_no_button',
				'options'         => [
					'on'  => esc_html__( 'Yes', 'rawnaq' ),
					'off' => esc_html__( 'No', 'rawnaq' ),
				],
				'default'         => 'off',
				'toggle_slug'     => 'settings',
			],
			'sync_timeline' => [
				'label'           => esc_html__( 'Sync Timeline Chapter ID', 'rawnaq' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'default'         => '',
				'toggle_slug'     => 'settings',
			],
			'ring_size' => [
				'label'           => esc_html__( 'Progress Ring Size (px)', 'rawnaq' ),
				'type'            => 'range',
				'option_category' => 'configuration',
				'default'         => '56',
				'range_settings'  => [
					'min'  => '40',
					'max'  => '96',
					'step' => '2',
				],
				'toggle_slug'     => 'style',
			],
			'accent_color' => [
				'label'        => esc_html__( 'Accent Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#FBBF24',
				'toggle_slug'  => 'style',
			],
			'accent_deep' => [
				'label'        => esc_html__( 'Active / Track Color', 'rawnaq' ),
				'type'         => 'color-alpha',
				'custom_color' => true,
				'default'      => '#4338CA',
				'toggle_slug'  => 'style',
			],
		];
	}

	public function render( $attrs, $content = null, $render_slug = '' ) {
		wp_enqueue_style( 'rawnaq-scroll-progress-toc' );
		wp_enqueue_script( 'rawnaq-scroll-progress-toc' );

		$raw = [
			'progress'           => $this->props['progress'] ?? 'both',
			'barPosition'        => $this->props['bar_position'] ?? 'top',
			'progressTarget'     => $this->props['progress_target'] ?? 'page',
			'progressSelector'   => $this->props['progress_selector'] ?? '',
			'showPercent'        => true,
			'clickToTop'         => ( $this->props['click_to_top'] ?? 'on' ) === 'on',
			'tocPosition'        => $this->props['toc_position'] ?? 'sticky',
			'tocTitle'           => $this->props['toc_title'] ?? 'Contents',
			'tocCollapsible'     => ( $this->props['toc_collapsible'] ?? 'on' ) === 'on',
			'source'             => $this->props['source'] ?? 'auto',
			'levels'             => $this->props['levels'] ?? 'h2,h3',
			'manual'             => $this->props['manual_items'] ?? '',
			'collapseSubs'       => ( $this->props['collapse_subs'] ?? 'off' ) === 'on',
			'showSearch'         => ( $this->props['show_search'] ?? 'off' ) === 'on',
			'urlHashSync'        => ( $this->props['url_hash_sync'] ?? 'off' ) === 'on',
			'sectionReadingTime' => false,
			'smooth'             => true,
			'scrollOffset'       => isset( $this->props['scroll_offset'] ) ? (int) $this->props['scroll_offset'] : 80,
			'readingTime'        => ( $this->props['reading_time'] ?? 'on' ) === 'on',
			'mobileCollapse'     => true,
			'dockAttach'         => ( $this->props['dock_attach'] ?? 'off' ) === 'on',
			'syncTimeline'       => $this->props['sync_timeline'] ?? '',
			'scope'              => $this->props['target_selector'] ?? '',
			'hideIfShort'        => true,
			'ringSize'           => isset( $this->props['ring_size'] ) ? (int) $this->props['ring_size'] : 56,
		];

		$cfg = function_exists( 'rawnaq_spt_cfg_sanitize' ) ? rawnaq_spt_cfg_sanitize( $raw ) : $raw;

		$accent_color = sanitize_hex_color( $this->props['accent_color'] ?? '#FBBF24' ) ?: '#FBBF24';
		$accent_deep  = sanitize_hex_color( $this->props['accent_deep'] ?? '#4338CA' ) ?: '#4338CA';
		$style_vars   = [
			'--spt-accent'      => $accent_color,
			'--spt-accent-deep' => $accent_deep,
		];

		if ( function_exists( 'rawnaq_spt_markup' ) ) {
			return rawnaq_spt_markup( $cfg, $style_vars );
		}

		return '';
	}
}
