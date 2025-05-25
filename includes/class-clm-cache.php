<?php
/**
 * Cache Management Class for Choir Lyrics Manager
 *
 * @package Choir_Lyrics_Manager
 * @since   1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CLM_Cache {
    
    /**
     * Cache group name
     */
    const CACHE_GROUP = 'clm_media_browser';
    
    /**
     * Cache expiration time (5 minutes)
     */
    const CACHE_EXPIRATION = 300;
    
    /**
     * Initialize the cache management
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Post save/delete hooks
        add_action('save_post', array($this, 'clear_cache_on_post_save'));
        add_action('delete_post', array($this, 'clear_cache_on_post_save'));
        
        // Meta update hooks
        add_action('updated_post_meta', array($this, 'clear_cache_on_meta_update'), 10, 4);
        add_action('added_post_meta', array($this, 'clear_cache_on_meta_update'), 10, 4);
        add_action('deleted_post_meta', array($this, 'clear_cache_on_meta_update'), 10, 4);
        
        // Admin hooks
        add_action('admin_init', array($this, 'handle_admin_cache_clear'));
        add_action('admin_bar_menu', array($this, 'add_cache_clear_admin_bar'), 100);
        
        // Debug hooks
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_footer', array($this, 'show_cache_debug_info'));
        }
    }
    
    /**
     * Clear media browser cache
     *
     * @param int|null $post_id Optional post ID
     */
    public function clear_media_browser_cache($post_id = null) {
        // Clear all media browser cache
        wp_cache_flush_group(self::CACHE_GROUP);
        
        // Also clear any related object cache
        if ($post_id && get_post_type($post_id) === 'clm_lyric') {
            wp_cache_delete('clm_lyric_' . $post_id, 'clm_lyrics');
        }
        
        // Log cache clear if debug is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CLM: Media browser cache cleared' . ($post_id ? ' for post ' . $post_id : ''));
        }
    }
    
    /**
     * Get cached data for media browser
     *
     * @param string $media_type Media type filter
     * @param int $page Page number
     * @return array|false Cached data or false if not found
     */
    public function get_media_browser_cache($media_type, $page) {
        $cache_key = 'clm_media_browser_' . $media_type . '_page_' . $page;
        return wp_cache_get($cache_key, self::CACHE_GROUP);
    }
    
    /**
     * Set cached data for media browser
     *
     * @param string $media_type Media type filter
     * @param int $page Page number
     * @param array $data Data to cache
     * @return bool True on success, false on failure
     */
    public function set_media_browser_cache($media_type, $page, $data) {
        $cache_key = 'clm_media_browser_' . $media_type . '_page_' . $page;
        return wp_cache_set($cache_key, $data, self::CACHE_GROUP, self::CACHE_EXPIRATION);
    }
    
    /**
     * Clear cache when lyric posts are saved/updated
     *
     * @param int $post_id Post ID
     */
    public function clear_cache_on_post_save($post_id) {
        if (get_post_type($post_id) === 'clm_lyric') {
            $this->clear_media_browser_cache($post_id);
        }
    }
    
    /**
     * Clear cache when media attachments are updated
     *
     * @param int $meta_id Meta ID
     * @param int $post_id Post ID
     * @param string $meta_key Meta key
     * @param mixed $meta_value Meta value
     */
    public function clear_cache_on_meta_update($meta_id, $post_id, $meta_key, $meta_value) {
        // Clear cache when media-related meta is updated
        $media_meta_keys = array(
            '_clm_audio_file_id',
            '_clm_video_embed',
            '_clm_sheet_music_id',
            '_clm_midi_file_id',
            '_clm_practice_tracks'
        );
        
        if (in_array($meta_key, $media_meta_keys)) {
            $this->clear_media_browser_cache($post_id);
        }
    }
    
    /**
     * Handle admin cache clear action
     */
    public function handle_admin_cache_clear() {
        if (current_user_can('manage_options') && isset($_GET['clm_clear_cache'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'clm_clear_cache')) {
                $this->clear_media_browser_cache();
                wp_redirect(add_query_arg('cache_cleared', '1', remove_query_arg(array('clm_clear_cache', '_wpnonce'))));
                exit;
            }
        }
        
        // Show success message
        if (isset($_GET['cache_cleared']) && $_GET['cache_cleared'] === '1') {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>CLM cache cleared successfully!</p></div>';
            });
        }
    }
    
    /**
     * Add cache clear button to admin bar
     *
     * @param WP_Admin_Bar $admin_bar Admin bar object
     */
    public function add_cache_clear_admin_bar($admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $admin_bar->add_menu(array(
            'id'    => 'clm-clear-cache',
            'title' => 'Clear CLM Cache',
            'href'  => wp_nonce_url(add_query_arg('clm_clear_cache', '1'), 'clm_clear_cache'),
            'meta'  => array(
                'title' => 'Clear Choir Lyrics Manager Cache',
            ),
        ));
    }
    
    /**
     * Get cache statistics (for debugging)
     *
     * @return array Cache statistics
     */
    public function get_cache_stats() {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return array();
        }
        
        $stats = array();
        
        // Check if object cache is enabled
        $stats['object_cache_enabled'] = wp_using_ext_object_cache();
        
        // Get cache group info (if available)
        if (function_exists('wp_cache_get_group')) {
            $stats['cache_groups'] = wp_cache_get_group(self::CACHE_GROUP);
        }
        
        return $stats;
    }
    
    /**
     * Debug function to show cache info in footer (only for admins with WP_DEBUG)
     */
    public function show_cache_debug_info() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (is_page_template('media-browse.php')) {
            $stats = $this->get_cache_stats();
            echo '<!-- CLM Cache Debug: ' . json_encode($stats) . ' -->';
        }
    }
}