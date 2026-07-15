<?php
/**
 * GPP Universal Shortcodes Module
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GPP_Shortcodes {

    public function __construct() {
        add_shortcode( 'gpp_property_details', array( $this, 'render_property_details' ) );
        add_shortcode( 'gpp_property_field', array( $this, 'render_property_field' ) );
    }

    /**
     * Render the complete property technical specs grid/list
     * Usage: [gpp_property_details layout="grid" columns="3" fields="precio,metros,habitaciones"]
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_property_details( $atts ) {
        $args = shortcode_atts( array(
            'layout'             => 'grid',
            'columns'            => '3',
            'fields'             => '',
            'post_id'            => 0,
            'icon_color'         => '',
            'text_color'         => '',
            'label_color'        => '',
            'card_background'    => '',
            'card_border_radius' => '',
        ), $atts, 'gpp_property_details' );

        // Convert post_id to int
        if ( ! empty( $args['post_id'] ) ) {
            $args['post_id'] = intval( $args['post_id'] );
        } else {
            $args['post_id'] = get_the_ID();
        }

        return gpp_get_property_details_html( $args );
    }

    /**
     * Render a single technical field of a property
     * Usage: [gpp_property_field field="precio" format="yes" label="yes"]
     *
     * @param array $atts Shortcode attributes
     * @return string Text or HTML output
     */
    public function render_property_field( $atts ) {
        $args = shortcode_atts( array(
            'field'   => '',
            'name'    => '', // Alias of 'field'
            'format'  => 'yes',
            'label'   => 'no',
            'post_id' => 0,
        ), $atts, 'gpp_property_field' );

        $field = ! empty( $args['field'] ) ? $args['field'] : $args['name'];
        if ( empty( $field ) ) {
            return '';
        }

        // Map alias field names
        $slug_map = array(
            'price'         => 'precio',
            'precio'        => 'precio',
            'area'          => 'metros',
            'metros'        => 'metros',
            'superficie'    => 'metros',
            'rooms'         => 'habitaciones',
            'habitaciones'  => 'habitaciones',
            'baths'         => 'banos',
            'banos'         => 'banos',
            'energy'        => 'certificacion',
            'certificacion' => 'certificacion',
            'address'       => 'direccion',
            'direccion'     => 'direccion',
            'latitude'      => 'latitud',
            'latitud'       => 'latitud',
            'longitude'     => 'longitud',
            'longitud'      => 'longitud',
        );

        $field_slug = isset( $slug_map[ $field ] ) ? $slug_map[ $field ] : $field;
        $post_id = intval( $args['post_id'] );
        if ( ! $post_id ) {
            $post_id = get_the_ID();
        }

        if ( ! $post_id ) {
            return '';
        }

        $raw_value = gpp_get_property_field( $field_slug, $post_id );

        if ( $raw_value === '' || $raw_value === false || $raw_value === null ) {
            return '';
        }

        // Format value if requested
        $formatted_value = $raw_value;
        $is_formatted = filter_var( $args['format'], FILTER_VALIDATE_BOOLEAN ) || strtolower( $args['format'] ) === 'yes';

        if ( $is_formatted ) {
            switch ( $field_slug ) {
                case 'precio':
                    $formatted_value = number_format( intval( $raw_value ), 0, ',', '.' ) . ' €';
                    break;
                case 'metros':
                    $formatted_value = esc_html( $raw_value ) . ' m²';
                    break;
                case 'habitaciones':
                    $formatted_value = esc_html( $raw_value ) . ' ' . _n( 'habitación', 'habitaciones', intval( $raw_value ), 'gestion-plataforma-pisos' );
                    break;
                case 'banos':
                    $formatted_value = esc_html( $raw_value ) . ' ' . _n( 'baño', 'baños', intval( $raw_value ), 'gestion-plataforma-pisos' );
                    break;
                case 'certificacion':
                    $formatted_value = __( 'Certificado clase', 'gestion-plataforma-pisos' ) . ' ' . esc_html( $raw_value );
                    break;
            }
        }

        // Build output with labels if requested
        $output = '';
        $show_label = filter_var( $args['label'], FILTER_VALIDATE_BOOLEAN ) || strtolower( $args['label'] ) === 'yes';

        if ( $show_label ) {
            $labels = array(
                'precio'        => __( 'Precio', 'gestion-plataforma-pisos' ),
                'metros'        => __( 'Superficie', 'gestion-plataforma-pisos' ),
                'habitaciones'  => __( 'Habitaciones', 'gestion-plataforma-pisos' ),
                'banos'         => __( 'Baños', 'gestion-plataforma-pisos' ),
                'certificacion' => __( 'Certificación Energética', 'gestion-plataforma-pisos' ),
                'direccion'     => __( 'Ubicación', 'gestion-plataforma-pisos' ),
                'latitud'       => __( 'Latitud', 'gestion-plataforma-pisos' ),
                'longitud'      => __( 'Longitud', 'gestion-plataforma-pisos' ),
            );
            $label_text = isset( $labels[ $field_slug ] ) ? $labels[ $field_slug ] : ucfirst( $field_slug );
            $output .= '<span class="gpp-field-label"><strong>' . esc_html( $label_text ) . ':</strong> </span>';
        }

        $output .= '<span class="gpp-field-value gpp-field-' . esc_attr( $field_slug ) . '">' . esc_html( $formatted_value ) . '</span>';

        return $output;
    }
}
