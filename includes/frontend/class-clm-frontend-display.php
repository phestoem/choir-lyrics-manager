<?php
// File: includes/frontend/class-clm-frontend-display.php

if (!defined('ABSPATH')) exit;

class CLM_Frontend_Display {

    public static function init() {
        // Shortcode to display lyric metadata
        add_shortcode('clm_lyric_metadata', [__CLASS__, 'display_lyric_metadata']);
    }

    // Retrieve and display the metadata for the lyric post
    public static function display_lyric_metadata($atts) {
        global $post;

        // Make sure we're working with a 'lyric' post
        if ('lyric' !== get_post_type($post)) {
            return ''; // Exit if it's not a lyric post
        }

        // Get the custom fields
        $year = get_post_meta($post->ID, 'clm_year', true);
        $is_recorded = get_post_meta($post->ID, 'clm_is_recorded', true);
        $recording_types = maybe_unserialize(get_post_meta($post->ID, 'clm_recording_types', true));
        $media_available = maybe_unserialize(get_post_meta($post->ID, 'clm_media_available', true));
        $downloadable = get_post_meta($post->ID, 'clm_downloadable', true);
        $key = get_post_meta($post->ID, 'clm_key', true);
        $tempo = get_post_meta($post->ID, 'clm_tempo', true);
        $time_signature = get_post_meta($post->ID, 'clm_time_signature', true);
        $chords = get_post_meta($post->ID, 'clm_chords', true);

        // Prepare the output HTML
        $output = '<div class="clm-lyric-metadata">';

        // Display the metadata
        if ($year) {
            $output .= '<p><strong>Year:</strong> ' . esc_html($year) . '</p>';
        }

        if ($is_recorded) {
            $output .= '<p><strong>Is Recorded:</strong> Yes</p>';
        }

        if ($recording_types && is_array($recording_types)) {
            $output .= '<p><strong>Recording Types:</strong> ' . implode(', ', array_map('ucfirst', $recording_types)) . '</p>';
        }

        if ($media_available && is_array($media_available)) {
            $output .= '<p><strong>Media Available:</strong> ' . implode(', ', array_map('ucfirst', $media_available)) . '</p>';
        }

        if ($downloadable) {
            $output .= '<p><strong>Downloadable:</strong> Yes</p>';
        }

        if ($key) {
            $output .= '<p><strong>Key:</strong> ' . esc_html($key) . '</p>';
        }

        if ($tempo) {
            $output .= '<p><strong>Tempo:</strong> ' . esc_html($tempo) . '</p>';
        }

        if ($time_signature) {
            $output .= '<p><strong>Time Signature:</strong> ' . esc_html($time_signature) . '</p>';
        }

        if ($chords) {
            $output .= '<p><strong>Chord Progression:</strong><br>' . nl2br(esc_textarea($chords)) . '</p>';
        }

        $output .= '</div>';

        return $output;
    }
}

// Initialize the frontend display functionality
CLM_Frontend_Display::init();
