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
                'wp-data',
                'rawnaq-hub-diagram',
                'rawnaq-flow-chart',
                'rawnaq-scroll-progress-toc',
                'rawnaq-bento-grid',
            ],
            RAWNAQ_VERSION,
            true
        );

        if ( function_exists( 'rawnaq_bento_preset_for_gutenberg' ) ) {
            $bento_presets = [];
            foreach ( [ 'featured', 'equal', 'wide' ] as $key ) {
                $pack = rawnaq_bento_preset_for_gutenberg( $key );
                if ( $pack ) {
                    $bento_presets[ $key ] = $pack;
                }
            }
            wp_localize_script( 'rawnaq-gutenberg-editor', 'rawnaqBentoPresets', $bento_presets );
        }

        if ( function_exists( 'rawnaq_timeline_preset_for_gutenberg' ) ) {
            $tl_presets = [];
            foreach ( array_keys( rawnaq_timeline_presets() ) as $key ) {
                $pack = rawnaq_timeline_preset_for_gutenberg( $key );
                if ( $pack ) {
                    $tl_presets[ $key ] = $pack;
                }
            }
            wp_localize_script( 'rawnaq-gutenberg-editor', 'rawnaqTimelinePresets', $tl_presets );
        }

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
                    'showExport'     => [ 'type' => 'boolean', 'default' => true ],
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
                    'enableFlip'   => [ 'type' => 'boolean', 'default' => false ],
                    'flipTrigger'  => [ 'type' => 'string', 'default' => 'hover' ],
                    'backTitle'    => [ 'type' => 'string', 'default' => 'Why choose us' ],
                    'backDesc'     => [ 'type' => 'string', 'default' => 'Add the extra detail or a persuasive reason on the reverse side.' ],
                    'backCtaText'  => [ 'type' => 'string', 'default' => 'Get started' ],
                    'backCtaLink'  => [ 'type' => 'string', 'default' => '' ],
                    'backBg'       => [ 'type' => 'string', 'default' => '#4338ca' ],
                    'backColor'    => [ 'type' => 'string', 'default' => '#ffffff' ],
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
                        'default' => '[{"meta":"Phase 1","title":"Ideation & Sketching","desc":"Gather initial ideas and draft blueprints.","icon":"","imageUrl":"","imageId":0,"video":"","ctaText":"","ctaLink":""},{"meta":"Phase 2","title":"Prototype Review","desc":"Interactive mockups and client reviews.","icon":"","imageUrl":"","imageId":0,"video":"","ctaText":"","ctaLink":""},{"meta":"Phase 3","title":"Development & Coding","desc":"Build, test, and deploy clean code.","icon":"","imageUrl":"","imageId":0,"video":"","ctaText":"","ctaLink":""}]',
                    ],
                    'layout'          => [ 'type' => 'string', 'default' => 'alternating' ],
                    'showNumbers'     => [ 'type' => 'boolean', 'default' => true ],
                    'timelineName'    => [ 'type' => 'string', 'default' => '' ],
                    'source'          => [ 'type' => 'string', 'default' => 'manual' ],
                    'postType'        => [ 'type' => 'string', 'default' => 'post' ],
                    'postsPerPage'    => [ 'type' => 'number', 'default' => 6 ],
                    'orderby'         => [ 'type' => 'string', 'default' => 'date' ],
                    'order'           => [ 'type' => 'string', 'default' => 'DESC' ],
                    'taxonomy'        => [ 'type' => 'string', 'default' => '' ],
                    'terms'           => [ 'type' => 'string', 'default' => '' ],
                    'includeIds'      => [ 'type' => 'string', 'default' => '' ],
                    'excludeIds'      => [ 'type' => 'string', 'default' => '' ],
                    'lineBg'          => [ 'type' => 'string', 'default' => '#e2e8f0' ],
                    'lineActive'      => [ 'type' => 'string', 'default' => '#6366f1' ],
                    'lineWidth'       => [ 'type' => 'number', 'default' => 4 ],
                    'bulletBorder'    => [ 'type' => 'string', 'default' => '#cbd5e1' ],
                    'bulletActive'    => [ 'type' => 'string', 'default' => '#6366f1' ],
                    'cardBg'          => [ 'type' => 'string', 'default' => '#ffffff' ],
                    'metaColor'       => [ 'type' => 'string', 'default' => '#6366f1' ],
                    'titleColor'      => [ 'type' => 'string', 'default' => '#1a1a1a' ],
                    'descColor'       => [ 'type' => 'string', 'default' => '#666666' ],
                    'ctaColor'        => [ 'type' => 'string', 'default' => '#6366f1' ],
                    'cardRadius'      => [ 'type' => 'number', 'default' => 16 ],
                    'bulletSize'      => [ 'type' => 'number', 'default' => 28 ],
                    'itemGap'         => [ 'type' => 'number', 'default' => 20 ],
                    'initialVisible'  => [ 'type' => 'number', 'default' => 0 ],
                    'loadChunk'       => [ 'type' => 'number', 'default' => 3 ],
                    'loadMoreText'    => [ 'type' => 'string', 'default' => 'Load more' ],
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
                    // WhatsApp Contact Mode
                    'whatsappMode'      => [ 'type' => 'boolean', 'default' => false ],
                    'positionWa'        => [ 'type' => 'string', 'default' => 'right' ],
                    'primaryChannel'    => [ 'type' => 'string', 'default' => 'whatsapp' ],
                    'agentsJson'        => [
                        'type'    => 'string',
                        'default' => '[{"name":"Customer Support","role":"Live Support","number":"8801700000000","avatar":"","msg":"আসসালামু আলাইকুম, আমি {pageTitle} পেজ থেকে লিখছি ({url})।"}]',
                    ],
                    'defaultMsg'        => [
                        'type'    => 'string',
                        'default' => 'আসসালামু আলাইকুম, আমি {pageTitle} থেকে যোগাযোগ করছি।',
                    ],
                    'secCall'           => [ 'type' => 'string', 'default' => '' ],
                    'secMessenger'      => [ 'type' => 'string', 'default' => '' ],
                    'secEmail'          => [ 'type' => 'string', 'default' => '' ],
                    'secTelegram'       => [ 'type' => 'string', 'default' => '' ],
                    'timezone'          => [ 'type' => 'string', 'default' => 'Asia/Dhaka' ],
                    'scheduleJson'      => [
                        'type'    => 'string',
                        'default' => '{"mon":{"enabled":true,"open":"09:00","close":"18:00"},"tue":{"enabled":true,"open":"09:00","close":"18:00"},"wed":{"enabled":true,"open":"09:00","close":"18:00"},"thu":{"enabled":true,"open":"09:00","close":"18:00"},"fri":{"enabled":true,"open":"09:00","close":"18:00"},"sat":{"enabled":false,"open":"09:00","close":"18:00"},"sun":{"enabled":false,"open":"09:00","close":"18:00"}}',
                    ],
                    'offHoursBehavior'  => [ 'type' => 'string', 'default' => 'offline_badge' ],
                    'offHoursRedirect'  => [ 'type' => 'string', 'default' => '' ],
                    'offHoursEmail'     => [ 'type' => 'string', 'default' => '' ],
                    'offHoursFormNote'  => [ 'type' => 'string', 'default' => 'We are offline right now. Leave a message and we will reply by email.' ],
                    'qrFallback'        => [ 'type' => 'boolean', 'default' => true ],
                    'desktopAction'     => [ 'type' => 'string', 'default' => 'choice' ],
                    'triggerDelay'      => [ 'type' => 'number', 'default' => 0 ],
                    'triggerScroll'     => [ 'type' => 'number', 'default' => 0 ],
                    'greetingText'      => [ 'type' => 'string', 'default' => 'আসসালামু আলাইকুম, সাহায্য লাগবে?' ],
                    'hideDesktop'       => [ 'type' => 'boolean', 'default' => false ],
                    'safeOffset'        => [ 'type' => 'number', 'default' => 0 ],
                    'visMode'           => [ 'type' => 'string', 'default' => 'all' ],
                    'visIds'            => [ 'type' => 'string', 'default' => '' ],
                    'visIncludeFront'   => [ 'type' => 'boolean', 'default' => false ],
                    'visIncludeProducts'=> [ 'type' => 'boolean', 'default' => false ],
                    'trackClicks'       => [ 'type' => 'boolean', 'default' => true ],
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
                    'direction' => [ 'type' => 'string', 'default' => 'tb' ],
                    'shape'     => [ 'type' => 'string', 'default' => 'rect' ],
                    'avatarShape' => [ 'type' => 'string', 'default' => 'rounded' ],
                    'avatarSize' => [ 'type' => 'number', 'default' => 30 ],
                    'avatarGap' => [ 'type' => 'number', 'default' => 8 ],
                    'avatarBg' => [ 'type' => 'string', 'default' => '#FEF3C7' ],
                    'avatarIconColor' => [ 'type' => 'string', 'default' => '#92400E' ],
                    'avatarIconSize' => [ 'type' => 'number', 'default' => 14 ],
                    'avatarBorderColor' => [ 'type' => 'string', 'default' => '' ],
                    'avatarBorderWidth' => [ 'type' => 'number', 'default' => 0 ],
                    'avatarObjectFit' => [ 'type' => 'string', 'default' => 'cover' ],
                    'avatarShadow' => [ 'type' => 'boolean', 'default' => false ],
                    'showExport' => [ 'type' => 'boolean', 'default' => true ],
                    'enableZoom' => [ 'type' => 'boolean', 'default' => true ],
                    'accentColor' => [ 'type' => 'string', 'default' => '#FBBF24' ],
                    'rootColorFrom' => [ 'type' => 'string', 'default' => '#4338CA' ],
                    'rootColorTo' => [ 'type' => 'string', 'default' => '#7C3AED' ],
                    'lineColor' => [ 'type' => 'string', 'default' => '#E6E2F0' ],
                    'nodeBg' => [ 'type' => 'string', 'default' => '#ffffff' ],
                    'nodeRadius' => [ 'type' => 'number', 'default' => 14 ],
                    'dataSource'=> [ 'type' => 'string', 'default' => 'manual' ],
                    'usersRole' => [ 'type' => 'string', 'default' => '' ],
                    'usersNumber'=> [ 'type' => 'number', 'default' => 20 ],
                    'nodesJson' => [
                        'type'    => 'string',
                        'default' => '[{"id":"ceo","parent":"","title":"Founder / CEO","role":"Leadership","icon":"★","image":"","detail":"Leads the company.","link":"","decision":false,"x":40,"y":5},{"id":"eng","parent":"ceo","title":"Engineering","role":"Product","icon":"⚙","image":"","detail":"Engineering roadmap.","link":"","decision":false,"x":15,"y":40},{"id":"ops","parent":"ceo","title":"Operations","role":"Delivery","icon":"◆","image":"","detail":"Project delivery.","link":"","decision":false,"x":40,"y":40},{"id":"e1","parent":"eng","title":"Frontend","role":"Team","icon":"▪","image":"","detail":"UI work.","link":"","decision":false,"x":5,"y":75}]',
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
                    'contentSelector'=> [ 'type' => 'string', 'default' => '' ],
                    'hideIfShort'    => [ 'type' => 'boolean', 'default' => true ],
                    'scrollOffset'   => [ 'type' => 'number', 'default' => 80 ],
                    'smooth'         => [ 'type' => 'boolean', 'default' => true ],
                    'readingTime'    => [ 'type' => 'boolean', 'default' => true ],
                    'showPercent'    => [ 'type' => 'boolean', 'default' => true ],
                    'mobileCollapse' => [ 'type' => 'boolean', 'default' => true ],
                    'manualJson'     => [ 'type' => 'string', 'default' => '[]' ],
                    'collapseSubs'   => [ 'type' => 'boolean', 'default' => false ],
                    'showSearch'     => [ 'type' => 'boolean', 'default' => false ],
                    'dockAttach'     => [ 'type' => 'boolean', 'default' => false ],
                    'syncTimeline'   => [ 'type' => 'string', 'default' => '' ],
                    'ringSize'       => [ 'type' => 'number', 'default' => 56 ],
                ],
            ] );
        }

        // 7. Bento Grid Block (+ cell child for InnerBlocks)
        if ( rawnaq_is_module_enabled( 'bento-grid' ) ) {
            register_block_type( 'rawnaq/bento-cell', [
                'editor_script' => 'rawnaq-gutenberg-editor',
                'editor_style'  => 'rawnaq-bento-grid',
                'style'         => 'rawnaq-bento-grid',
                'attributes'    => [
                    'cellType'   => [ 'type' => 'string', 'default' => 'text' ],
                    'colSpan'    => [ 'type' => 'number', 'default' => 1 ],
                    'rowSpan'    => [ 'type' => 'number', 'default' => 1 ],
                    'colMd'      => [ 'type' => 'number', 'default' => 0 ],
                    'rowMd'      => [ 'type' => 'number', 'default' => 0 ],
                    'colSm'      => [ 'type' => 'number', 'default' => 0 ],
                    'rowSm'      => [ 'type' => 'number', 'default' => 0 ],
                    'orderDesk'  => [ 'type' => 'number', 'default' => 0 ],
                    'orderMd'    => [ 'type' => 'number', 'default' => 0 ],
                    'orderSm'    => [ 'type' => 'number', 'default' => 0 ],
                    'align'      => [ 'type' => 'string', 'default' => '' ],
                    'tag'        => [ 'type' => 'string', 'default' => '' ],
                    'link'       => [ 'type' => 'string', 'default' => '' ],
                    'mediaUrl'   => [ 'type' => 'string', 'default' => '' ],
                    'tagBg'      => [ 'type' => 'string', 'default' => '' ],
                    'tagColor'   => [ 'type' => 'string', 'default' => '' ],
                ],
            ] );

            register_block_type( 'rawnaq/bento-grid', [
                'editor_script'   => 'rawnaq-gutenberg-editor',
                'editor_style'    => 'rawnaq-bento-grid',
                'style'           => 'rawnaq-bento-grid',
                'render_callback' => [ $this, 'render_bento_grid_block' ],
                'attributes'      => [
                    'preset'      => [ 'type' => 'string', 'default' => 'featured' ],
                    'columns'     => [ 'type' => 'number', 'default' => 4 ],
                    'rowHeight'   => [ 'type' => 'number', 'default' => 140 ],
                    'gap'         => [ 'type' => 'number', 'default' => 16 ],
                    'columnGap'   => [ 'type' => 'number', 'default' => 16 ],
                    'rowGap'      => [ 'type' => 'number', 'default' => 16 ],
                    'radius'      => [ 'type' => 'number', 'default' => 18 ],
                    'reveal'      => [ 'type' => 'boolean', 'default' => true ],
                    'hoverEffect' => [ 'type' => 'string', 'default' => 'lift' ],
                    'hairline'    => [ 'type' => 'boolean', 'default' => false ],
                    'overlayOpacity' => [ 'type' => 'number', 'default' => 100 ],
                    'tagBg'       => [ 'type' => 'string', 'default' => '#fef3c7' ],
                    'tagColor'    => [ 'type' => 'string', 'default' => '#92400e' ],
                    'titleColor'  => [ 'type' => 'string', 'default' => '#13231c' ],
                    'subColor'    => [ 'type' => 'string', 'default' => '#5c6f66' ],
                    'iconColor'   => [ 'type' => 'string', 'default' => '#0f766e' ],
                    'statColor'   => [ 'type' => 'string', 'default' => '#0f766e' ],
                    'cellBg'      => [ 'type' => 'string', 'default' => '#ffffff' ],
                    'cellBorder'  => [ 'type' => 'string', 'default' => '#d7e2dc' ],
                    'featuredFrom'=> [ 'type' => 'string', 'default' => '#0f766e' ],
                    'featuredTo'  => [ 'type' => 'string', 'default' => '#134e4a' ],
                    'ctaBg'       => [ 'type' => 'string', 'default' => '#fbbf24' ],
                    'ctaColor'    => [ 'type' => 'string', 'default' => '#92400e' ],
                    'cellsJson'   => [
                        'type'    => 'string',
                        'default' => '[{"type":"featured","col":2,"row":2,"tag":"Highlight","title":"Zero-jQuery performance","subtitle":"Per-page assets, clean output","icon":"dashicons-star-filled","image":"","video":"","stat":"42","suffix":"+","prefix":"","link":""},{"type":"image","col":2,"row":1,"tag":"Showcase","title":"Project gallery","subtitle":"Client work highlights","icon":"","image":"","video":"","stat":"","suffix":"","prefix":"","link":""},{"type":"stat","col":1,"row":1,"tag":"","title":"","subtitle":"Active installs","icon":"","image":"","video":"","stat":"42","suffix":"+","prefix":"","link":""},{"type":"text","col":1,"row":1,"tag":"","title":"Fast setup","subtitle":"Ready in minutes","icon":"dashicons-performance","image":"","video":"","stat":"","suffix":"","prefix":"","link":""}]',
                    ],
                ],
            ] );
        }

        // 8. Scroll Story Chapters
        if ( rawnaq_is_module_enabled( 'scroll-story' ) ) {
            register_block_type( 'rawnaq/scroll-story', [
                'editor_script'   => 'rawnaq-gutenberg-editor',
                'editor_style'    => 'rawnaq-scroll-story',
                'style'           => 'rawnaq-scroll-story',
                'render_callback' => [ $this, 'render_scroll_story_block' ],
                'attributes'      => [
                    'mediaSide'    => [ 'type' => 'string', 'default' => 'left' ],
                    'accent'       => [ 'type' => 'string', 'default' => '#0f766e' ],
                    'pinTop'       => [ 'type' => 'number', 'default' => 96 ],
                    'chaptersJson' => [
                        'type'    => 'string',
                        'default' => '[{"title":"The challenge","body":"Set the scene. What problem or opportunity opens the story?","image":"","caption":"","ctaText":"","ctaUrl":""},{"title":"The approach","body":"Explain the turning point — method, insight, or decision.","image":"","caption":"","ctaText":"","ctaUrl":""},{"title":"The outcome","body":"Close with the result readers should remember.","image":"","caption":"","ctaText":"","ctaUrl":""}]',
                    ],
                ],
            ] );
        }

        // 9. Smart Form
        if ( rawnaq_is_module_enabled( 'smart-form' ) ) {
            register_block_type( 'rawnaq/smart-form', [
                'editor_script'   => 'rawnaq-gutenberg-editor',
                'editor_style'    => 'rawnaq-smart-form',
                'style'           => 'rawnaq-smart-form',
                'render_callback' => [ $this, 'render_smart_form_block' ],
                'attributes'      => [
                    'fieldsJson'         => [
                        'type'    => 'string',
                        'default' => '[{"id":"name","type":"text","label":"Name","placeholder":"","required":true,"options":"","width":"50","step":1},{"id":"email","type":"email","label":"Email","placeholder":"","required":true,"options":"","width":"50","step":1},{"id":"phone","type":"phone","label":"Phone","placeholder":"","required":false,"options":"","width":"100","step":1},{"id":"message","type":"textarea","label":"Message","placeholder":"","required":true,"options":"","width":"100","step":1}]',
                    ],
                    'deliveryEmail'      => [ 'type' => 'boolean', 'default' => true ],
                    'deliveryWhatsapp'   => [ 'type' => 'boolean', 'default' => true ],
                    'emailTo'            => [ 'type' => 'string', 'default' => '' ],
                    'emailSubject'       => [ 'type' => 'string', 'default' => 'New website inquiry' ],
                    'waNumber'           => [ 'type' => 'string', 'default' => '' ],
                    'waTemplate'         => [ 'type' => 'string', 'default' => "New inquiry:\nName: {name}\nPhone: {phone}\nEmail: {email}\nMessage: {message}\nPage: {pageTitle}\nURL: {url}" ],
                    'afterSubmit'        => [ 'type' => 'string', 'default' => 'message' ],
                    'redirectUrl'        => [ 'type' => 'string', 'default' => '' ],
                    'submitLabel'        => [ 'type' => 'string', 'default' => 'Send message' ],
                    'successMessage'     => [ 'type' => 'string', 'default' => 'Message sent successfully.' ],
                    'errorMessage'       => [ 'type' => 'string', 'default' => 'Please fill in the required fields correctly.' ],
                    'consentEnabled'     => [ 'type' => 'boolean', 'default' => false ],
                    'consentText'        => [ 'type' => 'string', 'default' => 'I agree to the processing of my data.' ],
                    'logSubmissions'     => [ 'type' => 'boolean', 'default' => true ],
                    'recaptchaEnabled'   => [ 'type' => 'boolean', 'default' => false ],
                    'webhookEnabled'     => [ 'type' => 'boolean', 'default' => false ],
                    'webhookUrl'         => [ 'type' => 'string', 'default' => '' ],
                    'emailHtml'          => [ 'type' => 'boolean', 'default' => true ],
                    'crmProvider'        => [ 'type' => 'string', 'default' => 'none' ],
                    'crmAudience'        => [ 'type' => 'string', 'default' => '' ],
                    'buttonFullWidth'    => [ 'type' => 'boolean', 'default' => false ],
                    'accent'             => [ 'type' => 'string', 'default' => '#fbbf24' ],
                    'accentDeep'         => [ 'type' => 'string', 'default' => '#0f766e' ],
                    'buttonText'         => [ 'type' => 'string', 'default' => '#92400e' ],
                    'labelColor'         => [ 'type' => 'string', 'default' => '' ],
                    'inputBg'            => [ 'type' => 'string', 'default' => '' ],
                    'inputBorder'        => [ 'type' => 'string', 'default' => '' ],
                ],
            ] );
        }

        // 10. Case-Study Grid (+ card child for InnerBlocks)
        if ( rawnaq_is_module_enabled( 'case-study-grid' ) ) {
            $cs_default = wp_json_encode(
                array_map(
                    static function ( $p ) {
                        return [
                            'title'    => $p['title'],
                            'image'    => '',
                            'gallery'  => [],
                            'sector'   => $p['sector'],
                            'size'     => $p['size'],
                            'budget'   => $p['budget'],
                            'year'     => $p['year'],
                            'client'   => $p['client'],
                            'services' => $p['services'],
                            'excerpt'  => $p['excerpt'],
                            'detail'   => $p['detail'],
                            'link'     => '',
                            'featured' => ! empty( $p['featured'] ),
                            'col'      => $p['col'] ?? 1,
                            'row'      => $p['row'] ?? 1,
                        ];
                    },
                    function_exists( 'rawnaq_case_study_sample_projects' ) ? rawnaq_case_study_sample_projects() : []
                )
            );

            register_block_type( 'rawnaq/case-study-card', [
                'editor_script' => 'rawnaq-gutenberg-editor',
                'editor_style'  => 'rawnaq-case-study-grid',
                'attributes'    => [
                    'title'       => [ 'type' => 'string', 'default' => 'Project' ],
                    'image'       => [ 'type' => 'string', 'default' => '' ],
                    'galleryJson' => [ 'type' => 'string', 'default' => '[]' ],
                    'projectId'   => [ 'type' => 'string', 'default' => '' ],
                    'projectSlug' => [ 'type' => 'string', 'default' => '' ],
                    'sector'      => [ 'type' => 'string', 'default' => '' ],
                    'size'        => [ 'type' => 'string', 'default' => '' ],
                    'budget'      => [ 'type' => 'string', 'default' => '' ],
                    'year'        => [ 'type' => 'string', 'default' => '' ],
                    'client'      => [ 'type' => 'string', 'default' => '' ],
                    'services'    => [ 'type' => 'string', 'default' => '' ],
                    'excerpt'     => [ 'type' => 'string', 'default' => '' ],
                    'detail'      => [ 'type' => 'string', 'default' => '' ],
                    'link'        => [ 'type' => 'string', 'default' => '' ],
                    'featured'    => [ 'type' => 'boolean', 'default' => false ],
                    'col'         => [ 'type' => 'number', 'default' => 1 ],
                    'row'         => [ 'type' => 'number', 'default' => 1 ],
                ],
            ] );

            register_block_type( 'rawnaq/case-study-grid', [
                'editor_script'   => 'rawnaq-gutenberg-editor',
                'editor_style'    => 'rawnaq-case-study-grid',
                'style'           => 'rawnaq-case-study-grid',
                'render_callback' => [ $this, 'render_case_study_grid_block' ],
                'attributes'      => [
                    'source'         => [ 'type' => 'string', 'default' => 'manual' ],
                    'projectsJson'   => [ 'type' => 'string', 'default' => $cs_default ?: '[]' ],
                    'queryNumber'    => [ 'type' => 'number', 'default' => 12 ],
                    'queryOrderby'   => [ 'type' => 'string', 'default' => 'date' ],
                    'queryOrder'     => [ 'type' => 'string', 'default' => 'DESC' ],
                    'querySector'    => [ 'type' => 'string', 'default' => '' ],
                    'layout'         => [ 'type' => 'string', 'default' => 'bento' ],
                    'columns'        => [ 'type' => 'number', 'default' => 3 ],
                    'showFilter'     => [ 'type' => 'boolean', 'default' => true ],
                    'filterYear'     => [ 'type' => 'boolean', 'default' => true ],
                    'filterService'  => [ 'type' => 'boolean', 'default' => true ],
                    'sort'           => [ 'type' => 'string', 'default' => 'custom' ],
                    'hideBudget'     => [ 'type' => 'boolean', 'default' => false ],
                    'hideClient'     => [ 'type' => 'boolean', 'default' => false ],
                    'clickAction'    => [ 'type' => 'string', 'default' => 'modal' ],
                    'discussTarget'  => [ 'type' => 'string', 'default' => 'auto' ],
                    'initialVisible' => [ 'type' => 'number', 'default' => 0 ],
                    'loadChunk'      => [ 'type' => 'number', 'default' => 6 ],
                    'accent'         => [ 'type' => 'string', 'default' => '#fbbf24' ],
                    'cardBg'         => [ 'type' => 'string', 'default' => '#ffffff' ],
                    'cardBorder'     => [ 'type' => 'string', 'default' => '#d7e2dc' ],
                    'radius'         => [ 'type' => 'number', 'default' => 18 ],
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
        wp_enqueue_style( 'rawnaq-bento-grid' );
        wp_enqueue_style( 'rawnaq-scroll-story' );
        wp_enqueue_style( 'rawnaq-smart-form' );
        wp_enqueue_style( 'rawnaq-case-study-grid' );
        wp_enqueue_script( 'rawnaq-hub-diagram' );
        wp_enqueue_script( 'rawnaq-flow-chart' );
        wp_enqueue_script( 'rawnaq-bento-grid' );
        wp_enqueue_script( 'rawnaq-scroll-story' );
        wp_enqueue_script( 'rawnaq-smart-form' );
        wp_enqueue_script( 'rawnaq-case-study-grid' );
        wp_enqueue_script( 'rawnaq-bridge' );
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
            'export'         => ! isset( $attributes['showExport'] ) || ! empty( $attributes['showExport'] ),
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
            'enableFlip'   => false,
            'flipTrigger'  => 'hover',
            'backTitle'    => '',
            'backDesc'     => '',
            'backCtaText'  => '',
            'backCtaLink'  => '',
            'backBg'       => '#4338ca',
            'backColor'    => '#ffffff',
        ] );

        $has_image    = ! empty( $a['imageUrl'] );
        $align        = sanitize_html_class( $a['contentAlign'] ?: 'bottom' );
        $enable_flip  = ! empty( $a['enableFlip'] );
        $flip_trigger = ( $a['flipTrigger'] === 'click' ) ? 'click' : 'hover';
        $classes      = [ 'rawnaq-tilt-card', 'align-' . $align ];
        if ( $has_image ) {
            $classes[] = 'has-image';
        }
        if ( $enable_flip ) {
            $classes[] = 'is-flip';
            $classes[] = 'flip-' . $flip_trigger;
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

        // Front-face children (shared by flip + non-flip).
        ob_start();
        if ( $has_image ) :
            ?>
            <img class="rawnaq-tilt-image" src="<?php echo esc_url( $a['imageUrl'] ); ?>" alt="<?php echo esc_attr( $a['imageAlt'] ?: $a['title'] ); ?>" loading="lazy" />
            <span class="rawnaq-tilt-overlay" aria-hidden="true"></span>
            <?php
        endif;
        ?>
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
        <?php
        $front_html = ob_get_clean();

        $back_bg    = sanitize_hex_color( $a['backBg'] ) ?: '#4338ca';
        $back_color = sanitize_hex_color( $a['backColor'] ) ?: '#ffffff';
        $back_style = '--tilt-back-bg:' . $back_bg . ';--tilt-back-color:' . $back_color . ';';
        $back_cta   = trim( (string) $a['backCtaText'] );

        $card_attrs = '';
        if ( $enable_flip && 'click' === $flip_trigger ) {
            $card_attrs = ' tabindex="0" role="button" aria-pressed="false" aria-label="' . esc_attr( $a['title'] ?: __( 'Flip card', 'rawnaq' ) ) . '"';
        }

        ob_start();
        ?>
        <div class="rawnaq-tilt-container">
            <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
                 style="<?php echo esc_attr( $style ); ?>"
                 data-tilt-max="<?php echo esc_attr( $a['maxTilt'] ); ?>"
                 data-hover-scale="<?php echo esc_attr( $a['hoverScale'] ); ?>"
                 data-glare="<?php echo esc_attr( $a['glare'] ); ?>"<?php
					echo $card_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped above.
				?>>
                <?php if ( $enable_flip ) : ?>
                    <div class="rawnaq-tilt-flip">
                        <div class="rawnaq-tilt-face rawnaq-tilt-front">
                            <?php echo $front_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts above. ?>
                        </div>
                        <div class="rawnaq-tilt-back" style="<?php echo esc_attr( $back_style ); ?>">
                            <div class="rawnaq-tilt-back-inner">
                                <?php if ( ! empty( $a['backTitle'] ) ) : ?>
                                    <h3 class="rawnaq-tilt-back-title"><?php echo esc_html( $a['backTitle'] ); ?></h3>
                                <?php endif; ?>
                                <?php if ( ! empty( $a['backDesc'] ) ) : ?>
                                    <p class="rawnaq-tilt-back-desc"><?php echo esc_html( $a['backDesc'] ); ?></p>
                                <?php endif; ?>
                                <?php if ( $back_cta && ! empty( $a['backCtaLink'] ) ) : ?>
                                    <a class="rawnaq-tilt-btn rawnaq-tilt-back-btn" href="<?php echo esc_url( $a['backCtaLink'] ); ?>"><?php echo esc_html( $back_cta ); ?></a>
                                <?php elseif ( $back_cta ) : ?>
                                    <span class="rawnaq-tilt-btn is-static"><?php echo esc_html( $back_cta ); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else : ?>
                    <?php echo $front_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts above. ?>
                    <?php if ( ! empty( $a['link'] ) ) : ?>
                        <a class="rawnaq-tilt-stretch-link" href="<?php echo esc_url( $a['link'] ); ?>" target="<?php echo esc_attr( $target ); ?>"<?php if ( $rel_value ) : ?> rel="<?php echo esc_attr( $rel_value ); ?>"<?php endif; ?> aria-label="<?php echo esc_attr( $a['title'] ?: __( 'Open link', 'rawnaq' ) ); ?>"></a>
                    <?php endif; ?>
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
        wp_enqueue_script( 'rawnaq-bridge' );

        $source = sanitize_key( $attributes['source'] ?? 'manual' );
        $block_id = ! empty( $attributes['anchor'] )
            ? sanitize_html_class( $attributes['anchor'] )
            : substr( md5( wp_json_encode( $attributes ) ), 0, 8 );
        $fallback_tl = 'rawnaq-tl-' . $block_id;
        $custom_tl   = trim( (string) ( $attributes['timelineName'] ?? '' ) );
        $tl_name     = function_exists( 'rawnaq_timeline_sanitize_tl_name' )
            ? rawnaq_timeline_sanitize_tl_name( $custom_tl ? $custom_tl : $fallback_tl, $fallback_tl )
            : ( $custom_tl ? $custom_tl : $fallback_tl );

        $use_ajax = false;
        $query_b64 = '';
        $ajax_offset = 0;
        $has_more = false;

        if ( 'query' === $source && function_exists( 'rawnaq_timeline_query_result' ) ) {
            $payload = [
                'post_type' => $attributes['postType'] ?? 'post',
                'orderby'   => $attributes['orderby'] ?? 'date',
                'order'     => $attributes['order'] ?? 'DESC',
                'taxonomy'  => $attributes['taxonomy'] ?? '',
                'terms'     => $attributes['terms'] ?? '',
                'include'     => $attributes['includeIds'] ?? '',
                'exclude_ids' => $attributes['excludeIds'] ?? '',
                'max'         => max( 1, absint( $attributes['postsPerPage'] ?? 6 ) ),
            ];
            $initial_visible_pre = max( 0, absint( $attributes['initialVisible'] ?? 0 ) );
            $use_ajax = $initial_visible_pre > 0;
            $max      = (int) $payload['max'];
            $per_page = $use_ajax ? min( $initial_visible_pre, $max ) : $max;
            $result   = rawnaq_timeline_query_result(
                array_merge( $payload, [
                    'posts_per_page' => $per_page,
                    'offset'         => 0,
                ] ),
                [
                    'builder'  => 'gutenberg',
                    'block_id' => $block_id,
                ]
            );
            $steps       = $result['steps'];
            $ajax_offset = count( $steps );
            $found       = (int) $result['found_posts'];
            $has_more    = $use_ajax && $ajax_offset < $max && $ajax_offset < $found;
            $query_b64   = base64_encode( wp_json_encode( rawnaq_timeline_sanitize_query_args( $payload ) ) );
        } else {
            $steps = json_decode( $attributes['stepsJson'] ?? '[]', true ) ?: [];
            if ( function_exists( 'rawnaq_timeline_filter_steps' ) ) {
                $steps = rawnaq_timeline_filter_steps( $steps, [
                    'source'   => 'manual',
                    'builder'  => 'gutenberg',
                    'block_id' => $block_id,
                ] );
            }
        }

        $layout = sanitize_html_class( $attributes['layout'] ?? 'alternating' );
        if ( ! in_array( $layout, [ 'alternating', 'left', 'right', 'horizontal' ], true ) ) {
            $layout = 'alternating';
        }
        $show_numbers    = ! empty( $attributes['showNumbers'] );
        $initial_visible = max( 0, absint( $attributes['initialVisible'] ?? 0 ) );
        $load_chunk      = max( 1, absint( $attributes['loadChunk'] ?? 3 ) );
        $load_more_text  = trim( (string) ( $attributes['loadMoreText'] ?? '' ) );
        if ( '' === $load_more_text ) {
            $load_more_text = function_exists( 'rawnaq_translate' )
                ? rawnaq_translate( 'load_more', __( 'Load more', 'rawnaq' ) )
                : __( 'Load more', 'rawnaq' );
        } elseif ( function_exists( 'rawnaq_translate' ) ) {
            $load_more_text = rawnaq_translate( 'load_more', $load_more_text );
        }
        $wrap_class = 'rawnaq-timeline-wrapper layout-' . $layout;
        if ( $show_numbers ) {
            $wrap_class .= ' show-numbers';
        }

        $style_vars = [
            '--tl-line-bg'       => sanitize_hex_color( $attributes['lineBg'] ?? '' ) ?: '#e2e8f0',
            '--tl-line-active'   => sanitize_hex_color( $attributes['lineActive'] ?? '' ) ?: '#6366f1',
            '--tl-line-width'    => max( 1, min( 12, absint( $attributes['lineWidth'] ?? 4 ) ) ) . 'px',
            '--tl-bullet-border' => sanitize_hex_color( $attributes['bulletBorder'] ?? '' ) ?: '#cbd5e1',
            '--tl-bullet-active' => sanitize_hex_color( $attributes['bulletActive'] ?? '' ) ?: '#6366f1',
            '--tl-card-bg'       => sanitize_hex_color( $attributes['cardBg'] ?? '' ) ?: '#ffffff',
            '--tl-meta'          => sanitize_hex_color( $attributes['metaColor'] ?? '' ) ?: '#6366f1',
            '--tl-title'         => sanitize_hex_color( $attributes['titleColor'] ?? '' ) ?: '#1a1a1a',
            '--tl-desc'          => sanitize_hex_color( $attributes['descColor'] ?? '' ) ?: '#666666',
            '--tl-cta'           => sanitize_hex_color( $attributes['ctaColor'] ?? '' ) ?: '#6366f1',
            '--tl-card-radius'   => max( 0, min( 40, absint( $attributes['cardRadius'] ?? 16 ) ) ) . 'px',
            '--tl-bullet-size'   => max( 16, min( 48, absint( $attributes['bulletSize'] ?? 28 ) ) ) . 'px',
            '--tl-item-pad-y'    => max( 8, min( 80, absint( $attributes['itemGap'] ?? 20 ) ) ) . 'px',
        ];
        $style_attr = 'scroll-timeline-name: --' . $tl_name . ';';
        foreach ( $style_vars as $prop => $val ) {
            $style_attr .= $prop . ':' . $val . ';';
        }

        $show_load = $use_ajax
            ? $has_more
            : ( $initial_visible > 0 && count( $steps ) > $initial_visible );

        if ( 'query' === $source && function_exists( 'rawnaq_schema_print' ) && function_exists( 'rawnaq_schema_timeline' ) ) {
            rawnaq_schema_print( rawnaq_schema_timeline( $steps ), 'timeline' );
        }

        ob_start();
        ?>
        <div
            class="<?php echo esc_attr( $wrap_class ); ?>"
            data-show-numbers="<?php echo $show_numbers ? '1' : '0'; ?>"
            data-tl-name="<?php echo esc_attr( $tl_name ); ?>"
            data-initial-visible="<?php echo esc_attr( (string) ( $use_ajax ? 0 : $initial_visible ) ); ?>"
            data-load-chunk="<?php echo esc_attr( (string) $load_chunk ); ?>"
            <?php if ( $use_ajax ) : ?>
                data-tl-ajax="1"
                data-tl-offset="<?php echo esc_attr( (string) $ajax_offset ); ?>"
                data-tl-query="<?php echo esc_attr( $query_b64 ); ?>"
                data-tl-layout="<?php echo esc_attr( $layout ); ?>"
            <?php endif; ?>
            style="<?php echo esc_attr( $style_attr ); ?>"
        >
            <div class="rawnaq-timeline-line-bg"></div>
            <div class="rawnaq-timeline-line-active"></div>
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- helper escapes.
            echo rawnaq_timeline_render_items_html( $steps, $layout, $show_numbers, 0 );
            ?>
            <?php if ( $show_load ) : ?>
                <div class="rawnaq-timeline-load-more">
                    <button type="button"><?php echo esc_html( $load_more_text ); ?></button>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_floating_dock_block( $attributes ) {
        wp_enqueue_style( 'rawnaq-floating-dock' );
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_script( 'rawnaq-floating-dock' );

        $a = wp_parse_args( $attributes, [
            'position'         => 'bottom',
            'itemsJson'        => '[]',
            'offset'           => 20,
            'hideMobile'       => false,
            'mobileLabels'     => false,
            'dockBg'           => 'rgba(255,255,255,0.55)',
            'dockBorder'       => 'rgba(255,255,255,0.5)',
            'dockBlur'         => 16,
            'dockRadius'       => 24,
            'dockGap'          => 12,
            'dockPad'          => 10,
            'itemBg'           => '#ffffff',
            'iconColor'        => '#444444',
            'itemSize'         => 48,
            'itemRadius'       => 12,
            'badgeBg'          => '#ef4444',
            'badgeColor'       => '#ffffff',
            'magnify'          => true,
            'maxScale'         => 1.6,
            'whatsappMode'     => false,
            'positionWa'       => 'right',
            'primaryChannel'   => 'whatsapp',
            'agentsJson'       => '[]',
            'defaultMsg'       => '',
            'secCall'          => '',
            'secMessenger'     => '',
            'secEmail'         => '',
            'secTelegram'      => '',
            'timezone'         => 'Asia/Dhaka',
            'scheduleJson'     => '{}',
            'offHoursBehavior' => 'offline_badge',
            'offHoursRedirect' => '',
            'offHoursEmail'    => '',
            'offHoursFormNote' => '',
            'qrFallback'       => true,
            'desktopAction'    => 'choice',
            'triggerDelay'     => 0,
            'triggerScroll'    => 0,
            'greetingText'     => '',
            'hideDesktop'      => false,
            'safeOffset'       => 0,
            'visMode'          => 'all',
            'visIds'           => '',
            'visIncludeFront'  => false,
            'visIncludeProducts' => false,
            'trackClicks'      => true,
        ] );

        if ( ! rawnaq_dock_is_visible( [
            'mode'             => $a['visMode'] ?? 'all',
            'ids'              => $a['visIds'] ?? '',
            'include_front'    => ! empty( $a['visIncludeFront'] ),
            'include_products' => ! empty( $a['visIncludeProducts'] ),
        ] ) ) {
            return '';
        }

        $is_wa = ! empty( $a['whatsappMode'] );
        if ( $is_wa ) {
            wp_enqueue_script( 'rawnaq-qrcode' );
        }

        $track_clicks = ! isset( $a['trackClicks'] ) || ! empty( $a['trackClicks'] );

        $items = json_decode( $a['itemsJson'], true ) ?: [];
        $pos   = $is_wa
            ? sanitize_html_class( $a['positionWa'] ?: 'right' )
            : sanitize_html_class( $a['position'] ?: 'bottom' );
        if ( $is_wa && ! in_array( $pos, [ 'left', 'right' ], true ) ) {
            $pos = 'right';
        }
        if ( ! $is_wa && ! in_array( $pos, [ 'bottom', 'left', 'right' ], true ) ) {
            $pos = 'bottom';
        }

        $classes = [ 'rawnaq-dock-container', 'pos-' . $pos ];
        if ( $is_wa ) {
            $classes[] = 'rawnaq-whatsapp-dock-mode';
        }
        if ( ! empty( $a['hideMobile'] ) ) {
            $classes[] = 'hide-mobile';
        }
        if ( ! empty( $a['hideDesktop'] ) ) {
            $classes[] = 'hide-desktop';
        }
        if ( ! empty( $a['mobileLabels'] ) ) {
            $classes[] = 'mobile-labels';
        }

        $style_parts = [
            '--dock-offset:' . absint( $a['offset'] ) . 'px',
            '--dock-safe-offset:' . absint( $a['safeOffset'] ) . 'px',
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

        $wa_attr = '';
        if ( $is_wa ) {
            $agents_raw = json_decode( $a['agentsJson'], true );
            $agents     = [];
            if ( is_array( $agents_raw ) ) {
                foreach ( $agents_raw as $agent ) {
                    $agents[] = [
                        'name'   => sanitize_text_field( $agent['name'] ?? '' ),
                        'role'   => sanitize_text_field( $agent['role'] ?? '' ),
                        'number' => sanitize_text_field( $agent['number'] ?? '' ) ?: ( function_exists( 'rawnaq_get_default_wa_number' ) ? rawnaq_get_default_wa_number() : '' ),
                        'avatar' => ! empty( $agent['avatar'] ) ? esc_url_raw( $agent['avatar'] ) : '',
                        'msg'    => sanitize_textarea_field( $agent['msg'] ?? '' ),
                    ];
                }
            }
            $schedule = json_decode( $a['scheduleJson'], true );
            if ( ! is_array( $schedule ) ) {
                $schedule = [];
            }
            $wa_cfg = [
                'whatsappMode'     => true,
                'primaryChannel'   => sanitize_key( $a['primaryChannel'] ?: 'whatsapp' ),
                'agents'           => $agents,
                'defaultMsg'       => sanitize_textarea_field( $a['defaultMsg'] ?? '' ),
                'pageContext'      => function_exists( 'rawnaq_get_wa_page_context' ) ? rawnaq_get_wa_page_context() : [],
                'secCall'          => sanitize_text_field( $a['secCall'] ),
                'secMessenger'     => sanitize_text_field( $a['secMessenger'] ),
                'secEmail'         => sanitize_email( $a['secEmail'] ),
                'secTelegram'      => sanitize_text_field( $a['secTelegram'] ),
                'timezone'         => sanitize_text_field( $a['timezone'] ?: 'Asia/Dhaka' ),
                'schedule'         => $schedule,
                'offHoursBehavior' => sanitize_key( $a['offHoursBehavior'] ?: 'offline_badge' ),
                'offHoursRedirect' => esc_url_raw( $a['offHoursRedirect'] ),
                'offHoursEmail'    => sanitize_email( $a['offHoursEmail'] ?? '' ),
                'offHoursFormNote' => sanitize_textarea_field( $a['offHoursFormNote'] ?? '' ),
                'qrFallback'       => ( $a['desktopAction'] ?? 'choice' ) !== 'web',
                'desktopAction'    => in_array( ( $a['desktopAction'] ?? 'choice' ), [ 'choice', 'web', 'qr' ], true )
                    ? ( $a['desktopAction'] ?? 'choice' )
                    : 'choice',
                'triggerDelay'     => absint( $a['triggerDelay'] ),
                'triggerScroll'    => absint( $a['triggerScroll'] ),
                'greetingText'     => sanitize_text_field( $a['greetingText'] ),
                'trackClicks'      => $track_clicks,
            ];
            $wa_attr = rawurlencode( wp_json_encode( $wa_cfg ) );
        }

        ob_start();
        ?>
        <nav class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             style="<?php echo esc_attr( $style ); ?>"
             aria-label="<?php echo esc_attr__( 'Floating dock', 'rawnaq' ); ?>"
             data-magnify="<?php echo $magnify ? '1' : '0'; ?>"
             data-max-scale="<?php echo esc_attr( $max_scale ); ?>"
             data-base-size="<?php echo esc_attr( absint( $a['itemSize'] ) ); ?>"
             data-track-clicks="<?php echo $track_clicks ? '1' : '0'; ?>"
             <?php if ( $wa_attr ) : ?>
             data-wa-dock="<?php echo esc_attr( $wa_attr ); ?>"
             <?php endif; ?>>
            <?php if ( ! $is_wa ) : ?>
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
            <?php endif; ?>
        </nav>
        <?php
        return ob_get_clean();
    }

    public function render_flow_chart_block( $attributes ) {
        wp_enqueue_style( 'rawnaq-flow-chart' );
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_script( 'rawnaq-flow-chart' );

        $mode = sanitize_key( $attributes['mode'] ?? 'org' );
        if ( ! in_array( $mode, [ 'org', 'process', 'freeform' ], true ) ) {
            $mode = 'org';
        }
        $conn = sanitize_key( $attributes['connector'] ?? 'curved' );
        if ( ! in_array( $conn, [ 'curved', 'elbow', 'straight', 'dashed' ], true ) ) {
            $conn = 'curved';
        }
        $dir = sanitize_key( $attributes['direction'] ?? 'tb' );
        if ( ! in_array( $dir, [ 'tb', 'lr', 'rl' ], true ) ) {
            $dir = 'tb';
        }
        $shape = sanitize_key( $attributes['shape'] ?? 'rect' );
        if ( 'hexagon' === $shape ) {
            $shape = 'hex';
        }
        if ( ! in_array( $shape, [ 'rect', 'circle', 'hex' ], true ) ) {
            $shape = 'rect';
        }

        $avatar_shape = sanitize_key( $attributes['avatarShape'] ?? 'rounded' );
        if ( ! in_array( $avatar_shape, [ 'rounded', 'circle', 'square' ], true ) ) {
            $avatar_shape = 'rounded';
        }

        $source = sanitize_key( $attributes['dataSource'] ?? 'manual' );
        if ( 'wp_users' === $source && function_exists( 'rawnaq_flow_nodes_from_users' ) ) {
            $nodes = rawnaq_flow_nodes_from_users( [
                'number' => absint( $attributes['usersNumber'] ?? 20 ),
                'role'   => sanitize_key( $attributes['usersRole'] ?? '' ),
            ] );
            if ( 'freeform' === $mode ) {
                $mode = 'org';
            }
            // DFS cycle break.
            $by_id = [];
            foreach ( $nodes as $n ) {
                $by_id[ $n['id'] ] = $n;
            }
            foreach ( $nodes as &$n ) {
                $parent = $n['parent'] ?? '';
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
        } else {
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
            $x = isset( $item['x'] ) ? (float) $item['x'] : (float) ( $item['x_pos'] ?? 10 );
            $y = isset( $item['y'] ) ? (float) $item['y'] : (float) ( $item['y_pos'] ?? 10 );
            $nodes[] = [
                'id'       => $id,
                'parent'   => $parent,
                'title'    => sanitize_text_field( $item['title'] ?? '' ),
                'role'     => sanitize_text_field( $item['role'] ?? '' ),
                'icon'     => sanitize_text_field( $item['icon'] ?? '' ),
                'image'    => ! empty( $item['image'] ) ? esc_url_raw( $item['image'] ) : ( ! empty( $item['imageUrl'] ) ? esc_url_raw( $item['imageUrl'] ) : '' ),
                'edgeLabel' => sanitize_text_field( $item['edgeLabel'] ?? '' ),
                'lane'     => sanitize_text_field( $item['lane'] ?? '' ),
                'detail'   => sanitize_textarea_field( $item['detail'] ?? '' ),
                'link'     => ! empty( $item['link'] ) ? esc_url_raw( $item['link'] ) : '',
                'decision' => ! empty( $item['decision'] ),
                'x'        => max( 0, min( 100, $x ) ),
                'y'        => max( 0, min( 100, $y ) ),
            ];
        }
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
        } // end manual nodes

        $cfg = [
            'mode'        => $mode,
            'direction'   => $dir,
            'shape'       => $shape,
            'connector'   => $conn,
            'avatarShape' => $avatar_shape,
            'zoom'        => ! isset( $attributes['enableZoom'] ) || ! empty( $attributes['enableZoom'] ),
            'export'      => ! isset( $attributes['showExport'] ) || ! empty( $attributes['showExport'] ),
            'nodes'       => $nodes,
        ];

        $avatar_size   = max( 24, min( 96, absint( $attributes['avatarSize'] ?? 30 ) ) );
        $avatar_gap    = max( 0, min( 24, absint( $attributes['avatarGap'] ?? 8 ) ) );
        $avatar_icon_s = max( 10, min( 48, absint( $attributes['avatarIconSize'] ?? 14 ) ) );
        $avatar_bw     = max( 0, min( 8, absint( $attributes['avatarBorderWidth'] ?? 0 ) ) );
        $avatar_fit    = sanitize_key( $attributes['avatarObjectFit'] ?? 'cover' );
        if ( ! in_array( $avatar_fit, [ 'cover', 'contain', 'fill' ], true ) ) {
            $avatar_fit = 'cover';
        }
        $avatar_bg     = sanitize_hex_color( $attributes['avatarBg'] ?? '' ) ?: '#FEF3C7';
        $avatar_icon_c = sanitize_hex_color( $attributes['avatarIconColor'] ?? '' ) ?: '#92400E';
        $avatar_border = sanitize_hex_color( $attributes['avatarBorderColor'] ?? '' ) ?: 'transparent';
        $has_shadow    = ! empty( $attributes['avatarShadow'] );

        $accent     = sanitize_hex_color( $attributes['accentColor'] ?? '' ) ?: '#FBBF24';
        $root_from  = sanitize_hex_color( $attributes['rootColorFrom'] ?? '' ) ?: '#4338CA';
        $root_to    = sanitize_hex_color( $attributes['rootColorTo'] ?? '' ) ?: '#7C3AED';
        $line_color = sanitize_hex_color( $attributes['lineColor'] ?? '' ) ?: '#E6E2F0';
        $node_bg    = sanitize_hex_color( $attributes['nodeBg'] ?? '' ) ?: '#ffffff';
        $node_radius = max( 0, min( 40, absint( $attributes['nodeRadius'] ?? 14 ) ) );

        $style = sprintf(
            '--fc-amber:%1$s;--fc-indigo:%2$s;--fc-violet:%3$s;--fc-line:%4$s;--fc-panel:%5$s;--fc-radius:%6$dpx;--fc-avatar:%7$dpx;--fc-avatar-gap:%8$dpx;--fc-avatar-bg:%9$s;--fc-avatar-icon:%10$s;--fc-avatar-icon-size:%11$dpx;--fc-avatar-border:%12$s;--fc-avatar-border-w:%13$dpx;--fc-avatar-fit:%14$s;',
            $accent,
            $root_from,
            $root_to,
            $line_color,
            $node_bg,
            $node_radius,
            $avatar_size,
            $avatar_gap,
            $avatar_bg,
            $avatar_icon_c,
            $avatar_icon_s,
            $avatar_border,
            $avatar_bw,
            $avatar_fit
        );

        ob_start();
        ?>
        <div class="rawnaq-flow-chart avatar-<?php echo esc_attr( $avatar_shape ); ?><?php echo $has_shadow ? ' has-avatar-shadow' : ''; ?>"
             style="<?php echo esc_attr( $style ); ?>"
             data-flow="<?php echo esc_attr( rawurlencode( wp_json_encode( $cfg ) ) ); ?>">
            <div class="rawnaq-flow-viewport">
                <div class="rawnaq-flow-stage is-responsive"></div>
            </div>
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
            'collapseSubs'   => ! empty( $attributes['collapseSubs'] ),
            'showSearch'     => ! empty( $attributes['showSearch'] ),
            'smooth'         => ! empty( $attributes['smooth'] ),
            'scrollOffset'   => absint( $attributes['scrollOffset'] ?? 80 ),
            'readingTime'    => ! empty( $attributes['readingTime'] ),
            'mobileCollapse' => ! empty( $attributes['mobileCollapse'] ),
            'dockAttach'     => ! empty( $attributes['dockAttach'] ),
            'syncTimeline'   => sanitize_text_field( $attributes['syncTimeline'] ?? '' ),
            'scope'          => sanitize_text_field( $attributes['contentSelector'] ?? '' ),
            'hideIfShort'    => ! isset( $attributes['hideIfShort'] ) || ! empty( $attributes['hideIfShort'] ),
        ];
        $ring_size = absint( $attributes['ringSize'] ?? 56 );
        if ( $ring_size < 40 ) {
            $ring_size = 40;
        }
        if ( $ring_size > 96 ) {
            $ring_size = 96;
        }
        if ( ! in_array( $cfg['progress'], [ 'bar', 'ring', 'both', 'none' ], true ) ) {
            $cfg['progress'] = 'both';
        }
        if ( ! in_array( $cfg['tocPosition'], [ 'sticky', 'floating', 'inline', 'none' ], true ) ) {
            $cfg['tocPosition'] = 'sticky';
        }

        ob_start();
        ?>
        <div class="rawnaq-spt"
             style="--spt-offset: <?php echo esc_attr( (string) $cfg['scrollOffset'] ); ?>px; --spt-ring-size: <?php echo esc_attr( (string) $ring_size ); ?>px;"
             data-spt="<?php echo esc_attr( wp_json_encode( $cfg ) ); ?>">
            <?php if ( 'none' !== $cfg['tocPosition'] ) : ?>
                <nav class="rawnaq-spt-toc is-<?php echo esc_attr( $cfg['tocPosition'] ); ?>" aria-label="<?php echo esc_attr( $cfg['tocTitle'] ); ?>">
                    <p class="rawnaq-spt-reading" hidden></p>
                    <p class="rawnaq-spt-chapter" hidden></p>
                    <h3 class="rawnaq-spt-title"><?php echo esc_html( $cfg['tocTitle'] ); ?></h3>
                    <ul class="rawnaq-spt-list"></ul>
                </nav>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_bento_grid_block( $attributes, $content = '' ) {
        wp_enqueue_style( 'rawnaq-bento-grid' );
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_script( 'rawnaq-bento-grid' );

        $a = wp_parse_args( $attributes, [
            'preset'      => 'featured',
            'columns'     => 4,
            'rowHeight'   => 140,
            'gap'         => 16,
            'columnGap'   => 16,
            'rowGap'      => 16,
            'radius'      => 18,
            'reveal'      => true,
            'hoverEffect' => 'lift',
            'hairline'    => false,
            'overlayOpacity' => 100,
            'tagBg'       => '#fef3c7',
            'tagColor'    => '#92400e',
            'titleColor'  => '#13231c',
            'subColor'    => '#5c6f66',
            'iconColor'   => '#0f766e',
            'statColor'   => '#0f766e',
            'cellBg'      => '#ffffff',
            'cellBorder'  => '#d7e2dc',
            'featuredFrom'=> '#0f766e',
            'featuredTo'  => '#134e4a',
            'ctaBg'       => '#fbbf24',
            'ctaColor'    => '#92400e',
            'cellsJson'   => '[]',
        ] );

        $preset = sanitize_key( $a['preset'] );
        if ( 'wide' === $preset ) {
            $cols = 3;
        } elseif ( 'custom' === $preset ) {
            $cols = max( 2, min( 6, absint( $a['columns'] ) ?: 4 ) );
        } else {
            $cols = 4;
        }

        $hover = sanitize_key( $a['hoverEffect'] );
        if ( ! in_array( $hover, [ 'lift', 'zoom', 'tint', 'none' ], true ) ) {
            $hover = 'lift';
        }

        $classes = [ 'rawnaq-bento-grid' ];
        if ( ! empty( $a['hairline'] ) ) {
            $classes[] = 'rawnaq-bento-hairline';
        }

        $overlay_opacity = isset( $a['overlayOpacity'] ) ? floatval( $a['overlayOpacity'] ) : 100;
        $overlay_opacity = max( 0, min( 100, $overlay_opacity ) ) / 100;

        $legacy_gap = absint( $attributes['gap'] ?? 16 );
        $col_gap    = array_key_exists( 'columnGap', $attributes ) ? absint( $a['columnGap'] ) : $legacy_gap;
        $row_gap    = array_key_exists( 'rowGap', $attributes ) ? absint( $a['rowGap'] ) : $legacy_gap;

        $style = sprintf(
            '--bento-row:%dpx;--bento-gap-col:%dpx;--bento-gap-row:%dpx;--bento-radius:%dpx;--bento-tag-bg:%s;--bento-tag-color:%s;--bento-title-color:%s;--bento-sub-color:%s;--bento-icon-color:%s;--bento-stat-color:%s;--bento-panel:%s;--bento-line:%s;--bento-featured-from:%s;--bento-featured-to:%s;--bento-accent:%s;--bento-overlay-opacity:%s;--bento-cta-bg:%s;--bento-cta-color:%s;',
            absint( $a['rowHeight'] ),
            $col_gap,
            $row_gap,
            absint( $a['radius'] ),
            esc_attr( $a['tagBg'] ),
            esc_attr( $a['tagColor'] ),
            esc_attr( $a['titleColor'] ),
            esc_attr( $a['subColor'] ),
            esc_attr( $a['iconColor'] ),
            esc_attr( $a['statColor'] ),
            esc_attr( $a['cellBg'] ),
            esc_attr( $a['cellBorder'] ),
            esc_attr( $a['featuredFrom'] ),
            esc_attr( $a['featuredTo'] ),
            esc_attr( $a['iconColor'] ?: '#0f766e' ),
            esc_attr( (string) $overlay_opacity ),
            esc_attr( $a['ctaBg'] ),
            esc_attr( $a['ctaColor'] )
        );

        // InnerBlocks path (saved cell markup).
        if ( is_string( $content ) && '' !== trim( $content ) ) {
            $classes[] = 'is-innerblocks';
            ob_start();
            ?>
            <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
                 style="<?php echo esc_attr( $style ); ?>"
                 data-cols="<?php echo esc_attr( (string) $cols ); ?>"
                 data-reveal="<?php echo ! empty( $a['reveal'] ) ? '1' : '0'; ?>"
                 data-hover="<?php echo esc_attr( $hover ); ?>"
                 role="list">
                <?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block editor HTML ?>
            </div>
            <?php
            return ob_get_clean();
        }

        $cells_raw = json_decode( $a['cellsJson'], true );
        if ( ! is_array( $cells_raw ) ) {
            $cells_raw = [];
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             style="<?php echo esc_attr( $style ); ?>"
             data-cols="<?php echo esc_attr( (string) $cols ); ?>"
             data-reveal="<?php echo ! empty( $a['reveal'] ) ? '1' : '0'; ?>"
             data-hover="<?php echo esc_attr( $hover ); ?>"
             role="list">
            <?php foreach ( $cells_raw as $cell ) :
                $type     = sanitize_key( $cell['type'] ?? 'text' );
                $tag      = sanitize_text_field( $cell['tag'] ?? '' );
                $title    = sanitize_text_field( $cell['title'] ?? '' );
                $subtitle = sanitize_text_field( $cell['subtitle'] ?? '' );
                $icon     = sanitize_html_class( $cell['icon'] ?? '' );
                $image    = ! empty( $cell['image'] ) ? esc_url( $cell['image'] ) : '';
                $video    = ! empty( $cell['video'] ) ? esc_url( $cell['video'] ) : '';
                $link     = ! empty( $cell['link'] ) ? esc_url( $cell['link'] ) : '';
                $stat     = sanitize_text_field( $cell['stat'] ?? '' );
                $suffix   = sanitize_text_field( $cell['suffix'] ?? '' );
                $prefix   = sanitize_text_field( $cell['prefix'] ?? '' );
                $num      = floatval( preg_replace( '/[^\d.]/', '', $stat ) );
                $cta_text = sanitize_text_field( $cell['ctaText'] ?? '' );
                $cta_link = ! empty( $cell['ctaLink'] ) ? esc_url( $cell['ctaLink'] ) : '';
                if ( $cta_text && ! $cta_link && $link ) {
                    $cta_link = $link;
                }
                $has_cta = '' !== $cta_text;
                $tag_attrs = function_exists( 'rawnaq_bento_tag_attrs' )
                    ? rawnaq_bento_tag_attrs( $cell['tagBg'] ?? '', $cell['tagColor'] ?? '' )
                    : [ 'class' => 'rawnaq-bento-tag', 'style' => '' ];
                $role   = sanitize_text_field( $cell['role'] ?? '' );
                $avatar = ! empty( $cell['avatar'] ) ? esc_url( $cell['avatar'] ) : '';
                $rating = max( 0, min( 5, absint( $cell['rating'] ?? 0 ) ) );

                $layout = function_exists( 'rawnaq_bento_cell_layout' )
                    ? rawnaq_bento_cell_layout( [
                        'col'      => $cell['col'] ?? 1,
                        'row'      => $cell['row'] ?? 1,
                        'order'    => $cell['order'] ?? 0,
                        'col_md'   => $cell['colMd'] ?? 0,
                        'row_md'   => $cell['rowMd'] ?? 0,
                        'order_md' => $cell['orderMd'] ?? 0,
                        'col_sm'   => $cell['colSm'] ?? 0,
                        'row_sm'   => $cell['rowSm'] ?? 0,
                        'order_sm' => $cell['orderSm'] ?? 0,
                    ] )
                    : [
                        'style'   => sprintf(
                            'grid-column:span %d;grid-row:span %d;',
                            max( 1, absint( $cell['col'] ?? 1 ) ),
                            max( 1, absint( $cell['row'] ?? 1 ) )
                        ),
                        'classes' => [],
                    ];

                $cell_classes = array_merge( [ 'rawnaq-bento-cell' ], $layout['classes'] );
                $align_class  = function_exists( 'rawnaq_bento_align_class' )
                    ? rawnaq_bento_align_class( $cell['align'] ?? '' )
                    : '';
                if ( $align_class ) {
                    $cell_classes[] = $align_class;
                }
                if ( 'featured' === $type ) {
                    $cell_classes[] = 'is-featured';
                } elseif ( 'image' === $type ) {
                    $cell_classes[] = 'is-image';
                } elseif ( 'stat' === $type ) {
                    $cell_classes[] = 'is-stat';
                } elseif ( 'video' === $type ) {
                    $cell_classes[] = 'is-video';
                    $vid_parsed     = function_exists( 'rawnaq_bento_parse_video' ) ? rawnaq_bento_parse_video( $video ) : null;
                    if ( $vid_parsed && in_array( $vid_parsed['kind'], [ 'youtube', 'vimeo' ], true ) ) {
                        $cell_classes[] = 'is-embed';
                    }
                } elseif ( 'testimonial' === $type ) {
                    $cell_classes[] = 'is-testimonial';
                }

                $sync_raw  = trim( (string) ( $cell['timelineSync'] ?? $cell['sync_timeline'] ?? '' ) );
                $sync_name = $sync_raw ? ( function_exists( 'rawnaq_timeline_sanitize_tl_name' )
                    ? rawnaq_timeline_sanitize_tl_name( $sync_raw, $sync_raw )
                    : preg_replace( '/[^a-zA-Z0-9_-]/', '', $sync_raw ) ) : '';
                if ( $sync_name ) {
                    $cell_classes[] = 'tl-sync';
                }

                $cell_style = $layout['style'];
                if ( $sync_name ) {
                    $cell_style .= 'animation-timeline:--' . $sync_name . ';';
                }
                $tag_name = ( $link && ! $has_cta ) ? 'a' : 'div';
                if ( ! in_array( $tag_name, [ 'a', 'div' ], true ) ) {
                    $tag_name = 'div';
                }
                ?>
                <<?php echo tag_escape( $tag_name ); ?>
                    class="<?php echo esc_attr( implode( ' ', $cell_classes ) ); ?>"
                    style="<?php echo esc_attr( $cell_style ); ?>"
                    <?php if ( $sync_name ) : ?>data-tl-sync="<?php echo esc_attr( $sync_name ); ?>"<?php endif; ?>
                    <?php if ( $link && ! $has_cta ) : ?>href="<?php echo esc_url( $link ); ?>"<?php endif; ?>
                    role="listitem">
                    <?php if ( 'testimonial' === $type ) : ?>
                        <?php if ( $tag ) : ?>
                            <div class="<?php echo esc_attr( $tag_attrs['class'] ); ?>"<?php echo $tag_attrs['style'] ? ' style="' . esc_attr( $tag_attrs['style'] ) . '"' : ''; ?>><?php echo esc_html( $tag ); ?></div>
                        <?php endif; ?>
                        <?php if ( $subtitle ) : ?>
                            <blockquote class="rawnaq-bento-quote"><?php echo esc_html( $subtitle ); ?></blockquote>
                        <?php endif; ?>
                        <?php if ( $rating > 0 ) : ?>
                            <div class="rawnaq-bento-stars" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: star count */ __( '%d out of 5 stars', 'rawnaq' ), $rating ) ); ?>"><?php echo esc_html( str_repeat( '★', $rating ) ); ?></div>
                        <?php endif; ?>
                        <?php if ( $title || $role || $avatar ) : ?>
                            <div class="rawnaq-bento-author">
                                <?php if ( $avatar ) : ?>
                                    <img class="rawnaq-bento-avatar" src="<?php echo esc_url( $avatar ); ?>" alt="" loading="lazy" decoding="async" />
                                <?php elseif ( $title ) : ?>
                                    <div class="rawnaq-bento-avatar is-placeholder" aria-hidden="true"><?php echo esc_html( strtoupper( function_exists( 'mb_substr' ) ? mb_substr( $title, 0, 1 ) : substr( $title, 0, 1 ) ) ); ?></div>
                                <?php endif; ?>
                                <?php if ( $title || $role ) : ?>
                                    <div class="rawnaq-bento-author-meta">
                                        <?php if ( $title ) : ?><div class="rawnaq-bento-author-name"><?php echo esc_html( $title ); ?></div><?php endif; ?>
                                        <?php if ( $role ) : ?><div class="rawnaq-bento-author-role"><?php echo esc_html( $role ); ?></div><?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ( $has_cta ) : ?>
                            <?php if ( $cta_link ) : ?>
                                <a class="rawnaq-bento-cta" href="<?php echo esc_url( $cta_link ); ?>"><?php echo esc_html( $cta_text ); ?></a>
                            <?php else : ?>
                                <span class="rawnaq-bento-cta is-static"><?php echo esc_html( $cta_text ); ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php elseif ( 'image' === $type ) : ?>
                        <?php if ( $image ) : ?>
                            <img class="rawnaq-bento-media" src="<?php echo esc_url( $image ); ?>" alt="" loading="lazy" decoding="async" />
                        <?php else : ?>
                            <div class="rawnaq-bento-media" style="background:linear-gradient(135deg,#0f766e,#134e4a);" aria-hidden="true"></div>
                        <?php endif; ?>
                        <div class="rawnaq-bento-overlay" aria-hidden="true"></div>
                        <div class="rawnaq-bento-body">
                            <?php if ( $tag ) : ?>
                                <div class="<?php echo esc_attr( $tag_attrs['class'] ); ?>"<?php echo $tag_attrs['style'] ? ' style="' . esc_attr( $tag_attrs['style'] ) . '"' : ''; ?>><?php echo esc_html( $tag ); ?></div>
                            <?php endif; ?>
                            <?php if ( $title ) : ?><div class="rawnaq-bento-title"><?php echo esc_html( $title ); ?></div><?php endif; ?>
                            <?php if ( $subtitle ) : ?><div class="rawnaq-bento-sub"><?php echo esc_html( $subtitle ); ?></div><?php endif; ?>
                            <?php if ( $has_cta ) : ?>
                                <?php if ( $cta_link ) : ?>
                                    <a class="rawnaq-bento-cta" href="<?php echo esc_url( $cta_link ); ?>"><?php echo esc_html( $cta_text ); ?></a>
                                <?php else : ?>
                                    <span class="rawnaq-bento-cta is-static"><?php echo esc_html( $cta_text ); ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php elseif ( 'video' === $type ) : ?>
                        <?php if ( function_exists( 'rawnaq_bento_video_markup' ) ) : ?>
                            <?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup helper escapes.
                            echo rawnaq_bento_video_markup( $video );
                            ?>
                        <?php elseif ( $video ) : ?>
                            <video class="rawnaq-bento-video" muted playsinline loop preload="metadata" src="<?php echo esc_url( $video ); ?>"></video>
                        <?php else : ?>
                            <div class="rawnaq-bento-media" style="background:#1e1b2e;" aria-hidden="true"></div>
                        <?php endif; ?>
                        <div class="rawnaq-bento-overlay" aria-hidden="true"></div>
                        <div class="rawnaq-bento-body">
                            <?php if ( $tag ) : ?>
                                <div class="<?php echo esc_attr( $tag_attrs['class'] ); ?>"<?php echo $tag_attrs['style'] ? ' style="' . esc_attr( $tag_attrs['style'] ) . '"' : ''; ?>><?php echo esc_html( $tag ); ?></div>
                            <?php endif; ?>
                            <?php if ( $title ) : ?><div class="rawnaq-bento-title"><?php echo esc_html( $title ); ?></div><?php endif; ?>
                            <?php if ( $subtitle ) : ?><div class="rawnaq-bento-sub"><?php echo esc_html( $subtitle ); ?></div><?php endif; ?>
                            <?php if ( $has_cta ) : ?>
                                <?php if ( $cta_link ) : ?>
                                    <a class="rawnaq-bento-cta" href="<?php echo esc_url( $cta_link ); ?>"><?php echo esc_html( $cta_text ); ?></a>
                                <?php else : ?>
                                    <span class="rawnaq-bento-cta is-static"><?php echo esc_html( $cta_text ); ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php elseif ( 'stat' === $type ) : ?>
                        <?php if ( $tag ) : ?>
                            <div class="<?php echo esc_attr( $tag_attrs['class'] ); ?>"<?php echo $tag_attrs['style'] ? ' style="' . esc_attr( $tag_attrs['style'] ) . '"' : ''; ?>><?php echo esc_html( $tag ); ?></div>
                        <?php endif; ?>
                        <div class="rawnaq-bento-num"
                             data-count="<?php echo esc_attr( (string) $num ); ?>"
                             data-suffix="<?php echo esc_attr( $suffix ); ?>"
                             data-prefix="<?php echo esc_attr( $prefix ); ?>"><?php echo esc_html( $prefix . $stat . $suffix ); ?></div>
                        <?php if ( $subtitle ) : ?><div class="rawnaq-bento-sub"><?php echo esc_html( $subtitle ); ?></div><?php endif; ?>
                        <?php if ( $has_cta ) : ?>
                            <?php if ( $cta_link ) : ?>
                                <a class="rawnaq-bento-cta" href="<?php echo esc_url( $cta_link ); ?>"><?php echo esc_html( $cta_text ); ?></a>
                            <?php else : ?>
                                <span class="rawnaq-bento-cta is-static"><?php echo esc_html( $cta_text ); ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else : ?>
                        <?php if ( $tag ) : ?>
                            <div class="<?php echo esc_attr( $tag_attrs['class'] ); ?>"<?php echo $tag_attrs['style'] ? ' style="' . esc_attr( $tag_attrs['style'] ) . '"' : ''; ?>><?php echo esc_html( $tag ); ?></div>
                        <?php endif; ?>
                        <?php if ( $icon ) : ?>
                            <div class="rawnaq-bento-icon" aria-hidden="true"><span class="dashicons <?php echo esc_attr( $icon ); ?>"></span></div>
                        <?php endif; ?>
                        <?php if ( $title ) : ?><div class="rawnaq-bento-title"><?php echo esc_html( $title ); ?></div><?php endif; ?>
                        <?php if ( $subtitle ) : ?><div class="rawnaq-bento-sub"><?php echo esc_html( $subtitle ); ?></div><?php endif; ?>
                        <?php if ( $has_cta ) : ?>
                            <?php if ( $cta_link ) : ?>
                                <a class="rawnaq-bento-cta" href="<?php echo esc_url( $cta_link ); ?>"><?php echo esc_html( $cta_text ); ?></a>
                            <?php else : ?>
                                <span class="rawnaq-bento-cta is-static"><?php echo esc_html( $cta_text ); ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </<?php echo tag_escape( $tag_name ); ?>>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_scroll_story_block( $attributes ) {
        wp_enqueue_style( 'rawnaq-scroll-story' );
        wp_enqueue_script( 'rawnaq-scroll-story' );
        wp_enqueue_script( 'rawnaq-bridge' );

        $raw = json_decode( $attributes['chaptersJson'] ?? '[]', true );
        if ( ! is_array( $raw ) ) {
            $raw = [];
        }
        $chapters = [];
        foreach ( $raw as $row ) {
            $chapters[] = [
                'title'       => sanitize_text_field( $row['title'] ?? '' ),
                'body'        => wp_kses_post( $row['body'] ?? '' ),
                'image'       => esc_url_raw( $row['image'] ?? '' ),
                'imageAlt'    => sanitize_text_field( $row['imageAlt'] ?? '' ),
                'video'       => esc_url_raw( $row['video'] ?? '' ),
                'anchor'      => sanitize_title( $row['anchor'] ?? '' ),
                'caption'     => sanitize_text_field( $row['caption'] ?? '' ),
                'ctaText'     => sanitize_text_field( $row['ctaText'] ?? '' ),
                'ctaUrl'      => esc_url_raw( $row['ctaUrl'] ?? '' ),
                'ctaExt'      => false,
                'ctaNof'      => false,
                'projectId'   => sanitize_text_field( $row['projectId'] ?? '' ),
                'projectSlug' => sanitize_title( $row['projectSlug'] ?? '' ),
            ];
        }
        if ( ! $chapters ) {
            return '';
        }

        $side   = ( ( $attributes['mediaSide'] ?? 'left' ) === 'right' ) ? 'right' : 'left';
        $accent = sanitize_hex_color( $attributes['accent'] ?? '' );
        if ( ! $accent ) {
            $accent = '#0f766e';
        }
        $pin_top = absint( $attributes['pinTop'] ?? 96 );
        $pin_top = max( 40, min( 180, $pin_top ) );

        ob_start();
        printf(
            '<div style="--story-accent: %1$s; --story-pin-top: %2$dpx;">',
            esc_attr( $accent ),
            $pin_top
        );
        rawnaq_scroll_story_markup( $chapters, $side );
        echo '</div>';
        return ob_get_clean();
    }

    public function render_smart_form_block( $attributes ) {
        wp_enqueue_style( 'rawnaq-smart-form' );
        wp_enqueue_script( 'rawnaq-smart-form' );

        $fields = json_decode( $attributes['fieldsJson'] ?? '[]', true );
        if ( ! is_array( $fields ) ) {
            $fields = [];
        }

        $cfg = [
            'fields'            => $fields,
            'deliveryEmail'     => ! empty( $attributes['deliveryEmail'] ),
            'deliveryWhatsapp'  => ! empty( $attributes['deliveryWhatsapp'] ),
            'emailTo'           => $attributes['emailTo'] ?? '',
            'emailSubject'      => $attributes['emailSubject'] ?? '',
            'waNumber'          => $attributes['waNumber'] ?? '',
            'waTemplate'        => $attributes['waTemplate'] ?? '',
            'afterSubmit'       => $attributes['afterSubmit'] ?? 'message',
            'redirectUrl'       => $attributes['redirectUrl'] ?? '',
            'submitLabel'       => $attributes['submitLabel'] ?? '',
            'successMessage'    => $attributes['successMessage'] ?? '',
            'errorMessage'      => $attributes['errorMessage'] ?? '',
            'consentEnabled'    => ! empty( $attributes['consentEnabled'] ),
            'consentText'       => $attributes['consentText'] ?? '',
            'logSubmissions'    => ! isset( $attributes['logSubmissions'] ) || ! empty( $attributes['logSubmissions'] ),
            'recaptchaEnabled'  => ! empty( $attributes['recaptchaEnabled'] ),
            'webhookEnabled'    => ! empty( $attributes['webhookEnabled'] ),
            'webhookUrl'        => $attributes['webhookUrl'] ?? '',
            'emailHtml'         => ! isset( $attributes['emailHtml'] ) || ! empty( $attributes['emailHtml'] ),
            'crmProvider'       => sanitize_key( $attributes['crmProvider'] ?? 'none' ),
            'crmAudience'       => sanitize_text_field( $attributes['crmAudience'] ?? '' ),
            'buttonFullWidth'   => ! empty( $attributes['buttonFullWidth'] ),
            'labelColor'        => sanitize_hex_color( $attributes['labelColor'] ?? '' ) ?: '',
            'inputBg'           => sanitize_hex_color( $attributes['inputBg'] ?? '' ) ?: '',
            'inputBorder'       => sanitize_hex_color( $attributes['inputBorder'] ?? '' ) ?: '',
            'buttonBg'          => sanitize_hex_color( $attributes['accent'] ?? '' ) ?: '',
            'buttonText'        => sanitize_hex_color( $attributes['buttonText'] ?? '' ) ?: '',
        ];

        $accent = sanitize_hex_color( $attributes['accent'] ?? '' ) ?: '#fbbf24';
        $deep   = sanitize_hex_color( $attributes['accentDeep'] ?? '' ) ?: '#0f766e';
        $btn    = sanitize_hex_color( $attributes['buttonText'] ?? '' ) ?: '#92400e';

        ob_start();
        echo '<div style="--sf-accent:' . esc_attr( $accent ) . ';--sf-accent-deep:' . esc_attr( $deep ) . ';--sf-btn-text:' . esc_attr( $btn ) . ';">';
        rawnaq_smart_form_markup( $cfg, 'gb-' . substr( md5( wp_json_encode( $attributes ) ), 0, 12 ) );
        echo '</div>';
        return ob_get_clean();
    }

    public function render_case_study_grid_block( $attributes, $content = '', $block = null ) {
        wp_enqueue_style( 'rawnaq-case-study-grid' );
        wp_enqueue_script( 'rawnaq-case-study-grid' );
        wp_enqueue_script( 'rawnaq-bridge' );

        $source   = sanitize_key( $attributes['source'] ?? 'manual' );
        $projects = [];

        if ( 'query' !== $source && $block instanceof WP_Block && ! empty( $block->inner_blocks ) ) {
            foreach ( $block->inner_blocks as $inner ) {
                if ( 'rawnaq/case-study-card' !== $inner->name ) {
                    continue;
                }
                $a = $inner->attributes;
                $gallery = json_decode( $a['galleryJson'] ?? '[]', true );
                $projects[] = [
                    'id'       => $a['projectId'] ?? '',
                    'slug'     => $a['projectSlug'] ?? '',
                    'title'    => $a['title'] ?? '',
                    'image'    => $a['image'] ?? '',
                    'gallery'  => is_array( $gallery ) ? $gallery : [],
                    'sector'   => $a['sector'] ?? '',
                    'size'     => $a['size'] ?? '',
                    'budget'   => $a['budget'] ?? '',
                    'year'     => $a['year'] ?? '',
                    'client'   => $a['client'] ?? '',
                    'services' => $a['services'] ?? '',
                    'excerpt'  => $a['excerpt'] ?? '',
                    'detail'   => $a['detail'] ?? '',
                    'link'     => $a['link'] ?? '',
                    'featured' => ! empty( $a['featured'] ),
                    'col'      => $a['col'] ?? 1,
                    'row'      => $a['row'] ?? 1,
                ];
            }
        }

        if ( 'query' !== $source && ! $projects ) {
            $decoded = json_decode( $attributes['projectsJson'] ?? '[]', true );
            if ( is_array( $decoded ) ) {
                $projects = $decoded;
            }
        }

        $cfg = [
            'source'         => $source,
            'projects'       => $projects,
            'queryNumber'    => $attributes['queryNumber'] ?? 12,
            'queryOrderby'   => $attributes['queryOrderby'] ?? 'date',
            'queryOrder'     => $attributes['queryOrder'] ?? 'DESC',
            'querySector'    => $attributes['querySector'] ?? '',
            'layout'         => $attributes['layout'] ?? 'bento',
            'columns'        => $attributes['columns'] ?? 3,
            'showFilter'     => ! isset( $attributes['showFilter'] ) || ! empty( $attributes['showFilter'] ),
            'filterYear'     => ! isset( $attributes['filterYear'] ) || ! empty( $attributes['filterYear'] ),
            'filterService'  => ! isset( $attributes['filterService'] ) || ! empty( $attributes['filterService'] ),
            'sort'           => $attributes['sort'] ?? 'custom',
            'hideBudget'     => ! empty( $attributes['hideBudget'] ),
            'hideClient'     => ! empty( $attributes['hideClient'] ),
            'clickAction'    => $attributes['clickAction'] ?? 'modal',
            'discussTarget'  => $attributes['discussTarget'] ?? 'auto',
            'initialVisible' => isset( $attributes['initialVisible'] ) ? absint( $attributes['initialVisible'] ) : 0,
            'loadChunk'      => absint( $attributes['loadChunk'] ?? 6 ),
            'accent'         => sanitize_hex_color( $attributes['accent'] ?? '' ) ?: '#fbbf24',
            'cardBg'         => sanitize_hex_color( $attributes['cardBg'] ?? '' ) ?: '#ffffff',
            'cardBorder'     => sanitize_hex_color( $attributes['cardBorder'] ?? '' ) ?: '#d7e2dc',
            'radius'         => isset( $attributes['radius'] ) ? absint( $attributes['radius'] ) : 18,
        ];

        ob_start();
        rawnaq_case_study_markup( $cfg, 'gb-' . wp_unique_id() );
        return ob_get_clean();
    }
}

new Rawnaq_Gutenberg_Loader();
