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

        $settings = $this->get_settings_for_display();

        echo gpp_get_property_details_html( array(
            'layout'      => $settings['layout_style'],
            'columns'     => $settings['columns'],
            'post_id'     => $post_id,
            'extra_class' => 'gpp-elementor-widget',
        ) );
    }
}
