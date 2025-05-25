<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Choir_Lyrics_Manager
 */
// Include the AJAX handlers trait
require_once CLM_PLUGIN_DIR . 'includes/trait-clm-ajax-handlers.php';
class CLM_Public {
 
    // Use the trait for AJAX handlers if it defines them
    // Ensure the trait methods are public and correctly named.
    use CLM_Ajax_Handlers;

    private $plugin_name;
    private $version;
    private $settings; // Instance of CLM_Settings

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Instantiate CLM_Settings if needed by this class
        if (class_exists('CLM_Settings')) {
            $this->settings = new CLM_Settings($this->plugin_name, $this->version);
        } else {
            // Fallback or error log if CLM_Settings is critical and not found
            $this->settings = null; // or new stdClass() to avoid errors on ->get_setting()
            // error_log('CLM_Public Warning: CLM_Settings class not found during instantiation.');
        }

        // If AJAX handlers are in the trait, this call might be redundant if they auto-hook,
        // or necessary if register_ajax_handlers uses $this->loader (which it shouldn't in this class).
        // The main plugin class (Choir_Lyrics_Manager) should hook these AJAX actions directly.
        // For now, assuming register_ajax_handlers() uses add_action directly (which is fine for self-contained AJAX).
        if (method_exists($this, 'register_ajax_handlers')) {
             $this->register_ajax_handlers();
        }

        // Other direct filters
        add_filter('the_title', array($this, 'add_attachment_icons'), 10, 2);
        add_filter('theme_page_templates', array($this, 'register_custom_templates'));
        add_filter('template_include', array($this, 'add_custom_template_location'));
    }

    public function enqueue_styles() {
        // Ensure CLM_PLUGIN_URL is defined and correct
        $css_path = CLM_PLUGIN_URL . 'assets/css/public.css'; // Assuming this path
        wp_enqueue_style($this->plugin_name, $css_path, array(), $this->version, 'all');

        if ($this->settings && method_exists($this->settings, 'get_custom_css')) {
            $custom_css = $this->settings->get_custom_css();
            if (!empty($custom_css)) {
                wp_add_inline_style($this->plugin_name, $custom_css);
            }
        }
        wp_enqueue_style('dashicons');
    }

    public function enqueue_scripts() {
        // For PHP error log debugging
		 $is_single_lyric = is_singular('clm_lyric');
        if (is_singular('clm_lyric')) {
            // error_log('CLM_DEBUG: CLM_Public::enqueue_scripts() CALLED on SINGLE clm_lyric page.');
        }

        $script_handle = $this->plugin_name; // e.g., 'choir-lyrics-manager'
        $script_path = CLM_PLUGIN_URL . 'js/public.js'; // CONFIRMED PATH

        wp_enqueue_script(
            $script_handle,
            $script_path,
            array('jquery'),
            $this->version,
            true // Load in footer
        );

        // Prepare nonces
        $nonces_for_js = array(
            'search'               => wp_create_nonce('clm_search_nonce'), // For generic search if any
            'filter'               => wp_create_nonce('clm_filter_nonce'), // Fallback, and for main archive filter
            'ajax_filter'          => wp_create_nonce('clm_filter_nonce'), // Specific for handle_ajax_filter
            'shortcode_filter'     => wp_create_nonce('clm_shortcode_filter_nonce'), // Make sure PHP handler verifies this name

            'create_playlist'      => wp_create_nonce('clm_playlist_nonce'),
            'add_to_playlist'      => wp_create_nonce('clm_playlist_nonce'),
            'remove_from_playlist' => wp_create_nonce('clm_playlist_nonce'),
            'delete_user_playlist' => wp_create_nonce('clm_playlist_nonce'),

            // If you add other AJAX playlist actions, add their nonces here, likely using 'clm_playlist_nonce' too
            // 'update_playlist_details' => wp_create_nonce('clm_playlist_nonce'),
            // 'reorder_playlist_tracks' => wp_create_nonce('clm_playlist_nonce'),

            'log_lyric_practice'   => wp_create_nonce('clm_practice_nonce'),
            'set_lyric_skill_goal' => wp_create_nonce('clm_skills_nonce'),
        );

        // Initialize $localized_data as an array
        $localized_data = array(
            'ajaxurl'           => admin_url('admin-ajax.php'),
            'is_user_logged_in' => is_user_logged_in(),
            'nonce'             => $nonces_for_js,
            'text'              => array(
                'practice_success'        => __('Practice logged successfully!', 'choir-lyrics-manager'),
                'playlist_success'        => __('Playlist updated successfully!', 'choir-lyrics-manager'),
                'playlist_error'          => __('Error updating playlist.', 'choir-lyrics-manager'),
                'confirm_remove'          => __('Are you sure you want to remove this item?', 'choir-lyrics-manager'),
                'search_min_length'       => __('Please enter at least 2 characters', 'choir-lyrics-manager'),
                'searching'               => __('Searching...', 'choir-lyrics-manager'),
                'loading'                 => __('Loading...', 'choir-lyrics-manager'),
                'error'                   => __('An error occurred. Please try again.', 'choir-lyrics-manager'),
                'practice_logging'        => __('Logging practice...', 'choir-lyrics-manager'),
                'log_session'             => __('Log Session', 'choir-lyrics-manager'),
                'practice_log_success'    => __('Practice logged!', 'choir-lyrics-manager'),
                'practice_log_error'      => __('Error logging practice.', 'choir-lyrics-manager'),
                'practice_log_error_ajax' => __('AJAX request failed while logging practice.', 'choir-lyrics-manager'),
                'set_goal_title'          => __('Set Practice Goal', 'choir-lyrics-manager'),
                'set_goal_description'    => __('Choose a target date for mastering this piece:', 'choir-lyrics-manager'),
                'confirm'                 => __('Confirm', 'choir-lyrics-manager'), // Generic confirm
                'cancel'                  => __('Cancel', 'choir-lyrics-manager'),
                'saving'                  => __('Saving...', 'choir-lyrics-manager'),
                'skill_goal_saving'       => __('Saving goal...', 'choir-lyrics-manager'),
                'set_goal_button'         => __('Set Goal', 'choir-lyrics-manager'),
                'save_goal_button'        => __('Save Goal', 'choir-lyrics-manager'),
                'change_goal_button'      => __('Change Goal', 'choir-lyrics-manager'),
                'goal_set_success'        => __('Goal updated successfully!', 'choir-lyrics-manager'),
                'skill_goal_success'      => __('Goal updated!', 'choir-lyrics-manager'), // More generic
                'please_select_date'      => __('Please select a date', 'choir-lyrics-manager'),
                'goal'                    => __('Goal', 'choir-lyrics-manager'),
                'skill_goal_error'        => __('Error updating goal.', 'choir-lyrics-manager'),
                'skill_goal_error_ajax'   => __('AJAX request failed while setting goal.', 'choir-lyrics-manager'),
                'duration_0_minutes'      => __('0 minutes', 'choir-lyrics-manager'),
                'duration_minute'         => __(' minute', 'choir-lyrics-manager'),
                'duration_minutes'        => __(' minutes', 'choir-lyrics-manager'),
                'duration_hour'           => __(' hour', 'choir-lyrics-manager'),
                'duration_hours'          => __(' hours', 'choir-lyrics-manager'),
				
				
				'playlist_data_missing' 	=> __('Playlist or lyric data missing.', 'choir-lyrics-manager'),
				'playlist_adding' 			=> __('Adding...', 'choir-lyrics-manager'),
				'playlist_added_feedback' 	=> __('✓ Added', 'choir-lyrics-manager'),
				'playlist_already_in' 		=> __('Already in', 'choir-lyrics-manager'),
				'playlist_error_short' 		=> __('Error', 'choir-lyrics-manager'), // Short error for button
				'playlist_error_generic' 	=> __('An error occurred.', 'choir-lyrics-manager'), // For notification
				'playlist_error_connection' => __('Connection Error', 'choir-lyrics-manager'), // Short for button
				'playlist_error_connection_long' => __('Could not connect to the server. Please try again.', 'choir-lyrics-manager'), // For notification
				'playlist_name_required'	=> __('Playlist name is required', 'choir-lyrics-manager'),
				'playlist_creating'			=> __('Create a playlist', 'choir-lyrics-manager'),
				'playlist_created_added'	=> __('New Playlist has been successufully added', 'choir-lyrics-manager'),
                'playlist_confirm_remove_lyric' => __('Are you sure you want to remove this lyric from the playlist?', 'choir-lyrics-manager'),
                'playlist_removing_lyric'       => __('Removing...', 'choir-lyrics-manager'),
                'playlist_remove_success'       => __('Lyric removed from playlist!', 'choir-lyrics-manager'),
                'playlist_removed_feedback'     => __('✓ Removed', 'choir-lyrics-manager'),
                'playlist_confirm_delete_list' => __('Are you sure you want to permanently delete this playlist? This cannot be undone.', 'choir-lyrics-manager'),
                'playlist_deleting' => __('Deleting...', 'choir-lyrics-manager'),
                'playlist_delete_success' => __('Playlist deleted successfully!', 'choir-lyrics-manager'),
				
            ),
            // skill_levels_js will be added next
        );

        if (class_exists('CLM_Skills')) {
            $skills_temp = new CLM_Skills($this->plugin_name, $this->version);
            if (method_exists($skills_temp, 'get_skill_levels')) {
                $localized_data['skill_levels_js'] = $skills_temp->get_skill_levels();
            }
        }

        wp_localize_script($script_handle, 'clm_vars', $localized_data);

        // Debugging check (from Test A in previous response)
        if ($is_single_lyric) {
            $is_enqueued = wp_script_is($script_handle, 'enqueued');
            $is_registered = wp_script_is($script_handle, 'registered');
            $has_data = false;
            global $wp_scripts;
            if (isset($wp_scripts->registered[$script_handle]) && !empty($wp_scripts->get_data($script_handle, 'data'))) {
                $has_data = true;
            }
            // error_log("CLM_DEBUG SINGLE SCRIPT STATUS for '{$script_handle}': Path: {$script_path}, Registered: " . ($is_registered ? 'YES' : 'NO') . ", Enqueued: " . ($is_enqueued ? 'YES' : 'NO') . ", Has Localized Data: " . ($has_data ? 'YES' : 'NO'));
        }
    }


