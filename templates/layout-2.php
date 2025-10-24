<?php
// SECURITY CHECK - Ensure this file is only included from the plugin class
if (!defined('ABSPATH')) {
    exit;
}
?>     
<!-- MASONRY GRID CONTAINER - Wraps all video cards in a 2-column masonry layout -->
<div class="ytpg-maso-container">
    <div class="ytpg-maso-grid">
        <?php 
        // LOOP through each video in the playlist
        foreach ($videos['items'] as $item): 
            // Extract video information from the API response
            $snippet = $item['snippet'];
            $video_id = $snippet['resourceId']['videoId'];
            $title = $snippet['title'];
            $description = $snippet['description'];
            
            // Shorten the description to avoid overly long text and UTM links
            // Uses WordPress's wp_trim_words to limit to 20 words with ellipsis
            // This prevents full links/UTM params from showing if they're at the end
            $description = wp_trim_words($description, 30, '...');
            
            // Get the best quality thumbnail available
            // Try "high" quality first, fall back to "default" if not available
            $thumbnail = isset($snippet['thumbnails']['high']['url']) ? $snippet['thumbnails']['high']['url'] : (isset($snippet['thumbnails']['default']['url']) ? $snippet['thumbnails']['default']['url']: '');
            
            // Build the YouTube watch URL
            $video_url = 'https://www.youtube.com/watch?v=' . $video_id;
            
            // Randomize line clamp for description (2-4 lines) to enhance masonry height variation
            // This adds subtle randomness per card without affecting performance
            $line_clamp = rand(4, 8);  // Random integer between 2 and 4
        ?>
        
            <!-- SINGLE VIDEO CARD -->
            <div class="ytpg-maso-video-card">
                
                <!-- THUMBNAIL SECTION -->
                <div class="ytpg-maso-thumbnail">
                    <img src="<?php echo esc_url($thumbnail); ?>" 
                         alt="<?php echo esc_attr($title); ?>" 
                         loading="lazy">
                </div>
                
                <!-- CONTENT SECTION (title, description, button) -->
                <div class="ytpg-maso-content">
                    <!-- Video Title -->
                    <h3 class="ytpg-maso-title"><?php echo esc_html($title); ?></h3>
                    
                    <!-- Video Description (only show if it exists; now shortened) -->
                    <?php if (!empty($description)): ?>
                        <p class="ytpg-maso-description" style="-webkit-line-clamp: <?php echo $line_clamp; ?>;">
                            <?php echo esc_html($description); ?>
                        </p>
                    <?php endif; ?>
                    
                    <!-- Watch Button (opens in new tab) -->
                    <a href="<?php echo esc_url($video_url); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="ytpg-maso-play-button">
                        Watch Video
                    </a>
                </div>
                
            </div>
            
        <?php endforeach; ?>
        
    </div>
</div>