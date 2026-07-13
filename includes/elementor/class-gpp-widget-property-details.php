<?php
/**
 * GPP Elementor Property Details Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GPP_Widget_Property_Details extends \Elementor\Widget_Base {

    public function get_name() {
        return 'gpp_property_details';
    }

    public function get_title() {
        return __( 'Ficha Técnica del Inmueble (GPP)', 'gestion-plataforma-pisos' );
    }

    public function get_icon() {
        return 'eicon-post-info';
    }

    public function get_categories() {
        return array( 'general' );
    }

    public function get_style_depends() {
        return array( 'gpp-elementor-style' );
    }

    /**
     * Register control panels
     */
    protected function register_controls() {
        // Layout Controls Section
        $this->start_controls_section(
            'section_layout',
            array(
                'label' => __( 'Disposición (Layout)', 'gestion-plataforma-pisos' ),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'layout_style',
            array(
                'label'   => __( 'Estilo de Diseño', 'gestion-plataforma-pisos' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => array(
                    'grid' => __( 'Cuadrícula (Grid)', 'gestion-plataforma-pisos' ),
                    'list' => __( 'Lista (List)', 'gestion-plataforma-pisos' ),
                ),
            )
        );

        $this->add_control(
            'columns',
            array(
                'label'     => __( 'Columnas (solo Grid)', 'gestion-plataforma-pisos' ),
                'type'      => \Elementor\Controls_Manager::SELECT,
                'default'   => '3',
                'options'   => array(
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ),
                'condition' => array(
                    'layout_style' => 'grid',
                ),
            )
        );

        $this->end_controls_section();

        // Style Controls Section
        $this->start_controls_section(
            'section_style',
            array(
                'label' => __( 'Estilos y Colores', 'gestion-plataforma-pisos' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'icon_color',
            array(
                'label'     => __( 'Color de Iconos', 'gestion-plataforma-pisos' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#3b82f6',
                'selectors' => array(
                    '{{WRAPPER}} .gpp-detail-icon svg' => 'fill: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'text_color',
            array(
                'label'     => __( 'Color del Texto', 'gestion-plataforma-pisos' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#1e293b',
                'selectors' => array(
                    '{{WRAPPER}} .gpp-detail-value' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'label_color',
            array(
                'label'     => __( 'Color de Etiquetas', 'gestion-plataforma-pisos' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#64748b',
                'selectors' => array(
                    '{{WRAPPER}} .gpp-detail-label' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'card_background',
            array(
                'label'     => __( 'Fondo del Item', 'gestion-plataforma-pisos' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#f8fafc',
                'selectors' => array(
                    '{{WRAPPER}} .gpp-widget-item' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'card_border_radius',
            array(
                'label'      => __( 'Radio de Borde', 'gestion-plataforma-pisos' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%', 'em' ),
                'default'    => array(
                    'top'      => '8',
                    'right'    => '8',
                    'bottom'   => '8',
                    'left'     => '8',
                    'unit'     => 'px',
                    'isLinked' => true,
                ),
                'selectors' => array(
                    '{{WRAPPER}} .gpp-widget-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();
    }

    /**
     * Render widget content on frontend
     */
    protected function render() {
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return;
        }

        // Fetch values
        $precio   = get_post_meta( $post_id, '_gpp_precio', true );
        $metros   = get_post_meta( $post_id, '_gpp_metros_cuadrados', true );
        $habs     = get_post_meta( $post_id, '_gpp_habitaciones', true );
        $banos    = get_post_meta( $post_id, '_gpp_banos', true );
        $cert     = get_post_meta( $post_id, '_gpp_certificacion_energetica', true );
        $dir      = get_post_meta( $post_id, '_gpp_direccion', true );

        $settings = $this->get_settings_for_display();
        
        $layout_class = 'gpp-layout-' . esc_attr( $settings['layout_style'] );
        $cols_class   = ( 'grid' === $settings['layout_style'] ) ? 'gpp-cols-' . esc_attr( $settings['columns'] ) : '';

        // Formatted Values
        $precio_formatted = ! empty( $precio ) ? number_format( intval( $precio ), 0, ',', '.' ) . ' €' : __( 'Consultar', 'gestion-plataforma-pisos' );
        
        // Define clean inline SVG icons
        $svg_price = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H7c0-2.76 2.24-5 5-5s5 2.24 5 5c0 1.04-.42 1.99-1.07 2.25z"/></svg>';
        $svg_area  = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>';
        $svg_rooms = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M7 13c1.66 0 3-1.34 3-3S8.66 7 7 7s-3 1.34-3 3 1.34 3 3 3zm12-6h-8v7H3V5H1v15h2v-3h18v3h2v-9c0-2.21-1.79-4-4-4z"/></svg>';
        $svg_baths = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M7 7c0-1.1.9-2 2-2s2 .9 2 2-.9 2-2 2-2-.9-2-2zm10 5c-.7 0-1.37-.1-2-.29V17H9V9.71c-.63.19-1.3.29-2 .29H3v9c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-9h-4z"/></svg>';
        $svg_energy = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>';
        $svg_map   = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>';

        // Grid cards list
        $items = array();

        if ( ! empty( $precio ) ) {
            $items[] = array( 'label' => __( 'Precio', 'gestion-plataforma-pisos' ), 'value' => $precio_formatted, 'icon' => $svg_price );
        }
        if ( ! empty( $metros ) ) {
            $items[] = array( 'label' => __( 'Superficie', 'gestion-plataforma-pisos' ), 'value' => $metros . ' m²', 'icon' => $svg_area );
        }
        if ( ! empty( $habs ) ) {
            $items[] = array( 'label' => __( 'Habitaciones', 'gestion-plataforma-pisos' ), 'value' => $habs, 'icon' => $svg_rooms );
        }
        if ( ! empty( $banos ) ) {
            $items[] = array( 'label' => __( 'Baños', 'gestion-plataforma-pisos' ), 'value' => $banos, 'icon' => $svg_baths );
        }
        if ( ! empty( $cert ) ) {
            $items[] = array( 'label' => __( 'Certif. Energética', 'gestion-plataforma-pisos' ), 'value' => __( 'Clase', 'gestion-plataforma-pisos' ) . ' ' . $cert, 'icon' => $svg_energy );
        }
        if ( ! empty( $dir ) ) {
            $items[] = array( 'label' => __( 'Ubicación', 'gestion-plataforma-pisos' ), 'value' => $dir, 'icon' => $svg_map );
        }

        if ( empty( $items ) ) {
            echo '<p>' . __( 'No hay datos técnicos disponibles para este inmueble.', 'gestion-plataforma-pisos' ) . '</p>';
            return;
        }

        ?>
        <div class="gpp-elementor-widget <?php echo $layout_class; ?> <?php echo $cols_class; ?>">
            <?php foreach ( $items as $item ) : ?>
                <div class="gpp-widget-item">
                    <div class="gpp-detail-icon">
                        <?php echo $item['icon']; ?>
                    </div>
                    <div class="gpp-detail-meta">
                        <span class="gpp-detail-label"><?php echo esc_html( $item['label'] ); ?></span>
                        <span class="gpp-detail-value"><?php echo esc_html( $item['value'] ); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}
