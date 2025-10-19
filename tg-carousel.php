<?php
/**
 * Plugin Name: Zamkai YT Carousel
 * Description: Displays YouTube playlist videos in a customizable grid format
 * Author: Zamkai master
 * Version: 1.0
 */

// This prevents people from accessing this file directly in their browser
// It's a security measure to ensure this code only runs within WordPress
if (!defined('ABSPATH')) exit;

/**
 * MAIN PLUGIN CLASS
 * This is the container for all our plugin's functionality
 * Think of it as the "brain" of the plugin that coordinates everything
 */
class YouTube_Playlist_Grid {
    
    // This stores the name we use to save settings in the WordPress database
    // It's like a label on a storage box where we keep all our plugin settings
    private $option_name = 'ytpg_settings';
    
    /**
     * CONSTRUCTOR - This runs automatically when the plugin loads
     * It "hooks" our functions into WordPress so they run at the right times
     * Think of hooks as saying "Hey WordPress, when you do X, also run my function Y"
     */
    public function __construct() {
        // When WordPress builds the admin menu, add our settings page
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // When WordPress initializes admin features, register our settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // When WordPress initializes admin features, also check if user clicked "Clear Cache"
        add_action('admin_init', array($this, 'handle_cache_clear'));
        
        // Register our shortcode [youtube_playlist_grid] so it displays videos
        add_shortcode('youtube_playlist_grid', array($this, 'render_grid'));
        
        // When WordPress loads page styles, add our CSS
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    /**
     * ADD ADMIN MENU
     * Creates a link in the WordPress admin sidebar under "Settings"
     * This is where users will configure the plugin
     */
    public function add_admin_menu() {
        add_options_page(
            'YouTube Playlist Grid Settings',  // Page title (shows in browser tab)
            'YT Playlist Grid',                // Menu title (shows in sidebar)
            'manage_options',                  // Required user permission (only admins)
            'youtube-playlist-grid',           // Unique page identifier (slug)
            array($this, 'settings_page')      // Function to display the page
        );
    }
    
    /**
     * REGISTER SETTINGS
     * Tells WordPress "these settings are safe to save to the database"
     * Without this, WordPress won't save our settings for security reasons
     */
    public function register_settings() {
        register_setting('ytpg_settings_group', $this->option_name);
    }
    
    /**
     * HANDLE CACHE CLEAR
     * This function runs when someone clicks the "Clear Cache" button
     * It deletes the stored playlist data so fresh data is fetched next time
     */
    public function handle_cache_clear() {
        // Check if the clear cache button was clicked AND verify the security token
        if (isset($_POST['ytpg_clear_cache']) && check_admin_referer('ytpg_clear_cache_action', 'ytpg_clear_cache_nonce')) {
            
            // Get our saved settings from the database
            $settings = get_option($this->option_name);
            $playlist_id = $settings['playlist_id'] ?? '';
            $max_results = $settings['max_results'] ?? 6;
            
            // Only try to clear cache if we have a playlist ID
            if (!empty($playlist_id)) {
                // Generate the same cache key we use to store the data
                // This is like finding the right storage box to empty
                $cache_key = 'ytpg_videos_' . md5($playlist_id . $max_results);
                
                // Delete the cached data from WordPress
                delete_transient($cache_key);
                
                // Show a success message to the user
                add_settings_error(
                    'ytpg_messages',
                    'ytpg_cache_cleared',
                    'Cache cleared successfully! The playlist will refresh on the next page load.',
                    'success'
                );
            }
        }
    }
    
    /**
     * SETTINGS PAGE
     * This creates the entire admin interface where users configure the plugin
     * It displays input fields for API key, playlist ID, number of videos, and custom CSS
     */
    public function settings_page() {
        // Get our current settings from the database
        $settings = get_option($this->option_name);
        ?>
        <div class="wrap">
            <h1>YouTube Playlist Grid Settings</h1>
            
            <?php 
            // Display any success or error messages (like "Cache cleared!")
            settings_errors('ytpg_messages'); 
            ?>
            
            <!-- MAIN SETTINGS FORM -->
            <!-- This form saves to WordPress using options.php -->
            <form method="post" action="options.php">
                <?php 
                // Add hidden security fields that WordPress requires
                settings_fields('ytpg_settings_group'); 
                ?>
                
                <table class="form-table">
                    <!-- API KEY FIELD -->
                    <tr>
                        <th scope="row">
                            <label for="api_key">YouTube API Key</label>
                        </th>
                        <td>
                            <!-- Text input for the YouTube API key -->
                            <input type="text" id="api_key" name="<?php echo $this->option_name; ?>[api_key]" 
                                   value="<?php echo esc_attr($settings['api_key'] ?? ''); ?>" 
                                   class="regular-text" />
                            <p class="description">Get your API key from <a href="https://console.developers.google.com/" target="_blank">Google Developers Console</a></p>
                        </td>
                    </tr>
                    
                    <!-- PLAYLIST ID FIELD -->
                    <tr>
                        <th scope="row">
                            <label for="playlist_id">Playlist ID or URL</label>
                        </th>
                        <td>
                            <!-- Text input for the playlist ID or full URL -->
                            <input type="text" id="playlist_id" name="<?php echo $this->option_name; ?>[playlist_id]" 
                                   value="<?php echo esc_attr($settings['playlist_id'] ?? ''); ?>" 
                                   class="regular-text" />
                            <p class="description">Enter the playlist ID (e.g., PLxxxxxxxxxxx) or full YouTube playlist URL</p>
                        </td>
                    </tr>
                    
                    <!-- NUMBER OF VIDEOS FIELD -->
                    <tr>
                        <th scope="row">
                            <label for="max_results">Number of Videos</label>
                        </th>
                        <td>
                            <!-- Number input limited between 1 and 50 -->
                            <input type="number" id="max_results" name="<?php echo $this->option_name; ?>[max_results]" 
                                   value="<?php echo esc_attr($settings['max_results'] ?? 6); ?>" 
                                   min="1" max="50" />
                            <p class="description">Number of videos to display (1-50)</p>
                        </td>
                    </tr>
                    
                    <!-- CUSTOM CSS FIELD -->
                    <tr>
                        <th scope="row">
                            <label for="custom_css">Custom CSS</label>
                        </th>
                        <td>
                            <!-- Large text area for custom CSS code -->
                            <textarea id="custom_css" name="<?php echo $this->option_name; ?>[custom_css]" 
                                      rows="10" class="large-text code"><?php echo esc_textarea($settings['custom_css'] ?? ''); ?></textarea>
                            <p class="description">Add your custom CSS styles here</p>
                        </td>
                    </tr>
                </table>
                
                <?php 
                // Display the "Save Changes" button
                submit_button(); 
                ?>
            </form>
            
            <hr>
            
            <!-- CACHE MANAGEMENT SECTION -->
            <h2>Cache Management</h2>
            <p>The playlist is cached for 1 hour to improve performance and reduce API usage. Use the button below to manually refresh the cache after uploading new videos.</p>
            
            <!-- CLEAR CACHE FORM -->
            <!-- This is a separate form that only clears the cache -->
            <form method="post" action="">
                <?php 
                // Add a security token to prevent unauthorized cache clearing
                wp_nonce_field('ytpg_clear_cache_action', 'ytpg_clear_cache_nonce'); 
                ?>
                <input type="submit" name="ytpg_clear_cache" class="button button-secondary" value="Clear Cache & Refresh Playlist" />
            </form>
            
            <hr>
            
            <!-- USAGE INSTRUCTIONS -->
            <h2>Usage</h2>
            <p>Use the shortcode <code>[youtube_playlist_grid]</code> in any page or post to display your playlist grid.</p>
        </div>
        <?php
    }
    
    /**
     * ENQUEUE STYLES
     * This loads the CSS styles that make our video grid look good
     * It runs on every front-end page (not admin pages)
     */
    public function enqueue_styles() {
        // Get our settings to access custom CSS
        $settings = get_option($this->option_name);
        
        // Register a "virtual" stylesheet (it doesn't exist as a file)
        // We'll add our CSS code directly instead
        wp_register_style('ytpg-default', false);
        wp_enqueue_style('ytpg-default');
        
        // DEFAULT CSS - Makes the video grid look nice
        // This is the base styling that always applies
        $default_css = "
            /* CONTAINER - Wraps everything and centers it on the page */
            .ytpg-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }
            
            /* GRID - Creates the responsive grid layout for video cards */
            .ytpg-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 30px;
                margin-top: 20px;
            }
            
