<?php
/**
 * Template part for displaying a lyric item
 *
 * @package    Choir_Lyrics_Manager
 */

// Don't allow direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<li id="lyric-<?php the_ID(); ?>" class="clm-item clm-lyric-item" data-title="<?php echo esc_attr(get_the_title()); ?>">
    <div class="clm-item-card">
        <h2 class="clm-item-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h2>
        
        <div class="clm-item-meta">
            <?php
            $meta_items = array();
            
            // Show composer if available
            $composer = get_post_meta(get_the_ID(), '_clm_composer', true);
            if ($composer) {
                $meta_items[] = '<span class="clm-meta-composer"><strong>' . __('Composer:', 'choir-lyrics-manager') . '</strong> ' . esc_html($composer) . '</span>';
            }
            
            // Show language if available
            $language = get_post_meta(get_the_ID(), '_clm_language', true);
            if ($language) {
                $meta_items[] = '<span class="clm-meta-language"><strong>' . __('Language:', 'choir-lyrics-manager') . '</strong> ' . esc_html($language) . '</span>';
            }
            
            // Show difficulty if available and enabled
            if ($this->settings->get_setting('show_difficulty', true)) {
                $difficulty = get_post_meta(get_the_ID(), '_clm_difficulty', true);
                if ($difficulty) {
                    $stars = '';
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $difficulty) {
                            $stars .= '<span class="dashicons dashicons-star-filled"></span>';
                        } else {
                            $stars .= '<span class="dashicons dashicons-star-empty"></span>';
                        }
                    }
                    $meta_items[] = '<span class="clm-meta-difficulty"><strong>' . __('Difficulty:', 'choir-lyrics-manager') . '</strong> ' . $stars . '</span>';
                }
            }
            
            echo implode(' <span class="clm-meta-separator">•</span> ', $meta_items);
            ?>
        </div>
        
        <div class="clm-item-excerpt">
            <?php the_excerpt(); ?>
        </div>
        
        <div class="clm-item-actions">
            <a href="<?php the_permalink(); ?>" class="clm-button"><?php _e('View Lyric', 'choir-lyrics-manager'); ?></a>
            
            <?php if (is_user_logged_in()): ?>
                <?php
                // Show add to playlist button
                $playlists = new CLM_Playlists('choir-lyrics-manager', CLM_VERSION);
                echo $playlists->render_playlist_dropdown(get_the_ID());
                ?>
            <?php endif; ?>
        </div>
    </div>
</li>