/**
 * Add attachment icons to lyric titles
 *
 * @param string $title The post title
 * @param int $post_id The post ID
 * @return string Modified title with icons
 */
public function add_attachment_icons($title, $post_id) {
    // Only process lyric post types and only on the main site display (not admin, not in menus, etc.)
    if (get_post_type($post_id) !== 'clm_lyric' || is_admin()) {
        return $title;
    }
    
    // Check for attachments
    $audio_file_id = get_post_meta($post_id, '_clm_audio_file_id', true);
    $video_embed = get_post_meta($post_id, '_clm_video_embed', true);
    $sheet_music_id = get_post_meta($post_id, '_clm_sheet_music_id', true);
    $midi_file_id = get_post_meta($post_id, '_clm_midi_file_id', true);
    
    // Check practice tracks as well
    $practice_tracks = get_post_meta($post_id, '_clm_practice_tracks', true);
    $has_practice_tracks = !empty($practice_tracks) && is_array($practice_tracks) && count($practice_tracks) > 0;
    
    // Determine which icons to show
    $has_audio = (!empty($audio_file_id) && $audio_file_id !== '0') || $has_practice_tracks;
    $has_video = !empty($video_embed);
    $has_sheet = !empty($sheet_music_id) && $sheet_music_id !== '0';
    $has_midi = !empty($midi_file_id) && $midi_file_id !== '0';
    
    // If no attachments, return unchanged title
    if (!$has_audio && !$has_video && !$has_sheet && !$has_midi) {
        return $title;
    }
    
    // Build the icons HTML
    $icons_html = '<span class="clm-attachment-icons" style="display: inline-flex; margin-left: 8px; align-items: center;">';
    
    if ($has_audio) {
        $icons_html .= '<span class="clm-attachment-icon" title="Audio Available" style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; margin-right: 5px; background-color: #f0f7ff; border: 1px solid #d0e3ff; border-radius: 50%; color: #3498db; font-size: 14px;">🎵</span>';
    }
    
    if ($has_video) {
        $icons_html .= '<span class="clm-attachment-icon" title="Video Available" style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; margin-right: 5px; background-color: #fff0f0; border: 1px solid #ffd0d0; border-radius: 50%; color: #e74c3c; font-size: 14px;">🎬</span>';
    }
    
    if ($has_sheet) {
        $icons_html .= '<span class="clm-attachment-icon" title="Sheet Music Available" style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; margin-right: 5px; background-color: #f0fff5; border: 1px solid #d0ffd0; border-radius: 50%; color: #27ae60; font-size: 14px;">📄</span>';
    }
    
    if ($has_midi) {
        $icons_html .= '<span class="clm-attachment-icon" title="MIDI File Available" style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; margin-right: 5px; background-color: #f5f0ff; border: 1px solid #d0d0ff; border-radius: 50%; color: #9b59b6; font-size: 14px;">🎹</span>';
    }
    
    $icons_html .= '</span>';
    
    // Return the title with icons appended
    return $title . $icons_html;
}

 
	/**
	 * Register AJAX handlers
	 * Add this to the constructor or hook initialization
	 */
	public function register_ajax_handlers() {
		add_action('wp_ajax_clm_ajax_search', array($this, 'handle_ajax_search'));
		add_action('wp_ajax_nopriv_clm_ajax_search', array($this, 'handle_ajax_search'));
		
		add_action('wp_ajax_clm_ajax_filter', array($this, 'handle_ajax_filter'));
		add_action('wp_ajax_nopriv_clm_ajax_filter', array($this, 'handle_ajax_filter'));
		
		add_action('wp_ajax_clm_shortcode_filter', array($this, 'handle_shortcode_filter'));
		add_action('wp_ajax_nopriv_clm_shortcode_filter', array($this, 'handle_shortcode_filter'));
	}

    /**
     * Register shortcodes
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        // Lyric shortcode
        add_shortcode('clm_lyric', array($this, 'lyric_shortcode'));
        
        // Album shortcode
        add_shortcode('clm_album', array($this, 'album_shortcode'));
        
        // Lyrics list shortcode
        add_shortcode('clm_lyrics_list', array($this, 'lyrics_list_shortcode'));
        
        // Practice widget shortcode
        add_shortcode('clm_practice_widget', array($this, 'practice_widget_shortcode'));
        
        // Practice stats shortcode
        add_shortcode('clm_practice_stats', array($this, 'practice_stats_shortcode'));
        
        // Practice suggestions shortcode
        add_shortcode('clm_practice_suggestions', array($this, 'practice_suggestions_shortcode'));
        
        // Playlist shortcode
        add_shortcode('clm_playlist', array($this, 'playlist_shortcode'));
        // add_shortcode('clm_my_playlists', array($this, 'render_my_playlists_shortcode'));
        $playlists_handler = new CLM_Playlists($this->plugin_name, $this->version);
        add_shortcode('clm_my_playlists', array($playlists_handler, 'render_my_playlists_shortcode'));
        add_shortcode('clm_view_playlist_tracks', array($playlists_handler, 'render_single_playlist_from_url_shortcode'));

        // Submission form shortcode
        add_shortcode('clm_submission_form', array($this, 'submission_form_shortcode'));
        
        // User dashboard shortcode
        add_shortcode('clm_user_dashboard', array($this, 'user_dashboard_shortcode'));
        
        // Search form shortcode
        add_shortcode('clm_search_form', array($this, 'search_form_shortcode'));
		
		 // Add skills dashboard shortcode
		add_shortcode('clm_skills_dashboard', array($this, 'skills_dashboard_shortcode'));
		
		$albums_handler = new CLM_Albums($this->plugin_name, $this->version); // Or get instance if already created
		add_shortcode('clm_album', array($albums_handler, 'album_shortcode_output'));
		add_shortcode('clm_albums', array($albums_handler, 'albums_shortcode_output'));
    }
	public function skills_dashboard_shortcode($atts) {
		if (!is_user_logged_in()) {
			return '<p class="clm-notice">' . __('Please log in to view your skills dashboard.', 'choir-lyrics-manager') . '</p>';
		}
		
		ob_start();
		include CLM_PLUGIN_DIR . 'templates/member-skills-dashboard.php';
		return ob_get_clean();
	}
    /**
     * Lyric shortcode
     *
     * @since     1.0.0
     * @param     array     $atts    Shortcode attributes.
     * @return    string             Shortcode output.
     */
    public function lyric_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'show_details' => 'yes',
            'show_media' => 'yes',
            'show_practice' => 'yes',
        ), $atts);
        
        $lyric_id = intval($atts['id']);
        
        if (!$lyric_id) {
            return '<p class="clm-error">' . __('Lyric ID is required.', 'choir-lyrics-manager') . '</p>';
        }
        
        $lyric = get_post($lyric_id);
        
        if (!$lyric || $lyric->post_type !== 'clm_lyric' || $lyric->post_status !== 'publish') {
            return '<p class="clm-error">' . __('Lyric not found.', 'choir-lyrics-manager') . '</p>';
        }
        
        // Check if user has permission to view
        if (!$this->can_view_lyric($lyric_id)) {
            return '<p class="clm-error">' . __('You do not have permission to view this lyric.', 'choir-lyrics-manager') . '</p>';
        }
        
        ob_start();
        include CLM_PLUGIN_DIR . 'templates/single-lyric.php';
        return ob_get_clean();
    }

    /**
     * Album shortcode
     *
     * @since     1.0.0
     * @param     array     $atts    Shortcode attributes.
     * @return    string             Shortcode output.
     */
    public function album_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'show_details' => 'yes',
            'show_media' => 'yes',
        ), $atts);
        
        $album_id = intval($atts['id']);
        
        if (!$album_id) {
            return '<p class="clm-error">' . __('Album ID is required.', 'choir-lyrics-manager') . '</p>';
        }
        
        $album = get_post($album_id);
        
        if (!$album || $album->post_type !== 'clm_album' || $album->post_status !== 'publish') {
            return '<p class="clm-error">' . __('Album not found.', 'choir-lyrics-manager') . '</p>';
        }
        
        $lyrics_ids = get_post_meta($album_id, '_clm_lyrics', true);
        
        if (!is_array($lyrics_ids) || empty($lyrics_ids)) {
            return '<p class="clm-notice">' . __('This album is empty.', 'choir-lyrics-manager') . '</p>';
        }
        
        $lyrics = array();
        
        foreach ($lyrics_ids as $lyric_id) {
            $lyric = get_post($lyric_id);
            
            if ($lyric && $lyric->post_status === 'publish' && $this->can_view_lyric($lyric_id)) {
                $lyrics[] = $lyric;
            }
        }
        
        ob_start();
        include CLM_PLUGIN_DIR . 'templates/single-album.php';
        return ob_get_clean();
    }

    /**
     * Lyrics list shortcode
     *
     * @since     1.0.0
     * @param     array     $atts    Shortcode attributes.
     * @return    string             Shortcode output.
     */
  /**
 * Lyrics list shortcode (continued)
 */
