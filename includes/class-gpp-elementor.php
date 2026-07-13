<?php
/**
 * GPP Elementor Integration Class
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GPP_Elementor {

    public function __construct() {
        // Register Dynamic Tags Group and Tags
        add_action( 'elementor/dynamic_tags/register', array( $this, 'register_dynamic_tags' ) );

        // Register Custom Widgets
        add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
        
        // Enqueue frontend styles for our Elementor widget
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_styles' ) );
    }

    /**
     * Enqueue styles for our dynamic Elementor widgets
     */
    public function enqueue_frontend_styles() {
        wp_register_style( 'gpp-elementor-style', GPP_PLUGIN_URL . 'assets/css/elementor-style.css', array(), GPP_VERSION );
    }

    /**
     * Register Elementor Widgets
     *
     * @param \Elementor\Widgets_Manager $widgets_manager
     */
    public function register_widgets( $widgets_manager ) {
        require_once GPP_PLUGIN_DIR . 'includes/elementor/class-gpp-widget-property-details.php';
        $widgets_manager->register( new GPP_Widget_Property_Details() );
    }

    /**
     * Register Dynamic Tags
     *
     * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager
     */
    public function register_dynamic_tags( $dynamic_tags_manager ) {
        // Register GPP dynamic tags group
        $dynamic_tags_manager->register_group( 'gpp-properties-group', array(
            'title' => __( 'Inmuebles (GPP)', 'gestion-plataforma-pisos' )
        ) );

        // Include Tag classes
        require_once GPP_PLUGIN_DIR . 'includes/elementor/class-gpp-dynamic-tags.php';

        // Register each tag
        $dynamic_tags_manager->register( new GPP_Price_Tag() );
        $dynamic_tags_manager->register( new GPP_Metros_Tag() );
        $dynamic_tags_manager->register( new GPP_Habitaciones_Tag() );
        $dynamic_tags_manager->register( new GPP_Banos_Tag() );
        $dynamic_tags_manager->register( new GPP_Certificacion_Tag() );
        $dynamic_tags_manager->register( new GPP_Direccion_Tag() );
    }
}
