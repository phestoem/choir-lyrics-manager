<?php
/**
 * Fixed Skills class for proper directory structure
 *
 * @package    Choir_Lyrics_Manager
 */

class CLM_Skills {

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
     * Create skills tracking database table
     *
     * @since    1.0.0
     */
    public static function create_skills_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clm_member_skills';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            member_id bigint(20) UNSIGNED NOT NULL,
            lyric_id bigint(20) UNSIGNED NOT NULL,
            skill_level varchar(20) NOT NULL DEFAULT 'novice',
            last_practice_date datetime DEFAULT NULL,
            practice_count int(11) NOT NULL DEFAULT 0,
            performance_count int(11) NOT NULL DEFAULT 0,
            total_practice_minutes int(11) NOT NULL DEFAULT 0,
            teacher_notes text,
            assessed_by bigint(20) UNSIGNED DEFAULT NULL,
            assessed_date datetime DEFAULT NULL,
            goal_date date DEFAULT NULL,
            achievement_badges text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY member_lyric (member_id, lyric_id),
            KEY skill_level (skill_level),
            KEY member_id (member_id),
            KEY lyric_id (lyric_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Enqueue admin scripts and styles - FIXED VERSION
     *
     * @since    1.0.0
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'clm-') === false && strpos($hook, 'choir-lyrics-manager') === false) {
            return;
        }
        
        // Enqueue the main plugin admin JS
        wp_enqueue_script(
            $this->plugin_name . '-skills', 
            CLM_PLUGIN_URL . 'includes/js/skills.js', 
            array('jquery'), 
            $this->version, 
            false
        );
        
        // Pass AJAX URL and nonce to the script
        wp_localize_script($this->plugin_name . '-skills', 'clm_skills', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('clm_skills_nonce'),
            'texts' => array(
                'set_goal_title' => __('Set Practice Goal', 'choir-lyrics-manager'),
                'set_goal_description' => __('Choose a target date for mastering this piece:', 'choir-lyrics-manager'),
                'confirm' => __('Set Goal', 'choir-lyrics-manager'),
                'cancel' => __('Cancel', 'choir-lyrics-manager'),
                'saving' => __('Saving...', 'choir-lyrics-manager'),
                'goal_set_success' => __('Goal set successfully!', 'choir-lyrics-manager'),
                'please_select_date' => __('Please select a date', 'choir-lyrics-manager'),
                'goal' => __('Goal', 'choir-lyrics-manager'),
            )
        ));
        
        // Enqueue the main plugin admin CSS
        wp_enqueue_style(
            $this->plugin_name . '-skills', 
            CLM_PLUGIN_URL . 'includes/css/skills.css', 
            array(), 
            $this->version, 
            'all'
        );
    }

    /**
     * Get skill levels
     *
     * @since    1.0.0
     * @return   array    Array of skill levels.
     */
    public function get_skill_levels() {
        return array(
            'novice' => array(
                'label' => __('Novice', 'choir-lyrics-manager'),
                'description' => __('Does not know the piece', 'choir-lyrics-manager'),
                'color' => '#dc3545',
                'icon' => 'dashicons-warning',
                'value' => 1
            ),
            'learning' => array(
                'label' => __('Learning', 'choir-lyrics-manager'),
                'description' => __('Has some knowledge', 'choir-lyrics-manager'),
                'color' => '#ffc107',
                'icon' => 'dashicons-lightbulb',
                'value' => 2
            ),
            'proficient' => array(
                'label' => __('Proficient', 'choir-lyrics-manager'),
                'description' => __('Knows the piece well', 'choir-lyrics-manager'),
                'color' => '#17a2b8',
                'icon' => 'dashicons-yes',
                'value' => 3
            ),
            'master' => array(
                'label' => __('Master', 'choir-lyrics-manager'),
                'description' => __('Has mastered the piece', 'choir-lyrics-manager'),
                'color' => '#28a745',
                'icon' => 'dashicons-star-filled',
                'value' => 4
            )
        );
    }

    /**
     * Add skills tracking meta box to lyrics
     *
     * @since    1.0.0
     */
    public function add_skills_meta_box() {
        add_meta_box(
            'clm_lyric_skills',
            __('Member Skills', 'choir-lyrics-manager'),
            array($this, 'render_lyric_skills_meta_box'),
            'clm_lyric',
            'side',
            'default'
        );
    }

    /**
     * Render lyric skills meta box
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_lyric_skills_meta_box($post) {
        $skills = $this->get_lyric_skills($post->ID);
        $skill_levels = $this->get_skill_levels();
        
        ?>
        <div class="clm-lyric-skills">
            <?php if (empty($skills)): ?>
                <p><?php _e('No members have practiced this lyric yet.', 'choir-lyrics-manager'); ?></p>
            <?php else: ?>
                <div class="clm-skills-summary">
                    <?php
                    $counts = array('novice' => 0, 'learning' => 0, 'proficient' => 0, 'master' => 0);
                    foreach ($skills as $skill) {
                        if (isset($counts[$skill->skill_level])) {
                            $counts[$skill->skill_level]++;
                        }
                    }
                    
                    foreach ($skill_levels as $level => $info) {
                        if ($counts[$level] > 0) {
                            ?>
                            <div class="clm-skill-count" style="color: <?php echo $info['color']; ?>">
                                <span class="dashicons <?php echo $info['icon']; ?>"></span>
                                <strong><?php echo $counts[$level]; ?></strong> <?php echo $info['label']; ?>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
                
                <hr>
                
                <div class="clm-skills-list">
                    <strong><?php _e('Recent Activity:', 'choir-lyrics-manager'); ?></strong>
                    <?php
                    $recent_skills = array_slice($skills, 0, 5);
                    foreach ($recent_skills as $skill): 
                        $level_info = $skill_levels[$skill->skill_level];
                        ?>
                        <div class="clm-skill-item">
                            <span class="dashicons <?php echo $level_info['icon']; ?>" style="color: <?php echo $level_info['color']; ?>"></span>
                            <?php echo esc_html($skill->member_name); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <p class="clm-view-all-skills">
                <a href="<?php echo admin_url('admin.php?page=clm-lyric-skills&lyric_id=' . $post->ID); ?>">
                    <?php _e('View all member skills &raquo;', 'choir-lyrics-manager'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Get all skills for a lyric
     *
     * @since    1.0.0
     * @param    int       $lyric_id    The lyric ID.
     * @return   array                  Array of skill records.
     */
    public function get_lyric_skills($lyric_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clm_member_skills';
        
        $skills = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, p.post_title as member_name 
            FROM $table_name s 
            LEFT JOIN {$wpdb->posts} p ON s.member_id = p.ID 
            WHERE s.lyric_id = %d 
            ORDER BY s.skill_level DESC, p.post_title",
            $lyric_id
        ));
        
        return $skills;
    }

    /**
     * Get all skills for a member
     *
     * @since    1.0.0
     * @param    int       $member_id    The member ID.
     * @return   array                   Array of skill records.
     */
    public function get_member_skills($member_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clm_member_skills';
        
        $skills = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, p.post_title as lyric_title 
            FROM $table_name s 
            LEFT JOIN {$wpdb->posts} p ON s.lyric_id = p.ID 
            WHERE s.member_id = %d 
            ORDER BY p.post_title",
            $member_id
        ));
        
        return $skills;
    }

    /**
     * Get member skill for a specific lyric
     *
     * @since    1.0.0
     * @param    int       $member_id    The member ID.
     * @param    int       $lyric_id     The lyric ID.
     * @return   object|null             The skill record or null.
     */
    public function get_member_skill($member_id, $lyric_id) {
        global $wpdb; 
        
        $table_name = $wpdb->prefix . 'clm_member_skills';
        
        $skill = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE member_id = %d AND lyric_id = %d",
            $member_id,
            $lyric_id
        ));
        
        return $skill;
    }

    /**
     * Update member skill
     *
     * @since    1.0.0
     * @param    int       $member_id      The member ID.
     * @param    int       $lyric_id       The lyric ID.
     * @param    array     $skill_data     Array of skill data to update.
     * @return   bool                      Success or failure.
     */
    public function update_member_skill($member_id, $lyric_id, $skill_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clm_member_skills';
        
        // Check if skill record exists
        $existing = $this->get_member_skill($member_id, $lyric_id);
        
        $data = array(
            'member_id' => $member_id,
            'lyric_id' => $lyric_id,
        );
        
        // Add skill data
        if (isset($skill_data['skill_level'])) {
            $data['skill_level'] = $skill_data['skill_level'];
            
            // Track achievement dates
            if (!$existing || $existing->skill_level !== $skill_data['skill_level']) {
                $achievement_field = 'date_achieved_' . $skill_data['skill_level'];
                if (in_array($achievement_field, array('date_achieved_learning', 'date_achieved_proficient', 'date_achieved_master'))) {
                    $data[$achievement_field] = current_time('mysql');
                }
            }
        }
        
        if (isset($skill_data['last_practice_date'])) {
            $data['last_practice_date'] = $skill_data['last_practice_date'];
        }
        
        if (isset($skill_data['practice_count'])) {
            $data['practice_count'] = $skill_data['practice_count'];
        }
        
        if (isset($skill_data['performance_count'])) {
            $data['performance_count'] = $skill_data['performance_count'];
        }
        
        if (isset($skill_data['teacher_notes'])) {
            $data['teacher_notes'] = $skill_data['teacher_notes'];
        }
        
        if (isset($skill_data['assessed_by'])) {
            $data['assessed_by'] = $skill_data['assessed_by'];
            $data['assessed_date'] = current_time('mysql');
        }
        
        if ($existing) {
            // Update existing record
            $result = $wpdb->update(
                $table_name,
                $data,
                array(
                    'member_id' => $member_id,
                    'lyric_id' => $lyric_id,
                )
            );
        } else {
            // Insert new record
            $result = $wpdb->insert($table_name, $data);
        }
        
        return $result !== false;
    }

    /**
     * Log practice session
     *
     * @since    1.0.0
     * @param    int       $member_id    The member ID.
     * @param    int       $lyric_id     The lyric ID.
     * @return   bool                    Success or failure.
     */
    public function log_practice($member_id, $lyric_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clm_member_skills';
        
        // Get current skill
        $skill = $this->get_member_skill($member_id, $lyric_id);
        
        if ($skill) {
            // Update existing record
            $result = $wpdb->update(
                $table_name,
                array(
                    'last_practice_date' => current_time('mysql'),
                    'practice_count' => $skill->practice_count + 1,
                ),
                array(
                    'member_id' => $member_id,
                    'lyric_id' => $lyric_id,
                )
            );
        } else {
            // Create new record
            $result = $wpdb->insert(
                $table_name,
                array(
                    'member_id' => $member_id,
                    'lyric_id' => $lyric_id,
                    'skill_level' => 'novice',
                    'last_practice_date' => current_time('mysql'),
                    'practice_count' => 1,
                )
            );
        }
        
        return $result !== false;
    }

    /**
     * AJAX handler for updating skill goal
     *
     * @since    1.0.0
     */
    public function ajax_set_skill_goal() {
        if (!check_ajax_referer('clm_skills_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Security verification failed'));
            return;
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in'));
            return;
        }
        
        $skill_id = intval($_POST['skill_id']);
        $goal_date = sanitize_text_field($_POST['goal_date']);
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $goal_date)) {
            wp_send_json_error(array('message' => 'Invalid date format'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'clm_member_skills';
        
        // Verify the skill belongs to the current user
        $skill = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $skill_id
        ));
        
        if (!$skill || $skill->member_id != get_current_user_id()) {
            wp_send_json_error(array('message' => 'Invalid skill'));
            return;
        }
        
        // Update the goal date
        $result = $wpdb->update(
            $table_name,
            array('goal_date' => $goal_date),
            array('id' => $skill_id)
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'Goal set successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to set goal'));
        }
    }

    /**
     * AJAX handler for updating member skills
     *
     * @since    1.0.0
     */
    public function ajax_update_skill() {
        // Check nonce - FIXED NONCE VERIFICATION
        if (!check_ajax_referer('clm_skills_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'choir-lyrics-manager')));
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_clm_members')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'choir-lyrics-manager')));
            return;
        }
        
        $member_id = intval($_POST['member_id']);
        $lyric_id = intval($_POST['lyric_id']);
        $skill_level = sanitize_text_field($_POST['skill_level']);
        
        // Validate skill level
        $valid_levels = array_keys($this->get_skill_levels());
        if (!in_array($skill_level, $valid_levels)) {
            wp_send_json_error(array('message' => __('Invalid skill level.', 'choir-lyrics-manager')));
            return;
        }
        
        // Update skill
        $result = $this->update_member_skill($member_id, $lyric_id, array(
            'skill_level' => $skill_level,
            'assessed_by' => get_current_user_id(),
        ));
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Skill level updated successfully.', 'choir-lyrics-manager'),
                'skill_level' => $skill_level,
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to update skill level.', 'choir-lyrics-manager')));
        }
    }

    /**
     * AJAX handler for logging practice
     *
     * @since    1.0.0
     */
    public function ajax_log_practice() {
        // Check nonce - FIXED NONCE VERIFICATION
        if (!check_ajax_referer('clm_skills_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'choir-lyrics-manager')));
            return;
        }
        
        // Get member ID from current user
        $user_id = get_current_user_id();
        $members_instance = new CLM_Members($this->plugin_name, $this->version);
        $member = $members_instance->get_member_by_user_id($user_id);
        
        if (!$member) {
            wp_send_json_error(array('message' => __('Member profile not found.', 'choir-lyrics-manager')));
            return;
        }
        
        $lyric_id = intval($_POST['lyric_id']);
        
        // Log practice
        $result = $this->log_practice($member->ID, $lyric_id);
        
        if ($result) {
            $skill = $this->get_member_skill($member->ID, $lyric_id);
            wp_send_json_success(array(
                'message' => __('Practice logged successfully.', 'choir-lyrics-manager'),
                'skill' => $skill,
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to log practice.', 'choir-lyrics-manager')));
        }
    }

    /**
     * Get skill statistics for reporting
     *
     * @since    1.0.0
     * @param    array     $filters    Optional filters (member_id, lyric_id, date_from, date_to).
     * @return   array                 Array of statistics.
     */
    public function get_skill_statistics($filters = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'clm_member_skills';
        $where_clauses = array('1=1');
        
        if (!empty($filters['member_id'])) {
            $where_clauses[] = $wpdb->prepare('member_id = %d', $filters['member_id']);
        }
        
        if (!empty($filters['lyric_id'])) {
            $where_clauses[] = $wpdb->prepare('lyric_id = %d', $filters['lyric_id']);
        }
        
        if (!empty($filters['date_from'])) {
            $where_clauses[] = $wpdb->prepare('created_at >= %s', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = $wpdb->prepare('created_at <= %s', $filters['date_to']);
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        // Get overall statistics
        $stats = array();
        
        // Count by skill level
        $skill_counts = $wpdb->get_results(
            "SELECT skill_level, COUNT(*) as count 
            FROM $table_name 
            WHERE $where_sql 
            GROUP BY skill_level"
        );
        
        $stats['skill_distribution'] = array();
        foreach ($skill_counts as $row) {
            $stats['skill_distribution'][$row->skill_level] = intval($row->count);
        }
        
        // Average practice count
        $avg_practice = $wpdb->get_var(
            "SELECT AVG(practice_count) 
            FROM $table_name 
            WHERE $where_sql"
        );
        $stats['average_practice_count'] = round($avg_practice, 2);
        
        // Most practiced songs
        $stats['most_practiced'] = $wpdb->get_results(
            "SELECT lyric_id, SUM(practice_count) as total_practice, 
                    COUNT(*) as member_count 
            FROM $table_name 
            WHERE $where_sql 
            GROUP BY lyric_id 
            ORDER BY total_practice DESC 
            LIMIT 10"
        );
        
        // Recent achievements
        $stats['recent_achievements'] = $wpdb->get_results(
            "SELECT * FROM $table_name 
            WHERE $where_sql 
            AND (
                date_achieved_learning >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                OR date_achieved_proficient >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                OR date_achieved_master >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            )
            ORDER BY updated_at DESC 
            LIMIT 10"
        );
        
        return $stats;
    }

    /**
     * Format minutes to readable duration
     *
     * @since    1.0.0
     * @param    int       $minutes    Number of minutes
     * @return   string                Formatted duration string
     */
    public function format_minutes($minutes) {
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
            __('%1$d hours, %2$d minutes', 'choir-lyrics-manager'),
            $hours,
            $mins
        );
    }

    /**
     * Get recent practice sessions
     *
     * @since    1.0.0
     * @param    int       $member_id    Member ID
     * @param    int       $limit        Maximum number of sessions to return
     * @return   array                   Array of practice sessions
     */
    public function get_recent_practice_sessions($member_id, $limit = 5) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, l.post_title as lyric_title 
            FROM {$wpdb->prefix}clm_practice_stats p
            LEFT JOIN {$wpdb->posts} l ON p.lyric_id = l.ID
            WHERE p.user_id = %d
            ORDER BY p.practice_date DESC
            LIMIT %d",
            $member_id,
            $limit
        ));
    }
}