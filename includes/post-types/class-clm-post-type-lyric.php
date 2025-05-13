<?php
class CLM_Post_Type_Lyric {
    public static function register() {
        register_post_type('lyric', [
            'labels' => [
                'name' => __('Lyrics', 'choir-lyrics-manager'),
                'singular_name' => __('Lyric', 'choir-lyrics-manager'),
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'custom-fields'],
            'menu_icon' => 'dashicons-media-audio',
        ]);
    }
}

add_action('init', ['CLM_Post_Type_Lyric', 'register']);
