<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks,
 * and public-facing site hooks.
 *
 * @since      1.0.0
 * @package    Choir_Lyrics_Manager
 */

class Choir_Lyrics_Manager {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @since    1.0.0
     * @access   protected
     * @var      CLM_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Load the dependencies, define the locale, and set the hooks.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->plugin_name = 'choir-lyrics-manager';
        $this->version = CLM_VERSION;

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->init_helpers();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - CLM_Loader. Orchestrates the hooks of the plugin.
     * - CLM_i18n. Defines internationalization functionality.
     * - CLM_Admin. Defines all hooks for the admin area.
     * - CLM_Public. Defines all hooks for the public side of the site.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once CLM_PLUGIN_DIR . 'includes/class-clm-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once CLM_PLUGIN_DIR . 'includes/class-clm-i18n.php';
        
        /**
         * The class responsible for defining activation functionality.
         */
        require_once CLM_PLUGIN_DIR . 'includes/class-clm-activator.php';
        
        /**
         * The class responsible for defining deactivation functionality.
         */
        require_once CLM_PLUGIN_DIR . 'includes/class-clm-deactivator.php';

        /**
         * The class responsible for defining all custom post types.
         */
        require_once CLM_PLUGIN_DIR . 'includes/class-clm-cpt.php';

        /**
         * The class responsible for defining all custom taxonomies.
         */
        require_once CLM_PLUGIN_DIR . 'includes/class-clm-taxonomies.php';

        /**
         * The class responsible for handling media uploads and embeds.
         */
        require_once CLM_PLUGIN_DIR . 'includes/class-clm-media.php';

        /**
         * The class responsible for handling lyric submissions.
         */
        require_once CLM_PLUGIN_DIR . 'includes/class-clm-submissions.php';

        /**
         * The class responsible for playlist and bookmark functionality.
         */
        require_once CLM_PLUGIN_DIR . 'includes/class-clm-playlists.php';

        /**
         * The class responsible for practice tracking functionality.
         */
        require_once CLM_PLUGIN_DIR . 'includes/class-clm-practice.php';

        /**
         * The class responsible for analytics collection and display.
         */
        require_once CLM_PLUGIN_DIR . 'includes/class-clm-analytics.php';

        /**
         * The class responsible for role and capability management.
         */
        require_once CLM_PLUGIN_DIR . 'includes/class-clm-roles.php';

        /**
         * The class responsible for plugin settings.
         */
        require_once CLM_PLUGIN_DIR . 'includes/class-clm-settings.php';

        /**
         * The class responsible for admin-specific functionality.
         */
        require_once CLM_PLUGIN_DIR . 'includes/class-clm-admin.php';

        /**
         * The class responsible for public-facing functionality.
         */
        require_once CLM_PLUGIN_DIR . 'includes/class-clm-public.php';

        /**
         * The class responsible for media helper functions.
         */
        require_once CLM_PLUGIN_DIR . 'includes/class-clm-media-helper.php';

        $this->loader = new CLM_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the CLM_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new CLM_i18n();
        $plugin_i18n->set_domain($this->get_plugin_name());

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        // Admin class hooks
        $plugin_admin = new CLM_Admin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        
        // CPT hooks
        $plugin_cpt = new CLM_CPT($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('init', $plugin_cpt, 'register_post_types');
        $this->loader->add_filter('manage_clm_lyric_posts_columns', $plugin_cpt, 'set_custom_lyric_columns');
        $this->loader->add_action('manage_clm_lyric_posts_custom_column', $plugin_cpt, 'custom_lyric_column', 10, 2);
        
        // Taxonomies hooks
        $plugin_taxonomies = new CLM_Taxonomies($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('init', $plugin_taxonomies, 'register_taxonomies');
        
        // Media hooks
        $plugin_media = new CLM_Media($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('add_meta_boxes', $plugin_media, 'add_media_meta_boxes');
        $this->loader->add_action('save_post', $plugin_media, 'save_media_meta');
        
        // Submissions hooks
        $plugin_submissions = new CLM_Submissions($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_post_clm_submit_lyric', $plugin_submissions, 'process_lyric_submission');
        $this->loader->add_action('admin_post_nopriv_clm_submit_lyric', $plugin_submissions, 'process_lyric_submission');
        
        // Playlists hooks
        $plugin_playlists = new CLM_Playlists($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('wp_ajax_clm_add_to_playlist', $plugin_playlists, 'add_to_playlist');
        $this->loader->add_action('wp_ajax_clm_remove_from_playlist', $plugin_playlists, 'remove_from_playlist');
        
        // Practice tracking hooks
        $plugin_practice = new CLM_Practice($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('wp_ajax_clm_update_practice_log', $plugin_practice, 'update_practice_log');
        
        // Analytics hooks
        $plugin_analytics = new CLM_Analytics($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('wp_ajax_clm_get_analytics', $plugin_analytics, 'get_analytics_data');
        
        // Roles hooks
        $plugin_roles = new CLM_Roles($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_init', $plugin_roles, 'add_custom_roles');
        
        // Settings hooks
        $plugin_settings = new CLM_Settings($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_init', $plugin_settings, 'register_settings');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new CLM_Public($this->get_plugin_name(), $this->get_version());
        
        // Enqueue styles and scripts
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Register shortcodes
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
        
        // Add template filters
        $this->loader->add_filter('single_template', $plugin_public, 'load_lyric_template');
        $this->loader->add_filter('archive_template', $plugin_public, 'load_archive_template');
        $this->loader->add_filter('search_template', $plugin_public, 'load_search_template');
        
        // AJAX handlers
        $this->loader->add_action('wp_ajax_clm_ajax_search', $plugin_public, 'handle_ajax_search');
        $this->loader->add_action('wp_ajax_nopriv_clm_ajax_search', $plugin_public, 'handle_ajax_search');
        $this->loader->add_action('wp_ajax_clm_ajax_filter', $plugin_public, 'handle_ajax_filter');
        $this->loader->add_action('wp_ajax_nopriv_clm_ajax_filter', $plugin_public, 'handle_ajax_filter');
        $this->loader->add_action('wp_ajax_clm_shortcode_filter', $plugin_public, 'handle_shortcode_filter');
        $this->loader->add_action('wp_ajax_nopriv_clm_shortcode_filter', $plugin_public, 'handle_shortcode_filter');
    }

    /**
     * Initialize helper functions
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_helpers() {
        // Initialize media helper
        CLM_Media_Helper::init();
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    CLM_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}