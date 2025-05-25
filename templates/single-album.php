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
// $album, $album_id, and $lyrics might be passed from CLM_Albums::album_shortcode_output()
$show_details = isset($atts) && isset($atts['show_details']) ? $atts['show_details'] === 'yes' : true;
$show_media = isset($atts) && isset($atts['show_media']) ? $atts['show_media'] === 'yes' : true;

// If this is a direct template call (e.g., single-clm_album.php), get the post
if (!isset($album_post) && !isset($album)) { // Check for both possible variable names
    global $post;
    $album_post = $post; // Use $album_post to be consistent with shortcode data prep
    $album_id = $post->ID;
} elseif (isset($album) && !isset($album_id)) { // If $album is passed
    $album_id = $album->ID;
    $album_post = $album;
} elseif (isset($album_post) && !isset($album_id)) { // If $album_post is passed
    $album_id = $album_post->ID;
}


// If $lyrics is not already populated (e.g., by the shortcode handler)
if (!isset($lyrics) || empty($lyrics)) {
    // Fetch lyrics using the CORRECT meta key
    $lyric_ids_from_meta = get_post_meta($album_id, '_clm_album_lyric_ids', true); // CORRECTED META KEY

    $lyrics_data_for_template = array(); // Use a different variable name to avoid confusion if $lyrics was passed

    if (is_array($lyric_ids_from_meta) && !empty($lyric_ids_from_meta)) {
        // Query the lyrics based on the stored IDs, preserving order if $lyric_ids_from_meta is ordered.
        $track_args = array(
            'post_type'      => 'clm_lyric', // Assuming your lyric CPT slug
            'post__in'       => $lyric_ids_from_meta,
            'orderby'        => 'post__in', // To maintain the order from $lyric_ids_from_meta
            'posts_per_page' => -1,
            'post_status'    => 'publish', // Only fetch published lyrics
        );
        $tracks_query = new WP_Query( $track_args );
        if ( $tracks_query->have_posts() ) {
            while ( $tracks_query->have_posts() ) {
                $tracks_query->the_post();
                // Store WP_Post objects or specific data
                $lyrics_data_for_template[] = $tracks_query->post; // Store the full post object
            }
            wp_reset_postdata();
        }
    }
    // If $lyrics was not passed, use the data we just fetched
    if (!isset($lyrics)) {
        $lyrics = $lyrics_data_for_template;
    }
}


// Fetch other album details using the correct meta keys
$tagline        = get_post_meta($album_id, '_clm_album_tagline', true);
$release_date   = get_post_meta($album_id, '_clm_album_release_date', true); // Textual display date
$release_year   = get_post_meta($album_id, '_clm_album_release_year', true); // Numeric year for logic/filtering
$director       = get_post_meta($album_id, '_clm_director', true);


get_header();
?>

<div class="clm-container">
    <article id="album-<?php echo esc_attr($album_id); ?>" class="clm-album">
        <header class="clm-album-header">
            <h1 class="clm-album-title"><?php echo get_the_title($album_id); ?></h1>

            <?php if ($show_details): ?>
                <div class="clm-album-meta">
                    <?php if ($tagline): ?>
                        <p class="clm-album-tagline"><em><?php echo esc_html($tagline); ?></em></p>
                    <?php endif; ?>

                    <?php if ($release_date): // Use the textual release_date for display ?>
                        <div class="clm-meta-item clm-release-year"> 
                            <span class="clm-meta-label"><?php _e('Released:', 'choir-lyrics-manager'); ?></span>
                            <span class="clm-meta-value"><?php echo esc_html($release_date); ?></span>
                        </div>
                    <?php elseif ($release_year) : // Fallback to year if full date not present ?>
                         <div class="clm-meta-item clm-release-year">
                            <span class="clm-meta-label"><?php _e('Release Year:', 'choir-lyrics-manager'); ?></span>
                            <span class="clm-meta-value"><?php echo esc_html($release_year); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($director): ?>
                        <div class="clm-meta-item clm-director">
                            <span class="clm-meta-label"><?php _e('Director/Conductor:', 'choir-lyrics-manager'); ?></span>
                            <span class="clm-meta-value"><?php echo esc_html($director); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php
                    $collections = get_the_terms($album_id, 'clm_collection');
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

        <?php
        // Use $album_post for content if available, otherwise current global $post
        $album_content_source = isset($album_post) ? $album_post : $GLOBALS['post'];
        if ($album_content_source && !empty($album_content_source->post_content)): ?>
            <div class="clm-album-description">
                <?php echo apply_filters('the_content', $album_content_source->post_content); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($lyrics)): ?>
            <div class="clm-album-lyrics">
                <h3 class="clm-album-lyrics-title"><?php _e('Tracks in this Album', 'choir-lyrics-manager'); ?></h3> 

                <?php foreach ($lyrics as $lyric_post_object): // Assuming $lyrics now contains WP_Post objects ?>
                    <div class="clm-album-lyric-item">
                        <h4 class="clm-album-lyric-title">
                            <a href="<?php echo get_permalink($lyric_post_object->ID); ?>"><?php echo esc_html($lyric_post_object->post_title); ?></a>
                        </h4>

                        <div class="clm-album-lyric-meta">
                            <?php
                            $composer = get_post_meta($lyric_post_object->ID, '_clm_composer', true);
                            if ($composer) {
                                echo '<span class="clm-album-lyric-composer">' . __('Composer: ', 'choir-lyrics-manager') . esc_html($composer) . '</span> | ';
                            }

                            $language = get_post_meta($lyric_post_object->ID, '_clm_language', true); // This should be a term, not direct meta ideally
                            if ($language) { // If it's direct meta
                                echo '<span class="clm-album-lyric-language">' . __('Language: ', 'choir-lyrics-manager') . esc_html($language) . '</span>';
                            }
                            // If 'clm_language' is a taxonomy:
                            // $lang_terms = get_the_terms($lyric_post_object->ID, 'clm_language');
                            // if ($lang_terms && !is_wp_error($lang_terms)) {
                            // echo '<span class="clm-album-lyric-language">' . __('Language: ', 'choir-lyrics-manager') . esc_html($lang_terms[0]->name) . '</span>';
                            // }
                            ?>
                        </div>

                        <?php if ($show_media): ?>
                            <div class="clm-album-lyric-media">
                                <?php
                                $audio_file_id = get_post_meta($lyric_post_object->ID, '_clm_audio_file_id', true);
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
                            <a href="<?php echo get_permalink($lyric_post_object->ID); ?>" class="clm-button clm-button-small"><?php _e('View Lyric', 'choir-lyrics-manager'); ?></a>

                            <?php if (is_user_logged_in()): ?>
                                <?php
                                if (class_exists('CLM_Playlists')) {
                                    $playlists_manager = new CLM_Playlists('choir-lyrics-manager', defined('CLM_VERSION') ? CLM_VERSION : '1.0.0');
                                    echo $playlists_manager->render_playlist_dropdown($lyric_post_object->ID);
                                }
                                ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="clm-notice"><?php _e('This album currently has no tracks assigned or published.', 'choir-lyrics-manager'); ?></p>
        <?php endif; ?>
    </article>
</div>

<?php
get_footer();