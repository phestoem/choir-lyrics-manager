<?php
/**
 * Practice tracking functionality for the plugin.
 *
 * @since      1.0.0
 * @package    Choir_Lyrics_Manager
 */

class CLM_Practice {

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
    private $skills_manager_instance = null; // Initialize to null explicitly
    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Update practice log via AJAX
     *
     * @since    1.0.0
     */
    public function update_practice_log() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to track practice.', 'choir-lyrics-manager')));
    }
    
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'clm_practice_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'choir-lyrics-manager')));
    }
    
    // Check required fields
    if (empty($_POST['lyric_id']) || empty($_POST['duration']) || empty($_POST['confidence'])) {
        wp_send_json_error(array('message' => __('Required fields are missing.', 'choir-lyrics-manager')));
    }
    
    $user_id = get_current_user_id();
    $lyric_id = intval($_POST['lyric_id']);
    $duration = intval($_POST['duration']);
    $confidence = intval($_POST['confidence']);
    $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
    
    // Validate field values
    if ($confidence < 1 || $confidence > 5) {
        $confidence = 3; // Default to middle value if invalid
    }
    
    if ($duration < 1) {
        $duration = 1; // Minimum 1 minute
    }
    
    // Create practice log entry
    $log_data = array(
        'post_title'    => sprintf(__('Practice: %s', 'choir-lyrics-manager'), get_the_title($lyric_id)),
        'post_content'  => $notes,
        'post_status'   => 'publish',
        'post_author'   => $user_id,
        'post_type'     => 'clm_practice_log',
    );
    
    $log_id = wp_insert_post($log_data);
    
    if (is_wp_error($log_id)) {
        wp_send_json_error(array('message' => $log_id->get_error_message()));
    }
    
    // Set practice log meta
    update_post_meta($log_id, '_clm_lyric_id', $lyric_id);
    update_post_meta($log_id, '_clm_practice_date', date('Y-m-d'));
    update_post_meta($log_id, '_clm_duration', $duration);
    update_post_meta($log_id, '_clm_confidence', $confidence);
    
    // Update user total practice time
    $this->update_user_practice_stats($user_id, $lyric_id, $duration, $confidence);
    
    // Check if user has a member profile and update skills
    global $wpdb;
    $member = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} p 
        JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
        WHERE pm.meta_key = '_clm_wp_user_id' 
        AND pm.meta_value = %d 
        AND p.post_type = 'clm_member'
        LIMIT 1",
        $user_id
    ));
    
    $updated_skill = null;
    if ($member) {
        $updated_skill = $this->update_skill_from_practice($member->ID, $lyric_id, $duration, $confidence);
    }
    
    // Get updated practice stats
    $stats = $this->get_lyric_practice_stats($lyric_id, $user_id);
    
    wp_send_json_success(array(
        'message' => __('Practice log updated successfully.', 'choir-lyrics-manager'),
        'stats' => array(
            'total_time' => $stats['total_time'],
            'sessions' => $stats['sessions'],
            'last_date' => $stats['last_practice'] ? date_i18n(get_option('date_format'), strtotime($stats['last_practice'])) : '',
            'confidence' => $stats['confidence']
        ),
        'skill' => $updated_skill
    ));
}

    /**
     * AJAX handler for logging a new practice session.
     * This is the primary entry point for practice logging from the frontend.
     */
    public function ajax_log_lyric_practice() {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to track practice.', 'choir-lyrics-manager')));
            return; // Added explicit return
        }

        // Nonce check
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'clm_practice_nonce')) {
            error_log('CLM Practice Log: Nonce verification failed. Received: ' . (isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : 'NOT SET'));
            wp_send_json_error(array('message' => __('Security check failed (nonce).', 'choir-lyrics-manager')));
            return;
        }

        $required_fields = array('lyric_id', 'duration', 'confidence');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array('message' => sprintf(__('Required field "%s" is missing.', 'choir-lyrics-manager'), esc_html($field))));
                return;
            }
        }

        $wp_user_id = get_current_user_id();
        $lyric_id = intval($_POST['lyric_id']);
        $duration_minutes = intval($_POST['duration']);
        $confidence_rating = intval($_POST['confidence']);
        $session_notes = isset($_POST['notes']) ? sanitize_textarea_field(wp_unslash($_POST['notes'])) : '';

        // Validate input
        if ($duration_minutes < 1) $duration_minutes = 1;
        if ($confidence_rating < 1 || $confidence_rating > 5) $confidence_rating = 3;
        if (get_post_type($lyric_id) !== 'clm_lyric' || get_post_status($lyric_id) !== 'publish') {
             wp_send_json_error(array('message' => __('Invalid or non-published Lyric ID.', 'choir-lyrics-manager')));
             return;
        }

        $member_cpt_id = null;
        if (class_exists('CLM_Members')) {
            $members_manager = new CLM_Members($this->plugin_name, $this->version);
            $member_cpt_id = $members_manager->get_member_cpt_id_by_user_id($wp_user_id);
        }

        if (!$member_cpt_id) {
            wp_send_json_error(array('message' => __('Associated member profile not found. Cannot log practice.', 'choir-lyrics-manager')));
            return;
        }

        // 1. Create Practice Log CPT entry
        $log_title = sprintf(
            __('Practice: %s by %s on %s', 'choir-lyrics-manager'),
            get_the_title($lyric_id),
            get_the_title($member_cpt_id) ?: ('User #' . $wp_user_id), // Fallback title for member
            date_i18n(get_option('date_format')) // Current date formatted by WP settings
        );

        $log_data = array(
            'post_title'    => $log_title,
            'post_content'  => '', // Notes will be stored in meta for easier querying if needed
            'post_status'   => 'publish',
            'post_author'   => $wp_user_id,
            'post_type'     => 'clm_practice_log',
        );
        $log_id = wp_insert_post($log_data, true); // Pass true for WP_Error on failure

        if (is_wp_error($log_id)) {
            error_log('CLM Practice Log: Failed to insert post. ' . $log_id->get_error_message());
            wp_send_json_error(array('message' => __('Failed to create practice log entry: ', 'choir-lyrics-manager') . $log_id->get_error_message()));
            return;
        }

        // Save meta for the practice log entry
        update_post_meta($log_id, '_clm_member_id', $member_cpt_id);
        update_post_meta($log_id, '_clm_lyric_id', $lyric_id);
        update_post_meta($log_id, '_clm_practice_date', current_time('mysql', 1)); // Local time for display, GMT for consistency
        update_post_meta($log_id, '_clm_duration_minutes', $duration_minutes);
        update_post_meta($log_id, '_clm_confidence_rating', $confidence_rating);
        update_post_meta($log_id, '_clm_practice_notes', $session_notes);

