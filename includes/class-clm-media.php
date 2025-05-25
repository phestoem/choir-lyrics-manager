<?php
/**
 * Media handling functionality for the plugin.
 *
 * @since      1.0.0
 * @package    Choir_Lyrics_Manager
 */

class CLM_Media {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

     public function add_media_meta_boxes() {
        add_meta_box(
            'clm_media_files',
            __('Media Attachments', 'choir-lyrics-manager'), // Renamed title slightly
            array($this, 'render_media_meta_box'),
            'clm_lyric',
            'normal',
            'high'
        );
    }

    public function render_media_meta_box($post) {
        wp_nonce_field('clm_media_meta_box', 'clm_media_meta_box_nonce');
        
        $sheet_music_id = get_post_meta($post->ID, '_clm_sheet_music_id', true);
        $audio_file_id = get_post_meta($post->ID, '_clm_audio_file_id', true);
        $video_embed = get_post_meta($post->ID, '_clm_video_embed', true); // This will be the shortcode or raw embed
        $midi_file_id = get_post_meta($post->ID, '_clm_midi_file_id', true);
        $practice_tracks = get_post_meta($post->ID, '_clm_practice_tracks', true);
        if (!is_array($practice_tracks)) $practice_tracks = array();

        // For video preview, we process the saved shortcode/embed
        $video_display_html = '';
        if (!empty($video_embed) && class_exists('CLM_Media_Helper') && method_exists('CLM_Media_Helper', 'render_video_for_display')) {
            $video_display_html = CLM_Media_Helper::render_video_for_display($video_embed);
        }
        ?>
        <div class="clm-meta-box-container">
            <!-- Sheet Music -->
            <h4><?php _e('Sheet Music', 'choir-lyrics-manager'); ?></h4>
            <div class="clm-media-field">
                <input type="hidden" id="clm_sheet_music_id_field" name="clm_sheet_music_id_field" value="<?php echo esc_attr($sheet_music_id); ?>">
                <button type="button" class="button clm-upload-media" data-field-id="clm_sheet_music_id_field" data-preview-id="clm-sheet-music-preview" data-media-type="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/*" data-title="<?php esc_attr_e('Select Sheet Music', 'choir-lyrics-manager'); ?>"><?php _e('Select Sheet Music', 'choir-lyrics-manager'); ?></button>
                <button type="button" class="button clm-remove-media" data-field-id="clm_sheet_music_id_field" data-preview-id="clm-sheet-music-preview" <?php echo empty($sheet_music_id) ? 'style="display:none;"' : ''; ?>><?php _e('Remove', 'choir-lyrics-manager'); ?></button>
                <div class="clm-media-preview" id="clm-sheet-music-preview">
                    <?php if ($sheet_music_id) echo $this->render_attachment_preview($sheet_music_id); ?>
                </div>
            </div>

            <!-- Audio Recording -->
            <h4><?php _e('Main Audio Recording', 'choir-lyrics-manager'); ?></h4>
            <div class="clm-media-field">
                <input type="hidden" id="clm_audio_file_id_field" name="clm_audio_file_id_field" value="<?php echo esc_attr($audio_file_id); ?>">
                <button type="button" class="button clm-upload-media" data-field-id="clm_audio_file_id_field" data-preview-id="clm-audio-file-preview" data-media-type="audio" data-title="<?php esc_attr_e('Select Audio File', 'choir-lyrics-manager'); ?>"><?php _e('Select Audio', 'choir-lyrics-manager'); ?></button>
                <button type="button" class="button clm-remove-media" data-field-id="clm_audio_file_id_field" data-preview-id="clm-audio-file-preview" <?php echo empty($audio_file_id) ? 'style="display:none;"' : ''; ?>><?php _e('Remove', 'choir-lyrics-manager'); ?></button>
                <div class="clm-media-preview" id="clm-audio-file-preview">
                     <?php if ($audio_file_id) echo $this->render_attachment_preview($audio_file_id, true); ?>
                </div>
            </div>

            <!-- Video Embed -->
            <h4><?php _e('Video URL or Embed Code', 'choir-lyrics-manager'); ?></h4>
            <div class="clm-media-field">
                <textarea id="clm_video_embed_field" name="clm_video_embed_field" class="widefat" rows="3" placeholder="<?php esc_attr_e('Paste YouTube/Vimeo URL or full embed code', 'choir-lyrics-manager'); ?>"><?php echo esc_textarea($video_embed); // Display the raw saved value (shortcode or embed) ?></textarea>
                <?php if (!empty($video_display_html)): ?>
                    <div class="clm-video-preview-admin" style="margin-top:10px; max-width: 400px;">
                        <strong><?php _e('Preview:','choir-lyrics-manager');?></strong>
                        <?php echo $video_display_html; // Processed output for preview ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- MIDI File -->
            <h4><?php _e('MIDI File', 'choir-lyrics-manager'); ?></h4>
            <div class="clm-media-field">
                 <input type="hidden" id="clm_midi_file_id_field" name="clm_midi_file_id_field" value="<?php echo esc_attr($midi_file_id); ?>">
                <button type="button" class="button clm-upload-media" data-field-id="clm_midi_file_id_field" data-preview-id="clm-midi-file-preview" data-media-type="audio/midi,audio/mid" data-title="<?php esc_attr_e('Select MIDI File', 'choir-lyrics-manager'); ?>"><?php _e('Select MIDI', 'choir-lyrics-manager'); ?></button>
                <button type="button" class="button clm-remove-media" data-field-id="clm_midi_file_id_field" data-preview-id="clm-midi-file-preview" <?php echo empty($midi_file_id) ? 'style="display:none;"' : ''; ?>><?php _e('Remove', 'choir-lyrics-manager'); ?></button>
                <div class="clm-media-preview" id="clm-midi-file-preview">
                    <?php if ($midi_file_id) echo $this->render_attachment_preview($midi_file_id); ?>
                </div>
            </div>
            
            <!-- Practice Tracks (Repeater) -->
            <h4><?php _e('Practice Tracks (by Part)', 'choir-lyrics-manager'); ?></h4>
            <div id="clm-practice-tracks-container">
                <?php if (!empty($practice_tracks)): foreach ($practice_tracks as $index => $track): if (empty($track['id'])) continue; ?>
                    <div class="clm-practice-track-item">
                        <input type="text" name="clm_practice_tracks[<?php echo $index; ?>][title]" value="<?php echo esc_attr($track['title']); ?>" placeholder="<?php esc_attr_e('Track Title (e.g. Soprano)', 'choir-lyrics-manager'); ?>" class="regular-text clm-track-title-input">
                        <input type="hidden" name="clm_practice_tracks[<?php echo $index; ?>][id]" value="<?php echo esc_attr($track['id']); ?>" class="clm-track-id-input">
                        <button type="button" class="button clm-upload-practice-track" data-title="<?php esc_attr_e('Select Practice Audio', 'choir-lyrics-manager'); ?>"><?php _e('Change Audio', 'choir-lyrics-manager'); ?></button>
                        <button type="button" class="button button-link clm-remove-practice-track"><?php _e('Remove Track', 'choir-lyrics-manager'); ?></button>
                        <div class="clm-media-preview clm-practice-track-preview">
                            <?php echo $this->render_attachment_preview($track['id'], true); ?>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
            <button type="button" class="button clm-add-practice-track" style="margin-top:10px;"><?php _e('+ Add Practice Track', 'choir-lyrics-manager'); ?></button>
        </div>

        <script type="text/html" id="tmpl-clm-practice-track-item">
            <div class="clm-practice-track-item">
                <input type="text" name="clm_practice_tracks[{{data.index}}][title]" value="" placeholder="<?php esc_attr_e('Track Title (e.g. Soprano)', 'choir-lyrics-manager'); ?>" class="regular-text clm-track-title-input">
                <input type="hidden" name="clm_practice_tracks[{{data.index}}][id]" value="" class="clm-track-id-input">
                <button type="button" class="button clm-upload-practice-track" data-title="<?php esc_attr_e('Select Practice Audio', 'choir-lyrics-manager'); ?>"><?php _e('Select Audio', 'choir-lyrics-manager'); ?></button>
                <button type="button" class="button button-link clm-remove-practice-track"><?php _e('Remove Track', 'choir-lyrics-manager'); ?></button>
                <div class="clm-media-preview clm-practice-track-preview"></div>
            </div>
        </script>
        <?php
    }

