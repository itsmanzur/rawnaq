<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rawnaq_Gutenberg_Loader {

    private $modules = [];

    public function __construct() {
        $settings = get_option( 'rawnaq_settings', [] );
        $this->modules = isset( $settings['modules'] ) ? $settings['modules'] : [
            'hub-diagram'     => '1',
            'tilt-card'       => '1',
            'scroll-timeline' => '1',
            'floating-dock'   => '1',
        ];

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
            [ 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'rawnaq-hub-diagram' ],
            RAWNAQ_VERSION,
            true
        );

        // 1. Hub Diagram Block
        if ( isset( $this->modules['hub-diagram'] ) && $this->modules['hub-diagram'] === '1' ) {
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
        if ( isset( $this->modules['tilt-card'] ) && $this->modules['tilt-card'] === '1' ) {
            register_block_type( 'rawnaq/tilt-card', [
                'editor_script'   => 'rawnaq-gutenberg-editor',
                'editor_style'    => 'rawnaq-tilt-card',
                'style'           => 'rawnaq-tilt-card',
                'render_callback' => [ $this, 'render_tilt_card_block' ],
                'attributes'      => [
                    'title'       => [ 'type' => 'string', 'default' => 'Creative Service' ],
                    'desc'        => [ 'type' => 'string', 'default' => 'We design premium, high-speed interfaces tailored to stand out.' ],
                    'icon'        => [ 'type' => 'string', 'default' => 'dashicons-admin-generic' ],
                    'link'        => [ 'type' => 'string', 'default' => '' ],
                    'target'      => [ 'type' => 'string', 'default' => '_self' ],
                    'maxTilt'     => [ 'type' => 'number', 'default' => 15 ],
                ]
            ] );
        }

        // 3. Scroll Timeline Block
        if ( isset( $this->modules['scroll-timeline'] ) && $this->modules['scroll-timeline'] === '1' ) {
            register_block_type( 'rawnaq/scroll-timeline', [
                'editor_script'   => 'rawnaq-gutenberg-editor',
                'editor_style'    => 'rawnaq-scroll-timeline',
                'style'           => 'rawnaq-scroll-timeline',
                'render_callback' => [ $this, 'render_scroll_timeline_block' ],
                'attributes'      => [
                    'stepsJson'   => [ 'type' => 'string', 'default' => '[{"title":"Step 1: Ideation","desc":"Gather blueprints."},{"title":"Step 2: Prototyping","desc":"Presentation mockups."},{"title":"Step 3: Deployment","desc":"Deploy clean, fast code."}]' ],
                ]
            ] );
        }

        // 4. Floating Dock Block
        if ( isset( $this->modules['floating-dock'] ) && $this->modules['floating-dock'] === '1' ) {
            register_block_type( 'rawnaq/floating-dock', [
                'editor_script'   => 'rawnaq-gutenberg-editor',
                'editor_style'    => 'rawnaq-floating-dock',
                'style'           => 'rawnaq-floating-dock',
                'render_callback' => [ $this, 'render_floating_dock_block' ],
                'attributes'      => [
                    'position'    => [ 'type' => 'string', 'default' => 'bottom' ],
                    'itemsJson'   => [ 'type' => 'string', 'default' => '[{"label":"Home","icon":"dashicons-admin-home","link":"#","color":"#6366f1"},{"label":"Messages","icon":"dashicons-email-alt","link":"#","color":"#6366f1"},{"label":"Settings","icon":"dashicons-admin-generic","link":"#","color":"#6366f1"}]' ],
                ]
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
        wp_enqueue_script( 'rawnaq-hub-diagram' );
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

        $tag = $attributes['link'] ? 'a' : 'div';
        $href = $attributes['link'] ? ' href="' . esc_url( $attributes['link'] ) . '"' : '';
        $target = $attributes['link'] ? ' target="' . esc_attr( $attributes['target'] ) . '"' : '';

        ob_start();
        ?>
        <div class="rawnaq-tilt-container">
            <<?php echo $tag; ?> class="rawnaq-tilt-card" data-tilt-max="<?php echo esc_attr( $attributes['maxTilt'] ); ?>" <?php echo $href; ?> <?php echo $target; ?>>
                <span class="rawnaq-tilt-glow"></span>
                <?php if ( ! empty( $attributes['icon'] ) ) : ?>
                    <span class="rawnaq-tilt-icon dashicons <?php echo esc_attr( $attributes['icon'] ); ?>"></span>
                <?php endif; ?>
                <div class="rawnaq-tilt-content">
                    <h3 class="rawnaq-tilt-title"><?php echo esc_html( $attributes['title'] ); ?></h3>
                    <p class="rawnaq-tilt-desc"><?php echo esc_html( $attributes['desc'] ); ?></p>
                </div>
            </<?php echo $tag; ?>>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_scroll_timeline_block( $attributes ) {
        wp_enqueue_style( 'rawnaq-scroll-timeline' );
        wp_enqueue_script( 'rawnaq-scroll-timeline' );

        $steps = json_decode( $attributes['stepsJson'], true ) ?: [];
        ob_start();
        ?>
        <div class="rawnaq-timeline-wrapper">
            <div class="rawnaq-timeline-line-bg"></div>
            <div class="rawnaq-timeline-line-active"></div>
            <?php foreach ( $steps as $index => $step ) : 
                $alignment = ( $index % 2 === 0 ) ? 'left-item' : 'right-item';
                ?>
                <div class="rawnaq-timeline-item <?php echo esc_attr( $alignment ); ?>">
                    <span class="rawnaq-timeline-bullet"></span>
                    <div class="rawnaq-timeline-card">
                        <h4><?php echo esc_html( $step['title'] ); ?></h4>
                        <p><?php echo esc_html( $step['desc'] ); ?></p>
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

        $items = json_decode( $attributes['itemsJson'], true ) ?: [];
        $pos = $attributes['position'];
        ob_start();
        ?>
        <div class="rawnaq-dock-container pos-<?php echo esc_attr( $pos ); ?>">
            <?php foreach ( $items as $item ) : 
                $link = ! empty( $item['link'] ) ? esc_url( $item['link'] ) : '#';
                $color = $item['color'] ?? '#6366f1';
                ?>
                <a href="<?php echo esc_url( $link ); ?>" class="rawnaq-dock-item" style="--hover-color: <?php echo esc_attr( $color ); ?>;">
                    <span class="dashicons <?php echo esc_attr( $item['icon'] ); ?>"></span>
                    <span class="rawnaq-dock-tooltip"><?php echo esc_html( $item['label'] ); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <style>
            .rawnaq-dock-item:hover span.dashicons { color: var(--hover-color) !important; }
        </style>
        <?php
        return ob_get_clean();
    }
}

new Rawnaq_Gutenberg_Loader();
