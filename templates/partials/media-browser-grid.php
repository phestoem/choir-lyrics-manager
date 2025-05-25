<?php
/**
 * Media Browser Grid View Template for Choir Lyrics Manager
 * To be included in archive-lyric.php when media view is active
 */
?>
<div class="clm-media-browser-grid">
    <?php if (have_posts()) : while (have_posts()) : the_post(); 
        // Get media attachment data
        $lyric_id = get_the_ID();
        $audio_file_id = get_post_meta($lyric_id, '_clm_audio_file_id', true);
        $video_embed = get_post_meta($lyric_id, '_clm_video_embed', true);
        $sheet_music_id = get_post_meta($lyric_id, '_clm_sheet_music_id', true);
        $midi_file_id = get_post_meta($lyric_id, '_clm_midi_file_id', true);
        $practice_tracks = get_post_meta($lyric_id, '_clm_practice_tracks', true);
        
        // Determine what media types are available
        $has_audio = !empty($audio_file_id) || (!empty($practice_tracks) && is_array($practice_tracks));
        $has_video = !empty($video_embed);
        $has_sheet = !empty($sheet_music_id);
        $has_midi = !empty($midi_file_id);
        
        // Skip if no media
        if (!$has_audio && !$has_video && !$has_sheet && !$has_midi) {
            continue;
        }
        
        // Determine the primary media type for card styling
        $primary_type = 'default';
        $primary_icon = 'dashicons-format-audio';
        
        if ($has_video) {
            $primary_type = 'video';
            $primary_icon = 'dashicons-format-video';
        } elseif ($has_audio) {
            $primary_type = 'audio';
            $primary_icon = 'dashicons-format-audio';
        } elseif ($has_sheet) {
            $primary_type = 'sheet';
            $primary_icon = 'dashicons-media-document';
        } elseif ($has_midi) {
            $primary_type = 'midi';
            $primary_icon = 'dashicons-playlist-audio';
        }
        
        // Get featured image or placeholder
        $thumb_url = get_the_post_thumbnail_url($lyric_id, 'medium');
        if (!$thumb_url) {
            $placeholders = array(
                'video' => 'video-placeholder.png',
                'audio' => 'audio-placeholder.png',
                'sheet' => 'sheet-placeholder.png',
                'midi' => 'midi-placeholder.png',
                'default' => 'lyric-placeholder.png'
            );
			 // Check if SVG file exists, fall back to default if not
			$placeholder_file = 'assets/images/' . $placeholders[$primary_type];
			if (!file_exists(CLM_PLUGIN_DIR . $placeholder_file)) {
				$placeholder_file = 'assets/images/lyric-placeholder.png';
			}
            $thumb_url = CLM_PLUGIN_URL . 'assets/images/' . $placeholders[$primary_type];
        }
    ?>
    <div class="clm-media-card clm-media-type-<?php echo esc_attr($primary_type); ?>">
        <div class="clm-media-card-inner">
            <div class="clm-media-thumbnail" style="background-image: url('<?php echo esc_url($thumb_url); ?>');">
                <div class="clm-media-overlay">
                    <span class="dashicons <?php echo esc_attr($primary_icon); ?>"></span>
                </div>
                
                <div class="clm-media-badges">
                    <?php if ($has_audio): ?>
                    <span class="clm-media-badge audio-badge" title="<?php esc_attr_e('Audio Available', 'choir-lyrics-manager'); ?>">
                        <span class="dashicons dashicons-format-audio"></span>
                    </span>
                    <?php endif; ?>
                    
                    <?php if ($has_video): ?>
                    <span class="clm-media-badge video-badge" title="<?php esc_attr_e('Video Available', 'choir-lyrics-manager'); ?>">
                        <span class="dashicons dashicons-format-video"></span>
                    </span>
                    <?php endif; ?>
                    
                    <?php if ($has_sheet): ?>
                    <span class="clm-media-badge sheet-badge" title="<?php esc_attr_e('Sheet Music Available', 'choir-lyrics-manager'); ?>">
                        <span class="dashicons dashicons-media-document"></span>
                    </span>
                    <?php endif; ?>
                    
                    <?php if ($has_midi): ?>
                    <span class="clm-media-badge midi-badge" title="<?php esc_attr_e('MIDI File Available', 'choir-lyrics-manager'); ?>">
                        <span class="dashicons dashicons-playlist-audio"></span>
                    </span>
                    <?php endif; ?>
                </div>
                
                <?php if ($has_audio && $audio_file_id): ?>
                <div class="clm-quick-play">
                    <button class="clm-play-button" data-audio-url="<?php echo esc_url(wp_get_attachment_url($audio_file_id)); ?>">
                        <span class="dashicons dashicons-controls-play"></span>
                    </button>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="clm-media-content">
                <h3 class="clm-media-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h3>
                
                <div class="clm-media-meta">
                    <?php
                    // Display composer if available
                    $composer = get_post_meta($lyric_id, '_clm_composer', true);
                    if ($composer) {
                        echo '<span class="clm-media-composer">' . esc_html($composer) . '</span>';
                    }
                    
                    // Display language if available
                    $language_terms = get_the_terms($lyric_id, 'clm_language');
                    if (!is_wp_error($language_terms) && !empty($language_terms)) {
                        echo '<span class="clm-media-language">' . esc_html($language_terms[0]->name) . '</span>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="clm-media-actions">
                <a href="<?php the_permalink(); ?>" class="clm-button clm-button-small">
                    <?php _e('View Details', 'choir-lyrics-manager'); ?>
                </a>
                
                <?php if (is_user_logged_in()): ?>
                <button class="clm-create-playlist-button clm-button-small" data-lyric-id="<?php echo esc_attr($lyric_id); ?>">
                    <span class="dashicons dashicons-playlist-audio"></span>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endwhile; else: ?>
    <div class="clm-no-results">
        <p class="clm-notice"><?php _e('No media found matching your criteria.', 'choir-lyrics-manager'); ?></p>
        <p><?php _e('Try adjusting your filters or search terms.', 'choir-lyrics-manager'); ?></p>
    </div>
    <?php endif; ?>
</div>