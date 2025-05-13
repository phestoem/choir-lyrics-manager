<?php
class CLM_User_Meta {
    public static function register_meta() {
        register_meta('user', 'clm_practice_status', [
            'type' => 'string',
            'description' => 'Practice status for lyrics',
            'single' => false,
            'show_in_rest' => true,
        ]);
    }
}
add_action('init', ['CLM_User_Meta', 'register_meta']);
