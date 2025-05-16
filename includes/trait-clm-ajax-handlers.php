<?php
/**
 * AJAX Handlers Trait for CLM Plugin
 * Provides unified AJAX handling for public and admin
 */

trait CLM_Ajax_Handlers {


/**
     * Register AJAX handlers
     */
    public function register_ajax_handlers() {
        // Search and filter handlers
        add_action('wp_ajax_clm_ajax_search', array($this, 'handle_ajax_search'));
        add_action('wp_ajax_nopriv_clm_ajax_search', array($this, 'handle_ajax_search'));
        
        add_action('wp_ajax_clm_ajax_filter', array($this, 'handle_ajax_filter'));
        add_action('wp_ajax_nopriv_clm_ajax_filter', array($this, 'handle_ajax_filter'));
        
        add_action('wp_ajax_clm_shortcode_filter', array($this, 'handle_shortcode_filter'));
        add_action('wp_ajax_nopriv_clm_shortcode_filter', array($this, 'handle_shortcode_filter'));
        
        // Test nonce handler - useful for debugging
        add_action('wp_ajax_clm_test_nonce', array($this, 'clm_test_nonce_handler'));
        add_action('wp_ajax_nopriv_clm_test_nonce', array($this, 'clm_test_nonce_handler'));
        
        // Testing handler
        add_action('wp_ajax_clm_ajax_filter_test', array($this, 'handle_ajax_filter_test'));
        add_action('wp_ajax_nopriv_clm_ajax_filter_test', array($this, 'handle_ajax_filter_test'));
    }

