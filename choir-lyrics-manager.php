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
    CLM_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_choir_lyrics_manager() {
    require_once CLM_PLUGIN_DIR . 'includes/class-clm-deactivator.php';
    CLM_Deactivator::deactivate();
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


// Run the plugin
run_choir_lyrics_manager();