<?php
/**
 * Template for displaying single CLM Lyrics with Practice and Skill widgets.
 *
 * @package    Choir_Lyrics_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

get_header();

$lyric_id = get_the_ID();
$lyric_post = get_post($lyric_id);

//error_log("--- MEDIA DEBUG (single-clm_lyric.php) --- Lyric ID: " . $lyric_id . " ---");

// Get media status
$media_status = array('has_any_media' => false); // Default
if (class_exists('CLM_Media_Helper') && method_exists('CLM_Media_Helper', 'get_lyric_media_status')) {
    $media_status = CLM_Media_Helper::get_lyric_media_status($lyric_id);
 //   error_log("MEDIA DEBUG: Media Status from Helper: " . print_r($media_status, true));
} else {
 //   error_log("MEDIA DEBUG ERROR: CLM_Media_Helper class or get_lyric_media_status method not available.");
}

// Instantiate helper classes (as before)
$_clm_plugin_name = 'choir-lyrics-manager';
$_clm_plugin_version = defined('CLM_VERSION') ? CLM_VERSION : '1.0.0';
$settings_instance = (class_exists('CLM_Settings')) ? new CLM_Settings($_clm_plugin_name, $_clm_plugin_version) : null;
$practice_instance = (class_exists('CLM_Practice')) ? new CLM_Practice($_clm_plugin_name, $_clm_plugin_version) : null;
$skills_instance = (class_exists('CLM_Skills')) ? new CLM_Skills($_clm_plugin_name, $_clm_plugin_version) : null;
$playlists_instance = (class_exists('CLM_Playlists')) ? new CLM_Playlists($_clm_plugin_name, $_clm_plugin_version) : null;

// Get relevant settings
$show_details_setting = ($settings_instance && method_exists($settings_instance, 'get_setting')) ? $settings_instance->get_setting('show_lyric_details', true) : true; // Assuming a setting key
$show_difficulty_setting = ($settings_instance && method_exists($settings_instance, 'get_setting')) ? $settings_instance->get_setting('show_difficulty', true) : true;
$show_composer_setting = ($settings_instance && method_exists($settings_instance, 'get_setting')) ? $settings_instance->get_setting('show_composer', true) : true; // Already had this
$enable_practice_setting = ($settings_instance && method_exists($settings_instance, 'get_setting')) ? $settings_instance->get_setting('enable_practice', true) : true;

// Prepare metadata items (from your original snippet)
$meta_items = array();
if ($show_details_setting) { // Only gather if details are to be shown
    if ($show_composer_setting) {
        $composer = get_post_meta($lyric_id, '_clm_composer', true);
        if ($composer) {
            $meta_items['composer'] = array('label' => __('Composer', 'choir-lyrics-manager'), 'value' => $composer, 'icon' => 'businessman'); // Changed icon for composer
        }
        $arranger = get_post_meta($lyric_id, '_clm_arranger', true);
        if ($arranger) {
            $meta_items['arranger'] = array('label' => __('Arranger', 'choir-lyrics-manager'), 'value' => $arranger, 'icon' => 'edit');
        }
    }
    $year = get_post_meta($lyric_id, '_clm_year', true);
    if ($year) {
        $meta_items['year'] = array('label' => __('Year', 'choir-lyrics-manager'), 'value' => $year, 'icon' => 'calendar-alt'); // calendar-alt is better
    }

    // Language - fetch from taxonomy first, then meta as fallback
    $language_display = '';
    $language_terms = get_the_terms($lyric_id, 'clm_language');
    if ($language_terms && !is_wp_error($language_terms)) {
        $lang_names = array();
        foreach ($language_terms as $term) { $lang_names[] = esc_html($term->name); }
        $language_display = implode(', ', $lang_names);
    } else {
        $language_meta = get_post_meta($lyric_id, '_clm_language', true); // Assuming old meta key
        if ($language_meta) $language_display = $language_meta;
    }
    if ($language_display) {
        $meta_items['language'] = array('label' => __('Language', 'choir-lyrics-manager'), 'value' => $language_display, 'icon' => 'translation');
    }

    $genres = get_the_terms($lyric_id, 'clm_genre');
    if ($genres && !is_wp_error($genres)) {
        $genre_links = array();
        foreach ($genres as $genre) { $genre_links[] = '<a href="' . esc_url(get_term_link($genre)) . '">' . esc_html($genre->name) . '</a>'; }
        $meta_items['genres'] = array('label' => __('Genres', 'choir-lyrics-manager'), 'value' => implode(', ', $genre_links), 'icon' => 'tag', 'is_html' => true);
    }

    if ($show_difficulty_setting) {
        $difficulty = get_post_meta($lyric_id, '_clm_difficulty', true);
        if ($difficulty) {
            $stars = '';
            for ($i = 1; $i <= 5; $i++) {
                $stars .= '<span class="dashicons dashicons-star-' . ($i <= $difficulty ? 'filled' : 'empty') . '"></span>';
            }
            $meta_items['difficulty'] = array('label' => __('Difficulty', 'choir-lyrics-manager'), 'value' => $stars, 'icon' => 'chart-bar', 'is_html' => true);
        }
    }
}
$has_detailed_meta = !empty($meta_items);
$show_media_section = isset($atts) && isset($atts['show_media']) ? ($atts['show_media'] === 'yes') : true;

?>

<div id="primary" class="content-area clm-single-lyric-page-container">
    <main id="main" class="site-main clm-container" role="main">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <article id="lyric-<?php echo esc_attr($lyric_id); ?>" <?php post_class('clm-lyric-article'); ?>>
                <header class="clm-lyric-header">
                    <h1 class="clm-lyric-title"><?php the_title(); ?></h1>
                    
                    <div class="clm-lyric-header-meta-summary">
                        <?php // Display a few key meta items directly if needed, e.g., composer
                        if ($show_composer_setting && isset($meta_items['composer'])) {
                            echo '<span class="clm-header-meta-item clm-header-composer"><span class="dashicons dashicons-'.esc_attr($meta_items['composer']['icon']).'"></span> ' . esc_html($meta_items['composer']['value']) . '</span>';
                        }
                        if (isset($meta_items['language'])) {
                             echo '<span class="clm-header-meta-item clm-header-language"><span class="dashicons dashicons-'.esc_attr($meta_items['language']['icon']).'"></span> ' . esc_html(strip_tags($meta_items['language']['value'])) . '</span>'; // strip_tags for safety if lang was HTML
                        }
                        ?>
                    </div>

                    <?php if ($show_details_setting && $has_detailed_meta) : ?>
                        <button class="clm-toggle-button" data-target="#clm-detailed-meta-section" aria-expanded="false">
                            <span class="clm-toggle-text-show"><?php _e('Show Details', 'choir-lyrics-manager'); ?></span>
                            <span class="clm-toggle-text-hide" style="display:none;"><?php _e('Hide Details', 'choir-lyrics-manager'); ?></span>
                            <span class="dashicons dashicons-arrow-down-alt2 clm-toggle-icon"></span>
                        </button>
                    <?php endif; ?>
                </header>

                <?php if ($show_details_setting && $has_detailed_meta) : ?>
                <section id="clm-detailed-meta-section" class="clm-detailed-meta" style="display: none;" aria-hidden="true">
                    <div class="clm-meta-grid">
                        <?php foreach ($meta_items as $key => $item): ?>
                            <div class="clm-meta-item clm-meta-<?php echo esc_attr($key);?>">
                                <div class="clm-meta-icon">
                                    <span class="dashicons dashicons-<?php echo esc_attr($item['icon']); ?>"></span>
                                </div>
                                <div class="clm-meta-content">
                                    <span class="clm-meta-label"><?php echo esc_html($item['label']); ?></span>
                                    <?php if (isset($item['is_html']) && $item['is_html']): ?>
                                        <span class="clm-meta-value"><?php echo wp_kses_post($item['value']); // Use wp_kses_post for HTML values ?></span>
                                    <?php else: ?>
                                        <span class="clm-meta-value"><?php echo esc_html($item['value']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>


                <div class="clm-lyric-main-content">
                    <div class="clm-lyric-text-content">
                        <div class="clm-lyric-content">
                            <?php the_content(); ?>
                        </div>
                         <?php
                        $performance_notes = get_post_meta($lyric_id, '_clm_performance_notes', true);
                        if (!empty($performance_notes)): ?>
                            <div class="clm-performance-notes">
                                <h3 class="clm-subheading"><?php _e('Performance Notes', 'choir-lyrics-manager'); ?></h3>
                                <div class="clm-performance-notes-content">
                                    <?php echo wpautop(esc_html($performance_notes)); // esc_html then wpautop if notes are plain text
                                          // or just wpautop(wp_kses_post($performance_notes)) if notes can contain some HTML
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <aside class="clm-lyric-sidebar">
                        <?php
                        // Skill Status Widget
                        if ($skills_instance && method_exists($skills_instance, 'render_skill_widget') && is_user_logged_in()) {
                            echo '<h2>' . __('Your Progress', 'choir-lyrics-manager') . '</h2>';
                            echo $skills_instance->render_skill_widget($lyric_id);
                        }

                        // Practice Tracking Widget
                        if ($enable_practice_setting && $practice_instance && method_exists($practice_instance, 'render_practice_widget') && is_user_logged_in()) {
                            echo '<h2>' . __('Practice Log', 'choir-lyrics-manager') . '</h2>';
                            echo $practice_instance->render_practice_widget($lyric_id);
                        }

                        // Playlist Actions
                        if ($playlists_instance && method_exists($playlists_instance, 'render_playlist_dropdown') && is_user_logged_in()) {
                             echo '<h2>' . __('Playlists', 'choir-lyrics-manager') . '</h2>';
                            echo $playlists_instance->render_playlist_dropdown($lyric_id);
                        }
                        ?>
                    </aside>
                </div>

                                <?php // --- MEDIA SECTION ---
                if ($show_media_section && !empty($media_status) && $media_status['has_any_media']) :
                    //error_log("MEDIA DEBUG: ENTERING MEDIA DISPLAY SECTION.");
                ?>
                    <div class="clm-media-display-section">
                        <h2 class="clm-section-title"><?php _e('Media Resources', 'choir-lyrics-manager'); ?></h2>
                        <div class="clm-media-grid">
                            <?php
                            // Sheet Music
                            $sheet_music_id = get_post_meta($lyric_id, '_clm_sheet_music_id', true);
                            //error_log("MEDIA DEBUG: Sheet Music ID from meta: " . $sheet_music_id . " | Media Status Sheet: " . (!empty($media_status['sheet']) ? 'true' : 'false'));
                            if (!empty($media_status['sheet']) && !empty($sheet_music_id) && $sheet_music_id !== '0' && is_numeric($sheet_music_id)) : // Check $media_status['sheet'] directly
                                //error_log("MEDIA DEBUG: Rendering Sheet Music.");
                                $sheet_music_url = wp_get_attachment_url($sheet_music_id);
                                $sheet_music_filename = basename(get_attached_file($sheet_music_id));
                            ?>
                                <div class="clm-media-item clm-media-sheet-music">
                                    <h3 class="clm-media-item-title"><span class="dashicons dashicons-media-document"></span> <?php _e('Sheet Music', 'choir-lyrics-manager'); ?></h3>
                                    <a href="<?php echo esc_url($sheet_music_url); ?>" target="_blank" class="clm-button">
                                        <?php echo esc_html($sheet_music_filename ?: __('Download Sheet Music', 'choir-lyrics-manager')); ?>
                                    </a>
                                </div>
                            <?php endif; // End Sheet Music ?>

                            <?php
                            // Main Audio File
                            $audio_file_id = get_post_meta($lyric_id, '_clm_audio_file_id', true);
                            //error_log("MEDIA DEBUG: Main Audio ID from meta: " . $audio_file_id . " | Media Status Audio: " . (!empty($media_status['audio']) ? 'true' : 'false') . " | Practice Tracks Exist: " . (!empty($media_status['practice_tracks']) ? 'true' : 'false'));
                            // Condition: show if audio flag is true AND an audio_file_id exists.
                            // The 'audio' flag in $media_status is true if either main audio or practice tracks exist.
                            // So, we must check for $audio_file_id specifically if we only want to show the main one here.
                            if (!empty($audio_file_id) && $audio_file_id !== '0' && is_numeric($audio_file_id)) : // Only if a specific main audio ID exists
                                //error_log("MEDIA DEBUG: Rendering Main Audio File.");
                                $audio_url = wp_get_attachment_url($audio_file_id);
                                //error_log("MEDIA DEBUG: Audio URL for ID " . $audio_file_id . ": " . $audio_url);
                                if ($audio_url) :
                            ?>
                                    <div class="clm-media-item clm-media-audio">
                                        <h3 class="clm-media-item-title"><span class="dashicons dashicons-format-audio"></span> <?php _e('Audio Recording', 'choir-lyrics-manager'); ?></h3>
                                        <?php echo wp_audio_shortcode(array('src' => $audio_url)); ?>
                                    </div>
                                <?php else: //error_log("MEDIA DEBUG: No audio URL found for Main Audio ID " . $audio_file_id . ". Not outputting player.");
                                endif; // End if $audio_url
                            else:
                                // Log why main audio is skipped if it's not just because practice_tracks exist
                                if (empty($audio_file_id) || $audio_file_id === '0' || !is_numeric($audio_file_id)) {
                                     //error_log("MEDIA DEBUG: SKIPPING Main Audio File because no valid _clm_audio_file_id was found.");
                                } else {
                                     // This case should not be hit if the above 'if' is structured correctly.
                                     // error_log("MEDIA DEBUG: SKIPPING Main Audio File due to other conditions.");
                                }
                            endif; // End Main Audio File ?>

                            <?php
                            // Video Embed
                            $video_embed_meta = get_post_meta($lyric_id, '_clm_video_embed', true);
                            //error_log("MEDIA DEBUG: Video Embed Meta from DB: " . $video_embed_meta . " | Media Status Video: " . (!empty($media_status['video']) ? 'true' : 'false'));
                            if (!empty($media_status['video']) && !empty($video_embed_meta) && class_exists('CLM_Media_Helper') && method_exists('CLM_Media_Helper', 'render_video_for_display')) :
                                //error_log("MEDIA DEBUG: Rendering Video.");
                                $video_html = CLM_Media_Helper::render_video_for_display($video_embed_meta);
                                //error_log("MEDIA DEBUG: Video HTML from helper: " . $video_html);
                                if(!empty(trim($video_html))):
                            ?>
                                    <div class="clm-media-item clm-media-video">
                                        <h3 class="clm-media-item-title"><span class="dashicons dashicons-format-video"></span> <?php _e('Video', 'choir-lyrics-manager'); ?></h3>
                                        <div class="clm-video-embed-container">
                                            <?php echo $video_html; ?>
                                        </div>
                                    </div>
                                <?php endif; // End if !empty(trim($video_html)) ?>
                            <?php endif; // End Video Embed ?>

                            <?php
                            // MIDI File
                            $midi_file_id = get_post_meta($lyric_id, '_clm_midi_file_id', true);
                            //error_log("MEDIA DEBUG: MIDI ID from meta: " . $midi_file_id . " | Media Status MIDI: " . (!empty($media_status['midi']) ? 'true' : 'false'));
                            if (!empty($media_status['midi']) && !empty($midi_file_id) && $midi_file_id !== '0' && is_numeric($midi_file_id)) :
                                //error_log("MEDIA DEBUG: Rendering MIDI.");
                                $midi_url = wp_get_attachment_url($midi_file_id);
                                $midi_filename = basename(get_attached_file($midi_file_id));
                                if ($midi_url):
                            ?>
                                <div class="clm-media-item clm-media-midi">
                                    <h3 class="clm-media-item-title"><span class="dashicons dashicons-controls-volumeon"></span> <?php _e('MIDI File', 'choir-lyrics-manager'); ?></h3>
                                    <a href="<?php echo esc_url($midi_url); ?>" target="_blank" class="clm-button">
                                        <?php echo esc_html($midi_filename ?: __('Download MIDI', 'choir-lyrics-manager')); ?>
                                    </a>
                                </div>
                                <?php endif; // End if $midi_url ?>
                            <?php endif; // End MIDI File ?>
                        </div> <!-- .clm-media-grid -->

                        <?php
                        // Practice Tracks
                        $practice_tracks = get_post_meta($lyric_id, '_clm_practice_tracks', true);
                        //error_log("MEDIA DEBUG: Practice Tracks from meta: " . print_r($practice_tracks, true) . " | Media Status Practice Tracks: " . (!empty($media_status['practice_tracks']) ? 'true' : 'false'));
                        if (!empty($media_status['practice_tracks']) && !empty($practice_tracks) && is_array($practice_tracks)) :
                            //error_log("MEDIA DEBUG: Rendering Practice Tracks section.");
                        ?>
                            <div class="clm-practice-tracks-section">
                                <h3 class="clm-section-subtitle"><?php _e('Practice Tracks', 'choir-lyrics-manager'); ?></h3>
                                <div class="clm-practice-tracks-grid">
                                    <?php foreach ($practice_tracks as $index => $track) : /* ... as before ... */ endforeach; ?>
                                </div>
                            </div>
                        <?php endif; // End Practice Tracks ?>
                    </div> <!-- .clm-media-display-section -->
                <?php else: //error_log("MEDIA DEBUG: SKIPPING Media Display Section. ShowMedia: ".($show_media_section?'T':'F')." HasAnyMedia: ".(!empty($media_status) && $media_status['has_any_media']?'T':'F') ); ?>
                <?php endif; // End Media Section ($show_media_section && $media_status['has_any_media']) ?>

                <footer class="clm-lyric-footer">
                    <?php /* ... your genres and other footer content ... */ ?>
                </footer>
            </article>
        <?php endwhile; endif; ?>
    </main>
</div>
<?php get_footer(); ?>