public function lyrics_list_shortcode($atts) {
    $atts = shortcode_atts(array(
        'number' => 20,
        'genre' => '',
        'composer' => '',
        'language' => '',
        'difficulty' => '',
        'orderby' => 'title',
        'order' => 'ASC',
        'show_details' => 'yes',
        'show_search' => 'yes',
        'show_filters' => 'yes',
        'show_alphabet' => 'yes',
        'show_pagination' => 'yes',
        'ajax_enabled' => 'yes',
        'container_id' => 'clm-shortcode-' . uniqid(),
    ), $atts);
    
    // Get current page
    $paged = get_query_var('paged') ? get_query_var('paged') : 1;
    
    // Apply defaults for display attributes
    $show_details = $atts['show_details'] === 'yes';
    $show_search = $atts['show_search'] === 'yes';
    $show_filters = $atts['show_filters'] === 'yes';
    $show_alphabet = $atts['show_alphabet'] === 'yes';
    $show_pagination = $atts['show_pagination'] === 'yes';
    $ajax_enabled = $atts['ajax_enabled'] === 'yes';
    
    // Validate order parameters
    $valid_orderby = array('title', 'date', 'modified', 'menu_order', 'rand');
    $valid_order = array('ASC', 'DESC');
    
    if (!in_array($atts['orderby'], $valid_orderby)) {
        $atts['orderby'] = 'title';
    }
    
    if (!in_array($atts['order'], $valid_order)) {
        $atts['order'] = 'ASC';
    }
    
    // Build query arguments
    $args = array(
        'post_type' => 'clm_lyric',
        'posts_per_page' => intval($atts['number']),
        'paged' => $paged,
        'post_status' => 'publish',
        'orderby' => $atts['orderby'],
        'order' => $atts['order'],
    );
    
    // Add taxonomy queries from attributes
    $tax_query = array();
    
    if (!empty($atts['genre'])) {
        $tax_query[] = array(
            'taxonomy' => 'clm_genre',
            'field' => 'slug',
            'terms' => explode(',', $atts['genre']),
        );
    }
    
    if (!empty($atts['composer'])) {
        $tax_query[] = array(
            'taxonomy' => 'clm_composer',
            'field' => 'slug',
            'terms' => explode(',', $atts['composer']),
        );
    }
    
    if (!empty($atts['language'])) {
        $tax_query[] = array(
            'taxonomy' => 'clm_language',
            'field' => 'slug',
            'terms' => explode(',', $atts['language']),
        );
    }
    
    if (!empty($atts['difficulty'])) {
        $tax_query[] = array(
            'taxonomy' => 'clm_difficulty',
            'field' => 'slug',
            'terms' => explode(',', $atts['difficulty']),
        );
    }
    
    // Apply filters from GET parameters if they exist
    if (isset($_GET['genre'])) {
        $tax_query[] = array(
            'taxonomy' => 'clm_genre',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['genre']),
        );
    }
    
    if (isset($_GET['language'])) {
        $tax_query[] = array(
            'taxonomy' => 'clm_language',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['language']),
        );
    }
    
    if (isset($_GET['difficulty'])) {
        $tax_query[] = array(
            'taxonomy' => 'clm_difficulty',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['difficulty']),
        );
    }
    
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }
    
    // Add search query if present
    if (isset($_GET['clm_search'])) {
        $args['s'] = sanitize_text_field($_GET['clm_search']);
    }
    
    // Apply ordering from GET parameters
    if (isset($_GET['orderby']) && in_array($_GET['orderby'], $valid_orderby)) {
        $args['orderby'] = sanitize_text_field($_GET['orderby']);
    }
    
    if (isset($_GET['order']) && in_array($_GET['order'], $valid_order)) {
        $args['order'] = sanitize_text_field($_GET['order']);
    }
    
    // Execute query
    $query = new WP_Query($args);
    
    // Debug query info if needed
    if (defined('WP_DEBUG') && WP_DEBUG) {
        // error_log('CLM Shortcode Query: ' . print_r($args, true));
        // error_log('CLM Shortcode SQL: ' . $query->request);
    }
    
    ob_start();
    ?>
    
    <div id="<?php echo esc_attr($atts['container_id']); ?>" class="clm-shortcode-container" 
         data-ajax="<?php echo esc_attr($ajax_enabled ? 'yes' : 'no'); ?>"
         data-per-page="<?php echo esc_attr($args['posts_per_page']); ?>"
         data-orderby="<?php echo esc_attr($args['orderby']); ?>"
         data-order="<?php echo esc_attr($args['order']); ?>">
        
        <?php if ($show_search): ?>
        <!-- Enhanced Search Section -->
        <div class="clm-search-section">
            <div class="clm-search-wrapper">
                <form class="clm-shortcode-search-form">
                    <div class="clm-search-input-wrapper">
                        <input type="text" 
                               class="clm-search-input" 
                               name="clm_search"
                               placeholder="<?php _e('Search lyrics, composers, languages...', 'choir-lyrics-manager'); ?>"
                               value="<?php echo isset($_GET['clm_search']) ? esc_attr($_GET['clm_search']) : ''; ?>"
                               autocomplete="off">
                        <button type="submit" class="clm-search-button">
                            <span class="dashicons dashicons-search"></span>
                        </button>
                        <div class="clm-search-loading" style="display: none;">
                            <span class="dashicons dashicons-update-alt spinning"></span>
                        </div>
                    </div>
                    <div class="clm-search-suggestions" style="display: none;"></div>
                </form>
            </div>
            
            <!-- Quick filters -->
            <?php if ($show_alphabet): ?>
            <div class="clm-quick-filters">
                <button class="clm-quick-filter active" data-filter="all"><?php _e('All', 'choir-lyrics-manager'); ?></button>
                <?php
                $popular_genres = get_terms(array(
                    'taxonomy' => 'clm_genre',
                    'orderby' => 'count',
                    'order' => 'DESC',
                    'number' => 5,
                    'hide_empty' => true,
                ));
                
                foreach ($popular_genres as $genre) {
                    echo '<button class="clm-quick-filter" data-filter="genre" data-value="' . esc_attr($genre->slug) . '">' . esc_html($genre->name) . '</button>';
                }
                ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Results info and controls -->
        <div class="clm-results-info-controls">
            <div class="clm-results-info">
                <span class="clm-results-count"><?php echo absint($query->found_posts); ?></span> 
                <?php _e('lyrics found', 'choir-lyrics-manager'); ?>
            </div>
            
            <div class="clm-view-options">
                <select class="clm-items-per-page">
                    <option value="10" <?php selected($args['posts_per_page'], 10); ?>>10 <?php _e('per page', 'choir-lyrics-manager'); ?></option>
                    <option value="20" <?php selected($args['posts_per_page'], 20); ?>>20 <?php _e('per page', 'choir-lyrics-manager'); ?></option>
                    <option value="50" <?php selected($args['posts_per_page'], 50); ?>>50 <?php _e('per page', 'choir-lyrics-manager'); ?></option>
                    <option value="100" <?php selected($args['posts_per_page'], 100); ?>>100 <?php _e('per page', 'choir-lyrics-manager'); ?></option>
                </select>
                
                <?php if ($show_filters): ?>
                <button class="clm-toggle-filters">
                    <span class="dashicons dashicons-filter"></span>
                    <?php _e('Advanced Filters', 'choir-lyrics-manager'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($show_filters): ?>
        <!-- Enhanced Filters (Hidden by default) -->
        <div class="clm-advanced-filters" style="display: none;">
            <form class="clm-shortcode-filter-form">
                <div class="clm-filters-grid">
                    <!-- Genre Filter -->
                    <div class="clm-filter-group">
                        <label for="clm-genre-select"><?php _e('Genre', 'choir-lyrics-manager'); ?></label>
                        <select name="genre" class="clm-filter-select">
                            <option value=""><?php _e('All Genres', 'choir-lyrics-manager'); ?></option>
                            <?php
                            $genres = get_terms(array(
                                'taxonomy' => 'clm_genre',
                                'hide_empty' => true,
                            ));
                            
                            foreach ($genres as $genre) {
                                $selected = isset($_GET['genre']) && $_GET['genre'] == $genre->slug ? 'selected' : '';
                                echo '<option value="' . esc_attr($genre->slug) . '" ' . $selected . '>' . esc_html($genre->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- Language Filter -->
                    <div class="clm-filter-group">
                        <label for="clm-language-select"><?php _e('Language', 'choir-lyrics-manager'); ?></label>
                        <select name="language" class="clm-filter-select">
                            <option value=""><?php _e('All Languages', 'choir-lyrics-manager'); ?></option>
                            <?php
                            $languages = get_terms(array(
                                'taxonomy' => 'clm_language',
                                'hide_empty' => true,
                            ));
                            
                            foreach ($languages as $language) {
                                $selected = isset($_GET['language']) && $_GET['language'] == $language->slug ? 'selected' : '';
                                echo '<option value="' . esc_attr($language->slug) . '" ' . $selected . '>' . esc_html($language->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- Difficulty Filter -->
                    <div class="clm-filter-group">
                        <label for="clm-difficulty-select"><?php _e('Difficulty', 'choir-lyrics-manager'); ?></label>
                        <select name="difficulty" class="clm-filter-select">
                            <option value=""><?php _e('All Difficulties', 'choir-lyrics-manager'); ?></option>
                            <?php
                            $difficulties = get_terms(array(
                                'taxonomy' => 'clm_difficulty',
                                'hide_empty' => true,
                            ));
                            
                            foreach ($difficulties as $difficulty) {
                                $selected = isset($_GET['difficulty']) && $_GET['difficulty'] == $difficulty->slug ? 'selected' : '';
                                echo '<option value="' . esc_attr($difficulty->slug) . '" ' . $selected . '>' . esc_html($difficulty->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- Sort Options -->
                    <div class="clm-filter-group">
                        <fieldset>
                            <legend><?php _e('Sort Options', 'choir-lyrics-manager'); ?></legend>
                            <div class="clm-sort-options">
                                <div>
                                    <label for="clm-sort-select"><?php _e('Sort By', 'choir-lyrics-manager'); ?></label>
                                    <select id="clm-sort-select" name="orderby" class="clm-filter-select">
                                        <option value="title" <?php selected($args['orderby'], 'title'); ?>><?php _e('Title', 'choir-lyrics-manager'); ?></option>
                                        <option value="date" <?php selected($args['orderby'], 'date'); ?>><?php _e('Date Added', 'choir-lyrics-manager'); ?></option>
                                        <option value="modified" <?php selected($args['orderby'], 'modified'); ?>><?php _e('Last Modified', 'choir-lyrics-manager'); ?></option>
                                    </select>
                                </div>
                                <div>
                                    <label for="clm-order-select"><?php _e('Order', 'choir-lyrics-manager'); ?></label>
                                    <select id="clm-order-select" name="order" class="clm-filter-select">
                                        <option value="ASC" <?php selected($args['order'], 'ASC'); ?>><?php _e('Ascending', 'choir-lyrics-manager'); ?></option>
                                        <option value="DESC" <?php selected($args['order'], 'DESC'); ?>><?php _e('Descending', 'choir-lyrics-manager'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>
                
                <div class="clm-filter-actions">
                    <button type="submit" class="clm-button clm-apply-filters"><?php _e('Apply Filters', 'choir-lyrics-manager'); ?></button>
                    <button type="reset" class="clm-button-text clm-reset-filters"><?php _e('Reset All', 'choir-lyrics-manager'); ?></button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <?php if ($show_alphabet): ?>
        <!-- Alphabet Navigation -->
        <div class="clm-alphabet-nav">
            <a href="#" class="clm-alpha-link active" data-letter="all"><?php _e('All', 'choir-lyrics-manager'); ?></a>
            <?php foreach (range('A', 'Z') as $letter): ?>
                <a href="#" class="clm-alpha-link" data-letter="<?php echo $letter; ?>"><?php echo $letter; ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Loading indicator -->
        <div class="clm-loading-overlay" style="display: none;">
            <div class="clm-loading-spinner"></div>
        </div>
        
        <!-- Results container -->
        <div class="clm-shortcode-results">
            <?php if ($query->have_posts()) : ?>
                <ul class="clm-items-list">
                    <?php while ($query->have_posts()) : $query->the_post(); ?>
                        <li id="lyric-<?php the_ID(); ?>" class="clm-item clm-lyric-item" data-title="<?php echo esc_attr(get_the_title()); ?>">
                            <div class="clm-item-card">
                                <h2 class="clm-item-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h2>
                                
                                <?php if ($show_details): ?>
                                <div class="clm-item-meta">
                                    <?php
                                    $meta_items = array();
                                    
                                    // Show composer if available
                                    $composer = get_post_meta(get_the_ID(), '_clm_composer', true);
                                    if ($composer) {
                                        $meta_items[] = '<span class="clm-meta-composer"><strong>' . __('Composer:', 'choir-lyrics-manager') . '</strong> ' . esc_html($composer) . '</span>';
                                    }
                                    
                                    // Show language if available
                                    $language = get_post_meta(get_the_ID(), '_clm_language', true);
                                    if ($language) {
                                        $meta_items[] = '<span class="clm-meta-language"><strong>' . __('Language:', 'choir-lyrics-manager') . '</strong> ' . esc_html($language) . '</span>';
                                    }
                                    
                                    // Show difficulty if available and enabled
                                    $settings = new CLM_Settings($this->plugin_name, $this->version);
                                    if ($settings->get_setting('show_difficulty', true)) {
                                        $difficulty = get_post_meta(get_the_ID(), '_clm_difficulty', true);
                                        if ($difficulty) {
                                            $stars = '';
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $difficulty) {
                                                    $stars .= '<span class="dashicons dashicons-star-filled"></span>';
                                                } else {
                                                    $stars .= '<span class="dashicons dashicons-star-empty"></span>';
                                                }
                                            }
                                            $meta_items[] = '<span class="clm-meta-difficulty"><strong>' . __('Difficulty:', 'choir-lyrics-manager') . '</strong> ' . $stars . '</span>';
                                        }
                                    }
                                    
                                    echo implode(' <span class="clm-meta-separator">•</span> ', $meta_items);
                                    ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="clm-item-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                                
                                <div class="clm-item-actions">
                                    <a href="<?php the_permalink(); ?>" class="clm-button"><?php _e('View Lyric', 'choir-lyrics-manager'); ?></a>
                                    
                                    <?php if (is_user_logged_in()): ?>
                                        <?php
                                        // Show add to playlist button
                                        $playlists = new CLM_Playlists($this->plugin_name, $this->version);
                                        echo $playlists->render_playlist_dropdown(get_the_ID());
                                        ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
                
                <?php if ($show_pagination && $query->max_num_pages > 1): ?>
                <!-- Pagination -->
                <div class="clm-shortcode-pagination" data-container="shortcode">
                    <?php
                    echo paginate_links(array(
                        'base' => $ajax_enabled ? '#' : str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                        'format' => '?paged=%#%',
                        'current' => $paged,
                        'total' => $query->max_num_pages,
                        'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . __('Previous', 'choir-lyrics-manager'),
                        'next_text' => __('Next', 'choir-lyrics-manager') . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
                        'type' => 'list',
                        'before_page_number' => '<span data-page="',  // Add data attribute
                        'after_page_number' => '">',                  // Close span tag
                        'end_size' => 1,
                        'mid_size' => 2,
                    ));
                    ?>
                    
                    <div class="clm-page-jump">
                        <label><?php _e('Jump to page:', 'choir-lyrics-manager'); ?></label>
                        <input type="number" 
                               class="clm-page-jump-input"
                               min="1" 
                               max="<?php echo $query->max_num_pages; ?>" 
                               value="<?php echo $paged; ?>">
                        <button class="clm-page-jump-button clm-button-small"><?php _e('Go', 'choir-lyrics-manager'); ?></button>
                    </div>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="clm-no-results">
                    <p class="clm-notice"><?php _e('No lyrics found matching your criteria.', 'choir-lyrics-manager'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php wp_reset_postdata(); ?>
        </div>
    </div>
    
    <script>
    // Initialize the shortcode features when document is ready
    (function() {
        var checkInterval = setInterval(function() {
            if (typeof jQuery !== 'undefined' && typeof window.initShortcodeFeatures === 'function') {
                clearInterval(checkInterval);
                jQuery(document).ready(function($) {
                    window.initShortcodeFeatures('<?php echo esc_js($atts['container_id']); ?>');
                });
            }
        }, 50);
        
        // Stop checking after 5 seconds
        setTimeout(function() {
            clearInterval(checkInterval);
        }, 5000);
    })();
    </script>
    
    <?php
    return ob_get_clean();
}

   /**
 * Practice widget shortcode
 *
 * @since     1.0.0
 * @param     array     $atts    Shortcode attributes.
 * @return    string             Shortcode output.
 */
public function practice_widget_shortcode($atts) {
    $atts = shortcode_atts(array(
        'lyric_id' => 0,
    ), $atts);
    
    $lyric_id = intval($atts['lyric_id']);
    
    // If no lyric_id provided, try to get it automatically
    if (!$lyric_id) {
        global $post;
        
        // Check if we're on a single lyric page
        if (is_singular('clm_lyric') && $post) {
            $lyric_id = $post->ID;
        }
        // Check if we're in the loop
        elseif (in_the_loop() && get_post_type() === 'clm_lyric') {
            $lyric_id = get_the_ID();
        }
        // Check for query parameter
        elseif (isset($_GET['lyric_id'])) {
            $lyric_id = intval($_GET['lyric_id']);
        }
    }
    
    if (!$lyric_id) {
        return '<p class="clm-error">' . __('Lyric ID is required.', 'choir-lyrics-manager') . '</p>';
    }
    
    $lyric = get_post($lyric_id);
    
    if (!$lyric || $lyric->post_type !== 'clm_lyric' || $lyric->post_status !== 'publish') {
        return '<p class="clm-error">' . __('Lyric not found.', 'choir-lyrics-manager') . '</p>';
    }
    
    // Check if practice tracking is enabled
    if (!$this->settings->get_setting('enable_practice', true)) {
        return '<p class="clm-notice">' . __('Practice tracking is currently disabled.', 'choir-lyrics-manager') . '</p>';
    }
    
    $practice = new CLM_Practice($this->plugin_name, $this->version);
    return $practice->render_practice_widget($lyric_id);
}

    /**
     * Practice stats shortcode
     *
     * @since     1.0.0
     * @param     array     $atts    Shortcode attributes.
     * @return    string             Shortcode output.
     */
    public function practice_stats_shortcode($atts) {
        $atts = shortcode_atts(array(
            'lyric_id' => 0,
            'show_history' => 'yes',
        ), $atts);
        
        $lyric_id = intval($atts['lyric_id']);
        
        if (!$lyric_id) {
            return '<p class="clm-error">' . __('Lyric ID is required.', 'choir-lyrics-manager') . '</p>';
        }
        
        $lyric = get_post($lyric_id);
        
        if (!$lyric || $lyric->post_type !== 'clm_lyric' || $lyric->post_status !== 'publish') {
            return '<p class="clm-error">' . __('Lyric not found.', 'choir-lyrics-manager') . '</p>';
        }
        
        // Check if practice tracking is enabled
        if (!$this->settings->get_setting('enable_practice', true)) {
            return '<p class="clm-notice">' . __('Practice tracking is currently disabled.', 'choir-lyrics-manager') . '</p>';
        }
        
        $practice = new CLM_Practice($this->plugin_name, $this->version);
        return $practice->render_practice_stats_shortcode($atts);
    }

    /**
     * Practice suggestions shortcode
     *
     * @since     1.0.0
     * @param     array     $atts    Shortcode attributes.
     * @return    string             Shortcode output.
     */
    public function practice_suggestions_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 5,
        ), $atts);
        
        // Check if practice tracking is enabled
        if (!$this->settings->get_setting('enable_practice', true)) {
            return '<p class="clm-notice">' . __('Practice tracking is currently disabled.', 'choir-lyrics-manager') . '</p>';
        }
        
        $practice = new CLM_Practice($this->plugin_name, $this->version);
        return $practice->render_practice_suggestions_shortcode($atts);
    }

    /**
     * Playlist shortcode
     *
     * @since     1.0.0
     * @param     array     $atts    Shortcode attributes.
     * @return    string             Shortcode output.
     */
    public function playlist_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'show_media' => 'yes',
            'show_actions' => 'yes',
        ), $atts);
        
        $playlist_id = intval($atts['id']);
        
        if (!$playlist_id) {
            return '<p class="clm-error">' . __('Playlist ID is required.', 'choir-lyrics-manager') . '</p>';
        }
        
        $playlists = new CLM_Playlists($this->plugin_name, $this->version);
        return $playlists->render_playlist_shortcode($atts);
    }

    /**
     * Submission form shortcode
     *
     * @since     1.0.0
     * @param     array     $atts    Shortcode attributes.
     * @return    string             Shortcode output.
     */
    public function submission_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'redirect' => '',
        ), $atts);
        
        $submissions = new CLM_Submissions($this->plugin_name, $this->version);
        return $submissions->render_submission_form($atts);
    }

    /**
     * User dashboard shortcode
     *
     * @since     1.0.0
     * @param     array     $atts    Shortcode attributes.
     * @return    string             Shortcode output.
     */
    public function user_dashboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_practice' => 'yes',
            'show_playlists' => 'yes',
            'show_submissions' => 'yes',
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<p class="clm-notice">' . __('Please log in to view your dashboard.', 'choir-lyrics-manager') . '</p>';
        }
        
        ob_start();
        include CLM_PLUGIN_DIR . 'templates/member-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Search form shortcode
     *
     * @since     1.0.0
     * @param     array     $atts    Shortcode attributes.
     * @return    string             Shortcode output.
     */
    public function search_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => __('Search lyrics...', 'choir-lyrics-manager'),
            'button_text' => __('Search', 'choir-lyrics-manager'),
            'show_filters' => 'yes',
        ), $atts);
        
        ob_start();
        include CLM_PLUGIN_DIR . 'templates/search-form.php';
        return ob_get_clean();
    }

    /**
     * Load custom template for single lyric
     *
     * @since     1.0.0
     * @param     string    $template    Template path.
     * @return    string                 Modified template path.
     */
   public function load_lyric_template($template) {
    global $post;

    if (is_object($post) && isset($post->post_type) && $post->post_type === 'clm_lyric') { // 'clm_lyric' is your CPT slug

        // 1. Look for single-clm_lyric.php in child theme
        $theme_template_child = get_stylesheet_directory() . '/single-clm_lyric.php';
        if (file_exists($theme_template_child)) {
            // error_log('CLM_TEMPLATE_LOAD: Using CHILD THEME template: ' . $theme_template_child);
            return $theme_template_child;
        }

        // 2. Look for single-clm_lyric.php in parent theme
        $theme_template_parent = get_template_directory() . '/single-clm_lyric.php';
        if (file_exists($theme_template_parent)) {
            // error_log('CLM_TEMPLATE_LOAD: Using PARENT THEME template: ' . $theme_template_parent);
            return $theme_template_parent;
        }

        // 3. Fall back to plugin's template (with the conventional name)
        $plugin_template = CLM_PLUGIN_DIR . 'templates/single-clm_lyric.php'; // CORRECTED FILENAME
        if (file_exists($plugin_template)) {
            // error_log('CLM_TEMPLATE_LOAD: Using PLUGIN template: ' . $plugin_template . ' for post ID ' . $post->ID);
            return $plugin_template;
        } else {
            // error_log('CLM_TEMPLATE_LOAD_ERROR: Plugin template NOT FOUND: ' . $plugin_template);
        }
    }
    return $template; // Return original template if no conditions met or plugin template not found
}

    /**
     * Load custom template for archive
     *
     * @since     1.0.0
     * @param     string    $template    Template path.
     * @return    string                 Modified template path.
     */
    public function load_archive_template($template) {
        if (is_post_type_archive('clm_lyric')) {
            // Check if a custom template exists in the theme
            $theme_template = locate_template(array('archive-clm_lyric.php'));
            
            if ($theme_template) {
                return $theme_template;
            }
            
            // Use plugin template
            return CLM_PLUGIN_DIR . 'templates/archive-lyric.php';
        } elseif (is_post_type_archive('clm_album')) {
            // Check if a custom template exists in the theme
            $theme_template = locate_template(array('archive-clm_album.php'));
            
            if ($theme_template) {
                return $theme_template;
            }
            
            // Use plugin template
            return CLM_PLUGIN_DIR . 'templates/archive-album.php';
        } elseif (is_tax('clm_genre') || is_tax('clm_composer') || is_tax('clm_language') || is_tax('clm_difficulty')) {
            // Check if a custom template exists in the theme
            $theme_template = locate_template(array('taxonomy-clm.php'));
            
            if ($theme_template) {
                return $theme_template;
            }
            
            // Use plugin template
            return CLM_PLUGIN_DIR . 'templates/taxonomy-clm.php';
        }
        
        return $template;
    }

    /**
     * Load custom template for search
     *
     * @since     1.0.0
     * @param     string    $template    Template path.
     * @return    string                 Modified template path.
     */
    public function load_search_template($template) {
        global $wp_query;
        
        // Check if we're searching for lyrics
        if (isset($_GET['post_type']) && $_GET['post_type'] === 'clm_lyric') {
            // Check if a custom template exists in the theme
            $theme_template = locate_template(array('search-clm_lyric.php'));
            
            if ($theme_template) {
                return $theme_template;
            }
            
            // Use plugin template
            return CLM_PLUGIN_DIR . 'templates/search-results.php';
        }
        
        return $template;
    }

    /**
     * Check if user can view a lyric
     *
     * @since     1.0.0
     * @param     int       $lyric_id    The lyric ID.
     * @return    boolean                Whether the user can view the lyric.
     */
    private function can_view_lyric($lyric_id) {
        // Public users can view public lyrics
        $post_status = get_post_status($lyric_id);
        
        if ($post_status === 'publish') {
            return true;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return false;
        }
        
        // Get the post
        $post = get_post($lyric_id);
        
        // Authors can view their own lyrics
        if ($post->post_author == get_current_user_id()) {
            return true;
        }
        
        // Check user capabilities
        return current_user_can('read_private_clm_lyrics');
    }



    /**
     * Get user's playlists
     *
     * @since     1.0.0
     * @return    string    HTML for the playlists section.
     */
    public function get_user_playlists_html() {
        if (!is_user_logged_in()) {
            return '<p class="clm-notice">' . __('Please log in to view your playlists.', 'choir-lyrics-manager') . '</p>';
        }
        
        $playlists_manager = new CLM_Playlists($this->plugin_name, $this->version);
        $playlists = $playlists_manager->get_user_playlists();
        
        ob_start();
        ?>
        <div class="clm-user-playlists">
            <h3><?php _e('Your Playlists', 'choir-lyrics-manager'); ?></h3>
            
            <?php if (empty($playlists)) : ?>
                <p class="clm-notice"><?php _e('You haven\'t created any playlists yet.', 'choir-lyrics-manager'); ?></p>
            <?php else : ?>
                <ul class="clm-playlists-list">
                    <?php foreach ($playlists as $playlist) : ?>
                        <li class="clm-playlist-item">
                            <div class="clm-playlist-details">
                                <h4 class="clm-playlist-title"><?php echo esc_html($playlist->post_title); ?></h4>
                                
                                <?php
                                $lyrics_count = 0;
                                $lyrics_ids = get_post_meta($playlist->ID, '_clm_playlist_lyrics', true);
                                
                                if (is_array($lyrics_ids)) {
                                    $lyrics_count = count($lyrics_ids);
                                }
                                ?>
                                
                                <span class="clm-playlist-count">
                                    <?php echo sprintf(_n('%d lyric', '%d lyrics', $lyrics_count, 'choir-lyrics-manager'), $lyrics_count); ?>
                                </span>
                                
                                <?php
                                $visibility = get_post_meta($playlist->ID, '_clm_playlist_visibility', true);
                                $visibility_label = $visibility === 'public' ? __('Public', 'choir-lyrics-manager') : __('Private', 'choir-lyrics-manager');
                                ?>
                                
                                <span class="clm-playlist-visibility clm-visibility-<?php echo $visibility; ?>"><?php echo $visibility_label; ?></span>
                            </div>
                            
                            <div class="clm-playlist-actions">
                                <a href="<?php echo get_permalink($playlist->ID); ?>" class="clm-button clm-button-small"><?php _e('View', 'choir-lyrics-manager'); ?></a>
                                <a href="<?php echo get_edit_post_link($playlist->ID); ?>" class="clm-button clm-button-small"><?php _e('Edit', 'choir-lyrics-manager'); ?></a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <button class="clm-create-playlist-button clm-button clm-button-primary"><?php _e('Create New Playlist', 'choir-lyrics-manager'); ?></button>
            
            <div class="clm-create-playlist-form" style="display:none;">
                <h4><?php _e('Create New Playlist', 'choir-lyrics-manager'); ?></h4>
                
                <div class="clm-form-field">
                    <label for="clm-playlist-name"><?php _e('Playlist Name', 'choir-lyrics-manager'); ?></label>
                    <input type="text" id="clm-playlist-name" class="clm-playlist-name" placeholder="<?php _e('Enter playlist name', 'choir-lyrics-manager'); ?>">
                </div>
                
                <div class="clm-form-field">
                    <label for="clm-playlist-description"><?php _e('Description (optional)', 'choir-lyrics-manager'); ?></label>
                    <textarea id="clm-playlist-description" class="clm-playlist-description" rows="3"></textarea>
                </div>
                
                <div class="clm-form-field">
                    <label><?php _e('Visibility', 'choir-lyrics-manager'); ?></label>
                    <div class="clm-radio-group">
                        <label><input type="radio" name="clm-playlist-visibility" value="private" checked> <?php _e('Private', 'choir-lyrics-manager'); ?></label>
                        <label><input type="radio" name="clm-playlist-visibility" value="public"> <?php _e('Public', 'choir-lyrics-manager'); ?></label>
                    </div>
                </div>
                
                <div class="clm-form-actions">
                    <button class="clm-submit-playlist clm-button clm-button-primary"><?php _e('Create Playlist', 'choir-lyrics-manager'); ?></button>
                    <button class="clm-cancel-playlist clm-button"><?php _e('Cancel', 'choir-lyrics-manager'); ?></button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get user's practice stats
     *
     * @since     1.0.0
     * @return    string    HTML for the practice stats section.
     */
    public function get_user_practice_stats_html() {
        if (!is_user_logged_in()) {
            return '<p class="clm-notice">' . __('Please log in to view your practice statistics.', 'choir-lyrics-manager') . '</p>';
        }
        
        // Check if practice tracking is enabled
        if (!$this->settings->get_setting('enable_practice', true)) {
            return '<p class="clm-notice">' . __('Practice tracking is currently disabled.', 'choir-lyrics-manager') . '</p>';
        }
        
        $practice = new CLM_Practice($this->plugin_name, $this->version);
        $stats = $practice->get_user_practice_stats();
        
        ob_start();
        ?>
        <div class="clm-user-practice-stats">
            <h3><?php _e('Your Practice Statistics', 'choir-lyrics-manager'); ?></h3>
            
            <?php if ($stats['total_sessions'] === 0) : ?>
                <p class="clm-notice"><?php _e('You haven\'t logged any practice sessions yet.', 'choir-lyrics-manager'); ?></p>
            <?php else : ?>
                <div class="clm-practice-stats-summary">
                    <div class="clm-stat-box">
                        <span class="clm-stat-value"><?php echo $stats['total_sessions']; ?></span>
                        <span class="clm-stat-label"><?php _e('Practice Sessions', 'choir-lyrics-manager'); ?></span>
                    </div>
                    
                    <div class="clm-stat-box">
                        <span class="clm-stat-value"><?php echo $this->format_duration($stats['total_time']); ?></span>
                        <span class="clm-stat-label"><?php _e('Total Practice Time', 'choir-lyrics-manager'); ?></span>
                    </div>
                    
                    <div class="clm-stat-box">
                        <span class="clm-stat-value"><?php echo $stats['lyrics_practiced']; ?></span>
                        <span class="clm-stat-label"><?php _e('Lyrics Practiced', 'choir-lyrics-manager'); ?></span>
                    </div>
                    
                    <?php if ($stats['lyrics_practiced'] > 0) : ?>
                        <div class="clm-stat-box">
                            <span class="clm-stat-value"><?php echo $this->format_duration($stats['avg_time_per_lyric']); ?></span>
                            <span class="clm-stat-label"><?php _e('Avg. Time per Lyric', 'choir-lyrics-manager'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($stats['lyrics_practiced'] > 0) : ?>
                    <div class="clm-practice-suggestions">
                        <h4><?php _e('Practice Suggestions', 'choir-lyrics-manager'); ?></h4>
                        <?php echo $practice->render_practice_suggestions_shortcode(array('limit' => 3)); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get user's submissions
     *
     * @since     1.0.0
     * @return    string    HTML for the submissions section.
     */
    public function get_user_submissions_html() {
        if (!is_user_logged_in()) {
            return '<p class="clm-notice">' . __('Please log in to view your submissions.', 'choir-lyrics-manager') . '</p>';
        }
        
        $submissions = get_posts(array(
            'post_type' => 'clm_lyric',
            'author' => get_current_user_id(),
            'posts_per_page' => -1,
            'post_status' => array('publish', 'pending'),
        ));
        
        ob_start();
        ?>
        <div class="clm-user-submissions">
            <h3><?php _e('Your Submissions', 'choir-lyrics-manager'); ?></h3>
            
            <?php if (empty($submissions)) : ?>
                <p class="clm-notice"><?php _e('You haven\'t submitted any lyrics yet.', 'choir-lyrics-manager'); ?></p>
            <?php else : ?>
                <table class="clm-submissions-table">
                    <thead>
                        <tr>
                            <th><?php _e('Title', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Date', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Status', 'choir-lyrics-manager'); ?></th>
                            <th><?php _e('Actions', 'choir-lyrics-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission) : ?>
                            <tr>
                                <td><?php echo esc_html($submission->post_title); ?></td>
                                <td><?php echo get_the_date('', $submission->ID); ?></td>
                                <td>
                                    <?php if ($submission->post_status === 'publish') : ?>
                                        <span class="clm-status-published"><?php _e('Published', 'choir-lyrics-manager'); ?></span>
                                    <?php else : ?>
                                        <span class="clm-status-pending"><?php _e('Pending Review', 'choir-lyrics-manager'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($submission->post_status === 'publish') : ?>
                                        <a href="<?php echo get_permalink($submission->ID); ?>" class="clm-button clm-button-small"><?php _e('View', 'choir-lyrics-manager'); ?></a>
                                    <?php endif; ?>
                                    <a href="<?php echo get_edit_post_link($submission->ID); ?>" class="clm-button clm-button-small"><?php _e('Edit', 'choir-lyrics-manager'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <p>
                <a href="<?php echo esc_url(add_query_arg('clm_action', 'submit_lyric', home_url())); ?>" class="clm-button clm-button-primary"><?php _e('Submit New Lyric', 'choir-lyrics-manager'); ?></a>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Format duration in minutes to hours and minutes
     *
     * @since     1.0.0
     * @param     int       $minutes    Duration in minutes.
     * @return    string                Formatted duration.
     */
    private function format_duration($minutes) {
        $minutes = intval($minutes);
        
        if ($minutes < 60) {
            return sprintf(_n('%d minute', '%d minutes', $minutes, 'choir-lyrics-manager'), $minutes);
        }
        
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($mins === 0) {
            return sprintf(_n('%d hour', '%d hours', $hours, 'choir-lyrics-manager'), $hours);
        }
        
        return sprintf(
            _n('%d hour', '%d hours', $hours, 'choir-lyrics-manager') . ', ' . _n('%d minute', '%d minutes', $mins, 'choir-lyrics-manager'),
            $hours,
            $mins
        );
    }


/**
 * Register custom page templates
 *
 * @param array $templates The existing templates array
 * @return array Modified templates array
 */
public function register_custom_templates($templates) {
    $templates['media-browse.php'] = __('Media Browser', 'choir-lyrics-manager');
    return $templates;
}

/**
 * Add the template to the page templates
 */
public function add_custom_template_location($template) {
    if (is_page_template('media-browse.php')) {
        $template = plugin_dir_path(dirname(__FILE__)) . 'templates/media-browse.php';
    }
    return $template;
}
}

