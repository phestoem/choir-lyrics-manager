<?php
/**
 * The core plugin class - Revised
 */

class Choir_Lyrics_Manager {

    protected $loader;
    protected $plugin_name;
    protected $version;

    // Store instances of the primary admin and public controllers
    protected $clm_admin_controller;
    protected $clm_public_controller;

    public function __construct() {
        $this->plugin_name = 'choir-lyrics-manager';
        $this->version = defined('CLM_VERSION') ? CLM_VERSION : '1.0.0';

        // 1. Load the Loader class first
        require_once CLM_PLUGIN_DIR . 'includes/class-clm-loader.php';
        $this->loader = new CLM_Loader(); // Instantiate the loader

        // 2. Load all other dependencies
        $this->load_other_dependencies();

        // 3. Instantiate primary controllers (Admin and Public)
        // These classes might register some of their own hooks in their constructors,
        // or we will explicitly register their methods later.
        // For this boilerplate, hooks are usually registered via the loader.
        $this->clm_admin_controller = new CLM_Admin($this->get_plugin_name(), $this->get_version());
        $this->clm_public_controller = new CLM_Public($this->get_plugin_name(), $this->get_version());

        // 4. Set up locale
        $this->set_locale();

        // 5. Define hooks by telling the loader about methods in our component classes
        $this->define_data_structure_hooks(); // For CPTs, Taxonomies (run on init)
        $this->define_admin_specific_hooks();   // For admin UI, meta boxes, admin AJAX
        $this->define_public_facing_hooks();  // For frontend display, shortcodes, public AJAX

        // 6. Initialize any standalone helpers
        $this->init_helpers();
    }

    /**
     * Load all dependencies EXCEPT the loader (which is loaded first).
     */
    private function load_other_dependencies() {
        $base_dir = CLM_PLUGIN_DIR . 'includes/';
        $admin_dir = CLM_PLUGIN_DIR . 'admin/';

        // Order matters if classes depend on each other during instantiation (e.g. CLM_Public needing CLM_Settings)
		require_once $base_dir . 'class-clm-user-management.php';
        require_once $base_dir . 'class-clm-i18n.php';
        require_once $base_dir . 'class-clm-settings.php'; // Load Settings before Public if Public uses it in constructor
        require_once $base_dir . 'class-clm-invitation-codes.php';
		
        require_once $base_dir . 'class-clm-cpt.php';
        require_once $base_dir . 'class-clm-taxonomies.php';
        require_once $base_dir . 'class-clm-members.php';
        require_once $base_dir . 'class-clm-events.php';
        require_once $base_dir . 'class-clm-albums.php';
        require_once $base_dir . 'class-clm-skills.php';
        require_once $base_dir . 'class-clm-practice.php';
        require_once $base_dir . 'class-clm-playlists.php';

        require_once $base_dir . 'class-clm-media.php';
        require_once $base_dir . 'class-clm-submissions.php';
        require_once $base_dir . 'class-clm-analytics.php';
        require_once $base_dir . 'class-clm-roles.php';
        require_once $base_dir . 'class-clm-media-helper.php';

        // Load Admin and Public controller classes themselves
        require_once $admin_dir . 'class-clm-admin.php'; // This is $this->clm_admin_controller
        require_once $base_dir . 'class-clm-public.php'; // This is $this->clm_public_controller

        // Other admin components
        require_once $admin_dir . 'class-clm-admin-members.php';
        require_once $admin_dir . 'class-clm-album-admin.php';
        
        // Activator/Deactivator are for plugin lifecycle, not typically instantiated during regular runtime
        // require_once $base_dir . 'class-clm-activator.php';
        // require_once $base_dir . 'class-clm-deactivator.php';
    }


