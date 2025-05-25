<?php
/**
 * Plugin Name: Choir Lyrics Manager
 * Plugin URI: https://yourwebsite.com/choir-lyrics-manager
 * Description: A comprehensive lyrics management system for choirs and music groups
 * Version: 1.0.0
 * Author: Phesto Altimetrix
 * Author URI: https://yourwebsite.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: choir-lyrics-manager
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('CLM_VERSION', '1.0.0');
define('CLM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CLM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CLM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Add activation and deactivation hooks
register_activation_hook(__FILE__, 'activate_choir_lyrics_manager');
register_deactivation_hook(__FILE__, 'deactivate_choir_lyrics_manager');

/**
 * The code that runs during plugin activation.
 */
function activate_choir_lyrics_manager() {
    require_once CLM_PLUGIN_DIR . 'includes/class-clm-activator.php';
    // Ensure CLM_Skills is loaded BEFORE calling its static method
    if (!class_exists('CLM_Skills')) { // Good check
        require_once CLM_PLUGIN_DIR . 'includes/class-clm-skills.php';
    }
    CLM_Activator::activate(); // This should call CLM_Skills::create_skills_table()

    // If CLM_Activator doesn't handle it, this is a fallback:
    // if (class_exists('CLM_Skills') && method_exists('CLM_Skills', 'create_skills_table')) {
    //     CLM_Skills::create_skills_table();
    // }
    clm_add_media_browse_page();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_choir_lyrics_manager() {
    require_once CLM_PLUGIN_DIR . 'includes/class-clm-deactivator.php';
    CLM_Deactivator::deactivate();
}

/**
 * Add Media Browse page to WordPress
 */
function clm_add_media_browse_page() {
    // Check if the page already exists
    $page_exists = get_page_by_path('media-browse');
    
    if (!$page_exists) {
        // Create the page
        $page_id = wp_insert_post(array(
            'post_title'    => __('Browse by Media Type', 'choir-lyrics-manager'),
            'post_name'     => 'media-browse',
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_content'  => '',
            'comment_status' => 'closed',
            'page_template' => 'media-browse.php'
        ));
        
        // Log creation
        if ($page_id && !is_wp_error($page_id)) {
            //error_log('Created Media Browse page with ID: ' . $page_id);
        } else {
            //error_log('Failed to create Media Browse page');
        }
    }
}

// In your activation hook
function clm_add_database_indexes() {
    global $wpdb;
    
    // Add indexes for media meta queries
    $wpdb->query("ALTER TABLE {$wpdb->postmeta} ADD INDEX clm_audio_idx (meta_key, meta_value(10))");
    $wpdb->query("ALTER TABLE {$wpdb->postmeta} ADD INDEX clm_video_idx (meta_key, meta_value(10))");
    $wpdb->query("ALTER TABLE {$wpdb->postmeta} ADD INDEX clm_sheet_idx (meta_key, meta_value(10))");
    $wpdb->query("ALTER TABLE {$wpdb->postmeta} ADD INDEX clm_midi_idx (meta_key, meta_value(10))");
}

// Also run on admin_init to ensure the page exists when plugin is already active
add_action('admin_init', 'clm_add_media_browse_page');

/**
 * Render playlist dropdown for lyric item
 * 
 * @param int $lyric_id The lyric ID
 * @return string HTML for the playlist dropdown
 */
function clm_render_playlist_dropdown($lyric_id) {
    if (!class_exists('CLM_Playlists')) {
        return '';
    }
    
    // Create playlists instance
    $playlists = new CLM_Playlists('choir-lyrics-manager', CLM_VERSION);
    
    // Get user's playlists
    $user_playlists = $playlists->get_user_playlists();
    
    // Start output buffering
    ob_start();
    ?>
    <div class="clm-playlist-wrapper">
        <button type="button" class="clm-button clm-playlist-button" aria-haspopup="true" aria-expanded="false">
            <span class="dashicons dashicons-playlist-audio"></span>
            <?php _e('Add to Playlist', 'choir-lyrics-manager'); ?>
        </button>
        
        <div class="clm-playlist-dropdown" style="display: none;">
            <?php if (!empty($user_playlists)) : ?>
                <div class="clm-existing-playlists">
                    <h4><?php _e('Your Playlists', 'choir-lyrics-manager'); ?></h4>
                    <ul>
                        <?php foreach ($user_playlists as $playlist) : ?>
                            <li>
                                <a href="#" class="clm-add-to-playlist" data-playlist-id="<?php echo esc_attr($playlist->ID); ?>" data-lyric-id="<?php echo esc_attr($lyric_id); ?>">
                                    <?php echo esc_html($playlist->post_title); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="clm-create-new-playlist">
                <h4><?php _e('Create New Playlist', 'choir-lyrics-manager'); ?></h4>
                <div class="clm-form-field">
                    <input type="text" class="clm-new-playlist-name" placeholder="<?php esc_attr_e('Enter playlist name', 'choir-lyrics-manager'); ?>">
                </div>
                <button type="button" class="clm-button clm-create-and-add" data-lyric-id="<?php echo esc_attr($lyric_id); ?>">
                    <?php _e('Create & Add', 'choir-lyrics-manager'); ?>
                </button>
            </div>
        </div>
    </div>
    <?php
    
    return ob_get_clean();
}

/**
 * Enhance pagination HTML in AJAX responses
 *
 * @param array $data The response data
 * @param int $page The current page
 * @param int $max_pages The maximum pages
 * @return array The modified data
 */
function clm_enhance_pagination_html($data, $page, $max_pages) {
    // Skip if no pagination data
    if (!isset($data['pagination'])) {
        return $data;
    }
    
    // Add special class for debug
    $data['pagination'] = str_replace(
        'class="clm-pagination-wrapper"',
        'class="clm-pagination-wrapper clm-pagination-fixed"',
        $data['pagination']
    );
    
    // Ensure current page number is correctly styled
    $page_str = (string)$page;
    
    // Fix span elements
    $data['pagination'] = preg_replace(
        '/<span class="clm-page-link([^"]*)">(' . $page_str . ')<\/span>/',
        '<span class="clm-page-link$1 clm-current">$2</span>',
        $data['pagination']
    );
    
    // Fix link elements for current page (for rare cases where current page is a link)
    $data['pagination'] = preg_replace(
        '/<a class="clm-page-link([^"]*)" href="([^"]*)" data-page="' . $page_str . '">(' . $page_str . ')<\/a>/',
        '<span class="clm-page-link$1 clm-current">$3</span>',
        $data['pagination']
    );
    
    // Remove existing current class from non-current page links
    $data['pagination'] = preg_replace(
        '/<a class="([^"]*)clm-current([^"]*)" href="([^"]*)" data-page="(?!' . $page_str . ')([^"]*)">/',
        '<a class="$1$2" href="$3" data-page="$4">',
        $data['pagination']
    );
    
    return $data;
}

// Add filters to apply above function
add_filter('clm_ajax_filter_data', 'clm_enhance_pagination_html', 10, 3);
add_filter('clm_shortcode_filter_data', 'clm_enhance_pagination_html', 10, 3);

/**
 * Begins execution of the plugin.
 */
function run_choir_lyrics_manager() {
    // Ensure necessary template directories exist
    if (!file_exists(CLM_PLUGIN_DIR . 'templates')) {
        wp_mkdir_p(CLM_PLUGIN_DIR . 'templates');
    }
    
    if (!file_exists(CLM_PLUGIN_DIR . 'templates/partials')) {
        wp_mkdir_p(CLM_PLUGIN_DIR . 'templates/partials');
    }
    
	// Initialize cache management
    require_once CLM_PLUGIN_DIR . 'includes/class-clm-cache.php';
    new CLM_Cache();
	
    // Load the core plugin class
    require_once CLM_PLUGIN_DIR . 'includes/class-choir-lyrics-manager.php';    
    // Initialize the plugin
    $plugin = new Choir_Lyrics_Manager();
    $plugin->run();
}

/**
 * Fix empty pagination links in AJAX responses
 *
 * @param string $html The pagination HTML
 * @param int $current_page Current page number
 * @param int $max_pages Maximum number of pages
 * @return string Fixed pagination HTML
 */
function clm_fix_pagination_html($html, $current_page, $max_pages) {
    // Simple replacement approach - faster than DOM manipulation
    
    // 1. Fix current page - make sure it has the page number and proper classes
    $html = preg_replace(
        '/<span class="[^"]*current[^"]*"[^>]*><\/span>/',
        '<span class="current" data-page="' . $current_page . '">' . $current_page . '</span>',
        $html
    );
    
    // 2. Add page numbers to regular links that are empty
    $html = preg_replace_callback(
        '/<a class="[^"]*page-numbers[^"]*" href="([^"]*)"[^>]*>(\s*)<\/a>/',
        function($matches) {
            $href = $matches[1];
            $page = 1; // Default
            
            // Extract page from href
            if (preg_match('/[\?&]paged=(\d+)/', $href, $pg_match)) {
                $page = $pg_match[1];
            } elseif (preg_match('/\/page\/(\d+)\//', $href, $pg_match)) {
                $page = $pg_match[1];
            }
            
            // Return fixed link with page number
            return '<a class="page-numbers" href="' . $href . '" data-page="' . $page . '">' . $page . '</a>';
        },
        $html
    );
    
    // 3. Make sure the page jump form has the correct values
    if (strpos($html, 'clm-page-jump-input') !== false) {
        $html = preg_replace(
            '/class="clm-page-jump-input"[^>]*>/',
            'class="clm-page-jump-input" min="1" max="' . $max_pages . '" value="' . $current_page . '">',
            $html
        );
    }
    
    return $html;
}

// Add this filter to your plugin
add_filter('clm_ajax_filter_data', function($data, $page, $max_pages) {
    if (isset($data['pagination'])) {
        $data['pagination'] = clm_fix_pagination_html($data['pagination'], $page, $max_pages);
    }
    return $data;
}, 10, 3);

add_filter('clm_shortcode_filter_data', function($data, $page, $max_pages) {
    if (isset($data['pagination'])) {
        $data['pagination'] = clm_fix_pagination_html($data['pagination'], $page, $max_pages);
    }
    return $data;
}, 10, 3);

/**
 * Register the media-browse.php template
 * 
 * @param array $templates Existing page templates
 * @return array Modified templates array
 */
function clm_register_media_browse_template($templates) {
    $templates['media-browse.php'] = __('Media Browser', 'choir-lyrics-manager');
    return $templates;
}
add_filter('theme_page_templates', 'clm_register_media_browse_template');

/**
 * Use the plugin's template for media-browse.php
 * 
 * @param string $template The current template path
 * @return string The modified template path
 */
function clm_use_media_browse_template($template) {
    if (is_page_template('media-browse.php')) {
        $template = CLM_PLUGIN_DIR . 'templates/media-browse.php';
    }
    return $template;
}
add_filter('template_include', 'clm_use_media_browse_template');


// Run the plugin
run_choir_lyrics_manager();