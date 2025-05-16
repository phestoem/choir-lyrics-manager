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
 use CLM_Ajax_Handlers;
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
		$this->register_ajax_handlers();
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, CLM_PLUGIN_URL . 'assets/css/public.css', array(), $this->version, 'all');
        wp_add_inline_style($this->plugin_name, $this->settings->get_custom_css());
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
  /**
 * Update the enqueue_scripts method to include new nonces
 */
public function enqueue_scripts() {
    wp_enqueue_script($this->plugin_name, CLM_PLUGIN_URL . 'js/public.js', array('jquery'), $this->version, true);
    
	// Create nonces
   	 	
	// Debug the nonce generation
    $filter_nonce = wp_create_nonce('clm_filter_nonce');
	error_log('Creating filter nonce with action "clm_filter_nonce": ' . $filter_nonce);
    error_log('CLM Nonce Generated: ' . $filter_nonce);
	
	 $all_nonces = array(
        'practice' => wp_create_nonce('clm_practice_nonce'),
        'playlist' => wp_create_nonce('clm_playlist_nonce'),
        'search' => wp_create_nonce('clm_search_nonce'),
        'filter' => wp_create_nonce('clm_filter_nonce'),
        'skills' => wp_create_nonce('clm_skills_nonce'), // Add skills nonce
		'shortcode_filter' => wp_create_nonce('clm_shortcode_filter'), // Add this line
    );
	error_log('All nonces: ' . print_r($all_nonces, true));
	
    wp_localize_script($this->plugin_name, 'clm_vars', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
		'is_user_logged_in' => is_user_logged_in(),
        'nonce' => $all_nonces,
        'text' => array(
            'practice_success' => __('Practice logged successfully!', 'choir-lyrics-manager'),
            'playlist_success' => __('Playlist updated successfully!', 'choir-lyrics-manager'),
            'playlist_error' => __('Error updating playlist.', 'choir-lyrics-manager'),
            'confirm_remove' => __('Are you sure you want to remove this item?', 'choir-lyrics-manager'),
            'search_min_length' => __('Please enter at least 2 characters', 'choir-lyrics-manager'),
            'searching' => __('Searching...', 'choir-lyrics-manager'),
            'loading' => __('Loading...', 'choir-lyrics-manager'),
            'error' => __('An error occurred. Please try again.', 'choir-lyrics-manager'),			
			 // Add skill management texts
            'set_goal_title' => __('Set Practice Goal', 'choir-lyrics-manager'),
            'set_goal_description' => __('Choose a target date for mastering this piece:', 'choir-lyrics-manager'),
            'confirm' => __('Set Goal', 'choir-lyrics-manager'),
            'cancel' => __('Cancel', 'choir-lyrics-manager'),
            'saving' => __('Saving...', 'choir-lyrics-manager'),
            'goal_set_success' => __('Goal set successfully!', 'choir-lyrics-manager'),
            'please_select_date' => __('Please select a date', 'choir-lyrics-manager'),
            'goal' => __('Goal', 'choir-lyrics-manager'),
        ),
    ));
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
        
        // Submission form shortcode
        add_shortcode('clm_submission_form', array($this, 'submission_form_shortcode'));
        
        // User dashboard shortcode
        add_shortcode('clm_user_dashboard', array($this, 'user_dashboard_shortcode'));
        
        // Search form shortcode
        add_shortcode('clm_search_form', array($this, 'search_form_shortcode'));
		
		 // Add skills dashboard shortcode
		add_shortcode('clm_skills_dashboard', array($this, 'skills_dashboard_shortcode'));
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
   public function lyrics_list_shortcode($atts) {
    $atts = shortcode_atts(array(
        'number' => 10,
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
    if (isset($_GET['orderby'])) {
        $args['orderby'] = sanitize_text_field($_GET['orderby']);
    }
    
    if (isset($_GET['order'])) {
        $args['order'] = sanitize_text_field($_GET['order']);
    }
    
    // Execute query
    $query = new WP_Query($args);
    
    ob_start();
    ?>
    
    <div id="<?php echo esc_attr($atts['container_id']); ?>" class="clm-shortcode-container" data-ajax="<?php echo esc_attr($atts['ajax_enabled']); ?>">
        
        <?php if ($atts['show_search'] === 'yes'): ?>
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
        </div>
        <?php endif; ?>
        
        <!-- Results info and controls -->
        <div class="clm-results-controls">
            <div class="clm-results-info">
                <span class="clm-results-count"><?php echo absint($query->found_posts); ?></span> 
                <?php _e('lyrics found', 'choir-lyrics-manager'); ?>
            </div>
            
            <div class="clm-view-options">
                <select class="clm-items-per-page">
                    <option value="10" <?php selected($atts['number'], 10); ?>>10 <?php _e('per page', 'choir-lyrics-manager'); ?></option>
                    <option value="20" <?php selected($atts['number'], 20); ?>>20 <?php _e('per page', 'choir-lyrics-manager'); ?></option>
                    <option value="50" <?php selected($atts['number'], 50); ?>>50 <?php _e('per page', 'choir-lyrics-manager'); ?></option>
                    <option value="100" <?php selected($atts['number'], 100); ?>>100 <?php _e('per page', 'choir-lyrics-manager'); ?></option>
                </select>
                
                <?php if ($atts['show_filters'] === 'yes'): ?>
                <button class="clm-toggle-filters">
                    <span class="dashicons dashicons-filter"></span>
                    <?php _e('Advanced Filters', 'choir-lyrics-manager'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($atts['show_filters'] === 'yes'): ?>
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
                        <label for="clm-sort-select"><?php _e('Sort By', 'choir-lyrics-manager'); ?></label>
                        <div class="clm-sort-options">
                            <select name="orderby" class="clm-filter-select">
                                <option value="title" <?php selected(isset($_GET['orderby']) ? $_GET['orderby'] : $atts['orderby'], 'title'); ?>><?php _e('Title', 'choir-lyrics-manager'); ?></option>
                                <option value="date" <?php selected(isset($_GET['orderby']) ? $_GET['orderby'] : $atts['orderby'], 'date'); ?>><?php _e('Date Added', 'choir-lyrics-manager'); ?></option>
                                <option value="modified" <?php selected(isset($_GET['orderby']) ? $_GET['orderby'] : $atts['orderby'], 'modified'); ?>><?php _e('Last Modified', 'choir-lyrics-manager'); ?></option>
                            </select>
                            <select name="order" class="clm-filter-select">
                                <option value="ASC" <?php selected(isset($_GET['order']) ? $_GET['order'] : $atts['order'], 'ASC'); ?>><?php _e('Ascending', 'choir-lyrics-manager'); ?></option>
                                <option value="DESC" <?php selected(isset($_GET['order']) ? $_GET['order'] : $atts['order'], 'DESC'); ?>><?php _e('Descending', 'choir-lyrics-manager'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="clm-filter-actions">
                    <button type="submit" class="clm-button clm-apply-filters"><?php _e('Apply Filters', 'choir-lyrics-manager'); ?></button>
                    <button type="reset" class="clm-button-text clm-reset-filters"><?php _e('Reset All', 'choir-lyrics-manager'); ?></button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <?php if ($atts['show_alphabet'] === 'yes'): ?>
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
                                
                                <?php if ($atts['show_details'] === 'yes'): ?>
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
                                    if ($this->settings->get_setting('show_difficulty', true)) {
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
                                        $playlists = new CLM_Playlists('choir-lyrics-manager', CLM_VERSION);
                                        echo $playlists->render_playlist_dropdown(get_the_ID());
                                        ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
                
                <?php if ($atts['show_pagination'] === 'yes' && $query->max_num_pages > 1): ?>
                <!-- Enhanced Pagination -->
                <div class="clm-pagination">
                    <?php
                    echo paginate_links(array(
                        'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                        'format' => '?paged=%#%',
                        'current' => $paged,
                        'total' => $query->max_num_pages,
                        'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . __('Previous', 'choir-lyrics-manager'),
                        'next_text' => __('Next', 'choir-lyrics-manager') . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
                        'type' => 'list',
                    ));
                    ?>
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
	// Use a more robust approach to wait for the function
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
        
        if ($post->post_type === 'clm_lyric') {
            // Check if a custom template exists in the theme
            $theme_template = locate_template(array('single-clm_lyric.php'));
            
            if ($theme_template) {
                return $theme_template;
            }
            
            // Use plugin template
            return CLM_PLUGIN_DIR . 'templates/single-lyric.php';
        }
        
        return $template;
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

}

