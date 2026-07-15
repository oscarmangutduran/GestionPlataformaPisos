<?php
/**
 * GPP Helper Functions and Public API
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Retrieve a specific technical field value for a property CPT
 *
 * @param string $field_slug   Field slug (precio, metros, habitaciones, banos, certificacion, direccion, latitud, longitud)
 * @param int    $post_id      Post ID (defaults to current post)
 * @return mixed
 */
function gpp_get_property_field( $field_slug, $post_id = 0 ) {
    if ( ! $post_id ) {
        $post_id = get_the_ID();
    }
    if ( ! $post_id ) {
        return '';
    }

    $settings = get_option( 'gpp_settings' );
    $mappings = isset( $settings['field_mapping'] ) ? $settings['field_mapping'] : array();
    $meta_key = isset( $mappings[ $field_slug ] ) ? $mappings[ $field_slug ] : '';

    // Smart defaults if mappings are empty
    if ( empty( $meta_key ) ) {
        $defaults = array(
            'precio'        => '_gpp_precio',
            'metros'        => '_gpp_metros_cuadrados',
            'habitaciones'  => '_gpp_habitaciones',
            'banos'         => '_gpp_banos',
            'certificacion' => '_gpp_certificacion_energetica',
            'direccion'     => '_gpp_direccion',
            'latitud'       => '_gpp_latitud',
            'longitud'      => '_gpp_longitud',
        );
        $meta_key = isset( $defaults[ $field_slug ] ) ? $defaults[ $field_slug ] : '';
    }

    if ( empty( $meta_key ) ) {
        return '';
    }

    $value = get_post_meta( $post_id, $meta_key, true );

    // Fallback: if the key is default without underscore but saved with it
    if ( empty( $value ) && in_array( $meta_key, array( 'gpp_precio', 'gpp_metros_cuadrados', 'gpp_habitaciones', 'gpp_banos', 'gpp_certificacion_energetica', 'gpp_direccion', 'gpp_latitud', 'gpp_longitud' ) ) ) {
        $fallback_key = '_' . $meta_key;
        $value = get_post_meta( $post_id, $fallback_key, true );
    }

    return $value;
}

/**
 * Retrieve formatted data array for property details
 *
 * @param int $post_id
 * @return array
 */
function gpp_get_property_details_data( $post_id = 0 ) {
    if ( ! $post_id ) {
        $post_id = get_the_ID();
    }
    if ( ! $post_id ) {
        return array();
    }

    $precio   = gpp_get_property_field( 'precio', $post_id );
    $metros   = gpp_get_property_field( 'metros', $post_id );
    $habs     = gpp_get_property_field( 'habitaciones', $post_id );
    $banos    = gpp_get_property_field( 'banos', $post_id );
    $cert     = gpp_get_property_field( 'certificacion', $post_id );
    $dir      = gpp_get_property_field( 'direccion', $post_id );

    // Inline SVG Icons
    $svg_price  = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H7c0-2.76 2.24-5 5-5s5 2.24 5 5c0 1.04-.42 1.99-1.07 2.25z"/></svg>';
    $svg_area   = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>';
    $svg_rooms  = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M7 13c1.66 0 3-1.34 3-3S8.66 7 7 7s-3 1.34-3 3 1.34 3 3 3zm12-6h-8v7H3V5H1v15h2v-3h18v3h2v-9c0-2.21-1.79-4-4-4z"/></svg>';
    $svg_baths  = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M7 7c0-1.1.9-2 2-2s2 .9 2 2-.9 2-2 2-2-.9-2-2zm10 5c-.7 0-1.37-.1-2-.29V17H9V9.71c-.63.19-1.3.29-2 .29H3v9c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-9h-4z"/></svg>';
    $svg_energy = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>';
    $svg_map    = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>';

    $items = array();

    if ( ! empty( $precio ) ) {
        $items['precio'] = array(
            'label'     => __( 'Precio', 'gestion-plataforma-pisos' ),
            'value'     => number_format( intval( $precio ), 0, ',', '.' ) . ' €',
            'raw'       => $precio,
            'icon'      => $svg_price,
            'slug'      => 'precio'
        );
    }
    if ( ! empty( $metros ) ) {
        $items['metros'] = array(
            'label'     => __( 'Superficie', 'gestion-plataforma-pisos' ),
            'value'     => $metros . ' m²',
            'raw'       => $metros,
            'icon'      => $svg_area,
            'slug'      => 'metros'
        );
    }
    if ( ! empty( $habs ) ) {
        $items['habitaciones'] = array(
            'label'     => __( 'Habitaciones', 'gestion-plataforma-pisos' ),
            'value'     => $habs,
            'raw'       => $habs,
            'icon'      => $svg_rooms,
            'slug'      => 'habitaciones'
        );
    }
    if ( ! empty( $banos ) ) {
        $items['banos'] = array(
            'label'     => __( 'Baños', 'gestion-plataforma-pisos' ),
            'value'     => $banos,
            'raw'       => $banos,
            'icon'      => $svg_baths,
            'slug'      => 'banos'
        );
    }
    if ( ! empty( $cert ) ) {
        $items['certificacion'] = array(
            'label'     => __( 'Certif. Energética', 'gestion-plataforma-pisos' ),
            'value'     => __( 'Clase', 'gestion-plataforma-pisos' ) . ' ' . $cert,
            'raw'       => $cert,
            'icon'      => $svg_energy,
            'slug'      => 'certificacion'
        );
    }
    if ( ! empty( $dir ) ) {
        $items['direccion'] = array(
            'label'     => __( 'Ubicación', 'gestion-plataforma-pisos' ),
            'value'     => $dir,
            'raw'       => $dir,
            'icon'      => $svg_map,
            'slug'      => 'direccion'
        );
    }

    return $items;
}

