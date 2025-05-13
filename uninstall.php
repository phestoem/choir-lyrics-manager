<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://yourwebsite.com
 * @since      1.0.0
 *
 * @package    Choir_Lyrics_Manager
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Define uninstallation constants
define('CLM_DELETE_ALL_DATA', true);

/**
 * Remove all plugin data if CLM_DELETE_ALL_DATA is true
 */
if (CLM_DELETE_ALL_DATA) {
    // Delete custom post types and related data
    global $wpdb;
    
    // Get all post IDs for our custom post types
    $post_types = array('clm_lyric', 'clm_album', 'clm_practice_log');
    $post_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('%s', '%s', '%s')",
            $post_types
        )
    );
    
    // Delete all post meta
    foreach ($post_ids as $id) {
        delete_post_meta_by_key($id);
    }
    
    // Delete all posts
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->posts} WHERE post_type IN ('%s', '%s', '%s')",
            $post_types
        )
    );
    
    // Delete custom taxonomies
    $taxonomies = array('clm_genre', 'clm_composer', 'clm_language', 'clm_difficulty');
    foreach ($taxonomies as $taxonomy) {
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ]);
        
        foreach ($terms as $term) {
            wp_delete_term($term->term_id, $taxonomy);
        }
    }
    
    // Delete options
    delete_option('clm_settings');
    delete_option('clm_version');
    delete_option('clm_db_version');
    
    // Delete user meta for plugin roles and capabilities
    $users = get_users();
    foreach ($users as $user) {
        delete_user_meta($user->ID, 'clm_preferences');
        delete_user_meta($user->ID, 'clm_recent_lyrics');
    }
    
    // Clear any scheduled events
    wp_clear_scheduled_hook('clm_daily_analytics_update');
}