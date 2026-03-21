<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function __construct(){
	add_action( 'admin_init', array( $this, 'handle_cache_clear' ) );
}

/**
 * HANDLE CACHE CLEAR
 * This function runs when someone clicks the "Clear Cache" button
 * It deletes the stored playlist data so fresh data is fetched next time
 */
function handle_cache_clear() {
    // Check if the clear cache button was clicked AND verify the security token
    if ( isset( $_POST['ytpg_clear_cache'] ) && check_admin_referer( 'ytpg_clear_cache_action', 'ytpg_clear_cache_nonce' ) ) {

        // Get our saved settings from the database
        $settings    = get_option( $this->option_name );
        $playlist_id = $settings['playlist_id'] ?? '';
        $max_results = $settings['max_results'] ?? 6;

        // Only try to clear cache if we have a playlist ID
        if ( ! empty( $playlist_id ) ) {
            // Generate the same cache key we use to store the data
            // This is like finding the right storage box to empty
            $cache_key = 'ytpg_videos_' . md5( $playlist_id . $max_results );

            // Delete the cached data from WordPress
            delete_transient( $cache_key );

            // Store success message in a transient (temporary storage)
            // This way we can display it once and it won't duplicate
            // set_transient('ytpg_cache_cleared_notice', true, 30);
            add_settings_error(
                'ytpg_messages',               // Slug (can be anything)
                'ytpg_cache_cleared',          // Unique code
                'Cache cleared successfully and playlist refreshed!', // Message
                'success'                      // Type: 'success', 'error', 'warning', 'info'
            );
        }
    }
}