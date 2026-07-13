<?php
/**
 * GPP Portal Base Class
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class GPP_Portal_Base {

    protected $portal_id;
    protected $portal_name;
    protected $settings;
    protected $sandbox_mode;
    protected $enabled;

    /**
     * Constructor
     *
     * @param array $settings Plugin settings
     */
    public function __construct( $settings ) {
        $this->settings = $settings;
        $this->sandbox_mode = isset( $settings['sandbox_mode'] ) && '1' === $settings['sandbox_mode'];
        $this->setup_portal_config();
    }

    /**
     * Retrieve portal ID
     */
    public function get_id() {
        return $this->portal_id;
    }

    /**
     * Retrieve portal Name
     */
    public function get_name() {
        return $this->portal_name;
    }

    /**
     * Verify if portal is enabled
     */
    public function is_enabled() {
        return $this->enabled;
    }

    /**
     * Verify if portal is in sandbox/simulation mode
     */
    public function is_sandbox() {
        return $this->sandbox_mode;
    }

    /**
     * Setup portal configurations (to be overridden by child class)
     */
    abstract protected function setup_portal_config();

    /**
     * Send property data (Create or Update)
     *
     * @param int   $property_id
     * @param array $mapped_data
     * @param array $images
     * @return array array( 'status' => 'success|error', 'remote_id' => '...', 'message' => '...' )
     */
    abstract public function send_property( $property_id, $mapped_data, $images );

    /**
     * Delete property from portal
     *
     * @param int    $property_id
     * @param string $remote_id
     * @return array array( 'status' => 'success|error', 'message' => '...' )
     */
    abstract public function delete_property( $property_id, $remote_id );

    /**
     * Validate portal credentials
     *
     * @return bool
     */
    abstract public function validate_credentials();

    /**
     * Get the main plugin logger instance
     */
    protected function get_logger() {
        return GestionPlataformaPisos::get_instance()->logger;
    }

    /**
     * Get the main plugin image handler instance
     */
    protected function get_image_handler() {
        return GestionPlataformaPisos::get_instance()->image_handler;
    }
}
