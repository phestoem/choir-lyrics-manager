<?php
/**
 * Template for displaying single lyric
 *
 * @package    Choir_Lyrics_Manager
 */

// If accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}

// Get shortcode attributes if this is called from a shortcode
$show_details = isset($atts) && isset($atts['show_details']) ? $atts['show_details'] === 'yes' : true;
$show_media = isset($atts) && isset($atts['show_media']) ? $atts['show_media'] === 'yes' : true;
$show_practice = isset($atts) && isset($atts['show_practice']) ? $atts['show_practice'] === 'yes' : true;

// If this is a direct template call, get the post
if (!isset($lyric)) {
    global $post;
    $lyric = $post;
    $lyric_id = $post->ID;
}

// Get settings
$settings = new CLM_Settings('choir-lyrics-manager', CLM_VERSION);
$show_difficulty_setting = $settings->get_setting('show_difficulty', true);
$show_composer_setting = $settings->get_setting('show_composer', true);

get_header();
?>
<?php
// Helper function to extract video URL from embed code
function extract_video_url($embed_code) {
    // Try to extract YouTube URL
    preg_match('/src="([^"]+)"/', $embed_code, $match);
    if (!empty($match[1])) {
        return $match[1];
    }
    
    // Check if the embed code is just a URL
    if (filter_var($embed_code, FILTER_VALIDATE_URL)) {
        return $embed_code;
    }
    
    return false;
}
?>
<div class="clm-single-lyric-container">
    <article id="lyric-<?php echo $lyric->ID; ?>" class="clm-lyric">
        <div class="clm-lyric-header">
            <h1 class="clm-lyric-title"><?php echo get_the_title($lyric->ID); ?></h1>
            
            <?php if ($show_details): ?>
                <div class="clm-lyric-meta">
                    <?php 
                    // Prepare metadata items
                    $meta_items = array();
                    
                    // Composer and arranger
                    if ($show_composer_setting) {
                        $composer = get_post_meta($lyric->ID, '_clm_composer', true);
                        if ($composer) {
                            $meta_items[] = array(
                                'label' => __('Composer', 'choir-lyrics-manager'),
                                'value' => $composer,
                                'icon' => 'music'
                            );
                        }
                        
                        $arranger = get_post_meta($lyric->ID, '_clm_arranger', true);
                        if ($arranger) {
                            $meta_items[] = array(
                                'label' => __('Arranger', 'choir-lyrics-manager'),
                                'value' => $arranger,
                                'icon' => 'edit'
                            );
                        }
                    }
                    
                    // Year
                    $year = get_post_meta($lyric->ID, '_clm_year', true);
                    if ($year) {
                        $meta_items[] = array(
                            'label' => __('Year', 'choir-lyrics-manager'),
                            'value' => $year,
                            'icon' => 'calendar'
                        );
                    }
                    
                    // Language
                    $language = get_post_meta($lyric->ID, '_clm_language', true);
                    if ($language) {
                        $meta_items[] = array(
                            'label' => __('Language', 'choir-lyrics-manager'),
                            'value' => $language,
                            'icon' => 'translation'
                        );
                    }
                    
                    // Display genres
                    $genres = get_the_terms($lyric->ID, 'clm_genre');
                    if ($genres && !is_wp_error($genres)) {
                        $genre_links = array();
                        foreach ($genres as $genre) {
                            $genre_links[] = '<a href="' . esc_url(get_term_link($genre)) . '">' . esc_html($genre->name) . '</a>';
                        }
                        
                        $meta_items[] = array(
                            'label' => __('Genres', 'choir-lyrics-manager'),
                            'value' => implode(', ', $genre_links),
                            'icon' => 'tag',
                            'is_html' => true
                        );
                    }
                    
                    // Display metadata in a grid
                    if (!empty($meta_items)): ?>
                        <div class="clm-meta-grid">
                            <?php foreach ($meta_items as $item): ?>
                                <div class="clm-meta-item">
                                    <div class="clm-meta-icon">
                                        <span class="dashicons dashicons-<?php echo esc_attr($item['icon']); ?>"></span>
                                    </div>
                                    <div class="clm-meta-content">
                                        <span class="clm-meta-label"><?php echo esc_html($item['label']); ?></span>
                                        <?php if (isset($item['is_html']) && $item['is_html']): ?>
                                            <span class="clm-meta-value"><?php echo $item['value']; ?></span>
                                        <?php else: ?>
                                            <span class="clm-meta-value"><?php echo esc_html($item['value']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if ($show_difficulty_setting): 
                                $difficulty = get_post_meta($lyric->ID, '_clm_difficulty', true);
                                if ($difficulty): ?>
                                    <div class="clm-meta-item clm-difficulty-item">
                                        <div class="clm-meta-icon">
                                            <span class="dashicons dashicons-chart-bar"></span>
                                        </div>
                                        <div class="clm-meta-content">
                                            <span class="clm-meta-label"><?php _e('Difficulty', 'choir-lyrics-manager'); ?></span>
                                            <span class="clm-meta-value clm-difficulty-stars">
                                                <?php 
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $difficulty) {
                                                        echo '<span class="dashicons dashicons-star-filled"></span>';
                                                    } else {
                                                        echo '<span class="dashicons dashicons-star-empty"></span>';
                                                    }
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="clm-lyric-content-wrapper">
            <div class="clm-lyric-content">
                <?php echo apply_filters('the_content', $lyric->post_content); ?>
            </div>
            
            <?php 
            // Display performance notes if available
            $performance_notes = get_post_meta($lyric->ID, '_clm_performance_notes', true);
            if ($show_details && $performance_notes): 
            ?>
                <div class="clm-performance-notes">
                    <h3 class="clm-subheading"><?php _e('Performance Notes', 'choir-lyrics-manager'); ?></h3>
                    <div class="clm-performance-notes-content">
                        <?php echo wpautop(esc_html($performance_notes)); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php 
        // Display media if enabled
        if ($show_media): 
            // Get media fields
            $sheet_music_id = get_post_meta($lyric->ID, '_clm_sheet_music_id', true);
            $audio_file_id = get_post_meta($lyric->ID, '_clm_audio_file_id', true);
            $video_embed = get_post_meta($lyric->ID, '_clm_video_embed', true);
            $midi_file_id = get_post_meta($lyric->ID, '_clm_midi_file_id', true);
            $practice_tracks = get_post_meta($lyric->ID, '_clm_practice_tracks', true);
            
            // Only show media section if we have media
            if ($sheet_music_id || $audio_file_id || $video_embed || $midi_file_id || (!empty($practice_tracks) && is_array($practice_tracks))): 
        ?>
            <div class="clm-media-wrapper">
                <h2 class="clm-media-heading"><?php _e('Media', 'choir-lyrics-manager'); ?></h2>
                
                <div class="clm-media-grid">
                    <?php if ($sheet_music_id): ?>
                        <div class="clm-media-card clm-sheet-music">
                            <h3 class="clm-media-title">
                                <span class="dashicons dashicons-media-document"></span>
                                <?php _e('Sheet Music', 'choir-lyrics-manager'); ?>
                            </h3>
                            <?php 
                            $sheet_music_url = wp_get_attachment_url($sheet_music_id);
                            $sheet_music_filename = basename(get_attached_file($sheet_music_id));
                            $sheet_music_filetype = wp_check_filetype($sheet_music_url);
                            $icon = 'dashicons-media-default';
                            
                            // Set appropriate icon
                            if ($sheet_music_filetype['ext'] == 'pdf') {
                                $icon = 'dashicons-pdf';
                            } elseif (in_array($sheet_music_filetype['ext'], array('doc', 'docx'))) {
                                $icon = 'dashicons-media-document';
                            }
                            ?>
                            <div class="clm-media-content">
                                <a href="<?php echo esc_url($sheet_music_url); ?>" class="clm-file-link" target="_blank">
                                    <span class="dashicons <?php echo $icon; ?>"></span>
                                    <span class="clm-file-name"><?php echo esc_html($sheet_music_filename); ?></span>
                                    <span class="clm-download-text"><?php _e('Download', 'choir-lyrics-manager'); ?></span>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($audio_file_id): ?>
                        <div class="clm-media-card clm-audio">
                            <h3 class="clm-media-title">
                                <span class="dashicons dashicons-format-audio"></span>
                                <?php _e('Audio Recording', 'choir-lyrics-manager'); ?>
                            </h3>
                            <div class="clm-media-content">
                                <?php 
                                $audio_url = wp_get_attachment_url($audio_file_id);
                                echo wp_audio_shortcode(array('src' => $audio_url));
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
					<?php if ($video_embed): ?>
					<div class="clm-media-card clm-video full-width">
						<h3 class="clm-media-title">
							<span class="dashicons dashicons-format-video"></span>
							<?php _e('Video', 'choir-lyrics-manager'); ?>
						</h3>
						<div class="clm-media-content clm-video-container">
							<?php 
							// Make sure we're using wp_oembed_get for proper embedding
							$video_url = extract_video_url($video_embed);
							if ($video_url) {
								// Add rel=0 to disable related videos
								if (strpos($video_url, 'youtube.com') !== false) {
									$video_url = add_query_arg('rel', '0', $video_url);
									
									// Also add modestbranding=1 to reduce YouTube branding
									$video_url = add_query_arg('modestbranding', '1', $video_url);
								}
								echo wp_oembed_get($video_url);
							} else {
								// Fallback to the stored embed code with security handling
								// Modify iframe src to add rel=0 parameter
								$modified_embed = preg_replace(
									'/src="([^"]+)"/i',
									'src="$1?rel=0&modestbranding=1"',
									$video_embed
								);
								echo wp_kses($modified_embed, array(
									'iframe' => array(
										'src' => array(),
										'width' => array(),
										'height' => array(),
										'frameborder' => array(),
										'allowfullscreen' => array(),
										'allow' => array(),
										'title' => array(),
										'style' => array(),
										'class' => array()
									)
								));
							}
							?>
						</div>
					</div>
				<?php endif; ?>
                    
                    <?php if ($midi_file_id): ?>
                        <div class="clm-media-card clm-midi">
                            <h3 class="clm-media-title">
                                <span class="dashicons dashicons-format-audio"></span>
                                <?php _e('MIDI File', 'choir-lyrics-manager'); ?>
                            </h3>
                            <div class="clm-media-content">
                                <?php 
                                $midi_url = wp_get_attachment_url($midi_file_id);
                                $midi_filename = basename(get_attached_file($midi_file_id));
                                ?>
                                <a href="<?php echo esc_url($midi_url); ?>" class="clm-file-link" target="_blank">
                                    <span class="dashicons dashicons-format-audio"></span>
                                    <span class="clm-file-name"><?php echo esc_html($midi_filename); ?></span>
                                    <span class="clm-download-text"><?php _e('Download', 'choir-lyrics-manager'); ?></span>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php 
                if (!empty($practice_tracks) && is_array($practice_tracks)): 
                ?>
                    <div class="clm-practice-tracks-section">
                        <h3 class="clm-practice-tracks-title">
                            <span class="dashicons dashicons-playlist-audio"></span>
                            <?php _e('Practice Tracks', 'choir-lyrics-manager'); ?>
                        </h3>
                        
                        <div class="clm-practice-tracks-grid">
                            <?php foreach ($practice_tracks as $track): ?>
                                <div class="clm-practice-track-card">
                                    <h4 class="clm-practice-track-title"><?php echo esc_html($track['title']); ?></h4>
                                    <div class="clm-practice-track-player">
                                        <?php 
                                        $track_url = wp_get_attachment_url($track['id']);
                                        if ($track_url) {
                                            echo wp_audio_shortcode(array('src' => $track_url));
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php 
            endif; // end media check
        endif; // end show_media
        ?>
        
        <div class="clm-lyric-actions">
            <?php
            // Display playlist button
            if (is_user_logged_in()):
                $playlists = new CLM_Playlists('choir-lyrics-manager', CLM_VERSION);
                echo $playlists->render_playlist_dropdown($lyric->ID);
            endif;
            ?>
            
            <div class="clm-social-share">
                <span class="clm-share-label"><?php _e('Share:', 'choir-lyrics-manager'); ?></span>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink($lyric->ID)); ?>" target="_blank" class="clm-social-button facebook">
                    <span class="dashicons dashicons-facebook"></span>
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink($lyric->ID)); ?>&text=<?php echo urlencode(get_the_title($lyric->ID)); ?>" target="_blank" class="clm-social-button twitter">
                    <span class="dashicons dashicons-twitter"></span>
                </a>
                <a href="mailto:?subject=<?php echo urlencode(get_the_title($lyric->ID)); ?>&body=<?php echo urlencode(get_permalink($lyric->ID)); ?>" class="clm-social-button email">
                    <span class="dashicons dashicons-email"></span>
                </a>
            </div>
        </div>
        
        <?php 
        // Display practice tracking widget if enabled
        if ($show_practice && is_user_logged_in()):
            // Check if practice tracking is enabled
            if ($settings->get_setting('enable_practice', true)): 
                $practice = new CLM_Practice('choir-lyrics-manager', CLM_VERSION);
                echo $practice->render_practice_widget($lyric->ID);
            endif;
        endif;
        ?>
				
		
		<?php 
		// Add this right after the practice tracking widget, only once
		if (is_user_logged_in()):
			// Get current user's member info
			global $wpdb;
			$user_id = get_current_user_id();
			
			// Check if user has a member profile linked
			$member = $wpdb->get_row($wpdb->prepare(
				"SELECT * FROM {$wpdb->posts} p 
				JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
				WHERE pm.meta_key = '_clm_wp_user_id' 
				AND pm.meta_value = %d 
				AND p.post_type = 'clm_member'
				LIMIT 1",
				$user_id
			));
			
			if ($member):
				$skills = new CLM_Skills('choir-lyrics-manager', CLM_VERSION);
				$skill = $skills->get_member_skill($member->ID, $lyric->ID);
				
				if ($skill):
					$skill_levels = $skills->get_skill_levels();
					$level_info = $skill_levels[$skill->skill_level];
					?>
					<div class="clm-skill-status-widget">
						<h3><?php _e('Your Skill Level', 'choir-lyrics-manager'); ?></h3>
						<div class="clm-current-skill">
							<span class="clm-skill-badge" style="background-color: <?php echo $level_info['color']; ?>">
								<span class="dashicons <?php echo $level_info['icon']; ?>"></span>
								<?php echo $level_info['label']; ?>
							</span>
							<div class="clm-skill-stats">
								<div class="clm-skill-stat">
									<strong><?php _e('Practice Sessions:', 'choir-lyrics-manager'); ?></strong>
									<span><?php echo $skill->practice_count; ?></span>
								</div>
								<div class="clm-skill-stat">
									<strong><?php _e('Total Time:', 'choir-lyrics-manager'); ?></strong>
									<span><?php echo $skills->format_minutes($skill->total_practice_minutes); ?></span>
								</div>
								<?php if ($skill->goal_date): ?>
								<div class="clm-skill-stat">
									<strong><?php _e('Goal:', 'choir-lyrics-manager'); ?></strong>
									<span><?php echo date_i18n(get_option('date_format'), strtotime($skill->goal_date)); ?></span>
								</div>
								<?php endif; ?>
							</div>
							<div class="clm-skill-progress">
								<div class="clm-progress-bar">
									<div class="clm-progress-fill" style="width: <?php echo ($level_info['value'] / 4) * 100; ?>%; background-color: <?php echo $level_info['color']; ?>"></div>
								</div>
								<p class="clm-skill-description"><?php echo $level_info['description']; ?></p>
							</div>
						</div>
					</div>
					<?php 
				else: 
					?>
					<div class="clm-skill-status-widget">
						<h3><?php _e('Skill Progress', 'choir-lyrics-manager'); ?></h3>
						<p class="clm-no-skill"><?php _e('Start practicing to track your skill level for this song!', 'choir-lyrics-manager'); ?></p>
					</div>
					<?php
				endif;
			else:
				?>
				<div class="clm-skill-status-widget">
					<h3><?php _e('Member Profile Required', 'choir-lyrics-manager'); ?></h3>
					<p class="clm-notice"><?php _e('A member profile needs to be created for you to track skills. Please contact your choir administrator.', 'choir-lyrics-manager'); ?></p>
				</div>
				<?php
			endif;
		endif;
		?>
	
    </article>
