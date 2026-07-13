<?php
/**
 * GPP Image Handler Class
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GPP_Image_Handler {

    private $cache_dir;
    private $cache_url;

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->cache_dir = $upload_dir['basedir'] . '/gpp-cache';
        $this->cache_url = $upload_dir['baseurl'] . '/gpp-cache';
        
        // Ensure cache directory exists
        if ( ! file_exists( $this->cache_dir ) ) {
            wp_mkdir_p( $this->cache_dir );
        }
    }

    /**
     * Get path of an optimized version of an attachment image
     *
     * @param int   $attachment_id Image attachment ID
     * @param int   $max_width     Max width allowed by the portal
     * @param int   $max_height    Max height allowed by the portal
     * @param int   $max_bytes     Max file size in bytes
     * @return string|false        Absolute file path to the optimized image or false on error
     */
    public function get_optimized_image_path( $attachment_id, $max_width = 1920, $max_height = 1080, $max_bytes = 2097152 ) {
        $file_path = get_attached_file( $attachment_id );
        
        if ( ! $file_path || ! file_exists( $file_path ) ) {
            return false;
        }

        $file_size = filesize( $file_path );
        $image_meta = wp_get_attachment_metadata( $attachment_id );
        
        $width  = isset( $image_meta['width'] ) ? intval( $image_meta['width'] ) : 0;
        $height = isset( $image_meta['height'] ) ? intval( $image_meta['height'] ) : 0;

        // If the original image is already within bounds, return original path
        if ( $file_size <= $max_bytes && $width <= $max_width && $height <= $max_height ) {
            return $file_path;
        }

        // Generate cache filename based on original file, size and target constraints
        $file_info = pathinfo( $file_path );
        $mtime = filemtime( $file_path );
        $cache_filename = sprintf(
            '%s-%s-%s-%dx%d-%d.%s',
            $file_info['filename'],
            $attachment_id,
            $mtime,
            $max_width,
            $max_height,
            $max_bytes,
            'jpg' // Convert to JPG for best compression and portal compatibility
        );

        $cached_file_path = $this->cache_dir . '/' . $cache_filename;

        // If cached file already exists, return it
        if ( file_exists( $cached_file_path ) ) {
            return $cached_file_path;
        }

        // Generate optimized version
        $editor = wp_get_image_editor( $file_path );
        
        if ( is_wp_error( $editor ) ) {
            return false; // Fail gracefully by returning original if editor fails
        }

        // Resize image if it exceeds dimensions
        if ( $width > $max_width || $height > $max_height ) {
            $editor->resize( $max_width, $max_height, false );
        }

        // Set compression quality (80% is high quality but very small)
        $editor->set_quality( 80 );
        
        // Save as JPG
        $saved = $editor->save( $cached_file_path, 'image/jpeg' );

        if ( is_wp_error( $saved ) ) {
            return false;
        }

        // Check if file size is still too large. If so, compress further
        $new_size = filesize( $cached_file_path );
        if ( $new_size > $max_bytes ) {
            $editor = wp_get_image_editor( $cached_file_path );
            if ( ! is_wp_error( $editor ) ) {
                $editor->set_quality( 60 ); // Drop quality to fit size requirements
                $editor->save( $cached_file_path, 'image/jpeg' );
            }
        }

        return $cached_file_path;
    }

    /**
     * Get URL of an optimized version of an attachment image
     * Useful for sending public image URLs to external portal APIs
     *
     * @param int   $attachment_id Image attachment ID
     * @param int   $max_width     Max width allowed by the portal
     * @param int   $max_height    Max height allowed by the portal
     * @param int   $max_bytes     Max file size in bytes
     * @return string|false        Public URL to the optimized image or false on error
     */
    public function get_optimized_image_url( $attachment_id, $max_width = 1920, $max_height = 1080, $max_bytes = 2097152 ) {
        $path = $this->get_optimized_image_path( $attachment_id, $max_width, $max_height, $max_bytes );
        
        if ( ! $path ) {
            return false;
        }

        // If the path is the original file, return the original attachment URL
        $original_file = get_attached_file( $attachment_id );
        if ( $path === $original_file ) {
            return wp_get_attachment_url( $attachment_id );
        }

        // Otherwise return the cache URL
        $filename = basename( $path );
        return $this->cache_url . '/' . $filename;
    }

    /**
     * Clear all cached images
     */
    public function clear_cache() {
        $files = glob( $this->cache_dir . '/*' );
        foreach ( $files as $file ) {
            if ( is_file( $file ) ) {
                unlink( $file );
            }
        }
    }
}
