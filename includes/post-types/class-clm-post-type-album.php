<?php
class CLM_Post_Type_Album {
    public static function register() {
        register_post_type('album', [
            'labels' => [
                'name' => __('Albums', 'choir-lyrics-manager'),
                'singular_name' => __('Album', 'choir-lyrics-manager'),
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'custom-fields'],
            'menu_icon' => 'dashicons-album',
        ]);
    }
}
add_action('init', ['CLM_Post_Type_Album', 'register']);
