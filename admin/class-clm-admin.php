<?php
class CLM_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'register_menus']);
    }

    public function register_menus() {
        add_menu_page(
            __('Choir Manager', 'choir-lyrics-manager'),
            __('Choir Manager', 'choir-lyrics-manager'),
            'manage_options',
            'clm_dashboard',
            [$this, 'render_dashboard'],
            'dashicons-microphone',
            25
        );
    }

    public function render_dashboard() {
         include plugin_dir_path(dirname(__FILE__)) . 'admin/partials/dashboard.php';
    }
}
/**
 * Register the stylesheets for the admin area.
 *
 * @since    1.0.0
 */
public function enqueue_styles() {
    wp_enqueue_style($this->plugin_name, CLM_PLUGIN_URL . 'assets/css/admin.css', array(), $this->version, 'all');
    wp_add_inline_style($this->plugin_name, $this->settings->get_custom_css());
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
            // More localized texts...
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

new CLM_Admin();
