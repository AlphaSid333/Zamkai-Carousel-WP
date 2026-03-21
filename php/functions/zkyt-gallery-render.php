<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

class Zkyt_Frontend{

    private $option_name = 'zamkai_ytpg_settings';

    function __construct(){

        add_shortcode( 'zamkai_yt_gallery', array( $this, 'render_gallery' ) );

        require_once ZAMKAI_YT_GALLERY . 'php/functions/youtube-fetch.php';
        
    }

    /**
     * RENDER GALLERY
     * This is the main function that displays the video gallery on the front-end
     * It's called when someone uses the [zamkai_yt_gallery] shortcode
     */
    function render_gallery( $atts ) {

        // Get our saved settings from the database
        $settings = get_option( $this->option_name );

        // Extract the settings we need (use defaults if not set)
        $api_key     = $settings['api_key'] ?? '';
        $playlist_id = $settings['playlist_id'] ?? '';
        $max_results = $settings['max_results'] ?? 6;

        // If settings aren't configured, show an error message
        if ( empty( $api_key ) || empty( $playlist_id ) ) {
            return '<div class="zamkai_ytpg-error">Please configure the YouTube API key and Playlist ID in the plugin settings.</div>';
        }

        // Create a unique cache key based on playlist ID and number of videos
        // This is like a label for our stored data
        $cache_key = 'zamkai_ytpg_videos_' . md5( $playlist_id . $max_results );

        // Try to get cached videos from WordPress storage
        // This avoids hitting YouTube's API every time
        $videos = get_transient( $cache_key );

        // If no cached data exists (or cache expired)
        if ( false === $videos ) {
            // Fetch fresh data from YouTube
            $videos = fetch_playlist_videos( $api_key, $playlist_id, $max_results );

            // If we got valid data (no errors), cache it for 1 hour
            if ( ! isset( $videos['error'] ) ) {
                set_transient( $cache_key, $videos, HOUR_IN_SECONDS );
            }
        }

        // If there was an error fetching videos, show error message
        if ( isset( $videos['error'] ) ) {
            return '<div class="zamkai-ytpg-error">Error fetching videos: ' . esc_html( $videos['error'] ) . '</div>';
        }

        // If playlist is empty, show a message
        if ( empty( $videos['items'] ) ) {
            return '<div class="zamkai-ytpg-error">No videos found in this playlist.</div>';
        }

        // Start output buffering - we'll collect HTML and return it all at once
        ob_start();

        // Get the gallery style setting (default to 'simple' if not set)
        $gallery_style = $settings['gallery_style'] ?? 'simple';

        // Determine the template path based on the style
            $template_path = '';
        if ( $gallery_style === 'simple' ) {
            $template_path = ZAMKAI_YT_GALLERY . '/templates/layout-1.php';
        } elseif ( $gallery_style === 'modern' ) {
            $template_path = ZAMKAI_YT_GALLERY . '/templates/layout-2.php';
        } else {
            // Default fallback to 'simple' if invalid value
            $template_path = ZAMKAI_YT_GALLERY . '/templates/layout-1.php';
        }

            // Check if the template file exists, then include it
        if ( file_exists( $template_path ) ) {
            // Start output buffering - we'll collect HTML and return it all at once
            include $template_path;

            // Get all the HTML we collected and return it
        } else {
            // Fallback error if template is missing
            return '<div class="zamkai-ytpg-error">Template file not found for style: ' . esc_html( $gallery_style ) . '</div>';
        }

        // Get all the HTML we collected and return it
        return ob_get_clean();
    }
}
new Zkyt_Frontend();