<?php
/**
 * GPP Logger Class
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GPP_Logger {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'gpp_sync_logs';
    }

    /**
     * Create the logs table in database
     */
    public function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            property_id bigint(20) NOT NULL,
            portal varchar(50) NOT NULL,
            action varchar(20) NOT NULL,
            status varchar(20) NOT NULL,
            message text NOT NULL,
            payload longtext NOT NULL,
            response longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY property_id (property_id),
            KEY portal (portal),
            KEY status (status)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Log a sync action
     *
     * @param int    $property_id
     * @param string $portal
     * @param string $action
     * @param string $status
     * @param string $message
     * @param mixed  $payload
     * @param mixed  $response
     */
    public function log( $property_id, $portal, $action, $status, $message, $payload = '', $response = '' ) {
        global $wpdb;

        // Serialize payloads if they are arrays/objects
        if ( is_array( $payload ) || is_object( $payload ) ) {
            $payload = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
        }
        if ( is_array( $response ) || is_object( $response ) ) {
            $response = wp_json_encode( $response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
        }

        $wpdb->insert(
            $this->table_name,
            array(
                'property_id' => intval( $property_id ),
                'portal'      => sanitize_text_field( $portal ),
                'action'      => sanitize_text_field( $action ),
                'status'      => sanitize_text_field( $status ),
                'message'     => sanitize_text_field( $message ),
                'payload'     => $payload,
                'response'    => $response,
                'created_at'  => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Retrieve sync logs
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function get_logs( $limit = 50, $offset = 0 ) {
        global $wpdb;
        $limit  = intval( $limit );
        $offset = intval( $offset );

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $this->table_name ORDER BY id DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            ),
            ARRAY_A
        );
    }

    /**
     * Get total log count
     *
     * @return int
     */
    public function get_log_count() {
        global $wpdb;
        return intval( $wpdb->get_var( "SELECT COUNT(id) FROM $this->table_name" ) );
    }

    /**
     * Clear all logs
     */
    public function clear_logs() {
        global $wpdb;
        $wpdb->query( "TRUNCATE TABLE $this->table_name" );
    }
}
