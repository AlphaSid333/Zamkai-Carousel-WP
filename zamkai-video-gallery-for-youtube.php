<?php
/**
 * Plugin Name: Zamkai Video Gallery for YouTube Playlists
 * Description: Displays YouTube playlist videos in a customizable gallery format
 * Author: TechGrill
 * Version: 1.0
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define('ZAMKAI_YT_GALLERY', plugin_dir_path(__FILE__));
define('ZAMKAI_YT_GALLERY_URL', plugin_dir_url(__FILE__));

/**
 * MAIN PLUGIN CLASS
 * This is the container for all our plugin's functionality
 * Think of it as the "brain" of the plugin that coordinates everything
 */

class Zamkai_YTPG_Main {

	private $option_name = 'zamkai_ytpg_settings';

	function __construct() {

		// Include the admin class
		require_once ZAMKAI_YT_GALLERY . 'php/admin/zkyt-admin.php';

		require_once ZAMKAI_YT_GALLERY . 'php/functions/zkyt-gallery-render.php';

		add_action( 'init', array( $this, 'register_block' ) );

	}

	public function register_block() {
		// Only register if build exists
		if ( file_exists( ZAMKAI_YT_GALLERY . 'build/index.js' ) ) {
			register_block_type( ZAMKAI_YT_GALLERY . 'build' );
		}
	}
}


// CREATE AN INSTANCE OF OUR PLUGIN CLASS
// This actually starts the plugin running
new Zamkai_YTPG_Main();
