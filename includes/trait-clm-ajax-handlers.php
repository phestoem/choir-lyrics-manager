<?php
/**
 * AJAX Handlers Trait for CLM Plugin
 * Provides unified AJAX handling for public and admin
 */

trait CLM_Ajax_Handlers {

    /**
 * Handle AJAX filter request
 */
public function handle_ajax_filter() {
		error_log('handle_ajax_filter called');
		error_log('POST data: ' . print_r($_POST, true));
		
		// For logged-in users, check nonce
		if (is_user_logged_in()) {
			if (!check_ajax_referer('clm_filter_nonce', 'nonce', false)) {
				wp_send_json_error(array('message' => 'Security verification failed for logged-in user'));
				return;
			}
		}
		
		// Get request data
		$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
		$search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
		$filters = isset($_POST['filters']) ? $_POST['filters'] : array();
		
		// Get per_page from filters or default
		$per_page = isset($filters['per_page']) ? intval($filters['per_page']) : 20;
		if ($per_page < 1) {
			$per_page = 20;
		}
		
		error_log('Page: ' . $page . ', Per page: ' . $per_page);
        
        // Build query arguments
        $args = array(
            'post_type' => 'clm_lyric',
            'post_status' => 'publish',
            'paged' => $page,
            'posts_per_page' => isset($filters['per_page']) ? intval($filters['per_page']) : 10,
        );
        
        // Add search query
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        // Add orderby
        if (!empty($filters['orderby'])) {
            $args['orderby'] = $filters['orderby'];
            $args['order'] = isset($filters['order']) ? $filters['order'] : 'ASC';
        }
        
        // Add taxonomy queries
        $tax_query = array();
        
        if (!empty($filters['genre'])) {
            $tax_query[] = array(
                'taxonomy' => 'clm_genre',
                'field' => 'slug',
                'terms' => $filters['genre'],
            );
        }
        
        if (!empty($filters['language'])) {
            $tax_query[] = array(
                'taxonomy' => 'clm_language',
                'field' => 'slug',
                'terms' => $filters['language'],
            );
        }
        
        if (!empty($filters['difficulty'])) {
            $tax_query[] = array(
                'taxonomy' => 'clm_difficulty',
                'field' => 'slug',
                'terms' => $filters['difficulty'],
            );
        }
        
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
        
        // Add alphabet filter
        if (!empty($filters['starts_with']) && $filters['starts_with'] !== 'all') {
            add_filter('posts_where', function($where) use ($filters) {
                global $wpdb;
                $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_title LIKE %s", $filters['starts_with'] . '%');
                return $where;
            });
        }
        
        // Execute query
        $query = new WP_Query($args);
        
		error_log('Found posts: ' . $query->found_posts . ', Max pages: ' . $query->max_num_pages);
   
		
       // Generate HTML for items
		ob_start();
		if ($query->have_posts()) {
			while ($query->have_posts()) {
				$query->the_post();
				$this->render_lyric_item();
			}
		} else {
			echo '<p class="clm-no-results">' . __('No lyrics found matching your criteria.', 'choir-lyrics-manager') . '</p>';
		}
		$html = ob_get_clean();
        
       // Generate pagination with current page
		ob_start();
		$this->render_ajax_pagination($query, $page);
		$pagination = ob_get_clean();
		
		wp_reset_postdata();
        
			// Send response
		wp_send_json_success(array(
			'html' => $html,
			'pagination' => $pagination,
			'total' => $query->found_posts,
			'page' => $page,
			'max_pages' => $query->max_num_pages,
		));
	}

    /**
     * Handle AJAX search request
     */
    public function handle_ajax_search() {
        if (!check_ajax_referer('clm_search_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Security verification failed'));
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
        if (!check_ajax_referer('clm_filter_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Security verification failed'));
            return;
        }
        
        $container_id = isset($_POST['container_id']) ? sanitize_text_field($_POST['container_id']) : '';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        
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
                    }
                }
            }
        }
        
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
        
        // Execute query
        $query = new WP_Query($args);
        
        // Generate HTML
        ob_start();
        include(plugin_dir_path(__FILE__) . '../templates/partials/lyric-item-shortcode.php');
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html,
            'total' => $query->found_posts,
            'page' => $page,
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
     * Test AJAX handler
     */
    public function handle_ajax_filter_test() {
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        
        // Generate simple test response
        ob_start();
        ?>
        <li class="clm-item clm-lyric-item">
            <div class="clm-item-card">
                <h2 class="clm-item-title">Test Lyric <?php echo $page; ?></h2>
                <p>This is a test response for page <?php echo $page; ?></p>
            </div>
        </li>
        <?php
        $html = ob_get_clean();
        
        ob_start();
        ?>
        <ul class="page-numbers">
            <li><a href="#" data-page="1"<?php echo $page == 1 ? ' class="current"' : ''; ?>>1</a></li>
            <li><a href="#" data-page="2"<?php echo $page == 2 ? ' class="current"' : ''; ?>>2</a></li>
            <li><a href="#" data-page="3"<?php echo $page == 3 ? ' class="current"' : ''; ?>>3</a></li>
        </ul>
        <?php
        $pagination = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html,
            'pagination' => $pagination,
            'total' => 30,
            'page' => $page,
            'max_pages' => 3,
        ));
    }
    
    /**
     * Test nonce handler
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
}