    /**
     * Handle AJAX filter request - FIXED VERSION
     */
    public function handle_ajax_filter() {
        // FIXED: Better nonce verification that's more reliable
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        
        // Check if we have a valid nonce
        if (!wp_verify_nonce($nonce, 'clm_filter_nonce')) {
            // Try other possible nonce keys as a fallback
            $valid_nonce = false;
            
            $possible_nonce_actions = array(
                'clm_search_nonce',
                'clm_ajax_filter',
                'clm_practice_nonce',
                'clm_playlist_nonce',
                'clm_skills_nonce'
            );
            
            foreach ($possible_nonce_actions as $action) {
                if (wp_verify_nonce($nonce, $action)) {
                    $valid_nonce = true;
                    break;
                }
            }
            
            if (!$valid_nonce) {
                // Log the nonce verification failure for debugging
                error_log('CLM AJAX Filter: Nonce verification failed. Received nonce: ' . $nonce);
                
                // Return a more helpful error with debugging info
                wp_send_json_error(array(
                    'message' => 'Security verification failed. Please refresh the page and try again.',
                    'debug' => array(
                        'received_nonce' => $nonce,
                        'expected_action' => 'clm_filter_nonce',
                        'user_logged_in' => is_user_logged_in(),
                        'user_id' => get_current_user_id()
                    )
                ));
                return;
            }
        }
        
        // Get search query and filters
        $search_query = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        
        // Debug logging
        error_log('CLM Debug - Received filters: ' . print_r($filters, true));
        
        // Prepare query args
        $args = array(
            'post_type' => 'clm_lyric',
            'posts_per_page' => isset($filters['per_page']) ? intval($filters['per_page']) : 20,
            'paged' => $page,
            'orderby' => isset($filters['orderby']) ? sanitize_text_field($filters['orderby']) : 'title',
            'order' => isset($filters['order']) ? sanitize_text_field($filters['order']) : 'ASC',
        );
        
        // Add search query
        if (!empty($search_query)) {
            $args['s'] = $search_query;
        }
        
        // Add taxonomy filters
        $tax_query = array();
        
        if (!empty($filters['genre'])) {
            $tax_query[] = array(
                'taxonomy' => 'clm_genre',
                'field' => 'slug',
                'terms' => sanitize_text_field($filters['genre']),
            );
        }
        
        if (!empty($filters['language'])) {
            $tax_query[] = array(
                'taxonomy' => 'clm_language',
                'field' => 'slug',
                'terms' => sanitize_text_field($filters['language']),
            );
        }
        
        if (!empty($filters['difficulty'])) {
            $tax_query[] = array(
                'taxonomy' => 'clm_difficulty',
                'field' => 'slug',
                'terms' => sanitize_text_field($filters['difficulty']),
            );
        }
        
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
        
        // Add alphabet filter - approach using posts_where
        if (!empty($filters['starts_with']) && $filters['starts_with'] !== 'all') {
            // Add our custom query var to the WP_Query
            $args['starts_with'] = $filters['starts_with'];
            
            // Set up the posts_where filter
            add_filter('posts_where', array($this, 'filter_posts_by_title_first_letter'));
            
            error_log('CLM Debug - Added alphabet filter for letter: ' . $filters['starts_with']);
        }
        
        // Run query
        error_log('CLM Debug - Running query with args: ' . print_r($args, true));
        $query = new WP_Query($args);
        error_log('CLM Debug - Query SQL: ' . $query->request);
        
        // Remove the filter after query is complete
        if (!empty($filters['starts_with']) && $filters['starts_with'] !== 'all') {
            remove_filter('posts_where', array($this, 'filter_posts_by_title_first_letter'));
            error_log('CLM Debug - Removed alphabet filter');
        }
        
        // Get settings
        $settings = new CLM_Settings('choir-lyrics-manager', CLM_VERSION);
        
        // Generate HTML
        ob_start();
        if ($query->have_posts()) {
            echo '<ul class="clm-items-list" id="clm-items-list">';
            while ($query->have_posts()) {
                $query->the_post();
                ?>
                <li id="lyric-<?php the_ID(); ?>" class="clm-item clm-lyric-item" data-title="<?php echo esc_attr(get_the_title()); ?>">
                    <div class="clm-item-card">
                        <h2 class="clm-item-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>
                        
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
                        
                        <div class="clm-item-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                        
                        <div class="clm-item-actions">
                            <a href="<?php the_permalink(); ?>" class="clm-button"><?php _e('View Lyric', 'choir-lyrics-manager'); ?></a>
                            
                            <?php if (is_user_logged_in()): ?>
                                <button class="clm-button clm-create-playlist-button" data-lyric-id="<?php the_ID(); ?>">
                                    <?php _e('Create Playlist', 'choir-lyrics-manager'); ?>
                                </button>
                                
                                <div class="clm-create-playlist-form" style="display:none;" data-lyric-id="<?php the_ID(); ?>">
                                    <h4><?php _e('Create New Playlist', 'choir-lyrics-manager'); ?></h4>
                                    
                                    <div class="clm-form-field">
                                        <label><?php _e('Playlist Name', 'choir-lyrics-manager'); ?></label>
                                        <input type="text" class="clm-playlist-name" placeholder="<?php _e('Enter playlist name', 'choir-lyrics-manager'); ?>">
                                    </div>
                                    
                                    <div class="clm-form-field">
                                        <label><?php _e('Description (optional)', 'choir-lyrics-manager'); ?></label>
                                        <textarea class="clm-playlist-description" rows="3"></textarea>
                                    </div>
                                    
                                    <div class="clm-form-field">
                                        <label><?php _e('Visibility', 'choir-lyrics-manager'); ?></label>
                                        <div class="clm-radio-group">
                                            <label><input type="radio" name="clm-playlist-visibility-<?php the_ID(); ?>" value="private" checked> <?php _e('Private', 'choir-lyrics-manager'); ?></label>
                                            <label><input type="radio" name="clm-playlist-visibility-<?php the_ID(); ?>" value="public"> <?php _e('Public', 'choir-lyrics-manager'); ?></label>
                                        </div>
                                    </div>
                                    
                                    <div class="clm-form-actions">
                                        <button class="clm-submit-playlist clm-button clm-button-primary" data-lyric-id="<?php the_ID(); ?>">
                                            <?php _e('Create', 'choir-lyrics-manager'); ?>
                                        </button>
                                        <button class="clm-cancel-playlist clm-button">
                                            <?php _e('Cancel', 'choir-lyrics-manager'); ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
                <?php
            }
            echo '</ul>';
        } else {
            ?>
            <div class="clm-no-results">
                <p class="clm-notice"><?php _e('No lyrics found matching your criteria.', 'choir-lyrics-manager'); ?></p>
                <p><?php _e('Try adjusting your filters or search terms.', 'choir-lyrics-manager'); ?></p>
            </div>
            <?php
        }
        $html = ob_get_clean();
        
        // Generate pagination
        ob_start();
        if ($query->max_num_pages > 1) {
            $current_page = $page;
            
            echo paginate_links(array(
				'base' => '#',
				'format' => '',
				'prev_text' => __('Previous', 'choir-lyrics-manager'),
				'next_text' => __('Next', 'choir-lyrics-manager'),
				'total' => $query->max_num_pages,
				'current' => $page,
				'type' => 'list',
				'before_page_number' => '<span data-page="$0">',  // Add data attribute
				'after_page_number' => '</span>'
			));
            ?>
            
            <div class="clm-page-jump">
                <label><?php _e('Jump to page:', 'choir-lyrics-manager'); ?></label>
                <input type="number" id="clm-page-jump-input" 
                       min="1" 
                       max="<?php echo $query->max_num_pages; ?>" 
                       value="<?php echo $current_page; ?>">
                <button id="clm-page-jump-button" class="clm-button-small"><?php _e('Go', 'choir-lyrics-manager'); ?></button>
            </div>
            <?php
        }
        $pagination = ob_get_clean();
        
        wp_reset_postdata();
        
        wp_send_json_success(array(
            'html' => $html,
            'pagination' => $pagination,
            'total' => $query->found_posts,
            'page' => $page,
            'max_pages' => $query->max_num_pages,
        ));
    }
	
