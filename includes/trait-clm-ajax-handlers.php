<?php
/**
 * Enhanced AJAX Handlers for Choir Lyrics Manager
 *
 * @since      1.0.0
 * @package    Choir_Lyrics_Manager
 */

trait CLM_Ajax_Handlers {
    
    /**
     * Handle AJAX search with improved error handling
     *
     * @since    1.0.0
     */
    public function handle_ajax_search() {
        try {
            // Verify nonce
            if (!check_ajax_referer('clm_search_nonce', 'nonce', false)) {
                throw new Exception(__('Security verification failed', 'choir-lyrics-manager'));
            }
            
            // Validate input
            $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
            
            if (strlen($query) < 2) {
                throw new Exception(__('Search query must be at least 2 characters', 'choir-lyrics-manager'));
            }
            
            // Search in lyrics
            $args = array(
                'post_type' => 'clm_lyric',
                's' => $query,
                'posts_per_page' => 5,
                'post_status' => 'publish',
                'fields' => 'ids', // Get only IDs for better performance
            );
            
            $post_ids = get_posts($args);
            $suggestions = array();
            
            if (!empty($post_ids)) {
                foreach ($post_ids as $post_id) {
                    $post = get_post($post_id);
                    if (!$post) continue;
                    
                    $composer = get_post_meta($post_id, '_clm_composer', true);
                    $language = get_post_meta($post_id, '_clm_language', true);
                    
                    $meta = array_filter(array($composer, $language));
                    
                    $suggestions[] = array(
                        'id' => $post_id,
                        'title' => get_the_title($post_id),
                        'url' => get_permalink($post_id),
                        'meta' => implode(' • ', $meta),
                        'type' => 'lyric'
                    );
                }
            }
            
            wp_send_json_success(array(
                'suggestions' => $suggestions,
                'count' => count($suggestions)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Handle AJAX filter with improved error handling
     *
     * @since    1.0.0
     */

/**
 * Fixed handle_ajax_filter with reliable alphabet filtering
 * Replace your entire handle_ajax_filter method with this
 */

public function handle_ajax_filter() {
    try {
        // Verify nonce
        if (!check_ajax_referer('clm_filter_nonce', 'nonce', false)) {
            throw new Exception(__('Security verification failed', 'choir-lyrics-manager'));
        }
        
        // Get and validate parameters
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $filters = isset($_POST['filters']) ? $this->validate_filters($_POST['filters']) : array();
        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $per_page = isset($filters['per_page']) ? max(1, min(100, intval($filters['per_page']))) : 10;
        
        error_log('CLM AJAX Filter - Page: ' . $page . ', Per page: ' . $per_page);
        
        // Build query arguments
        $args = array(
            'post_type' => 'clm_lyric',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish',
        );
        
        // Add search
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        // Add ordering
        if (!empty($filters['orderby'])) {
            $valid_orders = array('title', 'date', 'modified', 'composer');
            if (in_array($filters['orderby'], $valid_orders)) {
                $args['orderby'] = $filters['orderby'];
                
                if ($filters['orderby'] === 'composer') {
                    $args['orderby'] = 'meta_value';
                    $args['meta_key'] = '_clm_composer';
                }
            }
        } else {
            $args['orderby'] = 'title';
        }
        
        if (!empty($filters['order'])) {
            $args['order'] = in_array(strtoupper($filters['order']), array('ASC', 'DESC')) ? strtoupper($filters['order']) : 'ASC';
        } else {
            $args['order'] = 'ASC';
        }
        
        // Add taxonomy queries
        $tax_query = array();
        
        $taxonomies = array(
            'genre' => 'clm_genre',
            'language' => 'clm_language',
            'difficulty' => 'clm_difficulty',
        );
        
        foreach ($taxonomies as $key => $taxonomy) {
            if (!empty($filters[$key])) {
                $tax_query[] = array(
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => sanitize_text_field($filters[$key]),
                );
            }
        }
        
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }
        
        // Handle alphabet filter using post__in approach
        if (!empty($filters['starts_with'])) {
            $letter = strtoupper(substr(sanitize_text_field($filters['starts_with']), 0, 1));
            
            if (ctype_alpha($letter)) {
                // Get all posts that start with this letter
                global $wpdb;
                $like = $wpdb->esc_like($letter) . '%';
                
                $matching_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} 
                    WHERE post_type = 'clm_lyric' 
                    AND post_status = 'publish' 
                    AND post_title LIKE %s
                    ORDER BY post_title ASC",
                    $like
                ));
                
                if (!empty($matching_ids)) {
                    $args['post__in'] = $matching_ids;
                } else {
                    // No posts found for this letter
                    $args['post__in'] = array(0);
                }
                
                error_log('CLM AJAX Filter - Found ' . count($matching_ids) . ' posts starting with ' . $letter);
            }
        }
        
        // Execute query
        $query = new WP_Query($args);
        
        error_log('CLM AJAX Filter - Query executed: found=' . $query->found_posts . ', pages=' . $query->max_num_pages . ', current_page=' . $page);
        
        // Generate HTML
        ob_start();
        if ($query->have_posts()) {
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
                            
                            $composer = get_post_meta(get_the_ID(), '_clm_composer', true);
                            if ($composer) {
                                $meta_items[] = '<span class="clm-meta-composer"><strong>' . __('Composer:', 'choir-lyrics-manager') . '</strong> ' . esc_html($composer) . '</span>';
                            }
                            
                            $language = get_post_meta(get_the_ID(), '_clm_language', true);
                            if ($language) {
                                $meta_items[] = '<span class="clm-meta-language"><strong>' . __('Language:', 'choir-lyrics-manager') . '</strong> ' . esc_html($language) . '</span>';
                            }
                            
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
                            
                            echo implode(' <span class="clm-meta-separator">•</span> ', $meta_items);
                            ?>
                        </div>
                        
                        <div class="clm-item-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                        
                        <div class="clm-item-actions">
                            <a href="<?php the_permalink(); ?>" class="clm-button"><?php _e('View Lyric', 'choir-lyrics-manager'); ?></a>
                        </div>
                    </div>
                </li>
                <?php
            }
        } else {
            echo '<li class="clm-no-results"><p class="clm-notice">' . __('No lyrics found matching your criteria.', 'choir-lyrics-manager') . '</p></li>';
        }
        $html = ob_get_clean();
        
        // Generate pagination
        ob_start();
        error_log('CLM AJAX Filter - Generating pagination for ' . $query->max_num_pages . ' pages');
        $this->render_pagination($query, $page);
        $pagination = ob_get_clean();
        error_log('CLM AJAX Filter - Pagination HTML length: ' . strlen($pagination));
        
        wp_reset_postdata();
        
        // Send response
        $response = array(
            'html' => $html,
            'pagination' => $pagination,
            'total' => $query->found_posts,
            'page' => $page,
            'max_pages' => $query->max_num_pages
        );
        
        error_log('CLM AJAX Filter - Response data: ' . json_encode(array(
            'total' => $response['total'],
            'page' => $response['page'],
            'max_pages' => $response['max_pages'],
            'pagination_length' => strlen($response['pagination'])
        )));
        
        wp_send_json_success($response);
        
    } catch (Exception $e) {
        error_log('CLM AJAX Filter - Error: ' . $e->getMessage());
        wp_send_json_error(array(
            'message' => $e->getMessage()
        ));
    }
}
    
    /**
     * Handle shortcode filter AJAX requests
     *
     * @since    1.0.0
     */
    public function handle_shortcode_filter() {
        try {
            // Verify nonce
            if (!check_ajax_referer('clm_filter_nonce', 'nonce', false)) {
                throw new Exception(__('Security verification failed', 'choir-lyrics-manager'));
            }
            
            // Get and validate parameters
            $container_id = isset($_POST['container_id']) ? sanitize_text_field($_POST['container_id']) : '';
            $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
            $per_page = isset($_POST['per_page']) ? max(1, min(100, intval($_POST['per_page']))) : 10;
            $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
            
            // Build query
            $args = array(
                'post_type' => 'clm_lyric',
                'posts_per_page' => $per_page,
                'paged' => $page,
                'post_status' => 'publish',
            );
            
            // Add search query
            if (!empty($search)) {
                $args['s'] = $search;
            }
            
            // Process taxonomy filters
            $tax_query = array();
            
            $taxonomies = array(
                'genre' => 'clm_genre',
                'language' => 'clm_language',
                'difficulty' => 'clm_difficulty',
            );
            
            foreach ($taxonomies as $key => $taxonomy) {
                $filter_key = 'filter_' . $key;
                if (!empty($_POST[$filter_key])) {
                    $tax_query[] = array(
                        'taxonomy' => $taxonomy,
                        'field' => 'slug',
                        'terms' => sanitize_text_field($_POST[$filter_key]),
                    );
                }
            }
            
            if (!empty($tax_query)) {
                $args['tax_query'] = $tax_query;
            }
            
            // Add ordering
            if (!empty($_POST['filter_orderby'])) {
                $args['orderby'] = sanitize_text_field($_POST['filter_orderby']);
            }
            
            if (!empty($_POST['filter_order'])) {
                $args['order'] = in_array(strtoupper($_POST['filter_order']), array('ASC', 'DESC')) ? strtoupper($_POST['filter_order']) : 'ASC';
            }
            
            // Execute query
            $query = new WP_Query($args);
            
            // Generate HTML
            ob_start();
            $this->render_shortcode_items($query);
            $html = ob_get_clean();
            
            wp_reset_postdata();
            
            // Send response
            wp_send_json_success(array(
                'html' => $html,
                'total' => $query->found_posts,
                'page' => $page,
                'max_pages' => $query->max_num_pages
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Validate filter parameters
     *
     * @since    1.0.0
     * @param    array    $filters    Raw filter data.
     * @return   array                Validated filters.
     */
    private function validate_filters($filters) {
        if (!is_array($filters)) {
            return array();
        }
        
        $validated = array();
        
        // Whitelist of allowed filter keys
        $allowed_keys = array(
            'genre', 'language', 'difficulty', 'orderby', 'order',
            'per_page', 'year_from', 'year_to', 'starts_with'
        );
        
        foreach ($allowed_keys as $key) {
            if (isset($filters[$key])) {
                $validated[$key] = $filters[$key];
            }
        }
        
        return $validated;
    }
    
    /**
     * Render lyric items for regular AJAX
     *
     * @since    1.0.0
     * @param    WP_Query    $query    Query object.
     */
    private function render_lyric_item() {
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
                
                echo implode(' <span class="clm-meta-separator">•</span> ', $meta_items);
                ?>
            </div>
            
            <div class="clm-item-excerpt">
                <?php the_excerpt(); ?>
            </div>
            
            <div class="clm-item-actions">
                <a href="<?php the_permalink(); ?>" class="clm-button"><?php _e('View Lyric', 'choir-lyrics-manager'); ?></a>
            </div>
        </div>
    </li>
    <?php
}
    
    
    /**
     * Render lyric items for shortcode AJAX
     *
     * @since    1.0.0
     * @param    WP_Query    $query    Query object.
     */
    private function render_shortcode_items($query) {
        if ($query->have_posts()) {
            echo '<ul class="clm-items-list">';
            while ($query->have_posts()) {
                $query->the_post();
                include CLM_PLUGIN_DIR . 'templates/partials/lyric-item-shortcode.php';
            }
            echo '</ul>';
        } else {
            echo '<div class="clm-no-results"><p class="clm-notice">' . __('No lyrics found matching your criteria.', 'choir-lyrics-manager') . '</p></div>';
        }
    }
    
    /**
     * Render pagination
     *
     * @since    1.0.0
     * @param    WP_Query    $query    Query object.
     * @param    int         $page     Current page.
     */
    private function render_pagination($query, $page) {
    error_log('render_pagination called - max_pages: ' . $query->max_num_pages . ', current: ' . $page);
    
    if ($query->max_num_pages <= 1) {
        echo '<div class="clm-pagination-empty"><!-- No pagination needed --></div>';
        return;
    }
    
    $pagination_args = array(
        'total' => $query->max_num_pages,
        'current' => $page,
        'mid_size' => 2,
        'end_size' => 1,
        'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . __('Previous', 'choir-lyrics-manager'),
        'next_text' => __('Next', 'choir-lyrics-manager') . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
        'type' => 'array',
        'base' => '?paged=%#%',
        'format' => '?paged=%#%',
    );
    
    error_log('Pagination args: ' . json_encode($pagination_args));
    
    $links = paginate_links($pagination_args);
    
    error_log('Generated links: ' . print_r($links, true));
    
    if (!is_array($links) || empty($links)) {
        echo '<div class="clm-pagination-empty"><!-- Pagination links generation failed --></div>';
        error_log('paginate_links returned no array or empty array');
        return;
    }
    
    echo '<ul class="clm-pagination-list">';
    
    foreach ($links as $index => $link) {
        error_log('Processing link ' . $index . ': ' . $link);
        
        $page_num = 0;
        
        // Extract page number from the link
        if (strpos($link, 'current') !== false) {
            $page_num = $page;
        } else {
            // Extract from href
            if (preg_match('/paged=(\d+)/', $link, $matches)) {
                $page_num = intval($matches[1]);
            } elseif (preg_match('/>(\d+)</', $link, $matches)) {
                $page_num = intval($matches[1]);
            } elseif (strpos($link, 'prev') !== false) {
                $page_num = max(1, $page - 1);
            } elseif (strpos($link, 'next') !== false) {
                $page_num = min($query->max_num_pages, $page + 1);
            }
        }
        
        // Add data-page attribute
        if ($page_num > 0) {
            $link = str_replace('<a ', '<a data-page="' . $page_num . '" ', $link);
            $link = str_replace('<span class="page-numbers current"', '<span class="page-numbers current" data-page="' . $page_num . '"', $link);
        }
        
        echo '<li class="clm-pagination-item">' . $link . '</li>';
    }
    
    echo '</ul>';
    
    // Page jump
    ?>
    <div class="clm-page-jump">
        <label for="clm-page-jump-input"><?php _e('Jump to page:', 'choir-lyrics-manager'); ?></label>
        <input type="number" 
               id="clm-page-jump-input" 
               min="1" 
               max="<?php echo esc_attr($query->max_num_pages); ?>" 
               value="<?php echo esc_attr($page); ?>"
               class="clm-page-input">
        <button id="clm-page-jump-button" class="clm-button-small">
            <?php _e('Go', 'choir-lyrics-manager'); ?>
        </button>
    </div>
    
    <div class="clm-pagination-info">
        <?php 
        printf(
            __('Page %1$d of %2$d', 'choir-lyrics-manager'),
            $page,
            $query->max_num_pages
        );
        ?>
    </div>
    <?php
}
	
	public function handle_ajax_filter_debug() {
    // Log incoming request
    error_log('CLM Debug - AJAX Filter Request: ' . print_r($_POST, true));
    
    try {
        // Verify nonce
        if (!check_ajax_referer('clm_filter_nonce', 'nonce', false)) {
            error_log('CLM Debug - Nonce verification failed');
            throw new Exception(__('Security verification failed', 'choir-lyrics-manager'));
        }
        
        // Get parameters
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        
        error_log('CLM Debug - Parsed parameters: ' . json_encode(array(
            'search' => $search,
            'filters' => $filters,
            'page' => $page
        )));
        
        // Build query
        $args = array(
            'post_type' => 'clm_lyric',
            'posts_per_page' => 10,
            'paged' => $page,
            'post_status' => 'publish',
        );
        
        // Execute query
        $query = new WP_Query($args);
        
        error_log('CLM Debug - Query results: ' . json_encode(array(
            'found_posts' => $query->found_posts,
            'max_num_pages' => $query->max_num_pages,
            'current_page' => $page,
            'have_posts' => $query->have_posts()
        )));
        
        // Generate HTML
        ob_start();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                echo '<li class="clm-item">Post ID: ' . get_the_ID() . ' - ' . get_the_title() . '</li>';
            }
        } else {
            echo '<li>No posts found</li>';
        }
        $html = ob_get_clean();
        
        // Generate pagination with debugging
        ob_start();
        $this->render_pagination_debug($query, $page);
        $pagination = ob_get_clean();
        
        wp_reset_postdata();
        
        $response = array(
            'html' => $html,
            'pagination' => $pagination,
            'total' => $query->found_posts,
            'page' => $page,
            'max_pages' => $query->max_num_pages
        );
        
        error_log('CLM Debug - Response: ' . json_encode($response));
        
        wp_send_json_success($response);
        
    } catch (Exception $e) {
        error_log('CLM Debug - Error: ' . $e->getMessage());
        wp_send_json_error(array('message' => $e->getMessage()));
    }
}

private function render_pagination_debug($query, $page) {
    error_log('CLM Debug - Rendering pagination for page ' . $page . ' of ' . $query->max_num_pages);
    
    if ($query->max_num_pages <= 1) {
        return;
    }
    
    $args = array(
        'total' => $query->max_num_pages,
        'current' => $page,
        'type' => 'array',
        'prev_text' => '&laquo; Previous',
        'next_text' => 'Next &raquo;',
    );
    
    $links = paginate_links($args);
    
    if (!$links) {
        error_log('CLM Debug - No pagination links generated');
        return;
    }
    
    echo '<div class="clm-pagination" id="clm-pagination">';
    echo '<ul class="clm-pagination-list">';
    
    foreach ($links as $link) {
        // Log each link for debugging
        error_log('CLM Debug - Pagination link: ' . htmlspecialchars($link));
        
        // Add data-page attributes
        if (preg_match('/href=["\']([^"\']+)["\']/', $link, $matches)) {
            $href = $matches[1];
            $link_page = $this->extract_page_from_url($href);
            
            if ($link_page) {
                $link = str_replace('<a ', '<a data-page="' . $link_page . '" ', $link);
            }
        }
        
        echo '<li class="clm-pagination-item">' . $link . '</li>';
    }
    
    echo '</ul>';
    echo '</div>';
}

private function extract_page_from_url($url) {
    // Extract page number from various URL formats
    if (preg_match('/paged=(\d+)/', $url, $matches)) {
        return $matches[1];
    }
    if (preg_match('/page\/(\d+)/', $url, $matches)) {
        return $matches[1];
    }
    return null;
}
}