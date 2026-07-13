<?php
/**
 * GPP Custom Post Type & Meta Box Class
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GPP_CPT {

    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'init', array( $this, 'register_post_status' ) );
        add_action( 'init', array( $this, 'register_metadata' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_inmueble', array( $this, 'save_meta_boxes' ) );
        add_action( 'admin_footer-post.php', array( $this, 'append_post_status_dropdown' ) );
        add_action( 'admin_footer-post-new.php', array( $this, 'append_post_status_dropdown' ) );
    }

    /**
     * Register Custom Post Type 'inmueble'
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x( 'Inmuebles', 'Post Type General Name', 'gestion-plataforma-pisos' ),
            'singular_name'         => _x( 'Inmueble', 'Post Type Singular Name', 'gestion-plataforma-pisos' ),
            'menu_name'             => __( 'Inmuebles', 'gestion-plataforma-pisos' ),
            'name_admin_bar'        => __( 'Inmueble', 'gestion-plataforma-pisos' ),
            'archives'              => __( 'Archivo de Inmuebles', 'gestion-plataforma-pisos' ),
            'attributes'            => __( 'Atributos del Inmueble', 'gestion-plataforma-pisos' ),
            'parent_item_colon'     => __( 'Inmueble Padre:', 'gestion-plataforma-pisos' ),
            'all_items'             => __( 'Todos los Inmuebles', 'gestion-plataforma-pisos' ),
            'add_new_item'          => __( 'Añadir Nuevo Inmueble', 'gestion-plataforma-pisos' ),
            'add_new'               => __( 'Añadir Nuevo', 'gestion-plataforma-pisos' ),
            'new_item'              => __( 'Nuevo Inmueble', 'gestion-plataforma-pisos' ),
            'edit_item'             => __( 'Editar Inmueble', 'gestion-plataforma-pisos' ),
            'update_item'           => __( 'Actualizar Inmueble', 'gestion-plataforma-pisos' ),
            'view_item'             => __( 'Ver Inmueble', 'gestion-plataforma-pisos' ),
            'view_items'            => __( 'Ver Inmuebles', 'gestion-plataforma-pisos' ),
            'search_items'          => __( 'Buscar Inmueble', 'gestion-plataforma-pisos' ),
            'not_found'             => __( 'No se encontraron inmuebles.', 'gestion-plataforma-pisos' ),
            'not_found_in_trash'    => __( 'No se encontraron inmuebles en la papelera.', 'gestion-plataforma-pisos' ),
            'featured_image'        => __( 'Imagen Destacada', 'gestion-plataforma-pisos' ),
            'set_featured_image'    => __( 'Establecer imagen destacada', 'gestion-plataforma-pisos' ),
            'remove_featured_image' => __( 'Eliminar imagen destacada', 'gestion-plataforma-pisos' ),
            'use_featured_image'    => __( 'Usar como imagen destacada', 'gestion-plataforma-pisos' ),
            'insert_into_item'      => __( 'Insertar en el inmueble', 'gestion-plataforma-pisos' ),
            'uploaded_to_this_item' => __( 'Subido a este inmueble', 'gestion-plataforma-pisos' ),
            'items_list'            => __( 'Lista de Inmuebles', 'gestion-plataforma-pisos' ),
            'items_list_navigation' => __( 'Navegación de lista de inmuebles', 'gestion-plataforma-pisos' ),
            'filter_items_list'     => __( 'Filtrar lista de inmuebles', 'gestion-plataforma-pisos' ),
        );

        $args = array(
            'label'                 => __( 'Inmueble', 'gestion-plataforma-pisos' ),
            'description'           => __( 'Custom Post Type para gestionar inmuebles.', 'gestion-plataforma-pisos' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
            'taxonomies'            => array(),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-admin-home',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'show_in_rest'          => true, // Enable Gutenberg support
        );

        register_post_type( 'inmueble', $args );
    }

    /**
     * Register custom post status 'vendido'
     */
    public function register_post_status() {
        register_post_status( 'vendido', array(
            'label'                     => _x( 'Vendido', 'post status label', 'gestion-plataforma-pisos' ),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Vendido <span class="count">(%s)</span>', 'Vendidos <span class="count">(%s)</span>', 'gestion-plataforma-pisos' ),
        ) );
    }

    /**
     * Register Meta Fields for the REST API and security sanitization
     */
    public function register_metadata() {
        $meta_fields = array(
            '_gpp_precio'                    => array( 'type' => 'number', 'single' => true, 'sanitize' => 'intval' ),
            '_gpp_metros_cuadrados'          => array( 'type' => 'number', 'single' => true, 'sanitize' => 'intval' ),
            '_gpp_habitaciones'              => array( 'type' => 'number', 'single' => true, 'sanitize' => 'intval' ),
            '_gpp_banos'                     => array( 'type' => 'number', 'single' => true, 'sanitize' => 'intval' ),
            '_gpp_certificacion_energetica'  => array( 'type' => 'string', 'single' => true, 'sanitize' => 'sanitize_text_field' ),
            '_gpp_direccion'                 => array( 'type' => 'string', 'single' => true, 'sanitize' => 'sanitize_text_field' ),
            '_gpp_latitud'                   => array( 'type' => 'string', 'single' => true, 'sanitize' => 'sanitize_text_field' ),
            '_gpp_longitud'                  => array( 'type' => 'string', 'single' => true, 'sanitize' => 'sanitize_text_field' ),
            '_gpp_galeria'                   => array( 'type' => 'string', 'single' => true, 'sanitize' => 'sanitize_text_field' ),
            
            // Sync triggers
            '_gpp_sync_idealista'            => array( 'type' => 'boolean', 'single' => true, 'sanitize' => 'rest_sanitize_boolean' ),
            '_gpp_sync_fotocasa'             => array( 'type' => 'boolean', 'single' => true, 'sanitize' => 'rest_sanitize_boolean' ),
            
            // Remote ID storage for portal tracking
            '_gpp_remote_id_idealista'       => array( 'type' => 'string', 'single' => true, 'sanitize' => 'sanitize_text_field' ),
            '_gpp_remote_id_fotocasa'        => array( 'type' => 'string', 'single' => true, 'sanitize' => 'sanitize_text_field' ),
        );

        foreach ( $meta_fields as $meta_key => $args ) {
            register_post_meta( 'inmueble', $meta_key, array(
                'show_in_rest'  => true,
                'single'        => $args['single'],
                'type'          => $args['type'],
                'auth_callback' => function() {
                    return current_user_can( 'edit_posts' );
                }
            ) );
        }
    }

    /**
     * Add Meta Boxes for Classic Editor and Gutenberg fallback
     */
    public function add_meta_boxes() {
        add_meta_box(
            'gpp_inmueble_datos',
            __( 'Datos Técnicos del Inmueble', 'gestion-plataforma-pisos' ),
            array( $this, 'render_inmueble_meta_box' ),
            'inmueble',
            'normal',
            'high'
        );

        add_meta_box(
            'gpp_inmueble_portales_classic',
            __( 'Sincronización (Editor Clásico)', 'gestion-plataforma-pisos' ),
            array( $this, 'render_portales_classic_meta_box' ),
            'inmueble',
            'side',
            'core'
        );
    }

    /**
     * Render the Data Meta Box
     */
    public function render_inmueble_meta_box( $post ) {
        wp_nonce_field( 'gpp_save_inmueble_meta', 'gpp_inmueble_meta_nonce' );

        // Get values
        $precio        = get_post_meta( $post->ID, '_gpp_precio', true );
        $metros        = get_post_meta( $post->ID, '_gpp_metros_cuadrados', true );
        $habitaciones  = get_post_meta( $post->ID, '_gpp_habitaciones', true );
        $banos         = get_post_meta( $post->ID, '_gpp_banos', true );
        $certificacion = get_post_meta( $post->ID, '_gpp_certificacion_energetica', true );
        $direccion     = get_post_meta( $post->ID, '_gpp_direccion', true );
        $latitud       = get_post_meta( $post->ID, '_gpp_latitud', true );
        $longitud      = get_post_meta( $post->ID, '_gpp_longitud', true );
        $galeria       = get_post_meta( $post->ID, '_gpp_galeria', true );

        ?>
        <div class="gpp-meta-container">
            <div class="gpp-meta-row gpp-grid-3">
                <div class="gpp-meta-col">
                    <label for="gpp_precio"><?php _e( 'Precio (€)', 'gestion-plataforma-pisos' ); ?></label>
                    <input type="number" id="gpp_precio" name="gpp_precio" value="<?php echo esc_attr( $precio ); ?>" placeholder="e.g. 250000" />
                </div>
                <div class="gpp-meta-col">
                    <label for="gpp_metros"><?php _e( 'Superficie (m²)', 'gestion-plataforma-pisos' ); ?></label>
                    <input type="number" id="gpp_metros" name="gpp_metros" value="<?php echo esc_attr( $metros ); ?>" placeholder="e.g. 90" />
                </div>
                <div class="gpp-meta-col">
                    <label for="gpp_certificacion"><?php _e( 'Certificación Energética', 'gestion-plataforma-pisos' ); ?></label>
                    <select id="gpp_certificacion" name="gpp_certificacion">
                        <option value=""><?php _e( 'Seleccionar...', 'gestion-plataforma-pisos' ); ?></option>
                        <?php foreach ( array( 'A', 'B', 'C', 'D', 'E', 'F', 'G' ) as $letra ) : ?>
                            <option value="<?php echo $letra; ?>" <?php selected( $certificacion, $letra ); ?>><?php echo $letra; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="gpp-meta-row gpp-grid-2">
                <div class="gpp-meta-col">
                    <label for="gpp_habitaciones"><?php _e( 'Habitaciones', 'gestion-plataforma-pisos' ); ?></label>
                    <input type="number" id="gpp_habitaciones" name="gpp_habitaciones" value="<?php echo esc_attr( $habitaciones ); ?>" placeholder="e.g. 3" min="0" />
                </div>
                <div class="gpp-meta-col">
                    <label for="gpp_banos"><?php _e( 'Baños', 'gestion-plataforma-pisos' ); ?></label>
                    <input type="number" id="gpp_banos" name="gpp_banos" value="<?php echo esc_attr( $banos ); ?>" placeholder="e.g. 2" min="0" />
                </div>
            </div>

            <hr class="gpp-divider">

            <div class="gpp-meta-row">
                <h3><?php _e( 'Ubicación y Geoposicionamiento', 'gestion-plataforma-pisos' ); ?></h3>
                <div class="gpp-meta-col">
                    <label for="gpp_direccion"><?php _e( 'Dirección Completa', 'gestion-plataforma-pisos' ); ?></label>
                    <input type="text" id="gpp_direccion" name="gpp_direccion" value="<?php echo esc_attr( $direccion ); ?>" placeholder="Calle Mayor, 12, Madrid" style="width: 100%;" />
                </div>
            </div>
            <div class="gpp-meta-row gpp-grid-2" style="margin-top: 15px;">
                <div class="gpp-meta-col">
                    <label for="gpp_latitud"><?php _e( 'Latitud', 'gestion-plataforma-pisos' ); ?></label>
                    <input type="text" id="gpp_latitud" name="gpp_latitud" value="<?php echo esc_attr( $latitud ); ?>" placeholder="40.416775" />
                </div>
                <div class="gpp-meta-col">
                    <label for="gpp_longitud"><?php _e( 'Longitud', 'gestion-plataforma-pisos' ); ?></label>
                    <input type="text" id="gpp_longitud" name="gpp_longitud" value="<?php echo esc_attr( $longitud ); ?>" placeholder="-3.703790" />
                </div>
            </div>

            <hr class="gpp-divider">

            <div class="gpp-meta-row">
                <h3><?php _e( 'Galería de Imágenes del Inmueble', 'gestion-plataforma-pisos' ); ?></h3>
                <input type="hidden" id="gpp_galeria" name="gpp_galeria" value="<?php echo esc_attr( $galeria ); ?>" />
                
                <div id="gpp-gallery-wrapper" class="gpp-gallery-wrapper">
                    <?php
                    if ( ! empty( $galeria ) ) {
                        $img_ids = explode( ',', $galeria );
                        foreach ( $img_ids as $img_id ) {
                            $img_src = wp_get_attachment_image_src( $img_id, 'thumbnail' );
                            if ( $img_src ) {
                                echo '<div class="gpp-gallery-item" data-id="' . esc_attr( $img_id ) . '">';
                                echo '<img src="' . esc_url( $img_src[0] ) . '" />';
                                echo '<button class="gpp-remove-image dashicons dashicons-no-alt" type="button"></button>';
                                echo '</div>';
                            }
                        }
                    }
                    ?>
                </div>
                <button type="button" class="button button-primary button-large" id="gpp-select-gallery" style="margin-top:15px;">
                    <span class="dashicons dashicons-images-alt2" style="margin-top: 4px; margin-right: 5px;"></span>
                    <?php _e( 'Gestionar Galería', 'gestion-plataforma-pisos' ); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Render the classic editor side metabox for sync control
     */
    public function render_portales_classic_meta_box( $post ) {
        $sync_idealista = get_post_meta( $post->ID, '_gpp_sync_idealista', true );
        $sync_fotocasa  = get_post_meta( $post->ID, '_gpp_sync_fotocasa', true );
        
        $remote_id_idealista = get_post_meta( $post->ID, '_gpp_remote_id_idealista', true );
        $remote_id_fotocasa  = get_post_meta( $post->ID, '_gpp_remote_id_fotocasa', true );

        ?>
        <p><em><?php _e( 'Selecciona a qué portales propagar este inmueble.', 'gestion-plataforma-pisos' ); ?></em></p>
        
        <div style="margin-bottom: 15px;">
            <label class="gpp-switch-label" style="display: flex; align-items: center; justify-content: space-between;">
                <span>Idealista</span>
                <input type="checkbox" name="gpp_sync_idealista" value="1" <?php checked( $sync_idealista, '1' ); ?> />
            </label>
            <?php if ( $remote_id_idealista ) : ?>
                <span class="description" style="color: #46b450; font-size: 11px; display: block; margin-top: 3px;">
                    ✓ Sincronizado (ID: <?php echo esc_html( $remote_id_idealista ); ?>)
                </span>
            <?php endif; ?>
        </div>

        <div style="margin-bottom: 15px;">
            <label class="gpp-switch-label" style="display: flex; align-items: center; justify-content: space-between;">
                <span>Fotocasa</span>
                <input type="checkbox" name="gpp_sync_fotocasa" value="1" <?php checked( $sync_fotocasa, '1' ); ?> />
            </label>
            <?php if ( $remote_id_fotocasa ) : ?>
                <span class="description" style="color: #46b450; font-size: 11px; display: block; margin-top: 3px;">
                    ✓ Sincronizado (ID: <?php echo esc_html( $remote_id_fotocasa ); ?>)
                </span>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Save Meta Box values
     */
    public function save_meta_boxes( $post_id ) {
        // Security check
        if ( ! isset( $_POST['gpp_inmueble_meta_nonce'] ) || ! wp_verify_nonce( $_POST['gpp_inmueble_meta_nonce'], 'gpp_save_inmueble_meta' ) ) {
            return;
        }

        // Autosave check
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Permissions check
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save technical fields
        $fields = array(
            'gpp_precio'                   => '_gpp_precio',
            'gpp_metros'                   => '_gpp_metros_cuadrados',
            'gpp_habitaciones'             => '_gpp_habitaciones',
            'gpp_banos'                    => '_gpp_banos',
            'gpp_certificacion'            => '_gpp_certificacion_energetica',
            'gpp_direccion'                => '_gpp_direccion',
            'gpp_latitud'                  => '_gpp_latitud',
            'gpp_longitud'                 => '_gpp_longitud',
            'gpp_galeria'                  => '_gpp_galeria',
        );

        foreach ( $fields as $post_key => $meta_key ) {
            if ( isset( $_POST[ $post_key ] ) ) {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $post_key ] ) );
            }
        }

        // Save Classic editor sync switches
        $sync_idealista = isset( $_POST['gpp_sync_idealista'] ) ? '1' : '0';
        $sync_fotocasa  = isset( $_POST['gpp_sync_fotocasa'] ) ? '1' : '0';

        // Update post meta if save is not coming from Gutenberg (Gutenberg directly sends post rest updates)
        if ( ! isset( $_POST['__wp-no-html-class-editor-support-flag'] ) ) {
            update_post_meta( $post_id, '_gpp_sync_idealista', $sync_idealista );
            update_post_meta( $post_id, '_gpp_sync_fotocasa', $sync_fotocasa );
        }
    }

    /**
     * Add post status 'vendido' to select drop-downs in Classic Editor
     */
    public function append_post_status_dropdown() {
        global $post;
        if ( ! $post || 'inmueble' !== $post->post_type ) {
            return;
        }
        $complete = ( 'vendido' === $post->post_status ) ? "selected='selected'" : '';
        ?>
        <script>
        jQuery(document).ready(function($){
            $("select#post_status").append("<option value='vendido' <?php echo $complete; ?>><?php _e( 'Vendido', 'gestion-plataforma-pisos' ); ?></option>");
            if ( 'vendido' === '<?php echo $post->post_status; ?>' ) {
                $('#post-status-display').text('<?php _e( "Vendido", "gestion-plataforma-pisos" ); ?>');
                $('.save-post').val('<?php _e( "Guardar como vendido", "gestion-plataforma-pisos" ); ?>');
            }
        });
        </script>
        <?php
    }
}