// Add a check after saving
$saved_duration = get_post_meta($log_id, '_clm_duration_minutes', true);

        // 2. Update aggregated skill data in clm_member_skills table
        $skills_manager = $this->get_skills_manager();
        if (!$skills_manager) {
            wp_send_json_error(array('message' => __('Skill manager not available. Practice logged, but skill not updated.', 'choir-lyrics-manager')));
            return;
        }

        $updated_skill_data = $skills_manager->update_member_skill_from_practice_session(
            $member_cpt_id,
            $lyric_id,
            $duration_minutes,
            $confidence_rating
        );

        if (is_wp_error($updated_skill_data)) {
             error_log('CLM Practice Log: Skill update failed. ' . $updated_skill_data->get_error_message());
             wp_send_json_error(array('message' => __('Practice logged, but skill update failed: ', 'choir-lyrics-manager') . $updated_skill_data->get_error_message()));
             return;
        }
        
        $skill_level_info = $skills_manager->get_skill_level_info($updated_skill_data->skill_level);

        wp_send_json_success(array(
            'message' => __('Practice logged successfully.', 'choir-lyrics-manager'),
            'skill' => array(
                'skill_level' => $updated_skill_data->skill_level,
                'skill_level_label' => $skill_level_info ? $skill_level_info['label'] : $updated_skill_data->skill_level,
                'skill_level_icon' =>  $skill_level_info ? $skill_level_info['icon'] : '',
                'skill_level_color' => $skill_level_info ? $skill_level_info['color'] : '#ccc',
                'practice_count' => $updated_skill_data->practice_count,
				 'total_practice_minutes' => $updated_skill_data->total_practice_minutes, // <<< ADD THIS
                'goal_date' => $updated_skill_data->goal_date ? date_i18n(get_option('date_format'), strtotime($updated_skill_data->goal_date)) : null,
				'raw_goal_date' => $updated_skill_data->goal_date ? $updated_skill_data->goal_date : null, // <<< ADD THIS for JS data attribute
            ),
            'stats' => array(
                'total_time_minutes' => $updated_skill_data->total_practice_minutes,
                'sessions' => $updated_skill_data->practice_count,
                'last_practice_date_display' => $updated_skill_data->last_practice_date ? date_i18n(get_option('date_format'), strtotime($updated_skill_data->last_practice_date)) : __('Never', 'choir-lyrics-manager'),
                'confidence' => $updated_skill_data->confidence_rating
            )
        ));
    }

 /**
     * Get the CLM_Skills manager instance.
     * Creates one if it doesn't exist.
     * @return CLM_Skills|null
     */
    private function get_skills_manager() {
        if (null === $this->skills_manager_instance) {
            if (class_exists('CLM_Skills')) {
                // Pass plugin_name and version when instantiating
                $this->skills_manager_instance = new CLM_Skills($this->plugin_name, $this->version);
            } else {
                // Log error, but try not to cause fatal error if CLM_Skills isn't loaded (though it should be)
                error_log('CLM_Error: CLM_Skills class not found when trying to get instance in CLM_Practice.');
                return null;
            }
        }
        return $this->skills_manager_instance;
    }


