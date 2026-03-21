<?php
/** SECURITY CHECK - Ensure this file is only included from the plugin class.**/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YTPG_Admin_Menu {

	private $option_name = 'ytpg_settings';

	public function __construct() {

		// When WordPress builds the admin menu, add our settings page
		add_action( 'admin_menu', array( $this, 'add_admin_page' ) );

		// When WordPress initializes admin features, register our settings
		add_action( 'admin_init', array( $this, 'register_settings' ) );
        
        // When WordPress loads page styles, add our CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );

        // Necessary for block editor styling
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_styles' ) );

	}

    /**
     * ADD ADMIN MENU
     * Creates a link in the WordPress admin sidebar under "Settings"
     * This is where users will configure the plugin
     */

	public function add_admin_page() {
		add_menu_page(
			'YouTube Playlist Grid Settings',  // Page title (shows in browser tab)
			'YT Playlist Grid',                // Menu title (shows in sidebar)
			'manage_options',                  // Required user permission (only admins)
			'youtube-playlist-grid',           // Unique page identifier (slug)
			array( $this, 'settings_page' ),      // Function to display the page
			'dashicons-youtube'                // Custom dashicon YT image for menu page
		);

	}
    function settings_page(){

        // Get our current settings from the database
        $settings = get_option( $this->option_name );

        require ZAMKAI_YT_GALLERY . 'php/admin-menu-1.php';
        require_once ZAMKAI_YT_GALLERY . 'php/cache-handler.php';
    }
    

	/**
	 * REGISTER SETTINGS
	 * Tells WordPress "these settings are safe to save to the database"
	 * Without this, WordPress won't save our settings for security reasons.
	 */

	public function register_settings() {
		register_setting(
			'ytpg_settings_group',
			$this->option_name,
			array( $this, 'sanitize_settings' )
		);
	}

	public function sanitize_settings( $values ) {
		// Always return the sanitized array (or scalar)
		$sanitized = array();

		// Example: if your option is an array of values.

		foreach ( (array) $values as $key => $value ) {
			if ( is_string( $value ) ) {
				$sanitized[ $key ] = sanitize_text_field( $value );
			} elseif ( is_array( $value ) ) {
				$sanitized[ $key ] = array_map( 'sanitize_text_field', $value );
			} elseif ( is_int( $value ) ) {
				$sanitized[ $key ] = (int) $value;
			} // ... handle other types: urls, emails, etc.
			else {
				$sanitized[ $key ] = $value; // fallback
			}
		}

		return $sanitized;
	}

    /**
	 * ENQUEUE STYLES
	 * This loads the CSS styles that make our video grid look good
	 * It runs on every front-end page (not admin pages)
	 */

	public function enqueue_styles() {
		// Get our settings to access custom CSS
		$settings = get_option( $this->option_name );

		// Get the gallery style setting (default to 'simple' if not set)
		$gallery_style = $settings['gallery_style'] ?? 'simple';

		if ( $gallery_style === 'modern' ) {
			wp_register_style(
				'ytpg-default',                          // Handle (unique identifier)
				plugins_url( 'css/modern-yt-cards.css', __FILE__ ), // URL to the CSS file
				array(),                                 // Dependencies (add if needed, e.g., array('wp-block-library'))
				'1.0.0',                                 // Version (update for cache busting)
				'all'                                    // Media type
			);
		} else {
			wp_register_style(
				'ytpg-default',                          // Handle (unique identifier)
				plugins_url( 'css/yt-cards.css', __FILE__ ), // URL to the CSS file
				array(),                                 // Dependencies (add if needed, e.g., array('wp-block-library'))
				'1.0.0',                                 // Version (update for cache busting)
				'all'                                    // Media type
			);
		}
		wp_enqueue_style( 'ytpg-default' );
		// Enqueue the external CSS file (replace __FILE__ with $this->plugin_file if needed)

		// If user added custom CSS in settings, add that too
		// This allows them to override our default styles (loads after the file)

		if ( ! empty( $settings['custom_css'] ) ) {
			wp_add_inline_style( 'ytpg-default', $settings['custom_css'] );
		}
	}
	
}
new YTPG_Admin_Menu();
