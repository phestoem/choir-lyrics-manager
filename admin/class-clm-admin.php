<?php
class CLM_Admin {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name = 'choir-lyrics-manager', $version = '1.0.0') {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        add_action('admin_menu', array($this, 'register_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function register_menus() {
        add_menu_page(
            __('Choir Manager', 'choir-lyrics-manager'),
            __('Choir Manager', 'choir-lyrics-manager'),
            'manage_options',
            'clm_dashboard',
            array($this, 'render_dashboard'),
            'dashicons-microphone',
            25
        );
    }

    public function render_dashboard() {
        // Get post counts safely
        $lyric_stats = wp_count_posts('clm_lyric');
        $album_stats = wp_count_posts('clm_album');
        $practice_stats = wp_count_posts('clm_practice_log');
        
        // Safe property access to avoid undefined property errors
        $lyrics_count = isset($lyric_stats->publish) ? $lyric_stats->publish : 0;
        $albums_count = isset($album_stats->publish) ? $album_stats->publish : 0;
        $practice_logs_count = isset($practice_stats->publish) ? $practice_stats->publish : 0;
        
        // Get recent posts
        $recent_lyrics = get_posts(array(
            'post_type' => 'clm_lyric',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $recent_practice = get_posts(array(
            'post_type' => 'clm_practice_log',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        // Include the dashboard template
        include plugin_dir_path(dirname(__FILE__)) . 'admin/partials/dashboard.php';
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, CLM_PLUGIN_URL . 'assets/css/admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
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
            ),
        ));
    }

    public function enqueue_charts_scripts() {
        wp_enqueue_script('clm-chartjs', CLM_PLUGIN_URL . 'assets/js/chart.min.js', array(), '2.9.4', true);
        wp_enqueue_script('clm-analytics', CLM_PLUGIN_URL . 'js/analytics.js', array('jquery', 'clm-chartjs'), $this->version, true);
        
        wp_localize_script('clm-analytics', 'clm_analytics_vars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('clm_analytics_nonce'),
        ));
    }
}

// Instantiate the class
new CLM_Admin();