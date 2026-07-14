<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rawnaq_Tilt_Card_Widget extends \Elementor\Widget_Base {

    public function get_name()       { return 'rawnaq_tilt_card'; }
    public function get_title()      { return esc_html__( '3D Tilt Card', 'rawnaq' ); }
    public function get_icon()       { return 'eicon-parallax'; }
    public function get_categories() { return [ 'rawnaq' ]; }

    public function get_style_depends()  { return [ 'rawnaq-tilt-card', 'rawnaq-fonts', 'dashicons' ]; }
    public function get_script_depends() { return [ 'rawnaq-tilt-card' ]; }

    protected function register_controls() {
        // Content Tab - Text
        $this->start_controls_section( 's_content', [
            'label' => esc_html__( 'Card Content', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );
        $this->add_control( 'title', [
            'label'       => esc_html__( 'Title', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'Creative Service',
            'label_block' => true,
        ] );
        $this->add_control( 'desc', [
            'label'   => esc_html__( 'Description', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::TEXTAREA,
            'default' => 'We design premium, high-speed interfaces tailored to stand out from competitors.',
            'rows'    => 3,
        ] );
        $this->add_control( 'icon', [
            'label'       => esc_html__( 'Dashicon Icon', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'dashicons-admin-generic',
            'placeholder' => 'dashicons-lightbulb',
        ] );
        $this->add_control( 'link', [
            'label'       => esc_html__( 'Redirect URL', 'rawnaq' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'https://example.com',
        ] );
        $this->add_control( 'target', [
            'label'   => esc_html__( 'Link Target', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => '_self',
            'options' => [
                '_self'  => esc_html__( 'Same Tab', 'rawnaq' ),
                '_blank' => esc_html__( 'New Tab', 'rawnaq' ),
            ],
        ] );
        $this->end_controls_section();

        // Style Tab - Tilt Mechanics & Colors
        $this->start_controls_section( 's_style', [
            'label' => esc_html__( 'Style &amp; Tilt Settings', 'rawnaq' ),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ] );
        $this->add_control( 'max_tilt', [
            'label'   => esc_html__( 'Max Tilt Intensity', 'rawnaq' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'min'     => 5,
            'max'     => 45,
            'step'    => 1,
            'default' => 15,
        ] );
        $this->add_control( 'card_bg', [
            'label'     => esc_html__( 'Card Background Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [ '{{WRAPPER}} .rawnaq-tilt-card' => 'background-color: {{VALUE}};' ],
        ] );
        $this->add_control( 'title_color', [
            'label'     => esc_html__( 'Title Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#1a1a1a',
            'selectors' => [ '{{WRAPPER}} .rawnaq-tilt-title' => 'color: {{VALUE}};' ],
        ] );
        $this->add_control( 'desc_color', [
            'label'     => esc_html__( 'Description Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#666666',
            'selectors' => [ '{{WRAPPER}} .rawnaq-tilt-desc' => 'color: {{VALUE}};' ],
        ] );
        $this->add_control( 'icon_color', [
            'label'     => esc_html__( 'Icon Color', 'rawnaq' ),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#6366f1',
            'selectors' => [ '{{WRAPPER}} .rawnaq-tilt-icon' => 'color: {{VALUE}};' ],
        ] );
        $this->end_controls_section();
    }

    protected function render() {
        $s = $this->get_settings_for_display();
        
        $link_url = $s['link'] ?? '';
        $target   = $s['target'] ?? '_self';
        $max_tilt = $s['max_tilt'] ?? 15;

        // Render card wrapper dynamically as tag <a> or <div>
        $tag = $link_url ? 'a' : 'div';
        $href_attr = $link_url ? ' href="' . esc_url( $link_url ) . '"' : '';
        $target_attr = $link_url ? ' target="' . esc_attr( $target ) . '"' : '';
        ?>
        <div class="rawnaq-tilt-container">
            <<?php echo $tag; ?> class="rawnaq-tilt-card" 
                data-tilt-max="<?php echo esc_attr( $max_tilt ); ?>"
                <?php echo $href_attr; ?>
                <?php echo $target_attr; ?>>
                
                <span class="rawnaq-tilt-glow"></span>
                
                <?php if ( ! empty( $s['icon'] ) ) : ?>
                    <span class="rawnaq-tilt-icon dashicons <?php echo esc_attr( $s['icon'] ); ?>"></span>
                <?php endif; ?>

                <div class="rawnaq-tilt-content">
                    <h3 class="rawnaq-tilt-title"><?php echo esc_html( $s['title'] ); ?></h3>
                    <p class="rawnaq-tilt-desc"><?php echo esc_html( $s['desc'] ); ?></p>
                </div>

            </<?php echo $tag; ?>>
        </div>
        <?php
    }

    protected function content_template() {
        ?>
        <#
        var max_tilt = settings.max_tilt || 15;
        var tag = settings.link ? 'a' : 'div';
        var href = settings.link ? ' href="' + settings.link + '"' : '';
        var target = settings.link ? ' target="' + settings.target + '"' : '';
        #>
        <div class="rawnaq-tilt-container">
            <{{tag}} class="rawnaq-tilt-card" data-tilt-max="{{max_tilt}}" {{href}} {{target}}>
                <span class="rawnaq-tilt-glow"></span>
                
                <# if ( settings.icon ) { #>
                    <span class="rawnaq-tilt-icon dashicons {{ settings.icon }}"></span>
                <# } #>

                <div class="rawnaq-tilt-content">
                    <h3 class="rawnaq-tilt-title">{{{ settings.title }}}</h3>
                    <p class="rawnaq-tilt-desc">{{{ settings.desc }}}</p>
                </div>
            </{{tag}}>
        </div>
        <?php
    }
}
