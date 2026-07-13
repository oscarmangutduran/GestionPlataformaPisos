<?php
/**
 * GPP Sync Engine Class
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GPP_Sync_Engine {

    public function __construct() {
        // Detect post status transitions (publish, draft, trash, etc.)
        add_action( 'transition_post_status', array( $this, 'handle_post_transitions' ), 10, 3 );
        
        // Cron action hook
        add_action( 'gpp_process_sync_queue', array( $this, 'process_sync' ), 10, 1 );
    }

    /**
     * Listen to post status changes to schedule sync tasks
     *
     * @param string  $new_status
     * @param string  $old_status
     * @param WP_Post $post
     */
    public function handle_post_transitions( $new_status, $old_status, $post ) {
        if ( 'inmueble' !== $post->post_type ) {
            return;
        }

        // Avoid infinite loops during sync itself
        if ( defined( 'GPP_IS_SYNCING' ) && GPP_IS_SYNCING ) {
            return;
        }

        // We trigger sync tasks on:
        // 1. Publication or updates of a published post ($new_status === 'publish')
        // 2. Draft/Trash/Vendido transition to take down property ($new_status is draft/trash/vendido and was previously published or contains active remote IDs)
        
        $has_remote_ids = $this->has_active_remote_ids( $post->ID );

        if ( 'publish' === $new_status ) {
            // Queue sync (upload/update)
            $this->queue_sync_task( $post->ID );
        } elseif ( in_array( $new_status, array( 'draft', 'trash', 'vendido', 'private' ) ) ) {
            // If the post has remote IDs or was published, we must take it down
            if ( 'publish' === $old_status || $has_remote_ids ) {
                // Queue sync (which will trigger deletion because status is not publish)
                $this->queue_sync_task( $post->ID );
            }
        }
    }

    /**
     * Schedule a single cron event to execute sync asynchronously
     *
     * @param int $post_id
     */
    private function queue_sync_task( $post_id ) {
        // Clear any pending sync for this specific post to avoid duplicate queues
        wp_clear_scheduled_hook( 'gpp_process_sync_queue', array( $post_id ) );

        // Schedule sync task to run immediately in 2 seconds (asynchronously)
        wp_schedule_single_event( time() + 2, 'gpp_process_sync_queue', array( $post_id ) );
    }

    /**
     * Check if a property has active portal associations
     *
     * @param int $post_id
     * @return bool
     */
    private function has_active_remote_ids( $post_id ) {
        $idealista_id = get_post_meta( $post_id, '_gpp_remote_id_idealista', true );
        $fotocasa_id  = get_post_meta( $post_id, '_gpp_remote_id_fotocasa', true );
        return ( ! empty( $idealista_id ) || ! empty( $fotocasa_id ) );
    }

    /**
     * Executed by WP-Cron to run the sync
     *
     * @param int $post_id
     */
    public function process_sync( $post_id ) {
        // Define constant to prevent post transition loops
        if ( ! defined( 'GPP_IS_SYNCING' ) ) {
            define( 'GPP_IS_SYNCING', true );
        }

        $this->execute_sync( $post_id );
    }

    /**
     * Primary sync routine (Can also be triggered manually)
     *
     * @param int $post_id
     * @return array Sync results
     */
    public function execute_sync( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || 'inmueble' !== $post->post_type ) {
            return array();
        }

        $settings = get_option( 'gpp_settings' );
        $mappings = isset( $settings['field_mapping'] ) ? $settings['field_mapping'] : array();

        // Instantiate portal clients
        $portals = array(
            'idealista' => new GPP_Portal_Idealista( $settings ),
            'fotocasa'  => new GPP_Portal_Fotocasa( $settings )
        );

        $results = array();

        // Gather local mapped data values
        $mapped_data = array();
        foreach ( $mappings as $portal_key => $meta_key ) {
            if ( ! empty( $meta_key ) ) {
                $mapped_data[ $portal_key ] = get_post_meta( $post_id, $meta_key, true );
            }
        }

        // If title/content/address mappings are empty, set fallbacks from post attributes
        if ( empty( $mapped_data['direccion'] ) ) {
            $mapped_data['direccion'] = $post->post_title;
        }

        // Build image IDs list (Featured Image + Gallery IDs)
        $images = array();
        
        // 1. Featured image
        $featured_id = get_post_thumbnail_id( $post_id );
        if ( $featured_id ) {
            $images[] = $featured_id;
        }

        // 2. Gallery images from metabox
        $galeria_meta = get_post_meta( $post_id, '_gpp_galeria', true );
        if ( ! empty( $galeria_meta ) ) {
            $gallery_ids = explode( ',', $galeria_meta );
            foreach ( $gallery_ids as $id ) {
                $id = intval( trim( $id ) );
                if ( $id && $id !== $featured_id ) {
                    $images[] = $id;
                }
            }
        }
        // De-duplicate images array
        $images = array_unique( $images );

        // Loop through each portal
        foreach ( $portals as $portal_slug => $portal ) {
            if ( ! $portal->is_enabled() ) {
                continue;
            }

            // Read the post-specific publication switch
            $is_portal_switched_on = get_post_meta( $post_id, '_gpp_sync_' . $portal_slug, true ) === '1';
            $remote_id             = get_post_meta( $post_id, '_gpp_remote_id_' . $portal_slug, true );

            // Execution path:
            // A. If post is published and the toggle is active => Upload or Update
            // B. If post is NOT published (draft, trash, vendido) OR toggle is inactive => Delete (if it exists on portal)
            
            $is_published = 'publish' === $post->post_status;

            if ( $is_published && $is_portal_switched_on ) {
                // Scenario A: Sync upload / update
                $results[ $portal_slug ] = $portal->send_property( $post_id, $mapped_data, $images );
            } else {
                // Scenario B: Take down from portal
                if ( ! empty( $remote_id ) ) {
                    $results[ $portal_slug ] = $portal->delete_property( $post_id, $remote_id );
                }
            }
        }

        return $results;
    }
}
