<?php
/**
 * GPP Settings & Logs Dashboard Class
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GPP_Settings {

    private $option_name = 'gpp_settings';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        // AJAX handlers
        add_action( 'wp_ajax_gpp_clear_logs', array( $this, 'ajax_clear_logs' ) );
        add_action( 'wp_ajax_gpp_test_credentials', array( $this, 'ajax_test_credentials' ) );
        add_action( 'wp_ajax_gpp_force_sync', array( $this, 'ajax_force_sync' ) );
    }

    /**
     * Register Admin Menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Sincronizador Inmobiliario', 'gestion-plataforma-pisos' ),
            __( 'Sincronizador Portales', 'gestion-plataforma-pisos' ),
            'manage_options',
            'gpp-settings',
            array( $this, 'render_settings_page' ),
            'dashicons-admin-home',
            30
        );
    }

    /**
     * Register Settings
     */
    public function register_settings() {
        register_setting( 'gpp_settings_group', $this->option_name, array( $this, 'sanitize_settings' ) );
    }

    /**
     * Sanitize settings input
     */
    public function sanitize_settings( $input ) {
        $output = array();

        $output['sandbox_mode']      = isset( $input['sandbox_mode'] ) ? '1' : '0';
        
        $output['idealista_enabled'] = isset( $input['idealista_enabled'] ) ? '1' : '0';
        $output['idealista_api_key'] = sanitize_text_field( $input['idealista_api_key'] );
        $output['idealista_secret']  = sanitize_text_field( $input['idealista_secret'] );

        $output['fotocasa_enabled']  = isset( $input['fotocasa_enabled'] ) ? '1' : '0';
        $output['fotocasa_client_id'] = sanitize_text_field( $input['fotocasa_client_id'] );
        $output['fotocasa_api_key']   = sanitize_text_field( $input['fotocasa_api_key'] );

        // Attribute Mappings
        if ( isset( $input['field_mapping'] ) && is_array( $input['field_mapping'] ) ) {
            $output['field_mapping'] = array_map( 'sanitize_text_field', $input['field_mapping'] );
        }

        return $output;
    }

    /**
     * Render Settings Page
     */
    public function render_settings_page() {
        $settings = get_option( $this->option_name );
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
        ?>
        <div class="wrap gpp-admin-wrap">
            <div class="gpp-admin-header">
                <h1>
                    <span class="dashicons dashicons-admin-home" style="font-size: 32px; width: 32px; height: 32px;"></span>
                    <?php _e( 'Sincronizador Inmobiliario Multiplataforma', 'gestion-plataforma-pisos' ); ?>
                </h1>
                <p><?php _e( 'Convierte tu WordPress en la Fuente Única de Verdad para tus inmuebles y propágalos automáticamente.', 'gestion-plataforma-pisos' ); ?></p>
            </div>

            <!-- Tab Navigation -->
            <h2 class="nav-tab-wrapper gpp-nav-tab-wrapper">
                <a href="?page=gpp-settings&tab=general" class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-generic"></span> <?php _e( 'Ajustes Generales', 'gestion-plataforma-pisos' ); ?>
                </a>
                <a href="?page=gpp-settings&tab=mapping" class="nav-tab <?php echo 'mapping' === $active_tab ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-settings"></span> <?php _e( 'Mapeo de Atributos', 'gestion-plataforma-pisos' ); ?>
                </a>
                <a href="?page=gpp-settings&tab=logs" class="nav-tab <?php echo 'logs' === $active_tab ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-list-view"></span> <?php _e( 'Registro de Sincronización', 'gestion-plataforma-pisos' ); ?>
                </a>
            </h2>

            <div class="gpp-tab-content-wrapper">
                <form method="post" action="options.php" style="<?php echo 'logs' === $active_tab ? 'display:none;' : ''; ?>">
                    <?php
                    settings_fields( 'gpp_settings_group' );
                    
                    if ( 'general' === $active_tab ) {
                        $this->render_general_tab( $settings );
                    } elseif ( 'mapping' === $active_tab ) {
                        $this->render_mapping_tab( $settings );
                    }

                    if ( 'logs' !== $active_tab ) {
                        submit_button( __( 'Guardar Configuración', 'gestion-plataforma-pisos' ), 'primary gpp-submit-btn' );
                    }
                    ?>
                </form>

                <?php
                if ( 'logs' === $active_tab ) {
                    $this->render_logs_tab();
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * General settings fields
     */
    private function render_general_tab( $settings ) {
        $sandbox = isset( $settings['sandbox_mode'] ) ? $settings['sandbox_mode'] : '1';
        
        $idealista_enabled = isset( $settings['idealista_enabled'] ) ? $settings['idealista_enabled'] : '0';
        $idealista_key     = isset( $settings['idealista_api_key'] ) ? $settings['idealista_api_key'] : '';
        $idealista_sec     = isset( $settings['idealista_secret'] ) ? $settings['idealista_secret'] : '';

        $fotocasa_enabled  = isset( $settings['fotocasa_enabled'] ) ? $settings['fotocasa_enabled'] : '0';
        $fotocasa_cid      = isset( $settings['fotocasa_client_id'] ) ? $settings['fotocasa_client_id'] : '';
        $fotocasa_key      = isset( $settings['fotocasa_api_key'] ) ? $settings['fotocasa_api_key'] : '';
        ?>
        <div class="gpp-card">
            <h2><?php _e( 'Entorno de Ejecución', 'gestion-plataforma-pisos' ); ?></h2>
            <div class="gpp-control-row">
                <div class="gpp-info-text">
                    <strong><?php _e( 'Modo Simulador / Sandbox', 'gestion-plataforma-pisos' ); ?></strong>
                    <p class="description"><?php _e( 'Si está activado, no se enviarán llamadas HTTP reales a los servidores de Idealista o Fotocasa. En su lugar, el plugin simulará las conexiones y registrará cargas útiles JSON/XML válidas para propósitos de prueba.', 'gestion-plataforma-pisos' ); ?></p>
                </div>
                <div class="gpp-switch-wrapper">
                    <label class="gpp-switch">
                        <input type="checkbox" name="gpp_settings[sandbox_mode]" value="1" <?php checked( $sandbox, '1' ); ?> />
                        <span class="gpp-slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <div class="gpp-grid-2">
            <!-- Idealista API Settings -->
            <div class="gpp-card">
                <div class="gpp-card-header">
                    <h2>Idealista API</h2>
                    <label class="gpp-switch">
                        <input type="checkbox" name="gpp_settings[idealista_enabled]" value="1" <?php checked( $idealista_enabled, '1' ); ?> />
                        <span class="gpp-slider"></span>
                    </label>
                </div>
                
                <div class="gpp-form-group">
                    <label for="idealista_api_key"><?php _e( 'API Key', 'gestion-plataforma-pisos' ); ?></label>
                    <input type="password" id="idealista_api_key" name="gpp_settings[idealista_api_key]" value="<?php echo esc_attr( $idealista_key ); ?>" class="regular-text" />
                </div>
                
                <div class="gpp-form-group">
                    <label for="idealista_secret"><?php _e( 'API Secret / Password', 'gestion-plataforma-pisos' ); ?></label>
                    <input type="password" id="idealista_secret" name="gpp_settings[idealista_secret]" value="<?php echo esc_attr( $idealista_sec ); ?>" class="regular-text" />
                </div>

                <div class="gpp-button-action" style="margin-top:20px;">
                    <button type="button" class="button gpp-test-cred-btn" data-portal="idealista">
                        <span class="dashicons dashicons-admin-links"></span>
                        <?php _e( 'Validar Conexión', 'gestion-plataforma-pisos' ); ?>
                    </button>
                    <span class="gpp-test-result" id="idealista-test-result"></span>
                </div>
            </div>

            <!-- Fotocasa XML API Settings -->
            <div class="gpp-card">
                <div class="gpp-card-header">
                    <h2>Fotocasa XML API</h2>
                    <label class="gpp-switch">
                        <input type="checkbox" name="gpp_settings[fotocasa_enabled]" value="1" <?php checked( $fotocasa_enabled, '1' ); ?> />
                        <span class="gpp-slider"></span>
                    </label>
                </div>

                <div class="gpp-form-group">
                    <label for="fotocasa_client_id"><?php _e( 'Client ID', 'gestion-plataforma-pisos' ); ?></label>
                    <input type="text" id="fotocasa_client_id" name="gpp_settings[fotocasa_client_id]" value="<?php echo esc_attr( $fotocasa_cid ); ?>" class="regular-text" />
                </div>

                <div class="gpp-form-group">
                    <label for="fotocasa_api_key"><?php _e( 'API Key', 'gestion-plataforma-pisos' ); ?></label>
                    <input type="password" id="fotocasa_api_key" name="gpp_settings[fotocasa_api_key]" value="<?php echo esc_attr( $fotocasa_key ); ?>" class="regular-text" />
                </div>

                <div class="gpp-button-action" style="margin-top:20px;">
                    <button type="button" class="button gpp-test-cred-btn" data-portal="fotocasa">
                        <span class="dashicons dashicons-admin-links"></span>
                        <?php _e( 'Validar Conexión', 'gestion-plataforma-pisos' ); ?>
                    </button>
                    <span class="gpp-test-result" id="fotocasa-test-result"></span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Attribute Mapping Screen
     */
    private function render_mapping_tab( $settings ) {
        $mappings = isset( $settings['field_mapping'] ) ? $settings['field_mapping'] : array();

        // Standard portal elements
        $portal_attributes = array(
            'precio'        => __( 'Precio del Inmueble', 'gestion-plataforma-pisos' ),
            'metros'        => __( 'Metros Cuadrados (Superficie)', 'gestion-plataforma-pisos' ),
            'habitaciones'  => __( 'Número de Habitaciones', 'gestion-plataforma-pisos' ),
            'banos'         => __( 'Número de Baños', 'gestion-plataforma-pisos' ),
            'certificacion' => __( 'Certificación Energética', 'gestion-plataforma-pisos' ),
            'direccion'     => __( 'Dirección Completa', 'gestion-plataforma-pisos' ),
            'latitud'       => __( 'Geolocalización: Latitud', 'gestion-plataforma-pisos' ),
            'longitud'      => __( 'Geolocalización: Longitud', 'gestion-plataforma-pisos' ),
        );

        // Get all meta keys from database to help user select mapping
        global $wpdb;
        $meta_keys = $wpdb->get_col( "SELECT DISTINCT meta_key FROM $wpdb->postmeta ORDER BY meta_key ASC" );
        
        // Filter empty/WP internal keys, but ensure our default ones exist
        $choices = array();
        $gpp_defaults = array(
            'gpp_precio' => '_gpp_precio',
            'gpp_metros_cuadrados' => '_gpp_metros_cuadrados',
            'gpp_habitaciones' => '_gpp_habitaciones',
            'gpp_banos' => '_gpp_banos',
            'gpp_certificacion_energetica' => '_gpp_certificacion_energetica',
            'gpp_direccion' => '_gpp_direccion',
            'gpp_latitud' => '_gpp_latitud',
            'gpp_longitud' => '_gpp_longitud'
        );

        foreach ( $meta_keys as $key ) {
            if ( 0 !== strpos( $key, '_' ) || in_array( $key, $gpp_defaults ) ) {
                $choices[ $key ] = $key;
            }
        }

        foreach ( $gpp_defaults as $display_name => $meta_key ) {
            $choices[ $meta_key ] = $display_name . ' (' . $meta_key . ')';
        }
        ksort( $choices );
        ?>
        <div class="gpp-card">
            <h2><?php _e( 'Mapeo Universal de Atributos', 'gestion-plataforma-pisos' ); ?></h2>
            <p class="description"><?php _e( 'Asocia los campos técnicos exigidos por Idealista y Fotocasa con las variables meta locales de tu WordPress.', 'gestion-plataforma-pisos' ); ?></p>
            
            <table class="form-table gpp-mapping-table">
                <thead>
                    <tr>
                        <th><?php _e( 'Atributo del Portal', 'gestion-plataforma-pisos' ); ?></th>
                        <th><?php _e( 'Campo WordPress (Meta Key)', 'gestion-plataforma-pisos' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $portal_attributes as $slug => $label ) : ?>
                        <?php 
                        $current_val = isset( $mappings[ $slug ] ) ? $mappings[ $slug ] : '';
                        if ( empty( $current_val ) ) {
                            // Smart fallback names matching defaults
                            if ( isset( $gpp_defaults[ 'gpp_' . $slug ] ) ) {
                                $current_val = $gpp_defaults[ 'gpp_' . $slug ];
                            }
                        }
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html( $label ); ?></strong>
                                <span class="gpp-attr-code"><code><?php echo esc_html( $slug ); ?></code></span>
                            </td>
                            <td>
                                <select name="gpp_settings[field_mapping][<?php echo esc_attr( $slug ); ?>]">
                                    <option value=""><?php _e( '-- Seleccionar Meta Key --', 'gestion-plataforma-pisos' ); ?></option>
                                    <?php foreach ( $choices as $key => $display ) : ?>
                                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_val, $key ); ?>>
                                            <?php echo esc_html( $display ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Renders log synchronization tab
     */
    private function render_logs_tab() {
        $logger = new GPP_Logger();
        $logs = $logger->get_logs( 50 );
        ?>
        <div class="gpp-card">
            <div class="gpp-card-header">
                <h2><?php _e( 'Historial de Sincronizaciones Recientes', 'gestion-plataforma-pisos' ); ?></h2>
                <div>
                    <button type="button" class="button button-secondary" id="gpp-clear-logs-btn">
                        <span class="dashicons dashicons-trash" style="margin-top: 4px;"></span>
                        <?php _e( 'Limpiar Registro', 'gestion-plataforma-pisos' ); ?>
                    </button>
                </div>
            </div>

            <?php if ( empty( $logs ) ) : ?>
                <div class="gpp-empty-state">
                    <span class="dashicons dashicons-saved" style="font-size: 48px; width: 48px; height: 48px; color: #a2a8b3;"></span>
                    <p><?php _e( 'No hay registros de sincronización disponibles.', 'gestion-plataforma-pisos' ); ?></p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped table-view-list gpp-logs-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;"><?php _e( 'ID', 'gestion-plataforma-pisos' ); ?></th>
                            <th><?php _e( 'Inmueble ID', 'gestion-plataforma-pisos' ); ?></th>
                            <th style="width: 120px;"><?php _e( 'Portal', 'gestion-plataforma-pisos' ); ?></th>
                            <th style="width: 120px;"><?php _e( 'Acción', 'gestion-plataforma-pisos' ); ?></th>
                            <th style="width: 130px;"><?php _e( 'Estado', 'gestion-plataforma-pisos' ); ?></th>
                            <th><?php _e( 'Detalles', 'gestion-plataforma-pisos' ); ?></th>
                            <th style="width: 180px;"><?php _e( 'Fecha y Hora', 'gestion-plataforma-pisos' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $logs as $log ) : 
                            $post_title = get_the_title( $log['property_id'] );
                            $post_title = $post_title ? $post_title : '#' . $log['property_id'];
                            
                            $status_class = 'gpp-status-' . $log['status'];
                            $portal_name  = ucfirst( $log['portal'] );
                            ?>
                            <tr class="gpp-log-row-summary" data-id="<?php echo esc_attr( $log['id'] ); ?>">
                                <td><?php echo esc_html( $log['id'] ); ?></td>
                                <td>
                                    <strong><a href="<?php echo get_edit_post_link( $log['property_id'] ); ?>"><?php echo esc_html( $post_title ); ?></a></strong>
                                </td>
                                <td>
                                    <span class="gpp-portal-badge gpp-badge-<?php echo esc_attr( $log['portal'] ); ?>">
                                        <?php echo esc_html( $portal_name ); ?>
                                    </span>
                                </td>
                                <td><code><?php echo esc_html( strtoupper( $log['action'] ) ); ?></code></td>
                                <td>
                                    <span class="gpp-status-badge <?php echo esc_attr( $status_class ); ?>">
                                        <?php echo esc_html( strtoupper( $log['status'] ) ); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="gpp-log-msg"><?php echo esc_html( $log['message'] ); ?></span>
                                    <button type="button" class="gpp-toggle-details-btn button-link">
                                        <?php _e( '[Ver Payloads]', 'gestion-plataforma-pisos' ); ?>
                                    </button>
                                </td>
                                <td><?php echo esc_html( $log['created_at'] ); ?></td>
                            </tr>
                            <tr class="gpp-log-row-details" id="log-details-<?php echo esc_attr( $log['id'] ); ?>" style="display:none;">
                                <td colspan="7" class="gpp-log-details-container">
                                    <div class="gpp-log-payloads-grid">
                                        <div class="gpp-payload-box">
                                            <h4><?php _e( 'Carga Útil Enviada (Payload)', 'gestion-plataforma-pisos' ); ?></h4>
                                            <pre class="gpp-code-block"><code><?php echo esc_html( $log['payload'] ); ?></code></pre>
                                        </div>
                                        <div class="gpp-payload-box">
                                            <h4><?php _e( 'Respuesta de la API', 'gestion-plataforma-pisos' ); ?></h4>
                                            <pre class="gpp-code-block"><code><?php echo esc_html( $log['response'] ); ?></code></pre>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * AJAX action to clear logs
     */
    public function ajax_clear_logs() {
        check_ajax_referer( 'gpp_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Forbidden', 'gestion-plataforma-pisos' ) ), 403 );
        }

        $logger = new GPP_Logger();
        $logger->clear_logs();
        wp_send_json_success( array( 'message' => __( 'Registros eliminados correctamente.', 'gestion-plataforma-pisos' ) ) );
    }

    /**
     * AJAX action to test portal credentials
     */
    public function ajax_test_credentials() {
        check_ajax_referer( 'gpp_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Forbidden', 'gestion-plataforma-pisos' ) ), 403 );
        }

        $portal = isset( $_POST['portal'] ) ? sanitize_text_field( $_POST['portal'] ) : '';
        $settings = get_option( $this->option_name );

        $client = null;
        if ( 'idealista' === $portal ) {
            $client = new GPP_Portal_Idealista( $settings );
        } elseif ( 'fotocasa' === $portal ) {
            $client = new GPP_Portal_Fotocasa( $settings );
        }

        if ( ! $client ) {
            wp_send_json_error( array( 'message' => __( 'Portal inválido.', 'gestion-plataforma-pisos' ) ) );
        }

        if ( $client->validate_credentials() ) {
            $msg = $client->is_sandbox() ? __( 'Simulación Sandbox: ¡Correcta!', 'gestion-plataforma-pisos' ) : __( '¡Conexión de producción validada correctamente!', 'gestion-plataforma-pisos' );
            wp_send_json_success( array( 'message' => $msg ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Error de credenciales o de red. Revisa la configuración.', 'gestion-plataforma-pisos' ) ) );
        }
    }

    /**
     * AJAX action to manually force a sync for testing purposes
     */
    public function ajax_force_sync() {
        check_ajax_referer( 'gpp_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Forbidden', 'gestion-plataforma-pisos' ) ), 403 );
        }

        $property_id = isset( $_POST['property_id'] ) ? intval( $_POST['property_id'] ) : 0;
        if ( ! $property_id || 'inmueble' !== get_post_type( $property_id ) ) {
            wp_send_json_error( array( 'message' => __( 'ID de inmueble no válido.', 'gestion-plataforma-pisos' ) ) );
        }

        // Trigger synchronization immediately
        $sync_engine = GestionPlataformaPisos::get_instance()->sync_engine;
        $results = $sync_engine->execute_sync( $property_id );

        wp_send_json_success( array(
            'message' => __( 'Sincronización forzada completada.', 'gestion-plataforma-pisos' ),
            'results' => $results
        ) );
    }
}
