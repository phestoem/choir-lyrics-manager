<?php
class CLM_Taxonomy_Genre {
    public static function register() {
        register_taxonomy('genre', 'lyric', [
            'label' => __('Genre', 'choir-lyrics-manager'),
            'hierarchical' => true,
            'show_admin_column' => true,
        ]);
    }
}
add_action('init', ['CLM_Taxonomy_Genre', 'register']);
