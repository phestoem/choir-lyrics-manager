<?php
/**
 * Template Part: Lyric Item for Shortcode
 * 
 * @package Choir_Lyrics_Manager
 */

// If accessed directly, exit
if (!defined("ABSPATH")) {
    exit;
}

// Get the lyric ID
$lyric_id = get_the_ID();

// Check for attachments
$has_audio = !empty(get_post_meta($lyric_id, '_clm_audio_file_id', true));
$has_video = !empty(get_post_meta($lyric_id, '_clm_video_embed', true));
$has_sheet = !empty(get_post_meta($lyric_id, '_clm_sheet_music_id', true));
$has_midi = !empty(get_post_meta($lyric_id, '_clm_midi_file_id', true));

$has_attachments = ($has_audio || $has_video || $has_sheet || $has_midi);
?>

<li id="lyric-<?php the_ID(); ?>" class="clm-item clm-lyric-item" data-title="<?php echo esc_attr(get_the_title()); ?>">
    <div class="clm-item-card">
        <h2 class="clm-item-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            
            <?php if ($has_attachments): ?>
            <div class="clm-attachment-icons" style="display: inline-flex; margin-left: 10px; align-items: center;">
                <?php if ($has_audio): ?>
                <span class="clm-attachment-icon" title="<?php esc_attr_e('Audio Available', 'choir-lyrics-manager'); ?>" style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; margin-right: 5px; background-color: #f0f7ff; border: 1px solid #d0e3ff; border-radius: 50%; color: #3498db; font-size: 14px;">
                    🎵
                </span>
                <?php endif; ?>
                
                <?php if ($has_video): ?>
                <span class="clm-attachment-icon" title="<?php esc_attr_e('Video Available', 'choir-lyrics-manager'); ?>" style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; margin-right: 5px; background-color: #fff0f0; border: 1px solid #ffd0d0; border-radius: 50%; color: #e74c3c; font-size: 14px;">
                    🎬
                </span>
                <?php endif; ?>
                
                <?php if ($has_sheet): ?>
                <span class="clm-attachment-icon" title="<?php esc_attr_e('Sheet Music Available', 'choir-lyrics-manager'); ?>" style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; margin-right: 5px; background-color: #f0fff5; border: 1px solid #d0ffd0; border-radius: 50%; color: #27ae60; font-size: 14px;">
                    📄
                </span>
                <?php endif; ?>
                
                <?php if ($has_midi): ?>
                <span class="clm-attachment-icon" title="<?php esc_attr_e('MIDI File Available', 'choir-lyrics-manager'); ?>" style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; margin-right: 5px; background-color: #f5f0ff; border: 1px solid #d0d0ff; border-radius: 50%; color: #9b59b6; font-size: 14px;">
                    🎹
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </h2>
        
        <div class="clm-item-meta">
            <?php
            $meta_items = array();
            
            // Show composer if available
            $composer = get_post_meta(get_the_ID(), '_clm_composer', true);
            if (!empty($composer)) {
                $meta_items[] = '<span class="clm-meta-composer"><strong>' . __('Composer:', 'choir-lyrics-manager') . '</strong> ' . esc_html($composer) . '</span>';
            }
            
            // Show language if available
            $language_terms = get_the_terms(get_the_ID(), 'clm_language');
            if (!is_wp_error($language_terms) && !empty($language_terms)) {
                $language_names = array();
                foreach ($language_terms as $term) {
                    $language_names[] = esc_html($term->name);
                }
                $meta_items[] = '<span class="clm-meta-language"><strong>' . __('Language:', 'choir-lyrics-manager') . '</strong> ' . implode(', ', $language_names) . '</span>';
            }
            
            // Show difficulty if available and enabled
            $difficulty_terms = get_the_terms(get_the_ID(), 'clm_difficulty');
            if (!is_wp_error($difficulty_terms) && !empty($difficulty_terms)) {
                // Get the first difficulty term
                $difficulty_term = reset($difficulty_terms);
                // Check if it has a difficulty rating as term meta
                $difficulty_rating = get_term_meta($difficulty_term->term_id, '_clm_difficulty_rating', true);
                if (!empty($difficulty_rating) && is_numeric($difficulty_rating)) {
                    $difficulty_rating = absint($difficulty_rating);
                    if ($difficulty_rating >= 1 && $difficulty_rating <= 5) {
                        $stars = '';
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $difficulty_rating) {
                                $stars .= '<span class="dashicons dashicons-star-filled"></span>';
                            } else {
                                $stars .= '<span class="dashicons dashicons-star-empty"></span>';
                            }
                        }
                        $meta_items[] = '<span class="clm-meta-difficulty"><strong>' . __('Difficulty:', 'choir-lyrics-manager') . '</strong> ' . $stars . '</span>';
                    }
                } else {
                    // Fall back to just showing the term name
                    $meta_items[] = '<span class="clm-meta-difficulty"><strong>' . __('Difficulty:', 'choir-lyrics-manager') . '</strong> ' . esc_html($difficulty_term->name) . '</span>';
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
                // Show playlist button
                if (class_exists('CLM_Playlists')) {
                    $playlists = new CLM_Playlists('choir-lyrics-manager', CLM_VERSION);
                    echo $playlists->render_playlist_dropdown(get_the_ID());
                }
                ?>
            <?php endif; ?>
        </div>
    </div>
</li>