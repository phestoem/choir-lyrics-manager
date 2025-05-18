<?php
/**
 * AJAX Handlers Trait for CLM Plugin
 * Provides unified AJAX handling for public and admin
 *
 * @package Choir_Lyrics_Manager
 */

trait CLM_Ajax_Handlers {
    /**
     * Register AJAX handlers
     *
     * @return void
     */
    public function register_ajax_handlers() {
        // Search and filter handlers
        add_action('wp_ajax_clm_ajax_search', [$this, 'handle_ajax_search']);
        add_action('wp_ajax_nopriv_clm_ajax_search', [$this, 'handle_ajax_search']);
        
        add_action('wp_ajax_clm_ajax_filter', [$this, 'handle_ajax_filter']);
        add_action('wp_ajax_nopriv_clm_ajax_filter', [$this, 'handle_ajax_filter']);
		
		add_action('wp_ajax_clm_alphabet_filter', [$this, 'handle_alphabet_filter']);
        add_action('wp_ajax_nopriv_clm_alphabet_filter', [$this, 'handle_alphabet_filter']);
        
        add_action('wp_ajax_clm_shortcode_filter', [$this, 'handle_shortcode_filter']);
        add_action('wp_ajax_nopriv_clm_shortcode_filter', [$this, 'handle_shortcode_filter']);
        
        // Only register these handlers in development environment
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // Test nonce handler - useful for debugging
            add_action('wp_ajax_clm_test_nonce', [$this, 'clm_test_nonce_handler']);
            add_action('wp_ajax_nopriv_clm_test_nonce', [$this, 'clm_test_nonce_handler']);
            
            // Testing handler
            add_action('wp_ajax_clm_ajax_filter_test', [$this, 'handle_ajax_filter_test']);
            add_action('wp_ajax_nopriv_clm_ajax_filter_test', [$this, 'handle_ajax_filter_test']);
        }
    }

    /**
     * Verify nonce with multiple possible actions
     * 
     * @param string $nonce The nonce to verify
     * @param array $possible_actions Array of possible nonce actions
     * @return bool Whether the nonce is valid
     */
    private function verify_multi_nonce($nonce, $possible_actions = []) {
        if (empty($nonce)) {
            return false;
        }
        
        // Default nonce actions if none provided
        if (empty($possible_actions)) {
            $possible_actions = [
                'clm_filter_nonce',
                'clm_search_nonce',
                'clm_practice_nonce',
                'clm_playlist_nonce',
                'clm_skills_nonce'
            ];
        }
        
        foreach ($possible_actions as $action) {
            if (wp_verify_nonce($nonce, $action)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Handle AJAX filter request
     * 
     * @return void
     */
    public function handle_ajax_filter() {
        // Verify nonce
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        if (!$this->verify_multi_nonce($nonce)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CLM AJAX Filter: Nonce verification failed. Received nonce: ' . $nonce);
            }
            
            wp_send_json_error([
                'message' => __('Security verification failed. Please refresh the page and try again.', 'choir-lyrics-manager'),
            ]);
            return;
        }
        
        // Get search query and filters
        $search_query = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $filters = isset($_POST['filters']) && is_array($_POST['filters']) ? $_POST['filters'] : [];
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        
        // Debug logging (only in debug mode)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CLM Debug - Received filters: ' . print_r($filters, true));
			if ($is_alphabet_filter) {
				error_log('CLM Debug - Direct alphabet filter request detected');
			}
        }
		
		
		// Special handling for alphabet filter
		if ($is_alphabet_filter && isset($filters['starts_with']) && !empty($filters['starts_with'])) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('CLM Debug - Processing alphabet filter for letter: ' . $filters['starts_with']);
			}
		}
        
        // Validate orderby and order parameters
        $valid_orderby = ['title', 'date', 'modified', 'menu_order', 'rand'];
        $valid_order = ['ASC', 'DESC'];
        
        $orderby = isset($filters['orderby']) && in_array($filters['orderby'], $valid_orderby) 
            ? sanitize_text_field($filters['orderby']) 
            : 'title';
            
        $order = isset($filters['order']) && in_array(strtoupper($filters['order']), $valid_order) 
            ? strtoupper(sanitize_text_field($filters['order'])) 
            : 'ASC';
        
        // Prepare query args
        $args = [
            'post_type' => 'clm_lyric',
            'posts_per_page' => isset($filters['per_page']) ? absint($filters['per_page']) : 20,
            'paged' => $page,
            'orderby' => $orderby,
            'order' => $order,
        ];
        
        // Add search query
        if (!empty($search_query)) {
            $args['s'] = $search_query;
        }
        
        // Add taxonomy filters
        $tax_query = [];
        
        // Process taxonomy filters with sanitization
        $taxonomies = ['genre', 'language', 'difficulty'];
        foreach ($taxonomies as $taxonomy) {
            if (!empty($filters[$taxonomy])) {
                $tax_query[] = [
                    'taxonomy' => 'clm_' . $taxonomy,
                    'field' => 'slug',
                    'terms' => sanitize_text_field($filters[$taxonomy]),
                ];
            }
        }
        
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
        
       // Add alphabet filter - approach using posts_where
		if (!empty($filters['starts_with']) && $filters['starts_with'] !== 'all') {
			// Add our custom query var to the WP_Query
			$args['starts_with'] = sanitize_text_field($filters['starts_with']);
			
			// Set up the posts_where filter
			add_filter('posts_where', [$this, 'filter_posts_by_title_first_letter']);
			
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('CLM Debug - Added alphabet filter for letter: ' . $filters['starts_with']);
			}
		}
        
        // Run query
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CLM Debug - Running query with args: ' . print_r($args, true));
        }
        
        $query = new WP_Query($args);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CLM Debug - Query SQL: ' . $query->request);
        }
        
        // Remove the filter after query is complete
        if (!empty($filters['starts_with']) && $filters['starts_with'] !== 'all') {
            remove_filter('posts_where', [$this, 'filter_posts_by_title_first_letter']);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CLM Debug - Removed alphabet filter');
            }
        }
        
        // Get settings
        $settings = new CLM_Settings('choir-lyrics-manager', CLM_VERSION);
        
        // Generate HTML
        ob_start();
        if ($query->have_posts()) {
            echo '<ul class="clm-items-list" id="clm-items-list">';
            while ($query->have_posts()) {
                $query->the_post();
                $this->render_lyric_item();
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
        $pagination = $this->generate_enhanced_pagination($page, $query->max_num_pages);
        
        wp_reset_postdata();
        
        wp_send_json_success([
            'html' => $html,
            'pagination' => $pagination,
            'total' => $query->found_posts,
            'page' => $page,
            'max_pages' => $query->max_num_pages,
            'new_nonce' => wp_create_nonce('clm_filter_nonce'),
        ]);
    }
    
    /**
     * Simple test handler to help debug filter issues
     * 
     * @return void
     */
    public function handle_ajax_filter_test() {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        wp_send_json_success([
            'message' => 'Test handler works!',
            'nonce_received' => $nonce,
        ]);
    }
    
    /**
     * Filter posts by the first letter of the title
     *
     * @param string $where The WHERE clause of the query
     * @return string The modified WHERE clause
     */
		public function filter_posts_by_title_first_letter($where) {
			global $wpdb, $wp_query;
			
			// Always log this call to help with debugging
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('CLM Filter: filter_posts_by_title_first_letter called');
				
				// Check where the filter is being applied from
				$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
				$caller = isset($backtrace[2]['function']) ? $backtrace[2]['function'] : 'unknown';
				error_log('CLM Filter: Called from ' . $caller);
			}
			
			// First check for direct filter from AJAX request (shortcode filter)
			if (defined('DOING_AJAX') && DOING_AJAX && isset($_POST['starts_with']) && !empty($_POST['starts_with'])) {
				$starts_with = strtoupper(sanitize_text_field($_POST['starts_with']));
				
				if ($starts_with !== 'ALL') {
					$where .= $wpdb->prepare(" AND UPPER(SUBSTRING({$wpdb->posts}.post_title, 1, 1)) = %s", $starts_with);
					
					if (defined('WP_DEBUG') && WP_DEBUG) {
						error_log('CLM Filter: Modified WHERE clause for letter (AJAX direct): ' . $starts_with);
						error_log('CLM Filter: WHERE clause is now: ' . $where);
					}
				}
				
				return $where;
			}
			
			// Check if our query var is set (WP_Query approach)
			if (isset($wp_query->query_vars['starts_with']) && !empty($wp_query->query_vars['starts_with'])) {
				$starts_with = strtoupper(sanitize_text_field($wp_query->query_vars['starts_with']));
				
				if ($starts_with !== 'ALL') {
					// Add the filter condition to the WHERE clause
					$where .= $wpdb->prepare(" AND UPPER(SUBSTRING({$wpdb->posts}.post_title, 1, 1)) = %s", $starts_with);
					
					if (defined('WP_DEBUG') && WP_DEBUG) {
						error_log('CLM Filter: Modified WHERE clause for letter (query var): ' . $starts_with);
						error_log('CLM Filter: WHERE clause is now: ' . $where);
					}
				}
			}
			
			// Also check the global wp_query for current_post_query which may have been set by get_posts()
			if (empty($wp_query->query_vars['starts_with']) && !empty($wp_query->query['starts_with'])) {
				$starts_with = strtoupper(sanitize_text_field($wp_query->query['starts_with']));
				
				if ($starts_with !== 'ALL') {
					$where .= $wpdb->prepare(" AND UPPER(SUBSTRING({$wpdb->posts}.post_title, 1, 1)) = %s", $starts_with);
					
					if (defined('WP_DEBUG') && WP_DEBUG) {
						error_log('CLM Filter: Modified WHERE clause from query array: ' . $starts_with);
						error_log('CLM Filter: WHERE clause is now: ' . $where);
					}
				}
			}
			
			return $where;
		}