            /* VIDEO CARD - The box containing each video */
            .ytpg-video-card {
                background: #fff;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }
            
            /* HOVER EFFECT - Card lifts up when you hover over it */
            .ytpg-video-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 4px 16px rgba(0,0,0,0.2);
            }
            
            /* THUMBNAIL - Container for the video thumbnail image */
            .ytpg-thumbnail {
                position: relative;
                width: 100%;
                padding-top: 56.25%; /* Creates 16:9 aspect ratio */
                overflow: hidden;
                background: #000;
            }
            
            /* THUMBNAIL IMAGE - The actual video preview image */
            .ytpg-thumbnail img {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                object-fit: cover; /* Makes image fill space nicely */
            }
            
            /* CONTENT - The text area below the thumbnail */
            .ytpg-content {
                padding: 15px;
            }
            
            /* TITLE - The video title */
            .ytpg-title {
                font-size: 16px;
                font-weight: 600;
                margin: 0 0 10px 0;
                color: #333;
                line-height: 1.4;
            }
            
            /* DESCRIPTION - The video description text */
            .ytpg-description {
                font-size: 14px;
                color: #666;
                line-height: 1.6;
                margin: 0 0 15px 0;
                display: -webkit-box;
                -webkit-line-clamp: 3; /* Limits to 3 lines */
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            
            /* PLAY BUTTON - The red button that links to YouTube */
            .ytpg-play-button {
                display: inline-block;
                background: #ff0000;
                color: #fff;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 4px;
                font-weight: 600;
                transition: background 0.3s ease;
            }
            
            /* PLAY BUTTON HOVER - Darker red when hovering */
            .ytpg-play-button:hover {
                background: #cc0000;
                color: #fff;
            }
            
            /* PLAY ICON - Adds the play triangle before button text */
            .ytpg-play-button::before {
                content: '▶ ';
                margin-right: 5px;
            }
            
            /* ERROR MESSAGE - Styling for error messages */
            .ytpg-error {
                background: #ffebee;
                border-left: 4px solid #f44336;
                padding: 15px;
                margin: 20px 0;
                color: #c62828;
            }
        ";
        
        // Add our default CSS to the page
        wp_add_inline_style('ytpg-default', $default_css);
        
        // If user added custom CSS in settings, add that too
        // This allows them to override our default styles
        if (!empty($settings['custom_css'])) {
            wp_add_inline_style('ytpg-default', $settings['custom_css']);
        }
    }
    