    private function set_locale() {
        $plugin_i18n = new CLM_i18n();
        $plugin_i18n->set_domain($this->get_plugin_name());
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Hooks for defining data structures (CPTs, Taxonomies).
     * These are all hooked to 'init'.
     */
    private function define_data_structure_hooks() {
        $clm_cpt = new CLM_CPT($this->plugin_name, $this->version);
        $this->loader->add_action('init', $clm_cpt, 'register_post_types');

        $clm_taxonomies = new CLM_Taxonomies($this->plugin_name, $this->version);
        $this->loader->add_action('init', $clm_taxonomies, 'register_taxonomies');

        $clm_members = new CLM_Members($this->plugin_name, $this->version);
        $this->loader->add_action('init', $clm_members, 'register_member_cpt');
        $this->loader->add_action('init', $clm_members, 'register_voice_type_taxonomy');

        $clm_events = new CLM_Events($this->plugin_name, $this->version);
        $this->loader->add_action('init', $clm_events, 'register_event_post_type');
        $this->loader->add_action('init', $clm_events, 'register_event_taxonomies');

        $clm_albums = new CLM_Albums($this->plugin_name, $this->version);
        $this->loader->add_action('init', $clm_albums, 'register_post_type_and_taxonomy');
    }

    /**
     * Hooks related to the admin area functionality.
     */
    private function define_admin_specific_hooks() {
        // Use the pre-instantiated admin controller
        $this->loader->add_action('admin_enqueue_scripts', $this->clm_admin_controller, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $this->clm_admin_controller, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $this->clm_admin_controller, 'add_plugin_admin_menu');

		$clm_invitation_codes = new CLM_Invitation_Codes($this->plugin_name, $this->version, $this->loader);
		$clm_invitation_codes->init();
   
        $plugin_settings = new CLM_Settings($this->plugin_name, $this->version);
        $this->loader->add_action('admin_init', $plugin_settings, 'register_settings');

        // Components for admin UI (meta boxes, columns, admin-specific AJAX)
        $clm_cpt = new CLM_CPT($this->plugin_name, $this->version); // For Lyric admin columns
        $this->loader->add_filter('manage_clm_lyric_posts_columns', $clm_cpt, 'set_custom_lyric_columns');
        $this->loader->add_action('manage_clm_lyric_posts_custom_column', $clm_cpt, 'custom_lyric_column', 10, 2);
        // Note: CLM_CPT's own register_meta_boxes method is hooked to 'add_meta_boxes' within its register_post_types method.

        $clm_members = new CLM_Members($this->plugin_name, $this->version); // For Member admin UI
        $this->loader->add_action('add_meta_boxes_clm_member', $clm_members, 'register_member_meta_boxes');
        $this->loader->add_action('save_post_clm_member', $clm_members, 'save_member_meta');
        $this->loader->add_filter('manage_clm_member_posts_columns', $clm_members, 'set_custom_member_columns');
        // ... other clm_members admin hooks ...
        $clm_admin_members = new CLM_Admin_Members($this->plugin_name, $this->version);
        $this->loader->add_action('admin_menu', $clm_admin_members, 'add_admin_menu');


        $clm_albums = new CLM_Albums($this->plugin_name, $this->version); // For Album CPT slug
        if (class_exists('CLM_Album_Admin')) {
            $clm_album_admin = new CLM_Album_Admin($this->plugin_name, $this->version);
            $album_cpt_slug = $clm_albums->album_cpt_slug;
            $this->loader->add_action('add_meta_boxes_' . $album_cpt_slug, $clm_album_admin, 'add_meta_boxes');
            $this->loader->add_action('save_post_' . $album_cpt_slug, $clm_album_admin, 'save_meta_data');
            // ... other CLM_Album_Admin hooks ...
             $this->loader->add_filter('manage_' . $album_cpt_slug . '_posts_columns', $clm_album_admin, 'add_admin_columns');
             $this->loader->add_action('manage_' . $album_cpt_slug . '_posts_custom_column', $clm_album_admin, 'display_admin_columns', 10, 2);
             $this->loader->add_action('admin_enqueue_scripts', $clm_album_admin, 'enqueue_admin_assets');
        }

        $clm_media = new CLM_Media($this->plugin_name, $this->version);
        $this->loader->add_action('add_meta_boxes_clm_lyric', $clm_media, 'add_media_meta_boxes');
        $this->loader->add_action('save_post_clm_lyric', $clm_media, 'save_media_meta');

        $clm_events = new CLM_Events($this->plugin_name, $this->version);
        $this->loader->add_action('add_meta_boxes_clm_event', $clm_events, 'add_event_meta_boxes');
        $this->loader->add_action('save_post_clm_event', $clm_events, 'save_event_meta');
        // ... other clm_events admin hooks ...


        $clm_skills = new CLM_Skills($this->plugin_name, $this->version);
        // $this->loader->add_action('add_meta_boxes_clm_lyric', $clm_skills, 'add_skills_meta_box'); // If skills meta box on lyric edit
        $this->loader->add_action('admin_enqueue_scripts', $clm_skills, 'enqueue_admin_scripts'); // For admin/js/skills.js
        $this->loader->add_action('wp_ajax_clm_update_skill', $clm_skills, 'ajax_update_skill'); // Admin updates skill

        // Submissions, Analytics, Roles
        $clm_submissions = new CLM_Submissions($this->plugin_name, $this->version);
        $this->loader->add_action('admin_post_clm_submit_lyric', $clm_submissions, 'process_lyric_submission');
        $this->loader->add_action('admin_post_nopriv_clm_submit_lyric', $clm_submissions, 'process_lyric_submission');

        $clm_analytics = new CLM_Analytics($this->plugin_name, $this->version);
        $this->loader->add_action('wp_ajax_clm_get_analytics', $clm_analytics, 'get_analytics_data');

        $clm_roles = new CLM_Roles($this->plugin_name, $this->version);
        $this->loader->add_action('admin_init', $clm_roles, 'add_custom_roles');
    }

    /**
     * Hooks related to the public-facing functionality.
     */
    private function define_public_facing_hooks() {
        // Use the pre-instantiated public controller
        $this->loader->add_action('wp_enqueue_scripts', $this->clm_public_controller, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $this->clm_public_controller, 'enqueue_scripts');
        $this->loader->add_action('init', $this->clm_public_controller, 'register_shortcodes');

        // CLM_Public's constructor registers its own AJAX handlers ('clm_ajax_search', 'clm_ajax_filter', etc.)

        // Template loading
        $this->loader->add_filter('single_template', $this->clm_public_controller, 'load_lyric_template');
        $this->loader->add_filter('archive_template', $this->clm_public_controller, 'load_archive_template');
        $this->loader->add_filter('search_template', $this->clm_public_controller, 'load_search_template');

        $clm_albums = new CLM_Albums($this->plugin_name, $this->version);
        $this->loader->add_filter('single_template', $clm_albums, 'load_single_album_template');
        $this->loader->add_filter('archive_template', $clm_albums, 'load_archive_album_template');
        $this->loader->add_filter('taxonomy_template', $clm_albums, 'load_taxonomy_collection_template');
        $this->loader->add_action('pre_get_posts', $clm_albums, 'clm_modify_archive_queries');
        
        $clm_events = new CLM_Events($this->plugin_name, $this->version); // For event shortcodes if not in CLM_Public
        $this->loader->add_action('init', $clm_events, 'register_shortcodes');


        // Member-specific AJAX Handlers (for logged-in users on the frontend)
        $clm_playlists = new CLM_Playlists($this->plugin_name, $this->version);
        $this->loader->add_action('wp_ajax_clm_create_playlist', $clm_playlists, 'create_playlist');
        $this->loader->add_action('wp_ajax_clm_add_to_playlist', $clm_playlists, 'add_to_playlist');
        $this->loader->add_action('wp_ajax_clm_remove_from_playlist', $clm_playlists, 'remove_from_playlist');
        $this->loader->add_action('wp_ajax_clm_delete_user_playlist', $clm_playlists, 'ajax_delete_user_playlist');

        // If you implement other AJAX actions for playlists (update, reorder, delete), add them here:
    // $this->loader->add_action('wp_ajax_clm_update_playlist_details', $clm_playlists, 'ajax_update_playlist_details');
    // $this->loader->add_action('wp_ajax_clm_reorder_playlist_tracks', $clm_playlists, 'ajax_reorder_playlist_tracks');


        $clm_practice = new CLM_Practice($this->plugin_name, $this->version);
        $this->loader->add_action('wp_ajax_clm_log_lyric_practice', $clm_practice, 'ajax_log_lyric_practice');

        $clm_skills = new CLM_Skills($this->plugin_name, $this->version);
        $this->loader->add_action('wp_ajax_clm_set_lyric_skill_goal', $clm_skills, 'ajax_set_lyric_skill_goal');
    }

    private function init_helpers() {
        if (class_exists('CLM_Media_Helper')) {
            CLM_Media_Helper::init();
        }
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() { return $this->plugin_name; }
    public function get_loader() { return $this->loader; }
    public function get_version() { return $this->version; }
}