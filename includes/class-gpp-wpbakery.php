<?php
/**
 * GPP WPBakery Page Builder Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GPP_WPBakery {

    public function __construct() {
        add_action( 'vc_before_init', array( $this, 'register_vc_element' ) );
    }

    /**
     * Map the shortcode to WPBakery editor
     */
    public function register_vc_element() {
        if ( ! function_exists( 'vc_map' ) ) {
            return;
        }

        vc_map( array(
            'name'        => __( 'Ficha Técnica del Inmueble (GPP)', 'gestion-plataforma-pisos' ),
            'base'        => 'gpp_property_details',
            'category'    => __( 'Inmuebles (GPP)', 'gestion-plataforma-pisos' ),
            'description' => __( 'Muestra la ficha técnica del inmueble con cuadrícula o lista.', 'gestion-plataforma-pisos' ),
            'icon'        => 'icon-wpb-application-icon-large',
            'params'      => array(
                array(
                    'type'        => 'dropdown',
                    'heading'     => __( 'Estilo de Diseño', 'gestion-plataforma-pisos' ),
                    'param_name'  => 'layout',
                    'value'       => array(
                        __( 'Cuadrícula (Grid)', 'gestion-plataforma-pisos' ) => 'grid',
                        __( 'Lista (List)', 'gestion-plataforma-pisos' )       => 'list',
                    ),
                    'description' => __( 'Selecciona la disposición de los detalles del inmueble.', 'gestion-plataforma-pisos' ),
                ),
                array(
                    'type'        => 'dropdown',
                    'heading'     => __( 'Columnas (solo Grid)', 'gestion-plataforma-pisos' ),
                    'param_name'  => 'columns',
                    'value'       => array(
                        '3' => '3',
                        '2' => '2',
                        '4' => '4',
                    ),
                    'dependency'  => array(
                        'element' => 'layout',
                        'value'   => array( 'grid' ),
                    ),
                    'description' => __( 'Selecciona el número de columnas a mostrar.', 'gestion-plataforma-pisos' ),
                ),
                array(
                    'type'        => 'textfield',
                    'heading'     => __( 'Filtrar Campos', 'gestion-plataforma-pisos' ),
                    'param_name'  => 'fields',
                    'value'       => '',
                    'description' => __( 'Introduce los slugs de los campos separados por comas para filtrar (ej. precio,metros,habitaciones). Dejar vacío para mostrar todos.', 'gestion-plataforma-pisos' ),
                ),
                array(
                    'type'        => 'colorpicker',
                    'heading'     => __( 'Color de Iconos', 'gestion-plataforma-pisos' ),
                    'param_name'  => 'icon_color',
                    'value'       => '',
                    'group'       => __( 'Estilos y Colores', 'gestion-plataforma-pisos' ),
                ),
                array(
                    'type'        => 'colorpicker',
                    'heading'     => __( 'Color de Etiquetas', 'gestion-plataforma-pisos' ),
                    'param_name'  => 'label_color',
                    'value'       => '',
                    'group'       => __( 'Estilos y Colores', 'gestion-plataforma-pisos' ),
                ),
                array(
                    'type'        => 'colorpicker',
                    'heading'     => __( 'Color de Texto (Valores)', 'gestion-plataforma-pisos' ),
                    'param_name'  => 'text_color',
                    'value'       => '',
                    'group'       => __( 'Estilos y Colores', 'gestion-plataforma-pisos' ),
                ),
                array(
                    'type'        => 'colorpicker',
                    'heading'     => __( 'Color de Fondo de las Fichas', 'gestion-plataforma-pisos' ),
                    'param_name'  => 'card_background',
                    'value'       => '',
                    'group'       => __( 'Estilos y Colores', 'gestion-plataforma-pisos' ),
                ),
                array(
                    'type'        => 'textfield',
                    'heading'     => __( 'Radio de Borde (px)', 'gestion-plataforma-pisos' ),
                    'param_name'  => 'card_border_radius',
                    'value'       => '',
                    'description' => __( 'Ej. 8', 'gestion-plataforma-pisos' ),
                    'group'       => __( 'Estilos y Colores', 'gestion-plataforma-pisos' ),
                ),
            ),
        ) );
    }
}
