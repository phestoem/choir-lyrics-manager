<?php
// File: includes/metaboxes/class-clm-metabox-lyric-meta.php

if (!defined('ABSPATH')) exit;

class CLM_Metabox_Lyric_Meta {

    public static function init() {
        add_action('add_meta_boxes', [__CLASS__, 'add_metabox']);
        add_action('save_post', [__CLASS__, 'save_metabox']);
    }

    public static function add_metabox() {
        add_meta_box(
            'clm_lyric_metadata',
            __('Lyric Metadata', 'choir-lyrics-manager'),
            [__CLASS__, 'render_metabox'],
            'lyric',
            'normal',
            'default'
        );
    }

    public static function render_metabox($post) {
        wp_nonce_field('clm_lyric_meta_nonce', 'clm_lyric_meta_nonce_field');

        $meta = get_post_meta($post->ID);

        $get = function($key, $default = '') use ($meta) {
            return isset($meta[$key]) ? esc_attr($meta[$key][0]) : $default;
        };

        $checked = function($key) use ($meta) {
            return !empty($meta[$key][0]) ? 'checked' : '';
        };

        ?>
        <p><label>Year: <input type="number" name="clm_year" value="<?= $get('clm_year') ?>" /></label></p>

        <p><label><input type="checkbox" name="clm_is_recorded" <?= $checked('clm_is_recorded') ?> /> Is Recorded?</label></p>

        <p>Recording Types:</p>
        <?php
        $types = ['live_video' => 'Live Video', 'audio' => 'Audio', 'studio' => 'Studio'];
        $saved_types = maybe_unserialize($get('clm_recording_types', []));
        foreach ($types as $val => $label) {
            $is_checked = is_array($saved_types) && in_array($val, $saved_types) ? 'checked' : '';
            echo "<label><input type='checkbox' name='clm_recording_types[]' value='$val' $is_checked /> $label</label><br>";
        }
        ?>

        <p>Media Availability:</p>
        <?php
        $media_options = ['mp3' => 'MP3', 'youtube' => 'YouTube', 'sheet_music' => 'Sheet Music', 'pdf' => 'PDF'];
        $saved_media = maybe_unserialize($get('clm_media_available', []));
        foreach ($media_options as $val => $label) {
            $is_checked = is_array($saved_media) && in_array($val, $saved_media) ? 'checked' : '';
            echo "<label><input type='checkbox' name='clm_media_available[]' value='$val' $is_checked /> $label</label><br>";
        }
        ?>

        <p><label><input type="checkbox" name="clm_downloadable" <?= $checked('clm_downloadable') ?> /> Allow Download</label></p>

        <hr>

        <p><label>Key: <input type="text" name="clm_key" value="<?= $get('clm_key') ?>" /></label></p>
        <p><label>Tempo: <input type="text" name="clm_tempo" value="<?= $get('clm_tempo') ?>" /></label></p>
        <p><label>Time Signature: <input type="text" name="clm_time_signature" value="<?= $get('clm_time_signature') ?>" /></label></p>
        <p><label>Chord Progression:<br>
        <textarea name="clm_chords" rows="4" cols="50"><?= esc_textarea($get('clm_chords')) ?></textarea></label></p>
        <?php
    }

    public static function save_metabox($post_id) {
        if (!isset($_POST['clm_lyric_meta_nonce_field']) || 
            !wp_verify_nonce($_POST['clm_lyric_meta_nonce_field'], 'clm_lyric_meta_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        if (!current_user_can('edit_post', $post_id)) return;

        $fields = [
            'clm_year', 'clm_key', 'clm_tempo', 'clm_time_signature', 'clm_chords'
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            } else {
                delete_post_meta($post_id, $field);
            }
        }

        update_post_meta($post_id, 'clm_is_recorded', isset($_POST['clm_is_recorded']) ? '1' : '');
        update_post_meta($post_id, 'clm_downloadable', isset($_POST['clm_downloadable']) ? '1' : '');

        $multi_fields = [
            'clm_recording_types',
            'clm_media_available'
        ];

        foreach ($multi_fields as $field) {
            $value = isset($_POST[$field]) && is_array($_POST[$field]) ? array_map('sanitize_text_field', $_POST[$field]) : [];
            update_post_meta($post_id, $field, $value);
        }
    }
}
/

// Initialize the metabox and frontend display functionality
CLM_Metabox_Lyric_Meta::init();
