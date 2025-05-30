<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Choir_Lyrics_Manager
 */

class CLM_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The settings instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      CLM_Settings    $settings    The settings instance.
     */
    private $settings;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string       $plugin_name    The name of this plugin.
     * @param    string       $version        The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->settings = new CLM_Settings($plugin_name, $version);
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name . '-admin-style', CLM_PLUGIN_URL . 'assets/css/admin.css', array(), $this->version, 'all');
       
        // wp_enqueue_style($this->plugin_name, CLM_PLUGIN_URL . 'assets/css/admin.css', array(), $this->version, 'all');
        
        // wp_add_inline_style($this->plugin_name, $this->settings->get_custom_css());

        wp_enqueue_script($this->plugin_name . '-admin-script', CLM_PLUGIN_URL . 'js/admin.js', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Admin scripts
        wp_enqueue_script($this->plugin_name, CLM_PLUGIN_URL . 'js/admin.js', array('jquery', 'jquery-ui-sortable', 'wp-color-picker'), $this->version, false);
        
        // Media uploader scripts
        wp_enqueue_media();
        
        // Localize the script with data for AJAX
        wp_localize_script($this->plugin_name, 'clm_admin_vars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('clm_admin_nonce'),
            'text' => array(
                'upload_title' => __('Select or Upload File', 'choir-lyrics-manager'),
                'upload_button' => __('Use this file', 'choir-lyrics-manager'),
                'upload_sheet_music' => __('Upload Sheet Music', 'choir-lyrics-manager'),
                'upload_audio' => __('Upload Audio File', 'choir-lyrics-manager'),
                'upload_midi' => __('Upload MIDI File', 'choir-lyrics-manager'),
                'confirm_delete' => __('Are you sure you want to delete this item?', 'choir-lyrics-manager'),
                'track_title' => __('Track Title (e.g. Soprano)', 'choir-lyrics-manager'),
            ),
        ));
    }

        /**
         * Add plugin admin menu items.
         *
         * @since    1.0.0
         */
        public function add_plugin_admin_menu() {
            $main_menu_slug = 'clm_dashboard'; // Your main plugin dashboard slug

            // Main Top-Level Menu Page for "Choir Manager"
            add_menu_page(
                __('Choir Manager', 'choir-lyrics-manager'),
                __('Choir Manager', 'choir-lyrics-manager'),
                'edit_posts', // General capability to see the main menu
                $main_menu_slug,
                array($this, 'display_plugin_dashboard_page'),
                'dashicons-groups',
                26
            );
    
            // Submenu: Dashboard (linked to the main menu slug)
            add_submenu_page(
                $main_menu_slug,
                __('CLM Dashboard', 'choir-lyrics-manager'),
                __('Dashboard', 'choir-lyrics-manager'),
                'edit_posts',
                $main_menu_slug, // Default page for the top-level menu
                array($this, 'display_plugin_dashboard_page')
            );
    
            // CPTs (Lyrics, Albums, Members, Events, Playlists)
            // These will be added automatically as submenus if their 'show_in_menu'
            // argument in register_post_type() is set to $main_menu_slug ('clm_dashboard').
            // WordPress will add "All [CPT Name]" and "Add New [CPT Name]"
            // Example: Lyrics (if 'show_in_menu' => 'clm_dashboard' in CLM_CPT)
            // add_submenu_page($main_menu_slug, __('Lyrics', 'c'), __('Lyrics','c'), 'edit_posts', 'edit.php?post_type=clm_lyric');
            // add_submenu_page($main_menu_slug, __('Add Lyric', 'c'), __('Add New','c'), 'edit_posts', 'post-new.php?post_type=clm_lyric');
            // This is just illustrative - let CPT registration handle their primary menu items.
    
            // "My Activity" Section - Using a slightly different approach for the header
            // We will add "My Skills" first, then use its slug as a reference if needed,
            // or simply add a visually distinct item that does nothing.
            // A common trick for a visual separator/header is to add an item with a specific class via JS
            // or to add a menu item that essentially does nothing or points to the parent.
            // For simplicity and reliability with `add_submenu_page`:
            // We will create "My Skills" as a direct submenu, and then "My Playlists" and "My Practice Logs"
            // will appear alongside it if they share the same parent or are added sequentially.
            // To group them, we can add a placeholder that just outputs a title.
    
            $my_activity_position = 40; // Start position for this group
    
            add_submenu_page(
                $main_menu_slug,
                __('My Activity', 'choir-lyrics-manager'), // Page title for browser
                '<span class="clm-menu-section-header">' . __('My Activity', 'choir-lyrics-manager') . '</span>', // Menu title (can use HTML)
                'read',                                   // Capability for the header itself
                'clm_my_activity_placeholder',            // Slug for this placeholder
                '__return_false',                         // Make it non-functional directly
                 $my_activity_position++
            );
    
            // Submenu: My Skills
            add_submenu_page(
                $main_menu_slug, // Parent is still the main dashboard slug
                __('My Skills Dashboard', 'choir-lyrics-manager'),
                __('My Skills', 'choir-lyrics-manager'),
                'read',
                'clm_my_skills_page', // Unique slug for this page
                array($this, 'display_my_skills_page_callback'),
                $my_activity_position++
            );
    
            // Submenu: My Playlists
            $current_wp_user = wp_get_current_user();
            if ($current_wp_user && $current_wp_user->ID > 0) {
                add_submenu_page(
                    $main_menu_slug, // Parent is still the main dashboard slug
                    __('My Playlists', 'choir-lyrics-manager'),
                    __('My Playlists', 'choir-lyrics-manager'),
                    'read',
                    'edit.php?post_type=clm_playlist&author=' . $current_wp_user->ID,
                    '', // No callback needed for direct edit.php links
                    $my_activity_position++
                );
    
                // Submenu: My Practice Logs
                add_submenu_page(
                    $main_menu_slug, // Parent is still the main dashboard slug
                    __('My Practice Logs', 'choir-lyrics-manager'),
                    __('My Practice Logs', 'choir-lyrics-manager'),
                    'read',
                    'edit.php?post_type=clm_practice_log&author=' . $current_wp_user->ID,
                    '', // No callback needed
                    $my_activity_position++
                );
            }
    
            // --- Separator before Admin Tools ---
             add_submenu_page(
                $main_menu_slug,
                '',
                '<span class="clm-menu-section-header">' . __('Admin Tools', 'choir-lyrics-manager') . '</span>',
                'manage_options', // Only admins see this header
                'clm_admin_tools_header_slug',
                '__return_false',
                $my_activity_position + 10 // Ensure it's after "My Activity" items
            );
    
    
            // Submenu: Analytics
            add_submenu_page(
                $main_menu_slug,
                __('Analytics', 'choir-lyrics-manager'),
                __('Analytics', 'choir-lyrics-manager'),
                'view_clm_analytics',
                'clm_analytics_page',
                array($this, 'display_analytics_page'),
                $my_activity_position + 11
            );
    
            // Submenu: Settings
            add_submenu_page(
                $main_menu_slug,
                __('Settings', 'choir-lyrics-manager'),
                __('Settings', 'choir-lyrics-manager'),
                'manage_options',
                'clm_settings_page',
                array($this, 'display_settings_page'),
                $my_activity_position + 12
            );
        }
    


    /**
     * Display the "My Skills" page for the current user.
     */
    public function display_my_skills_page_callback() {
        if (!is_user_logged_in()) {
            wp_die(__('You must be logged in to view this page.', 'choir-lyrics-manager'));
            return;
        }

        $current_wp_user_id = get_current_user_id();
        $member_cpt_id = null;
        $member_post = null; // This variable will be used by the included template

        if (class_exists('CLM_Members')) {
            // Instantiate CLM_Members with plugin_name and version
            $members_manager = new CLM_Members($this->plugin_name, $this->version);
            if (method_exists($members_manager, 'get_member_cpt_id_by_user_id')) {
                $member_cpt_id = $members_manager->get_member_cpt_id_by_user_id($current_wp_user_id);
                if ($member_cpt_id) {
                    $member_post = get_post($member_cpt_id);
                }
            }
        }

        // The template 'templates/member-dashboard/skills.php' expects $member_post to be set.
        // It will then handle the case where $member_post might be null.
        $skills_template = CLM_PLUGIN_DIR . 'templates/member-dashboard/skills.php';
        if (file_exists($skills_template)) {
            include $skills_template;
        } else {
            echo '<div class="wrap"><p>Error: My Skills template file not found.</p></div>';
        }
    }


    // Callback for the main dashboard page
    public function display_plugin_dashboard_page() {
       // Fetch data needed for the dashboard template
       $lyric_stats = wp_count_posts('clm_lyric');
       $album_stats = wp_count_posts('clm_album');
       $practice_log_stats = wp_count_posts('clm_practice_log');

       $lyrics_count = is_object($lyric_stats) ? $lyric_stats->publish : 0;
       $albums_count = is_object($album_stats) ? $album_stats->publish : 0;
       $practice_logs_count = is_object($practice_log_stats) ? $practice_log_stats->publish : 0;

       $recent_lyrics = get_posts(array(
           'post_type' => 'clm_lyric', 'posts_per_page' => 5,
           'orderby' => 'date', 'order' => 'DESC', 'post_status' => 'publish'
       ));
       $recent_practice = get_posts(array(
           'post_type' => 'clm_practice_log', 'posts_per_page' => 5,
           'orderby' => 'date', 'order' => 'DESC', 'post_status' => 'publish'
       ));

       $practice_formatter = null;
       if (class_exists('CLM_Practice') && method_exists('CLM_Practice', 'format_duration')) {
           $practice_formatter = new CLM_Practice($this->plugin_name, $this->version);
       }

       // Make variables available to the template
       extract(array(
           'lyrics_count' => $lyrics_count,
           'albums_count' => $albums_count,
           'practice_logs_count' => $practice_logs_count,
           'recent_lyrics' => $recent_lyrics,
           'recent_practice' => $recent_practice,
           'practice_formatter' => $practice_formatter,
       ));
       
       // Use include_once if there's any chance of it being included multiple times, otherwise include is fine.
       $dashboard_template = CLM_PLUGIN_DIR . 'admin/partials/dashboard.php';
       if (file_exists($dashboard_template)) {
           include $dashboard_template;
       } else {
           echo '<div class="wrap"><p>Error: Dashboard template file not found.</p></div>';
       }
    }

    

    private function render_admin_template($template_file_path, $args = array()) {
        if (!empty($args) && is_array($args)) {
            extract($args, EXTR_SKIP); // EXTR_SKIP prevents overwriting existing vars in this scope
        }
        $template_path_full = CLM_PLUGIN_DIR . $template_file_path;
        if (file_exists($template_path_full)) {
            include $template_path_full;
        } else {
            echo '<div class="notice notice-error"><p>Admin template file not found: ' . esc_html($template_file_path) . '</p></div>';
            error_log('CLM Admin Error: Template file not found: ' . $template_path_full);
        }
    }

    /**
     * Load assets for the settings page.
     *
     * @since    1.0.0
     */
    public function load_settings_page() {
        // Enqueue color picker
        add_action('admin_enqueue_scripts', array($this, 'enqueue_color_picker'));
    }
    
    /**
     * Enqueue color picker script and styles.
     *
     * @since    1.0.0
     */
    public function enqueue_color_picker() {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }
    
    /**
     * Load assets for the analytics page.
     *
     * @since    1.0.0
     */
    public function load_analytics_page() {
        // Enqueue Chart.js
        add_action('admin_enqueue_scripts', array($this, 'enqueue_charts_scripts'));
    }
    
    /**
     * Enqueue Chart.js script.
     *
     * @since    1.0.0
     */
    public function enqueue_charts_scripts() {
        wp_enqueue_script('clm-chartjs', CLM_PLUGIN_URL . 'assets/js/chart.min.js', array(), '2.9.4', true);
        wp_enqueue_script('clm-analytics', CLM_PLUGIN_URL . 'js/analytics.js', array('jquery', 'clm-chartjs'), $this->version, true);
        
        wp_localize_script('clm-analytics', 'clm_analytics_vars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('clm_analytics_nonce'),
        ));
    }
    
    /**
     * Display the plugin dashboard page.
     *
     * @since    1.0.0
     */
    public function display_plugin_dashboard() {
        // Get data for dashboard
    $lyric_stats = wp_count_posts('clm_lyric');
    $album_stats = wp_count_posts('clm_album');
    $practice_log_stats = wp_count_posts('clm_practice_log');
    
    // Safely access the publish property
    $lyrics_count = isset($lyric_stats->publish) ? $lyric_stats->publish : 0;
    $albums_count = isset($album_stats->publish) ? $album_stats->publish : 0;
    $practice_logs_count = isset($practice_log_stats->publish) ? $practice_log_stats->publish : 0;
    
        
        $recent_lyrics = get_posts(array(
            'post_type' => 'clm_lyric',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        
        $recent_practice = get_posts(array(
            'post_type' => 'clm_practice_log',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        
        // Include template
        include CLM_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }
    
	
	
   /**
     * Display the analytics page.
     */
    public function display_analytics_page() {
        if (class_exists('CLM_Analytics')) {
            $analytics = new CLM_Analytics($this->plugin_name, $this->version);
            // Assuming render_analytics_dashboard() is a public method in CLM_Analytics
            if (method_exists($analytics, 'render_analytics_dashboard')) {
                 echo $analytics->render_analytics_dashboard();
            } else {
                 echo '<div class="wrap"><p>Analytics dashboard rendering method not found.</p></div>';
            }
        } else {
             echo '<div class="wrap"><p>Analytics system not available.</p></div>';
        }
    }
    
	// Enqueue admin scripts for events
	public function enqueue_events_admin_scripts($hook) {
		global $post_type;
		
		if ('clm_event' === $post_type) {
			wp_enqueue_script('jquery-ui-sortable');
			wp_enqueue_script('jquery-ui-datepicker');
			wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
		}
	}
	
       /**
     * Display the settings page.
     */
    public function display_settings_page() {
        // $this->settings should already be instantiated in the constructor
        if ($this->settings && method_exists($this->settings, 'render_settings_page')) {
            $this->settings->render_settings_page();
        } else {
            echo '<div class="wrap"><p>Settings page rendering method not found.</p></div>';
        }
    }
    
    /**
     * Add action links to the plugins page.
     *
     * @since    1.0.0
     * @param    array    $links    Action links.
     * @return   array              Modified action links.
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=clm-settings') . '">' . __('Settings', 'choir-lyrics-manager') . '</a>';
        array_unshift($links, $settings_link);
        
        return $links;
    }
    
    /**
     * Add dashboard widgets.
     *
     * @since    1.0.0
     */
    public function add_dashboard_widgets() {
        wp_add_dashboard_widget(
            'clm_recent_lyrics_widget',
            __('Recent Lyrics', 'choir-lyrics-manager'),
            array($this, 'display_recent_lyrics_widget')
        );
        
        // Only show practice widget to users who can view analytics
        if (current_user_can('view_clm_analytics')) {
            wp_add_dashboard_widget(
                'clm_practice_stats_widget',
                __('Practice Statistics', 'choir-lyrics-manager'),
                array($this, 'display_practice_stats_widget')
            );
        }
    }
    
    /**
     * Display recent lyrics dashboard widget.
     *
     * @since    1.0.0
     */
    public function display_recent_lyrics_widget() {
        $recent_lyrics = get_posts(array(
            'post_type' => 'clm_lyric',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        
        if (empty($recent_lyrics)) {
            echo '<p>' . __('No lyrics found.', 'choir-lyrics-manager') . '</p>';
            return;
        }
        
        echo '<ul>';
        
        foreach ($recent_lyrics as $lyric) {
            echo '<li>';
            echo '<a href="' . get_edit_post_link($lyric->ID) . '">' . get_the_title($lyric->ID) . '</a>';
            echo ' <span class="post-date">' . get_the_date('', $lyric->ID) . '</span>';
            echo '</li>';
        }
        
        echo '</ul>';
        echo '<p class="clm-widget-footer"><a href="' . admin_url('edit.php?post_type=clm_lyric') . '">' . __('View all lyrics', 'choir-lyrics-manager') . '</a></p>';
    }
    
    /**
     * Display practice stats dashboard widget.
     *
     * @since    1.0.0
     */
    public function display_practice_stats_widget() {
        global $wpdb;
        
        // Get total practice time
        $total_practice_time = $wpdb->get_var(
            "SELECT SUM(meta_value) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_clm_duration'"
        );
        
        // Get total practice sessions
        $total_practice_sessions = $wpdb->get_var(
            "SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'clm_practice_log' 
            AND post_status = 'publish'"
        );
        
        // Get active users (users with practice logs)
        $active_users = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_author) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'clm_practice_log' 
            AND post_status = 'publish'"
        );
        
        // Format practice time
        $hours = floor($total_practice_time / 60);
        $minutes = $total_practice_time % 60;
        $practice_time_formatted = sprintf(
            _n('%d hour', '%d hours', $hours, 'choir-lyrics-manager'),
            $hours
        );
        
        if ($minutes > 0) {
            $practice_time_formatted .= ' ' . sprintf(
                _n('%d minute', '%d minutes', $minutes, 'choir-lyrics-manager'),
                $minutes
            );
        }
        
        // Display stats
        echo '<div class="clm-dashboard-stats">';
        
        echo '<div class="clm-stat-box">';
        echo '<span class="clm-stat-value">' . esc_html($total_practice_sessions) . '</span>';
        echo '<span class="clm-stat-label">' . __('Practice Sessions', 'choir-lyrics-manager') . '</span>';
        echo '</div>';
        
        echo '<div class="clm-stat-box">';
        echo '<span class="clm-stat-value">' . esc_html($practice_time_formatted) . '</span>';
        echo '<span class="clm-stat-label">' . __('Total Practice Time', 'choir-lyrics-manager') . '</span>';
        echo '</div>';
        
        echo '<div class="clm-stat-box">';
        echo '<span class="clm-stat-value">' . esc_html($active_users) . '</span>';
        echo '<span class="clm-stat-label">' . __('Active Users', 'choir-lyrics-manager') . '</span>';
        echo '</div>';
        
        echo '</div>';
        
        echo '<p class="clm-widget-footer"><a href="' . admin_url('admin.php?page=clm-analytics') . '">' . __('View detailed analytics', 'choir-lyrics-manager') . '</a></p>';
    }
    
    /**
     * Filter the enter title text for custom post types.
     *
     * @since     1.0.0
     * @param     string    $title    Default title placeholder.
     * @param     WP_Post   $post     Post object.
     * @return    string              Modified title placeholder.
     */
    public function filter_enter_title_here($title, $post) {
        if ($post->post_type === 'clm_lyric') {
            return __('Enter song title here', 'choir-lyrics-manager');
        } elseif ($post->post_type === 'clm_album') {
            return __('Enter album title here', 'choir-lyrics-manager');
        } elseif ($post->post_type === 'clm_practice_log') {
            return __('Enter practice session title here', 'choir-lyrics-manager');
        }
        
        return $title;
    }
    
    /**
     * Add admin notices.
     *
     * @since    1.0.0
     */
    public function admin_notices() {
        $screen = get_current_screen();
        
        // Show notice on plugin pages only
        if (strpos($screen->id, 'clm_') === false && strpos($screen->id, 'choir-lyrics-manager') === false) {
            return;
        }
        
        // Check if default terms were added
        if (!get_option('clm_default_terms_added')) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p>' . __('Choir Lyrics Manager: Default taxonomy terms were not added properly. ', 'choir-lyrics-manager');
            echo '<a href="' . admin_url('admin.php?page=clm-settings&tab=tools&action=add_terms') . '">' . __('Add default terms now', 'choir-lyrics-manager') . '</a></p>';
            echo '</div>';
        }
    }
    
    /**
     * Modify admin footer text on plugin pages.
     *
     * @since     1.0.0
     * @param     string    $text    Default footer text.
     * @return    string             Modified footer text.
     */
    public function admin_footer_text($text) {
        $screen = get_current_screen();
        
        // Show custom footer on plugin pages only
        if (strpos($screen->id, 'clm_') !== false || strpos($screen->id, 'choir-lyrics-manager') !== false) {
            $text = sprintf(
                __('Thank you for using %1$s! Please rate us on %2$s', 'choir-lyrics-manager'),
                '<strong>Choir Lyrics Manager</strong>',
                '<a href="https://wordpress.org/support/plugin/choir-lyrics-manager/reviews/?filter=5" target="_blank">WordPress.org</a>'
            );
        }
        
        return $text;
    }
    
    /**
     * Add help tabs to admin screens.
     *
     * @since    1.0.0
     */
    public function add_help_tabs() {
        $screen = get_current_screen();
        
        // Add help tabs for custom post types
        if ($screen->post_type === 'clm_lyric') {
            $screen->add_help_tab(array(
                'id'      => 'clm_lyric_help',
                'title'   => __('Lyrics Help', 'choir-lyrics-manager'),
                'content' => $this->get_lyric_help_content(),
            ));
        } elseif ($screen->post_type === 'clm_album') {
            $screen->add_help_tab(array(
                'id'      => 'clm_album_help',
                'title'   => __('Albums Help', 'choir-lyrics-manager'),
                'content' => $this->get_album_help_content(),
            ));
        } elseif ($screen->post_type === 'clm_practice_log') {
            $screen->add_help_tab(array(
                'id'      => 'clm_practice_help',
                'title'   => __('Practice Logs Help', 'choir-lyrics-manager'),
                'content' => $this->get_practice_help_content(),
            ));
        }
        
        // Add help tab for settings page
        if ($screen->id === 'choir-lyrics_page_clm-settings') {
            $screen->add_help_tab(array(
                'id'      => 'clm_settings_help',
                'title'   => __('Settings Help', 'choir-lyrics-manager'),
                'content' => $this->get_settings_help_content(),
            ));
        }
    }
    
    /**
     * Get help content for lyrics.
     *
     * @since     1.0.0
     * @return    string    Help content.
     */
    private function get_lyric_help_content() {
        $content = '<h2>' . __('Managing Lyrics', 'choir-lyrics-manager') . '</h2>';
        $content .= '<p>' . __('Lyrics are the main content type in this plugin. Each lyric represents a song or music piece.', 'choir-lyrics-manager') . '</p>';
        
        $content .= '<h3>' . __('Lyric Details', 'choir-lyrics-manager') . '</h3>';
        $content .= '<ul>';
        $content .= '<li>' . __('<strong>Title:</strong> The name of the song or music piece.', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('<strong>Content:</strong> The actual lyrics text.', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('<strong>Composer:</strong> The person who wrote the music.', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('<strong>Arranger:</strong> The person who arranged the music (if applicable).', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('<strong>Year:</strong> The year the music was composed or arranged.', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('<strong>Language:</strong> The language of the lyrics.', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('<strong>Difficulty:</strong> How challenging the piece is to perform (1-5).', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('<strong>Performance Notes:</strong> Any additional notes about performing the piece.', 'choir-lyrics-manager') . '</li>';
        $content .= '</ul>';
        
        $content .= '<h3>' . __('Media Files', 'choir-lyrics-manager') . '</h3>';
        $content .= '<p>' . __('You can attach various media files to a lyric:', 'choir-lyrics-manager') . '</p>';
        $content .= '<ul>';
        $content .= '<li>' . __('<strong>Sheet Music:</strong> PDF or image files of the sheet music.', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('<strong>Audio Recording:</strong> An MP3 or other audio file of the performance.', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('<strong>Video Embed:</strong> Embed code for YouTube, Vimeo, or other video platforms.', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('<strong>MIDI File:</strong> A MIDI file for practicing with digital instruments.', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('<strong>Practice Tracks:</strong> Separate audio files for different voice parts.', 'choir-lyrics-manager') . '</li>';
        $content .= '</ul>';
        
        return $content;
    }
    
    /**
     * Get help content for albums.
     *
     * @since     1.0.0
     * @return    string    Help content.
     */
    private function get_album_help_content() {
        $content = '<h2>' . __('Managing Albums', 'choir-lyrics-manager') . '</h2>';
        $content .= '<p>' . __('Albums are collections of lyrics/songs. They can be used to organize lyrics for concerts, recordings, or other groupings.', 'choir-lyrics-manager') . '</p>';
        
        $content .= '<h3>' . __('Album Details', 'choir-lyrics-manager') . '</h3>';
        $content .= '<ul>';
        $content .= '<li>' . __('<strong>Title:</strong> The name of the album or collection.', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('<strong>Description:</strong> Information about the album or collection.', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('<strong>Release Year:</strong> When the album was released or performed.', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('<strong>Director/Conductor:</strong> Who directed or conducted the performance.', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('<strong>Lyrics:</strong> The songs included in this album. You can add existing lyrics and arrange their order.', 'choir-lyrics-manager') . '</li>';
        $content .= '</ul>';
        
        $content .= '<h3>' . __('Collections', 'choir-lyrics-manager') . '</h3>';
        $content .= '<p>' . __('You can organize albums into collections using the Collections taxonomy. This is useful for grouping albums by type (concerts, competitions, recordings, etc.).', 'choir-lyrics-manager') . '</p>';
        
        return $content;
    }
    
    /**
     * Get help content for practice logs.
     *
     * @since     1.0.0
     * @return    string    Help content.
     */
    private function get_practice_help_content() {
        $content = '<h2>' . __('Managing Practice Logs', 'choir-lyrics-manager') . '</h2>';
        $content .= '<p>' . __('Practice logs track practice sessions for lyrics. They help choir members and directors monitor progress.', 'choir-lyrics-manager') . '</p>';
        
        $content .= '<h3>' . __('Practice Log Details', 'choir-lyrics-manager') . '</h3>';
        $content .= '<ul>';
        $content .= '<li>' . __('<strong>Title:</strong> A title for the practice session (auto-generated).', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('<strong>Lyric:</strong> The song that was practiced.', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('<strong>Practice Date:</strong> When the practice session occurred.', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('<strong>Duration:</strong> How long the practice session lasted (in minutes).', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('<strong>Confidence Level:</strong> How confident the performer feels (1-5).', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('<strong>Notes:</strong> Additional notes about the practice session.', 'choir-lyrics-manager') . '</li>';
        $content .= '</ul>';
        
        $content .= '<h3>' . __('Practice Tracking', 'choir-lyrics-manager') . '</h3>';
        $content .= '<p>' . __('The plugin automatically calculates statistics based on practice logs:', 'choir-lyrics-manager') . '</p>';
        $content .= '<ul>';
        $content .= '<li>' . __('Total practice time per lyric', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('Number of practice sessions per lyric', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('Latest confidence level', 'choir-lyrics-manager') . '</li>';
        $content .= '<li>' . __('Most recent practice date', 'choir-lyrics-manager') . '</li>';
        $content .= '</ul>';
        
        return $content;
    }
    
    /**
     * Get help content for settings.
     *
     * @since     1.0.0
     * @return    string    Help content.
     */
    private function get_settings_help_content() {
        $content = '<h2>' . __('Plugin Settings', 'choir-lyrics-manager') . '</h2>';
        $content .= '<p>' . __('Configure the Choir Lyrics Manager plugin to suit your needs.', 'choir-lyrics-manager') . '</p>';
        
        $content .= '<h3>' . __('General Settings', 'choir-lyrics-manager') . '</h3>';
        $content .= '<p>' . __('Basic configuration options for the plugin.', 'choir-lyrics-manager') . '</p>';
        
        $content .= '<h3>' . __('Role Settings', 'choir-lyrics-manager') . '</h3>';
        $content .= '<p>' . __('Control which user roles can perform specific actions.', 'choir-lyrics-manager') . '</p>';
        
        $content .= '<h3>' . __('Practice Settings', 'choir-lyrics-manager') . '</h3>';
        $content .= '<p>' . __('Configure practice tracking features.', 'choir-lyrics-manager') . '</p>';
        
        $content .= '<h3>' . __('Appearance Settings', 'choir-lyrics-manager') . '</h3>';
        $content .= '<p>' . __('Customize the look and feel of the plugin.', 'choir-lyrics-manager') . '</p>';
        
        return $content;
    }
    
    /**
     * Add meta boxes to the post edit screen.
     *
     * @since    1.0.0
     */
    public function add_meta_boxes() {
        // Add shortcode meta box for lyrics
        add_meta_box(
            'clm_lyric_shortcodes',
            __('Lyric Shortcodes', 'choir-lyrics-manager'),
            array($this, 'render_lyric_shortcodes_meta_box'),
            'clm_lyric',
            'side',
            'high'
        );
        
        // Add shortcode meta box for albums
        add_meta_box(
            'clm_album_shortcodes',
            __('Album Shortcodes', 'choir-lyrics-manager'),
            array($this, 'render_album_shortcodes_meta_box'),
            'clm_album',
            'side',
            'high'
        );
    }
    
    /**
     * Render lyric shortcodes meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_lyric_shortcodes_meta_box($post) {
        ?>
        <p><?php _e('Use these shortcodes to display this lyric on your site:', 'choir-lyrics-manager'); ?></p>
        
        <p><strong><?php _e('Display lyric with all details:', 'choir-lyrics-manager'); ?></strong></p>
        <code>[clm_lyric id="<?php echo $post->ID; ?>"]</code>
        
        <p><strong><?php _e('Display only lyric text:', 'choir-lyrics-manager'); ?></strong></p>
        <code>[clm_lyric id="<?php echo $post->ID; ?>" show_details="no"]</code>
        
        <p><strong><?php _e('Display practice widget:', 'choir-lyrics-manager'); ?></strong></p>
        <code>[clm_practice_widget lyric_id="<?php echo $post->ID; ?>"]</code>
        
        <p><strong><?php _e('Display practice stats:', 'choir-lyrics-manager'); ?></strong></p>
        <code>[clm_practice_stats lyric_id="<?php echo $post->ID; ?>"]</code>
        <?php
    }
    
    /**
     * Render album shortcodes meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_album_shortcodes_meta_box($post) {
        ?>
        <p><?php _e('Use these shortcodes to display this album on your site:', 'choir-lyrics-manager'); ?></p>
        
        <p><strong><?php _e('Display album with all lyrics:', 'choir-lyrics-manager'); ?></strong></p>
        <code>[clm_album id="<?php echo $post->ID; ?>"]</code>
        
        <p><strong><?php _e('Display album without media:', 'choir-lyrics-manager'); ?></strong></p>
        <code>[clm_album id="<?php echo $post->ID; ?>" show_media="no"]</code>
        
        <p><strong><?php _e('Display album as playlist:', 'choir-lyrics-manager'); ?></strong></p>
        <code>[clm_playlist id="<?php echo $post->ID; ?>"]</code>
        <?php
    }
}