/**
 * Handle alphabet filter specifically
 */
public function handle_alphabet_filter() {
    // Verify nonce
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    if (!$this->verify_multi_nonce($nonce)) {
        wp_send_json_error(['message' => 'Security verification failed']);
        return;
    }
    
    $letter = isset($_POST['letter']) ? sanitize_text_field($_POST['letter']) : '';
    if (empty($letter) || $letter === 'all') {
        // For "all", just run normal search without the filter
        $args = [
            'post_type' => 'clm_lyric',
            'posts_per_page' => isset($_POST['per_page']) ? absint($_POST['per_page']) : 20,
            'paged' => 1,
            'orderby' => 'title',
            'order' => 'ASC',
        ];
    } else {
        // For specific letter
        add_filter('posts_where', function($where) use ($letter) {
            global $wpdb;
            $where .= $wpdb->prepare(" AND UPPER(SUBSTRING({$wpdb->posts}.post_title, 1, 1)) = %s", strtoupper($letter));
            error_log('CLM Debug - Direct alphabet filter SQL: ' . $where);
            return $where;
        });
        
        $args = [
            'post_type' => 'clm_lyric',
            'posts_per_page' => isset($_POST['per_page']) ? absint($_POST['per_page']) : 20,
            'paged' => 1,
            'orderby' => 'title',
            'order' => 'ASC',
        ];
    }
    
    // Run query
    $query = new WP_Query($args);
    
    // Generate HTML
    ob_start();
    if ($query->have_posts()) {
        echo '<ul class="clm-items-list" id="clm-items-list">';
        while ($query->have_posts()) {
            $query->the_post();
            $this->render_lyric_item();
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
    $pagination = $this->generate_enhanced_pagination(1, $query->max_num_pages);
    
    wp_reset_postdata();
    
    wp_send_json_success([
        'html' => $html,
        'pagination' => $pagination,
        'total' => $query->found_posts,
        'page' => 1,
        'max_pages' => $query->max_num_pages,
        'new_nonce' => wp_create_nonce('clm_filter_nonce'),
    ]);
}



    /**
     * Handle AJAX search request
     * 
     * @return void
     */
    public function handle_ajax_search() {
        // Verify nonce
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        if (!$this->verify_multi_nonce($nonce)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CLM AJAX Search: Nonce verification failed. Received nonce: ' . $nonce);
            }
            
            wp_send_json_error([
                'message' => __('Security verification failed. Please refresh the page and try again.', 'choir-lyrics-manager'),
            ]);
            return;
        }
        
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        
        if (strlen($query) < 2) {
            wp_send_json_error(['message' => __('Query too short', 'choir-lyrics-manager')]);
            return;
        }
        
        // Search for lyrics
        $args = [
            'post_type' => 'clm_lyric',
            'post_status' => 'publish',
            's' => $query,
            'posts_per_page' => 5,
        ];
        
        $search_query = new WP_Query($args);
        $suggestions = [];
        
        if ($search_query->have_posts()) {
            while ($search_query->have_posts()) {
                $search_query->the_post();
                
                $suggestions[] = [
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'meta' => $this->get_lyric_meta_string(get_the_ID()),
                ];
            }
        }
        
        wp_reset_postdata();
        
        wp_send_json_success([
            'suggestions' => $suggestions,
            'new_nonce' => wp_create_nonce('clm_search_nonce'),
        ]);
    }

    /**
     * Handle shortcode filter request with enhanced ordering support
     * 
     * @return void
     */
		 public function handle_shortcode_filter() {
			// Get parameters from request with proper sanitization
			$container_id = isset($_POST['container_id']) ? sanitize_text_field($_POST['container_id']) : '';
			$page = isset($_POST['page']) ? absint($_POST['page']) : 1;
			$search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
			
			// Get per_page parameter
			$per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 20;
			
			// Get ordering parameters with validation
			$valid_orderby_values = ['title', 'date', 'modified', 'menu_order', 'ID', 'rand', 'comment_count'];
			$valid_order_values = ['ASC', 'DESC'];
			
			$orderby = isset($_POST['orderby']) && in_array($_POST['orderby'], $valid_orderby_values) 
				? sanitize_text_field($_POST['orderby']) 
				: 'title';
					
			$order = isset($_POST['order']) && in_array(strtoupper($_POST['order']), $valid_order_values) 
				? strtoupper(sanitize_text_field($_POST['order'])) 
				: 'ASC';
			
			// Check for starts_with parameter (alphabet filtering)
			$starts_with = isset($_POST['starts_with']) ? sanitize_text_field($_POST['starts_with']) : '';
			
			// Debug log
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('CLM Shortcode Filter Request: ' . print_r([
					'orderby' => $orderby,
					'order' => $order,
					'per_page' => $per_page,
					'page' => $page,
					'starts_with' => $starts_with
				], true));
			}
			
			// Build query arguments
			$args = [
				'post_type' => 'clm_lyric',
				'post_status' => 'publish',
				'paged' => $page,
				'posts_per_page' => $per_page,
				'orderby' => $orderby,
				'order' => $order,
			];
			
			// Add search query
			if (!empty($search)) {
				$args['s'] = $search;
			}
			
			// Add taxonomy filters
			$tax_query = [];
			
			// Process all taxonomy filters
			foreach ($_POST as $key => $value) {
				if (strpos($key, 'filter_') === 0 && !empty($value)) {
					$filter_key = str_replace('filter_', '', $key);
					$value = sanitize_text_field($value);
					
					switch ($filter_key) {
						case 'genre':
						case 'language':
						case 'difficulty':
							$tax_query[] = [
								'taxonomy' => 'clm_' . $filter_key,
								'field' => 'slug',
								'terms' => $value,
							];
							break;
					}
				}
			}
			
			// Also check for direct filter parameters (non-prefixed)
			foreach (['genre', 'language', 'difficulty'] as $taxonomy) {
				if (isset($_POST[$taxonomy]) && !empty($_POST[$taxonomy])) {
					$tax_query[] = [
						'taxonomy' => 'clm_' . $taxonomy,
						'field' => 'slug',
						'terms' => sanitize_text_field($_POST[$taxonomy]),
					];
				}
			}
			
			if (!empty($tax_query)) {
				$args['tax_query'] = $tax_query;
			}
			
			// Add alphabet filter if specified
			if (!empty($starts_with) && $starts_with !== 'all') {
				// Store starts_with in query vars
				$args['starts_with'] = $starts_with;
				
				// Add filter to modify WHERE clause
				add_filter('posts_where', [$this, 'filter_posts_by_title_first_letter']);
				
				if (defined('WP_DEBUG') && WP_DEBUG) {
					error_log('CLM Shortcode Filter: Added alphabet filter for letter: ' . $starts_with);
				}
			}
			
			// Log the final query arguments
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('CLM Shortcode Filter Final Query Args: ' . print_r($args, true));
			}
			
			// Execute query
			$query = new WP_Query($args);
			
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('CLM Shortcode Filter SQL: ' . $query->request);
			}
			
			// Remove filter if added
			if (!empty($starts_with) && $starts_with !== 'all') {
				remove_filter('posts_where', [$this, 'filter_posts_by_title_first_letter']);
				
				if (defined('WP_DEBUG') && WP_DEBUG) {
					error_log('CLM Shortcode Filter: Removed alphabet filter');
				}
			}
			
			// Generate HTML for results
			ob_start();
			include_once(CLM_PLUGIN_DIR . 'templates/partials/lyric-item-shortcode.php');
			$html = ob_get_clean();
			
			// Generate pagination HTML
			$pagination = $this->generate_enhanced_pagination($page, $query->max_num_pages);
			
			// Reset postdata
			wp_reset_postdata();
			
			// Create fresh nonce for security
			$new_nonce = wp_create_nonce('clm_filter_nonce');
			
			// Send the response
			wp_send_json_success([
				'html' => $html,
				'pagination' => $pagination,
				'total' => $query->found_posts,
				'page' => $page,
				'max_pages' => $query->max_num_pages,
				'per_page' => $per_page,
				'orderby' => $orderby,
				'order' => $order,
				'starts_with' => $starts_with, // Return the letter used for filtering
				'new_nonce' => $new_nonce,
				'sql' => defined('WP_DEBUG') && WP_DEBUG ? $query->request : '',
			]);
		}
    
    /**
     * Add this utility function to help with ordering debugging if needed
     * 
     * @return void
     */
    public function log_query_vars() {
        // Only run in admin or for logged-in users to avoid performance issues
        if ((is_admin() || is_user_logged_in()) && defined('WP_DEBUG') && WP_DEBUG) {
            global $wp_query;
            $query_vars = $wp_query->query_vars;
            
            // Log relevant query vars
            error_log('CLM Query Vars: ' . print_r([
                'orderby' => isset($query_vars['orderby']) ? $query_vars['orderby'] : 'default',
                'order' => isset($query_vars['order']) ? $query_vars['order'] : 'default',
                'post_type' => isset($query_vars['post_type']) ? $query_vars['post_type'] : 'default',
                'posts_per_page' => isset($query_vars['posts_per_page']) ? $query_vars['posts_per_page'] : 'default',
            ], true));
        }
    }
    
    /**
     * Render a single lyric item
     * 
     * @return void
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
                    $meta_items = [];
                    
                    // Get lyric metadata
                    $composer = get_post_meta(get_the_ID(), '_clm_composer', true);
                    if (!empty($composer)) {
                        $meta_items[] = '<span class="clm-meta-composer"><strong>' . esc_html__('Composer:', 'choir-lyrics-manager') . '</strong> ' . esc_html($composer) . '</span>';
                    }
                    
                    // Get language from taxonomy terms instead of custom field for better organization
                    $language_terms = get_the_terms(get_the_ID(), 'clm_language');
                    if (!is_wp_error($language_terms) && !empty($language_terms)) {
                        $language = esc_html($language_terms[0]->name);
                        $meta_items[] = '<span class="clm-meta-language"><strong>' . esc_html__('Language:', 'choir-lyrics-manager') . '</strong> ' . $language . '</span>';
                    }
                    
                    // Check if difficulty display is enabled
                    if ($settings->get_setting('show_difficulty', true)) {
                        $difficulty_terms = get_the_terms(get_the_ID(), 'clm_difficulty');
                        if (!is_wp_error($difficulty_terms) && !empty($difficulty_terms)) {
                            // Get difficulty rating (either from term meta or backup from post meta)
                            $difficulty_rating = get_term_meta($difficulty_terms[0]->term_id, '_clm_difficulty_rating', true);
                            
                            if (empty($difficulty_rating)) {
                                $difficulty_rating = get_post_meta(get_the_ID(), '_clm_difficulty', true);
                            }
                            
                            if (!empty($difficulty_rating) && is_numeric($difficulty_rating)) {
                                $stars = '';
                                for ($i = 1; $i <= 5; $i++) {
                                    $star_class = $i <= (int)$difficulty_rating ? 'dashicons-star-filled' : 'dashicons-star-empty';
                                    $stars .= '<span class="dashicons ' . $star_class . '"></span>';
                                }
                                $meta_items[] = '<span class="clm-meta-difficulty"><strong>' . esc_html__('Difficulty:', 'choir-lyrics-manager') . '</strong> ' . $stars . '</span>';
                            }
                        }
                    }
                    
                    echo !empty($meta_items) ? implode(' <span class="clm-meta-separator">•</span> ', $meta_items) : '';
                    ?>
                </div>
                
                <div class="clm-item-excerpt">
                    <?php the_excerpt(); ?>
                </div>
                
                <div class="clm-item-actions">
                    <a href="<?php the_permalink(); ?>" class="clm-button"><?php esc_html_e('View Lyric', 'choir-lyrics-manager'); ?></a>
                    
                    <?php if (is_user_logged_in()): ?>
                        <button class="clm-button clm-create-playlist-button" data-lyric-id="<?php echo esc_attr(get_the_ID()); ?>">
                            <?php esc_html_e('Create Playlist', 'choir-lyrics-manager'); ?>
                        </button>
                        
                        <div class="clm-create-playlist-form" style="display:none;" data-lyric-id="<?php echo esc_attr(get_the_ID()); ?>">
                            <h4><?php esc_html_e('Create New Playlist', 'choir-lyrics-manager'); ?></h4>
                            
                            <?php wp_nonce_field('clm_playlist_nonce', 'clm_playlist_nonce_' . get_the_ID()); ?>
                            
                            <div class="clm-form-field">
                                <label for="clm-playlist-name-<?php the_ID(); ?>"><?php esc_html_e('Playlist Name', 'choir-lyrics-manager'); ?></label>
                                <input type="text" 
                                       id="clm-playlist-name-<?php the_ID(); ?>"
                                       class="clm-playlist-name" 
                                       required
                                       placeholder="<?php esc_attr_e('Enter playlist name', 'choir-lyrics-manager'); ?>">
                            </div>
                            
                            <div class="clm-form-field">
                                <label for="clm-playlist-description-<?php the_ID(); ?>"><?php esc_html_e('Description (optional)', 'choir-lyrics-manager'); ?></label>
                                <textarea id="clm-playlist-description-<?php the_ID(); ?>"
                                          class="clm-playlist-description" 
                                          rows="3"></textarea>
                            </div>
                            
                            <div class="clm-form-field">
                                <label><?php esc_html_e('Visibility', 'choir-lyrics-manager'); ?></label>
                                <div class="clm-radio-group">
                                    <label>
                                        <input type="radio" name="clm-playlist-visibility-<?php the_ID(); ?>" value="private" checked> 
                                        <?php esc_html_e('Private', 'choir-lyrics-manager'); ?>
                                    </label>
                                    <label>
                                        <input type="radio" name="clm-playlist-visibility-<?php the_ID(); ?>" value="public"> 
                                        <?php esc_html_e('Public', 'choir-lyrics-manager'); ?>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="clm-form-actions">
                                <button type="button" class="clm-submit-playlist clm-button clm-button-primary" data-lyric-id="<?php echo esc_attr(get_the_ID()); ?>">
                                    <?php esc_html_e('Create', 'choir-lyrics-manager'); ?>
                                </button>
                                <button type="button" class="clm-cancel-playlist clm-button">
                                    <?php esc_html_e('Cancel', 'choir-lyrics-manager'); ?>
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
	 * Generate enhanced pagination HTML
	 * 
	 * @param int $current_page Current page number
	 * @param int $total_pages Total number of pages
	 * @param string $base Base URL for pagination
	 * @return string HTML pagination markup
	 */
	public function generate_enhanced_pagination($current_page, $total_pages, $base = '#') {
		if ($total_pages <= 1) {
			return '';
		}
		
		ob_start();
		?>
		<div class="clm-pagination-wrapper">
			<?php
			// Previous button
			if ($current_page > 1) : ?>
				<a class="clm-page-link clm-prev" href="<?php echo esc_url($base); ?>" data-page="<?php echo $current_page - 1; ?>">
					<span class="dashicons dashicons-arrow-left-alt2"></span> <?php _e('Previous', 'choir-lyrics-manager'); ?>
				</a>
			<?php else : ?>
				<span class="clm-page-link clm-prev disabled">
					<span class="dashicons dashicons-arrow-left-alt2"></span> <?php _e('Previous', 'choir-lyrics-manager'); ?>
				</span>
			<?php endif; ?>
			
			<?php
			// Calculate page numbers to show
			$start_page = max(1, $current_page - 2);
			$end_page = min($total_pages, $current_page + 2);
			
			// Show first page if not in range
			if ($start_page > 1) {
				echo '<a class="clm-page-link" href="' . esc_url($base) . '" data-page="1">1</a>';
				if ($start_page > 2) {
					echo '<span class="clm-page-link clm-dots">...</span>';
				}
			}
			
			// Page numbers
			for ($i = $start_page; $i <= $end_page; $i++) {
				if ($i == $current_page) {
					echo '<span class="clm-page-link clm-current">' . $i . '</span>';
				} else {
					echo '<a class="clm-page-link" href="' . esc_url($base) . '" data-page="' . $i . '">' . $i . '</a>';
				}
			}
			
			// Show last page if not in range
			if ($end_page < $total_pages) {
				if ($end_page < $total_pages - 1) {
					echo '<span class="clm-page-link clm-dots">...</span>';
				}
				echo '<a class="clm-page-link" href="' . esc_url($base) . '" data-page="' . $total_pages . '">' . $total_pages . '</a>';
			}
			
			// Next button
			if ($current_page < $total_pages) : ?>
				<a class="clm-page-link clm-next" href="<?php echo esc_url($base); ?>" data-page="<?php echo $current_page + 1; ?>">
					<?php _e('Next', 'choir-lyrics-manager'); ?> <span class="dashicons dashicons-arrow-right-alt2"></span>
				</a>
			<?php else : ?>
				<span class="clm-page-link clm-next disabled">
					<?php _e('Next', 'choir-lyrics-manager'); ?> <span class="dashicons dashicons-arrow-right-alt2"></span>
				</span>
			<?php endif; ?>
		</div>
		
		<div class="clm-page-jump">
			<label for="clm-page-jump-input-<?php echo esc_attr(uniqid()); ?>"><?php _e('Jump to page:', 'choir-lyrics-manager'); ?></label>
			<input type="number" 
				   id="clm-page-jump-input-<?php echo esc_attr(uniqid()); ?>"
				   class="clm-page-jump-input"
				   min="1" 
				   max="<?php echo esc_attr($total_pages); ?>" 
				   value="<?php echo esc_attr($current_page); ?>">
			<button type="button" class="clm-page-jump-button clm-go-button"><?php _e('Go', 'choir-lyrics-manager'); ?></button>
		</div>
		<?php
		
		return ob_get_clean();
	}

    /**
     * Get lyric meta string
     *
     * @param int $lyric_id ID of the lyric post
     * @return string Formatted meta string
     */
    private function get_lyric_meta_string($lyric_id) {
        $meta_parts = [];
        
        // Get composer info
        $composer = get_post_meta($lyric_id, '_clm_composer', true);
        if (!empty($composer)) {
            $meta_parts[] = esc_html($composer);
        }
        
        // Get language from taxonomy if available
        $language_terms = get_the_terms($lyric_id, 'clm_language');
        if (!is_wp_error($language_terms) && !empty($language_terms)) {
            $meta_parts[] = esc_html($language_terms[0]->name);
        } else {
            // Fallback to meta field
            $language = get_post_meta($lyric_id, '_clm_language', true);
            if (!empty($language)) {
                $meta_parts[] = esc_html($language);
            }
        }
        
        return implode(' • ', $meta_parts);
    }

    /**
     * Test nonce handler - useful for debugging
     * 
     * @return void
     */
    public function clm_test_nonce_handler() {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        
        $verify_result = wp_verify_nonce($nonce, 'clm_filter_nonce');
        $ajax_check = check_ajax_referer('clm_filter_nonce', 'nonce', false);
        
        $fresh_nonce = wp_create_nonce('clm_filter_nonce');
        
        wp_send_json_success([
            'received_nonce' => $nonce,
            'fresh_nonce' => $fresh_nonce,
            'verify_result' => $verify_result,
            'ajax_check' => $ajax_check,
            'user_logged_in' => is_user_logged_in(),
            'user_id' => get_current_user_id(),
            'nonces_match' => ($nonce === $fresh_nonce),
            'session_token' => wp_get_session_token(),
        ]);
    }
    
    /**
     * Handle export event to calendar format
     * 
     * @return void
     */
    public function handle_export_event() {
        if (!isset($_GET['event_id']) || !isset($_GET['format'])) {
            wp_die(__('Invalid request', 'choir-lyrics-manager'));
        }
        
        $event_id = absint($_GET['event_id']);
        $format = sanitize_text_field($_GET['format']);
        
        $event = get_post($event_id);
        if (!$event || $event->post_type !== 'clm_event') {
            wp_die(__('Invalid event', 'choir-lyrics-manager'));
        }
        
        // Get event details
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
            $ics .= "UID:" . md5(uniqid(mt_rand(), true)) . "@" . sanitize_text_field($_SERVER['HTTP_HOST']) . "\r\n";
            $ics .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
            
            if ($event_date && $event_time) {
                $start_datetime = gmdate('Ymd\THis\Z', strtotime($event_date . ' ' . $event_time));
                $ics .= "DTSTART:" . $start_datetime . "\r\n";
                
                if ($event_end_time) {
                    $end_datetime = gmdate('Ymd\THis\Z', strtotime($event_date . ' ' . $event_end_time));
                    $ics .= "DTEND:" . $end_datetime . "\r\n";
                }
            }
            
            $ics .= "SUMMARY:" . $this->escape_ics_text($event->post_title) . "\r\n";
            $ics .= "DESCRIPTION:" . $this->escape_ics_text(wp_strip_all_tags($event->post_content)) . "\r\n";
            
            if ($event_location) {
                $ics .= "LOCATION:" . $this->escape_ics_text($event_location) . "\r\n";
            }
            
            $ics .= "URL:" . esc_url(get_permalink($event_id)) . "\r\n";
            $ics .= "END:VEVENT\r\n";
            $ics .= "END:VCALENDAR\r\n";
            
            echo $ics;
            exit;
        }
    }
    
    /**
     * Escape text for ICS format
     * 
     * @param string $text Text to escape
     * @return string Escaped text
     */
    private function escape_ics_text($text) {
        $text = str_replace('\\', '\\\\', $text); // Escape backslashes first
        $text = str_replace(',', '\,', $text);
        $text = str_replace(';', '\;', $text);
        $text = str_replace("\n", '\n', $text);
        return $text;
    }
}