<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = get_option( $this->option_name );

		// Get the gallery style setting (default to 'simple' if not set)
		$gallery_style = $settings['gallery_style'] ?? 'simple';

		if ( $gallery_style === 'modern' ) {
			wp_register_style(
				'zamkai-ytpg-default',                          // Handle (unique identifier)
				ZAMKAI_YT_GALLERY_URL.'css/modern-yt-cards.css' , // URL to the CSS file
				array(),                                 // Dependencies (add if needed, e.g., array('wp-block-library'))
				'1.0.0',                                 // Version (update for cache busting)
				'all'                                    // Media type
			);
		} else {
			wp_register_style(
				'zamkai-ytpg-default',                          // Handle (unique identifier)
				ZAMKAI_YT_GALLERY_URL.'/css/yt-cards.css', // URL to the CSS file
				array(),                                 // Dependencies (add if needed, e.g., array('wp-block-library'))
				'1.0.0',                                 // Version (update for cache busting)
				'all'                                    // Media type
			);
		}
		wp_enqueue_style( 'zamkai-ytpg-default' );