    /**
     * Helper to render preview for an attachment in meta box.
     */
    private function render_attachment_preview($attachment_id, $with_player = false) {
        if (empty($attachment_id)) return '';
        $url = wp_get_attachment_url($attachment_id);
        $filename = basename(get_attached_file($attachment_id));
        $filetype = wp_check_filetype($url);
        $icon = 'dashicons-media-default';

        if ($filetype) {
            switch ($filetype['ext']) {
                case 'pdf': $icon = 'dashicons-pdf'; break;
                case 'doc': case 'docx': $icon = 'dashicons-media-document'; break;
                case 'mp3': case 'wav': case 'ogg': case 'mid': case 'midi': $icon = 'dashicons-format-audio'; break;
            }
        }
        
        $output = '<div class="clm-media-item">';
        $output .= '<span class="dashicons ' . esc_attr($icon) . '"></span> ';
        $output .= '<a href="' . esc_url($url) . '" target="_blank">' . esc_html($filename) . '</a>';
        if ($with_player && strpos($filetype['type'], 'audio') === 0) {
            $output .= '<div class="clm-audio-player" style="margin-top:5px;">' . wp_audio_shortcode(array('src' => $url)) . '</div>';
        }
        $output .= '</div>';
        return $output;
    }


    public function save_media_meta($post_id) {
        if (!isset($_POST['clm_media_meta_box_nonce']) || !wp_verify_nonce($_POST['clm_media_meta_box_nonce'], 'clm_media_meta_box')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if (get_post_type($post_id) !== 'clm_lyric') return; // Ensure it's our CPT

        // Save individual media files
        $fields_to_save_int = array(
            '_clm_sheet_music_id' => 'clm_sheet_music_id_field',
            '_clm_audio_file_id'  => 'clm_audio_file_id_field',
            '_clm_midi_file_id'   => 'clm_midi_file_id_field',
        );
        foreach ($fields_to_save_int as $meta_key => $post_field_name) {
            if (isset($_POST[$post_field_name])) {
                update_post_meta($post_id, $meta_key, intval($_POST[$post_field_name]));
            }
        }
        
		// In CLM_Media::save_media_meta()
		//if (isset($_POST['clm_video_embed_field'])) { // Assuming your input field name
		//	$video_input = sanitize_textarea_field(wp_unslash($_POST['clm_video_embed_field']));
		//	$processed_video_embed = apply_filters('clm_save_video_embed', $video_input);
		//	update_post_meta($post_id, '_clm_video_embed', $processed_video_embed);
		//}
		
        // Save video embed (apply filter from CLM_Media_Helper)
        if (isset($_POST['clm_video_embed_field'])) {
            $raw_video_input = wp_unslash($_POST['clm_video_embed_field']);
			$video_input = sanitize_textarea_field(wp_unslash($_POST['clm_video_embed_field']));
            // Basic sanitization before filter if needed, but filter should handle specific conversions
            $processed_video_embed = apply_filters('clm_save_video_embed', $raw_video_input);
            update_post_meta($post_id, '_clm_video_embed', $processed_video_embed);
        } else {
            // If field is not sent, it could mean clear it, or it wasn't present in form.
            // For textarea, if empty, it will be sent as empty string.
            // delete_post_meta($post_id, '_clm_video_embed'); // Or update with empty string
        }

        // Process and save practice tracks
        $new_practice_tracks = array();
        if (isset($_POST['clm_practice_tracks']) && is_array($_POST['clm_practice_tracks'])) {
            foreach ($_POST['clm_practice_tracks'] as $track_input) {
                if (!empty($track_input['id']) && is_numeric($track_input['id'])) {
                    $new_practice_tracks[] = array(
                        'id'    => intval($track_input['id']),
                        'title' => sanitize_text_field(wp_unslash($track_input['title'])),
                    );
                }
            }
        }
        update_post_meta($post_id, '_clm_practice_tracks', $new_practice_tracks);
    }
}
?>