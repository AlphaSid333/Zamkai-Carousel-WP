<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class Zamkai_YTPG_Admin {

	// This stores the name we use to save settings in the WordPress database.
	// It's like a label on a storage box where we keep all our plugin settings.

	private $option_name = 'zamkai_ytpg_settings';

	public function __construct() {
		// When WordPress builds the admin menu, add our settings page
		add_action( 'admin_menu', array( $this, 'add_admin_page' ) );

		// When WordPress initializes admin features, register our settings
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// When WordPress loads page styles, add our CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_styles' ) );

        // add_action( 'init', array( $this, 'register_block' ) );

	}

    /**
     * ADD ADMIN MENU
     * Creates a link in the WordPress admin sidebar under "Settings"
     * This is where users will configure the plugin
     */
	public function add_admin_page() {
		add_menu_page(
			'Zamkai YouTube Playlist Gallery Settings',  // Page title (shows in browser tab)
			'Zamkai YT Gallery',                // Menu title (shows in sidebar)
			'manage_options',                  // Required user permission (only admins)
			'zamkai-yt-playlist-gallery',           // Unique page identifier (slug)
			array( $this, 'settings_page' ),      // Function to display the page
			'dashicons-youtube'                // Custom dashicon YT image for menu page
		);
	}

    /**
     * SETTINGS PAGE
     * This creates the entire admin interface where users configure the plugin
     * It displays input fields for API key, playlist ID, number of videos, and custom CSS
     */

    public function settings_page() {
        // Get our current settings from the database
        $settings = get_option( $this->option_name );

        require ZAMKAI_YT_GALLERY . 'php/admin/admin-menu-1.php';
        require_once ZAMKAI_YT_GALLERY . 'php/cache-handler.php';
    }

	/**
	 * REGISTER SETTINGS
	 * Tells WordPress "these settings are safe to save to the database"
	 * Without this, WordPress won't save our settings for security reasons.
	 */
	public function register_settings() {
		register_setting(
			'zamkai_ytpg_settings_group',
			$this->option_name,
			array( $this, 'sanitize_settings' )
		);
	}

    /**
	 * SANITIZATION SETTINGS
	 * Security stuff, keeps the data transfers clean.
	 */
	public function sanitize_settings( $values ) {
		$sanitized = array();

		// Sanitize API key
		if ( isset( $values['api_key'] ) ) {
			$sanitized['api_key'] = sanitize_text_field( $values['api_key'] );
		}

		// Sanitize playlist ID
		if ( isset( $values['playlist_id'] ) ) {
			$sanitized['playlist_id'] = sanitize_text_field( $values['playlist_id'] );
		}

		// Sanitize max results (ensure it's a number between 1-50)
		if ( isset( $values['max_results'] ) ) {
			$sanitized['max_results'] = absint( $values['max_results'] );
			$sanitized['max_results'] = max( 1, min( 50, $sanitized['max_results'] ) );
		}

		// Sanitize gallery style (only allow specific values)
		if ( isset( $values['gallery_style'] ) ) {
			$sanitized['gallery_style'] = sanitize_text_field( $values['gallery_style'] );
		}

		return $sanitized;
	}

    /**
	 * ENQUEUE STYLES
	 * This loads the CSS styles that make our video gallery look good
	 * It runs on every front-end page (not admin pages)
	 */
	public function enqueue_styles() {
		
        require_once ZAMKAI_YT_GALLERY . 'php/zkyt-styles.php';

	}
}
new Zamkai_YTPG_Admin();