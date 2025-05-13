<?php
class CLM_Taxonomy_Composer {
    public static function register() {
        register_taxonomy('composer', ['lyric', 'album'], [
            'label' => __('Composer', 'choir-lyrics-manager'),
            'hierarchical' => false,
            'show_admin_column' => true,
        ]);
    }
}
add_action('init', ['CLM_Taxonomy_Composer', 'register']);
