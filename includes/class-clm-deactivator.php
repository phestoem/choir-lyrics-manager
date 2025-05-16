<?php
/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 * @package    Choir_Lyrics_Manager
 */

class CLM_Deactivator {

    /**
     * Deactivate the plugin.
     *
     * Clears scheduled events and flushes rewrite rules.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Clear scheduled events
        self::clear_scheduled_events();
        
        // Remove capabilities
        self::remove_capabilities();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Clear scheduled events.
     *
     * @since    1.0.0
     */
    private static function clear_scheduled_events() {
        wp_clear_scheduled_hook('clm_daily_analytics_update');
        wp_clear_scheduled_hook('clm_weekly_practice_reminder');
    }
    
    /**
     * Remove plugin capabilities from roles.
     *
     * @since    1.0.0
     */
    private static function remove_capabilities() {
        // Remove member capabilities
        CLM_Members::remove_capabilities();
        
        // Remove other custom capabilities
        $capabilities = array(
            // Practice log capabilities
            'edit_clm_practice_log',
            'read_clm_practice_log',
            'delete_clm_practice_log',
            'edit_clm_practice_logs',
            'edit_others_clm_practice_logs',
            'publish_clm_practice_logs',
            'read_private_clm_practice_logs',
            'delete_clm_practice_logs',
            'delete_private_clm_practice_logs',
            'delete_published_clm_practice_logs',
            'delete_others_clm_practice_logs',
            'edit_private_clm_practice_logs',
            'edit_published_clm_practice_logs',
            
            // Playlist capabilities
            'edit_clm_playlist',
            'read_clm_playlist',
            'delete_clm_playlist',
            'edit_clm_playlists',
            'edit_others_clm_playlists',
            'publish_clm_playlists',
            'read_private_clm_playlists',
            'delete_clm_playlists',
            'delete_private_clm_playlists',
            'delete_published_clm_playlists',
            'delete_others_clm_playlists',
            'edit_private_clm_playlists',
            'edit_published_clm_playlists',
        );
        
        $roles = array('administrator', 'editor', 'author', 'clm_manager', 'clm_contributor');
        
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
    }
}