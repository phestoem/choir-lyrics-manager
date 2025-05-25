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
        // Unified nonce verification approach
        $verified = false;
        
        // Try to verify nonce for both logged-in and non-logged-in users
        if (isset($_POST['nonce'])) {
            $nonce = sanitize_text_field($_POST['nonce']);
            $verified = wp_verify_nonce($nonce, 'clm_filter_nonce');
        }
        
        // Continue only if verified or if user is not logged in
        if (!$verified && is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Security verification failed for logged-in user'));
            return;
        }
    
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
        
		// Add meta queries for media type filters
			$meta_query = array();

			if (!empty($filters['has_audio'])) {
				$meta_query[] = array(
					'relation' => 'OR',
					array(
						'key' => '_clm_audio_file_id',
						'value' => '',
						'compare' => '!='
					),
					array(
						'key' => '_clm_practice_tracks',
						'compare' => 'EXISTS'
					)
				);
			}

			if (!empty($filters['has_video'])) {
				$meta_query[] = array(
					'key' => '_clm_video_embed',
					'value' => '',
					'compare' => '!='
				);
			}

			if (!empty($filters['has_sheet'])) {
				$meta_query[] = array(
					'key' => '_clm_sheet_music_id',
					'value' => '',
					'compare' => '!='
				);
			}

			if (!empty($filters['has_midi'])) {
				$meta_query[] = array(
					'key' => '_clm_midi_file_id',
					'value' => '',
					'compare' => '!='
				);
			}

			// If any media type filter is active without specific type
			if (isset($filters['has_media']) && empty($filters['has_audio']) && empty($filters['has_video']) && 
				empty($filters['has_sheet']) && empty($filters['has_midi'])) {
				$meta_query[] = array(
					'relation' => 'OR',
					array('key' => '_clm_audio_file_id', 'value' => '', 'compare' => '!='),
					array('key' => '_clm_video_embed', 'value' => '', 'compare' => '!='),
					array('key' => '_clm_sheet_music_id', 'value' => '', 'compare' => '!='),
					array('key' => '_clm_midi_file_id', 'value' => '', 'compare' => '!='),
					array('key' => '_clm_practice_tracks', 'compare' => 'EXISTS')
				);
			}

			if (!empty($meta_query)) {
				if (count($meta_query) > 1) {
					array_unshift($meta_query, array('relation' => 'AND'));
				}
				$args['meta_query'] = $meta_query;
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
     * Handle shortcode filter request with improved error handling
     */
    public function handle_shortcode_filter() {
        try {
            // Verify nonce
            if (!check_ajax_referer('clm_filter_nonce', 'nonce', false) && is_user_logged_in()) {
                wp_send_json_error(array('message' => 'Security verification failed'));
                return;
            }
            
            // Get essential parameters
            $container_id = isset($_POST['container_id']) ? sanitize_text_field($_POST['container_id']) : '';
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
            
            // Build query arguments
            $args = array(
                'post_type' => 'clm_lyric',
                'post_status' => 'publish',
                'paged' => $page,
                'posts_per_page' => $per_page,
            );
            
            // Add search query if present
            if (isset($_POST['search']) && !empty($_POST['search'])) {
                $args['s'] = sanitize_text_field($_POST['search']);
            }
            
            // Add ordering
            if (isset($_POST['orderby']) && !empty($_POST['orderby'])) {
                $args['orderby'] = sanitize_text_field($_POST['orderby']);
                
                if (isset($_POST['order']) && !empty($_POST['order'])) {
                    $args['order'] = sanitize_text_field($_POST['order']);
                }
            }
            
            // Process taxonomy filters
            $tax_query = array();
            
            if (isset($_POST['genre']) && !empty($_POST['genre'])) {
                $tax_query[] = array(
                    'taxonomy' => 'clm_genre',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($_POST['genre']),
                );
            }
            
            if (isset($_POST['language']) && !empty($_POST['language'])) {
                $tax_query[] = array(
                    'taxonomy' => 'clm_language',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($_POST['language']),
                );
            }
            
            if (isset($_POST['difficulty']) && !empty($_POST['difficulty'])) {
                $tax_query[] = array(
                    'taxonomy' => 'clm_difficulty',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($_POST['difficulty']),
                );
            }
            
            if (!empty($tax_query)) {
                $args['tax_query'] = $tax_query;
            }
            
            // Handle alphabet filter (starts_with)
            if (isset($_POST['starts_with']) && !empty($_POST['starts_with']) && $_POST['starts_with'] !== 'all') {
                add_filter('posts_where', function($where) {
                    global $wpdb;
                    $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_title LIKE %s", 
                        sanitize_text_field($_POST['starts_with']) . '%');
                    return $where;
                });
            }
            
            // Execute query
            $query = new WP_Query($args);
            
            // Generate HTML
            ob_start();
            if ($query->have_posts()) {
                echo '<ul class="clm-items-list">';
                while ($query->have_posts()) {
                    $query->the_post();
                    include(plugin_dir_path(dirname(__FILE__)) . 'templates/partials/lyric-item-shortcode.php');
                }
                echo '</ul>';
            } else {
                echo '<div class="clm-no-results">';
                echo '<p class="clm-notice">' . __("No lyrics found matching your criteria.", "choir-lyrics-manager") . '</p>';
                echo '</div>';
            }
            $html = ob_get_clean();
            
            wp_reset_postdata();
            
            // Generate pagination
            ob_start();
            
            if ($query->max_num_pages > 1) {
                echo paginate_links(array(
                    'base' => '#',
                    'format' => '?paged=%#%',
                    'current' => $page,
                    'total' => $query->max_num_pages,
                    'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . __('Previous', 'choir-lyrics-manager'),
                    'next_text' => __('Next', 'choir-lyrics-manager') . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
                    'type' => 'list',
                    'end_size' => 1,
                    'mid_size' => 2,
                ));
            }
            
            $pagination = ob_get_clean();
            
            // Send success response
            wp_send_json_success(array(
                'html' => $html,
                'pagination' => $pagination,
                'total' => $query->found_posts,
                'page' => $page,
                'max_pages' => $query->max_num_pages,
            ));
            
        } catch (Exception $e) {
            // Log the error
            error_log('Shortcode filter error: ' . $e->getMessage());
            
            // Send error response
            wp_send_json_error(array(
                'message' => 'An error occurred: ' . $e->getMessage(),
            ));
        }
    }

    /**
     * Render a single lyric item
     * 
     * @return void
     */
    private function render_lyric_item() {
        // Get lyric ID
        $lyric_id = get_the_ID();
        
        // Get attachment meta values
        $audio_file_id = get_post_meta($lyric_id, '_clm_audio_file_id', true);
        $video_embed = get_post_meta($lyric_id, '_clm_video_embed', true);
        $sheet_music_id = get_post_meta($lyric_id, '_clm_sheet_music_id', true);
        $midi_file_id = get_post_meta($lyric_id, '_clm_midi_file_id', true);
        
        // Check for existence with stringent checks
        $has_audio = !empty($audio_file_id) && $audio_file_id !== '0' && $audio_file_id !== '';
        $has_video = !empty($video_embed);
        $has_sheet = !empty($sheet_music_id) && $sheet_music_id !== '0' && $sheet_music_id !== '';
        $has_midi = !empty($midi_file_id) && $midi_file_id !== '0' && $midi_file_id !== '';
        
        // Check for practice tracks as well
        $practice_tracks = get_post_meta($lyric_id, '_clm_practice_tracks', true);
        $has_practice_tracks = !empty($practice_tracks) && is_array($practice_tracks) && count($practice_tracks) > 0;
        if ($has_practice_tracks) {
            $has_audio = true; // Consider practice tracks as audio
        }
        
        // Flag for any attachments
        $has_attachments = ($has_audio || $has_video || $has_sheet || $has_midi);
        
        // Start HTML output
        ?>
        <li id="lyric-<?php echo esc_attr($lyric_id); ?>" class="clm-item clm-lyric-item" data-title="<?php echo esc_attr(get_the_title()); ?>">
            <div class="clm-item-card">
                <h2 class="clm-item-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    
                    <?php if ($has_attachments): ?>
                    <div class="clm-attachment-icons">
                        <?php if ($has_audio): ?>
                        <span class="clm-attachment-icon audio-icon" title="<?php esc_attr_e('Audio Available', 'choir-lyrics-manager'); ?>">
                            <span>🎵</span>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($has_video): ?>
                        <span class="clm-attachment-icon video-icon" title="<?php esc_attr_e('Video Available', 'choir-lyrics-manager'); ?>">
                            <span>🎬</span>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($has_sheet): ?>
                        <span class="clm-attachment-icon sheet-icon" title="<?php esc_attr_e('Sheet Music Available', 'choir-lyrics-manager'); ?>">
                            <span>📄</span>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($has_midi): ?>
                        <span class="clm-attachment-icon midi-icon" title="<?php esc_attr_e('MIDI File Available', 'choir-lyrics-manager'); ?>">
                            <span>🎹</span>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
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
                    $settings = new CLM_Settings('choir-lyrics-manager', CLM_VERSION);
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
                        <button class="clm-create-playlist-button" data-lyric-id="<?php echo esc_attr(get_the_ID()); ?>">
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
     * Export event to calendar format
     */
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