<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
	 * EXTRACT PLAYLIST ID
	 * Takes either a playlist ID or full YouTube URL and returns just the ID
	 * Example: Converts "https://youtube.com/playlist?list=PLxxx" to "PLxxx"
	 */
	function extract_playlist_id( $input ) {
		// If it's already just an ID (letters, numbers, underscores, hyphens)
		if ( preg_match( '/^[A-Za-z0-9_-]+$/', $input ) ) {
			return $input;
		}

		// If it's a URL, extract the ID from the "list=" parameter
		if ( preg_match( '/[?&]list=([A-Za-z0-9_-]+)/', $input, $matches ) ) {
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
	function fetch_playlist_videos( $api_key, $playlist_id, $max_results ) {
		// Clean up the playlist ID (remove URL parts if needed)
		$playlist_id = extract_playlist_id( $playlist_id );

		// Build the YouTube API URL with our parameters
		// add_query_arg safely adds parameters to a URL
		$api_url = add_query_arg(
			array(
				'part'       => 'snippet',              // We want video details (snippet)
				'playlistId' => $playlist_id,     // Which playlist to get
				'maxResults' => $max_results,     // How many videos to fetch
				'key'        => $api_key,                 // Our API key for authentication
			),
			'https://www.googleapis.com/youtube/v3/playlistItems'
		);

		// Make the HTTP request to YouTube
		// timeout: 15 means give up after 15 seconds if no response

		$response = wp_remote_get( $api_url, array( 'timeout' => 15 ) );

		// Check if the request failed (network error, etc.)
		if ( is_wp_error( $response ) ) {
			return array( 'error' => $response->get_error_message() );
		}

		// Get the response body (the actual data YouTube sent back)
		$body = wp_remote_retrieve_body( $response );

		// Convert the JSON response to a PHP array we can use
		$data = json_decode( $body, true );

		// Check if YouTube sent back an error (wrong API key, wrong playlist ID, etc.)
		if ( isset( $data['error'] ) ) {
			return array( 'error' => $data['error']['message'] );
		}

		// Everything worked! Return the video data
		return $data;
	}