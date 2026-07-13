<?php
/**
 * Plugin Name: Gestion Plataforma Pisos - Sincronizador Inmobiliario Multiplataforma
 * Plugin URI: https://github.com/oscarmangutduran/GestionPlataformaPisos
 * Description: WordPress plugin that turns the website into the Single Source of Truth for real estate listings, syncing them automatically with Idealista, Fotocasa, and other portals.
 * Version: 1.0.0
 * Author: Antigravity AI
 * Author URI: https://github.com/oscarmangutduran
 * License: GPL2
 * Text Domain: gestion-plataforma-pisos
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define Plugin Constants
define( 'GPP_VERSION', '1.0.0' );
define( 'GPP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GPP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GPP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main Plugin Class (Singleton)
 */
class GestionPlataformaPisos {

    private static $instance = null;

    // Singletons instances of modules
    public $logger;
    public $cpt;
    public $image_handler;
    public $sync_engine;
    public $settings;

    /**
     * Get instance of the class
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_modules();
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once GPP_PLUGIN_DIR . 'includes/class-gpp-logger.php';
        require_once GPP_PLUGIN_DIR . 'includes/class-gpp-cpt.php';
        require_once GPP_PLUGIN_DIR . 'includes/class-gpp-image-handler.php';
        require_once GPP_PLUGIN_DIR . 'includes/class-gpp-settings.php';
        require_once GPP_PLUGIN_DIR . 'includes/class-gpp-sync-engine.php';

        // Load portal classes
        require_once GPP_PLUGIN_DIR . 'includes/portals/class-gpp-portal-base.php';
        require_once GPP_PLUGIN_DIR . 'includes/portals/class-gpp-portal-idealista.php';
        require_once GPP_PLUGIN_DIR . 'includes/portals/class-gpp-portal-fotocasa.php';

        // Load Elementor integration if active
        if ( did_action( 'elementor/loaded' ) ) {
            require_once GPP_PLUGIN_DIR . 'includes/class-gpp-elementor.php';
        }
    }

    /**
     * Initialize modules
     */
    private function init_modules() {
        $this->logger        = new GPP_Logger();
        $this->cpt           = new GPP_CPT();
        $this->image_handler = new GPP_Image_Handler();
        $this->settings      = new GPP_Settings();
        $this->sync_engine   = new GPP_Sync_Engine();

        if ( did_action( 'elementor/loaded' ) ) {
            new GPP_Elementor();
        }
    }

    /**
     * Initialize Hooks
     */
    private function init_hooks() {
        // Enqueue styles and scripts for blocks and admin if needed here
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
    }

    /**
     * Enqueue Admin Styles and Scripts
     */
    public function enqueue_admin_assets( $hook ) {
        // Only load on plugin settings or property CPT pages to avoid bloat
        $screen = get_current_screen();
        
        if ( 'toplevel_page_gpp-settings' === $hook || ( $screen && 'inmueble' === $screen->post_type ) ) {
            wp_enqueue_style( 'gpp-admin-style', GPP_PLUGIN_URL . 'assets/css/admin-style.css', array(), GPP_VERSION );
            wp_enqueue_script( 'gpp-admin-script', GPP_PLUGIN_URL . 'assets/js/admin-script.js', array( 'jquery' ), GPP_VERSION, true );
            
            // Localize script for ajax actions
            wp_localize_script( 'gpp-admin-script', 'gppParams', array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'gpp_admin_nonce' ),
            ) );
        }
    }

    /**
     * Enqueue Block Editor Assets (Gutenberg)
     */
    public function enqueue_block_editor_assets() {
        $screen = get_current_screen();
        if ( $screen && 'inmueble' === $screen->post_type ) {
            wp_enqueue_script(
                'gpp-gutenberg-sidebar',
                GPP_PLUGIN_URL . 'assets/js/gutenberg-sidebar.js',
                array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data' ),
                GPP_VERSION,
                true
            );
        }
    }

    /**
     * Plugin Activation Routine
     */
    public static function activate() {
        // Create Log table
        require_once GPP_PLUGIN_DIR . 'includes/class-gpp-logger.php';
        $logger = new GPP_Logger();
        $logger->create_table();

        // Flush rewrite rules for CPT
        require_once GPP_PLUGIN_DIR . 'includes/class-gpp-cpt.php';
        $cpt = new GPP_CPT();
        $cpt->register_post_type();
        flush_rewrite_rules();

        // Create default settings option if not exists
        if ( ! get_option( 'gpp_settings' ) ) {
            update_option( 'gpp_settings', array(
                'sandbox_mode' => '1',
                'idealista_enabled' => '0',
                'idealista_api_key' => '',
                'idealista_secret' => '',
                'fotocasa_enabled' => '0',
                'fotocasa_client_id' => '',
                'fotocasa_api_key' => '',
                'field_mapping' => array(
                    'precio' => 'gpp_precio',
                    'metros' => 'gpp_metros_cuadrados',
                    'habitaciones' => 'gpp_habitaciones',
                    'banos' => 'gpp_banos',
                    'certificacion' => 'gpp_certificacion_energetica',
                    'direccion' => 'gpp_direccion',
                    'latitud' => 'gpp_latitud',
                    'longitud' => 'gpp_longitud'
                )
            ) );
        }
    }

    /**
     * Plugin Deactivation Routine
     */
    public static function deactivate() {
        // Clear WP-Cron scheduled hooks
        wp_clear_scheduled_hook( 'gpp_process_sync_queue' );
        flush_rewrite_rules();
    }
}

// Register Hooks
register_activation_hook( __FILE__, array( 'GestionPlataformaPisos', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'GestionPlataformaPisos', 'deactivate' ) );

// Initialize the plugin
function gpp_init_plugin() {
    return GestionPlataformaPisos::get_instance();
}
add_action( 'plugins_loaded', 'gpp_init_plugin' );