/**
 * Generate the HTML markup for the property technical details
 *
 * @param array $args Custom configuration parameters
 * @return string HTML output
 */
function gpp_get_property_details_html( $args = array() ) {
    $defaults = array(
        'layout'             => 'grid',
        'columns'            => '3',
        'fields'             => '',
        'post_id'            => 0,
        'icon_color'         => '',
        'text_color'         => '',
        'label_color'        => '',
        'card_background'    => '',
        'card_border_radius' => '',
        'extra_class'        => '',
    );

    $args = wp_parse_args( $args, $defaults );
    $post_id = intval( $args['post_id'] );
    if ( ! $post_id ) {
        $post_id = get_the_ID();
    }

    if ( ! $post_id ) {
        return '';
    }

    $all_items = gpp_get_property_details_data( $post_id );

    if ( empty( $all_items ) ) {
        return '<p class="gpp-no-data">' . esc_html__( 'No hay datos técnicos disponibles para este inmueble.', 'gestion-plataforma-pisos' ) . '</p>';
    }

    // Filter fields if requested
    $items = array();
    if ( ! empty( $args['fields'] ) ) {
        $allowed_fields = array_map( 'trim', explode( ',', $args['fields'] ) );
        foreach ( $allowed_fields as $field_slug ) {
            // Check for localized/Spanish slugs or exact match
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
                'direccion'     => 'direccion'
            );
            
            $target_slug = isset( $slug_map[ $field_slug ] ) ? $slug_map[ $field_slug ] : $field_slug;
            
            if ( isset( $all_items[ $target_slug ] ) ) {
                $items[ $target_slug ] = $all_items[ $target_slug ];
            }
        }
    } else {
        $items = $all_items;
    }

    if ( empty( $items ) ) {
        return '';
    }

    // Build classes
    $layout_class = 'gpp-layout-' . esc_attr( $args['layout'] );
    $cols_class   = ( 'grid' === $args['layout'] ) ? 'gpp-cols-' . esc_attr( $args['columns'] ) : '';

    // Enqueue frontend styles if not already enqueued
    if ( ! wp_style_is( 'gpp-frontend-style', 'enqueued' ) && ! wp_style_is( 'gpp-frontend-style', 'registered' ) ) {
        wp_enqueue_style( 'gpp-frontend-style', GPP_PLUGIN_URL . 'assets/css/frontend-style.css', array(), GPP_VERSION );
    } else {
        wp_enqueue_style( 'gpp-frontend-style' );
    }

    // Dynamic styles
    $container_style = '';
    $item_style      = '';
    $icon_style      = '';
    $label_style     = '';
    $value_style     = '';

    if ( ! empty( $args['card_background'] ) ) {
        $item_style .= 'background-color: ' . esc_attr( $args['card_background'] ) . ';';
    }
    if ( ! empty( $args['card_border_radius'] ) ) {
        // Append px if numeric
        $radius = is_numeric( $args['card_border_radius'] ) ? $args['card_border_radius'] . 'px' : $args['card_border_radius'];
        $item_style .= 'border-radius: ' . esc_attr( $radius ) . ';';
    }
    if ( ! empty( $args['icon_color'] ) ) {
        $icon_style .= 'fill: ' . esc_attr( $args['icon_color'] ) . ';';
    }
    if ( ! empty( $args['label_color'] ) ) {
        $label_style .= 'color: ' . esc_attr( $args['label_color'] ) . ';';
    }
    if ( ! empty( $args['text_color'] ) ) {
        $value_style .= 'color: ' . esc_attr( $args['text_color'] ) . ';';
    }

    // Render container
    ob_start();
    ?>
    <div class="gpp-property-details-container <?php echo $layout_class; ?> <?php echo $cols_class; ?> <?php echo esc_attr( $args['extra_class'] ); ?>" style="<?php echo esc_attr( $container_style ); ?>">
        <?php foreach ( $items as $item ) : ?>
            <div class="gpp-widget-item" style="<?php echo esc_attr( $item_style ); ?>">
                <div class="gpp-detail-icon" style="<?php echo esc_attr( $icon_style ); ?>">
                    <?php 
                    // Set SVG fill style if icon_color is defined
                    if ( ! empty( $icon_style ) ) {
                        echo str_replace( '<svg ', '<svg style="' . esc_attr( $icon_style ) . '" ', $item['icon'] );
                    } else {
                        echo $item['icon']; 
                    }
                    ?>
                </div>
                <div class="gpp-detail-meta">
                    <span class="gpp-detail-label" style="<?php echo esc_attr( $label_style ); ?>"><?php echo esc_html( $item['label'] ); ?></span>
                    <span class="gpp-detail-value" style="<?php echo esc_attr( $value_style ); ?>"><?php echo esc_html( $item['value'] ); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
