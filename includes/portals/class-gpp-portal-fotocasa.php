<?php
/**
 * GPP Fotocasa Portal Class
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GPP_Portal_Fotocasa extends GPP_Portal_Base {

    protected function setup_portal_config() {
        $this->portal_id   = 'fotocasa';
        $this->portal_name = 'Fotocasa';
        $this->enabled     = isset( $this->settings['fotocasa_enabled'] ) && '1' === $this->settings['fotocasa_enabled'];
    }

    /**
     * Send property to Fotocasa (XML Payload)
     */
    public function send_property( $property_id, $mapped_data, $images ) {
        // Build XML Payload
        $xml = new SimpleXMLElement( '<?xml version="1.0" encoding="utf-8"?><realestate_listing></realestate_listing>' );
        
        $xml->addChild( 'transaction_type', 'sale' );
        $xml->addChild( 'property_type', 'apartment' );
        $xml->addChild( 'price', isset( $mapped_data['precio'] ) ? intval( $mapped_data['precio'] ) : 0 );
        $xml->addChild( 'surface_area', isset( $mapped_data['metros'] ) ? intval( $mapped_data['metros'] ) : 0 );
        $xml->addChild( 'rooms', isset( $mapped_data['habitaciones'] ) ? intval( $mapped_data['habitaciones'] ) : 0 );
        $xml->addChild( 'bathrooms', isset( $mapped_data['banos'] ) ? intval( $mapped_data['banos'] ) : 0 );
        $xml->addChild( 'energy_rating', isset( $mapped_data['certificacion'] ) ? sanitize_text_field( $mapped_data['certificacion'] ) : 'G' );
        
        $location = $xml->addChild( 'location' );
        $location->addChild( 'full_address', isset( $mapped_data['direccion'] ) ? sanitize_text_field( $mapped_data['direccion'] ) : '' );
        $location->addChild( 'latitude', isset( $mapped_data['latitud'] ) ? doubleval( $mapped_data['latitud'] ) : 0.0 );
        $location->addChild( 'longitude', isset( $mapped_data['longitud'] ) ? doubleval( $mapped_data['longitud'] ) : 0.0 );

        $gallery = $xml->addChild( 'multimedia_gallery' );

        // Process images with Optimizer constraints (Fotocasa constraints: max 2MB, max 1920x1080)
        $image_handler = $this->get_image_handler();
        foreach ( $images as $img_id ) {
            $optimized_url = $image_handler->get_optimized_image_url( $img_id, 1920, 1080, 2097152 );
            if ( $optimized_url ) {
                $image_node = $gallery->addChild( 'media_file' );
                $image_node->addAttribute( 'type', 'image' );
                $image_node->addChild( 'url', esc_url( $optimized_url ) );
            }
        }

        // Convert XML to String for payload logging
        $payload_xml = $xml->asXML();

        // Determine if it is a new listing or update
        $remote_id = get_post_meta( $property_id, '_gpp_remote_id_fotocasa', true );
        $action = empty( $remote_id ) ? 'create' : 'update';

        if ( $this->is_sandbox() ) {
            // Simulate sandbox latency and response
            usleep( 500000 ); // 500ms
            
            $simulated_remote_id = empty( $remote_id ) ? 'FTC-' . rand( 100000, 999999 ) : $remote_id;
            
            // Build mock XML response
            $response_xml = '<?xml version="1.0" encoding="utf-8"?>
            <response>
                <status>success</status>
                <code style="color: #444;">200</code>
                <message>Property synced correctly via Fotocasa XML API</message>
                <listing_id>' . $simulated_remote_id . '</listing_id>
            </response>';

            // Save remote ID if was created
            if ( 'create' === $action ) {
                update_post_meta( $property_id, '_gpp_remote_id_fotocasa', $simulated_remote_id );
            }

            $this->get_logger()->log(
                $property_id,
                $this->portal_id,
                $action,
                'success',
                'Simulado en Sandbox correctamente (XML). ID: ' . $simulated_remote_id,
                $payload_xml,
                $response_xml
            );

            return array(
                'status'    => 'success',
                'remote_id' => $simulated_remote_id,
                'message'   => 'Simulado en Sandbox con éxito.'
            );
        } else {
            // Live SOAP/REST XML integration
            $client_id = isset( $this->settings['fotocasa_client_id'] ) ? $this->settings['fotocasa_client_id'] : '';
            $api_key   = isset( $this->settings['fotocasa_api_key'] ) ? $this->settings['fotocasa_api_key'] : '';

            if ( empty( $client_id ) || empty( $api_key ) ) {
                $msg = 'Error de Credenciales: Faltan credenciales de Fotocasa';
                $this->get_logger()->log( $property_id, $this->portal_id, $action, 'error', $msg, $payload_xml );
                return array( 'status' => 'error', 'message' => $msg );
            }

            $url = 'https://api.fotocasa.es/v1/listings';
            if ( 'update' === $action ) {
                $url .= '/' . $remote_id;
            }

            $response_raw = wp_remote_post( $url, array(
                'headers' => array(
                    'Content-Type'  => 'application/xml',
                    'X-Client-Id'   => $client_id,
                    'X-Api-Key'     => $api_key
                ),
                'body'    => $payload_xml,
                'method'  => ( 'update' === $action ) ? 'PUT' : 'POST',
                'timeout' => 15
            ) );

            if ( is_wp_error( $response_raw ) ) {
                $error_msg = $response_raw->get_error_message();
                $this->get_logger()->log( $property_id, $this->portal_id, $action, 'error', $error_msg, $payload_xml );
                return array( 'status' => 'error', 'message' => $error_msg );
            }

            $response_code = wp_remote_retrieve_response_code( $response_raw );
            $response_body = wp_remote_retrieve_body( $response_raw );

            // Process XML response
            libxml_use_internal_errors( true );
            $xml_response = simplexml_load_string( $response_body );

            if ( 200 === $response_code || 201 === $response_code ) {
                $new_remote_id = ( $xml_response && isset( $xml_response->listing_id ) ) ? (string) $xml_response->listing_id : $remote_id;
                if ( 'create' === $action ) {
                    update_post_meta( $property_id, '_gpp_remote_id_fotocasa', $new_remote_id );
                }

                $this->get_logger()->log(
                    $property_id,
                    $this->portal_id,
                    $action,
                    'success',
                    'Propiedad subida correctamente en XML.',
                    $payload_xml,
                    $response_body
                );

                return array(
                    'status'    => 'success',
                    'remote_id' => $new_remote_id,
                    'message'   => 'Sincronizado correctamente.'
                );
            } else {
                $err = ( $xml_response && isset( $xml_response->message ) ) ? (string) $xml_response->message : 'Respuesta errónea del servidor Fotocasa XML API (Código: ' . $response_code . ')';
                $this->get_logger()->log( $property_id, $this->portal_id, $action, 'error', $err, $payload_xml, $response_body );
                return array( 'status' => 'error', 'message' => $err );
            }
        }
    }

    /**
     * Delete property from Fotocasa (XML Payload)
     */
    public function delete_property( $property_id, $remote_id ) {
        if ( empty( $remote_id ) ) {
            return array( 'status' => 'error', 'message' => 'No remote ID exists to delete.' );
        }

        // Build XML delete request
        $xml = new SimpleXMLElement( '<?xml version="1.0" encoding="utf-8"?><delete_listing></delete_listing>' );
        $xml->addChild( 'listing_id', $remote_id );
        $xml->addChild( 'status', 'removed' );
        $payload_xml = $xml->asXML();

        if ( $this->is_sandbox() ) {
            usleep( 300000 ); // 300ms
            
            $response_xml = '<?xml version="1.0" encoding="utf-8"?>
            <response>
                <status>success</status>
                <message>Property successfully deleted from Fotocasa XML simulation</message>
            </response>';

            delete_post_meta( $property_id, '_gpp_remote_id_fotocasa' );

            $this->get_logger()->log(
                $property_id,
                $this->portal_id,
                'delete',
                'success',
                'Retirada de propiedad simulada en Sandbox. ID: ' . $remote_id,
                $payload_xml,
                $response_xml
            );

            return array( 'status' => 'success', 'message' => 'Retirada simulada con éxito.' );
        } else {
            $client_id = isset( $this->settings['fotocasa_client_id'] ) ? $this->settings['fotocasa_client_id'] : '';
            $api_key   = isset( $this->settings['fotocasa_api_key'] ) ? $this->settings['fotocasa_api_key'] : '';

            if ( empty( $client_id ) || empty( $api_key ) ) {
                return array( 'status' => 'error', 'message' => 'Credenciales incompletas' );
            }

            $url = 'https://api.fotocasa.es/v1/listings/' . $remote_id;
            
            $response_raw = wp_remote_post( $url, array(
                'headers' => array(
                    'Content-Type'  => 'application/xml',
                    'X-Client-Id'   => $client_id,
                    'X-Api-Key'     => $api_key
                ),
                'body'    => $payload_xml,
                'method'  => 'DELETE',
                'timeout' => 15
            ) );

            if ( is_wp_error( $response_raw ) ) {
                $error_msg = $response_raw->get_error_message();
                $this->get_logger()->log( $property_id, $this->portal_id, 'delete', 'error', $error_msg, $payload_xml );
                return array( 'status' => 'error', 'message' => $error_msg );
            }

            $response_code = wp_remote_retrieve_response_code( $response_raw );
            $response_body = wp_remote_retrieve_body( $response_raw );

            if ( 200 === $response_code || 204 === $response_code ) {
                delete_post_meta( $property_id, '_gpp_remote_id_fotocasa' );
                $this->get_logger()->log(
                    $property_id,
                    $this->portal_id,
                    'delete',
                    'success',
                    'Propiedad retirada del portal con éxito (XML).',
                    $payload_xml,
                    $response_body
                );
                return array( 'status' => 'success', 'message' => 'Retirada con éxito.' );
            } else {
                $this->get_logger()->log( $property_id, $this->portal_id, 'delete', 'error', 'Error en borrado (Código: ' . $response_code . ')', $payload_xml, $response_body );
                return array( 'status' => 'error', 'message' => 'Error al borrar del portal.' );
            }
        }
    }

    /**
     * Validate Fotocasa credentials
     */
    public function validate_credentials() {
        if ( $this->is_sandbox() ) {
            return true;
        }

        $client_id = isset( $this->settings['fotocasa_client_id'] ) ? $this->settings['fotocasa_client_id'] : '';
        $api_key   = isset( $this->settings['fotocasa_api_key'] ) ? $this->settings['fotocasa_api_key'] : '';

        if ( empty( $client_id ) || empty( $api_key ) ) {
            return false;
        }

        // Live credentials check endpoint ping
        $response = wp_remote_get( 'https://api.fotocasa.es/v1/auth/verify', array(
            'headers' => array(
                'X-Client-Id' => $client_id,
                'X-Api-Key'   => $api_key
            ),
            'timeout' => 8
        ) );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $code = wp_remote_retrieve_response_code( $response );
        return 200 === $code;
    }
}
