<?php
/**
 * GPP Gutenberg Block Registration
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GPP_Gutenberg {

    public function __construct() {
        add_action( 'init', array( $this, 'register_blocks' ) );
    }

    /**
     * Register Custom Gutenberg Blocks
     */
    public function register_blocks() {
        // Register GPP Property Details Dynamic Block
        register_block_type( 'gpp/property-details', array(
            'api_version'     => 2,
            'title'           => __( 'Ficha Técnica del Inmueble (GPP)', 'gestion-plataforma-pisos' ),
            'category'        => 'widgets',
            'icon'            => 'admin-home',
            'description'     => __( 'Muestra la ficha técnica del inmueble.', 'gestion-plataforma-pisos' ),
            'attributes'      => array(
                'layout'             => array( 'type' => 'string', 'default' => 'grid' ),
                'columns'            => array( 'type' => 'string', 'default' => '3' ),
                'fields'             => array( 'type' => 'string', 'default' => '' ),
                'icon_color'         => array( 'type' => 'string', 'default' => '' ),
                'text_color'         => array( 'type' => 'string', 'default' => '' ),
                'label_color'        => array( 'type' => 'string', 'default' => '' ),
                'card_background'    => array( 'type' => 'string', 'default' => '' ),
                'card_border_radius' => array( 'type' => 'string', 'default' => '' ),
            ),
            'render_callback' => array( $this, 'render_property_details_block' ),
        ) );
    }

    /**
     * Server-side rendering callback for the block
     *
     * @param array $attributes Block attributes
     * @return string Block HTML content
     */
    public function render_property_details_block( $attributes ) {
        // Fallback: resolve post_id in REST API context if needed
        if ( empty( $attributes['post_id'] ) ) {
            $attributes['post_id'] = get_the_ID();
        }
        
        return gpp_get_property_details_html( $attributes );
    }
}
