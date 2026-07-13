<?php
/**
 * GPP Elementor Dynamic Tags classes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// -------------------------------------------------------------
// 1. PRICE TAG
// -------------------------------------------------------------
class GPP_Price_Tag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'gpp-price-tag';
    }

    public function get_title() {
        return __( 'Inmueble: Precio', 'gestion-plataforma-pisos' );
    }

    public function get_group() {
        return 'gpp-properties-group';
    }

    public function get_categories() {
        return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY, \Elementor\Modules\DynamicTags\Module::NUMBER_CATEGORY );
    }

    public function render() {
        $price = get_post_meta( get_the_ID(), '_gpp_precio', true );
        if ( ! empty( $price ) ) {
            echo number_format( intval( $price ), 0, ',', '.' ) . ' €';
        } else {
            echo __( 'Consultar precio', 'gestion-plataforma-pisos' );
        }
    }
}

// -------------------------------------------------------------
// 2. SQUARE METERS TAG
// -------------------------------------------------------------
class GPP_Metros_Tag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'gpp-metros-tag';
    }

    public function get_title() {
        return __( 'Inmueble: Superficie (m²)', 'gestion-plataforma-pisos' );
    }

    public function get_group() {
        return 'gpp-properties-group';
    }

    public function get_categories() {
        return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
    }

    public function render() {
        $metros = get_post_meta( get_the_ID(), '_gpp_metros_cuadrados', true );
        if ( ! empty( $metros ) ) {
            echo esc_html( $metros ) . ' m²';
        }
    }
}

// -------------------------------------------------------------
// 3. ROOMS TAG
// -------------------------------------------------------------
class GPP_Habitaciones_Tag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'gpp-habitaciones-tag';
    }

    public function get_title() {
        return __( 'Inmueble: Habitaciones', 'gestion-plataforma-pisos' );
    }

    public function get_group() {
        return 'gpp-properties-group';
    }

    public function get_categories() {
        return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
    }

    public function render() {
        $rooms = get_post_meta( get_the_ID(), '_gpp_habitaciones', true );
        if ( ! empty( $rooms ) ) {
            echo esc_html( $rooms ) . ' ' . _n( 'habitación', 'habitaciones', intval( $rooms ), 'gestion-plataforma-pisos' );
        }
    }
}

// -------------------------------------------------------------
// 4. BATHROOMS TAG
// -------------------------------------------------------------
class GPP_Banos_Tag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'gpp-banos-tag';
    }

    public function get_title() {
        return __( 'Inmueble: Baños', 'gestion-plataforma-pisos' );
    }

    public function get_group() {
        return 'gpp-properties-group';
    }

    public function get_categories() {
        return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
    }

    public function render() {
        $banos = get_post_meta( get_the_ID(), '_gpp_banos', true );
        if ( ! empty( $banos ) ) {
            echo esc_html( $banos ) . ' ' . _n( 'baño', 'baños', intval( $banos ), 'gestion-plataforma-pisos' );
        }
    }
}

// -------------------------------------------------------------
// 5. ENERGY CERTIFICATION TAG
// -------------------------------------------------------------
class GPP_Certificacion_Tag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'gpp-certificacion-tag';
    }

    public function get_title() {
        return __( 'Inmueble: Certificación Energética', 'gestion-plataforma-pisos' );
    }

    public function get_group() {
        return 'gpp-properties-group';
    }

    public function get_categories() {
        return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
    }

    public function render() {
        $cert = get_post_meta( get_the_ID(), '_gpp_certificacion_energetica', true );
        if ( ! empty( $cert ) ) {
            echo __( 'Certificado', 'gestion-plataforma-pisos' ) . ' ' . esc_html( $cert );
        }
    }
}

// -------------------------------------------------------------
// 6. ADDRESS TAG
// -------------------------------------------------------------
class GPP_Direccion_Tag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'gpp-direccion-tag';
    }

    public function get_title() {
        return __( 'Inmueble: Dirección', 'gestion-plataforma-pisos' );
    }

    public function get_group() {
        return 'gpp-properties-group';
    }

    public function get_categories() {
        return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
    }

    public function render() {
        $direccion = get_post_meta( get_the_ID(), '_gpp_direccion', true );
        if ( ! empty( $direccion ) ) {
            echo esc_html( $direccion );
        }
    }
}
