<?php
/**
 * Plugin Name: Zamkai YT Carousel
 * Description: Displays YouTube playlist videos in a customizable grid format
 * Author: Zamkai Master
 * Text Domain: zamkai-yt-carousel
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;

class YouTube_Playlist_Grid {
    
    private $option_name = 'ytpg_settings';
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('youtube_playlist_grid', array($this, 'render_grid'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    public function add_admin_menu() {
        add_options_page(
            'YouTube Playlist Grid Settings',
            'YT Playlist Grid',
            'manage_options',
            'youtube-playlist-grid',
            array($this, 'settings_page')
        );
    }
    
    public function register_settings() {
        register_setting('ytpg_settings_group', $this->option_name);
    }
    
    public function settings_page() {
        $settings = get_option($this->option_name);
        ?>
        <div class="wrap">
            <h1>YouTube Playlist Grid Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('ytpg_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="api_key">YouTube API Key</label>
                        </th>
                        <td>
                            <input type="text" id="api_key" name="<?php echo $this->option_name; ?>[api_key]" 
                                   value="<?php echo esc_attr($settings['api_key'] ?? ''); ?>" 
                                   class="regular-text" />
                            <p class="description">Get your API key from <a href="https://console.developers.google.com/" target="_blank">Google Developers Console</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="playlist_id">Playlist ID or URL</label>
                        </th>
                        <td>
                            <input type="text" id="playlist_id" name="<?php echo $this->option_name; ?>[playlist_id]" 
                                   value="<?php echo esc_attr($settings['playlist_id'] ?? ''); ?>" 
                                   class="regular-text" />
                            <p class="description">Enter the playlist ID (e.g., PLxxxxxxxxxxx) or full YouTube playlist URL</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="max_results">Number of Videos</label>
                        </th>
                        <td>
                            <input type="number" id="max_results" name="<?php echo $this->option_name; ?>[max_results]" 
                                   value="<?php echo esc_attr($settings['max_results'] ?? 6); ?>" 
                                   min="1" max="50" />
                            <p class="description">Number of videos to display (1-50)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="custom_css">Custom CSS</label>
                        </th>
                        <td>
                            <textarea id="custom_css" name="<?php echo $this->option_name; ?>[custom_css]" 
                                      rows="10" class="large-text code"><?php echo esc_textarea($settings['custom_css'] ?? ''); ?></textarea>
                            <p class="description">Add your custom CSS styles here</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            
            <hr>
            <h2>Usage</h2>
            <p>Use the shortcode <code>[youtube_playlist_grid]</code> in any page or post to display your playlist grid.</p>
        </div>
        <?php
    }
    
    public function enqueue_styles() {
        $settings = get_option($this->option_name);
        
        // Default CSS
        wp_register_style('ytpg-default', false);
        wp_enqueue_style('ytpg-default');
        
        $default_css = "
            .ytpg-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }
            .ytpg-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 30px;
                margin-top: 20px;
            }
            .ytpg-video-card {
                background: #fff;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }
            .ytpg-video-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 4px 16px rgba(0,0,0,0.2);
            }
            .ytpg-thumbnail {
                position: relative;
                width: 100%;
                padding-top: 56.25%;
                overflow: hidden;
                background: #000;
            }
            .ytpg-thumbnail img {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .ytpg-content {
                padding: 15px;
            }
            .ytpg-title {
                font-size: 16px;
                font-weight: 600;
                margin: 0 0 10px 0;
                color: #333;
                line-height: 1.4;
            }
            .ytpg-description {
                font-size: 14px;
                color: #666;
                line-height: 1.6;
                margin: 0 0 15px 0;
                display: -webkit-box;
                -webkit-line-clamp: 3;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
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
            .ytpg-play-button:hover {
                background: #cc0000;
                color: #fff;
            }
            .ytpg-play-button::before {
                content: '▶ ';
                margin-right: 5px;
            }
            .ytpg-error {
                background: #ffebee;
                border-left: 4px solid #f44336;
                padding: 15px;
                margin: 20px 0;
                color: #c62828;
            }
        ";
        
        wp_add_inline_style('ytpg-default', $default_css);
        
        // Custom CSS
        if (!empty($settings['custom_css'])) {
            wp_add_inline_style('ytpg-default', $settings['custom_css']);
        }
    }
    
    private function extract_playlist_id($input) {
        // If it's already just an ID
        if (preg_match('/^[A-Za-z0-9_-]+$/', $input)) {
            return $input;
        }
        
        // Extract from URL
        if (preg_match('/[?&]list=([A-Za-z0-9_-]+)/', $input, $matches)) {
            return $matches[1];
        }
        
        return $input;
    }
    
    private function fetch_playlist_videos($api_key, $playlist_id, $max_results) {
        $playlist_id = $this->extract_playlist_id($playlist_id);
        
        $api_url = add_query_arg(array(
            'part' => 'snippet',
            'playlistId' => $playlist_id,
            'maxResults' => $max_results,
            'key' => $api_key
        ), 'https://www.googleapis.com/youtube/v3/playlistItems');
        
        $response = wp_remote_get($api_url, array('timeout' => 15));
        
        if (is_wp_error($response)) {
            return array('error' => $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            return array('error' => $data['error']['message']);
        }
        
        return $data;
    }
    
    public function render_grid($atts) {
        $settings = get_option($this->option_name);
        
        $api_key = $settings['api_key'] ?? '';
        $playlist_id = $settings['playlist_id'] ?? '';
        $max_results = $settings['max_results'] ?? 6;
        
        if (empty($api_key) || empty($playlist_id)) {
            return '<div class="ytpg-error">Please configure the YouTube API key and Playlist ID in the plugin settings.</div>';
        }
        
        $cache_key = 'ytpg_videos_' . md5($playlist_id . $max_results);
        $videos = get_transient($cache_key);
        
        if (false === $videos) {
            $videos = $this->fetch_playlist_videos($api_key, $playlist_id, $max_results);
            
            if (!isset($videos['error'])) {
                set_transient($cache_key, $videos, HOUR_IN_SECONDS);
            }
        }
        
        if (isset($videos['error'])) {
            return '<div class="ytpg-error">Error fetching videos: ' . esc_html($videos['error']) . '</div>';
        }
        
        if (empty($videos['items'])) {
            return '<div class="ytpg-error">No videos found in this playlist.</div>';
        }
        
        ob_start();
        ?>
        <div class="ytpg-container">
            <div class="ytpg-grid">
                <?php foreach ($videos['items'] as $item): 
                    $snippet = $item['snippet'];
                    $video_id = $snippet['resourceId']['videoId'];
                    $title = $snippet['title'];
                    $description = $snippet['description'];
                    $thumbnail = $snippet['thumbnails']['high']['url'] ?? $snippet['thumbnails']['default']['url'];
                    $video_url = 'https://www.youtube.com/watch?v=' . $video_id;
                ?>
                    <div class="ytpg-video-card">
                        <div class="ytpg-thumbnail">
                            <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
                        </div>
                        <div class="ytpg-content">
                            <h3 class="ytpg-title"><?php echo esc_html($title); ?></h3>
                            <?php if (!empty($description)): ?>
                                <p class="ytpg-description"><?php echo esc_html($description); ?></p>
                            <?php endif; ?>
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
        return ob_get_clean();
    }
}

new YouTube_Playlist_Grid();