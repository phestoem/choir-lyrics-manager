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
    private $db_table_name;
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
        global $wpdb;
        $this->db_table_name = $wpdb->prefix . 'clm_member_skills';
    }

        /**
         * Create/Update the clm_member_skills database table on plugin activation.
         */
        public static function create_skills_table() {
            global $wpdb;
            $table_name = $wpdb->prefix . 'clm_member_skills';
            $charset_collate = $wpdb->get_charset_collate();

            // Added performance_count for compatibility with your dashboard template
            $sql = "CREATE TABLE $table_name (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                member_id bigint(20) UNSIGNED NOT NULL,
                lyric_id bigint(20) UNSIGNED NOT NULL,
                skill_level varchar(20) NOT NULL DEFAULT 'novice',
                last_practice_date datetime DEFAULT NULL,
                practice_count int(11) NOT NULL DEFAULT 0,
                total_practice_minutes int(11) NOT NULL DEFAULT 0,
                performance_count int(11) NOT NULL DEFAULT 0,
                confidence_rating tinyint(1) DEFAULT NULL,
                goal_date date DEFAULT NULL,
                notes text DEFAULT NULL,
                assessed_by bigint(20) UNSIGNED DEFAULT NULL,
                assessed_date datetime DEFAULT NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY member_lyric (member_id, lyric_id),
                KEY member_id (member_id),
                KEY lyric_id (lyric_id),
                KEY skill_level (skill_level)
            ) $charset_collate;"; // Semicolon at the end of CREATE TABLE statement is important
        
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            
            //error_log("CLM_DBDELTA_DEBUG: SQL for {$table_name}: " . $sql); // Log the exact SQL
            $dbdelta_results = dbDelta($sql); 
        
            // Enhanced logging for dbDelta results
            if (is_array($dbdelta_results) && !empty($dbdelta_results)) {
                foreach ($dbdelta_results as $delta_message) {
                    // error_log("CLM_DBDELTA_RESULT for {$table_name}: " . $delta_message);
                }
            } elseif (!empty($wpdb->last_error)) {
                // error_log("CLM_DBDELTA_ERROR for {$table_name} (no array result but wpdb error exists): " . $wpdb->last_error);
            } else {
                // error_log("CLM_DBDELTA_INFO: No specific messages returned by dbDelta for {$table_name}, or table is up to date.");
            }
            // You can also log $wpdb->last_query here if issues persist to see what dbDelta tried to run
            // if ($wpdb->last_query) error_log("CLM_DBDELTA_LAST_QUERY: " . $wpdb->last_query);
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
            'unknown'    => array('label' => __('Unknown', 'choir-lyrics-manager'),    'color' => '#95a5a6', 'icon' => 'dashicons-editor-help',  'value' => 0, 'progress' => 0),
            'novice'     => array('label' => __('Novice', 'choir-lyrics-manager'),      'color' => '#e74c3c', 'icon' => 'dashicons-warning',        'value' => 1, 'progress' => 20), // e.g. Red
            'learning'   => array('label' => __('Learning', 'choir-lyrics-manager'),    'color' => '#f39c12', 'icon' => 'dashicons-lightbulb',      'value' => 2, 'progress' => 40), // e.g. Orange
            'proficient' => array('label' => __('Proficient', 'choir-lyrics-manager'),  'color' => '#3498db', 'icon' => 'dashicons-yes-alt',        'value' => 3, 'progress' => 70), // e.g. Blue
            'mastered'   => array('label' => __('Mastered', 'choir-lyrics-manager'),    'color' => '#2ecc71', 'icon' => 'dashicons-star-filled',    'value' => 4, 'progress' => 100) // e.g. Green
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


    public function get_skill_level_info($skill_level_slug) {
        $levels = $this->get_skill_levels();
        return isset($levels[$skill_level_slug]) ? $levels[$skill_level_slug] : $levels['unknown'];
    }

 

	public function update_member_skill_from_practice_session( $member_cpt_id, $lyric_id, $practice_duration_minutes, $practice_confidence ) {
		global $wpdb;
		$now_mysql_gmt = current_time('mysql', 1); // Get GMT time for database

		$current_skill = $this->get_member_skill( $member_cpt_id, $lyric_id );

		if ( $current_skill ) {
			// --- UPDATE existing skill record ---
			$data_to_update = array(
				'last_practice_date' => $now_mysql_gmt,
				'confidence_rating' => $practice_confidence,
				'updated_at' => $now_mysql_gmt,
				'practice_count' => $current_skill->practice_count + 1,
				'total_practice_minutes' => $current_skill->total_practice_minutes + $practice_duration_minutes,
			);
			// Calculate new skill level based on updated counts and current confidence
			$data_to_update['skill_level'] = $this->calculate_new_skill_level(
				$current_skill->skill_level,
				$data_to_update['practice_count'],
				$data_to_update['total_practice_minutes'],
				$practice_confidence // Use the confidence from this session
			);

			// Define formats for the fields being updated
			$update_data_formats = array(
				'%s', // last_practice_date
				'%d', // confidence_rating
				'%s', // updated_at
				'%d', // practice_count
				'%d', // total_practice_minutes
				'%s'  // skill_level
			);
			

			$result = $wpdb->update(
				$this->db_table_name,
				$data_to_update,
				array( 'id' => $current_skill->id ), // WHERE condition
				$update_data_formats,                // Format of data
				array('%d')                          // Format of WHERE clause
			);

		} else {
			// --- INSERT new skill record ---
			$data_to_insert = array(
				'member_id' => $member_cpt_id,
				'lyric_id' => $lyric_id,
				'skill_level' => 'novice', // Initial default before calculation
				'last_practice_date' => $now_mysql_gmt,
				'practice_count' => 1,
				'total_practice_minutes' => $practice_duration_minutes,
				'performance_count' => 0, // Default for new record
				'confidence_rating' => $practice_confidence,
				'created_at' => $now_mysql_gmt,
				'updated_at' => $now_mysql_gmt
				// goal_date, notes, assessed_by, assessed_date will use DB defaults (NULL or as defined)
			);

			// Calculate skill level for the very first entry based on this first practice
			$data_to_insert['skill_level'] = $this->calculate_new_skill_level(
				'novice', // Starting point for calculation
				$data_to_insert['practice_count'],
				$data_to_insert['total_practice_minutes'],
				$practice_confidence
			);
			// $data_to_insert['skill_level'] should now be 'novice' or 'learning'

			// Define formats for the fields being inserted (must match order of keys in $data_to_insert if not named)
			// $wpdb->insert() prefers an associative array for $data, and an indexed array for $format
			// if $format keys don't match $data keys. For clarity, ensure they match or use an indexed $format.
			// The safest way is to provide an indexed format array that matches the column order $wpdb expects
			// or rely on $wpdb to infer if types are simple. For explicit control:
			$insert_data_formats = array(
				'%d', // member_id
				'%d', // lyric_id
				'%s', // skill_level
				'%s', // last_practice_date
				'%d', // practice_count
				'%d', // total_practice_minutes
				'%d', // performance_count
				'%d', // confidence_rating
				'%s', // created_at
				'%s'  // updated_at
			);
			// Note: The $data_to_insert array must have its keys in an order that $wpdb->insert expects
			// if you use an indexed $insert_data_formats. Or, ensure $insert_data_formats is an associative array
			// where keys match $data_to_insert (though $wpdb typically uses indexed for $format).
			// To be very safe with indexed formats, ensure $data_to_insert keys are in a predictable order,
			// or build the $insert_data_formats array by iterating over $data_to_insert keys.

			// For $wpdb->insert, if the $format parameter is an array, its elements are mapped to the columns in $data.
			// It's simpler to just pass the associative $data_to_insert and let $wpdb infer if types are simple,
			// or provide a format array that matches the order of columns in your DB table if $data_to_insert also matches that order.
			// However, passing an associative array for $data and an indexed array for $format is standard.
			// The order of elements in $insert_data_formats must correspond to the order of elements in $data_to_insert
			// as they would be iterated if $data_to_insert were treated as an numerically indexed array (which it's not).
			// This is a common confusion point with $wpdb->insert.

			// Let's simplify and rely on $wpdb's type inference for $data_to_insert,
			// or be very explicit about order if providing $insert_data_formats.
			// To avoid issues, it's often best to pass only $data_to_insert if types are simple,
			// or construct $data_to_insert and $insert_data_formats with absolute matching order.

			// Simpler approach for $wpdb->insert:
			//error_log("CLM_SKILLS_DEBUG: Data for INSERT: " . print_r($data_to_insert, true));
			// If $wpdb->insert is having trouble with formats, remove $insert_data_formats for simpler types,
			// or ensure the format array keys/order perfectly align with $data_to_insert keys/order.
			// $result = $wpdb->insert($this->db_table_name, $data_to_insert, $insert_data_formats);

			// Let's try without explicit formats for insert first, $wpdb is usually good with this.
			// If it fails or inserts wrong types, we'll add the $insert_data_formats array.
			$result = $wpdb->insert($this->db_table_name, $data_to_insert);


			if ( false === $result && !empty($wpdb->last_error) ) { // Check for DB error specifically
				 error_log("CLM_SKILLS_DB_INSERT_ERROR: " . $wpdb->last_error . " | Query: " . $wpdb->last_query . " | Data: " . print_r($data_to_insert, true));
			}
		}

		if ( false === $result ) {
			return new WP_Error(
				'db_error',
				__('Could not update or insert member skill record in the database.', 'choir-lyrics-manager'),
				array('last_db_error' => $wpdb->last_error, 'last_query' => $wpdb->last_query) // Include last query
			);
		}
		return $this->get_member_skill( $member_cpt_id, $lyric_id );
	}



    private function calculate_new_skill_level( $current_level_slug, $practice_count, $total_minutes, $last_confidence ) {
        $levels = $this->get_skill_levels();
        $current_level_value = isset($levels[$current_level_slug]) ? $levels[$current_level_slug]['value'] : 0;
        $new_level_slug = $current_level_slug;

        // Define thresholds for progression (example)
        $thresholds = array(
            'novice' => array('min_sessions' => 1, 'min_confidence' => 2, 'next_level' => 'learning'),
            'learning' => array('min_sessions' => 3, 'min_total_minutes' => 45, 'min_confidence' => 3, 'next_level' => 'proficient'),
            'proficient' => array('min_sessions' => 5, 'min_total_minutes' => 90, 'min_confidence' => 4, 'next_level' => 'mastered'),
            'mastered' => null, // Top level
            'unknown' => array('min_sessions' => 1, 'min_confidence' => 1, 'next_level' => 'novice'), // From unknown to novice
        );

        if (isset($thresholds[$current_level_slug]) && $thresholds[$current_level_slug] !== null) {
            $rules = $thresholds[$current_level_slug];
            $can_progress = true;

            if (isset($rules['min_sessions']) && $practice_count < $rules['min_sessions']) $can_progress = false;
            if (isset($rules['min_total_minutes']) && $total_minutes < $rules['min_total_minutes']) $can_progress = false;
            if (isset($rules['min_confidence']) && $last_confidence < $rules['min_confidence']) $can_progress = false;

            if ($can_progress) {
                $new_level_slug = $rules['next_level'];
            }
        }
		
		// Ensure we always return a valid slug, not an integer or the $current_level_value
		// If $new_level_slug is still, for instance, 'unknown' from an initial state, that's fine.
		// But if $current_level_slug was invalid and $new_level_slug didn't change, we need a default.
		if (!isset($levels[$new_level_slug])) {
			return 'novice'; // Default to 'novice' if something went very wrong or current_level_slug was invalid
		}
		
        return $new_level_slug;
    }

    public function ajax_set_lyric_skill_goal() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'choir-lyrics-manager' ) ) );
            return;
        }
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['nonce'])), 'clm_skills_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed (nonce).', 'choir-lyrics-manager' ) ) );
            return;
        }
        if ( empty( $_POST['lyric_id'] ) || empty( $_POST['goal_date'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Lyric ID and Goal Date are required.', 'choir-lyrics-manager' ) ) );
            return;
        }

        $wp_user_id = get_current_user_id();
        $lyric_id   = intval( $_POST['lyric_id'] );
        $goal_date_str  = sanitize_text_field( $_POST['goal_date'] );

        if ( ! preg_match( "/^\d{4}-\d{2}-\d{2}$/", $goal_date_str ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid goal date format. Please use YYYY-MM-DD.', 'choir-lyrics-manager' ) ) );
            return;
        }
        if (get_post_type($lyric_id) !== 'clm_lyric' || get_post_status($lyric_id) !== 'publish') {
             wp_send_json_error(array('message' => __('Invalid or non-published Lyric ID.', 'choir-lyrics-manager')));
             return;
        }

        $member_cpt_id = null;
        if (class_exists('CLM_Members')) {
            $members_manager = new CLM_Members($this->plugin_name, $this->version);
            $member_cpt_id = $members_manager->get_member_cpt_id_by_user_id($wp_user_id);
        }
        if ( ! $member_cpt_id ) {
            wp_send_json_error( array( 'message' => __( 'Associated member profile not found.', 'choir-lyrics-manager' ) ) );
            return;
        }

        global $wpdb;
        $now_mysql_gmt = current_time('mysql', 1);
        $current_skill = $this->get_member_skill( $member_cpt_id, $lyric_id );

        if ( $current_skill ) {
            $result = $wpdb->update(
                $this->db_table_name,
                array( 'goal_date' => $goal_date_str, 'updated_at' => $now_mysql_gmt ),
                array( 'id' => $current_skill->id ),
                array( '%s', '%s' ),
                array( '%d' )
            );
        } else {
            $result = $wpdb->insert(
                $this->db_table_name,
                array(
                    'member_id'  => $member_cpt_id,
                    'lyric_id'   => $lyric_id,
                    'skill_level'=> 'novice',
                    'goal_date'  => $goal_date_str,
                    'created_at' => $now_mysql_gmt,
                    'updated_at' => $now_mysql_gmt,
                    'performance_count' => 0, // Default for new record
                ),
                array('%d', '%d', '%s', '%s', '%s', '%s', '%d')
            );
        }

        if ( false === $result ) {
            wp_send_json_error( array( 'message' => __( 'Failed to set skill goal.', 'choir-lyrics-manager' ), 'db_error' => $wpdb->last_error ) );
        } else {
            wp_send_json_success( array( 'message' => __( 'Skill goal updated successfully.', 'choir-lyrics-manager' ), 'goal_date' => $goal_date_str ) );
        }
    }

  

    public function get_member_skills_with_lyric_titles( $member_cpt_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT ms.*, p.post_title as lyric_title
             FROM {$this->db_table_name} ms
             JOIN {$wpdb->posts} p ON ms.lyric_id = p.ID AND p.post_type = 'clm_lyric' AND p.post_status = 'publish'
             WHERE ms.member_id = %d
             ORDER BY p.post_title ASC",
            $member_cpt_id
        ) );
    }
    
    public function get_lyric_skill_distribution( $lyric_id ) {
        global $wpdb;
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT skill_level, COUNT(id) as count
             FROM {$this->db_table_name}
             WHERE lyric_id = %d
             GROUP BY skill_level",
            $lyric_id
        ), ARRAY_A );

        $distribution = array();
        foreach ($this->get_skill_levels() as $slug => $info) {
            $distribution[$slug] = array('label' => $info['label'], 'count' => 0, 'color' => $info['color'], 'icon' => $info['icon'], 'progress' => $info['progress']);
        }
        foreach ($results as $row) {
            if (isset($distribution[$row['skill_level']])) {
                $distribution[$row['skill_level']]['count'] = (int) $row['count'];
            }
        }
        return $distribution;
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
     * Render the skill status widget for a given lyric for the current user (frontend).
     *
     * @param int $lyric_id The ID of the lyric.
     * @return string HTML output for the widget.
     */
    public function render_skill_widget($lyric_id) {
        if (!is_user_logged_in()) {
            // Optionally return a message or empty string if user not logged in
            // return '<p class="clm-notice">' . __('Please log in to view your skill status.', 'choir-lyrics-manager') . '</p>';
            return ''; // Or simply return nothing if it shouldn't show for guests
        }
        if (!class_exists('CLM_Members')) {
            error_log('CLM_Skills::render_skill_widget - CLM_Members class not found.');
            return '<p class="clm-error">Member system not available.</p>';
        }

        $wp_user_id = get_current_user_id();
        $members_manager = new CLM_Members($this->plugin_name, $this->version);
        $member_cpt_id = $members_manager->get_member_cpt_id_by_user_id($wp_user_id);

        if (!$member_cpt_id) {
            // This message might be better placed in the template that calls this widget
            // return '<p class="clm-notice">' . __('Associated member profile not found. Cannot display skill status.', 'choir-lyrics-manager') . '</p>';
            return ''; // Or return a specific placeholder if member profile is required.
        }

        $skill = $this->get_member_skill($member_cpt_id, $lyric_id);
		
        $skill_info = $skill ? $this->get_skill_level_info($skill->skill_level) : $this->get_skill_level_info('novice');
        $goal_date_formatted = $skill && $skill->goal_date ? date_i18n(get_option('date_format'), strtotime($skill->goal_date)) : '';
        
        // Prepare default values for the goal date input
        $default_goal_date_input = $skill && $skill->goal_date ? $skill->goal_date : date('Y-m-d', strtotime('+1 month'));


        ob_start();
        ?>
        <div class="clm-skill-status-widget" data-lyric-id="<?php echo esc_attr($lyric_id); ?>">
            <?php /* The <h4>Your Skill Level</h4> can be added by the parent template if preferred */ ?>
            <div class="clm-current-skill-display">
                <span class="clm-skill-badge" 
                      style="background-color:<?php echo esc_attr($skill_info['color']); ?>; color:white; padding: 5px 10px; display:inline-block; border-radius:4px; margin-right: 10px;">
                    <span class="dashicons <?php echo esc_attr($skill_info['icon']); ?>"></span>
                    <?php echo esc_html($skill_info['label']); ?>
                </span>

                <?php if ($skill && $skill->goal_date): ?>
                    <span class="clm-skill-goal-date-container">
                        <strong><?php _e('Goal:', 'choir-lyrics-manager'); ?></strong>
                        <span class="clm-skill-goal-date-display"><?php echo esc_html($goal_date_formatted); ?></span>
                    </span>
                <?php endif; ?>
            </div>

            <div class="clm-skill-details-for-widget" style="font-size:0.9em; margin-top:5px;">
                <?php if ($skill) : ?>
                    <span><?php printf(_n('%s session', '%s sessions', $skill->practice_count, 'choir-lyrics-manager'), esc_html($skill->practice_count)); ?></span>
                    | <span><?php echo esc_html($this->format_minutes($skill->total_practice_minutes)); ?> <?php _e('total', 'choir-lyrics-manager'); ?></span>
                <?php else: ?>
                    <span><?php _e('No practice logged for this skill yet.', 'choir-lyrics-manager'); ?></span>
                <?php endif; ?>
            </div>
            
            <button type="button" 
                    class="clm-set-skill-goal-button clm-button clm-button-small" 
                    data-lyric-id="<?php echo esc_attr($lyric_id); ?>" 
                    data-current-goal="<?php echo esc_attr($skill ? $skill->goal_date : ''); ?>"
                    style="margin-top: 10px;">
                <?php echo ($skill && $skill->goal_date) ? esc_html__('Change Goal', 'choir-lyrics-manager') : esc_html__('Set Goal', 'choir-lyrics-manager'); ?>
            </button>

            <div id="clm-set-goal-form-container-<?php echo esc_attr($lyric_id); ?>" class="clm-set-goal-form" style="display:none; margin-top:10px; padding:10px; border:1px solid #eee; background:#f9f9f9;">
                <p>
                    <label for="clm-goal-date-input-<?php echo esc_attr($lyric_id); ?>"><?php _e('New Goal Date:', 'choir-lyrics-manager'); ?></label><br>
                    <input type="date" id="clm-goal-date-input-<?php echo esc_attr($lyric_id); ?>" value="<?php echo esc_attr($default_goal_date_input); ?>" min="<?php echo date('Y-m-d'); ?>">
                </p>
                <button type="button" class="clm-submit-new-goal clm-button clm-button-primary clm-button-small" data-lyric-id="<?php echo esc_attr($lyric_id); ?>"><?php _e('Save Goal', 'choir-lyrics-manager'); ?></button>
                <button type="button" class="clm-cancel-new-goal clm-button-text"><?php _e('Cancel', 'choir-lyrics-manager'); ?></button>
                <div class="clm-set-goal-message" style="display:none; margin-top:5px;"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // Make sure format_minutes method is present in this class if called with $this->format_minutes
    // Or, if it's in CLM_Practice, call it via an instance of CLM_Practice.
    // For now, let's assume it's also in CLM_Skills for simplicity of this widget.
    /**
     * Format minutes to readable duration
     * (Duplicate from CLM_Practice for standalone use here, consider a utility class)
     */
    public function format_minutes($minutes) {
        $minutes = intval($minutes);
        if ($minutes < 1) return __('0 minutes', 'choir-lyrics-manager');
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
  public function get_member_skill( $member_cpt_id, $lyric_id ) { // Parameter $member_cpt_id
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->db_table_name} WHERE member_id = %d AND lyric_id = %d", // Uses $this->db_table_name
            $member_cpt_id,
            $lyric_id
        ) );
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
        $stats['recent_achievements'] = $wpdb->get_results( $wpdb->prepare(
            "SELECT s.*, m.post_title as member_name, l.post_title as lyric_title
            FROM {$this->db_table_name} s
            LEFT JOIN {$wpdb->posts} m ON s.member_id = m.ID
            LEFT JOIN {$wpdb->posts} l ON s.lyric_id = l.ID
            WHERE {$where_sql} 
            AND s.updated_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            ORDER BY s.updated_at DESC
            LIMIT 10",
            30 // Get skills updated in the last 30 days
        ) );
        
        return $stats;
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