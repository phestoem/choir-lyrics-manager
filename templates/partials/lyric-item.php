<?php
/**
 * Template for displaying a single lyric item
 * Combined version with best features from both templates
 *
 * @package Choir_Lyrics_Manager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$lyric_id = get_the_ID();

// Enhanced attachment detection (more robust than original)
$audio_file_id = get_post_meta($lyric_id, '_clm_audio_file_id', true);
$video_embed = get_post_meta($lyric_id, '_clm_video_embed', true);
$sheet_music_id = get_post_meta($lyric_id, '_clm_sheet_music_id', true);
$midi_file_id = get_post_meta($lyric_id, '_clm_midi_file_id', true);
$practice_tracks = get_post_meta($lyric_id, '_clm_practice_tracks', true);

// More thorough checks for media availability
$has_audio = (!empty($audio_file_id) && $audio_file_id !== '0') || 
             (!empty($practice_tracks) && is_array($practice_tracks) && count($practice_tracks) > 0);
$has_video = !empty($video_embed);
$has_sheet = !empty($sheet_music_id) && $sheet_music_id !== '0';
$has_midi = !empty($midi_file_id) && $midi_file_id !== '0';

$has_attachments = ($has_audio || $has_video || $has_sheet || $has_midi);

// Get settings with error handling
if (!isset($clm_settings)) {
    if (class_exists('CLM_Settings')) {
        try {
            $clm_settings = new CLM_Settings('choir-lyrics-manager', CLM_VERSION);
        } catch (Exception $e) {
            error_log('CLM Settings Error in lyric-item.php: ' . $e->getMessage());
            $clm_settings = null;
        }
    } else {
        $clm_settings = null;
    }
}
?>

<li id="lyric-<?php echo esc_attr($lyric_id); ?>" class="clm-item clm-lyric-item" data-title="<?php echo esc_attr(get_the_title()); ?>">
    <div class="clm-item-card">
        <h2 class="clm-item-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            
            <?php if ($has_attachments): ?>
            <div class="clm-attachment-icons" style="display: inline-flex; margin-left: 10px; align-items: center;">
                <?php if ($has_audio): ?>
                <span class="clm-attachment-icon" 
                      title="<?php esc_attr_e('Audio Available', 'choir-lyrics-manager'); ?>" 
                      style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; margin-right: 5px; background-color: #f0f7ff; border: 1px solid #d0e3ff; border-radius: 50%; color: #3498db; font-size: 14px;">
                    🎵
                </span>
                <?php endif; ?>
                
                <?php if ($has_video): ?>
                <span class="clm-attachment-icon" 
                      title="<?php esc_attr_e('Video Available', 'choir-lyrics-manager'); ?>" 
                      style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; margin-right: 5px; background-color: #fff0f0; border: 1px solid #ffd0d0; border-radius: 50%; color: #e74c3c; font-size: 14px;">
                    🎬
                </span>
                <?php endif; ?>
                
                <?php if ($has_sheet): ?>
                <span class="clm-attachment-icon" 
                      title="<?php esc_attr_e('Sheet Music Available', 'choir-lyrics-manager'); ?>" 
                      style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; margin-right: 5px; background-color: #f0fff5; border: 1px solid #d0ffd0; border-radius: 50%; color: #27ae60; font-size: 14px;">
                    📄
                </span>
                <?php endif; ?>
                
                <?php if ($has_midi): ?>
                <span class="clm-attachment-icon" 
                      title="<?php esc_attr_e('MIDI File Available', 'choir-lyrics-manager'); ?>" 
                      style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; margin-right: 5px; background-color: #f5f0ff; border: 1px solid #d0d0ff; border-radius: 50%; color: #9b59b6; font-size: 14px;">
                    🎹
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </h2>
        
        <div class="clm-item-meta">
            <?php
            $meta_items = array();
            
            // Composer
            $composer = get_post_meta($lyric_id, '_clm_composer', true);
            if ($composer) {
                $meta_items[] = '<span class="clm-meta-composer"><strong>' . __('Composer:', 'choir-lyrics-manager') . '</strong> ' . esc_html($composer) . '</span>';
            }
            
            // Language - support multiple languages like the original
            $language_terms = get_the_terms($lyric_id, 'clm_language');
            if (!is_wp_error($language_terms) && !empty($language_terms)) {
                $language_names = array();
                foreach ($language_terms as $term) {
                    $language_names[] = esc_html($term->name);
                }
                $meta_items[] = '<span class="clm-meta-language"><strong>' . __('Language:', 'choir-lyrics-manager') . '</strong> ' . implode(', ', $language_names) . '</span>';
            }
            
            // Difficulty - with better fallback handling like the original
            if ($clm_settings && $clm_settings->get_setting('show_difficulty', true)) {
                $difficulty_terms = get_the_terms($lyric_id, 'clm_difficulty');
                if (!is_wp_error($difficulty_terms) && !empty($difficulty_terms)) {
                    $difficulty_term = reset($difficulty_terms);
                    $difficulty_rating = get_term_meta($difficulty_term->term_id, '_clm_difficulty_rating', true);
                    
                    if (!empty($difficulty_rating) && is_numeric($difficulty_rating)) {
                        $difficulty_rating = absint($difficulty_rating);
                        if ($difficulty_rating >= 1 && $difficulty_rating <= 5) {
                            $stars = '';
                            for ($i = 1; $i <= 5; $i++) {
                                $stars .= $i <= $difficulty_rating ? 
                                    '<span class="dashicons dashicons-star-filled"></span>' : 
                                    '<span class="dashicons dashicons-star-empty"></span>';
                            }
                            $meta_items[] = '<span class="clm-meta-difficulty"><strong>' . __('Difficulty:', 'choir-lyrics-manager') . '</strong> ' . $stars . '</span>';
                        }
                    } else {
                        // Fallback to just showing the term name (like original)
                        $meta_items[] = '<span class="clm-meta-difficulty"><strong>' . __('Difficulty:', 'choir-lyrics-manager') . '</strong> ' . esc_html($difficulty_term->name) . '</span>';
                    }
                }
            }
            
            echo !empty($meta_items) ? implode(' <span class="clm-meta-separator">•</span> ', $meta_items) : '';
            ?>
        </div>
        
        <div class="clm-item-excerpt">
            <?php the_excerpt(); ?>
        </div>
        
        <div class="clm-item-actions">
            <a href="<?php the_permalink(); ?>" class="clm-button"><?php _e('View Lyric', 'choir-lyrics-manager'); ?></a>
            
            <?php if (is_user_logged_in()): ?>
                <?php
                // Enhanced playlist dropdown handling with multiple fallbacks (from original)
                $playlist_rendered = false;
                
                // Try the global function first
                if (function_exists('clm_render_playlist_dropdown')) {
                    echo clm_render_playlist_dropdown($lyric_id);
                    $playlist_rendered = true;
                } elseif (class_exists('CLM_Playlists')) {
                    // Try the class method
                    try {
                        $playlists = new CLM_Playlists('choir-lyrics-manager', CLM_VERSION);
                        if (method_exists($playlists, 'render_playlist_dropdown')) {
                            echo $playlists->render_playlist_dropdown($lyric_id);
                            $playlist_rendered = true;
                        }
                    } catch (Exception $e) {
                        error_log('CLM Playlist Error: ' . $e->getMessage());
                    }
                }
                
                // Fallback playlist form (from original)
                if (!$playlist_rendered) {
                    ?>
                    <button class="clm-create-playlist-button" data-lyric-id="<?php echo esc_attr($lyric_id); ?>">
                        <?php _e('Create Playlist', 'choir-lyrics-manager'); ?>
                    </button>
                    
                    <div class="clm-create-playlist-form" style="display:none;" data-lyric-id="<?php echo esc_attr($lyric_id); ?>">
                        <h4><?php _e('Create New Playlist', 'choir-lyrics-manager'); ?></h4>
                        
                        <?php wp_nonce_field('clm_playlist_nonce', 'clm_playlist_nonce_' . $lyric_id); ?>
                        
                        <div class="clm-form-field">
                            <label for="clm-playlist-name-<?php echo esc_attr($lyric_id); ?>"><?php _e('Playlist Name', 'choir-lyrics-manager'); ?></label>
                            <input type="text" 
                                   id="clm-playlist-name-<?php echo esc_attr($lyric_id); ?>"
                                   class="clm-playlist-name" 
                                   required
                                   placeholder="<?php esc_attr_e('Enter playlist name', 'choir-lyrics-manager'); ?>">
                        </div>
                        
                        <div class="clm-form-field">
                            <label for="clm-playlist-description-<?php echo esc_attr($lyric_id); ?>"><?php _e('Description (optional)', 'choir-lyrics-manager'); ?></label>
                            <textarea id="clm-playlist-description-<?php echo esc_attr($lyric_id); ?>"
                                      class="clm-playlist-description" 
                                      rows="3"></textarea>
                        </div>
                        
                        <div class="clm-form-field">
                            <label><?php _e('Visibility', 'choir-lyrics-manager'); ?></label>
                            <div class="clm-radio-group">
                                <label>
                                    <input type="radio" 
                                           name="clm-playlist-visibility-<?php echo esc_attr($lyric_id); ?>" 
                                           value="private" 
                                           checked> 
                                    <?php _e('Private', 'choir-lyrics-manager'); ?>
                                </label>
                                <label>
                                    <input type="radio" 
                                           name="clm-playlist-visibility-<?php echo esc_attr($lyric_id); ?>" 
                                           value="public"> 
                                    <?php _e('Public', 'choir-lyrics-manager'); ?>
                                </label>
                            </div>
                        </div>
                        
                        <div class="clm-form-actions">
                            <button type="button" class="clm-submit-playlist clm-button clm-button-primary" data-lyric-id="<?php echo esc_attr($lyric_id); ?>">
                                <?php _e('Create', 'choir-lyrics-manager'); ?>
                            </button>
                            <button type="button" class="clm-cancel-playlist clm-button">
                                <?php _e('Cancel', 'choir-lyrics-manager'); ?>
                            </button>
                        </div>
                    </div>
                    <?php
                }
                ?>
            <?php endif; ?>
        </div>
    </div>
</li>