// Fixed version of the skill update method
private function update_skill_from_practice($member_id, $lyric_id, $duration, $confidence) {
    $skills = new CLM_Skills($this->plugin_name, $this->version);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'clm_member_skills';
    
    // Get current skill
    $current_skill = $skills->get_member_skill($member_id, $lyric_id);
    
    if ($current_skill) {
        // Update existing skill
        $update_data = array(
            'practice_count' => $current_skill->practice_count + 1,
            'total_practice_minutes' => $current_skill->total_practice_minutes + intval($duration),
            'last_practice_date' => current_time('mysql')
        );
        
        // Auto-progression logic
        if ($current_skill->skill_level == 'novice' && $current_skill->practice_count >= 1) {
            $update_data['skill_level'] = 'learning';
        } elseif ($current_skill->skill_level == 'learning' && $confidence >= 4) {
            $update_data['skill_level'] = 'proficient';
        } elseif ($current_skill->skill_level == 'proficient' && $confidence >= 5 && $current_skill->practice_count >= 4) {
            $update_data['skill_level'] = 'master';
        }
        
        $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $current_skill->id)
        );
        
        // Get updated skill
        return $skills->get_member_skill($member_id, $lyric_id);
    } else {
        // Create new skill entry
        $wpdb->insert($table_name, array(
            'member_id' => $member_id,
            'lyric_id' => $lyric_id,
            'skill_level' => 'novice',
            'practice_count' => 1,
            'total_practice_minutes' => intval($duration),
            'last_practice_date' => current_time('mysql'),
            'created_at' => current_time('mysql')
        ));
        
        return $skills->get_member_skill($member_id, $lyric_id);
    }
}


    /**
     * Update user practice statistics
     *
     * @since    1.0.0
     * @param    int       $user_id      The user ID.
     * @param    int       $lyric_id     The lyric ID.
     * @param    int       $duration     Practice duration in minutes.
     * @param    int       $confidence   Confidence level (1-5).
     */
    private function update_user_practice_stats($user_id, $lyric_id, $duration, $confidence) {
        // Update total practice time for this lyric
        $practice_time = get_user_meta($user_id, 'clm_practice_time_' . $lyric_id, true);
        $practice_time = intval($practice_time) + $duration;
        update_user_meta($user_id, 'clm_practice_time_' . $lyric_id, $practice_time);
        
        // Update total practice sessions for this lyric
        $practice_sessions = get_user_meta($user_id, 'clm_practice_sessions_' . $lyric_id, true);
        $practice_sessions = intval($practice_sessions) + 1;
        update_user_meta($user_id, 'clm_practice_sessions_' . $lyric_id, $practice_sessions);
        
        // Update last practice date for this lyric
        update_user_meta($user_id, 'clm_last_practice_' . $lyric_id, date('Y-m-d H:i:s'));
        
        // Update confidence level for this lyric (most recent one)
        update_user_meta($user_id, 'clm_confidence_' . $lyric_id, $confidence);
        
        // Update overall practice stats
        $total_practice_time = get_user_meta($user_id, 'clm_total_practice_time', true);
        $total_practice_time = intval($total_practice_time) + $duration;
        update_user_meta($user_id, 'clm_total_practice_time', $total_practice_time);
        
        $total_practice_sessions = get_user_meta($user_id, 'clm_total_practice_sessions', true);
        $total_practice_sessions = intval($total_practice_sessions) + 1;
        update_user_meta($user_id, 'clm_total_practice_sessions', $total_practice_sessions);
        
        // Add lyric to practiced lyrics list if not already there
        $practiced_lyrics = get_user_meta($user_id, 'clm_practiced_lyrics', true);
        
        if (!is_array($practiced_lyrics)) {
            $practiced_lyrics = array();
        }
        
        if (!in_array($lyric_id, $practiced_lyrics)) {
            $practiced_lyrics[] = $lyric_id;
            update_user_meta($user_id, 'clm_practiced_lyrics', $practiced_lyrics);
        }
    }

    /**
     * Get practice statistics for a lyric
     *
     * @since     1.0.0
     * @param     int       $lyric_id    The lyric ID.
     * @param     int       $user_id     The user ID, defaults to current user.
     * @return    array                  Array of practice statistics.
     */
    public function get_lyric_practice_stats($lyric_id, $user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return array(
                'total_time' => 0,
                'sessions' => 0,
                'last_practice' => '',
                'confidence' => 0
            );
        }
        
        $total_time = get_user_meta($user_id, 'clm_practice_time_' . $lyric_id, true);
        $sessions = get_user_meta($user_id, 'clm_practice_sessions_' . $lyric_id, true);
        $last_practice = get_user_meta($user_id, 'clm_last_practice_' . $lyric_id, true);
        $confidence = get_user_meta($user_id, 'clm_confidence_' . $lyric_id, true);
        
        return array(
            'total_time' => intval($total_time),
            'sessions' => intval($sessions),
            'last_practice' => $last_practice,
            'confidence' => intval($confidence)
        );
    }

    /**
     * Get user's overall practice statistics
     *
     * @since     1.0.0
     * @param     int       $user_id    The user ID, defaults to current user.
     * @return    array                 Array of practice statistics.
     */
    public function get_user_practice_stats($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return array(
                'total_time' => 0,
                'total_sessions' => 0,
                'lyrics_practiced' => 0,
                'avg_time_per_lyric' => 0
            );
        }
        
        $total_time = get_user_meta($user_id, 'clm_total_practice_time', true);
        $total_sessions = get_user_meta($user_id, 'clm_total_practice_sessions', true);
        $practiced_lyrics = get_user_meta($user_id, 'clm_practiced_lyrics', true);
        
        if (!is_array($practiced_lyrics)) {
            $practiced_lyrics = array();
        }
        
        $lyrics_practiced = count($practiced_lyrics);
        $avg_time_per_lyric = $lyrics_practiced > 0 ? intval($total_time / $lyrics_practiced) : 0;
        
        return array(
            'total_time' => intval($total_time),
            'total_sessions' => intval($total_sessions),
            'lyrics_practiced' => $lyrics_practiced,
            'avg_time_per_lyric' => $avg_time_per_lyric,
            'practiced_lyrics' => $practiced_lyrics
        );
    }

    /**
     * Get practice history for a lyric by a specific member.
     * Queries the clm_practice_log CPT.
     *
     * @param int $member_cpt_id The Post ID of the clm_member CPT.
     * @param int $lyric_id The Post ID of the clm_lyric CPT.
     * @param int $limit Number of log entries to retrieve.
     * @param int $offset Offset for pagination.
     * @return array Array of practice history items.
     */
    public function get_practice_history($member_cpt_id, $lyric_id, $limit = 10, $offset = 0) {
        if ( empty($member_cpt_id) || empty($lyric_id) ) { // Check both params
            return array();
        }

        $args = array(
            'post_type'      => 'clm_practice_log',
            'posts_per_page' => intval($limit),
            'offset'         => intval($offset),
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => '_clm_member_id', // Query by Member CPT ID
                    'value'   => $member_cpt_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
                array(
                    'key'     => '_clm_lyric_id',
                    'value'   => $lyric_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
            ),
            'orderby'        => 'meta_value', // Order by the actual practice date stored in meta
            'meta_key'       => '_clm_practice_date',
            'meta_type'      => 'DATETIME', // Important for correct date sorting
            'order'          => 'DESC',
        );

        $logs_query = new WP_Query($args);
        $history_items = array();

        if ($logs_query->have_posts()) {
            while ($logs_query->have_posts()) {
                $logs_query->the_post();
                $log_post_id = get_the_ID();
                
                // Use the correct meta keys for retrieval
                $practice_date = get_post_meta($log_post_id, '_clm_practice_date', true);
                $duration = get_post_meta($log_post_id, '_clm_duration_minutes', true); // CORRECTED
                $confidence = get_post_meta($log_post_id, '_clm_confidence_rating', true); // CORRECTED
                $notes = get_post_meta($log_post_id, '_clm_practice_notes', true);       // CORRECTED (reading from meta)
                                                                                          // OR use get_the_content() if you decide to store notes in post_content
                
                $history_items[] = array(
                    'id'                => $log_post_id,
                    'date'              => $practice_date,
                    'duration_minutes'  => intval($duration),
                    'confidence_rating' => intval($confidence),
                    'notes'             => $notes
                );
            }
            wp_reset_postdata();
        }
        return $history_items;
    }