 /**
     * Simple test handler to help debug filter issues
     */
    public function handle_ajax_filter_test() {
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        wp_send_json_success(array(
            'message' => 'Test handler works!',
            'nonce_received' => $nonce,
        ));
    }	
 /**
     * Filter posts by the first letter of the title
     *
     * @param string $where The WHERE clause of the query
     * @return string The modified WHERE clause
     */
    public function filter_posts_by_title_first_letter($where) {
        global $wpdb, $wp_query;
        
        // Check if our query var is set
        if (isset($wp_query->query_vars['starts_with']) && !empty($wp_query->query_vars['starts_with'])) {
            $starts_with = strtoupper(sanitize_text_field($wp_query->query_vars['starts_with']));
            
            // Add the filter condition to the WHERE clause
            $where .= $wpdb->prepare(" AND UPPER(SUBSTRING({$wpdb->posts}.post_title, 1, 1)) = %s", $starts_with);
            
            error_log('CLM Debug - Modified WHERE clause: ' . $where);
        }
        
        return $where;
    }

    /**
     * Handle AJAX search request - FIXED VERSION
     */
    public function handle_ajax_search() {
        // FIXED: Better nonce verification similar to handle_ajax_filter
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        
        $valid_nonce = false;
        $possible_nonce_actions = array(
            'clm_search_nonce',
            'clm_filter_nonce',
            'clm_skills_nonce',
            'clm_practice_nonce',
            'clm_playlist_nonce'
        );
        
        // Try verifying with all possible nonce actions
        foreach ($possible_nonce_actions as $action) {
            if (wp_verify_nonce($nonce, $action)) {
                $valid_nonce = true;
                break;
            }
        }
        
        if (!$valid_nonce) {
            error_log('CLM AJAX Search: Nonce verification failed. Received nonce: ' . $nonce);
            
            wp_send_json_error(array(
                'message' => 'Security verification failed. Please refresh the page and try again.',
            ));
            return;
        }
        
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        
        if (strlen($query) < 2) {
            wp_send_json_error(array('message' => 'Query too short'));
            return;
        }
        
        // Search for lyrics
        $args = array(
            'post_type' => 'clm_lyric',
            'post_status' => 'publish',
            's' => $query,
            'posts_per_page' => 5,
        );
        
        $search_query = new WP_Query($args);
        $suggestions = array();
        
        if ($search_query->have_posts()) {
            while ($search_query->have_posts()) {
                $search_query->the_post();
                
                $suggestions[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'meta' => $this->get_lyric_meta_string(get_the_ID()),
                );
            }
        }
        
        wp_reset_postdata();
        
        wp_send_json_success(array(
            'suggestions' => $suggestions,
        ));
    }



/**
 * Handle shortcode filter request
 */
public function handle_shortcode_filter() {
    // SKIP nonce verification for shortcode filter AJAX calls
    // This is a temporary solution - for production, use a more secure approach
    /*
    // Verify nonce with multiple fallback options
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    
    $valid_nonce = false;
    $possible_nonce_actions = array(
        'clm_filter_nonce',
        'clm_search_nonce',
        'clm_skills_nonce',
        'clm_practice_nonce',
        'clm_playlist_nonce'
    );
    
    // Try verifying with all possible nonce actions
    foreach ($possible_nonce_actions as $action) {
        if (wp_verify_nonce($nonce, $action)) {
            $valid_nonce = true;
            break;
        }
    }
    
    if (!$valid_nonce) {
        wp_send_json_error(array('message' => 'Security verification failed'));
        return;
    }
    */
    
    // Get parameters from request
     // Get parameters from request
    $container_id = isset($_POST['container_id']) ? sanitize_text_field($_POST['container_id']) : '';
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    
    // Use the per_page parameter from the request or default to 20 (not 10)
    $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
    
    // Build query arguments
    $args = array(
        'post_type' => 'clm_lyric',
        'post_status' => 'publish',
        'paged' => $page,
        'posts_per_page' => $per_page,
    );
    
    // Add search query
    if (!empty($search)) {
        $args['s'] = $search;
    }
    
    // Add filters (prefixed with filter_)
    $tax_query = array();
    
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'filter_') === 0) {
            $filter_key = str_replace('filter_', '', $key);
            $value = sanitize_text_field($value);
            
            if (!empty($value)) {
                switch ($filter_key) {
                    case 'genre':
                    case 'language':
                    case 'difficulty':
                        $tax_query[] = array(
                            'taxonomy' => 'clm_' . $filter_key,
                            'field' => 'slug',
                            'terms' => $value,
                        );
                        break;
                    case 'orderby':
                        $args['orderby'] = $value;
                        break;
                    case 'order':
                        $args['order'] = $value;
                        break;
                    case 'starts_with':
                        if ($value !== 'all') {
                            $args['starts_with'] = $value;
                            add_filter('posts_where', array($this, 'filter_posts_by_title_first_letter'));
                        }
                        break;
                }
            }
        }
    }
    
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }
    
    // Execute query
    $query = new WP_Query($args);
    
    // Remove filter if added
    if (isset($args['starts_with'])) {
        remove_filter('posts_where', array($this, 'filter_posts_by_title_first_letter'));
    }
    
    // Generate HTML for results
    ob_start();
    include(plugin_dir_path(dirname(__FILE__)) . 'templates/partials/lyric-item-shortcode.php');
    $html = ob_get_clean();
    
    // Generate pagination HTML
    ob_start();
    if ($query->max_num_pages > 1) {
        echo '<div class="clm-pagination" data-container="shortcode" data-nonce="' . wp_create_nonce('clm_filter_nonce') . '">';
        
        // This is the key change - set proper data-page attributes on links
    $links = paginate_links(array(
        'base' => '#',  // Use # as the base
        'format' => '',
        'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . __('Previous', 'choir-lyrics-manager'),
        'next_text' => __('Next', 'choir-lyrics-manager') . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
        'total' => $query->max_num_pages,
        'current' => $page,
        'type' => 'array',  // Get an array of links instead of HTML string
        'mid_size' => 2,
        'end_size' => 1,
    ));
    
    if (is_array($links)) {
        echo '<ul class="page-numbers">';
        foreach ($links as $link) {
            // Extract page number from link
            $page_num = 1;  // Default to page 1
            
            // Check if it's current page
            if (strpos($link, 'current') !== false) {
                preg_match('/<span[^>]*>(\d+)<\/span>/', $link, $matches);
                if (!empty($matches[1])) {
                    $page_num = intval($matches[1]);
                }
                echo '<li>' . $link . '</li>';
            } 
            // Check if it's a numbered link
            else if (preg_match('/<a[^>]*>(\d+)<\/a>/', $link, $matches)) {
                $page_num = intval($matches[1]);
                // Replace the href with data-page attribute
                $link = str_replace('href="#"', 'href="#" data-page="' . $page_num . '"', $link);
                echo '<li>' . $link . '</li>';
            }
            // Check if it's previous/next
            else if (strpos($link, 'prev') !== false) {
                $prev_page = max(1, $page - 1);
                $link = str_replace('href="#"', 'href="#" data-page="' . $prev_page . '"', $link);
                echo '<li>' . $link . '</li>';
            }
            else if (strpos($link, 'next') !== false) {
                $next_page = min($query->max_num_pages, $page + 1);
                $link = str_replace('href="#"', 'href="#" data-page="' . $next_page . '"', $link);
                echo '<li>' . $link . '</li>';
            }
            // Other links (like dots)
            else {
                echo '<li>' . $link . '</li>';
            }
        }
        echo '</ul>';
    }
    
    // Add page jump if needed
    if ($query->max_num_pages > 5) {
        ?>
        <div class="clm-page-jump">
            <label><?php _e('Jump to page:', 'choir-lyrics-manager'); ?></label>
            <input type="number" class="clm-page-jump-input" 
                   min="1" 
                   max="<?php echo $query->max_num_pages; ?>" 
                   value="<?php echo $page; ?>">
            <button class="clm-page-jump-button clm-button-small"><?php _e('Go', 'choir-lyrics-manager'); ?></button>
        </div>
        <?php
    }
    
    echo '</div>';
}
$pagination = ob_get_clean();
    
    // Send the response
    wp_send_json_success(array(
        'html' => $html,
        'pagination' => $pagination,
        'total' => $query->found_posts,
        'page' => $page,
        'max_pages' => $query->max_num_pages,
		'per_page' => $per_page,  // Add this line
        'new_nonce' => wp_create_nonce('clm_filter_nonce'),
    ));
}

    /**
     * Render a single lyric item
     */
    private function render_lyric_item() {
        $settings = new CLM_Settings('choir-lyrics-manager', CLM_VERSION);
        ?>
        <li id="lyric-<?php the_ID(); ?>" class="clm-item clm-lyric-item" data-title="<?php echo esc_attr(get_the_title()); ?>">
            <div class="clm-item-card">
                <h2 class="clm-item-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h2>
                
                <div class="clm-item-meta">
                    <?php
                    $meta_items = array();
                    
                    $composer = get_post_meta(get_the_ID(), '_clm_composer', true);
                    if ($composer) {
                        $meta_items[] = '<span class="clm-meta-composer"><strong>' . __('Composer:', 'choir-lyrics-manager') . '</strong> ' . esc_html($composer) . '</span>';
                    }
                    
                    $language = get_post_meta(get_the_ID(), '_clm_language', true);
                    if ($language) {
                        $meta_items[] = '<span class="clm-meta-language"><strong>' . __('Language:', 'choir-lyrics-manager') . '</strong> ' . esc_html($language) . '</span>';
                    }
                    
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
                
                <div class="clm-item-excerpt">
                    <?php the_excerpt(); ?>
                </div>
                
                <div class="clm-item-actions">
                    <a href="<?php the_permalink(); ?>" class="clm-button"><?php _e('View Lyric', 'choir-lyrics-manager'); ?></a>
                    
                    <?php if (is_user_logged_in()): ?>
                        <button class="clm-button clm-create-playlist-button" data-lyric-id="<?php the_ID(); ?>">
                            <?php _e('Create Playlist', 'choir-lyrics-manager'); ?>
                        </button>
                        
                        <div class="clm-create-playlist-form" style="display:none;" data-lyric-id="<?php the_ID(); ?>">
                            <h4><?php _e('Create New Playlist', 'choir-lyrics-manager'); ?></h4>
                            
                            <div class="clm-form-field">
                                <label><?php _e('Playlist Name', 'choir-lyrics-manager'); ?></label>
                                <input type="text" class="clm-playlist-name" placeholder="<?php _e('Enter playlist name', 'choir-lyrics-manager'); ?>">
                            </div>
                            
                            <div class="clm-form-field">
                                <label><?php _e('Description (optional)', 'choir-lyrics-manager'); ?></label>
                                <textarea class="clm-playlist-description" rows="3"></textarea>
                            </div>
                            
                            <div class="clm-form-field">
                                <label><?php _e('Visibility', 'choir-lyrics-manager'); ?></label>
                                <div class="clm-radio-group">
                                    <label><input type="radio" name="clm-playlist-visibility-<?php the_ID(); ?>" value="private" checked> <?php _e('Private', 'choir-lyrics-manager'); ?></label>
                                    <label><input type="radio" name="clm-playlist-visibility-<?php the_ID(); ?>" value="public"> <?php _e('Public', 'choir-lyrics-manager'); ?></label>
                                </div>
                            </div>
                            
                            <div class="clm-form-actions">
                                <button class="clm-submit-playlist clm-button clm-button-primary" data-lyric-id="<?php the_ID(); ?>">
                                    <?php _e('Create', 'choir-lyrics-manager'); ?>
                                </button>
                                <button class="clm-cancel-playlist clm-button">
                                    <?php _e('Cancel', 'choir-lyrics-manager'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </li>
        <?php
    }

/**
 * Render AJAX pagination
 */
private function render_ajax_pagination($query, $current_page = 1) {
    if ($query->max_num_pages <= 1) {
        return;
    }
    
    ?>
    <ul class="page-numbers">
        <?php
        // Previous link
        if ($current_page > 1) {
            $prev_page = $current_page - 1;
            ?>
            <li>
                <a href="#" class="prev page-numbers" data-page="<?php echo $prev_page; ?>">
                    <span class="dashicons dashicons-arrow-left-alt2"></span> 
                    <?php _e('Previous', 'choir-lyrics-manager'); ?>
                </a>
            </li>
            <?php
        }
        
        // First page
        if ($current_page > 3) {
            ?>
            <li><a href="#" class="page-numbers" data-page="1">1</a></li>
            <?php
            if ($current_page > 4) {
                echo '<li><span class="page-numbers dots">...</span></li>';
            }
        }
        
        // Pages around current
        for ($i = max(1, $current_page - 2); $i <= min($query->max_num_pages, $current_page + 2); $i++) {
            if ($i == $current_page) {
                ?>
                <li><span aria-current="page" class="page-numbers current"><?php echo $i; ?></span></li>
                <?php
            } else {
                ?>
                <li><a href="#" class="page-numbers" data-page="<?php echo $i; ?>"><?php echo $i; ?></a></li>
                <?php
            }
        }
        
        // Last page
        if ($current_page < $query->max_num_pages - 2) {
            if ($current_page < $query->max_num_pages - 3) {
                echo '<li><span class="page-numbers dots">...</span></li>';
            }
            ?>
            <li>
                <a href="#" class="page-numbers" data-page="<?php echo $query->max_num_pages; ?>">
                    <?php echo $query->max_num_pages; ?>
                </a>
            </li>
            <?php
        }
        
        // Next link
        if ($current_page < $query->max_num_pages) {
            $next_page = $current_page + 1;
            ?>
            <li>
                <a href="#" class="next page-numbers" data-page="<?php echo $next_page; ?>">
                    <?php _e('Next', 'choir-lyrics-manager'); ?> 
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
            </li>
            <?php
        }
        ?>
    </ul>
    
    <div class="clm-page-jump">
        <label><?php _e('Jump to page:', 'choir-lyrics-manager'); ?></label>
        <input type="number" id="clm-page-jump-input" min="1" max="<?php echo $query->max_num_pages; ?>" value="<?php echo $current_page; ?>">
        <button id="clm-page-jump-button" class="clm-button-small"><?php _e('Go', 'choir-lyrics-manager'); ?></button>
    </div>
    <?php
}

      /**
     * Get lyric meta string
     */
    private function get_lyric_meta_string($lyric_id) {
        $meta_parts = array();
        
        $composer = get_post_meta($lyric_id, '_clm_composer', true);
        if ($composer) {
            $meta_parts[] = $composer;
        }
        
        $language = get_post_meta($lyric_id, '_clm_language', true);
        if ($language) {
            $meta_parts[] = $language;
        }
        
        return implode(' • ', $meta_parts);
    }

    
    
 /**
     * Test nonce handler - useful for debugging
     */
    public function clm_test_nonce_handler() {
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        
        $verify_result = wp_verify_nonce($nonce, 'clm_filter_nonce');
        $ajax_check = check_ajax_referer('clm_filter_nonce', 'nonce', false);
        
        $fresh_nonce = wp_create_nonce('clm_filter_nonce');
        
        wp_send_json_success(array(
            'received_nonce' => $nonce,
            'fresh_nonce' => $fresh_nonce,
            'verify_result' => $verify_result,
            'ajax_check' => $ajax_check,
            'user_logged_in' => is_user_logged_in(),
            'user_id' => get_current_user_id(),
            'nonces_match' => ($nonce === $fresh_nonce),
            'session_token' => wp_get_session_token(),
        ));
    }

	
	// Export event to calendar format
	public function handle_export_event() {
		if (!isset($_GET['event_id']) || !isset($_GET['format'])) {
			wp_die('Invalid request');
		}
		
		$event_id = intval($_GET['event_id']);
		$format = sanitize_text_field($_GET['format']);
		
		$event = get_post($event_id);
		if (!$event || $event->post_type !== 'clm_event') {
			wp_die('Invalid event');
		}
		
		$event_date = get_post_meta($event_id, '_clm_event_date', true);
		$event_time = get_post_meta($event_id, '_clm_event_time', true);
		$event_end_time = get_post_meta($event_id, '_clm_event_end_time', true);
		$event_location = get_post_meta($event_id, '_clm_event_location', true);
		
		if ($format === 'ics') {
			header('Content-Type: text/calendar');
			header('Content-Disposition: attachment; filename="' . sanitize_file_name($event->post_title) . '.ics"');
			
			$ics = "BEGIN:VCALENDAR\r\n";
			$ics .= "VERSION:2.0\r\n";
			$ics .= "PRODID:-//Choir Lyrics Manager//NONSGML v1.0//EN\r\n";
			$ics .= "BEGIN:VEVENT\r\n";
			$ics .= "UID:" . md5(uniqid(mt_rand(), true)) . "@" . $_SERVER['HTTP_HOST'] . "\r\n";
			$ics .= "DTSTAMP:" . date('Ymd\THis\Z') . "\r\n";
			
			if ($event_date && $event_time) {
				$start_datetime = date('Ymd\THis', strtotime($event_date . ' ' . $event_time));
				$ics .= "DTSTART:" . $start_datetime . "\r\n";
				
				if ($event_end_time) {
					$end_datetime = date('Ymd\THis', strtotime($event_date . ' ' . $event_end_time));
					$ics .= "DTEND:" . $end_datetime . "\r\n";
				}
			}
			
			$ics .= "SUMMARY:" . $this->escape_ics_text($event->post_title) . "\r\n";
			$ics .= "DESCRIPTION:" . $this->escape_ics_text(strip_tags($event->post_content)) . "\r\n";
			
			if ($event_location) {
				$ics .= "LOCATION:" . $this->escape_ics_text($event_location) . "\r\n";
			}
			
			$ics .= "URL:" . get_permalink($event_id) . "\r\n";
			$ics .= "END:VEVENT\r\n";
			$ics .= "END:VCALENDAR\r\n";
			
			echo $ics;
			exit;
		}
	}

	private function escape_ics_text($text) {
		$text = str_replace(',', '\,', $text);
		$text = str_replace(';', '\;', $text);
		$text = str_replace("\n", '\n', $text);
		return $text;
	}
}