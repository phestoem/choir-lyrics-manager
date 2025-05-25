<?php
/**
 * Media helper functions for the plugin.
 *
 * @since      1.0.0
 * @package    Choir_Lyrics_Manager
 */

class CLM_Media_Helper {

    /**
     * Initialize the helper
     */
    public static function init() {
        // Filter applied when CLM_Media saves the video embed meta.
        // This converts known URLs to [clm_youtube] or [clm_vimeo] shortcodes.
        add_filter('clm_save_video_embed', array(__CLASS__, 'convert_url_to_video_shortcode_on_save'));

        // Optional: Allow iframes if you need to support raw iframe pastes beyond what WordPress allows by default for certain users.
        // Be cautious with this and consider user roles if enabling.
        // add_action('init', array(__CLASS__, 'conditionally_allow_iframe_tags'));
    }

    /**
     * Conditionally allow iframes in WordPress content for specific roles (example).
     */
    public static function conditionally_allow_iframe_tags() {
        if (current_user_can('publish_posts')) { // Example: Only for users who can publish posts
            global $allowedposttags;
            if (!isset($allowedposttags['iframe'])) {
                $allowedposttags['iframe'] = array(
                    'src' => true, 'width' => true, 'height' => true, 'frameborder' => true,
                    'allowfullscreen' => true, 'allow' => true, 'title' => true,
                    'style' => true, 'class' => true, 'loading' => true, 'referrerpolicy' => true,
                );
            }
        }
    }

    /**
     * Converts known video URLs (YouTube, Vimeo) to custom shortcodes when saving.
     * If not a recognized URL, returns the input as is (could be other URL, raw iframe, etc.).
     *
     * @param string $video_input The raw input from the video embed field.
     * @return string The processed string (shortcode or original input).
     */
    public static function convert_url_to_video_shortcode_on_save($video_input) {
        $video_input_trimmed = trim($video_input);

        // YouTube (various formats including shorts, live, embed, and nocookie)
        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:shorts/|live/|watch\?(?:.*&)?v=|embed/|v/)|youtu\.be/)([^"&?/ ]{11})%i', $video_input_trimmed, $matches)) {
            return '[clm_youtube id="' . esc_attr($matches[1]) . '"]';
        }
        // Vimeo (various formats including player URLs)
        if (preg_match('/vimeo\.com\/(?:video\/)?(?:player\.vimeo\.com\/video\/)?(\d+)/i', $video_input_trimmed, $matches)) {
            return '[clm_vimeo id="' . esc_attr($matches[1]) . '"]';
        }
        
        // If it wasn't a YouTube or Vimeo URL, return the original (trimmed) input.
        // Further processing/sanitization for raw iframes or other URLs happens at display time.
        return $video_input_trimmed;
    }

    /**
     * Renders the video for display on the frontend.
     * Handles [clm_youtube] and [clm_vimeo] shortcodes.
     * For other direct URLs, attempts wp_oembed_get.
     * For raw iframes, sanitizes and adds security parameters.
     *
     * @param string $saved_video_meta The meta value stored for the video.
     * @return string HTML output for the video.
     */
    /**
     * Renders the video for display on the frontend.
     * Handles [clm_youtube] and [clm_vimeo] shortcodes.
     * For other direct URLs, attempts wp_oembed_get.
     * For raw iframes, sanitizes and adds security parameters.
     *
     * @param string $saved_video_meta The meta value stored for the video.
     * @return string HTML output for the video.
     */
    public static function render_video_for_display($saved_video_meta) {
        //error_log("MEDIA_HELPER_VIDEO: Input video meta: " . $saved_video_meta);
        $output_html = ''; // Initialize to empty string
        $processed_meta = trim($saved_video_meta);

        if (empty($processed_meta)) {
            
            return '';
        }

        // Handle [clm_youtube] shortcode
        if (strpos($processed_meta, '[clm_youtube') === 0 && preg_match('/\[clm_youtube id="([^"]+)"\]/i', $processed_meta, $matches)) {
            $video_id = $matches[1];
            $youtube_url = 'https://www.youtube.com/embed/' . esc_attr($video_id);
            $youtube_url = add_query_arg(array('rel' => 0, 'modestbranding' => 1, 'showinfo' => 0, 'iv_load_policy' => 3, 'autoplay' => 0), $youtube_url);
            $output_html = '<div class="clm-video-wrapper is-youtube"><iframe width="560" height="315" src="' . esc_url($youtube_url) . '" title="' . esc_attr__('YouTube video player', 'choir-lyrics-manager') . '" frameborder="0" allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>';
        
        // Handle [clm_vimeo] shortcode
        } elseif (strpos($processed_meta, '[clm_vimeo') === 0 && preg_match('/\[clm_vimeo id="([^"]+)"\]/i', $processed_meta, $matches)) {
            $video_id = $matches[1];
            $vimeo_url = 'https://player.vimeo.com/video/' . esc_attr($video_id);
            $vimeo_url = add_query_arg(array('dnt' => 1, 'title' => 0, 'byline' => 0, 'portrait' => 0, 'autoplay' => 0), $vimeo_url);
            $output_html = '<div class="clm-video-wrapper is-vimeo"><iframe src="' . esc_url($vimeo_url) . '" width="640" height="360" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen title="' . esc_attr__('Vimeo video player', 'choir-lyrics-manager') . '" loading="lazy"></iframe></div>';
        
        // If it's a direct URL (and not a shortcode we processed)
        } elseif (filter_var($processed_meta, FILTER_VALIDATE_URL)) {
            $oembed_html_content = wp_oembed_get($processed_meta);
            if ($oembed_html_content) {
                $output_html = '<div class="clm-video-wrapper is-oembed">' . $oembed_html_content . '</div>';
            } else {
                // If oEmbed fails, provide a link as a fallback
                $output_html = '<p><a href="'.esc_url($processed_meta).'" target="_blank" rel="noopener noreferrer">'.sprintf(esc_html__('Watch video: %s', 'choir-lyrics-manager'), esc_html($processed_meta)).'</a></p>';
                // error_log("MEDIA_HELPER_VIDEO: oEmbed failed for URL: " . $processed_meta);
            }
        
        // If it's raw iframe code
        } elseif (strpos(strtolower($processed_meta), '<iframe') !== false) {
             $allowed_iframe_html = array( /* ... your allowed iframe attributes array from before ... */ );
             $modified_embed = preg_replace_callback( /* ... your YouTube iframe param adder ... */ ); // As before
             $output_html = '<div class="clm-video-wrapper is-raw-iframe">' . wp_kses($modified_embed, $allowed_iframe_html) . '</div>';
        
        } else {
            // If it's none of the above (e.g., just some unrecognized text)
            // error_log("MEDIA_HELPER_VIDEO: Unrecognized video meta format: " . $processed_meta);
            // $output_html remains '' (empty string)
        }

        // error_log("MEDIA_HELPER_VIDEO: Final HTML to be returned: " . $output_html);
        return $output_html;
    }