// Add to class-clm-practice.php

public function update_member_skill_from_practice($member_id, $lyric_id, $practice_data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'clm_member_skills';
    
    // Get current skill
    $current_skill = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE member_id = %d AND lyric_id = %d",
        $member_id,
        $lyric_id
    ));
    
    if ($current_skill) {
        // Update existing skill
        $update_data = array(
            'practice_count' => $current_skill->practice_count + 1,
            'total_practice_minutes' => $current_skill->total_practice_minutes + intval($practice_data['duration']),
            'last_practice_date' => current_time('mysql')
        );
        
        // Auto-progression logic
        if ($practice_data['confidence'] >= 4 && $current_skill->skill_level == 'learning') {
            $update_data['skill_level'] = 'proficient';
        } elseif ($practice_data['confidence'] >= 5 && $current_skill->practice_count >= 5 && $current_skill->skill_level == 'proficient') {
            $update_data['skill_level'] = 'master';
        } elseif ($current_skill->skill_level == 'novice' && $current_skill->practice_count >= 1) {
            $update_data['skill_level'] = 'learning';
        }
        
        $wpdb->update($table_name, $update_data, array('id' => $current_skill->id));
    } else {
        // Create new skill entry
        $wpdb->insert($table_name, array(
            'member_id' => $member_id,
            'lyric_id' => $lyric_id,
            'skill_level' => 'learning',
            'practice_count' => 1,
            'total_practice_minutes' => intval($practice_data['duration']),
            'last_practice_date' => current_time('mysql')
        ));
    }
    
    // Check for achievements
    $this->check_achievements($member_id, $lyric_id);
}

