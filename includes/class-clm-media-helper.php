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
     *
     * @since    1.0.0
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'allow_iframe_tags'));
        add_filter('clm_save_video_embed', array(__CLASS__, 'save_video_embed'));
    }

    /**
     * Allow iframes in WordPress content
     *
     * @since    1.0.0
     */
    public static function allow_iframe_tags() {
        global $allowedposttags;
        
        $allowedposttags['iframe'] = array(
            'src' => array(),
            'width' => array(),
            'height' => array(),
            'frameborder' => array(),
            'allowfullscreen' => array(),
            'allow' => array(),
            'title' => array(),
            'style' => array(),
            'class' => array()
        );
    }

    /**
     * Convert video URLs to shortcode format when saving
     *
     * @since    1.0.0
     * @param    string    $video_embed    The video embed code.
     * @return   string                    The processed video embed code.
     */
    public static function save_video_embed($video_embed) {
        // If it's a YouTube URL
        if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $video_embed, $matches) || 
            preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $video_embed, $matches)) {
            return '[clm_youtube id="' . $matches[1] . '"]';
        }
        
        // If it's a Vimeo URL
        if (preg_match('/vimeo\.com\/(\d+)/', $video_embed, $matches)) {
            return '[clm_vimeo id="' . $matches[1] . '"]';
        }
        
        return $video_embed;
    }

    /**
     * Process video embed for display
     *
     * @since    1.0.0
     * @param    string    $video_embed    The video embed code.
     * @return   string                    The processed video embed code.
     */
    public static function process_video_embed($video_embed) {
        // Check if it's a YouTube shortcode
        if (preg_match('/\[clm_youtube id="([^"]+)"\]/', $video_embed, $matches)) {
            $video_id = $matches[1];
            $iframe = '<iframe width="560" height="315" 
                src="https://www.youtube.com/embed/' . esc_attr($video_id) . '?rel=0&modestbranding=1" 
                frameborder="0" 
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                allowfullscreen></iframe>';
            return '<div class="clm-video-wrapper">' . $iframe . '</div>';
        }
        
        // Check if it's a Vimeo shortcode
        if (preg_match('/\[clm_vimeo id="([^"]+)"\]/', $video_embed, $matches)) {
            $video_id = $matches[1];
            $iframe = '<iframe src="https://player.vimeo.com/video/' . esc_attr($video_id) . '" 
                width="560" height="315" 
                frameborder="0" 
                allow="autoplay; fullscreen; picture-in-picture" 
                allowfullscreen></iframe>';
            return '<div class="clm-video-wrapper">' . $iframe . '</div>';
        }
        
        // Return original if not a shortcode
        return $video_embed;
    }
}