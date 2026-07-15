<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rawnaq_Gutenberg_Loader {

    private $modules = [];

    public function __construct() {
        $this->modules = function_exists( 'rawnaq_get_modules' ) ? rawnaq_get_modules() : [];

        add_action( 'init', [ $this, 'register_blocks' ] );
        add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
    }

    public function register_blocks() {
        // Register editor scripts bundle
        // Register shared assets early so editor_script deps resolve.
        if ( class_exists( 'Rawnaq_Elements' ) ) {
            Rawnaq_Elements::get_instance()->register_shared_assets();
        }

        wp_register_script(
            'rawnaq-gutenberg-editor',
            RAWNAQ_URL . 'assets/js/gutenberg-editor.js',
            [
                'wp-blocks',
                'wp-element',
                'wp-components',
                'wp-block-editor',
                'rawnaq-hub-diagram',
                'rawnaq-flow-chart',
                'rawnaq-scroll-progress-toc',
            ],
            RAWNAQ_VERSION,
            true
        );

        // 1. Hub Diagram Block
        if ( rawnaq_is_module_enabled( 'hub-diagram' ) ) {
            register_block_type( 'rawnaq/hub-diagram', [
                'editor_script'   => 'rawnaq-gutenberg-editor',
                'editor_style'    => 'rawnaq-hub-diagram',
                'style'           => 'rawnaq-hub-diagram',
                'render_callback' => [ $this, 'render_hub_diagram_block' ],
                'attributes'      => [
                    'centerTitle'    => [ 'type' => 'string', 'default' => 'STUDY 2D & 3D' ],
                    'centerSubtitle' => [ 'type' => 'string', 'default' => "REVIEW WITH\nCLIENT" ],
                    'lineColor'      => [ 'type' => 'string', 'default' => '#c2c2c2' ],
                    'seg1Color'      => [ 'type' => 'string', 'default' => '#E8793A' ],
                    'seg2Color'      => [ 'type' => 'string', 'default' => '#D4A92A' ],
                    'seg3Color'      => [ 'type' => 'string', 'default' => '#26B8B8' ],
                    'cardShape'      => [ 'type' => 'string', 'default' => 'rect' ],
                    'lineStyle'      => [ 'type' => 'string', 'default' => 'solid' ],
                    'glowLines'      => [ 'type' => 'string', 'default' => 'no' ],
                    'centerStyle'    => [ 'type' => 'string', 'default' => 'conic' ],
                    'layoutFlow'     => [ 'type' => 'string', 'default' => 'horizontal' ],
                    'importJson'     => [ 'type' => 'string', 'default' => '' ],
                    'height'         => [ 'type' => 'number', 'default' => 540 ],
                    'topNodesJson'   => [ 'type' => 'string', 'default' => '[{"label":"Design","color":"#E8793A","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-art","link":"","target":"_self"},{"label":"P&ID","color":"#D4A92A","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-editor-justify","link":"","target":"_self"},{"label":"Sketch","color":"#26B8B8","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-welcome-write-blog","link":"","target":"_self"},{"label":"Specification","color":"#E8793A","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-clipboard","link":"","target":"_self"}]' ],
                    'botNodesJson'   => [ 'type' => 'string', 'default' => '[{"label":"MTO/BOQ","color":"#E8793A","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-list-view","link":"","target":"_self"},{"label":"3D CAD Model","color":"#D4A92A","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-format-image","link":"","target":"_self"},{"label":"Drawings","color":"#26B8B8","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-portfolio","link":"","target":"_self"},{"label":"Pipe Isometric","color":"#E8793A","cardBg":"#ffffff","cardColor":"#1a1a1a","icon":"dashicons-chart-area","link":"","target":"_self"}]' ],
                ]
            ] );
        }

        // 2. 3D Tilt Card Block
        if ( rawnaq_is_module_enabled( 'tilt-card' ) ) {
            register_block_type( 'rawnaq/tilt-card', [
                'editor_script'   => 'rawnaq-gutenberg-editor',
                'editor_style'    => 'rawnaq-tilt-card',
                'style'           => 'rawnaq-tilt-card',
                'render_callback' => [ $this, 'render_tilt_card_block' ],
                'attributes'      => [
                    'title'       => [ 'type' => 'string', 'default' => 'Creative Service' ],
                    'desc'        => [ 'type' => 'string', 'default' => 'We design premium, high-speed interfaces tailored to stand out.' ],
                    'icon'        => [ 'type' => 'string', 'default' => 'dashicons-star-filled' ],
                    'imageUrl'    => [ 'type' => 'string', 'default' => '' ],
                    'imageId'     => [ 'type' => 'number', 'default' => 0 ],
                    'imageAlt'    => [ 'type' => 'string', 'default' => '' ],
                    'badge'       => [ 'type' => 'string', 'default' => '' ],
                    'ctaText'     => [ 'type' => 'string', 'default' => 'Learn more' ],
                    'ctaLink'     => [ 'type' => 'string', 'default' => '' ],
                    'link'        => [ 'type' => 'string', 'default' => '' ],
                    'target'      => [ 'type' => 'string', 'default' => '_self' ],
                    'maxTilt'     => [ 'type' => 'number', 'default' => 15 ],
                    'contentAlign'=> [ 'type' => 'string', 'default' => 'bottom' ],
                    'overlay'     => [ 'type' => 'number', 'default' => 0.7 ],
                    'glare'       => [ 'type' => 'number', 'default' => 0.45 ],
                    'hoverScale'  => [ 'type' => 'number', 'default' => 1.03 ],
                    'radius'       => [ 'type' => 'number', 'default' => 20 ],
                    'height'       => [ 'type' => 'number', 'default' => 380 ],
                    'cardBg'       => [ 'type' => 'string', 'default' => '#ffffff' ],
                    'badgeBg'      => [ 'type' => 'string', 'default' => '#6366f1' ],
                    'badgeColor'   => [ 'type' => 'string', 'default' => '#ffffff' ],
                    'titleColor'   => [ 'type' => 'string', 'default' => '' ],
                    'descColor'    => [ 'type' => 'string', 'default' => '' ],
                    'iconColor'    => [ 'type' => 'string', 'default' => '' ],
                    'btnBg'        => [ 'type' => 'string', 'default' => '#6366f1' ],
                    'btnColor'     => [ 'type' => 'string', 'default' => '#ffffff' ],
                ]
            ] );
        }

        // 3. Scroll Timeline Block
        if ( rawnaq_is_module_enabled( 'scroll-timeline' ) ) {
            register_block_type( 'rawnaq/scroll-timeline', [
                'editor_script'   => 'rawnaq-gutenberg-editor',
                'editor_style'    => 'rawnaq-scroll-timeline',
                'style'           => 'rawnaq-scroll-timeline',
                'render_callback' => [ $this, 'render_scroll_timeline_block' ],
                'attributes'      => [
                    'stepsJson'   => [
                        'type'    => 'string',
                        'default' => '[{"meta":"Phase 1","title":"Ideation & Sketching","desc":"Gather initial ideas and draft blueprints.","icon":"","imageUrl":"","imageId":0,"ctaText":"","ctaLink":""},{"meta":"Phase 2","title":"Prototype Review","desc":"Interactive mockups and client reviews.","icon":"","imageUrl":"","imageId":0,"ctaText":"","ctaLink":""},{"meta":"Phase 3","title":"Development & Coding","desc":"Build, test, and deploy clean code.","icon":"","imageUrl":"","imageId":0,"ctaText":"","ctaLink":""}]',
                    ],
                    'layout'       => [ 'type' => 'string', 'default' => 'alternating' ],
                    'showNumbers'  => [ 'type' => 'boolean', 'default' => true ],
                    'lineBg'       => [ 'type' => 'string', 'default' => '#e2e8f0' ],
                    'lineActive'   => [ 'type' => 'string', 'default' => '#6366f1' ],
                    'bulletBorder' => [ 'type' => 'string', 'default' => '#cbd5e1' ],
                    'bulletActive' => [ 'type' => 'string', 'default' => '#6366f1' ],
                    'cardBg'       => [ 'type' => 'string', 'default' => '#ffffff' ],
                    'metaColor'    => [ 'type' => 'string', 'default' => '#6366f1' ],
                    'titleColor'   => [ 'type' => 'string', 'default' => '#1a1a1a' ],
                    'descColor'    => [ 'type' => 'string', 'default' => '#666666' ],
                    'ctaColor'     => [ 'type' => 'string', 'default' => '#6366f1' ],
                    'cardRadius'   => [ 'type' => 'number', 'default' => 16 ],
                    'bulletSize'   => [ 'type' => 'number', 'default' => 28 ],
                    'itemGap'      => [ 'type' => 'number', 'default' => 20 ],
                ]
            ] );
        }

        // 4. Floating Dock Block
        if ( rawnaq_is_module_enabled( 'floating-dock' ) ) {
            register_block_type( 'rawnaq/floating-dock', [
                'editor_script'   => 'rawnaq-gutenberg-editor',
                'editor_style'    => 'rawnaq-floating-dock',
                'style'           => 'rawnaq-floating-dock',
                'render_callback' => [ $this, 'render_floating_dock_block' ],
                'attributes'      => [
                    'position'     => [ 'type' => 'string', 'default' => 'bottom' ],
                    'itemsJson'    => [
                        'type'    => 'string',
                        'default' => '[{"label":"Home","icon":"dashicons-admin-home","link":"#","target":"_self","badge":"","color":"#6366f1"},{"label":"Messages","icon":"dashicons-email-alt","link":"#","target":"_self","badge":"3","color":"#6366f1"},{"label":"Settings","icon":"dashicons-admin-generic","link":"#","target":"_self","badge":"","color":"#6366f1"}]',
                    ],
                    'offset'       => [ 'type' => 'number', 'default' => 20 ],
                    'hideMobile'   => [ 'type' => 'boolean', 'default' => false ],
                    'mobileLabels' => [ 'type' => 'boolean', 'default' => false ],
                    'dockBg'       => [ 'type' => 'string', 'default' => 'rgba(255,255,255,0.55)' ],
                    'dockBorder'   => [ 'type' => 'string', 'default' => 'rgba(255,255,255,0.5)' ],
                    'dockBlur'     => [ 'type' => 'number', 'default' => 16 ],
                    'dockRadius'   => [ 'type' => 'number', 'default' => 24 ],
                    'dockGap'      => [ 'type' => 'number', 'default' => 12 ],
                    'dockPad'      => [ 'type' => 'number', 'default' => 10 ],
                    'itemBg'       => [ 'type' => 'string', 'default' => '#ffffff' ],
                    'iconColor'    => [ 'type' => 'string', 'default' => '#444444' ],
                    'itemSize'     => [ 'type' => 'number', 'default' => 48 ],
                    'itemRadius'   => [ 'type' => 'number', 'default' => 12 ],
                    'badgeBg'      => [ 'type' => 'string', 'default' => '#ef4444' ],
                    'badgeColor'   => [ 'type' => 'string', 'default' => '#ffffff' ],
                    'magnify'      => [ 'type' => 'boolean', 'default' => true ],
                    'maxScale'     => [ 'type' => 'number', 'default' => 1.6 ],
                ]
            ] );
        }

        // 5. Flow Chart Block
        if ( rawnaq_is_module_enabled( 'flow-chart' ) ) {
            register_block_type( 'rawnaq/flow-chart', [
                'editor_script'   => 'rawnaq-gutenberg-editor',
                'editor_style'    => 'rawnaq-flow-chart',
                'style'           => 'rawnaq-flow-chart',
                'render_callback' => [ $this, 'render_flow_chart_block' ],
                'attributes'      => [
                    'mode'      => [ 'type' => 'string', 'default' => 'org' ],
                    'connector' => [ 'type' => 'string', 'default' => 'curved' ],
                    'nodesJson' => [
                        'type'    => 'string',
                        'default' => '[{"id":"ceo","parent":"","title":"Founder / CEO","role":"Leadership","icon":"★","detail":"Leads the company.","link":"","decision":false},{"id":"eng","parent":"ceo","title":"Engineering","role":"Product","icon":"⚙","detail":"Engineering roadmap.","link":"","decision":false},{"id":"ops","parent":"ceo","title":"Operations","role":"Delivery","icon":"◆","detail":"Project delivery.","link":"","decision":false},{"id":"e1","parent":"eng","title":"Frontend","role":"Team","icon":"▪","detail":"UI work.","link":"","decision":false}]',
                    ],
                ],
            ] );
        }

        // 6. Scroll Progress + TOC Block
        if ( rawnaq_is_module_enabled( 'scroll-progress-toc' ) ) {
            register_block_type( 'rawnaq/scroll-progress-toc', [
                'editor_script'   => 'rawnaq-gutenberg-editor',
                'editor_style'    => 'rawnaq-scroll-progress-toc',
                'style'           => 'rawnaq-scroll-progress-toc',
                'render_callback' => [ $this, 'render_scroll_progress_toc_block' ],
                'attributes'      => [
                    'progress'       => [ 'type' => 'string', 'default' => 'both' ],
                    'barPosition'    => [ 'type' => 'string', 'default' => 'top' ],
                    'tocPosition'    => [ 'type' => 'string', 'default' => 'sticky' ],
                    'tocTitle'       => [ 'type' => 'string', 'default' => 'Contents' ],
                    'source'         => [ 'type' => 'string', 'default' => 'auto' ],
                    'levels'         => [ 'type' => 'string', 'default' => 'h2,h3' ],
                    'scrollOffset'   => [ 'type' => 'number', 'default' => 80 ],
                    'smooth'         => [ 'type' => 'boolean', 'default' => true ],
                    'readingTime'    => [ 'type' => 'boolean', 'default' => true ],
                    'showPercent'    => [ 'type' => 'boolean', 'default' => true ],
                    'mobileCollapse' => [ 'type' => 'boolean', 'default' => true ],
                    'manualJson'     => [ 'type' => 'string', 'default' => '[]' ],
                ],
            ] );
        }
    }

    public function enqueue_editor_assets() {
        // Ensure shared widget assets are registered before enqueue.
        if ( class_exists( 'Rawnaq_Elements' ) ) {
            Rawnaq_Elements::get_instance()->register_shared_assets();
        }

        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'rawnaq-fonts' );
        wp_enqueue_style( 'rawnaq-hub-diagram' );
        wp_enqueue_style( 'rawnaq-tilt-card' );
        wp_enqueue_style( 'rawnaq-scroll-timeline' );
        wp_enqueue_style( 'rawnaq-floating-dock' );
        wp_enqueue_style( 'rawnaq-flow-chart' );
        wp_enqueue_style( 'rawnaq-scroll-progress-toc' );
        wp_enqueue_script( 'rawnaq-hub-diagram' );
        wp_enqueue_script( 'rawnaq-flow-chart' );
    }

    // ── Render Callbacks ──

    public function render_hub_diagram_block( $attributes ) {
        wp_enqueue_style( 'rawnaq-fonts' );
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'rawnaq-hub-diagram' );
        wp_enqueue_script( 'rawnaq-hub-diagram' );

        $top = json_decode( $attributes['topNodesJson'], true ) ?: [];
        $bot = json_decode( $attributes['botNodesJson'], true ) ?: [];

        $map = function( $nodes ) {
            $out = [];
            foreach( $nodes as $i => $n ) {
                $out[] = [
                    'id'        => 'n' . $i,
                    'label'     => $n['label'] ?? '',
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
            'centerTitle'    => $attributes['centerTitle'],
            'centerSubtitle' => $attributes['centerSubtitle'],
            'lineColor'      => $attributes['lineColor'],
            'seg1Color'      => $attributes['seg1Color'],
            'seg2Color'      => $attributes['seg2Color'],
            'seg3Color'      => $attributes['seg3Color'],
            'cardShape'      => $attributes['cardShape'],
            'lineStyle'      => $attributes['lineStyle'],
            'glowLines'      => $attributes['glowLines'],
            'centerStyle'    => $attributes['centerStyle'],
            'layoutFlow'     => $attributes['layoutFlow'],
            'importJson'     => $attributes['importJson'],
            'top'            => $map( $top ),
            'bottom'         => $map( $bot ),
        ];

        $unique_id = 'gb-hub-' . wp_generate_uuid4();
        ob_start();
        ?>
        <div class="hub-diagram-host"
             id="<?php echo esc_attr( $unique_id ); ?>"
             style="height: <?php echo esc_attr( $attributes['height'] ); ?>px;"
             data-hub="<?php echo esc_attr( wp_json_encode( $cfg ) ); ?>">
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_tilt_card_block( $attributes ) {
        wp_enqueue_style( 'rawnaq-fonts' );
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'rawnaq-tilt-card' );
        wp_enqueue_script( 'rawnaq-tilt-card' );

        $a = wp_parse_args( $attributes, [
            'title'        => '',
            'desc'         => '',
            'icon'         => '',
            'imageUrl'     => '',
            'imageAlt'     => '',
            'badge'        => '',
            'ctaText'      => '',
            'ctaLink'      => '',
            'link'         => '',
            'target'       => '_self',
            'maxTilt'      => 15,
            'contentAlign' => 'bottom',
            'overlay'      => 0.7,
            'glare'        => 0.45,
            'hoverScale'   => 1.03,
            'radius'       => 20,
            'height'       => 380,
            'cardBg'       => '#ffffff',
            'badgeBg'      => '#6366f1',
            'badgeColor'   => '#ffffff',
            'titleColor'   => '',
            'descColor'    => '',
            'iconColor'    => '',
            'btnBg'        => '#6366f1',
            'btnColor'     => '#ffffff',
        ] );

        $has_image = ! empty( $a['imageUrl'] );
        $align     = sanitize_html_class( $a['contentAlign'] ?: 'bottom' );
        $classes   = [ 'rawnaq-tilt-card', 'align-' . $align ];
        if ( $has_image ) {
            $classes[] = 'has-image';
        }

        $cta_url   = ! empty( $a['ctaLink'] ) ? $a['ctaLink'] : $a['link'];
        $cta_text  = trim( (string) $a['ctaText'] );
        $target    = $a['target'] === '_blank' ? '_blank' : '_self';
        $rel_value = $target === '_blank' ? 'noopener' : '';

        $style_parts = [
            '--overlay:' . esc_attr( (string) $a['overlay'] ),
            '--glare:' . esc_attr( (string) $a['glare'] ),
            '--hover-scale:' . esc_attr( (string) $a['hoverScale'] ),
            'border-radius:' . esc_attr( (string) intval( $a['radius'] ) ) . 'px',
            'height:' . esc_attr( (string) intval( $a['height'] ) ) . 'px',
            '--tilt-card-bg:' . esc_attr( sanitize_hex_color( $a['cardBg'] ) ?: '#ffffff' ),
            '--tilt-badge-bg:' . esc_attr( sanitize_hex_color( $a['badgeBg'] ) ?: '#6366f1' ),
            '--tilt-badge-color:' . esc_attr( sanitize_hex_color( $a['badgeColor'] ) ?: '#ffffff' ),
            '--tilt-btn-bg:' . esc_attr( sanitize_hex_color( $a['btnBg'] ) ?: '#6366f1' ),
            '--tilt-btn-color:' . esc_attr( sanitize_hex_color( $a['btnColor'] ) ?: '#ffffff' ),
        ];
        foreach ( [ 'titleColor' => '--tilt-title', 'descColor' => '--tilt-desc', 'iconColor' => '--tilt-icon' ] as $key => $var ) {
            $hex = sanitize_hex_color( $a[ $key ] ?? '' );
            if ( $hex ) {
                $style_parts[] = $var . ':' . esc_attr( $hex );
            }
        }
        $style = implode( ';', $style_parts ) . ';';

        ob_start();
        ?>
        <div class="rawnaq-tilt-container">
            <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
                 style="<?php echo esc_attr( $style ); ?>"
                 data-tilt-max="<?php echo esc_attr( $a['maxTilt'] ); ?>"
                 data-hover-scale="<?php echo esc_attr( $a['hoverScale'] ); ?>"
                 data-glare="<?php echo esc_attr( $a['glare'] ); ?>">
                <?php if ( $has_image ) : ?>
                    <img class="rawnaq-tilt-image" src="<?php echo esc_url( $a['imageUrl'] ); ?>" alt="<?php echo esc_attr( $a['imageAlt'] ?: $a['title'] ); ?>" loading="lazy" />
                    <span class="rawnaq-tilt-overlay" aria-hidden="true"></span>
                <?php endif; ?>
                <span class="rawnaq-tilt-glare" aria-hidden="true"></span>
                <?php if ( ! empty( $a['badge'] ) ) : ?>
                    <span class="rawnaq-tilt-badge"><?php echo esc_html( $a['badge'] ); ?></span>
                <?php endif; ?>
                <?php if ( ! empty( $a['icon'] ) ) : ?>
                    <span class="rawnaq-tilt-icon dashicons <?php echo esc_attr( $a['icon'] ); ?>"></span>
                <?php endif; ?>
                <div class="rawnaq-tilt-content">
                    <?php if ( $a['title'] ) : ?>
                        <h3 class="rawnaq-tilt-title"><?php echo esc_html( $a['title'] ); ?></h3>
                    <?php endif; ?>
                    <?php if ( $a['desc'] ) : ?>
                        <p class="rawnaq-tilt-desc"><?php echo esc_html( $a['desc'] ); ?></p>
                    <?php endif; ?>
                    <?php if ( $cta_text && $cta_url ) : ?>
                        <a class="rawnaq-tilt-btn" href="<?php echo esc_url( $cta_url ); ?>" target="<?php echo esc_attr( $target ); ?>"<?php if ( $rel_value ) : ?> rel="<?php echo esc_attr( $rel_value ); ?>"<?php endif; ?>><?php echo esc_html( $cta_text ); ?></a>
                    <?php elseif ( $cta_text ) : ?>
                        <span class="rawnaq-tilt-btn is-static"><?php echo esc_html( $cta_text ); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ( ! empty( $a['link'] ) ) : ?>
                    <a class="rawnaq-tilt-stretch-link" href="<?php echo esc_url( $a['link'] ); ?>" target="<?php echo esc_attr( $target ); ?>"<?php if ( $rel_value ) : ?> rel="<?php echo esc_attr( $rel_value ); ?>"<?php endif; ?> aria-label="<?php echo esc_attr( $a['title'] ?: __( 'Open link', 'rawnaq' ) ); ?>"></a>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_scroll_timeline_block( $attributes ) {
        wp_enqueue_style( 'rawnaq-scroll-timeline' );
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_script( 'rawnaq-scroll-timeline' );

        $steps        = json_decode( $attributes['stepsJson'] ?? '[]', true ) ?: [];
        $layout       = sanitize_html_class( $attributes['layout'] ?? 'alternating' );
        if ( ! in_array( $layout, [ 'alternating', 'left', 'right' ], true ) ) {
            $layout = 'alternating';
        }
        $show_numbers = ! empty( $attributes['showNumbers'] );
        $wrap_class   = 'rawnaq-timeline-wrapper layout-' . $layout;
        if ( $show_numbers ) {
            $wrap_class .= ' show-numbers';
        }

        $style_vars = [
            '--tl-line-bg'      => sanitize_hex_color( $attributes['lineBg'] ?? '' ) ?: '#e2e8f0',
            '--tl-line-active'  => sanitize_hex_color( $attributes['lineActive'] ?? '' ) ?: '#6366f1',
            '--tl-bullet-border'=> sanitize_hex_color( $attributes['bulletBorder'] ?? '' ) ?: '#cbd5e1',
            '--tl-bullet-active'=> sanitize_hex_color( $attributes['bulletActive'] ?? '' ) ?: '#6366f1',
            '--tl-card-bg'      => sanitize_hex_color( $attributes['cardBg'] ?? '' ) ?: '#ffffff',
            '--tl-meta'         => sanitize_hex_color( $attributes['metaColor'] ?? '' ) ?: '#6366f1',
            '--tl-title'        => sanitize_hex_color( $attributes['titleColor'] ?? '' ) ?: '#1a1a1a',
            '--tl-desc'         => sanitize_hex_color( $attributes['descColor'] ?? '' ) ?: '#666666',
            '--tl-cta'          => sanitize_hex_color( $attributes['ctaColor'] ?? '' ) ?: '#6366f1',
            '--tl-card-radius'  => max( 0, min( 40, absint( $attributes['cardRadius'] ?? 16 ) ) ) . 'px',
            '--tl-bullet-size'  => max( 16, min( 48, absint( $attributes['bulletSize'] ?? 28 ) ) ) . 'px',
            '--tl-item-pad-y'   => max( 8, min( 80, absint( $attributes['itemGap'] ?? 20 ) ) ) . 'px',
        ];
        $style_attr = '';
        foreach ( $style_vars as $prop => $val ) {
            $style_attr .= $prop . ':' . $val . ';';
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr( $wrap_class ); ?>" data-show-numbers="<?php echo $show_numbers ? '1' : '0'; ?>" style="<?php echo esc_attr( $style_attr ); ?>">
            <div class="rawnaq-timeline-line-bg"></div>
            <div class="rawnaq-timeline-line-active"></div>
            <?php foreach ( $steps as $index => $step ) :
                if ( 'left' === $layout ) {
                    $side = 'left-item';
                } elseif ( 'right' === $layout ) {
                    $side = 'right-item';
                } else {
                    $side = ( 0 === ( $index % 2 ) ) ? 'left-item' : 'right-item';
                }
                $num      = str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT );
                $meta     = $step['meta'] ?? '';
                $title    = $step['title'] ?? '';
                $desc     = $step['desc'] ?? '';
                $icon     = $step['icon'] ?? '';
                $img      = $step['imageUrl'] ?? '';
                $cta_text = trim( (string) ( $step['ctaText'] ?? '' ) );
                $cta_link = $step['ctaLink'] ?? '';
                ?>
                <div class="rawnaq-timeline-item <?php echo esc_attr( $side ); ?>">
                    <span class="rawnaq-timeline-bullet">
                        <?php if ( $show_numbers ) : ?>
                            <span class="num"><?php echo esc_html( $num ); ?></span>
                        <?php endif; ?>
                    </span>
                    <div class="rawnaq-timeline-card">
                        <?php if ( $img ) : ?>
                            <img class="rawnaq-timeline-thumb" src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
                        <?php endif; ?>
                        <?php if ( $meta ) : ?>
                            <span class="rawnaq-timeline-meta"><?php echo esc_html( $meta ); ?></span>
                        <?php endif; ?>
                        <?php if ( $icon ) : ?>
                            <span class="rawnaq-timeline-icon"><span class="dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span></span>
                        <?php endif; ?>
                        <?php if ( $title ) : ?>
                            <h4><?php echo esc_html( $title ); ?></h4>
                        <?php endif; ?>
                        <?php if ( $desc ) : ?>
                            <p><?php echo esc_html( $desc ); ?></p>
                        <?php endif; ?>
                        <?php if ( $cta_text && $cta_link ) : ?>
                            <a class="rawnaq-timeline-cta" href="<?php echo esc_url( $cta_link ); ?>"><?php echo esc_html( $cta_text ); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_floating_dock_block( $attributes ) {
        wp_enqueue_style( 'rawnaq-floating-dock' );
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_script( 'rawnaq-floating-dock' );

        $a = wp_parse_args( $attributes, [
            'position'     => 'bottom',
            'itemsJson'    => '[]',
            'offset'       => 20,
            'hideMobile'   => false,
            'mobileLabels' => false,
            'dockBg'       => 'rgba(255,255,255,0.55)',
            'dockBorder'   => 'rgba(255,255,255,0.5)',
            'dockBlur'     => 16,
            'dockRadius'   => 24,
            'dockGap'      => 12,
            'dockPad'      => 10,
            'itemBg'       => '#ffffff',
            'iconColor'    => '#444444',
            'itemSize'     => 48,
            'itemRadius'   => 12,
            'badgeBg'      => '#ef4444',
            'badgeColor'   => '#ffffff',
            'magnify'      => true,
            'maxScale'     => 1.6,
        ] );

        $items = json_decode( $a['itemsJson'], true ) ?: [];
        $pos   = sanitize_html_class( $a['position'] ?: 'bottom' );
        if ( ! in_array( $pos, [ 'bottom', 'left', 'right' ], true ) ) {
            $pos = 'bottom';
        }

        $classes = [ 'rawnaq-dock-container', 'pos-' . $pos ];
        if ( ! empty( $a['hideMobile'] ) ) {
            $classes[] = 'hide-mobile';
        }
        if ( ! empty( $a['mobileLabels'] ) ) {
            $classes[] = 'mobile-labels';
        }

        $style_parts = [
            '--dock-offset:' . absint( $a['offset'] ) . 'px',
            '--dock-bg:' . esc_attr( $a['dockBg'] ),
            '--dock-border:' . esc_attr( $a['dockBorder'] ),
            '--dock-blur:' . absint( $a['dockBlur'] ) . 'px',
            '--dock-radius:' . absint( $a['dockRadius'] ) . 'px',
            '--dock-gap:' . absint( $a['dockGap'] ) . 'px',
            '--dock-pad:' . absint( $a['dockPad'] ) . 'px',
            '--dock-item-bg:' . esc_attr( sanitize_hex_color( $a['itemBg'] ) ?: '#ffffff' ),
            '--dock-icon:' . esc_attr( sanitize_hex_color( $a['iconColor'] ) ?: '#444444' ),
            '--dock-item-size:' . absint( $a['itemSize'] ) . 'px',
            '--dock-item-radius:' . absint( $a['itemRadius'] ) . 'px',
            '--dock-badge-bg:' . esc_attr( sanitize_hex_color( $a['badgeBg'] ) ?: '#ef4444' ),
            '--dock-badge-color:' . esc_attr( sanitize_hex_color( $a['badgeColor'] ) ?: '#ffffff' ),
        ];
        $style = implode( ';', $style_parts ) . ';';

        $magnify   = ! empty( $a['magnify'] );
        $max_scale = floatval( $a['maxScale'] );
        if ( $max_scale < 1.1 ) {
            $max_scale = 1.6;
        }

        ob_start();
        ?>
        <nav class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             style="<?php echo esc_attr( $style ); ?>"
             aria-label="<?php echo esc_attr__( 'Floating dock', 'rawnaq' ); ?>"
             data-magnify="<?php echo $magnify ? '1' : '0'; ?>"
             data-max-scale="<?php echo esc_attr( $max_scale ); ?>"
             data-base-size="<?php echo esc_attr( absint( $a['itemSize'] ) ); ?>">
            <?php foreach ( $items as $item ) :
                $label  = $item['label'] ?? '';
                $icon   = $item['icon'] ?? 'dashicons-admin-generic';
                $link   = ! empty( $item['link'] ) ? $item['link'] : '#';
                $target = ( isset( $item['target'] ) && $item['target'] === '_blank' ) ? '_blank' : '_self';
                $rel_value = $target === '_blank' ? 'noopener' : '';
                $badge  = trim( (string) ( $item['badge'] ?? '' ) );
                $color  = sanitize_hex_color( $item['color'] ?? '' ) ?: '#6366f1';
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
        </nav>
        <?php
        return ob_get_clean();
    }

    public function render_flow_chart_block( $attributes ) {
        wp_enqueue_style( 'rawnaq-flow-chart' );
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_script( 'rawnaq-flow-chart' );

        $mode = ( $attributes['mode'] ?? 'org' ) === 'process' ? 'process' : 'org';
        $conn = sanitize_key( $attributes['connector'] ?? 'curved' );
        if ( ! in_array( $conn, [ 'curved', 'elbow', 'straight', 'dashed' ], true ) ) {
            $conn = 'curved';
        }
        $raw_nodes = json_decode( $attributes['nodesJson'] ?? '[]', true );
        if ( ! is_array( $raw_nodes ) ) {
            $raw_nodes = [];
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
            $parent      = sanitize_key( $item['parent'] ?? '' );
            if ( $parent === $id ) {
                $parent = '';
            }
            $nodes[] = [
                'id'       => $id,
                'parent'   => $parent,
                'title'    => sanitize_text_field( $item['title'] ?? '' ),
                'role'     => sanitize_text_field( $item['role'] ?? '' ),
                'icon'     => sanitize_text_field( $item['icon'] ?? '' ),
                'detail'   => sanitize_textarea_field( $item['detail'] ?? '' ),
                'link'     => ! empty( $item['link'] ) ? esc_url_raw( $item['link'] ) : '',
                'decision' => ! empty( $item['decision'] ),
            ];
        }
        $cfg = [
            'mode'      => $mode,
            'connector' => $conn,
            'nodes'     => $nodes,
        ];
        ob_start();
        ?>
        <div class="rawnaq-flow-chart" data-flow="<?php echo esc_attr( rawurlencode( wp_json_encode( $cfg ) ) ); ?>">
            <div class="rawnaq-flow-stage is-responsive"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_scroll_progress_toc_block( $attributes ) {
        wp_enqueue_style( 'rawnaq-scroll-progress-toc' );
        wp_enqueue_script( 'rawnaq-scroll-progress-toc' );

        $levels_raw = $attributes['levels'] ?? 'h2,h3';
        $levels     = array_values( array_filter( array_map( 'trim', explode( ',', $levels_raw ) ) ) );
        $manual     = json_decode( $attributes['manualJson'] ?? '[]', true );
        if ( ! is_array( $manual ) ) {
            $manual = [];
        }
        $manual_clean = [];
        foreach ( $manual as $item ) {
            $manual_clean[] = [
                'title' => sanitize_text_field( $item['title'] ?? '' ),
                'id'    => sanitize_title( $item['id'] ?? '' ),
                'level' => sanitize_text_field( $item['level'] ?? '2' ),
            ];
        }

        $cfg = [
            'progress'       => sanitize_key( $attributes['progress'] ?? 'both' ),
            'barPosition'    => sanitize_key( $attributes['barPosition'] ?? 'top' ),
            'showPercent'    => ! empty( $attributes['showPercent'] ),
            'tocPosition'    => sanitize_key( $attributes['tocPosition'] ?? 'sticky' ),
            'tocTitle'       => sanitize_text_field( $attributes['tocTitle'] ?? 'Contents' ),
            'source'         => sanitize_key( $attributes['source'] ?? 'auto' ),
            'levels'         => $levels ?: [ 'h2', 'h3' ],
            'manual'         => $manual_clean,
            'collapseSubs'   => false,
            'smooth'         => ! empty( $attributes['smooth'] ),
            'scrollOffset'   => absint( $attributes['scrollOffset'] ?? 80 ),
            'readingTime'    => ! empty( $attributes['readingTime'] ),
            'mobileCollapse' => ! empty( $attributes['mobileCollapse'] ),
            'hideIfShort'    => true,
        ];
        if ( ! in_array( $cfg['progress'], [ 'bar', 'ring', 'both', 'none' ], true ) ) {
            $cfg['progress'] = 'both';
        }
        if ( ! in_array( $cfg['tocPosition'], [ 'sticky', 'floating', 'inline', 'none' ], true ) ) {
            $cfg['tocPosition'] = 'sticky';
        }

        ob_start();
        ?>
        <div class="rawnaq-spt"
             style="--spt-offset: <?php echo esc_attr( (string) $cfg['scrollOffset'] ); ?>px;"
             data-spt="<?php echo esc_attr( wp_json_encode( $cfg ) ); ?>">
            <?php if ( 'none' !== $cfg['tocPosition'] ) : ?>
                <nav class="rawnaq-spt-toc is-<?php echo esc_attr( $cfg['tocPosition'] ); ?>" aria-label="<?php echo esc_attr( $cfg['tocTitle'] ); ?>">
                    <p class="rawnaq-spt-reading" hidden></p>
                    <h3 class="rawnaq-spt-title"><?php echo esc_html( $cfg['tocTitle'] ); ?></h3>
                    <ul class="rawnaq-spt-list"></ul>
                </nav>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

new Rawnaq_Gutenberg_Loader();
