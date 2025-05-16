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

// Run the plugin
run_choir_lyrics_manager();