    /**
     * EXTRACT PLAYLIST ID
     * Takes either a playlist ID or full YouTube URL and returns just the ID
     * Example: Converts "https://youtube.com/playlist?list=PLxxx" to "PLxxx"
     */
    private function extract_playlist_id($input) {
        // If it's already just an ID (letters, numbers, underscores, hyphens)
        if (preg_match('/^[A-Za-z0-9_-]+$/', $input)) {
            return $input;
        }
        
        // If it's a URL, extract the ID from the "list=" parameter
        if (preg_match('/[?&]list=([A-Za-z0-9_-]+)/', $input, $matches)) {
            return $matches[1];
        }
        
        // If we can't figure it out, just return what they gave us
        return $input;
    }
    
    /**
     * FETCH PLAYLIST VIDEOS
     * Contacts YouTube's API and gets the list of videos from the playlist
     * This is where we actually talk to YouTube to get video information
     */
    private function fetch_playlist_videos($api_key, $playlist_id, $max_results) {
        // Clean up the playlist ID (remove URL parts if needed)
        $playlist_id = $this->extract_playlist_id($playlist_id);
        
        // Build the YouTube API URL with our parameters
        // add_query_arg safely adds parameters to a URL
        $api_url = add_query_arg(array(
            'part' => 'snippet',              // We want video details (snippet)
            'playlistId' => $playlist_id,     // Which playlist to get
            'maxResults' => $max_results,     // How many videos to fetch
            'key' => $api_key                 // Our API key for authentication
        ), 'https://www.googleapis.com/youtube/v3/playlistItems');
        
        // Make the HTTP request to YouTube
        // timeout: 15 means give up after 15 seconds if no response
        $response = wp_remote_get($api_url, array('timeout' => 15));
        
        // Check if the request failed (network error, etc.)
        if (is_wp_error($response)) {
            return array('error' => $response->get_error_message());
        }
        
        // Get the response body (the actual data YouTube sent back)
        $body = wp_remote_retrieve_body($response);
        
        // Convert the JSON response to a PHP array we can use
        $data = json_decode($body, true);
        
        // Check if YouTube sent back an error (wrong API key, wrong playlist ID, etc.)
        if (isset($data['error'])) {
            return array('error' => $data['error']['message']);
        }
        
        // Everything worked! Return the video data
        return $data;
    }
    
