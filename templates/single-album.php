<?php
/**
 * Template for displaying single album
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

// If this is a direct template call, get the post
if (!isset($album)) {
    global $post;
    $album = $post;
    $album_id = $post->ID;
}

// If this is a direct template call, also get the lyrics
if (!isset($lyrics)) {
    $lyrics_ids = get_post_meta($album->ID, '_clm_lyrics', true);
    
    $lyrics = array();
    
    if (is_array($lyrics_ids) && !empty($lyrics_ids)) {
        foreach ($lyrics_ids as $lyric_id) {
            $lyric = get_post($lyric_id);
            
            if ($lyric && $lyric->post_status === 'publish') {
                $lyrics[] = $lyric;
            }
        }
    }
}

get_header();
?>

<div class="clm-container">
    <article id="album-<?php echo $album->ID; ?>" class="clm-album">
        <header class="clm-album-header">
            <h1 class="clm-album-title"><?php echo get_the_title($album->ID); ?></h1>
            
            <?php if ($show_details): ?>
                <div class="clm-album-meta">
                    <?php 
                    $release_year = get_post_meta($album->ID, '_clm_release_year', true);
                    if ($release_year): 
                    ?>
                        <div class="clm-meta-item clm-release-year">
                            <span class="clm-meta-label"><?php _e('Release Year:', 'choir-lyrics-manager'); ?></span>
                            <span class="clm-meta-value"><?php echo esc_html($release_year); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php 
                    $director = get_post_meta($album->ID, '_clm_director', true);
                    if ($director): 
                    ?>
                        <div class="clm-meta-item clm-director">
                            <span class="clm-meta-label"><?php _e('Director/Conductor:', 'choir-lyrics-manager'); ?></span>
                            <span class="clm-meta-value"><?php echo esc_html($director); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    // Display collections
                    $collections = get_the_terms($album->ID, 'clm_collection');
                    if ($collections && !is_wp_error($collections)):
                    ?>
                        <div class="clm-meta-item clm-collections">
                            <span class="clm-meta-label"><?php _e('Collections:', 'choir-lyrics-manager'); ?></span>
                            <span class="clm-meta-value">
                                <?php 
                                $collection_links = array();
                                foreach ($collections as $collection) {
                                    $collection_links[] = '<a href="' . esc_url(get_term_link($collection)) . '">' . esc_html($collection->name) . '</a>';
                                }
                                echo implode(', ', $collection_links);
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </header>
        
        <?php if ($album->post_content): ?>
            <div class="clm-album-description">
                <?php echo apply_filters('the_content', $album->post_content); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($lyrics)): ?>
            <div class="clm-album-lyrics">
                <h3 class="clm-album-lyrics-title"><?php _e('Lyrics in this Album', 'choir-lyrics-manager'); ?></h3>
                
                <?php foreach ($lyrics as $index => $lyric): ?>
                    <div class="clm-album-lyric-item">
                        <h4 class="clm-album-lyric-title">
                            <a href="<?php echo get_permalink($lyric->ID); ?>"><?php echo esc_html($lyric->post_title); ?></a>
                        </h4>
                        
                        <div class="clm-album-lyric-meta">
                            <?php 
                            $composer = get_post_meta($lyric->ID, '_clm_composer', true);
                            if ($composer) {
                                echo '<span class="clm-album-lyric-composer">' . __('Composer: ', 'choir-lyrics-manager') . esc_html($composer) . '</span> | ';
                            }
                            
                            $language = get_post_meta($lyric->ID, '_clm_language', true);
                            if ($language) {
                                echo '<span class="clm-album-lyric-language">' . __('Language: ', 'choir-lyrics-manager') . esc_html($language) . '</span>';
                            }
                            ?>
                        </div>
                        
                        <?php if ($show_media): ?>
                            <div class="clm-album-lyric-media">
                                <?php
                                $audio_file_id = get_post_meta($lyric->ID, '_clm_audio_file_id', true);
                                if ($audio_file_id):
                                    $audio_url = wp_get_attachment_url($audio_file_id);
                                ?>
                                    <div class="clm-album-lyric-audio">
                                        <?php echo wp_audio_shortcode(array('src' => $audio_url)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="clm-album-lyric-actions">
                            <a href="<?php echo get_permalink($lyric->ID); ?>" class="clm-button clm-button-small"><?php _e('View Lyric', 'choir-lyrics-manager'); ?></a>
                            
                            <?php if (is_user_logged_in()): ?>
                                <?php
                                // Show add to playlist button
                                $playlists = new CLM_Playlists('choir-lyrics-manager', CLM_VERSION);
                                echo $playlists->render_playlist_dropdown($lyric->ID);
                                ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="clm-notice"><?php _e('This album is empty.', 'choir-lyrics-manager'); ?></p>
        <?php endif; ?>
    </article>
</div>

<?php
get_footer();