private function check_achievements($member_id, $lyric_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'clm_member_skills';
    
    $skill = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE member_id = %d AND lyric_id = %d",
        $member_id,
        $lyric_id
    ));
    
    if (!$skill) return;
    
    $badges = maybe_unserialize($skill->achievement_badges) ?: array();
    $new_badges = array();
    
    // First practice badge
    if ($skill->practice_count == 1 && !isset($badges['first_practice'])) {
        $new_badges['first_practice'] = array(
            'name' => __('First Steps', 'choir-lyrics-manager'),
            'description' => __('Completed your first practice session', 'choir-lyrics-manager'),
            'icon' => 'dashicons-flag',
            'earned_date' => current_time('mysql')
        );
    }
    
    // Consistency badges
    if ($skill->practice_count >= 5 && !isset($badges['consistent_5'])) {
        $new_badges['consistent_5'] = array(
            'name' => __('Consistent Learner', 'choir-lyrics-manager'),
            'description' => __('Practiced 5 times', 'choir-lyrics-manager'),
            'icon' => 'dashicons-awards',
            'earned_date' => current_time('mysql')
        );
    }
    
    // Skill level badges
    if ($skill->skill_level == 'master' && !isset($badges['master_level'])) {
        $new_badges['master_level'] = array(
            'name' => __('Master', 'choir-lyrics-manager'),
            'description' => __('Achieved master level', 'choir-lyrics-manager'),
            'icon' => 'dashicons-star-filled',
            'earned_date' => current_time('mysql')
        );
    }
    
    if (!empty($new_badges)) {
        $badges = array_merge($badges, $new_badges);
        $wpdb->update(
            $table_name,
            array('achievement_badges' => serialize($badges)),
            array('id' => $skill->id)
        );
    }
}

      /**
     * Render practice tracking widget
     *
     * @since     1.0.0
     * @param     int       $lyric_id    The lyric ID.
     * @return    string                 HTML for the practice tracking widget.
     */
    public function render_practice_widget($lyric_id) {
        if (!is_user_logged_in()) {
            return '<div class="clm-notice">' . __('Please log in to track your practice.', 'choir-lyrics-manager') . '</div>';
        }

        $wp_user_id = get_current_user_id();
        $member_cpt_id = null;

        if (class_exists('CLM_Members')) {
            $members_manager = new CLM_Members($this->plugin_name, $this->version);
            $member_cpt_id = $members_manager->get_member_cpt_id_by_user_id($wp_user_id);
        }

        if (!$member_cpt_id) {
             return '<div class="clm-notice">' . __('Associated member profile not found. Cannot display practice tracker.', 'choir-lyrics-manager') . '</div>';
        }

        // Get current aggregated skill/practice stats from CLM_Skills
        $current_skill_data = null;
        $skills_manager = $this->get_skills_manager(); // Ensure this returns a CLM_Skills instance
        if ($skills_manager) {
            $current_skill_data = $skills_manager->get_member_skill($member_cpt_id, $lyric_id);
        }

        // Get recent practice history (log entries)
        // Now passing member_cpt_id as the first argument
        $history_items = $this->get_practice_history($member_cpt_id, $lyric_id, 5);

        ob_start();
        ?>
        <div class="clm-practice-tracker" data-lyric-id="<?php echo esc_attr($lyric_id); ?>">
            <h3><?php _e('Practice Tracker', 'choir-lyrics-manager'); ?></h3>

            <div class="clm-practice-stats-display"> 
                <h4><?php _e('Current Practice Stats for this Lyric', 'choir-lyrics-manager'); ?></h4>
                <p>
                    <strong><?php _e('Total Time:', 'choir-lyrics-manager'); ?></strong>
                    <span class="clm-stat-value total-time">
                        <?php echo $current_skill_data ? esc_html($this->format_duration($current_skill_data->total_practice_minutes)) : __('0 minutes', 'choir-lyrics-manager'); ?>
                    </span>
                </p>
                <p>
                    <strong><?php _e('Sessions:', 'choir-lyrics-manager'); ?></strong>
                    <span class="clm-stat-value sessions-count">
                        <?php echo $current_skill_data ? esc_html($current_skill_data->practice_count) : '0'; ?>
                    </span>
                </p>
                <p>
                    <strong><?php _e('Last Practiced:', 'choir-lyrics-manager'); ?></strong>
                    <span class="clm-stat-value last-practice-date">
                        <?php echo $current_skill_data && $current_skill_data->last_practice_date ? esc_html(date_i18n(get_option('date_format'), strtotime($current_skill_data->last_practice_date))) : __('Never', 'choir-lyrics-manager'); ?>
                    </span>
                </p>
                <p>
                    <strong><?php _e('Last Confidence:', 'choir-lyrics-manager'); ?></strong>
                    <span class="clm-stat-value confidence-stars">
                        <?php
                        $last_confidence = $current_skill_data ? intval($current_skill_data->confidence_rating) : 0;
                        for ($i = 1; $i <= 5; $i++) {
                            echo '<span class="dashicons dashicons-star-' . ($i <= $last_confidence ? 'filled' : 'empty') . '"></span>';
                        }
                        ?>
                    </span>
                </p>
            </div>

            <h4><?php _e('Log New Practice Session', 'choir-lyrics-manager'); ?></h4>
            <div id="clm-practice-form-fields">
                <p>
                    <label for="clm-practice-duration"><?php _e('Duration (minutes):', 'choir-lyrics-manager'); ?></label><br>
                    <input type="number" id="clm-practice-duration" min="1" value="15">
                </p>
                <p>
                    <label for="clm-practice-confidence"><?php _e('Your Confidence (1-5):', 'choir-lyrics-manager'); ?></label><br>
                    <select id="clm-practice-confidence">
                         <?php
                        $confidence_levels = $this->get_confidence_level_options();
                        $default_confidence = 3;

                        foreach ($confidence_levels as $value => $label) {
                            echo "<option value='{$value}'" . selected($value, $default_confidence, false) . ">" . esc_html($label) . "</option>";
                        }
                        ?>
                    </select>
                </p>
                <p>
                    <label for="clm-practice-notes"><?php _e('Notes (optional):', 'choir-lyrics-manager'); ?></label><br>
                    <textarea id="clm-practice-notes" rows="3" placeholder="<?php esc_attr_e('e.g., worked on bridge, timing issues...', 'choir-lyrics-manager'); ?>"></textarea>
                </p>
                <button type="button" id="clm-submit-practice-log" class="clm-button"><?php _e('Log Session', 'choir-lyrics-manager'); ?></button>
            </div>
            <div id="clm-practice-log-message" class="clm-ajax-message" style="display:none;"></div>

            <?php if (!empty($history_items)) : ?>
                <div class="clm-practice-history" style="margin-top: 20px;">
                    <h4><?php _e('Recent Practice History', 'choir-lyrics-manager'); ?></h4>
                    <table class="clm-practice-history-table">
                        <thead>
                            <tr>
                                <th><?php _e('Date', 'choir-lyrics-manager'); ?></th>
                                <th><?php _e('Duration', 'choir-lyrics-manager'); ?></th>
                                <th><?php _e('Confidence', 'choir-lyrics-manager'); ?></th>
                                <th><?php _e('Notes', 'choir-lyrics-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history_items as $log_item) : ?>
                                <tr>
                                    <td><?php echo $log_item['date'] ? esc_html(date_i18n(get_option('date_format'), strtotime($log_item['date']))) : '—'; ?></td>
                                    <td><?php echo $log_item['duration_minutes'] ? sprintf(_n('%d minute', '%d minutes', $log_item['duration_minutes'], 'choir-lyrics-manager'), esc_html($log_item['duration_minutes'])) : '—'; ?></td>
                                    <td>
                                        <?php
                                        $confidence_val = intval($log_item['confidence_rating']);
                                        if ($confidence_val > 0) {
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo '<span class="dashicons dashicons-star-' . ($i <= $confidence_val ? 'filled' : 'empty') . '"></span>';
                                            }
                                        } else {
                                            echo '—';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo !empty($log_item['notes']) ? nl2br(esc_html($log_item['notes'])) : '—'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php // You might want a link to a full history page if $limit is used
                    // if (count($history_items) >= $your_history_limit) echo '<a href="#" class="clm-view-all-history">View All History »</a>';
                    ?>
                </div>
            <?php endif; ?>

            <?php // Nonce field is good for non-JS fallback, but clm_vars handles AJAX nonce
                 // wp_nonce_field('clm_practice_nonce', 'clm_practice_log_nonce_field_for_js');
            ?>
        </div>
        <?php
        return ob_get_clean();
    }
	
	public function get_confidence_level_options() {
        return array(
            1 => __('1 - Just Starting / Unsure', 'choir-lyrics-manager'),
            2 => __('2 - Some Sections Known', 'choir-lyrics-manager'),
            3 => __('3 - Getting There / Mostly Known', 'choir-lyrics-manager'),
            4 => __('4 - Confident / Few Slips', 'choir-lyrics-manager'),
            5 => __('5 - Mastered / Performance Ready', 'choir-lyrics-manager')
        );
    }
    
    /**
     * Format duration in minutes to hours and minutes
     *
     * @since     1.0.0
     * @param     int       $minutes    Duration in minutes.
     * @return    string                Formatted duration.
     */
    public function format_duration($minutes) {
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
     * Render practice stats shortcode
     *
     * @since     1.0.0
     * @param     array     $atts    Shortcode attributes.
     * @return    string             HTML for the practice stats.
     */
    public function render_practice_stats_shortcode($atts) {
        $atts = shortcode_atts(array(
            'lyric_id' => 0,
            'show_history' => 'yes',
        ), $atts);
        
        $lyric_id = intval($atts['lyric_id']);
        
        if (!$lyric_id) {
            return '<p class="clm-error">' . __('Lyric ID is required.', 'choir-lyrics-manager') . '</p>';
        }
        
        if (!is_user_logged_in()) {
            return '<p class="clm-notice">' . __('Please log in to view practice statistics.', 'choir-lyrics-manager') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $stats = $this->get_lyric_practice_stats($lyric_id, $user_id);
        
        $output = '<div class="clm-practice-stats-widget">';
        $output .= '<h3>' . __('Practice Statistics', 'choir-lyrics-manager') . '</h3>';
        
        $output .= '<div class="clm-practice-stats">';
        $output .= '<div class="clm-practice-stat">';
        $output .= '<span class="clm-stat-label">' . __('Total Practice Time', 'choir-lyrics-manager') . '</span>';
        $output .= '<span class="clm-stat-value">' . $this->format_duration($stats['total_time']) . '</span>';
        $output .= '</div>';
        
        $output .= '<div class="clm-practice-stat">';
        $output .= '<span class="clm-stat-label">' . __('Practice Sessions', 'choir-lyrics-manager') . '</span>';
        $output .= '<span class="clm-stat-value">' . $stats['sessions'] . '</span>';
        $output .= '</div>';
        
        $output .= '<div class="clm-practice-stat">';
        $output .= '<span class="clm-stat-label">' . __('Last Practice', 'choir-lyrics-manager') . '</span>';
        $output .= '<span class="clm-stat-value">';
        
        if (!empty($stats['last_practice'])) {
            $output .= date_i18n(get_option('date_format'), strtotime($stats['last_practice']));
        } else {
            $output .= __('Never', 'choir-lyrics-manager');
        }
        
        $output .= '</span>';
        $output .= '</div>';
        
        $output .= '<div class="clm-practice-stat">';
        $output .= '<span class="clm-stat-label">' . __('Confidence Level', 'choir-lyrics-manager') . '</span>';
        $output .= '<span class="clm-stat-value clm-confidence-stars">';
        
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $stats['confidence']) {
                $output .= '<span class="dashicons dashicons-star-filled"></span>';
            } else {
                $output .= '<span class="dashicons dashicons-star-empty"></span>';
            }
        }
        
        $output .= '</span>';
        $output .= '</div>';
        $output .= '</div>';
        
        if ($atts['show_history'] === 'yes') {
            $history = $this->get_practice_history($lyric_id, $user_id, 5);
            
            if (!empty($history)) {
                $output .= '<div class="clm-practice-history">';
                $output .= '<h4>' . __('Recent Practice History', 'choir-lyrics-manager') . '</h4>';
                $output .= '<table class="clm-practice-history-table">';
                $output .= '<thead>';
                $output .= '<tr>';
                $output .= '<th>' . __('Date', 'choir-lyrics-manager') . '</th>';
                $output .= '<th>' . __('Duration', 'choir-lyrics-manager') . '</th>';
                $output .= '<th>' . __('Confidence', 'choir-lyrics-manager') . '</th>';
                $output .= '<th>' . __('Notes', 'choir-lyrics-manager') . '</th>';
                $output .= '</tr>';
                $output .= '</thead>';
                $output .= '<tbody>';
                
                foreach ($history as $log) {
                    $output .= '<tr>';
                    $output .= '<td>' . date_i18n(get_option('date_format'), strtotime($log['date'])) . '</td>';
                    $output .= '<td>' . sprintf(_n('%d minute', '%d minutes', $log['duration'], 'choir-lyrics-manager'), $log['duration']) . '</td>';
                    $output .= '<td>';
                    
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $log['confidence']) {
                            $output .= '<span class="dashicons dashicons-star-filled"></span>';
                        } else {
                            $output .= '<span class="dashicons dashicons-star-empty"></span>';
                        }
                    }
                    
                    $output .= '</td>';
                    $output .= '<td>' . (!empty($log['notes']) ? esc_html($log['notes']) : '—') . '</td>';
                    $output .= '</tr>';
                }
                
                $output .= '</tbody>';
                $output .= '</table>';
                $output .= '</div>';
            }
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Get least practiced lyrics for a user
     *
     * @since     1.0.0
     * @param     int       $user_id     The user ID, defaults to current user.
     * @param     int       $limit       Number of lyrics to return, defaults to 5.
     * @return    array                  Array of lyric objects with practice data.
     */
    public function get_least_practiced_lyrics($user_id = 0, $limit = 5) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return array();
        }
        
        // Get all lyrics user has access to
        $args = array(
            'post_type'      => 'clm_lyric',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );
        
        // Apply role-based filtering if needed
        $user = get_userdata($user_id);
        
        if ($user && !in_array('administrator', (array) $user->roles)) {
            // Filter lyrics based on user's role and capabilities
            // This would depend on your specific access control logic
        }
        
        $all_lyrics = get_posts($args);
        
        if (empty($all_lyrics)) {
            return array();
        }
        
        $practiced_lyrics = get_user_meta($user_id, 'clm_practiced_lyrics', true);
        
        if (!is_array($practiced_lyrics)) {
            $practiced_lyrics = array();
        }
        
        $lyrics_with_practice = array();
        
        // First, collect all lyrics with their practice data
        foreach ($all_lyrics as $lyric) {
            $practice_time = get_user_meta($user_id, 'clm_practice_time_' . $lyric->ID, true);
            $practice_time = intval($practice_time);
            
            $last_practice = get_user_meta($user_id, 'clm_last_practice_' . $lyric->ID, true);
            $last_practice_timestamp = !empty($last_practice) ? strtotime($last_practice) : 0;
            
            $confidence = get_user_meta($user_id, 'clm_confidence_' . $lyric->ID, true);
            $confidence = intval($confidence);
            
            // Calculate priority score - lower is higher priority
            // 1. Not practiced yet (practice_time = 0)
            // 2. Low confidence (1-2)
            // 3. Not practiced recently (older last_practice date)
            // 4. Less total practice time
            
            $priority_score = 0;
            
            if ($practice_time === 0) {
                // Highest priority: never practiced
                $priority_score = 0;
            } else {
                // Base score on confidence (reversed, so lower confidence = lower score = higher priority)
                $priority_score = $confidence > 0 ? 6 - $confidence : 5;
                
                // Adjust based on recency (more recent = higher score = lower priority)
                if ($last_practice_timestamp > 0) {
                    $days_since_practice = floor((time() - $last_practice_timestamp) / (60 * 60 * 24));
                    
                    if ($days_since_practice < 7) {
                        $priority_score += 3; // Practiced very recently
                    } elseif ($days_since_practice < 30) {
                        $priority_score += 1; // Practiced somewhat recently
                    }
                    // Practiced long ago - don't adjust score
                }
                
                // Adjust based on total practice time (more practice = higher score = lower priority)
                if ($practice_time > 180) { // More than 3 hours
                    $priority_score += 2;
                } elseif ($practice_time > 60) { // More than 1 hour
                    $priority_score += 1;
                }
            }
            
            $lyrics_with_practice[] = array(
                'lyric' => $lyric,
                'practice_time' => $practice_time,
                'last_practice' => $last_practice,
                'confidence' => $confidence,
                'priority_score' => $priority_score
            );
        }
        
        // Sort by priority score (lowest first)
        usort($lyrics_with_practice, function($a, $b) {
            return $a['priority_score'] - $b['priority_score'];
        });
        
        // Limit to requested number
        $lyrics_with_practice = array_slice($lyrics_with_practice, 0, $limit);
        
        return $lyrics_with_practice;
    }
    
    /**
     * Render practice suggestions shortcode
     *
     * @since     1.0.0
     * @param     array     $atts    Shortcode attributes.
     * @return    string             HTML for the practice suggestions.
     */
    public function render_practice_suggestions_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 5,
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<p class="clm-notice">' . __('Please log in to view practice suggestions.', 'choir-lyrics-manager') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $limit = intval($atts['limit']);
        $suggestions = $this->get_least_practiced_lyrics($user_id, $limit);
        
        if (empty($suggestions)) {
            return '<p class="clm-notice">' . __('No lyrics available for practice suggestions.', 'choir-lyrics-manager') . '</p>';
        }
        
        $output = '<div class="clm-practice-suggestions">';
        $output .= '<h3>' . __('Practice Suggestions', 'choir-lyrics-manager') . '</h3>';
        $output .= '<p class="clm-suggestions-intro">' . __('Here are some lyrics you might want to practice:', 'choir-lyrics-manager') . '</p>';
        $output .= '<ul class="clm-suggestion-list">';
        
        foreach ($suggestions as $item) {
            $lyric = $item['lyric'];
            $confidence = $item['confidence'];
            $practice_time = $item['practice_time'];
            $last_practice = $item['last_practice'];
            
            $output .= '<li class="clm-suggestion-item">';
            $output .= '<div class="clm-suggestion-title">';
            $output .= '<a href="' . get_permalink($lyric->ID) . '">' . esc_html($lyric->post_title) . '</a>';
            $output .= '</div>';
            
            $output .= '<div class="clm-suggestion-reason">';
            
            if ($practice_time === 0) {
                $output .= '<span class="clm-suggestion-badge clm-badge-new">' . __('New', 'choir-lyrics-manager') . '</span>';
                $output .= __('You haven\'t practiced this lyric yet.', 'choir-lyrics-manager');
            } elseif ($confidence <= 2) {
                $output .= '<span class="clm-suggestion-badge clm-badge-low-confidence">' . __('Low Confidence', 'choir-lyrics-manager') . '</span>';
                $output .= __('You rated your confidence as low.', 'choir-lyrics-manager');
            } elseif (empty($last_practice) || strtotime($last_practice) < strtotime('-30 days')) {
                $output .= '<span class="clm-suggestion-badge clm-badge-not-recent">' . __('Not Recent', 'choir-lyrics-manager') . '</span>';
                $output .= __('You haven\'t practiced this recently.', 'choir-lyrics-manager');
            } else {
                $output .= '<span class="clm-suggestion-badge clm-badge-regular">' . __('Regular Practice', 'choir-lyrics-manager') . '</span>';
                $output .= __('Keep up your regular practice.', 'choir-lyrics-manager');
            }
            
            $output .= '</div>';
            
            $output .= '<div class="clm-suggestion-stats">';
            
            if ($practice_time > 0) {
                $output .= '<span class="clm-suggestion-stat">';
                $output .= '<i class="dashicons dashicons-clock"></i> ';
                $output .= $this->format_duration($practice_time);
                $output .= '</span>';
                
                if (!empty($last_practice)) {
                    $output .= '<span class="clm-suggestion-stat">';
                    $output .= '<i class="dashicons dashicons-calendar-alt"></i> ';
                    $output .= sprintf(__('Last: %s', 'choir-lyrics-manager'), date_i18n(get_option('date_format'), strtotime($last_practice)));
                    $output .= '</span>';
                }
                
                $output .= '<span class="clm-suggestion-stat clm-confidence-stars">';
                
                for ($i = 1; $i <= 5; $i++) {
                    if ($i <= $confidence) {
                        $output .= '<span class="dashicons dashicons-star-filled"></span>';
                    } else {
                        $output .= '<span class="dashicons dashicons-star-empty"></span>';
                    }
                }
                
                $output .= '</span>';
            }
            
            $output .= '</div>';
            
            $output .= '<a href="' . get_permalink($lyric->ID) . '#practice" class="clm-suggestion-action button">' . __('Practice Now', 'choir-lyrics-manager') . '</a>';
            
            $output .= '</li>';
        }
        
        $output .= '</ul>';
        $output .= '</div>';
        
        return $output;
    }
}