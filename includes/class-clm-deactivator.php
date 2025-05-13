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
}