</div>



<style>
/* Enhanced styles for single lyric page */
.clm-single-lyric-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    line-height: 1.6;
}

.clm-lyric {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    overflow: hidden;
    margin-bottom: 40px;
}

.clm-lyric-header {
    padding: 25px 30px;
    border-bottom: 1px solid #eaeaea;
    background-color: #f9f9f9;
}

.clm-lyric-title {
    font-size: 2.2em;
    margin: 0 0 15px;
    line-height: 1.2;
    color: #333;
}

.clm-meta-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.clm-meta-item {
    display: flex;
    align-items: flex-start;
    background-color: #fff;
    padding: 10px 15px;
    border-radius: 6px;
    border: 1px solid #eee;
}

.clm-meta-icon {
    margin-right: 10px;
    color: #3498db;
}

.clm-meta-content {
    flex: 1;
}

.clm-meta-label {
    display: block;
    font-size: 0.8em;
    color: #666;
    margin-bottom: 2px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.clm-meta-value {
    font-weight: 500;
    color: #333;
}

.clm-difficulty-stars {
    color: #f39c12;
}

.clm-difficulty-stars .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.clm-difficulty-stars .dashicons-star-empty {
    color: #ddd;
}

.clm-lyric-content-wrapper {
    padding: 30px;
}

.clm-lyric-content {
    font-size: 1.05em;
    line-height: 1.7;
    white-space: pre-line;
    margin-bottom: 30px;
}

.clm-lyric-content p {
    margin-bottom: 1em;
}

.clm-performance-notes {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 6px;
    margin-top: 20px;
}

.clm-subheading {
    font-size: 1.4em;
    margin-bottom: 15px;
    color: #333;
    padding-bottom: 8px;
    border-bottom: 1px solid #eee;
}

.clm-media-wrapper {
    padding: 0 30px 30px;
}

.clm-media-heading {
    font-size: 1.6em;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.clm-media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.clm-media-grid .full-width {
    grid-column: 1 / -1;
}

.clm-media-card {
    background-color: #f9f9f9;
    border-radius: 6px;
    overflow: hidden;
    border: 1px solid #eee;
}

.clm-media-title {
    background-color: #f1f1f1;
    margin: 0;
    padding: 12px 15px;
    font-size: 1.1em;
    display: flex;
    align-items: center;
    border-bottom: 1px solid #e5e5e5;
}

.clm-media-title .dashicons {
    margin-right: 8px;
    color: #3498db;
}

.clm-media-content {
    padding: 15px;
}

.clm-file-link {
    display: flex;
    align-items: center;
    padding: 10px;
    background-color: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    text-decoration: none;
    color: #333;
    transition: all 0.2s ease;
}

.clm-file-link:hover {
    background-color: #f5f5f5;
    color: #3498db;
}

.clm-file-link .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
    margin-right: 10px;
    color: #3498db;
}

.clm-file-name {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.clm-download-text {
    margin-left: 10px;
    font-size: 0.9em;
    background-color: #3498db;
    color: white;
    padding: 2px 8px;
    border-radius: 3px;
}

.clm-practice-tracks-section {
    margin-top: 30px;
}

.clm-practice-tracks-title {
    font-size: 1.3em;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
}

.clm-practice-tracks-title .dashicons {
    margin-right: 8px;
    color: #3498db;
}

.clm-practice-tracks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.clm-practice-track-card {
    background-color: #f9f9f9;
    border-radius: 6px;
    overflow: hidden;
    border: 1px solid #eee;
}

.clm-practice-track-title {
    background-color: #f1f1f1;
    margin: 0;
    padding: 10px 15px;
    font-size: 1em;
    border-bottom: 1px solid #e5e5e5;
}

.clm-practice-track-player {
    padding: 15px;
}

.clm-practice-track-player .wp-audio-shortcode {
    margin: 0;
}

.clm-lyric-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 30px;
    background-color: #f9f9f9;
    border-top: 1px solid #eaeaea;
}

.clm-social-share {
    display: flex;
    align-items: center;
}

.clm-share-label {
    margin-right: 10px;
    font-weight: 500;
}

.clm-social-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    margin-right: 8px;
    text-decoration: none;
    color: white;
    transition: all 0.2s ease;
}

.clm-social-button.facebook {
    background-color: #3b5998;
}

.clm-social-button.twitter {
    background-color: #1da1f2;
}

.clm-social-button.email {
    background-color: #777;
}

.clm-social-button:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}

.clm-video-container {
    position: relative;
    padding-bottom: 56.25%;
    height: 0;
    overflow: hidden;
}

.clm-video-container iframe,
.clm-video-container object,
.clm-video-container embed {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

/* Styles for the practice widget */
.clm-practice-tracker {
    margin: 30px;
    padding: 25px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .clm-meta-grid,
    .clm-media-grid,
    .clm-practice-tracks-grid {
        grid-template-columns: 1fr;
    }
    
    .clm-lyric-header,
    .clm-lyric-content-wrapper,
    .clm-media-wrapper,
    .clm-lyric-actions {
        padding: 20px;
    }
    
    .clm-practice-tracker {
        margin: 20px;
        padding: 20px;
    }
    
    .clm-lyric-title {
        font-size: 1.8em;
    }
    
    .clm-lyric-actions {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .clm-social-share {
        margin-top: 15px;
    }
}
</style>

<?php
get_footer();