    /**
     * RENDER GRID
     * This is the main function that displays the video grid on the front-end
     * It's called when someone uses the [youtube_playlist_grid] shortcode
     */
    public function render_grid($atts) {
        // Get our saved settings from the database
        $settings = get_option($this->option_name);
        
        // Extract the settings we need (use defaults if not set)
        $api_key = $settings['api_key'] ?? '';
        $playlist_id = $settings['playlist_id'] ?? '';
        $max_results = $settings['max_results'] ?? 6;
        
        // If settings aren't configured, show an error message
        if (empty($api_key) || empty($playlist_id)) {
            return '<div class="ytpg-error">Please configure the YouTube API key and Playlist ID in the plugin settings.</div>';
        }
        
        // Create a unique cache key based on playlist ID and number of videos
        // This is like a label for our stored data
        $cache_key = 'ytpg_videos_' . md5($playlist_id . $max_results);
        
        // Try to get cached videos from WordPress storage
        // This avoids hitting YouTube's API every time
        $videos = get_transient($cache_key);
        
        // If no cached data exists (or cache expired)
        if (false === $videos) {
            // Fetch fresh data from YouTube
            $videos = $this->fetch_playlist_videos($api_key, $playlist_id, $max_results);
            
            // If we got valid data (no errors), cache it for 1 hour
            if (!isset($videos['error'])) {
                set_transient($cache_key, $videos, HOUR_IN_SECONDS);
            }
        }
        
        // If there was an error fetching videos, show error message
        if (isset($videos['error'])) {
            return '<div class="ytpg-error">Error fetching videos: ' . esc_html($videos['error']) . '</div>';
        }
        
        // If playlist is empty, show a message
        if (empty($videos['items'])) {
            return '<div class="ytpg-error">No videos found in this playlist.</div>';
        }
        
        // Start output buffering - we'll collect HTML and return it all at once
        ob_start();
        ?>
        
        <!-- GRID CONTAINER - Wraps all video cards -->
        <div class="ytpg-container">
            <div class="ytpg-grid">
                
                <?php 
                // LOOP through each video in the playlist
                foreach ($videos['items'] as $item): 
                    // Extract video information from the API response
                    $snippet = $item['snippet'];
                    $video_id = $snippet['resourceId']['videoId'];
                    $title = $snippet['title'];
                    $description = $snippet['description'];
                    
                    // Get the best quality thumbnail available
                    // Try "high" quality first, fall back to "default" if not available
                    $thumbnail = $snippet['thumbnails']['high']['url'] ?? $snippet['thumbnails']['default']['url'];
                    
                    // Build the YouTube watch URL
                    $video_url = 'https://www.youtube.com/watch?v=' . $video_id;
                ?>
                
                    <!-- SINGLE VIDEO CARD -->
                    <div class="ytpg-video-card">
                        
                        <!-- THUMBNAIL SECTION -->
                        <div class="ytpg-thumbnail">
                            <img src="<?php echo esc_url($thumbnail); ?>" 
                                 alt="<?php echo esc_attr($title); ?>" 
                                 loading="lazy">
                        </div>
                        
                        <!-- CONTENT SECTION (title, description, button) -->
                        <div class="ytpg-content">
                            <!-- Video Title -->
                            <h3 class="ytpg-title"><?php echo esc_html($title); ?></h3>
                            
                            <!-- Video Description (only show if it exists) -->
                            <?php if (!empty($description)): ?>
                                <p class="ytpg-description"><?php echo esc_html($description); ?></p>
                            <?php endif; ?>
                            
                            <!-- Watch Button (opens in new tab) -->
                            <a href="<?php echo esc_url($video_url); ?>" 
                               target="_blank" 
                               rel="noopener noreferrer" 
                               class="ytpg-play-button">
                                Watch Video
                            </a>
                        </div>
                        
                    </div>
                    
                <?php endforeach; ?>
                
            </div>
        </div>
        
        <?php
        // Get all the HTML we collected and return it
        return ob_get_clean();
    }
}

// CREATE AN INSTANCE OF OUR PLUGIN CLASS
// This actually starts the plugin running
new YouTube_Playlist_Grid();