// error_log("CLM_DEBUG: class-clm-media-helper.php file was fully parsed and this line executed."); // Ensure this is AFTER the class closing brace

    /**
     * Get the media attachment status for a given lyric.
     *
     * @param int $lyric_id The ID of the lyric post.
     * @return array Associative array of media statuses.
     */
    public static function get_lyric_media_status($lyric_id) {
        // error_log("MEDIA_HELPER_STATUS: Checking media for lyric ID: " . $lyric_id);

        $audio_file_id = get_post_meta($lyric_id, '_clm_audio_file_id', true);
        $video_embed = get_post_meta($lyric_id, '_clm_video_embed', true);
        $sheet_music_id = get_post_meta($lyric_id, '_clm_sheet_music_id', true);
        $midi_file_id = get_post_meta($lyric_id, '_clm_midi_file_id', true);
        $practice_tracks_meta = get_post_meta($lyric_id, '_clm_practice_tracks', true);

        // error_log("MEDIA_HELPER_STATUS: Raw Meta - AudioID: {$audio_file_id}, VideoEmbed: [{$video_embed}], SheetID: {$sheet_music_id}, MidiID: {$midi_file_id}, PracticeTracks: " . print_r($practice_tracks_meta, true));

        // Ensure IDs are treated as integers for checks
        $audio_file_id = !empty($audio_file_id) ? intval($audio_file_id) : 0;
        $sheet_music_id = !empty($sheet_music_id) ? intval($sheet_music_id) : 0;
        $midi_file_id = !empty($midi_file_id) ? intval($midi_file_id) : 0;

        $has_audio = $audio_file_id > 0;
        $has_video = !empty(trim($video_embed)); // Video embed is a string
        $has_sheet = $sheet_music_id > 0;
        $has_midi = $midi_file_id > 0;
        $has_practice_tracks = !empty($practice_tracks_meta) && is_array($practice_tracks_meta) && count($practice_tracks_meta) > 0;

        // If practice tracks exist, consider that audio is available.
        if ($has_practice_tracks) {
            $has_audio = true;
        }

        $status = array(
            'audio' => $has_audio,
            'video' => $has_video,
            'sheet' => $has_sheet,
            'midi' => $has_midi,
            'practice_tracks' => $has_practice_tracks,
            'has_any_media' => ($has_audio || $has_video || $has_sheet || $has_midi)
        );
        // error_log("MEDIA_HELPER_STATUS: Calculated Status for Lyric ID {$lyric_id}: " . print_r($status, true));
        return $status;
    }
}

// error_log("CLM_DEBUG: class-clm-media-helper.php file was fully parsed and this line executed.");
?>