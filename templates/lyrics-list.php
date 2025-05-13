<?php
/**
 * Template for displaying lyrics list
 *
 * @package    Choir_Lyrics_Manager
 */

// If accessed directly, exit
if (!defined('ABSPATH')) {
    exit;
}

// Get shortcode attributes
$show_details = isset($atts['show_details']) && $atts['show_details'] === 'yes';

// Get settings
$settings = new CLM_Settings('choir-lyrics-manager', CLM_VERSION);
$show_difficulty_setting = $settings->get_setting('show_difficulty', true);
$show_composer_setting = $settings->get_setting('show_composer', true);

?>

<div class="clm-lyrics-list-container">
    <?php if (!empty($lyrics)): ?>
        <ul class="clm-lyrics-list">
            <?php foreach ($lyrics as $lyric): ?>
                <li class="clm-lyric-item">
                    <div class="clm-lyric-card">
                        <h3 class="clm-lyric-title">
                            <a href="<?php echo get_permalink($lyric->ID); ?>"><?php echo esc_html($lyric->post_title); ?></a>
                        </h3>
                        
                        <?php if ($show_details): ?>
                            <div class="clm-lyric-meta">
                                <?php
                                // Show composer if available and enabled
                                if ($show_composer_setting) {
                                    $composer = get_post_meta($lyric->ID, '_clm_composer', true);
                                    if ($composer) {
                                        echo '<span class="clm-meta-item clm-composer">';
                                        echo '<span class="clm-meta-label">' . __('Composer:', 'choir-lyrics-manager') . '</span> ';
                                        echo '<span class="clm-meta-value">' . esc_html($composer) . '</span>';
                                        echo '</span>';
                                    }
                                }
                                
                                // Show language if available
                                $language = get_post_meta($lyric->ID, '_clm_language', true);
                                if ($language) {
                                    echo '<span class="clm-meta-item clm-language">';
                                    echo '<span class="clm-meta-label">' . __('Language:', 'choir-lyrics-manager') . '</span> ';
                                    echo '<span class="clm-meta-value">' . esc_html($language) . '</span>';
                                    echo '</span>';
                                }
                                
                                // Show difficulty if available and enabled
                                if ($show_difficulty_setting) {
                                    $difficulty = get_post_meta($lyric->ID, '_clm_difficulty', true);
                                    if ($difficulty) {
                                        echo '<span class="clm-meta-item clm-difficulty">';
                                        echo '<span class="clm-meta-label">' . __('Difficulty:', 'choir-lyrics-manager') . '</span> ';
                                        echo '<span class="clm-meta-value clm-difficulty-stars">';
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $difficulty) {
                                                echo '<span class="dashicons dashicons-star-filled"></span>';
                                            } else {
                                                echo '<span class="dashicons dashicons-star-empty"></span>';
                                            }
                                        }
                                        echo '</span>';
                                        echo '</span>';
                                    }
                                }
                                
                                // Show genres
                                $genres = get_the_terms($lyric->ID, 'clm_genre');
                                if ($genres && !is_wp_error($genres)) {
                                    $genre_names = array();
                                    foreach ($genres as $genre) {
                                        $genre_names[] = '<a href="' . esc_url(get_term_link($genre)) . '">' . esc_html($genre->name) . '</a>';
                                    }
                                    echo '<span class="clm-meta-item clm-genres">';
                                    echo '<span class="clm-meta-label">' . __('Genres:', 'choir-lyrics-manager') . '</span> ';
                                    echo '<span class="clm-meta-value">' . implode(', ', $genre_names) . '</span>';
                                    echo '</span>';
                                }
                                
                                // Show year if available
                                $year = get_post_meta($lyric->ID, '_clm_year', true);
                                if ($year) {
                                    echo '<span class="clm-meta-item clm-year">';
                                    echo '<span class="clm-meta-label">' . __('Year:', 'choir-lyrics-manager') . '</span> ';
                                    echo '<span class="clm-meta-value">' . esc_html($year) . '</span>';
                                    echo '</span>';
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($lyric->post_excerpt)): ?>
                            <div class="clm-lyric-excerpt">
                                <?php echo wpautop(esc_html($lyric->post_excerpt)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="clm-lyric-actions">
                            <a href="<?php echo get_permalink($lyric->ID); ?>" class="clm-button clm-button-small"><?php _e('View Lyric', 'choir-lyrics-manager'); ?></a>
                            
                            <?php
                            // Check if audio file exists
                            $audio_file_id = get_post_meta($lyric->ID, '_clm_audio_file_id', true);
                            if ($audio_file_id) {
                                $audio_url = wp_get_attachment_url($audio_file_id);
                                if ($audio_url) {
                                    echo '<a href="' . esc_url($audio_url) . '" class="clm-button clm-button-small clm-button-secondary" target="_blank">' . __('Listen', 'choir-lyrics-manager') . '</a>';
                                }
                            }
                            
                            // Check if sheet music exists
                            $sheet_music_id = get_post_meta($lyric->ID, '_clm_sheet_music_id', true);
                            if ($sheet_music_id) {
                                $sheet_music_url = wp_get_attachment_url($sheet_music_id);
                                if ($sheet_music_url) {
                                    echo '<a href="' . esc_url($sheet_music_url) . '" class="clm-button clm-button-small clm-button-secondary" target="_blank">' . __('Sheet Music', 'choir-lyrics-manager') . '</a>';
                                }
                            }
                            
                            // Add to playlist button for logged in users
                            if (is_user_logged_in()) {
                                $playlists = new CLM_Playlists('choir-lyrics-manager', CLM_VERSION);
                                echo $playlists->render_playlist_dropdown($lyric->ID);
                            }
                            ?>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="clm-notice"><?php _e('No lyrics found matching your criteria.', 'choir-lyrics-manager'); ?></p>
    <?php endif; ?>
</div>

<style>
/* Lyrics List Styles */
.clm-lyrics-list-container {
    margin: 20px 0;
}

.clm-lyrics-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.clm-lyric-item {
    margin: 0;
}

.clm-lyric-card {
    background-color: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: box-shadow 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.clm-lyric-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.clm-lyric-title {
    font-size: 1.3em;
    margin: 0 0 15px 0;
}

.clm-lyric-title a {
    text-decoration: none;
    color: #333;
}

.clm-lyric-title a:hover {
    color: #3498db;
}

.clm-lyric-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
    font-size: 0.9em;
}

.clm-meta-item {
    display: inline-block;
}

.clm-meta-label {
    font-weight: 600;
    color: #666;
}

.clm-meta-value {
    color: #333;
}

.clm-meta-value a {
    color: #3498db;
    text-decoration: none;
}

.clm-meta-value a:hover {
    text-decoration: underline;
}

.clm-difficulty-stars {
    color: #f39c12;
}

.clm-difficulty-stars .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.clm-difficulty-stars .dashicons-star-empty {
    color: #ddd;
}

.clm-lyric-excerpt {
    font-size: 0.95em;
    line-height: 1.5;
    color: #666;
    margin-bottom: 15px;
    flex-grow: 1;
}

.clm-lyric-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-top: auto;
}

.clm-button-secondary {
    background-color: #f5f5f5;
    color: #333;
    border-color: #ddd;
}

.clm-button-secondary:hover {
    background-color: #e9e9e9;
    color: #333;
}

/* Responsive adjustments */
@media screen and (max-width: 768px) {
    .clm-lyrics-list {
        grid-template-columns: 1fr;
    }
    
    .clm-lyric-meta {
        font-size: 0.85em;
    }
    
    .clm-lyric-actions {
        flex-wrap: wrap;
    }
}

/* Single column layout option */
.clm-lyrics-list.single-column {
    grid-template-columns: 1fr;
    max-width: 800px;
    margin: 0 auto;
}

.clm-lyrics-list.single-column .clm-lyric-card {
    display: block;
}

.clm-lyrics-list.single-column .clm-lyric-meta {
    justify-content: flex-start;
}

/* No results notice */
.clm-notice {
    text-align: center;
    padding: 40px 20px;
    background-color: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 8px;
    color: #666;
    font-size: 1.1em;
}
</style>