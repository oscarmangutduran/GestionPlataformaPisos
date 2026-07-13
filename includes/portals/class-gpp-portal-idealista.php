<?php
/**
 * GPP Idealista Portal Class
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GPP_Portal_Idealista extends GPP_Portal_Base {

    protected function setup_portal_config() {
        $this->portal_id   = 'idealista';
        $this->portal_name = 'Idealista';
        $this->enabled     = isset( $this->settings['idealista_enabled'] ) && '1' === $this->settings['idealista_enabled'];
    }

    /**
     * Send property to Idealista (JSON)
     */
    public function send_property( $property_id, $mapped_data, $images ) {
        // Build JSON Payload
        $payload = array(
            'operation'       => 'sale', // Default to sale
            'propertyType'    => 'flat', // Default to flat
            'price'           => isset( $mapped_data['precio'] ) ? intval( $mapped_data['precio'] ) : 0,
            'size'            => isset( $mapped_data['metros'] ) ? intval( $mapped_data['metros'] ) : 0,
            'rooms'           => isset( $mapped_data['habitaciones'] ) ? intval( $mapped_data['habitaciones'] ) : 0,
            'bathrooms'       => isset( $mapped_data['banos'] ) ? intval( $mapped_data['banos'] ) : 0,
            'energyCertificate' => array(
                'label' => isset( $mapped_data['certificacion'] ) ? sanitize_text_field( $mapped_data['certificacion'] ) : 'G'
            ),
            'address' => array(
                'streetName'  => isset( $mapped_data['direccion'] ) ? sanitize_text_field( $mapped_data['direccion'] ) : '',
                'coordinates' => array(
                    'latitude'  => isset( $mapped_data['latitud'] ) ? doubleval( $mapped_data['latitud'] ) : 0.0,
                    'longitude' => isset( $mapped_data['longitud'] ) ? doubleval( $mapped_data['longitud'] ) : 0.0
                )
            ),
            'images' => array()
        );

        // Process images with Optimizer constraints (Idealista requirements: max 2MB, max 1920x1080)
        $image_handler = $this->get_image_handler();
        $order = 1;
        foreach ( $images as $img_id ) {
            $optimized_url = $image_handler->get_optimized_image_url( $img_id, 1920, 1080, 2097152 );
            if ( $optimized_url ) {
                $payload['images'][] = array(
                    'url'   => $optimized_url,
                    'order' => $order++
                );
            }
        }

        // Determine if it is a new listing or update
        $remote_id = get_post_meta( $property_id, '_gpp_remote_id_idealista', true );
        $action = empty( $remote_id ) ? 'create' : 'update';

        // Execute request
        if ( $this->is_sandbox() ) {
            // Simulate sandbox latency and response
            usleep( 500000 ); // 500ms
            
            $simulated_remote_id = empty( $remote_id ) ? 'IDE-' . rand( 100000, 999999 ) : $remote_id;
            $response = array(
                'status'    => 200,
                'message'   => 'Property successfully synchronized in Sandbox Mode.',
                'idealistaId' => $simulated_remote_id,
                'timestamp' => current_time( 'mysql' )
            );

            // Save remote ID if was created
            if ( 'create' === $action ) {
                update_post_meta( $property_id, '_gpp_remote_id_idealista', $simulated_remote_id );
            }

            $this->get_logger()->log(
                $property_id,
                $this->portal_id,
                $action,
                'success',
                'Simulado en Sandbox correctamente. ID: ' . $simulated_remote_id,
                $payload,
                $response
            );

            return array(
                'status'    => 'success',
                'remote_id' => $simulated_remote_id,
                'message'   => 'Simulado en Sandbox con éxito.'
            );
        } else {
            // Live integration
            $api_key = isset( $this->settings['idealista_api_key'] ) ? $this->settings['idealista_api_key'] : '';
            $secret  = isset( $this->settings['idealista_secret'] ) ? $this->settings['idealista_secret'] : '';

            if ( empty( $api_key ) || empty( $secret ) ) {
                $msg = 'Error de Credenciales: Faltan credenciales de Idealista';
                $this->get_logger()->log( $property_id, $this->portal_id, $action, 'error', $msg, $payload );
                return array( 'status' => 'error', 'message' => $msg );
            }

            $url = 'https://api.idealista.com/v2/property';
            if ( 'update' === $action ) {
                $url .= '/' . $remote_id;
            }

            // Real HTTP Request
            $response_raw = wp_remote_post( $url, array(
                'headers' => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $this->get_oauth_token( $api_key, $secret )
                ),
                'body'    => wp_json_encode( $payload ),
                'method'  => ( 'update' === $action ) ? 'PUT' : 'POST',
                'timeout' => 15
            ) );

            if ( is_wp_error( $response_raw ) ) {
                $error_msg = $response_raw->get_error_message();
                $this->get_logger()->log( $property_id, $this->portal_id, $action, 'error', $error_msg, $payload );
                return array( 'status' => 'error', 'message' => $error_msg );
            }

            $response_code = wp_remote_retrieve_response_code( $response_raw );
            $response_body = wp_remote_retrieve_body( $response_raw );
            $decoded_body  = json_decode( $response_body, true );

            if ( 200 === $response_code || 201 === $response_code ) {
                $new_remote_id = isset( $decoded_body['id'] ) ? $decoded_body['id'] : $remote_id;
                if ( 'create' === $action ) {
                    update_post_meta( $property_id, '_gpp_remote_id_idealista', $new_remote_id );
                }
                
                $this->get_logger()->log(
                    $property_id,
                    $this->portal_id,
                    $action,
                    'success',
                    'Propiedad subida correctamente.',
                    $payload,
                    $decoded_body
                );
                
                return array(
                    'status'    => 'success',
                    'remote_id' => $new_remote_id,
                    'message'   => 'Sincronizado correctamente.'
                );
            } else {
                $err = isset( $decoded_body['message'] ) ? $decoded_body['message'] : 'Respuesta errónea del servidor API (Código: ' . $response_code . ')';
                $this->get_logger()->log( $property_id, $this->portal_id, $action, 'error', $err, $payload, $decoded_body );
                return array( 'status' => 'error', 'message' => $err );
            }
        }
    }

    /**
     * Delete property from Idealista (JSON)
     */
    public function delete_property( $property_id, $remote_id ) {
        if ( empty( $remote_id ) ) {
            return array( 'status' => 'error', 'message' => 'No remote ID exists to delete.' );
        }

        $payload = array(
            'status' => 'inactive',
            'reason' => 'sold_or_draft'
        );

        if ( $this->is_sandbox() ) {
            usleep( 300000 ); // 300ms
            
            $response = array(
                'status'    => 200,
                'message'   => 'Property successfully deleted in Sandbox Mode.',
                'idealistaId' => $remote_id,
            );

            delete_post_meta( $property_id, '_gpp_remote_id_idealista' );

            $this->get_logger()->log(
                $property_id,
                $this->portal_id,
                'delete',
                'success',
                'Retirada de propiedad simulada en Sandbox. ID: ' . $remote_id,
                $payload,
                $response
            );

            return array( 'status' => 'success', 'message' => 'Retirada simulada con éxito.' );
        } else {
            $api_key = isset( $this->settings['idealista_api_key'] ) ? $this->settings['idealista_api_key'] : '';
            $secret  = isset( $this->settings['idealista_secret'] ) ? $this->settings['idealista_secret'] : '';

            if ( empty( $api_key ) || empty( $secret ) ) {
                return array( 'status' => 'error', 'message' => 'Credenciales incompletas' );
            }

            $url = 'https://api.idealista.com/v2/property/' . $remote_id;
            
            $response_raw = wp_remote_post( $url, array(
                'headers' => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $this->get_oauth_token( $api_key, $secret )
                ),
                'body'    => wp_json_encode( $payload ),
                'method'  => 'DELETE',
                'timeout' => 15
            ) );

            if ( is_wp_error( $response_raw ) ) {
                $error_msg = $response_raw->get_error_message();
                $this->get_logger()->log( $property_id, $this->portal_id, 'delete', 'error', $error_msg, $payload );
                return array( 'status' => 'error', 'message' => $error_msg );
            }

            $response_code = wp_remote_retrieve_response_code( $response_raw );
            $response_body = wp_remote_retrieve_body( $response_raw );
            $decoded_body  = json_decode( $response_body, true );

            if ( 200 === $response_code || 204 === $response_code ) {
                delete_post_meta( $property_id, '_gpp_remote_id_idealista' );
                $this->get_logger()->log(
                    $property_id,
                    $this->portal_id,
                    'delete',
                    'success',
                    'Propiedad retirada del portal con éxito.',
                    $payload,
                    $decoded_body
                );
                return array( 'status' => 'success', 'message' => 'Retirada con éxito.' );
            } else {
                $err = isset( $decoded_body['message'] ) ? $decoded_body['message'] : 'Error en la petición de borrado (Código: ' . $response_code . ')';
                $this->get_logger()->log( $property_id, $this->portal_id, 'delete', 'error', $err, $payload, $decoded_body );
                return array( 'status' => 'error', 'message' => $err );
            }
        }
    }

    /**
     * Mock / Actual OAuth credential validation
     */
    public function validate_credentials() {
        if ( $this->is_sandbox() ) {
            return true;
        }

        $api_key = isset( $this->settings['idealista_api_key'] ) ? $this->settings['idealista_api_key'] : '';
        $secret  = isset( $this->settings['idealista_secret'] ) ? $this->settings['idealista_secret'] : '';
        
        if ( empty( $api_key ) || empty( $secret ) ) {
            return false;
        }

        // Simulates getting an authorization token
        $token = $this->get_oauth_token( $api_key, $secret );
        return ! empty( $token );
    }

    /**
     * Get OAuth Authorization token
     */
    private function get_oauth_token( $api_key, $secret ) {
        // Cache token in transients to avoid request overflow
        $transient_key = 'gpp_idealista_token_' . md5( $api_key . $secret );
        $cached_token = get_transient( $transient_key );

        if ( $cached_token ) {
            return $cached_token;
        }

        $auth_hash = base64_encode( $api_key . ':' . $secret );
        
        $response = wp_remote_post( 'https://api.idealista.com/oauth/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . $auth_hash,
                'Content-Type'  => 'application/x-www-form-urlencoded;charset=UTF-8'
            ),
            'body' => array(
                'grant_type' => 'client_credentials',
                'scope'      => 'read_write'
            ),
            'timeout' => 10
        ) );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $token = isset( $body['access_token'] ) ? $body['access_token'] : false;
        $expires = isset( $body['expires_in'] ) ? intval( $body['expires_in'] ) - 60 : 3500; // Deduct 1 min margin

        if ( $token ) {
            set_transient( $transient_key, $token, $expires );
        }

        return $token;
    }
}
