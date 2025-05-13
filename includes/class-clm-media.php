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

    /**
     * Register meta boxes for media files
     *
     * @since    1.0.0
     */
    public function add_media_meta_boxes() {
        add_meta_box(
            'clm_media_files',
            __('Media Files', 'choir-lyrics-manager'),
            array($this, 'render_media_meta_box'),
            'clm_lyric',
            'normal',
            'high'
        );
    }

    /**
     * Render media meta box
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_media_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('clm_media_meta_box', 'clm_media_meta_box_nonce');
        
        // Retrieve current values
        $sheet_music_id = get_post_meta($post->ID, '_clm_sheet_music_id', true);
        $audio_file_id = get_post_meta($post->ID, '_clm_audio_file_id', true);
        $video_embed = get_post_meta($post->ID, '_clm_video_embed', true);
        $midi_file_id = get_post_meta($post->ID, '_clm_midi_file_id', true);
        $practice_tracks = get_post_meta($post->ID, '_clm_practice_tracks', true);
        
        if (!is_array($practice_tracks)) {
            $practice_tracks = array();
        }
        
        // Meta box content
        ?>
        <div class="clm-meta-box-container">
            <h4><?php _e('Sheet Music', 'choir-lyrics-manager'); ?></h4>
            <div class="clm-media-field">
                <input type="hidden" id="clm_sheet_music_id" name="clm_sheet_music_id" value="<?php echo esc_attr($sheet_music_id); ?>">
                <button type="button" class="button clm-upload-media" data-type="sheet_music" data-title="<?php _e('Upload or Select Sheet Music', 'choir-lyrics-manager'); ?>"><?php _e('Upload/Select Sheet Music', 'choir-lyrics-manager'); ?></button>
                <button type="button" class="button clm-remove-media" data-type="sheet_music" <?php echo empty($sheet_music_id) ? 'style="display:none;"' : ''; ?>><?php _e('Remove', 'choir-lyrics-manager'); ?></button>
                <div class="clm-media-preview" id="clm-sheet-music-preview">
                    <?php if ($sheet_music_id) : ?>
                        <?php
                        $sheet_music_url = wp_get_attachment_url($sheet_music_id);
                        $sheet_music_filename = basename(get_attached_file($sheet_music_id));
                        $sheet_music_filetype = wp_check_filetype($sheet_music_url);
                        $icon = '';
                        
                        switch ($sheet_music_filetype['ext']) {
                            case 'pdf':
                                $icon = 'dashicons-pdf';
                                break;
                            case 'doc':
                            case 'docx':
                                $icon = 'dashicons-media-document';
                                break;
                            default:
                                $icon = 'dashicons-media-default';
                        }
                        ?>
                        <div class="clm-media-item">
                            <span class="dashicons <?php echo $icon; ?>"></span>
                            <a href="<?php echo esc_url($sheet_music_url); ?>" target="_blank">
                                <?php echo esc_html($sheet_music_filename); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <h4><?php _e('Audio Recording', 'choir-lyrics-manager'); ?></h4>
            <div class="clm-media-field">
                <input type="hidden" id="clm_audio_file_id" name="clm_audio_file_id" value="<?php echo esc_attr($audio_file_id); ?>">
                <button type="button" class="button clm-upload-media" data-type="audio_file" data-title="<?php _e('Upload or Select Audio File', 'choir-lyrics-manager'); ?>"><?php _e('Upload/Select Audio', 'choir-lyrics-manager'); ?></button>
                <button type="button" class="button clm-remove-media" data-type="audio_file" <?php echo empty($audio_file_id) ? 'style="display:none;"' : ''; ?>><?php _e('Remove', 'choir-lyrics-manager'); ?></button>
                <div class="clm-media-preview" id="clm-audio-file-preview">
                    <?php if ($audio_file_id) : ?>
                        <?php
                        $audio_url = wp_get_attachment_url($audio_file_id);
                        $audio_filename = basename(get_attached_file($audio_file_id));
                        ?>
                        <div class="clm-media-item">
                            <span class="dashicons dashicons-format-audio"></span>
                            <a href="<?php echo esc_url($audio_url); ?>" target="_blank">
                                <?php echo esc_html($audio_filename); ?>
                            </a>
                            <div class="clm-audio-player">
                                <?php echo wp_audio_shortcode(array('src' => $audio_url)); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <h4><?php _e('Video Embed', 'choir-lyrics-manager'); ?></h4>
            <div class="clm-media-field">
                <textarea id="clm_video_embed" name="clm_video_embed" class="large-text" rows="4" placeholder="<?php _e('Paste YouTube, Vimeo or other embed code here', 'choir-lyrics-manager'); ?>"><?php echo esc_textarea($video_embed); ?></textarea>
                <?php if (!empty($video_embed)) : ?>
                    <div class="clm-video-preview">
                        <?php echo wp_kses_post($video_embed); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <h4><?php _e('MIDI File', 'choir-lyrics-manager'); ?></h4>
            <div class="clm-media-field">
                <input type="hidden" id="clm_midi_file_id" name="clm_midi_file_id" value="<?php echo esc_attr($midi_file_id); ?>">
                <button type="button" class="button clm-upload-media" data-type="midi_file" data-title="<?php _e('Upload or Select MIDI File', 'choir-lyrics-manager'); ?>"><?php _e('Upload/Select MIDI', 'choir-lyrics-manager'); ?></button>
                <button type="button" class="button clm-remove-media" data-type="midi_file" <?php echo empty($midi_file_id) ? 'style="display:none;"' : ''; ?>><?php _e('Remove', 'choir-lyrics-manager'); ?></button>
                <div class="clm-media-preview" id="clm-midi-file-preview">
                    <?php if ($midi_file_id) : ?>
                        <?php
                        $midi_url = wp_get_attachment_url($midi_file_id);
                        $midi_filename = basename(get_attached_file($midi_file_id));
                        ?>
                        <div class="clm-media-item">
                            <span class="dashicons dashicons-format-audio"></span>
                            <a href="<?php echo esc_url($midi_url); ?>" target="_blank">
                                <?php echo esc_html($midi_filename); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <h4><?php _e('Practice Tracks', 'choir-lyrics-manager'); ?></h4>
            <div class="clm-media-field">
                <div id="clm-practice-tracks-container">
                    <?php foreach ($practice_tracks as $index => $track) : ?>
                        <div class="clm-practice-track-item">
                            <input type="hidden" name="clm_practice_tracks[<?php echo $index; ?>][id]" value="<?php echo esc_attr($track['id']); ?>">
                            <input type="text" name="clm_practice_tracks[<?php echo $index; ?>][title]" value="<?php echo esc_attr($track['title']); ?>" placeholder="<?php _e('Track Title (e.g. Soprano)', 'choir-lyrics-manager'); ?>" class="regular-text">
                            <button type="button" class="button clm-upload-practice-track" data-index="<?php echo $index; ?>"><?php _e('Select Audio', 'choir-lyrics-manager'); ?></button>
                            <button type="button" class="button clm-remove-practice-track"><?php _e('Remove', 'choir-lyrics-manager'); ?></button>
                            <div class="clm-practice-track-preview">
                                <?php
                                $track_url = wp_get_attachment_url($track['id']);
                                $track_filename = basename(get_attached_file($track['id']));
                                ?>
                                <div class="clm-media-item">
                                    <span class="dashicons dashicons-format-audio"></span>
                                    <a href="<?php echo esc_url($track_url); ?>" target="_blank">
                                        <?php echo esc_html($track_filename); ?>
                                    </a>
                                    <div class="clm-audio-player">
                                        <?php echo wp_audio_shortcode(array('src' => $track_url)); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button clm-add-practice-track"><?php _e('Add Practice Track', 'choir-lyrics-manager'); ?></button>
            </div>
        </div>
        
        <script type="text/html" id="tmpl-clm-practice-track">
            <div class="clm-practice-track-item">
                <input type="hidden" name="clm_practice_tracks[{{data.index}}][id]" value="">
                <input type="text" name="clm_practice_tracks[{{data.index}}][title]" value="" placeholder="<?php _e('Track Title (e.g. Soprano)', 'choir-lyrics-manager'); ?>" class="regular-text">
                <button type="button" class="button clm-upload-practice-track" data-index="{{data.index}}"><?php _e('Select Audio', 'choir-lyrics-manager'); ?></button>
                <button type="button" class="button clm-remove-practice-track"><?php _e('Remove', 'choir-lyrics-manager'); ?></button>
                <div class="clm-practice-track-preview"></div>
            </div>
        </script>
        <?php
    }

    /**
     * Save media meta box data
     *
     * @since    1.0.0
     * @param    int        $post_id    The post ID.
     */
    public function save_media_meta($post_id) {
        // Check if our nonce is set
        if (!isset($_POST['clm_media_meta_box_nonce'])) {
            return;
        }
        
        // Verify that the nonce is valid
        if (!wp_verify_nonce($_POST['clm_media_meta_box_nonce'], 'clm_media_meta_box')) {
            return;
        }
        
        // Save media data
        if (isset($_POST['clm_sheet_music_id'])) {
            update_post_meta($post_id, '_clm_sheet_music_id', intval($_POST['clm_sheet_music_id']));
        }
        
        if (isset($_POST['clm_audio_file_id'])) {
            update_post_meta($post_id, '_clm_audio_file_id', intval($_POST['clm_audio_file_id']));
        }
        
        if (isset($_POST['clm_video_embed'])) {
            update_post_meta($post_id, '_clm_video_embed', wp_kses_post($_POST['clm_video_embed']));
        }
        
        if (isset($_POST['clm_midi_file_id'])) {
            update_post_meta($post_id, '_clm_midi_file_id', intval($_POST['clm_midi_file_id']));
        }
        
        // Process practice tracks
        $practice_tracks = array();
        
        if (isset($_POST['clm_practice_tracks']) && is_array($_POST['clm_practice_tracks'])) {
            foreach ($_POST['clm_practice_tracks'] as $track) {
                if (!empty($track['id'])) {
                    $practice_tracks[] = array(
                        'id' => intval($track['id']),
                        'title' => sanitize_text_field($track['title'])
                    );
                }
            }
        }
        
        update_post_meta($post_id, '_clm_practice_tracks', $practice_tracks);
    }

    /**
     * Get file icon based on file type
     *
     * @since     1.0.0
     * @param     string    $file_url    The file URL.
     * @return    string                 The icon class.
     */
    public function get_file_icon($file_url) {
        $file_type = wp_check_filetype($file_url);
        $icon = 'dashicons-media-default';
        
        switch ($file_type['ext']) {
            case 'pdf':
                $icon = 'dashicons-pdf';
                break;
            case 'doc':
            case 'docx':
                $icon = 'dashicons-media-document';
                break;
            case 'mp3':
            case 'wav':
            case 'ogg':
                $icon = 'dashicons-format-audio';
                break;
            case 'mid':
            case 'midi':
                $icon = 'dashicons-format-audio';
                break;
            case 'mp4':
            case 'mov':
            case 'avi':
                $icon = 'dashicons-format-video';
                break;
        }
        
        return $icon;
    }

    /**
     * Render audio player for a given file
     *
     * @since     1.0.0
     * @param     int       $attachment_id    The attachment ID.
     * @return    string                      The audio player HTML.
     */
    public function render_audio_player($attachment_id) {
        $audio_url = wp_get_attachment_url($attachment_id);
        
        if (!$audio_url) {
            return '';
        }
        
        $output = '';
        $output .= '<div class="clm-audio-player">';
        $output .= wp_audio_shortcode(array('src' => $audio_url));
        $output .= '</div>';
        
        return $output;
    }


   /**
 * Process embedded video code
 *
 * @since     1.0.0
 * @param     string    $embed_code    The embed code.
 * @return    string                   The processed embed code.
 */
public function process_video_embed($embed_code) {
    // Trim whitespace
    $embed_code = trim($embed_code);
    
    // Check if it's a URL rather than an embed code
    if (filter_var($embed_code, FILTER_VALIDATE_URL)) {
        // YouTube URL pattern
        if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $embed_code, $matches) || 
            preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $embed_code, $matches)) {
            // Convert to embed code
            $video_id = $matches[1];
            return '<iframe width="560" height="315" src="https://www.youtube.com/embed/' . esc_attr($video_id) . '" frameborder="0" allowfullscreen></iframe>';
        }
        
        // If it's a URL but not YouTube, try to use oEmbed
        $oembed = wp_oembed_get($embed_code);
        if ($oembed) {
            return $oembed;
        }
    }
    
    // If it's already an embed code, ensure it has proper protocols
    if (strpos($embed_code, '<iframe') !== false) {
        // Make sure src has protocol
        $embed_code = str_replace('src="//', 'src="https://', $embed_code);
        
        // Add security attributes
        $embed_code = str_replace('<iframe', '<iframe loading="lazy" referrerpolicy="no-referrer-when-downgrade"', $embed_code);
    }
    
    return $embed_code;
}
}